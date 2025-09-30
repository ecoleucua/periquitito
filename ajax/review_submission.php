<?php
// Círculo Activo - ajax/review_submission.php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// 1. Seguridad y Validación
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Debes iniciar sesión para revisar un envío.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método de solicitud no válido.']);
    exit();
}

$submission_id = filter_input(INPUT_POST, 'submission_id', FILTER_VALIDATE_INT);
$action = $_POST['action'] ?? '';
$user_id = $_SESSION['user_id'];

if (!$submission_id || !in_array($action, ['approve', 'reject'])) {
    echo json_encode(['status' => 'error', 'message' => 'Datos no válidos.']);
    exit();
}

// 2. Verificación de Propiedad y Estado
$stmt_verify = $mysqli->prepare("
    SELECT s.id, s.status, s.executor_id, t.id as task_id, t.creator_id, t.points_value, t.title as task_title, u.username as executor_username
    FROM submissions s
    JOIN tasks t ON s.task_id = t.id
    JOIN users u ON s.executor_id = u.id
    WHERE s.id = ?
");
$stmt_verify->bind_param("i", $submission_id);
$stmt_verify->execute();
$result = $stmt_verify->get_result();
$submission_data = $result->fetch_assoc();
$stmt_verify->close();

if (!$submission_data) {
    echo json_encode(['status' => 'error', 'message' => 'El envío no existe.']);
    exit();
}

if ($submission_data['creator_id'] != $user_id) {
    echo json_encode(['status' => 'error', 'message' => 'No tienes permiso para revisar este envío.']);
    exit();
}

if ($submission_data['status'] !== 'pending_review') {
    echo json_encode(['status' => 'error', 'message' => 'Este envío ya ha sido revisado.']);
    exit();
}

$executor_id = $submission_data['executor_id'];
$task_id = $submission_data['task_id'];
$points_value = $submission_data['points_value'];
$task_title_safe = htmlspecialchars($submission_data['task_title']);
$executor_username_safe = htmlspecialchars($submission_data['executor_username']);


// 3. Lógica de Aprobación o Rechazo
$mysqli->begin_transaction();

try {
    if ($action == 'approve') {
        // --- LÓGICA DE APROBACIÓN ---
        
        // a) Actualizar estado del envío a 'approved'
        $stmt_sub = $mysqli->prepare("UPDATE submissions SET status = 'approved' WHERE id = ?");
        $stmt_sub->bind_param("i", $submission_id);
        if (!$stmt_sub->execute()) throw new Exception("Error al actualizar el envío.");
        $stmt_sub->close();

        // b) Actualizar estado de la tarea a 'completed'
        $stmt_task = $mysqli->prepare("UPDATE tasks SET status = 'completed' WHERE id = ?");
        $stmt_task->bind_param("i", $task_id);
        if (!$stmt_task->execute()) throw new Exception("Error al actualizar la tarea.");
        $stmt_task->close();

        // c) Transferir puntos al ejecutor
        $stmt_points = $mysqli->prepare("UPDATE users SET points_balance = points_balance + ? WHERE id = ?");
        $stmt_points->bind_param("ii", $points_value, $executor_id);
        if (!$stmt_points->execute()) throw new Exception("Error al transferir puntos.");
        $stmt_points->close();

        // d) Registrar transacción
        $stmt_trans = $mysqli->prepare("INSERT INTO transactions (from_user_id, to_user_id, amount, type, related_submission_id) VALUES (?, ?, ?, 'task_completion', ?)");
        $stmt_trans->bind_param("iiii", $user_id, $executor_id, $points_value, $submission_id);
        if (!$stmt_trans->execute()) throw new Exception("Error al registrar la transacción.");
        $stmt_trans->close();

        // e) Crear notificación de éxito para el ejecutor
        $message = "¡Buenas noticias! Tu envío para la tarea \"{$task_title_safe}\" ha sido APROBADO. Has ganado {$points_value} puntos.";
        create_notification($executor_id, $message, 'my_tasks.php?tab=completed', $mysqli);
        
        $response_message = 'Envío aprobado. Se han transferido los puntos.';

    } elseif ($action == 'reject') {
        // --- LÓGICA DE RECHAZO ---
        $rejection_text = trim($_POST['rejection_reason_text'] ?? '');
        $rejection_image_url = trim($_POST['rejection_image_url'] ?? '');
        
        $combined_rejection_reason = '';
        if (!empty($rejection_text)) {
            $combined_rejection_reason .= "Texto de prueba: " . $rejection_text;
        }
        if (!empty($rejection_image_url)) {
            if (!empty($combined_rejection_reason)) $combined_rejection_reason .= "\n\n";
            $combined_rejection_reason .= "Imagen de prueba: " . $rejection_image_url;
        }

        if (empty($combined_rejection_reason)) {
             throw new Exception("Debes proporcionar una razón (texto o imagen) para rechazar el envío.");
        }

        // a) Actualizar estado del envío a 'rejected' y guardar la razón
        $stmt_sub = $mysqli->prepare("UPDATE submissions SET status = 'rejected', rejection_reason = ? WHERE id = ?");
        $stmt_sub->bind_param("si", $combined_rejection_reason, $submission_id);
        if (!$stmt_sub->execute()) throw new Exception("Error al actualizar el envío.");
        $stmt_sub->close();

        // b) Reactivar la tarea para que otro la pueda hacer
        $stmt_task = $mysqli->prepare("UPDATE tasks SET status = 'active', reserved_by = NULL, reserved_at = NULL WHERE id = ?");
        $stmt_task->bind_param("i", $task_id);
        if (!$stmt_task->execute()) throw new Exception("Error al reactivar la tarea.");
        $stmt_task->close();

        // c) Crear notificación de rechazo para el ejecutor
        $message_executor = "Atención: Tu envío para la tarea \"{$task_title_safe}\" ha sido RECHAZADO. Puedes apelar la decisión desde tu panel de tareas.";
        create_notification($executor_id, $message_executor, 'my_tasks.php?tab=completed', $mysqli);
        
        // CAMBIO: Crear notificación para el creador informando que su tarea está activa de nuevo
        $message_creator = "Has rechazado el envío de {$executor_username_safe} para tu tarea \"{$task_title_safe}\". La tarea ha sido reactivada y está disponible para otros usuarios.";
        create_notification($user_id, $message_creator, 'my_tasks.php?tab=published', $mysqli);

        $response_message = 'Envío rechazado correctamente.';
    }

    // 4. Actualizar Reputación (para ambas acciones)
    calculate_and_update_reputation($executor_id, $mysqli); // Actualiza la fiabilidad del ejecutor
    calculate_and_update_reputation($user_id, $mysqli);    // Actualiza la justicia del creador

    // Si todo fue bien, confirmar la transacción
    $mysqli->commit();
    echo json_encode(['status' => 'success', 'message' => $response_message]);

} catch (Exception $e) {
    $mysqli->rollback();
    // Para depuración: error_log("Error en review_submission: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

?>

