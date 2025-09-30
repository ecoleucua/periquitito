
<?php
// Círculo Activo - Panel de Administración
// Archivo: admin/user_action.php

// La seguridad es lo primero. Este header verifica si el usuario es admin.
require_once 'admin_header.php';

$action = $_GET['action'] ?? '';
$user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$allowed_actions = ['suspend', 'reactivate', 'ban'];
$new_status = '';

if (!$user_id || !in_array($action, $allowed_actions)) {
    header("Location: manage_users.php?error=invalid_action");
    exit();
}

// Mapear acción a nuevo estado en la base de datos
switch ($action) {
    case 'suspend':
        $new_status = 'suspended';
        break;
    case 'reactivate':
        $new_status = 'active';
        break;
    case 'ban':
        $new_status = 'banned';
        break;
}

if ($new_status) {
    $stmt = $mysqli->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $user_id);
    $stmt->execute();
    $stmt->close();
}

$mysqli->close();
header("Location: manage_users.php?success=action_completed");
exit();
?>
