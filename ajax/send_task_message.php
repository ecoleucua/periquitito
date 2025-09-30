<?php
// Círculo Activo - ajax/send_task_message.php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado.']);
    exit();
}
$sender_id = $_SESSION['user_id'];
$task_id = filter_input(INPUT_POST, 'task_id', FILTER_VALIDATE_INT);
$message_text = trim($_POST['message_text'] ?? '');

if (!$task_id || empty($message_text)) {
    echo json_encode(['status' => 'error', 'message' => 'Faltan datos.']);
    exit();
}

// --- LÓGICA DE PERMISOS Y DESTINATARIO REFORZADA ---
// 1. Obtener los involucrados en la tarea
$stmt_task = $mysqli->prepare("
    SELECT 
        t.title,
        t.creator_id, 
        t.reserved_by,
        (SELECT s.executor_id FROM submissions s WHERE s.task_id = t.id ORDER BY s.submitted_at DESC LIMIT 1) as last_executor_id
    FROM tasks t 
    WHERE t.id = ?
");
$stmt_task->bind_param("i", $task_id);
$stmt_task->execute();
$task = $stmt_task->get_result()->fetch_assoc();
$stmt_task->close();

if (!$task) {
    echo json_encode(['status' => 'error', 'message' => 'Tarea no encontrada.']);
    exit();
}

// 2. Determinar el receptor y verificar permisos del emisor
$creator_id = $task['creator_id'];
// El interlocutor es la persona que tiene la tarea reservada, o si no hay nadie, la última persona que la ejecutó.
$other_party_id = $task['reserved_by'] ?? $task['last_executor_id'];
$receiver_id = null;

if ($sender_id == $creator_id) {
    $receiver_id = $other_party_id;
} elseif ($sender_id == $other_party_id) {
    $receiver_id = $creator_id;
} else {
    echo json_encode(['status' => 'error', 'message' => 'No tienes permiso para enviar mensajes en esta tarea.']);
    exit();
}

if (!$receiver_id) {
    echo json_encode(['status' => 'error', 'message' => 'No se pudo determinar el destinatario. Nadie ha reclamado esta tarea aún.']);
    exit();
}

// 3. Insertar el mensaje
$stmt_insert = $mysqli->prepare("INSERT INTO task_messages (task_id, sender_id, receiver_id, message_text) VALUES (?, ?, ?, ?)");
$stmt_insert->bind_param("iiis", $task_id, $sender_id, $receiver_id, $message_text);

if ($stmt_insert->execute()) {
    // --- CAMBIO EN LA NOTIFICACIÓN ---
    $sender_username = $_SESSION['username'] ?? 'Un usuario';
    $task_title_safe = htmlspecialchars($task['title']);
    $notif_message = "Has recibido un nuevo mensaje de {$sender_username} sobre la tarea \"{$task_title_safe}\".";
    
    // El nuevo enlace inteligente que contiene el parámetro para abrir el chat
    $notif_link = "task_view.php?id={$task_id}&open_chat_for_task={$task_id}";
    create_notification($receiver_id, $notif_message, $notif_link, $mysqli);

    echo json_encode(['status' => 'success', 'message' => 'Mensaje enviado.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'No se pudo enviar el mensaje.']);
}
$stmt_insert->close();
?>

