<?php
/**
 * LMS-ARIANE - Fonctions d'authentification et de session
 */

session_start();

/**
 * Vérifie que l'utilisateur est connecté, sinon redirige vers la connexion.
 */
function exigerConnexion() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /LMS-ARIANE/auth/login.php');
        exit;
    }
}

/**
 * Vérifie que l'utilisateur connecté possède le rôle attendu.
 * Sinon redirige vers le tableau de bord correspondant à son rôle réel.
 */
function exigerRole($roleAttendu) {
    exigerConnexion();
    if ($_SESSION['user_role'] !== $roleAttendu) {
        header('Location: /LMS-ARIANE/' . $_SESSION['user_role'] . '/dashboard.php');
        exit;
    }
}

/**
 * Retourne true si l'utilisateur courant possède le rôle donné.
 */
function aRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

/**
 * Renvoie le nom complet de l'utilisateur connecté.
 */
function nomUtilisateur() {
    return ($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? '');
}

/**
 * Génère un code de certificat unique.
 */
function genererCodeCertificat($etudiantId, $moduleId) {
    return 'ARIANE-' . strtoupper(substr(md5($etudiantId . '-' . $moduleId . '-' . time()), 0, 10));
}
