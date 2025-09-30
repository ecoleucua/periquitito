<?php
// Círculo Activo - ajax/cancel_task.php
session_start();
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Debes iniciar sesión para cancelar una tarea.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método de solicitud no válido.']);
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
    // Bloquear la fila de la tarea para evitar condiciones de carrera
    $stmt_task = $mysqli->prepare("SELECT creator_id, points_value, status FROM tasks WHERE id = ? FOR UPDATE");
    $stmt_task->bind_param("i", $task_id);
    $stmt_task->execute();
    $task = $stmt_task->get_result()->fetch_assoc();
    $stmt_task->close();

    if (!$task) {
        throw new Exception("La tarea no existe o ya fue eliminada.");
    }
    if ($task['creator_id'] != $user_id) {
        throw new Exception("No tienes permiso para cancelar esta tarea.");
    }
    if ($task['status'] != 'active') {
        throw new Exception("Solo se pueden cancelar tareas que están activas y sin reclamar.");
    }

    // Calcular el total de puntos a devolver (recompensa + tarifa del 10%)
    $points_to_refund = $task['points_value'] + floor($task['points_value'] * 0.10);

    // 1. Devolver los puntos al saldo del creador
    $stmt_refund = $mysqli->prepare("UPDATE users SET points_balance = points_balance + ? WHERE id = ?");
    $stmt_refund->bind_param("ii", $points_to_refund, $user_id);
    if(!$stmt_refund->execute()) throw new Exception("Error al devolver los puntos a tu saldo.");
    $stmt_refund->close();

    // 2. Cambiar el estado de la tarea a 'deleted' para ocultarla
    $stmt_delete = $mysqli->prepare("UPDATE tasks SET status = 'deleted' WHERE id = ?");
    $stmt_delete->bind_param("i", $task_id);
    if(!$stmt_delete->execute()) throw new Exception("Error al actualizar el estado de la tarea.");
    $stmt_delete->close();
    
    // 3. Registrar la transacción de reembolso para mantener un historial claro
    $stmt_trans = $mysqli->prepare("INSERT INTO transactions (to_user_id, amount, type) VALUES (?, ?, 'refund')");
    $stmt_trans->bind_param("ii", $user_id, $points_to_refund);
    if(!$stmt_trans->execute()) throw new Exception("Error al registrar la transacción de reembolso.");
    $stmt_trans->close();

    // Si todas las operaciones tienen éxito, confirmar la transacción
    $mysqli->commit();
    echo json_encode(['status' => 'success', 'message' => 'Tarea cancelada con éxito. Los puntos han sido devueltos a tu saldo.']);

} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>

