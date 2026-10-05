<?php
// =============================================
// api/get_record.php
// Called by results/summary.php
// Returns patient info + health record vitals
// Query params: ?patient_id=N&record_id=N (record_id optional)
// =============================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// FIX: was '../db_config.php' (wrong path). config.php is in the same /api/ folder.
require_once 'config.php';

$patient_id = intval($_GET['patient_id'] ?? 0);
$record_id  = intval($_GET['record_id']  ?? 0);

if (!$patient_id) {
    echo json_encode(['success' => false, 'error' => 'No patient_id']);
    exit;
}

try {
    // FIX: DB schema uses `id` as PK in patients table, not `patient_id`
    $pStmt = $pdo->prepare("SELECT * FROM patients WHERE id = ? LIMIT 1");
    $pStmt->execute([$patient_id]);
    $patient = $pStmt->fetch();

    if (!$patient) {
        echo json_encode(['success' => false, 'error' => 'Patient not found']);
        exit;
    }

    // FIX: DB schema uses `id` as PK and `patient_id` as FK in health_records
    if ($record_id) {
        $rStmt = $pdo->prepare("SELECT * FROM health_records WHERE id = ? AND patient_id = ? LIMIT 1");
        $rStmt->execute([$record_id, $patient_id]);
    } else {
        $rStmt = $pdo->prepare("SELECT * FROM health_records WHERE patient_id = ? ORDER BY id DESC LIMIT 1");
        $rStmt->execute([$patient_id]);
    }
    $record = $rStmt->fetch();

    echo json_encode([
        'success' => true,
        'patient' => $patient,
        'record'  => $record ?: null,
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>