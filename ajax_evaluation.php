<?php
/**
 * LMS-ARIANE - Traitement AJAX de la soumission d'une évaluation
 * Renvoie un JSON : { success, note, valide, certificat }
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
$reponses = $_POST['reponses'] ?? []; // tableau [question_id => reponse_id]

if (!$leconId || empty($reponses)) {
    echo json_encode(['success' => false, 'message' => 'Données invalides.']);
    exit;
}

// Récupérer la leçon + évaluation
$stmt = $pdo->prepare("
    SELECT l.*, c.module_id, e.id AS evaluation_id
    FROM lecons l
    JOIN cours c ON c.id = l.cours_id
    LEFT JOIN evaluations e ON e.lecon_id = l.id
    WHERE l.id = ?
");
$stmt->execute([$leconId]);
$lecon = $stmt->fetch();

if (!$lecon || !$lecon['evaluation_id']) {
    echo json_encode(['success' => false, 'message' => 'Évaluation introuvable.']);
    exit;
}

// Charger les questions et leurs bonnes réponses
$stmt = $pdo->prepare("SELECT id, points FROM questions WHERE evaluation_id = ?");
$stmt->execute([$lecon['evaluation_id']]);
$questions = $stmt->fetchAll();

$totalPoints = 0;
$pointsObtenus = 0;

foreach ($questions as $q) {
    $totalPoints += $q['points'];

    $reponseDonnee = (int)($reponses[$q['id']] ?? 0);

    $stmt2 = $pdo->prepare("SELECT id FROM reponses WHERE question_id = ? AND est_correcte = 1");
    $stmt2->execute([$q['id']]);
    $bonneReponse = $stmt2->fetchColumn();

    if ($bonneReponse && (int)$bonneReponse === $reponseDonnee) {
        $pointsObtenus += $q['points'];
    }
}

$note = $totalPoints > 0 ? round(($pointsObtenus / $totalPoints) * 100, 2) : 0;

// Enregistrer / mettre à jour la progression
$stmt = $pdo->prepare("
    INSERT INTO progression (etudiant_id, lecon_id, evaluation_id, note, statut)
    VALUES (?, ?, ?, ?, 'termine')
    ON DUPLICATE KEY UPDATE note = GREATEST(note, ?), date_passage = CURRENT_TIMESTAMP
");
$stmt->execute([$etudiantId, $leconId, $lecon['evaluation_id'], $note, $note]);

// Vérifier si le module est désormais validé -> attribuer certificat
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

echo json_encode([
    'success'    => true,
    'note'       => $note,
    'certificat' => $certificatObtenu,
]);
