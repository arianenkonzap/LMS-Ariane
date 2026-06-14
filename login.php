<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Redirige si déjà connecté
if (isset($_SESSION['user_id'])) {
    header('Location: /LMS-ARIANE/' . $_SESSION['user_role'] . '/dashboard.php');
    exit;
}

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $motDePasse = $_POST['mot_de_passe'] ?? '';

    if ($email === '' || $motDePasse === '') {
        $erreur = "Merci de renseigner votre email et votre mot de passe.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ?");
        $stmt->execute([$email]);
        $utilisateur = $stmt->fetch();

        if ($utilisateur && password_verify($motDePasse, $utilisateur['mot_de_passe'])) {
            $_SESSION['user_id']     = $utilisateur['id'];
            $_SESSION['user_nom']    = $utilisateur['nom'];
            $_SESSION['user_prenom'] = $utilisateur['prenom'];
            $_SESSION['user_role']   = $utilisateur['role'];

            header('Location: /LMS-ARIANE/' . $utilisateur['role'] . '/dashboard.php');
            exit;
        } else {
            $erreur = "Email ou mot de passe incorrect.";
        }
    }
}

$pageTitle = "Connexion";
include __DIR__ . '/../includes/header.php';
?>

<div class="auth-page">
    <div class="auth-card">
        <div class="brand"><span class="thread-dot"></span> LMS-ARIANE</div>
        <p class="tagline">Suivez le fil de votre apprentissage</p>

        <?php if ($erreur): ?>
            <div class="alert alert-error"><?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['inscrit'])): ?>
            <div class="alert alert-success">Compte créé avec succès. Vous pouvez vous connecter.</div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="email">Adresse e-mail</label>
                <input type="email" id="email" name="email" required autofocus value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="mot_de_passe">Mot de passe</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Se connecter</button>
        </form>

        <p class="switch-link">Pas encore de compte ? <a href="register.php">Créer un compte</a></p>

        <p class="form-hint" style="text-align:center; margin-top:18px;">
            Comptes de démonstration (mot de passe : <code>password123</code>)<br>
            promoteur@ariane.cm · enseignant@ariane.cm · etudiant@ariane.cm
        </p>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
