<?php
// Círculo Activo - ajax/submit_proof.php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Usar un manejador de errores para siempre devolver JSON
set_exception_handler(function ($exception) {
    // error_log($exception); // Opcional: registrar el error real en el servidor
    echo json_encode(['status' => 'error', 'message' => 'Ocurrió un error interno en el servidor.']);
    exit();
});

// 1. Seguridad y Validación
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Debes iniciar sesión para completar una tarea.']);
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método no válido.']);
    exit();
}
$executor_id = $_SESSION['user_id'];
$task_id = filter_input(INPUT_POST, 'task_id', FILTER_VALIDATE_INT);
if (!$task_id) {
    echo json_encode(['status' => 'error', 'message' => 'ID de tarea no válido.']);
    exit();
}

// 2. Combinar Pruebas y Validar
$proof_text = trim($_POST['proof_data_text'] ?? '');
$proof_image_url = trim($_POST['proof_data_image_url'] ?? '');
$combined_proof = '';

if (!empty($proof_text)) {
    $combined_proof .= "Texto de prueba: " . $proof_text;
}
if (!empty($proof_image_url)) {
    if (!empty($combined_proof)) $combined_proof .= "\n\n";
    $combined_proof .= "Imagen de prueba: " . $proof_image_url;
}
if (empty($combined_proof)) {
    echo json_encode(['status' => 'error', 'message' => 'La prueba no puede estar vacía.']);
    exit();
}

// 3. Verificaciones en la Base de Datos (dentro de una transacción)
$mysqli->begin_transaction();
try {
    // a) Bloquear la tarea para evitar race conditions y obtener su estado actual
    $stmt_task = $mysqli->prepare("SELECT creator_id, status, reserved_by, title FROM tasks WHERE id = ? FOR UPDATE");
    $stmt_task->bind_param("i", $task_id);
    $stmt_task->execute();
    $task = $stmt_task->get_result()->fetch_assoc();
    $stmt_task->close();

    if (!$task) throw new Exception("La tarea ya no existe.");
    if ($task['creator_id'] == $executor_id) throw new Exception("No puedes completar tu propia tarea.");
    if ($task['status'] !== 'reviewing' || $task['reserved_by'] != $executor_id) {
        throw new Exception("Esta tarea no está reservada por ti o su tiempo ha expirado.");
    }

    // b) Comprobar si ya existe un envío para esta tarea por este usuario
    $stmt_check = $mysqli->prepare("SELECT id FROM submissions WHERE task_id = ? AND executor_id = ?");
    $stmt_check->bind_param("ii", $task_id, $executor_id);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        throw new Exception("Ya has enviado una prueba para esta tarea.");
    }
    $stmt_check->close();

    // 4. Inserción de la Prueba
    $stmt_insert = $mysqli->prepare("INSERT INTO submissions (task_id, executor_id, proof_data, status) VALUES (?, ?, ?, 'pending_review')");
    $stmt_insert->bind_param("iis", $task_id, $executor_id, $combined_proof);
    if (!$stmt_insert->execute()) {
        throw new Exception("No se pudo guardar tu prueba. Inténtalo de nuevo.");
    }
    $stmt_insert->close();

    // 5. Notificación al creador
    $task_title_safe = htmlspecialchars($task['title']);
    $executor_username_safe = htmlspecialchars($_SESSION['username'] ?? 'Un usuario');
    $message = "El usuario {$executor_username_safe} ha completado tu tarea \"{$task_title_safe}\" y está esperando tu revisión.";
    create_notification($task['creator_id'], $message, 'my_tasks.php?tab=published', $mysqli);
    
    // Si todo fue bien, confirmar la transacción
    $mysqli->commit();
    echo json_encode(['status' => 'success', 'message' => '¡Prueba enviada! Serás notificado cuando el creador la revise.']);

} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>

