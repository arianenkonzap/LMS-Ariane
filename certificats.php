<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
exigerRole('etudiant');

$etudiantId = $_SESSION['user_id'];
$voirCertificat = (int)($_GET['voir'] ?? 0);

if ($voirCertificat) {
    $stmt = $pdo->prepare("
        SELECT cert.*, m.titre AS module_titre
        FROM certificats cert
        JOIN modules m ON m.id = cert.module_id
        WHERE cert.id = ? AND cert.etudiant_id = ?
    ");
    $stmt->execute([$voirCertificat, $etudiantId]);
    $cert = $stmt->fetch();

    if (!$cert) {
        header('Location: certificats.php');
        exit;
    }

    $pageTitle = "Certificat";
    include __DIR__ . '/../includes/header.php';
    ?>
    <div class="app-shell">
        <?php $activePage = "certificats"; include __DIR__ . '/../includes/sidebar.php'; ?>
        <main class="main-content">
            <div class="page-header">
                <div class="eyebrow"><a href="certificats.php">← Mes certificats</a></div>
                <h1>Certificat de validation</h1>
            </div>

            <div class="certificate">
                <div class="cert-eyebrow">LMS-ARIANE · Certificat de validation de module</div>
                <h1>Certificat de réussite</h1>
                <p>Ce certificat est décerné à</p>
                <div class="cert-name"><?= htmlspecialchars($_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom']) ?></div>
                <p>pour avoir validé avec succès le module</p>
                <h2 style="margin-top:8px;"><?= htmlspecialchars($cert['module_titre']) ?></h2>
                <p style="margin-top:18px;">Score final obtenu : <strong><?= number_format($cert['score_final'], 1) ?>%</strong></p>
                <p>Délivré le <?= date('d/m/Y', strtotime($cert['date_obtention'])) ?></p>
                <div class="cert-code">Code de vérification : <?= htmlspecialchars($cert['code_certificat']) ?></div>
            </div>

            <div style="text-align:center; margin-top:24px;">
                <button class="btn btn-thread" onclick="window.print()">Imprimer / Enregistrer en PDF</button>
            </div>
        </main>
    </div>
    <?php
} else {
    $stmt = $pdo->prepare("
        SELECT cert.*, m.titre AS module_titre
        FROM certificats cert
        JOIN modules m ON m.id = cert.module_id
        WHERE cert.etudiant_id = ?
        ORDER BY cert.date_obtention DESC
    ");
    $stmt->execute([$etudiantId]);
    $certificats = $stmt->fetchAll();

    $pageTitle = "Mes certificats";
    $activePage = "certificats";
    include __DIR__ . '/../includes/header.php';
    ?>
    <div class="app-shell">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        <main class="main-content">
            <div class="page-header">
                <div class="eyebrow">Espace étudiant</div>
                <h1>Mes certificats</h1>
                <p>Retrouvez ici tous les certificats obtenus en validant des modules.</p>
            </div>

            <?php if (empty($certificats)): ?>
                <div class="empty-state card">
                    <h3>Aucun certificat pour le moment</h3>
                    <p>Terminez toutes les leçons d'un module avec une moyenne suffisante pour obtenir votre premier certificat.</p>
                    <a href="modules.php" class="btn btn-primary">Voir les modules</a>
                </div>
            <?php else: ?>
                <div class="card-grid">
                    <?php foreach ($certificats as $c): ?>
                        <div class="module-card">
                            <span class="badge badge-success">Validé</span>
                            <h3 style="margin-top:8px;"><?= htmlspecialchars($c['module_titre']) ?></h3>
                            <p>Score final : <strong><?= number_format($c['score_final'], 1) ?>%</strong></p>
                            <p style="font-size:0.85rem; color:var(--ariane-text-muted);">Obtenu le <?= date('d/m/Y', strtotime($c['date_obtention'])) ?></p>
                            <a href="certificats.php?voir=<?= $c['id'] ?>" class="btn btn-primary btn-sm">Voir le certificat</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
    <?php
}

include __DIR__ . '/../includes/footer.php';
?>
