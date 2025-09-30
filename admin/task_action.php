<?php
// Círculo Activo - Panel de Administración
// Archivo: admin/task_action.php

require_once 'admin_header.php'; // Seguridad de administrador

$action = $_GET['action'] ?? '';
$task_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$allowed_actions = ['pause', 'activate', 'delete'];

if (!$task_id || !in_array($action, $allowed_actions)) {
    header("Location: manage_tasks.php?error=invalid_action");
    exit();
}

// Lógica de Acciones
if ($action === 'pause' || $action === 'activate') {
    $new_status = ($action === 'pause') ? 'paused' : 'active';
    $stmt = $mysqli->prepare("UPDATE tasks SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $task_id);
    $stmt->execute();
    $stmt->close();
} elseif ($action === 'delete') {
    $stmt = $mysqli->prepare("DELETE FROM tasks WHERE id = ?");
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    $stmt->close();
}

$mysqli->close();
header("Location: manage_tasks.php?success=action_completed");
exit();
?>
