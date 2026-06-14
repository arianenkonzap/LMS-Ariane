<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

if (isset($_SESSION['user_id'])) {
    header('Location: /LMS-ARIANE/' . $_SESSION['user_role'] . '/dashboard.php');
    exit;
}

$erreur = '';
$valeurs = ['nom' => '', 'prenom' => '', 'email' => '', 'role' => 'etudiant'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $valeurs['nom']    = trim($_POST['nom'] ?? '');
    $valeurs['prenom'] = trim($_POST['prenom'] ?? '');
    $valeurs['email']  = trim($_POST['email'] ?? '');
    $valeurs['role']   = $_POST['role'] ?? 'etudiant';
    $motDePasse        = $_POST['mot_de_passe'] ?? '';
    $confirmation      = $_POST['confirmation'] ?? '';

    $rolesAutorises = ['etudiant', 'enseignant', 'promoteur'];

    if ($valeurs['nom'] === '' || $valeurs['prenom'] === '' || $valeurs['email'] === '' || $motDePasse === '') {
        $erreur = "Tous les champs sont obligatoires.";
    } elseif (!in_array($valeurs['role'], $rolesAutorises, true)) {
        $erreur = "Rôle invalide.";
    } elseif (strlen($motDePasse) < 6) {
        $erreur = "Le mot de passe doit contenir au moins 6 caractères.";
    } elseif ($motDePasse !== $confirmation) {
        $erreur = "Les mots de passe ne correspondent pas.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
        $stmt->execute([$valeurs['email']]);
        if ($stmt->fetch()) {
            $erreur = "Cette adresse e-mail est déjà utilisée.";
        } else {
            $hash = password_hash($motDePasse, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$valeurs['nom'], $valeurs['prenom'], $valeurs['email'], $hash, $valeurs['role']]);

            header('Location: login.php?inscrit=1');
            exit;
        }
    }
}

$pageTitle = "Créer un compte";
include __DIR__ . '/../includes/header.php';
?>

<div class="auth-page">
    <div class="auth-card">
        <div class="brand"><span class="thread-dot"></span> LMS-ARIANE</div>
        <p class="tagline">Rejoignez la plateforme</p>

        <?php if ($erreur): ?>
            <div class="alert alert-error"><?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <div class="form-group">
                <label for="prenom">Prénom</label>
                <input type="text" id="prenom" name="prenom" required value="<?= htmlspecialchars($valeurs['prenom']) ?>">
            </div>
            <div class="form-group">
                <label for="nom">Nom</label>
                <input type="text" id="nom" name="nom" required value="<?= htmlspecialchars($valeurs['nom']) ?>">
            </div>
            <div class="form-group">
                <label for="email">Adresse e-mail</label>
                <input type="email" id="email" name="email" required value="<?= htmlspecialchars($valeurs['email']) ?>">
            </div>
            <div class="form-group">
                <label for="role">Je suis...</label>
                <select id="role" name="role">
                    <option value="etudiant" <?= $valeurs['role'] === 'etudiant' ? 'selected' : '' ?>>Étudiant</option>
                    <option value="enseignant" <?= $valeurs['role'] === 'enseignant' ? 'selected' : '' ?>>Enseignant</option>
                    <option value="promoteur" <?= $valeurs['role'] === 'promoteur' ? 'selected' : '' ?>>Promoteur</option>
                </select>
            </div>
            <div class="form-group">
                <label for="mot_de_passe">Mot de passe</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe" required minlength="6">
                <p class="form-hint">6 caractères minimum.</p>
            </div>
            <div class="form-group">
                <label for="confirmation">Confirmer le mot de passe</label>
                <input type="password" id="confirmation" name="confirmation" required minlength="6">
            </div>
            <button type="submit" class="btn btn-primary btn-block">Créer mon compte</button>
        </form>

        <p class="switch-link">Déjà inscrit ? <a href="login.php">Se connecter</a></p>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
