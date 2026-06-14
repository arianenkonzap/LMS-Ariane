<?php
/**
 * LMS-ARIANE - Barre latérale de navigation
 * Variable attendue : $activePage (string, ex: 'dashboard', 'modules', 'certificats')
 */
$role = $_SESSION['user_role'];
$base = '/LMS-ARIANE/' . $role;

$navItems = [
    'enseignant' => [
        ['key' => 'dashboard', 'label' => 'Tableau de bord', 'href' => "$base/dashboard.php"],
        ['key' => 'cours', 'label' => 'Mes cours', 'href' => "$base/cours.php"],
        ['key' => 'lecons', 'label' => 'Leçons & évaluations', 'href' => "$base/lecons.php"],
    ],
    'etudiant' => [
        ['key' => 'dashboard', 'label' => 'Tableau de bord', 'href' => "$base/dashboard.php"],
        ['key' => 'modules', 'label' => 'Modules', 'href' => "$base/modules.php"],
        ['key' => 'certificats', 'label' => 'Mes certificats', 'href' => "$base/certificats.php"],
    ],
    'promoteur' => [
        ['key' => 'dashboard', 'label' => 'Tableau de bord', 'href' => "$base/dashboard.php"],
        ['key' => 'modules', 'label' => 'Modules', 'href' => "$base/modules.php"],
        ['key' => 'etudiants', 'label' => 'Étudiants & certificats', 'href' => "$base/etudiants.php"],
    ],
];

$roleLabels = [
    'enseignant' => 'Enseignant',
    'etudiant'   => 'Étudiant',
    'promoteur'  => 'Promoteur',
];
?>
<aside class="sidebar">
    <div class="brand">
        <span class="thread-dot"></span> LMS-ARIANE
    </div>
    <nav>
        <?php foreach ($navItems[$role] as $item): ?>
            <a href="<?= $item['href'] ?>" class="<?= ($activePage ?? '') === $item['key'] ? 'active' : '' ?>">
                <?= htmlspecialchars($item['label']) ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="user-box">
        <strong><?= htmlspecialchars(nomUtilisateur()) ?></strong><br>
        <span class="role-tag"><?= $roleLabels[$role] ?></span>
        <a href="/LMS-ARIANE/auth/logout.php" class="logout">Se déconnecter</a>
    </div>
</aside>
