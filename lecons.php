<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
exigerRole('enseignant');

$enseignantId = $_SESSION['user_id'];
$erreur = '';
$succes = '';

$coursId = (int)($_GET['cours_id'] ?? 0);

// Vérifier que le cours appartient à l'enseignant
$stmt = $pdo->prepare("SELECT c.*, m.titre AS module_titre FROM cours c JOIN modules m ON m.id = c.module_id WHERE c.id = ? AND c.enseignant_id = ?");
$stmt->execute([$coursId, $enseignantId]);
$cours = $stmt->fetch();

if (!$cours) {
    header('Location: cours.php');
    exit;
}

// --- Ajout d'une leçon (avec upload de fichier) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'ajouter_lecon') {
    $titre = trim($_POST['titre'] ?? '');
    $type = $_POST['type_contenu'] ?? '';

    if ($titre === '' || !in_array($type, ['pdf', 'video'], true)) {
        $erreur = "Le titre et le type de contenu sont obligatoires.";
    } elseif (!isset($_FILES['fichier']) || $_FILES['fichier']['error'] !== UPLOAD_ERR_OK) {
        $erreur = "Merci de sélectionner un fichier valide.";
    } else {
        $fichier = $_FILES['fichier'];
        $extension = strtolower(pathinfo($fichier['name'], PATHINFO_EXTENSION));

        $extensionsAutorisees = $type === 'pdf' ? ['pdf'] : ['mp4', 'webm', 'ogg'];

        if (!in_array($extension, $extensionsAutorisees, true)) {
            $erreur = "Extension de fichier non autorisée pour ce type de contenu (" . implode(', ', $extensionsAutorisees) . ").";
        } else {
            $dossier = $type === 'pdf' ? 'uploads/pdf' : 'uploads/video';
            $nomFichier = 'lecon_' . $coursId . '_' . time() . '.' . $extension;
            $cheminComplet = __DIR__ . '/../' . $dossier . '/' . $nomFichier;
            $cheminRelatif = $dossier . '/' . $nomFichier;

            if (move_uploaded_file($fichier['tmp_name'], $cheminComplet)) {
                // Déterminer l'ordre
                $stmtOrdre = $pdo->prepare("SELECT COALESCE(MAX(ordre), 0) + 1 FROM lecons WHERE cours_id = ?");
                $stmtOrdre->execute([$coursId]);
                $ordre = $stmtOrdre->fetchColumn();

                $stmtInsert = $pdo->prepare("INSERT INTO lecons (cours_id, titre, type_contenu, chemin_fichier, ordre) VALUES (?, ?, ?, ?, ?)");
                $stmtInsert->execute([$coursId, $titre, $type, $cheminRelatif, $ordre]);

                $succes = "Leçon ajoutée avec succès. Vous pouvez maintenant créer son évaluation.";
            } else {
                $erreur = "Erreur lors de l'enregistrement du fichier.";
            }
        }
    }
}

// --- Suppression d'une leçon ---
if (isset($_GET['supprimer_lecon'])) {
    $leconId = (int)$_GET['supprimer_lecon'];
    $stmt = $pdo->prepare("SELECT l.* FROM lecons l WHERE l.id = ? AND l.cours_id = ?");
    $stmt->execute([$leconId, $coursId]);
    $lecon = $stmt->fetch();
    if ($lecon) {
        @unlink(__DIR__ . '/../' . $lecon['chemin_fichier']);
        $stmt = $pdo->prepare("DELETE FROM lecons WHERE id = ?");
        $stmt->execute([$leconId]);
        $succes = "Leçon supprimée.";
    }
}

