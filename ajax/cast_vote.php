<?php
// Círculo Activo
// Archivo: ajax/cast_vote.php

header('Content-Type: application/json');
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
session_start();

define('JURY_SIZE', 3);
define('VOTES_TO_WIN', ceil(JURY_SIZE / 2));

// 1. Seguridad y Validación
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado.']);
    exit();
}

$juror_id = $_SESSION['user_id'];
$dispute_id = filter_input(INPUT_POST, 'dispute_id', FILTER_VALIDATE_INT);
$vote = $_POST['vote'] ?? '';

if (!$dispute_id || !in_array($vote, ['executor', 'creator'])) {
    echo json_encode(['status' => 'error', 'message' => 'Voto no válido.']);
    exit();
}

// Validar que el usuario es Nivel Oro
$stmt_tier = $mysqli->prepare("SELECT reputation_tier FROM users WHERE id = ?");
$stmt_tier->bind_param("i", $juror_id);
$stmt_tier->execute();
if ($stmt_tier->get_result()->fetch_assoc()['reputation_tier'] !== 'gold') {
    echo json_encode(['status' => 'error', 'message' => 'Solo miembros Nivel Oro pueden votar.']);
    exit();
}
$stmt_tier->close();

// Obtener info de la disputa para validar que no sea parte y que no haya votado
$stmt_info = $mysqli->prepare("SELECT s.executor_id, t.creator_id, d.status FROM disputes d JOIN submissions s ON d.submission_id = s.id JOIN tasks t ON s.task_id = t.id WHERE d.id = ?");
$stmt_info->bind_param("i", $dispute_id);
$stmt_info->execute();
$dispute_info = $stmt_info->get_result()->fetch_assoc();
$stmt_info->close();

if (!$dispute_info || $dispute_info['status'] !== 'voting' || $dispute_info['creator_id'] == $juror_id || $dispute_info['executor_id'] == $juror_id) {
    echo json_encode(['status' => 'error', 'message' => 'No puedes votar en este caso.']);
    exit();
}

// 2. Registrar el Voto
try {
    $stmt_vote = $mysqli->prepare("INSERT INTO jury_votes (dispute_id, juror_id, vote) VALUES (?, ?, ?)");
    $stmt_vote->bind_param("iis", $dispute_id, $juror_id, $vote);
    $stmt_vote->execute();
    $stmt_vote->close();
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Ya has votado en este caso.']);
    exit();
}

// 3. Lógica de Veredicto
$stmt_count = $mysqli->prepare("SELECT vote, COUNT(*) as count FROM jury_votes WHERE dispute_id = ? GROUP BY vote");
$stmt_count->bind_param("i", $dispute_id);
$stmt_count->execute();
$votes = $stmt_count->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_count->close();

$counts = ['executor' => 0, 'creator' => 0];
foreach ($votes as $v) {
    $counts[$v['vote']] = $v['count'];
}

$verdict_reached = false;
$winner = null;

if ($counts['executor'] >= VOTES_TO_WIN) {
    $verdict_reached = true;
    $winner = 'executor';
} elseif ($counts['creator'] >= VOTES_TO_WIN) {
    $verdict_reached = true;
    $winner = 'creator';
}

if ($verdict_reached) {
    $mysqli->begin_transaction();
    try {
        // Obtener datos necesarios para la resolución
        $stmt_data = $mysqli->prepare("SELECT s.id as submission_id, s.executor_id, t.creator_id, t.points_value, t.title FROM disputes d JOIN submissions s ON d.submission_id = s.id JOIN tasks t ON s.task_id = t.id WHERE d.id = ?");
        $stmt_data->bind_param("i", $dispute_id);
        $stmt_data->execute();
        $data = $stmt_data->get_result()->fetch_assoc();
        $stmt_data->close();

        $new_dispute_status = 'resolved_' . $winner;
        $mysqli->prepare("UPDATE disputes SET status = ? WHERE id = ?")->execute([$new_dispute_status, $dispute_id]);

        if ($winner === 'executor') {
            // Gana el Ejecutor: Lógica de aprobación
            $mysqli->prepare("UPDATE submissions SET status = 'approved' WHERE id = ?")->execute([$data['submission_id']]);
            $mysqli->prepare("UPDATE users SET points_balance = points_balance + ? WHERE id = ?")->execute([$data['points_value'], $data['executor_id']]);
            $mysqli->prepare("INSERT INTO transactions (from_user_id, to_user_id, amount, type, related_submission_id) VALUES (?, ?, ?, 'task_completion', ?)")->execute([$data['creator_id'], $data['executor_id'], $data['points_value'], $data['submission_id']]);
            
            create_notification($data['executor_id'], "Un jurado ha fallado a tu favor en el caso de \"{$data['title']}\". Has recibido {$data['points_value']} puntos.", 'my_tasks.php', $mysqli);
            create_notification($data['creator_id'], "Un jurado ha resuelto una disputa sobre tu tarea \"{$data['title']}\" a favor del ejecutor.", 'my_tasks.php', $mysqli);
        } else {
            // Gana el Creador: El rechazo se mantiene
            create_notification($data['executor_id'], "Un jurado ha confirmado el rechazo de tu envío para la tarea \"{$data['title']}\".", 'my_tasks.php', $mysqli);
            create_notification($data['creator_id'], "Un jurado ha fallado a tu favor en la disputa sobre tu tarea \"{$data['title']}\".", 'my_tasks.php', $mysqli);
        }

        // Actualizar reputación de AMBAS partes
        calculate_and_update_reputation($data['executor_id'], $mysqli);
        calculate_and_update_reputation($data['creator_id'], $mysqli);

        $mysqli->commit();
        echo json_encode(['status' => 'success', 'message' => 'Voto registrado. ¡Se ha alcanzado un veredicto!', 'verdict' => true]);
    } catch (Exception $e) {
        $mysqli->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Error crítico al procesar el veredicto.']);
    }
} else {
    echo json_encode(['status' => 'success', 'message' => 'Tu voto ha sido registrado.', 'verdict' => false]);
}

$mysqli->close();
?>
