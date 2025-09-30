<?php
// Círculo Activo - ajax/executor_cancel_task.php
session_start();
require_once '../includes/db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Debes iniciar sesión.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método no válido.']);
    exit();
}

$task_id = filter_input(INPUT_POST, 'task_id', FILTER_VALIDATE_INT);
$user_id = $_SESSION['user_id'];

if (!$task_id) {
    echo json_encode(['status' => 'error', 'message' => 'ID de tarea no válido.']);
    exit();
}

$mysqli->begin_transaction();

try {
    // 1. Verificar que el usuario actual es quien tiene la tarea reservada
    $stmt_check = $mysqli->prepare("SELECT status FROM tasks WHERE id = ? AND reserved_by = ? FOR UPDATE");
    $stmt_check->bind_param("ii", $task_id, $user_id);
    $stmt_check->execute();
    $task = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();

    if (!$task) {
        throw new Exception("No tienes permiso para cancelar esta tarea o ya no existe.");
    }
    if ($task['status'] !== 'reviewing') {
        throw new Exception("Esta tarea no se puede cancelar en su estado actual.");
    }

    // 2. Republicar la tarea
    $stmt_update = $mysqli->prepare("UPDATE tasks SET status = 'active', reserved_by = NULL, reserved_at = NULL WHERE id = ?");
    $stmt_update->bind_param("i", $task_id);
    if (!$stmt_update->execute()) {
        throw new Exception("Error al republicar la tarea.");
    }
    $stmt_update->close();
    
    $mysqli->commit();
    echo json_encode(['status' => 'success', 'message' => 'Has cancelado la tarea. Ahora está disponible para otros usuarios.']);

} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
