<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
exigerRole('promoteur');

$promoteurId = $_SESSION['user_id'];

// Statistiques
$nbModules = $pdo->prepare("SELECT COUNT(*) FROM modules WHERE promoteur_id = ?");
$nbModules->execute([$promoteurId]);
$totalModules = $nbModules->fetchColumn();

$totalEtudiants = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role = 'etudiant'")->fetchColumn();
$totalCertificats = $pdo->query("SELECT COUNT(*) FROM certificats")->fetchColumn();
$totalCours = $pdo->query("SELECT COUNT(*) FROM cours")->fetchColumn();

// Derniers modules
$stmt = $pdo->prepare("
    SELECT m.*,
        (SELECT COUNT(*) FROM cours c WHERE c.module_id = m.id) AS nb_cours,
        (SELECT COUNT(*) FROM inscriptions i WHERE i.module_id = m.id) AS nb_inscrits
    FROM modules m
    WHERE m.promoteur_id = ?
    ORDER BY m.date_creation DESC
    LIMIT 5
");
$stmt->execute([$promoteurId]);
$modules = $stmt->fetchAll();

$pageTitle = "Tableau de bord";
$activePage = "dashboard";
include __DIR__ . '/../includes/header.php';
?>

<div class="app-shell">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header">
            <div class="eyebrow">Espace promoteur</div>
            <h1>Bonjour, <?= htmlspecialchars($_SESSION['user_prenom']) ?></h1>
            <p>Pilotez les modules de formation et suivez la progression des étudiants.</p>
        </div>

        <div class="card-grid" style="margin-bottom: 32px;">
            <div class="card">
                <div class="eyebrow">Modules créés</div>
                <h2 style="font-size:2.2rem;"><?= $totalModules ?></h2>
            </div>
            <div class="card">
                <div class="eyebrow">Cours publiés</div>
                <h2 style="font-size:2.2rem;"><?= $totalCours ?></h2>
            </div>
            <div class="card">
                <div class="eyebrow">Étudiants inscrits</div>
                <h2 style="font-size:2.2rem;"><?= $totalEtudiants ?></h2>
            </div>
            <div class="card">
                <div class="eyebrow">Certificats délivrés</div>
                <h2 style="font-size:2.2rem;"><?= $totalCertificats ?></h2>
            </div>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
            <h2 style="margin:0;">Mes modules récents</h2>
            <a href="modules.php" class="btn btn-thread">+ Nouveau module</a>
        </div>

        <?php if (empty($modules)): ?>
            <div class="empty-state card">
                <h3>Aucun module pour le moment</h3>
                <p>Créez votre premier module pour structurer le parcours des étudiants.</p>
                <a href="modules.php" class="btn btn-primary">Créer un module</a>
            </div>
        <?php else: ?>
            <div class="card-grid">
                <?php foreach ($modules as $m): ?>
                    <div class="module-card">
                        <h3><?= htmlspecialchars($m['titre']) ?></h3>
                        <p><?= htmlspecialchars($m['description']) ?></p>
                        <div style="display:flex; gap:8px; flex-wrap:wrap; font-size:0.85rem; color:var(--ariane-text-muted);">
                            <span><?= $m['nb_cours'] ?> cours</span> ·
                            <span><?= $m['nb_inscrits'] ?> inscrits</span> ·
                            <span>Seuil : <?= $m['seuil_validation'] ?>%</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
