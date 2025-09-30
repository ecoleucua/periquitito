<?php
// Círculo Activo - Cabecera de la página
// includes/header.php

// Iniciar la sesión si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inicializar variables para evitar errores
$user_points_balance = 0;
$user_avatar = 'icon/perfil.png'; // Avatar por defecto
$unread_notifications_count = 0;
$current_page = basename($_SERVER['PHP_SELF']);

// Si el usuario ha iniciado sesión, obtener sus datos
if (isset($_SESSION['user_id'])) {
    $user_id_header = $_SESSION['user_id'];
    
    // Es crucial que la conexión a la base de datos exista antes de este punto.
    // Asumimos que la página principal (index.php, profile.php, etc.) ya ha incluido db_connect.php
    if (isset($mysqli)) {
        // Obtener saldo de puntos, avatar y si es admin en una sola consulta
        $stmt_user_data = $mysqli->prepare("SELECT points_balance, avatar_url, is_admin, reputation_tier FROM users WHERE id = ?");
        $stmt_user_data->bind_param("i", $user_id_header);
        $stmt_user_data->execute();
        $user_data_result = $stmt_user_data->get_result();
        if ($user_data = $user_data_result->fetch_assoc()) {
            $user_points_balance = $user_data['points_balance'];
            if (!empty($user_data['avatar_url'])) {
                $user_avatar = $user_data['avatar_url'];
            }
            // Guardar en sesión para no tener que consultarlo en cada página
            $_SESSION['is_admin'] = (bool)$user_data['is_admin'];
            $_SESSION['reputation_tier'] = $user_data['reputation_tier'];
        }
        $stmt_user_data->close();

        // Contar notificaciones no leídas
        $stmt_notif = $mysqli->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = FALSE");
        $stmt_notif->bind_param("i", $user_id_header);
        $stmt_notif->execute();
        $unread_notifications_count = $stmt_notif->get_result()->fetch_assoc()['count'];
        $stmt_notif->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Círculo Activo</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <header class="top-nav">
        <a href="index.php" class="logo">Círculo Activo</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="user-info">
                <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                    <a href="admin/index.php" class="btn admin-btn">Admin</a>
                <?php endif; ?>
                
                <a href="#" id="points-history-btn" class="user-balance">
                    🪙 <span><?php echo htmlspecialchars($user_points_balance); ?></span>
                </a>
                
                <a href="#" id="notifications-btn" class="notification-bell">
                    <i class="fa-solid fa-bell"></i>
                    <?php if ($unread_notifications_count > 0): ?>
                        <span class="notification-count"><?php echo $unread_notifications_count; ?></span>
                    <?php endif; ?>
                </a>
                
                <a href="profile.php">
                    <img src="<?php echo htmlspecialchars($user_avatar); ?>" alt="Avatar" class="user-avatar-sm">
                </a>
                
                <a href="logout.php" class="btn btn-secondary">Salir</a>
            </div>
        <?php else: ?>
            <div class="user-info">
                <a href="login.php" class="btn btn-secondary">Iniciar Sesión</a>
                <a href="register.php" class="btn btn-primary">Registrarse</a>
            </div>
        <?php endif; ?>
    </header>

    <!-- Paneles Flotantes (inicialmente ocultos) -->
    <div id="notifications-panel" class="floating-panel"></div>
    <div id="points-history-panel" class="floating-panel"></div>

    <!-- El resto del contenido de la página irá aquí -->

