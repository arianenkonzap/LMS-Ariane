<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
exigerRole('promoteur');

$promoteurId = $_SESSION['user_id'];
$erreur = '';
$succes = '';

// --- Création d'un module ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'creer') {
    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $seuil = (int)($_POST['seuil_validation'] ?? 60);

    if ($titre === '') {
        $erreur = "Le titre du module est obligatoire.";
    } elseif ($seuil < 0 || $seuil > 100) {
        $erreur = "Le seuil de validation doit être compris entre 0 et 100.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO modules (titre, description, promoteur_id, seuil_validation) VALUES (?, ?, ?, ?)");
        $stmt->execute([$titre, $description, $promoteurId, $seuil]);
        $succes = "Module créé avec succès.";
    }
}

// --- Suppression d'un module ---
if (isset($_GET['supprimer'])) {
    $stmt = $pdo->prepare("DELETE FROM modules WHERE id = ? AND promoteur_id = ?");
    $stmt->execute([(int)$_GET['supprimer'], $promoteurId]);
    $succes = "Module supprimé.";
}

// Liste des modules avec stats
$stmt = $pdo->prepare("
    SELECT m.*,
        (SELECT COUNT(*) FROM cours c WHERE c.module_id = m.id) AS nb_cours,
        (SELECT COUNT(*) FROM inscriptions i WHERE i.module_id = m.id) AS nb_inscrits,
        (SELECT COUNT(*) FROM certificats cert WHERE cert.module_id = m.id) AS nb_certifs
    FROM modules m
    WHERE m.promoteur_id = ?
    ORDER BY m.date_creation DESC
");
$stmt->execute([$promoteurId]);
$modules = $stmt->fetchAll();

$pageTitle = "Modules";
$activePage = "modules";
include __DIR__ . '/../includes/header.php';
?>

<div class="app-shell">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header">
            <div class="eyebrow">Espace promoteur</div>
            <h1>Modules de cours</h1>
            <p>Définissez les grands modules de formation. Les enseignants y rattacheront leurs cours.</p>
        </div>

        <?php if ($erreur): ?><div class="alert alert-error"><?= htmlspecialchars($erreur) ?></div><?php endif; ?>
        <?php if ($succes): ?><div class="alert alert-success"><?= htmlspecialchars($succes) ?></div><?php endif; ?>

        <div style="display:grid; grid-template-columns: 1fr 1.6fr; gap:24px; align-items:start;">
            <!-- Formulaire de création -->
            <div class="card">
                <h3>Créer un module</h3>
                <form method="POST" action="modules.php">
                    <input type="hidden" name="action" value="creer">
                    <div class="form-group">
                        <label for="titre">Titre du module</label>
                        <input type="text" id="titre" name="titre" required placeholder="Ex : Développement Web Fondamental">
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" placeholder="Décrivez les objectifs du module..."></textarea>
                    </div>
                    <div class="form-group">
                        <label for="seuil_validation">Seuil de validation (%)</label>
                        <input type="number" id="seuil_validation" name="seuil_validation" min="0" max="100" value="60">
                        <p class="form-hint">Score moyen minimum requis pour obtenir le certificat du module.</p>
                    </div>
                    <button type="submit" class="btn btn-thread btn-block">Créer le module</button>
                </form>
            </div>

            <!-- Liste des modules -->
            <div>
                <?php if (empty($modules)): ?>
                    <div class="empty-state card">
                        <h3>Aucun module créé</h3>
                        <p>Utilisez le formulaire pour créer votre premier module.</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Module</th>
                                <th>Cours</th>
                                <th>Inscrits</th>
                                <th>Seuil</th>
                                <th>Certificats</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($modules as $m): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($m['titre']) ?></strong><br>
                                        <span style="color:var(--ariane-text-muted); font-size:0.85rem;"><?= htmlspecialchars(mb_strimwidth($m['description'], 0, 60, '…')) ?></span>
                                    </td>
                                    <td><?= $m['nb_cours'] ?></td>
                                    <td><?= $m['nb_inscrits'] ?></td>
                                    <td><?= $m['seuil_validation'] ?>%</td>
                                    <td><?= $m['nb_certifs'] ?></td>
                                    <td>
                                        <a href="modules.php?supprimer=<?= $m['id'] ?>"
                                           class="btn btn-ghost btn-sm"
                                           onclick="return confirm('Supprimer ce module et tout son contenu ?');">Supprimer</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
