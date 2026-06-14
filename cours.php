<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
exigerRole('enseignant');

$enseignantId = $_SESSION['user_id'];
$erreur = '';
$succes = '';

// --- Création d'un cours ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'creer') {
    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $moduleId = (int)($_POST['module_id'] ?? 0);

    if ($titre === '' || $moduleId <= 0) {
        $erreur = "Le titre et le module sont obligatoires.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO cours (module_id, enseignant_id, titre, description) VALUES (?, ?, ?, ?)");
        $stmt->execute([$moduleId, $enseignantId, $titre, $description]);
        $succes = "Cours créé avec succès. Vous pouvez maintenant y ajouter des leçons.";
    }
}

// --- Suppression d'un cours ---
if (isset($_GET['supprimer'])) {
    $stmt = $pdo->prepare("DELETE FROM cours WHERE id = ? AND enseignant_id = ?");
    $stmt->execute([(int)$_GET['supprimer'], $enseignantId]);
    $succes = "Cours supprimé.";
}

// Modules disponibles
$modules = $pdo->query("SELECT * FROM modules ORDER BY titre")->fetchAll();

// Liste des cours de l'enseignant
$stmt = $pdo->prepare("
    SELECT c.*, m.titre AS module_titre,
        (SELECT COUNT(*) FROM lecons l WHERE l.cours_id = c.id) AS nb_lecons
    FROM cours c
    JOIN modules m ON m.id = c.module_id
    WHERE c.enseignant_id = ?
    ORDER BY c.date_creation DESC
");
$stmt->execute([$enseignantId]);
$cours = $stmt->fetchAll();

$pageTitle = "Mes cours";
$activePage = "cours";
include __DIR__ . '/../includes/header.php';
?>

<div class="app-shell">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header">
            <div class="eyebrow">Espace enseignant</div>
            <h1>Mes cours</h1>
            <p>Créez un cours et rattachez-le à un module défini par le promoteur.</p>
        </div>

        <?php if ($erreur): ?><div class="alert alert-error"><?= htmlspecialchars($erreur) ?></div><?php endif; ?>
        <?php if ($succes): ?><div class="alert alert-success"><?= htmlspecialchars($succes) ?></div><?php endif; ?>

        <?php if (empty($modules)): ?>
            <div class="alert alert-error">Aucun module n'a encore été créé par le promoteur. Demandez-lui d'en créer un avant de publier un cours.</div>
        <?php endif; ?>

        <div style="display:grid; grid-template-columns: 1fr 1.6fr; gap:24px; align-items:start;">
            <div class="card">
                <h3>Créer un cours</h3>
                <form method="POST" action="cours.php">
                    <input type="hidden" name="action" value="creer">
                    <div class="form-group">
                        <label for="module_id">Module</label>
                        <select id="module_id" name="module_id" required>
                            <option value="">— Choisir un module —</option>
                            <?php foreach ($modules as $m): ?>
                                <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['titre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="titre">Titre du cours</label>
                        <input type="text" id="titre" name="titre" required placeholder="Ex : Introduction au HTML & CSS">
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" placeholder="Présentez le contenu du cours..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-thread btn-block" <?= empty($modules) ? 'disabled' : '' ?>>Créer le cours</button>
                </form>
            </div>

            <div>
                <?php if (empty($cours)): ?>
                    <div class="empty-state card">
                        <h3>Aucun cours créé</h3>
                        <p>Utilisez le formulaire pour créer votre premier cours.</p>
                    </div>
                <?php else: ?>
                    <div class="card-grid">
                        <?php foreach ($cours as $c): ?>
                            <div class="module-card">
                                <div class="badge badge-pending" style="margin-bottom:8px;"><?= htmlspecialchars($c['module_titre']) ?></div>
                                <h3><?= htmlspecialchars($c['titre']) ?></h3>
                                <p><?= htmlspecialchars($c['description']) ?></p>
                                <p style="font-size:0.85rem; color:var(--ariane-text-muted);"><?= $c['nb_lecons'] ?> leçon(s)</p>
                                <div style="display:flex; gap:8px;">
                                    <a href="lecons.php?cours_id=<?= $c['id'] ?>" class="btn btn-primary btn-sm">Gérer les leçons</a>
                                    <a href="cours.php?supprimer=<?= $c['id'] ?>" class="btn btn-ghost btn-sm" onclick="return confirm('Supprimer ce cours et toutes ses leçons ?');">Supprimer</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
