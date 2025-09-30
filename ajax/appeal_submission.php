<?php
// Círculo Activo
// Archivo: ajax/appeal_submission.php

header('Content-Type: application/json');
require_once '../includes/db_connect.php';
require_once '../includes/functions.php'; // Para create_notification
session_start();

// 1. Verificación de seguridad
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Acción no autorizada.']);
    exit();
}
$user_id = $_SESSION['user_id'];
$submission_id = $_POST['submission_id'] ?? null;

if (!filter_var($submission_id, FILTER_VALIDATE_INT)) {
    echo json_encode(['status' => 'error', 'message' => 'Datos no válidos.']);
    exit();
}

$mysqli->begin_transaction();

try {
    // 2. Verificar que el envío le pertenece al usuario y está rechazado
    $stmt = $mysqli->prepare("SELECT id FROM submissions WHERE id = ? AND executor_id = ? AND status = 'rejected'");
    $stmt->bind_param("ii", $submission_id, $user_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows !== 1) {
        throw new Exception("No puedes apelar este envío.");
    }
    $stmt->close();

    // 3. Iniciar la disputa
    // Actualizar el estado del envío
    $stmt_update = $mysqli->prepare("UPDATE submissions SET status = 'in_dispute' WHERE id = ?");
    $stmt_update->bind_param("i", $submission_id);
    $stmt_update->execute();
    $stmt_update->close();

    // Insertar el registro de la disputa
    $stmt_insert = $mysqli->prepare("INSERT INTO disputes (submission_id, status) VALUES (?, 'voting')");
    $stmt_insert->bind_param("i", $submission_id);
    $stmt_insert->execute();
    $dispute_id = $mysqli->insert_id;
    $stmt_insert->close();

    // 4. Notificar a los usuarios de Nivel Oro
    $gold_users_stmt = $mysqli->prepare("SELECT id FROM users WHERE reputation_tier = 'gold' AND id != ?");
    $gold_users_stmt->bind_param("i", $user_id);
    $gold_users_stmt->execute();
    $gold_users_result = $gold_users_stmt->get_result();

    $message = "Hay un nuevo caso de disputa (#$dispute_id) que requiere tu voto como jurado.";
    $link = "dispute_center.php";

    while ($gold_user = $gold_users_result->fetch_assoc()) {
        create_notification($gold_user['id'], $message, $link, $mysqli);
    }
    $gold_users_stmt->close();


    $mysqli->commit();
    echo json_encode(['status' => 'success', 'message' => 'Tu apelación ha sido registrada. Un jurado de la comunidad revisará tu caso.']);

} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$mysqli->close();
?>

