<?php
// Círculo Activo - Cabecera del Panel de Administración
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/db_connect.php';

$is_admin_check = false;
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
        $is_admin_check = true;
    } else {
        // Doble verificación en la BD por seguridad
        $stmt = $mysqli->prepare("SELECT is_admin FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($user = $result->fetch_assoc()) {
            if ($user['is_admin']) {
                $_SESSION['is_admin'] = true;
                $is_admin_check = true;
            }
        }
        $stmt->close();
    }
}

if (!$is_admin_check) {
    header("Location: ../index.php");
    exit();
}

// Determinar la página activa para el estilo de la barra lateral
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Círculo Activo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin_style.css">
</head>
<body>
<div class="admin-wrapper">
    <aside class="admin-sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>Admin Panel</h2>
        </div>
        <nav class="sidebar-nav">
            <a href="index.php" class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="manage_users.php" class="<?php echo ($current_page == 'manage_users.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-users"></i> Gestionar Usuarios
            </a>
            <a href="manage_tasks.php" class="<?php echo ($current_page == 'manage_tasks.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-tasks"></i> Gestionar Tareas
            </a>
        </nav>
        <div class="sidebar-footer">
            <a href="../index.php">
                <i class="fa-solid fa-arrow-left"></i> Volver al Sitio
            </a>
        </div>
    </aside>

    <main class="admin-main-content">
        <header class="admin-header">
            <button id="menu-toggle"><i class="fa-solid fa-bars"></i></button>
            <h3>Panel de Administración</h3>
        </header>

