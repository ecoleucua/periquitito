<?php
// Círculo Activo
// Archivo: includes/functions.php

/**
 * NUEVO: Crea una notificación para un usuario.
 *
 * @param int $user_id El ID del usuario a notificar.
 * @param string $message El mensaje de la notificación.
 * @param string|null $link Un enlace opcional asociado a la notificación.
 * @param mysqli $mysqli El objeto de conexión a la base de datos.
 * @return void
 */
function create_notification($user_id, $message, $link, $mysqli) {
    $sql = "INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("iss", $user_id, $message, $link);
        $stmt->execute();
        $stmt->close();
    }
}


/**
 * Actualiza el Nivel de Reputación (Tier) de un usuario basado en sus estadísticas.
 * (código existente del paso 7)
 * ...
 */
function update_user_tier($user_id, $mysqli) {
    // ... (código existente)
    $stmt = $mysqli->prepare("SELECT reliability_score, fairness_score, reputation_tier FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_stats = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user_stats) return;

    $stmt_tasks = $mysqli->prepare("SELECT COUNT(*) AS total_tasks FROM submissions WHERE executor_id = ? AND status = 'approved'");
    $stmt_tasks->bind_param("i", $user_id);
    $stmt_tasks->execute();
    $tasks_count = $stmt_tasks->get_result()->fetch_assoc()['total_tasks'];
    $stmt_tasks->close();

    $new_tier = 'bronze';

    if ($tasks_count >= 100 && $user_stats['reliability_score'] >= 95 && $user_stats['fairness_score'] >= 90) {
        $new_tier = 'gold';
    } elseif ($tasks_count >= 25 && $user_stats['reliability_score'] >= 90) {
        $new_tier = 'silver';
    }

    if ($new_tier !== $user_stats['reputation_tier']) {
        $stmt_update = $mysqli->prepare("UPDATE users SET reputation_tier = ? WHERE id = ?");
        $stmt_update->bind_param("si", $new_tier, $user_id);
        $stmt_update->execute();
        $stmt_update->close();
    }
}


/**
 * Calcula y actualiza los scores de reputación (Fiabilidad y Justicia) de un usuario.
 * (código existente del paso 7)
 * ...
 */
function calculate_and_update_reputation($user_id, $mysqli) {
    // ... (código existente)
    $stmt_reliability = $mysqli->prepare("SELECT COUNT(*) AS total, SUM(IF(status = 'approved', 1, 0)) AS approved FROM submissions WHERE executor_id = ?");
    $stmt_reliability->bind_param("i", $user_id);
    $stmt_reliability->execute();
    $rel_data = $stmt_reliability->get_result()->fetch_assoc();
    $stmt_reliability->close();
    
    $reliability_score = ($rel_data['total'] > 0) ? ($rel_data['approved'] / $rel_data['total']) * 100 : 100.0;

    $stmt_fairness = $mysqli->prepare(
        "SELECT COUNT(s.id) AS reviewed_submissions, SUM(IF(s.status = 'approved', 1, 0)) AS approved_submissions
         FROM tasks t JOIN submissions s ON t.id = s.task_id
         WHERE t.creator_id = ? AND s.status IN ('approved', 'rejected')"
    );
    $stmt_fairness->bind_param("i", $user_id);
    $stmt_fairness->execute();
    $fair_data = $stmt_fairness->get_result()->fetch_assoc();
    $stmt_fairness->close();

    $fairness_score = ($fair_data['reviewed_submissions'] > 0) ? ($fair_data['approved_submissions'] / $fair_data['reviewed_submissions']) * 100 : 100.0;

    $stmt_update = $mysqli->prepare("UPDATE users SET reliability_score = ?, fairness_score = ? WHERE id = ?");
    $stmt_update->bind_param("ddi", $reliability_score, $fairness_score, $user_id);
    $stmt_update->execute();
    $stmt_update->close();

    update_user_tier($user_id, $mysqli);
}
?>