// --- Création / mise à jour de l'évaluation (questions + réponses) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'enregistrer_evaluation') {
    $leconId = (int)$_POST['lecon_id'];

    // Vérifier appartenance
    $stmt = $pdo->prepare("SELECT id FROM lecons WHERE id = ? AND cours_id = ?");
    $stmt->execute([$leconId, $coursId]);
    if (!$stmt->fetch()) {
        $erreur = "Leçon invalide.";
    } else {
        // Récupérer ou créer l'évaluation
        $stmt = $pdo->prepare("SELECT id FROM evaluations WHERE lecon_id = ?");
        $stmt->execute([$leconId]);
        $evaluationId = $stmt->fetchColumn();

        if (!$evaluationId) {
            $titreEval = trim($_POST['titre_evaluation'] ?? 'Évaluation');
            $stmt = $pdo->prepare("INSERT INTO evaluations (lecon_id, titre) VALUES (?, ?)");
            $stmt->execute([$leconId, $titreEval !== '' ? $titreEval : 'Évaluation']);
            $evaluationId = $pdo->lastInsertId();
        } else {
            $titreEval = trim($_POST['titre_evaluation'] ?? '');
            if ($titreEval !== '') {
                $stmt = $pdo->prepare("UPDATE evaluations SET titre = ? WHERE id = ?");
                $stmt->execute([$titreEval, $evaluationId]);
            }
            // Supprimer les anciennes questions pour les recréer
            $stmt = $pdo->prepare("DELETE FROM questions WHERE evaluation_id = ?");
            $stmt->execute([$evaluationId]);
        }

        // Insertion des nouvelles questions/réponses
        $enonces = $_POST['question'] ?? [];
        $pointsList = $_POST['points'] ?? [];
        $reponsesList = $_POST['reponses'] ?? [];
        $correcteList = $_POST['correcte'] ?? [];

        $ordre = 1;
        foreach ($enonces as $i => $enonce) {
            $enonce = trim($enonce);
            if ($enonce === '') continue;

            $points = (int)($pointsList[$i] ?? 1);
            $stmt = $pdo->prepare("INSERT INTO questions (evaluation_id, enonce, points, ordre) VALUES (?, ?, ?, ?)");
            $stmt->execute([$evaluationId, $enonce, max(1, $points), $ordre]);
            $questionId = $pdo->lastInsertId();

            $reps = $reponsesList[$i] ?? [];
            $correcte = (int)($correcteList[$i] ?? -1);

            foreach ($reps as $j => $texteReponse) {
                $texteReponse = trim($texteReponse);
                if ($texteReponse === '') continue;
                $estCorrecte = ((int)$j === $correcte) ? 1 : 0;
                $stmt = $pdo->prepare("INSERT INTO reponses (question_id, texte, est_correcte) VALUES (?, ?, ?)");
                $stmt->execute([$questionId, $texteReponse, $estCorrecte]);
            }
            $ordre++;
        }

        $succes = "Évaluation enregistrée avec succès.";
    }
}

