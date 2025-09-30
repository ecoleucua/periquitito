<?php
// Círculo Activo - notifications.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];
require_once 'includes/db_connect.php';

// Marcar todas las notificaciones como leídas al visitar esta página
$mysqli->query("UPDATE notifications SET is_read = TRUE WHERE user_id = {$user_id}");

require_once 'includes/header.php';

// Obtener todas las notificaciones del usuario
$stmt = $mysqli->prepare("SELECT message, link, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<div class="main-container">
    <div class="card">
        <h1>Todas las Notificaciones</h1>
        
        <div class="notification-list" style="margin-top: 1.5rem;">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($n = $result->fetch_assoc()): ?>
                    <a href="<?php echo htmlspecialchars($n['link'] ?? '#'); ?>" class="notification-item read"> <!-- Todas se marcan como leídas visualmente -->
                        <i class="fa-solid fa-info-circle"></i>
                        <div class="notification-content">
                            <p><?php echo htmlspecialchars($n['message']); ?></p>
                            <span class="notification-time"><?php echo date('d/m/Y H:i', strtotime($n['created_at'])); ?></span>
                        </div>
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="text-align: center;">No tienes notificaciones.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php 
$stmt->close();
require_once 'includes/footer.php'; 
?>

