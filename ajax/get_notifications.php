<?php
// Círculo Activo - ajax/get_notifications.php
session_start();
require_once '../includes/db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado.']);
    exit();
}
$user_id = $_SESSION['user_id'];

// Obtener las últimas 10 notificaciones
$stmt = $mysqli->prepare("SELECT message, link, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$notifications = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Marcar estas notificaciones como leídas
$mysqli->query("UPDATE notifications SET is_read = TRUE WHERE user_id = {$user_id} AND is_read = FALSE");

echo json_encode(['status' => 'success', 'notifications' => $notifications]);
?>
