<?php
// Círculo Activo
// Archivo: profile.php

// CORRECCIÓN: Reorganización completa para arreglar el error 500

// 1. Iniciar sesión
session_start();

// 2. Conectar a la base de datos
require_once 'includes/db_connect.php';

// 3. Incluir el header
require_once 'includes/header.php';

// 4. Lógica de la página
$user_id_to_view = $_SESSION['user_id']; // Por defecto, ver el perfil propio
if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $user_id_to_view = $_GET['id'];
}

// Obtener información del usuario del perfil
$stmt = $mysqli->prepare("
    SELECT username, email, points_balance, reputation_tier, reliability_score, fairness_score, avatar_url 
    FROM users WHERE id = ?
");
$stmt->bind_param("i", $user_id_to_view);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    echo '<div class="main-container"><div class="card"><p>Usuario no encontrado.</p></div></div>';
    require_once 'includes/footer.php';
    exit();
}

$is_own_profile = ($user_id_to_view == ($_SESSION['user_id'] ?? null));

// Obtener estadísticas
$stmt_completed = $mysqli->prepare("SELECT COUNT(*) as count FROM submissions WHERE executor_id = ? AND status = 'approved'");
$stmt_completed->bind_param("i", $user_id_to_view);
$stmt_completed->execute();
$completed_count = $stmt_completed->get_result()->fetch_assoc()['count'];
$stmt_completed->close();

$stmt_published = $mysqli->prepare("SELECT COUNT(*) as count FROM tasks WHERE creator_id = ?");
$stmt_published->bind_param("i", $user_id_to_view);
$stmt_published->execute();
$published_count = $stmt_published->get_result()->fetch_assoc()['count'];
$stmt_published->close();

$tier_icon = '🥉';
$tier_name = 'Bronce';
if ($user['reputation_tier'] == 'silver') {
    $tier_icon = '🥈';
    $tier_name = 'Plata';
}
if ($user['reputation_tier'] == 'gold') {
    $tier_icon = '🥇';
    $tier_name = 'Oro';
}

$user_avatar_profile = $user['avatar_url'] ?? 'icon/perfil.png';

?>

<div class="main-container">
    <div class="profile-header">
        <img src="<?php echo htmlspecialchars($user_avatar_profile); ?>" alt="Avatar de <?php echo htmlspecialchars($user['username']); ?>" class="profile-avatar">
        <div class="profile-info">
            <h1><?php echo htmlspecialchars($user['username']); ?></h1>
            <span class="reputation-badge"><?php echo $tier_icon . ' Nivel ' . $tier_name; ?></span>
        </div>
        <?php if ($is_own_profile): ?>
            <a href="settings.php" class="btn btn-secondary btn-small" style="margin-top: 1rem;"><i class="fa-solid fa-gear"></i> Configuración de la Cuenta</a>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2 style="margin-bottom: 1.5rem; text-align: center;">Estadísticas de Reputación</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($user['reliability_score'], 1); ?>%</div>
                <div class="stat-label">Fiabilidad como Ejecutor</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($user['fairness_score'], 1); ?>%</div>
                <div class="stat-label">Justicia como Creador</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $completed_count; ?></div>
                <div class="stat-label">Tareas Completadas</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $published_count; ?></div>
                <div class="stat-label">Tareas Publicadas</div>
            </div>
            <?php if ($is_own_profile): ?>
            <div class="stat-card">
                <div class="stat-value">🪙 <?php echo $user['points_balance']; ?></div>
                <div class="stat-label">Saldo de Puntos</div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>

