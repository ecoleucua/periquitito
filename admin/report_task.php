<?php
// Círculo Activo
// Archivo: ajax/report_task.php

header('Content-Type: application/json');
require_once '../includes/db_connect.php';
session_start();

// 1. Seguridad y Validación
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Acción no autorizada.']);
    exit();
}

$reporter_id = $_SESSION['user_id'];
$task_id = filter_input(INPUT_POST, 'task_id', FILTER_VALIDATE_INT);

if (!$task_id) {
    echo json_encode(['status' => 'error', 'message' => 'ID de tarea no válido.']);
    exit();
}

// 2. Verificación anti-spam (comprobar si ya reportó)
$stmt_check = $mysqli->prepare("SELECT id FROM reports WHERE task_id = ? AND reporter_id = ?");
$stmt_check->bind_param("ii", $task_id, $reporter_id);
$stmt_check->execute();
if ($stmt_check->get_result()->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Ya has reportado esta tarea.']);
    exit();
}
$stmt_check->close();

// 3. Lógica de Reporte (Transacción)
$mysqli->begin_transaction();
try {
    // Insertar el reporte
    $stmt_insert = $mysqli->prepare("INSERT INTO reports (task_id, reporter_id) VALUES (?, ?)");
    $stmt_insert->bind_param("ii", $task_id, $reporter_id);
    $stmt_insert->execute();
    $stmt_insert->close();

    // Incrementar el contador en la tabla de tareas
    $stmt_update = $mysqli->prepare("UPDATE tasks SET report_count = report_count + 1 WHERE id = ?");
    $stmt_update->bind_param("i", $task_id);
    $stmt_update->execute();
    $stmt_update->close();

    // Si todo fue bien, confirmar
    $mysqli->commit();
    echo json_encode(['status' => 'success', 'message' => 'Tarea reportada. Gracias por ayudar a mantener la comunidad.']);

} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(['status' => 'error', 'message' => 'Ocurrió un error al procesar tu reporte.']);
}

$mysqli->close();
?>

