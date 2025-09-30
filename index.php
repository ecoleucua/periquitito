<?php
// Círculo Activo - index.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];
require_once 'includes/db_connect.php';

// --- CORRECCIÓN DE ZONA HORARIA ---
// Liberar tareas cuyo tiempo de reserva ha expirado, usando UTC_TIMESTAMP para consistencia.
$mysqli->query("
    UPDATE tasks 
    SET status = 'active', reserved_by = NULL, reserved_at = NULL 
    WHERE status = 'reviewing' 
      AND reserved_by IS NOT NULL 
      AND reserved_at < UTC_TIMESTAMP() - INTERVAL time_limit MINUTE
");

require_once 'includes/header.php';

// Obtener la lista de tareas activas
$stmt = $mysqli->prepare("
    SELECT t.id, t.title, t.points_value, t.link, t.time_limit, u.username as creator_username, u.id as creator_id, u.reputation_tier
    FROM tasks t
    JOIN users u ON t.creator_id = u.id
    WHERE t.status = 'active' AND t.creator_id != ?
    ORDER BY t.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$tasks_result = $stmt->get_result();
?>

<!-- Contenido HTML de la página -->
<div class="main-container">
    <h1 style="margin-bottom: 1.5rem;">Tareas Disponibles</h1>

    <div class="task-list">
        <?php if ($tasks_result->num_rows > 0): ?>
            <?php while ($task = $tasks_result->fetch_assoc()): ?>
                <div class="task-card">
                    <div class="task-card-image">
                        <?php
                        $link_lower = strtolower($task['link'] ?? '');
                        $icon_src = 'icon/otro.png';
                        if (strpos($link_lower, 'ali') !== false || strpos($link_lower, 'aliexpress') !== false) {
                            $icon_src = 'icon/aliexpress.png';
                        } elseif (strpos($link_lower, 'temu') !== false) {
                            $icon_src = 'icon/temu.png';
                        } elseif (strpos($link_lower, 'shein') !== false) {
                            $icon_src = 'icon/shein.png';
                        }
                        ?>
                        <img src="<?php echo htmlspecialchars($icon_src); ?>" alt="Icono de tarea">
                    </div>
                    <div class="task-card-content">
                        <h2><?php echo htmlspecialchars($task['title']); ?></h2>
                        <p>
                            Por: <span class="user-link" data-user-id="<?php echo $task['creator_id']; ?>">
                                <?php echo htmlspecialchars($task['creator_username']); ?>
                            </span>
                            <?php
                                $tier_icon = '🥉';
                                if ($task['reputation_tier'] == 'silver') $tier_icon = '🥈';
                                if ($task['reputation_tier'] == 'gold') $tier_icon = '🥇';
                                echo $tier_icon;
                            ?>
                        </p>
                        <p style="font-size: 0.8rem; color: #888;">Tiempo para completar: <?php echo htmlspecialchars($task['time_limit']); ?> min.</p>
                    </div>
                    <div class="task-card-actions">
                        <span class="task-points">🪙 <?php echo htmlspecialchars($task['points_value']); ?> Puntos</span>
                        <button class="btn btn-primary reserve-task-btn" data-task-id="<?php echo $task['id']; ?>">Comenzar Tarea</button>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="card" style="text-align: center; padding: 2rem;">
                <h3>¡Vaya! Parece que no hay tareas disponibles.</h3>
                <p>¿Por qué no eres el primero en publicar una?</p>
                <a href="publish_task.php" class="btn btn-primary" style="margin-top: 1rem;">Publicar una Tarea</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal para Confirmar Reserva de Tarea -->
<div id="reserve-modal" class="modal-overlay">
    <div class="modal-content">
        <form id="reserve-form">
            <div class="modal-header">
                <h2>Confirmar Tarea</h2>
                <button type="button" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <p>Estás a punto de comenzar esta tarea. Una vez que confirmes, tendrás un tiempo limitado para completarla y se ocultará de la lista pública.</p>
                <p>¿Estás listo para empezar?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary modal-close">Cancelar</button>
                <button type="submit" class="btn btn-primary">Sí, comenzar ahora</button>
            </div>
        </form>
    </div>
</div>


<?php
$stmt->close();
require_once 'includes/footer.php';
?>

