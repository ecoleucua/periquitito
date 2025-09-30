<?php
// Círculo Activo - ajax/reserve_task.php
session_start();
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

// 1. Seguridad y Validación
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Debes iniciar sesión para reservar una tarea.']);
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

// 2. Lógica de Reserva (Transacción Atómica para evitar que dos usuarios reserven a la vez)
$mysqli->begin_transaction();

try {
    // Bloquear la fila de la tarea para asegurar que solo un proceso la modifique
    $stmt_lock = $mysqli->prepare("SELECT creator_id, status, reserved_by FROM tasks WHERE id = ? FOR UPDATE");
    $stmt_lock->bind_param("i", $task_id);
    $stmt_lock->execute();
    $result = $stmt_lock->get_result();
    $task = $result->fetch_assoc();
    $stmt_lock->close();

    if (!$task) {
        throw new Exception('La tarea ya no existe.');
    }

    if ($task['creator_id'] == $user_id) {
        throw new Exception('No puedes reservar tu propia tarea.');
    }
    
    if ($task['status'] !== 'active' || $task['reserved_by'] !== null) {
        throw new Exception('Lo sentimos, esta tarea acaba de ser reservada por otro usuario o ya no está activa.');
    }

    // Si la tarea está disponible, la reservamos usando la hora universal (UTC)
    $stmt_reserve = $mysqli->prepare("UPDATE tasks SET status = 'reviewing', reserved_by = ?, reserved_at = UTC_TIMESTAMP() WHERE id = ?");
    $stmt_reserve->bind_param("ii", $user_id, $task_id);
    
    if (!$stmt_reserve->execute() || $stmt_reserve->affected_rows === 0) {
        throw new Exception('No se pudo reservar la tarea. Por favor, inténtalo de nuevo.');
    }
    $stmt_reserve->close();

    // Si todo fue bien, confirmar la transacción
    $mysqli->commit();
    echo json_encode([
        'status' => 'success', 
        'message' => '¡Tarea reservada! Tienes tiempo limitado para completarla.',
        'redirect_url' => 'task_view.php?id=' . $task_id
    ]);

} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>

