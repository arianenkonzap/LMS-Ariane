<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
exigerRole('etudiant');

$etudiantId = $_SESSION['user_id'];
$leconId = (int)($_GET['id'] ?? 0);

// Récupérer la leçon, le cours et le module
$stmt = $pdo->prepare("
    SELECT l.*, c.module_id, c.titre AS cours_titre, m.titre AS module_titre,
        e.id AS evaluation_id, e.titre AS evaluation_titre
    FROM lecons l
    JOIN cours c ON c.id = l.cours_id
    JOIN modules m ON m.id = c.module_id
    LEFT JOIN evaluations e ON e.lecon_id = l.id
    WHERE l.id = ?
");
$stmt->execute([$leconId]);
$lecon = $stmt->fetch();

if (!$lecon) {
    header('Location: modules.php');
    exit;
}

// Vérifier que la leçon précédente est complétée (verrouillage séquentiel)
$stmt = $pdo->prepare("SELECT * FROM lecons WHERE cours_id = (SELECT cours_id FROM lecons WHERE id = ?) AND ordre < ? ORDER BY ordre DESC LIMIT 1");
$stmt->execute([$leconId, $lecon['ordre']]);
$leconPrecedente = $stmt->fetch();

if ($leconPrecedente) {
    $stmt = $pdo->prepare("SELECT id FROM progression WHERE etudiant_id = ? AND lecon_id = ?");
    $stmt->execute([$etudiantId, $leconPrecedente['id']]);
    if (!$stmt->fetch()) {
        header('Location: modules.php?id=' . $lecon['module_id']);
        exit;
    }
}

// Progression existante pour cette leçon
$stmt = $pdo->prepare("SELECT * FROM progression WHERE etudiant_id = ? AND lecon_id = ?");
$stmt->execute([$etudiantId, $leconId]);
$progressionExistante = $stmt->fetch();

// Charger les questions/réponses de l'évaluation
$questions = [];
if ($lecon['evaluation_id']) {
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE evaluation_id = ? ORDER BY ordre");
    $stmt->execute([$lecon['evaluation_id']]);
    $questions = $stmt->fetchAll();
    foreach ($questions as &$q) {
        $stmt2 = $pdo->prepare("SELECT id, texte FROM reponses WHERE question_id = ? ORDER BY id");
        $stmt2->execute([$q['id']]);
        $q['reponses'] = $stmt2->fetchAll();
    }
}

$pageTitle = $lecon['titre'];
$activePage = "modules";
include __DIR__ . '/../includes/header.php';
?>

<div class="app-shell">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header">
            <div class="eyebrow">
                <a href="modules.php?id=<?= $lecon['module_id'] ?>">← <?= htmlspecialchars($lecon['module_titre']) ?></a>
                · <?= htmlspecialchars($lecon['cours_titre']) ?>
            </div>
            <h1><?= htmlspecialchars($lecon['titre']) ?></h1>
        </div>

        <!-- Contenu de la leçon -->
        <div class="card" style="margin-bottom:24px;">
            <?php if ($lecon['type_contenu'] === 'pdf'): ?>
                <embed src="/LMS-ARIANE/<?= htmlspecialchars($lecon['chemin_fichier']) ?>" type="application/pdf" width="100%" height="600px" style="border-radius: var(--radius-sm); border:1px solid var(--ariane-border);">
                <p style="margin-top:10px;"><a href="/LMS-ARIANE/<?= htmlspecialchars($lecon['chemin_fichier']) ?>" target="_blank">Ouvrir le PDF dans un nouvel onglet</a></p>
            <?php else: ?>
                <video controls width="100%" style="border-radius: var(--radius-sm); background:#000;">
                    <source src="/LMS-ARIANE/<?= htmlspecialchars($lecon['chemin_fichier']) ?>">
                    Votre navigateur ne supporte pas la lecture vidéo.
                </video>
            <?php endif; ?>
        </div>

        <!-- Évaluation -->
        <?php if (!$lecon['evaluation_id'] || empty($questions)): ?>
            <div class="card">
                <h2>Évaluation</h2>
                <p style="color:var(--ariane-text-muted);">Aucune évaluation n'a encore été configurée pour cette leçon par l'enseignant.</p>
                <?php if (!$progressionExistante): ?>
                    <button class="btn btn-thread" id="validerSansEval">Marquer comme terminée (100%)</button>
                <?php else: ?>
                    <span class="badge badge-success">Leçon terminée — <?= number_format($progressionExistante['note'], 1) ?>%</span>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="card">
                <h2><?= htmlspecialchars($lecon['evaluation_titre']) ?></h2>

                <?php if ($progressionExistante): ?>
                    <div class="alert alert-success">
                        Vous avez déjà passé cette évaluation. Score obtenu : <strong><?= number_format($progressionExistante['note'], 1) ?>%</strong>.
                        Vous pouvez la repasser pour améliorer votre score.
                    </div>
                <?php endif; ?>

                <div id="quizMessages"></div>

                <form id="quizForm">
                    <?php foreach ($questions as $qi => $q): ?>
                        <div class="quiz-question">
                            <h4>Question <?= $qi + 1 ?> <span style="color:var(--ariane-text-muted); font-size:0.8rem; font-weight:400;">(<?= $q['points'] ?> pt<?= $q['points'] > 1 ? 's' : '' ?>)</span></h4>
                            <p><?= htmlspecialchars($q['enonce']) ?></p>
                            <?php foreach ($q['reponses'] as $r): ?>
                                <label class="quiz-option">
                                    <input type="radio" name="question_<?= $q['id'] ?>" value="<?= $r['id'] ?>" required>
                                    <?= htmlspecialchars($r['texte']) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>

                    <button type="submit" class="btn btn-thread">Valider mes réponses</button>
                </form>
            </div>
        <?php endif; ?>
    </main>
</div>

<script>
const LECON_ID = <?= json_encode($leconId) ?>;
const MODULE_ID = <?= json_encode($lecon['module_id']) ?>;
</script>
<script src="/LMS-ARIANE/assets/js/quiz.js"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
