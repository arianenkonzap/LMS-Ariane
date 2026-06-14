<?php
/**
 * LMS-ARIANE - Marquer une leçon sans évaluation comme terminée (100%)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'etudiant') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès refusé.']);
    exit;
}

$etudiantId = $_SESSION['user_id'];
$leconId = (int)($_POST['lecon_id'] ?? 0);

if (!$leconId) {
    echo json_encode(['success' => false, 'message' => 'Leçon invalide.']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT l.*, c.module_id
    FROM lecons l
    JOIN cours c ON c.id = l.cours_id
    WHERE l.id = ?
");
$stmt->execute([$leconId]);
$lecon = $stmt->fetch();

if (!$lecon) {
    echo json_encode(['success' => false, 'message' => 'Leçon introuvable.']);
    exit;
}

// On crée une "fausse" évaluation à la volée si elle n'existe pas, sinon on utilise l'existante
$stmt = $pdo->prepare("SELECT id FROM evaluations WHERE lecon_id = ?");
$stmt->execute([$leconId]);
$evaluationId = $stmt->fetchColumn();

if (!$evaluationId) {
    $stmt = $pdo->prepare("INSERT INTO evaluations (lecon_id, titre) VALUES (?, 'Validation automatique')");
    $stmt->execute([$leconId]);
    $evaluationId = $pdo->lastInsertId();
}

$stmt = $pdo->prepare("
    INSERT INTO progression (etudiant_id, lecon_id, evaluation_id, note, statut)
    VALUES (?, ?, ?, 100, 'termine')
    ON DUPLICATE KEY UPDATE date_passage = CURRENT_TIMESTAMP
");
$stmt->execute([$etudiantId, $leconId, $evaluationId]);

// Vérification certificat (même logique que ajax_evaluation.php)
$moduleId = $lecon['module_id'];

$stmt = $pdo->prepare("SELECT seuil_validation FROM modules WHERE id = ?");
$stmt->execute([$moduleId]);
$seuil = (float)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM lecons l JOIN cours c ON c.id = l.cours_id WHERE c.module_id = ?");
$stmt->execute([$moduleId]);
$totalLecons = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*) AS nb, AVG(p.note) AS moyenne
    FROM progression p
    JOIN lecons l ON l.id = p.lecon_id
    JOIN cours c ON c.id = l.cours_id
    WHERE c.module_id = ? AND p.etudiant_id = ?
");
$stmt->execute([$moduleId, $etudiantId]);
$resume = $stmt->fetch();

$certificatObtenu = false;

if ($totalLecons > 0 && (int)$resume['nb'] === $totalLecons && (float)$resume['moyenne'] >= $seuil) {
    $stmt = $pdo->prepare("SELECT id FROM certificats WHERE etudiant_id = ? AND module_id = ?");
    $stmt->execute([$etudiantId, $moduleId]);
    if (!$stmt->fetch()) {
        $code = genererCodeCertificat($etudiantId, $moduleId);
        $stmt = $pdo->prepare("INSERT INTO certificats (etudiant_id, module_id, code_certificat, score_final) VALUES (?, ?, ?, ?)");
        $stmt->execute([$etudiantId, $moduleId, $code, round($resume['moyenne'], 2)]);
        $certificatObtenu = true;
    }
}

echo json_encode(['success' => true, 'certificat' => $certificatObtenu]);
