<?php
// Círculo Activo - ajax/get_task_messages.php
session_start();
require_once '../includes/db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado.']);
    exit();
}
$user_id = $_SESSION['user_id'];
$task_id = filter_input(INPUT_POST, 'task_id', FILTER_VALIDATE_INT);

if (!$task_id) {
    echo json_encode(['status' => 'error', 'message' => 'ID de tarea no válido.']);
    exit();
}

// --- LÓGICA DE PERMISOS REFORZADA ---
// 1. Obtener los involucrados en la tarea
$stmt_perm = $mysqli->prepare("
    SELECT 
        t.creator_id, 
        t.reserved_by,
        (SELECT s.executor_id FROM submissions s WHERE s.task_id = t.id ORDER BY s.submitted_at DESC LIMIT 1) as last_executor_id
    FROM tasks t 
    WHERE t.id = ?
");
$stmt_perm->bind_param("i", $task_id);
$stmt_perm->execute();
$task_parties = $stmt_perm->get_result()->fetch_assoc();
$stmt_perm->close();

if (!$task_parties) {
    echo json_encode(['status' => 'error', 'message' => 'Tarea no encontrada.']);
    exit();
}

// 2. Verificar si el usuario actual es una de las partes
$is_creator = ($user_id == $task_parties['creator_id']);
$is_reserver = ($user_id == $task_parties['reserved_by']);
$is_executor = ($user_id == $task_parties['last_executor_id']);

if (!$is_creator && !$is_reserver && !$is_executor) {
    echo json_encode(['status' => 'error', 'message' => 'No tienes permiso para ver estos mensajes.']);
    exit();
}

// 3. Obtener los mensajes y los datos del remitente
$stmt_msg = $mysqli->prepare("
    SELECT 
        m.sender_id, m.message_text, m.created_at, 
        u.username, u.avatar_url
    FROM task_messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.task_id = ? 
    ORDER BY m.created_at ASC
");
$stmt_msg->bind_param("i", $task_id);
$stmt_msg->execute();
$messages = $stmt_msg->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_msg->close();

// 4. Marcar mensajes como leídos para el usuario actual
$stmt_read = $mysqli->prepare("UPDATE task_messages SET is_read = 1 WHERE task_id = ? AND receiver_id = ? AND is_read = 0");
$stmt_read->bind_param("ii", $task_id, $user_id);
$stmt_read->execute();
$stmt_read->close();

echo json_encode(['status' => 'success', 'messages' => $messages, 'current_user_id' => $user_id]);
?>