// Liste des leçons avec leur évaluation (s'il y en a)
$stmt = $pdo->prepare("
    SELECT l.*, e.id AS evaluation_id, e.titre AS evaluation_titre,
        (SELECT COUNT(*) FROM questions q WHERE q.evaluation_id = e.id) AS nb_questions
    FROM lecons l
    LEFT JOIN evaluations e ON e.lecon_id = l.id
    WHERE l.cours_id = ?
    ORDER BY l.ordre
");
$stmt->execute([$coursId]);
$lecons = $stmt->fetchAll();

// Pour la leçon en édition (évaluation), charger questions/réponses existantes
$leconEdition = (int)($_GET['edition'] ?? 0);
$questionsExistantes = [];
if ($leconEdition) {
    $stmt = $pdo->prepare("SELECT id FROM evaluations WHERE lecon_id = ?");
    $stmt->execute([$leconEdition]);
    $evalId = $stmt->fetchColumn();
    if ($evalId) {
        $stmt = $pdo->prepare("SELECT * FROM questions WHERE evaluation_id = ? ORDER BY ordre");
        $stmt->execute([$evalId]);
        $questions = $stmt->fetchAll();
        foreach ($questions as &$q) {
            $stmtR = $pdo->prepare("SELECT * FROM reponses WHERE question_id = ? ORDER BY id");
            $stmtR->execute([$q['id']]);
            $q['reponses'] = $stmtR->fetchAll();
        }
        $questionsExistantes = $questions;
    }
}

$pageTitle = "Leçons & évaluations";
$activePage = "lecons";
include __DIR__ . '/../includes/header.php';
?>

<div class="app-shell">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header">
            <div class="eyebrow">Espace enseignant · <?= htmlspecialchars($cours['module_titre']) ?></div>
            <h1><?= htmlspecialchars($cours['titre']) ?></h1>
            <p><?= htmlspecialchars($cours['description']) ?></p>
            <a href="cours.php" class="btn btn-ghost btn-sm">← Retour à mes cours</a>
        </div>

        <?php if ($erreur): ?><div class="alert alert-error"><?= htmlspecialchars($erreur) ?></div><?php endif; ?>
        <?php if ($succes): ?><div class="alert alert-success"><?= htmlspecialchars($succes) ?></div><?php endif; ?>

        <div style="display:grid; grid-template-columns: 1fr 1.6fr; gap:24px; align-items:start;">
            <!-- Formulaire ajout leçon -->
            <div class="card">
                <h3>Ajouter une leçon</h3>
                <form method="POST" action="lecons.php?cours_id=<?= $coursId ?>" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="ajouter_lecon">
                    <div class="form-group">
                        <label for="titre">Titre de la leçon</label>
                        <input type="text" id="titre" name="titre" required placeholder="Ex : Leçon 1 : Introduction">
                    </div>
                    <div class="form-group">
                        <label for="type_contenu">Type de contenu</label>
                        <select id="type_contenu" name="type_contenu" required>
                            <option value="pdf">Document PDF</option>
                            <option value="video">Vidéo</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="fichier">Fichier (PDF ou vidéo .mp4/.webm/.ogg)</label>
                        <input type="file" id="fichier" name="fichier" required>
                    </div>
                    <button type="submit" class="btn btn-thread btn-block">Ajouter la leçon</button>
                </form>
            </div>

            <!-- Liste des leçons -->
            <div>
                <?php if (empty($lecons)): ?>
                    <div class="empty-state card">
                        <h3>Aucune leçon pour ce cours</h3>
                        <p>Ajoutez une première leçon (PDF ou vidéo) à l'aide du formulaire.</p>
                    </div>
                <?php else: ?>
                    <div class="lesson-path">
                        <?php foreach ($lecons as $i => $l): ?>
                            <div class="lesson-node <?= $l['evaluation_id'] ? 'done' : '' ?>">
                                <div class="node-dot"><?= $i + 1 ?></div>
                                <div class="node-body card" style="margin-bottom:0;">
                                    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px;">
                                        <div>
                                            <h4>
                                                <?= htmlspecialchars($l['titre']) ?>
                                                <span class="badge badge-<?= $l['type_contenu'] ?>"><?= strtoupper($l['type_contenu']) ?></span>
                                            </h4>
                                            <div class="node-meta">
                                                <a href="/LMS-ARIANE/<?= htmlspecialchars($l['chemin_fichier']) ?>" target="_blank">Voir le fichier</a>
                                                ·
                                                <?php if ($l['evaluation_id']): ?>
                                                    <span class="badge badge-success"><?= $l['nb_questions'] ?> question(s)</span>
                                                <?php else: ?>
                                                    <span class="badge badge-pending">Pas d'évaluation</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div style="display:flex; gap:8px; flex-shrink:0;">
                                            <a href="lecons.php?cours_id=<?= $coursId ?>&edition=<?= $l['id'] ?>#evaluation" class="btn btn-primary btn-sm">
                                                <?= $l['evaluation_id'] ? 'Modifier l\'éval.' : 'Créer l\'éval.' ?>
                                            </a>
                                            <a href="lecons.php?cours_id=<?= $coursId ?>&supprimer_lecon=<?= $l['id'] ?>" class="btn btn-ghost btn-sm" onclick="return confirm('Supprimer cette leçon ?');">Suppr.</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Éditeur d'évaluation -->
        <?php if ($leconEdition): ?>
            <?php
            $leconActuelle = null;
            foreach ($lecons as $l) {
                if ($l['id'] === $leconEdition) { $leconActuelle = $l; break; }
            }
            ?>
            <?php if ($leconActuelle): ?>
                <div id="evaluation" class="card" style="margin-top:32px;">
                    <h2>Évaluation — <?= htmlspecialchars($leconActuelle['titre']) ?></h2>
                    <p style="color:var(--ariane-text-muted);">Ajoutez des questions à choix unique. Cochez la bonne réponse pour chaque question.</p>

                    <form method="POST" action="lecons.php?cours_id=<?= $coursId ?>&edition=<?= $leconEdition ?>#evaluation" id="quizForm">
                        <input type="hidden" name="action" value="enregistrer_evaluation">
                        <input type="hidden" name="lecon_id" value="<?= $leconEdition ?>">

                        <div class="form-group">
                            <label for="titre_evaluation">Titre de l'évaluation</label>
                            <input type="text" id="titre_evaluation" name="titre_evaluation"
                                   value="<?= htmlspecialchars($leconActuelle['evaluation_titre'] ?? ('Évaluation : ' . $leconActuelle['titre'])) ?>">
                        </div>

                        <div id="questionsContainer">
                            <?php if (!empty($questionsExistantes)): ?>
                                <?php foreach ($questionsExistantes as $qi => $q): ?>
                                    <div class="quiz-question" data-question>
                                        <h4>Question <?= $qi + 1 ?>
                                            <button type="button" class="btn btn-ghost btn-sm" onclick="this.closest('[data-question]').remove()" style="float:right;">Retirer</button>
                                        </h4>
                                        <div class="form-group">
                                            <label>Énoncé</label>
                                            <input type="text" name="question[]" value="<?= htmlspecialchars($q['enonce']) ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Points</label>
                                            <input type="number" name="points[]" value="<?= $q['points'] ?>" min="1" style="max-width:120px;">
                                        </div>
                                        <label>Propositions (cochez la bonne réponse)</label>
                                        <?php for ($r = 0; $r < 4; $r++): ?>
                                            <?php $rep = $q['reponses'][$r] ?? null; ?>
                                            <div class="quiz-option">
                                                <input type="radio" name="correcte[<?= $qi ?>]" value="<?= $r ?>" <?= ($rep && $rep['est_correcte']) ? 'checked' : '' ?>>
                                                <input type="text" name="reponses[<?= $qi ?>][]" value="<?= $rep ? htmlspecialchars($rep['texte']) : '' ?>" placeholder="Proposition <?= $r + 1 ?>" style="border:none; flex:1; padding:4px;">
                                            </div>
                                        <?php endfor; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="quiz-question" data-question>
                                    <h4>Question 1
                                        <button type="button" class="btn btn-ghost btn-sm" onclick="this.closest('[data-question]').remove()" style="float:right;">Retirer</button>
                                    </h4>
                                    <div class="form-group">
                                        <label>Énoncé</label>
                                        <input type="text" name="question[]" required placeholder="Saisissez la question...">
                                    </div>
                                    <div class="form-group">
                                        <label>Points</label>
                                        <input type="number" name="points[]" value="1" min="1" style="max-width:120px;">
                                    </div>
                                    <label>Propositions (cochez la bonne réponse)</label>
                                    <?php for ($r = 0; $r < 4; $r++): ?>
                                        <div class="quiz-option">
                                            <input type="radio" name="correcte[0]" value="<?= $r ?>">
                                            <input type="text" name="reponses[0][]" placeholder="Proposition <?= $r + 1 ?>" style="border:none; flex:1; padding:4px;">
                                        </div>
                                    <?php endfor; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <button type="button" class="btn btn-ghost" id="addQuestionBtn">+ Ajouter une question</button>
                        <hr style="border:none; border-top:1px solid var(--ariane-border); margin:20px 0;">
                        <button type="submit" class="btn btn-thread">Enregistrer l'évaluation</button>
                    </form>
                </div>

                <!-- Template pour nouvelle question (JS) -->
                <template id="questionTemplate">
                    <div class="quiz-question" data-question>
                        <h4>Question
                            <button type="button" class="btn btn-ghost btn-sm" onclick="this.closest('[data-question]').remove()" style="float:right;">Retirer</button>
                        </h4>
                        <div class="form-group">
                            <label>Énoncé</label>
                            <input type="text" name="question[]" required placeholder="Saisissez la question...">
                        </div>
                        <div class="form-group">
                            <label>Points</label>
                            <input type="number" name="points[]" value="1" min="1" style="max-width:120px;">
                        </div>
                        <label>Propositions (cochez la bonne réponse)</label>
                        <div class="quiz-option">
                            <input type="radio" name="correcte[__INDEX__]" value="0">
                            <input type="text" name="reponses[__INDEX__][]" placeholder="Proposition 1" style="border:none; flex:1; padding:4px;">
                        </div>
                        <div class="quiz-option">
                            <input type="radio" name="correcte[__INDEX__]" value="1">
                            <input type="text" name="reponses[__INDEX__][]" placeholder="Proposition 2" style="border:none; flex:1; padding:4px;">
                        </div>
                        <div class="quiz-option">
                            <input type="radio" name="correcte[__INDEX__]" value="2">
                            <input type="text" name="reponses[__INDEX__][]" placeholder="Proposition 3" style="border:none; flex:1; padding:4px;">
                        </div>
                        <div class="quiz-option">
                            <input type="radio" name="correcte[__INDEX__]" value="3">
                            <input type="text" name="reponses[__INDEX__][]" placeholder="Proposition 4" style="border:none; flex:1; padding:4px;">
                        </div>
                    </div>
                </template>

                <script src="/LMS-ARIANE/assets/js/quiz-builder.js"></script>
            <?php endif; ?>
        <?php endif; ?>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
