<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
exigerRole('promoteur');

// Liste des étudiants avec leur progression par module
$stmt = $pdo->query("
    SELECT u.id, u.nom, u.prenom, u.email,
        (SELECT COUNT(*) FROM certificats c WHERE c.etudiant_id = u.id) AS nb_certifs
    FROM utilisateurs u
    WHERE u.role = 'etudiant'
    ORDER BY u.nom, u.prenom
");
$etudiants = $stmt->fetchAll();

// Tous les certificats délivrés
$certificats = $pdo->query("
    SELECT cert.*, u.nom, u.prenom, m.titre AS module_titre
    FROM certificats cert
    JOIN utilisateurs u ON u.id = cert.etudiant_id
    JOIN modules m ON m.id = cert.module_id
    ORDER BY cert.date_obtention DESC
")->fetchAll();

$pageTitle = "Étudiants & certificats";
$activePage = "etudiants";
include __DIR__ . '/../includes/header.php';
?>

<div class="app-shell">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header">
            <div class="eyebrow">Espace promoteur</div>
            <h1>Étudiants & certificats</h1>
            <p>Consultez la communauté étudiante et les certificats délivrés.</p>
        </div>

        <h2>Étudiants inscrits</h2>
        <?php if (empty($etudiants)): ?>
            <div class="empty-state card"><h3>Aucun étudiant inscrit pour le moment.</h3></div>
        <?php else: ?>
            <table style="margin-bottom: 32px;">
                <thead>
                    <tr><th>Nom</th><th>Email</th><th>Certificats obtenus</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($etudiants as $e): ?>
                        <tr>
                            <td><?= htmlspecialchars($e['prenom'] . ' ' . $e['nom']) ?></td>
                            <td><?= htmlspecialchars($e['email']) ?></td>
                            <td>
                                <?php if ($e['nb_certifs'] > 0): ?>
                                    <span class="badge badge-success"><?= $e['nb_certifs'] ?> certificat(s)</span>
                                <?php else: ?>
                                    <span style="color:var(--ariane-text-muted);">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <h2>Certificats délivrés</h2>
        <?php if (empty($certificats)): ?>
            <div class="empty-state card"><h3>Aucun certificat délivré pour le moment.</h3></div>
        <?php else: ?>
            <table>
                <thead>
                    <tr><th>Étudiant</th><th>Module</th><th>Score final</th><th>Code</th><th>Date</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($certificats as $c): ?>
                        <tr>
                            <td><?= htmlspecialchars($c['prenom'] . ' ' . $c['nom']) ?></td>
                            <td><?= htmlspecialchars($c['module_titre']) ?></td>
                            <td><span class="badge badge-success"><?= number_format($c['score_final'], 1) ?>%</span></td>
                            <td><code style="font-family:var(--font-mono); font-size:0.85rem;"><?= htmlspecialchars($c['code_certificat']) ?></code></td>
                            <td><?= date('d/m/Y', strtotime($c['date_obtention'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
