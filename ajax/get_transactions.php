<?php
// Círculo Activo - ajax/get_transactions.php
session_start();
require_once '../includes/db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado.']);
    exit();
}
$user_id = $_SESSION['user_id'];

// Obtener las últimas 10 transacciones del usuario
$stmt = $mysqli->prepare("
    SELECT amount, type, timestamp, from_user_id 
    FROM transactions 
    WHERE from_user_id = ? OR to_user_id = ? 
    ORDER BY timestamp DESC LIMIT 10
");
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$transactions = [];
while ($row = $result->fetch_assoc()) {
    // Determinar si es un ingreso o un egreso para el usuario actual
    if ($row['from_user_id'] == $user_id) {
        $row['amount'] = -$row['amount']; // Es un egreso
    }
    $transactions[] = $row;
}
$stmt->close();

echo json_encode(['status' => 'success', 'transactions' => $transactions]);
?>
