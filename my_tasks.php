<?php
// Círculo Activo - my_tasks.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
require_once 'includes/db_connect.php';
require_once 'includes/header.php';

// Determinar la pestaña activa
$active_tab = $_GET['tab'] ?? 'published';

?>

<div class="main-container">
    <div class="card">
        <h1>Panel de Tareas</h1>
        <p>Gestiona tus actividades y revisa tu historial de trabajo en la comunidad.</p>

        <div class="tabs">
            <a href="my_tasks.php?tab=published" class="tab-link <?php echo ($active_tab == 'published') ? 'active' : ''; ?>">Mis Tareas Publicadas</a>
            <a href="my_tasks.php?tab=in_progress" class="tab-link <?php echo ($active_tab == 'in_progress') ? 'active' : ''; ?>">Tareas en Proceso</a>
            <a href="my_tasks.php?tab=completed" class="tab-link <?php echo ($active_tab == 'completed') ? 'active' : ''; ?>">Historial de tareas completadas</a>
        </div>

        <?php if ($active_tab == 'published'): ?>
            <div>
                <h3>Tus tareas y envíos pendientes</h3>
                 <?php
                $stmt_published = $mysqli->prepare("SELECT id, title, status FROM tasks WHERE creator_id = ? ORDER BY created_at DESC");
                $stmt_published->bind_param("i", $user_id);
                $stmt_published->execute();
                $published_tasks = $stmt_published->get_result();

                if ($published_tasks->num_rows > 0) {
                    while ($task = $published_tasks->fetch_assoc()) {
                        echo '<div class="card" style="margin-top: 1rem; background-color: #f9f9f9;">';
                        echo '<h4>' . htmlspecialchars($task['title']) . ' (Estado: ' . ucfirst($task['status']) . ')</h4>';

                        $stmt_submissions = $mysqli->prepare("
                            SELECT s.id, s.proof_data, u.username as executor_username, u.id as executor_id
                            FROM submissions s JOIN users u ON s.executor_id = u.id
                            WHERE s.task_id = ? AND s.status = 'pending_review'
                        ");
                        $stmt_submissions->bind_param("i", $task['id']);
                        $stmt_submissions->execute();
                        $submissions = $stmt_submissions->get_result();

                        if ($submissions->num_rows > 0) {
                            echo '<p><strong>Envíos pendientes de revisión:</strong></p>';
                            while ($sub = $submissions->fetch_assoc()) {
                                echo '<div class="proof-display-container">';
                                echo '<p><strong>Envío de:</strong> <span class="user-link" data-user-id="' . $sub['executor_id'] . '">' . htmlspecialchars($sub['executor_username']) . '</span></p>';
                                $proof_parts = parse_proof($sub['proof_data']);
                                if ($proof_parts['text']) echo '<p><strong>Prueba (texto):</strong> ' . nl2br(htmlspecialchars($proof_parts['text'])) . '</p>';
                                if ($proof_parts['image_url']) echo '<img src="' . htmlspecialchars($proof_parts['image_url']) . '" alt="Prueba de completado" class="proof-image" loading="lazy">';
                                                                echo '<div style="margin-top: 10px;">
                                                                                <button class="btn btn-success btn-small review-btn approve" data-submission-id="' . $sub['id'] . '">Aprobar</button> 
                                                                                <button class="btn btn-danger btn-small reject-btn" data-submission-id="' . $sub['id'] . '">Rechazar</button>
                                                                            </div></div>';
                            }
                        } else {
                            echo '<p>No hay envíos pendientes por ahora.</p>';
                        }
                        
                        // CAMBIO: Contar mensajes no leídos para esta tarea
                        $stmt_chat_count = $mysqli->prepare("SELECT COUNT(*) as count FROM task_messages WHERE task_id = ? AND receiver_id = ? AND is_read = 0");
                        $stmt_chat_count->bind_param("ii", $task['id'], $user_id);
                        $stmt_chat_count->execute();
                        $unread_chat_count = $stmt_chat_count->get_result()->fetch_assoc()['count'];
                        $stmt_chat_count->close();

                        // Botón de chat actualizado con el contador
                        echo '<div style="margin-top: 10px;">
                                <button class="btn btn-secondary btn-small open-chat-btn" data-task-id="' . $task['id'] . '">
                                    💬 Ver Mensajes' . ($unread_chat_count > 0 ? ' <span class="chat-badge">' . $unread_chat_count . '</span>' : '') . '
                                </button>';
                        if ($task['status'] == 'active') {
                           echo ' <button class="btn btn-secondary btn-small cancel-task-btn" data-task-id="' . $task['id'] . '">Cancelar Tarea</button>';
                        }
                        echo '</div>';

                        $stmt_submissions->close();
                        echo '</div>';
                    }
                } else {
                    echo '<p>Aún no has publicado ninguna tarea.</p>';
                }
                $stmt_published->close();
                ?>
            </div>
        <?php elseif ($active_tab == 'in_progress'): ?>
             <div>
                <h3>Tareas que estás realizando</h3>
                <p>Estas son las tareas que has reservado y cuyo tiempo para completar aún no ha expirado.</p>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Tarea</th>
                                <th>Tiempo Restante</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                             <?php
                            $stmt_progress = $mysqli->prepare("
                                SELECT t.id, t.title, t.time_limit, t.reserved_at
                                FROM tasks t 
                                LEFT JOIN submissions s ON t.id = s.task_id AND s.executor_id = ?
                                WHERE t.reserved_by = ? AND t.status = 'reviewing'
                                AND t.reserved_at >= UTC_TIMESTAMP() - INTERVAL t.time_limit MINUTE
                                AND s.id IS NULL
                                ORDER BY t.reserved_at DESC
                            ");
                            $stmt_progress->bind_param("ii", $user_id, $user_id);
                            $stmt_progress->execute();
                            $progress_tasks = $stmt_progress->get_result();

                             if ($progress_tasks->num_rows > 0) {
                                while ($prog_task = $progress_tasks->fetch_assoc()) {
                                    $reserved_at_time = new DateTime($prog_task['reserved_at'], new DateTimeZone('UTC'));
                                    $now_time = new DateTime('now', new DateTimeZone('UTC'));
                                    $interval = $now_time->getTimestamp() - $reserved_at_time->getTimestamp();
                                    $time_left_seconds = ($prog_task['time_limit'] * 60) - $interval;
                                    $time_left_minutes = floor($time_left_seconds / 60);

                                    echo '<tr>';
                                    echo '<td>' . htmlspecialchars($prog_task['title']) . '</td>';
                                    echo '<td>Aprox. ' . $time_left_minutes . ' minutos</td>';
                                    echo '<td><a href="task_view.php?id=' . $prog_task['id'] . '" class="btn btn-primary btn-small">Continuar Tarea</a></td>';
                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr><td colspan="3">No tienes ninguna tarea en proceso en este momento.</td></tr>';
                            }
                             $stmt_progress->close();
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php elseif ($active_tab == 'completed'): ?>
            <div>
                <h3>Tu Historial de Tareas Completadas</h3>
                <div class="table-responsive">
                     <table>
                        <thead>
                            <tr>
                                <th>Tarea</th>
                                <th>Creador</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                             <?php
                            $stmt_completed = $mysqli->prepare("
                                SELECT s.id, s.status, s.submitted_at, s.proof_data, s.rejection_reason, t.title, u.username as creator_username, u.id as creator_id
                                FROM submissions s JOIN tasks t ON s.task_id = t.id JOIN users u ON t.creator_id = u.id
                                WHERE s.executor_id = ? ORDER BY s.submitted_at DESC
                            ");
                            $stmt_completed->bind_param("i", $user_id);
                            $stmt_completed->execute();
                            $completed_tasks = $stmt_completed->get_result();

                            if ($completed_tasks->num_rows > 0) {
                                while ($comp_task = $completed_tasks->fetch_assoc()) {
                                    echo '<tr>';
                                    echo '<td>' . htmlspecialchars($comp_task['title']) . '</td>';
                                    echo '<td><span class="user-link" data-user-id="' . $comp_task['creator_id'] . '">' . htmlspecialchars($comp_task['creator_username']) . '</span></td>';
                                    echo '<td>' . date('d/m/Y', strtotime($comp_task['submitted_at'])) . '</td>';
                                    echo '<td><span class="status-badge status-' . str_replace('_', '-', $comp_task['status']) . '">' . ucfirst(str_replace('_', ' ', $comp_task['status'])) . '</span></td>';
                                    echo '<td>';
                                    if ($comp_task['status'] == 'rejected') {
                                        echo '<button class="btn btn-warning btn-small appeal-btn" data-submission-id="' . $comp_task['id'] . '">Apelar</button>';
                                    } else {
                                        // CAMBIO: Contar mensajes no leídos para la tarea completada
                                        $stmt_chat_count_comp = $mysqli->prepare("SELECT COUNT(*) as count FROM task_messages WHERE task_id = ? AND receiver_id = ? AND is_read = 0");
                                        $stmt_chat_count_comp->bind_param("ii", $comp_task['task_id'], $user_id); // Se usa task_id de la tarea completada
                                        $stmt_chat_count_comp->execute();
                                        $unread_chat_count_comp = $stmt_chat_count_comp->get_result()->fetch_assoc()['count'];
                                        $stmt_chat_count_comp->close();
                                        
                                        echo '<button class="btn btn-secondary btn-small open-chat-btn" data-task-id="' . $comp_task['task_id'] . '">
                                                Ver Chat' . ($unread_chat_count_comp > 0 ? ' <span class="chat-badge">' . $unread_chat_count_comp . '</span>' : '') . '
                                              </button>';
                                    }
                                    echo '</td></tr>';
                                    
                                    echo '<tr class="details-row"><td colspan="5"><div class="proof-display-container">';
                                    $proof_parts = parse_proof($comp_task['proof_data']);
                                    echo '<strong>Tu prueba enviada:</strong>';
                                    if ($proof_parts['text']) echo '<p>' . nl2br(htmlspecialchars($proof_parts['text'])) . '</p>';
                                    if ($proof_parts['image_url']) echo '<img src="' . htmlspecialchars($proof_parts['image_url']) . '" alt="Prueba enviada" class="proof-image">';
                                    if ($comp_task['status'] == 'rejected' && !empty($comp_task['rejection_reason'])) {
                                         $rejection_parts = parse_proof($comp_task['rejection_reason']);
                                         echo '<hr style="margin: 10px 0;"><strong>Razón del Rechazo:</strong>';
                                         if ($rejection_parts['text']) echo '<p>' . nl2br(htmlspecialchars($rejection_parts['text'])) . '</p>';
                                         if ($rejection_parts['image_url']) echo '<img src="' . htmlspecialchars($rejection_parts['image_url']) . '" alt="Prueba de rechazo" class="proof-image">';
                                    }
                                    echo '</div></td></tr>';
                                }
                            } else {
                                echo '<tr><td colspan="5">Aún no has completado ninguna tarea.</td></tr>';
                            }
                            $stmt_completed->close();
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modales -->
<div id="reject-modal" class="modal-overlay">
    <div class="modal-content">
        <form id="reject-form">
            <div class="modal-header"><h2>Rechazar Envío</h2><button type="button" class="modal-close">&times;</button></div>
            <div class="modal-body">
                <p>Por favor, explica por qué estás rechazando este envío. Puedes incluir una imagen como prueba.</p>
                <div class="form-group"><label for="rejection_reason_text">Razón del rechazo (opcional si subes imagen):</label><textarea name="rejection_reason_text" class="form-control" rows="3"></textarea></div>
                <div class="form-group"><label>Sube una captura de pantalla de prueba (opcional):</label><div class="image-uploader"><p>Arrastra, pega o selecciona una imagen</p></div><div class="image-preview-container" style="display:none; margin-top:1rem;"><img src="#" alt="Vista previa" class="image-preview"></div><input type="hidden" name="rejection_image_url" value=""></div>
                <div id="reject-form-message"></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary modal-close">Cancelar</button><button type="submit" class="btn btn-danger">Confirmar Rechazo</button></div>
        </form>
    </div>
</div>
<div id="image-viewer-modal" class="modal-overlay">
    <div class="modal-content" style="padding: 0; max-width: 90vw; background: transparent;"><span class="modal-close" style="position: absolute; top: 10px; right: 20px; color: white; font-size: 2rem; text-shadow: 1px 1px 3px black;">&times;</span><img src="" alt="Vista ampliada" style="width: 100%; max-height: 90vh; object-fit: contain;"></div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
// --- Actualización automática de contadores de chat ---
function updateChatBadges() {
    document.querySelectorAll('.open-chat-btn').forEach(function(btn) {
        var taskId = btn.dataset.taskId;
        fetch('ajax/get_task_messages.php', {
            method: 'POST',
            body: new URLSearchParams({ task_id: taskId })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                var unread = data.messages.filter(m => m.receiver_id == data.current_user_id && m.is_read == 0).length;
                var badge = btn.querySelector('.chat-badge');
                if (unread > 0) {
                    if (!badge) {
                        var span = document.createElement('span');
                        span.className = 'chat-badge';
                        span.textContent = unread;
                        btn.appendChild(span);
                    } else {
                        badge.textContent = unread;
                    }
                } else if (badge) {
                    badge.remove();
                }
            }
        });
    });
}
setInterval(updateChatBadges, 30000); // Actualiza cada 30 segundos
window.addEventListener('DOMContentLoaded', updateChatBadges);
</script>

