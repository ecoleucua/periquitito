<?php
// Círculo Activo - task_view.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
require_once 'includes/db_connect.php';

// Validar el ID de la tarea
$task_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$task_id) {
    header("Location: index.php");
    exit();
}

require_once 'includes/header.php';

// Obtener detalles de la tarea
$stmt = $mysqli->prepare("
    SELECT t.*, u.username as creator_username, u.id as creator_id, u.reputation_tier
    FROM tasks t
    JOIN users u ON t.creator_id = u.id
    WHERE t.id = ?
");
$stmt->bind_param("i", $task_id);
$stmt->execute();
$result = $stmt->get_result();
$task = $result->fetch_assoc();
$stmt->close();

if (!$task) {
    echo '<div class="main-container"><div class="card">Tarea no encontrada.</div></div>';
    require_once 'includes/footer.php';
    exit();
}

$time_left = 0;
if ($task['status'] == 'reviewing' && $task['reserved_by'] == $user_id && $task['reserved_at']) {
    $reserved_at_time = new DateTime($task['reserved_at'], new DateTimeZone('UTC'));
    $now_time = new DateTime('now', new DateTimeZone('UTC'));
    $interval = $now_time->getTimestamp() - $reserved_at_time->getTimestamp();
    $total_seconds_limit = $task['time_limit'] * 60;
    $time_left = $total_seconds_limit - $interval;
    if ($time_left < 0) $time_left = 0;
}

$is_task_owner = ($task['creator_id'] == $user_id);
// Un usuario puede ver el chat si es el dueño o si tiene la tarea reservada o ya la ha enviado
$can_chat = $is_task_owner || ($task['reserved_by'] == $user_id) || in_array($task['status'], ['completed', 'rejected', 'in_dispute']);
$can_submit_proof = ($task['status'] == 'reviewing' && $task['reserved_by'] == $user_id && $time_left > 0);

// CAMBIO: Contar mensajes de chat no leídos para esta tarea
$unread_chat_messages_count = 0;
if ($can_chat) {
    $stmt_chat_count = $mysqli->prepare("SELECT COUNT(*) as count FROM task_messages WHERE task_id = ? AND receiver_id = ? AND is_read = 0");
    $stmt_chat_count->bind_param("ii", $task_id, $user_id);
    $stmt_chat_count->execute();
    $unread_chat_messages_count = $stmt_chat_count->get_result()->fetch_assoc()['count'];
    $stmt_chat_count->close();
}
?>

<div class="main-container">
    <div class="card task-detail-card">
        <h1><?php echo htmlspecialchars($task['title']); ?></h1>
        <div class="task-detail-meta">
            Publicada por <span class="user-link" data-user-id="<?php echo $task['creator_id']; ?>"><?php echo htmlspecialchars($task['creator_username']); ?></span> |
            Recompensa: 🪙 <?php echo htmlspecialchars($task['points_value']); ?> Puntos
        </div>

        <?php if ($can_submit_proof): ?>
            <div id="countdown-timer" class="card" style="margin-bottom: 1rem; text-align: center; background-color: var(--color-warning);">
                <h3 style="margin:0;">Tiempo restante: <span id="timer" data-time-left="<?php echo $time_left; ?>">--:--</span></h3>
            </div>
        <?php endif; ?>

        <div class="task-detail-section">
            <h3>Instrucciones de la Tarea</h3>
            <p><?php echo nl2br(htmlspecialchars($task['instructions'])); ?></p>
        </div>
        
        <?php if (!empty($task['link'])): ?>
        <div class="task-detail-section">
            <h3>Enlace de la Tarea</h3>
            <p><a href="<?php echo htmlspecialchars($task['link']); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($task['link']); ?></a></p>
        </div>
        <?php endif; ?>

        <!-- CAMBIO: Sección de comunicación separada y más limpia -->
        <?php if ($can_chat): ?>
        <div class="task-detail-section">
            <h3>Comunicación</h3>
            <!-- CAMBIO: Botón de chat actualizado con el contador -->
            <button class="btn btn-secondary open-chat-btn" data-task-id="<?php echo $task['id']; ?>">
                💬 Ver / Enviar Mensaje
                <?php if ($unread_chat_messages_count > 0): ?>
                    <span class="chat-badge"><?php echo $unread_chat_messages_count; ?></span>
                <?php endif; ?>
            </button>
        </div>
        <?php endif; ?>

        <?php if ($can_submit_proof): ?>
            <div class="task-detail-section">
                <h3>Enviar Prueba de Completado</h3>
                <form id="proof-form">
                     <div class="proof-submission-area">
                        <div class="form-group">
                            <label for="proof_data_text">Escribe tu prueba (opcional si subes una imagen):</label>
                            <textarea id="proof_data_text" name="proof_data_text" class="form-control" placeholder="Ej: He completado la tarea, mi nombre de usuario es 'ejemplo123'..."></textarea>
                        </div>
                        <div class="form-group">
                            <label>Sube una captura de pantalla (opcional si escribes una prueba):</label>
                            <div class="image-uploader">
                                <i class="fa-solid fa-cloud-arrow-up" style="font-size: 2rem; color: #ccc; margin-bottom: 1rem;"></i>
                                <p>Arrastra una imagen aquí, pégala, o haz clic para seleccionarla.</p>
                            </div>
                            <div class="image-preview-container" style="display: none; margin-top: 1rem;">
                                <img class="image-preview" src="#" alt="Vista previa de la imagen">
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                    <input type="hidden" name="proof_data_image_url" value="">
                    <div id="form-message" style="margin-top: 1rem;"></div>
                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Enviar prueba para revisión</button>
                </form>
            </div>
        <?php elseif (!$is_task_owner): ?>
            <div class="card" style="text-align:center; margin-top: 2rem; background-color: #f1f1f1;">
                <p>Esta tarea no está disponible para ser completada en este momento.</p>
                <?php if ($task['status'] == 'reviewing' && $task['reserved_by'] != $user_id) echo '<p>Actualmente está siendo realizada por otro usuario.</p>'; ?>
                <?php if ($task['status'] == 'completed') echo '<p>Esta tarea ya fue completada.</p>'; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!$is_task_owner): ?>
        <div style="margin-top: 2rem; text-align: right;">
             <button id="report-task-btn" class="btn btn-secondary btn-small" data-task-id="<?php echo $task['id']; ?>">🚩 Reportar Tarea</button>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

