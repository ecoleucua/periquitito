<?php
// Círculo Activo - Dashboard de Administración
require_once 'admin_header.php';

// Consultas para las estadísticas
$total_users = $mysqli->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$active_tasks = $mysqli->query("SELECT COUNT(*) as count FROM tasks WHERE status = 'active'")->fetch_assoc()['count'];
$pending_submissions = $mysqli->query("SELECT COUNT(*) as count FROM submissions WHERE status = 'pending_review'")->fetch_assoc()['count'];
$total_reports = $mysqli->query("SELECT SUM(report_count) as count FROM tasks")->fetch_assoc()['count'] ?? 0;

?>

<div class="admin-container">
    <h1 style="margin-bottom: 0.5rem;">Dashboard de Administración</h1>
    <p style="margin-bottom: 2rem; color: var(--admin-text-light);">Vista general del estado de la plataforma.</p>

    <div class="stat-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
        
        <div class="admin-card stat-card">
            <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
            <div class="stat-info">
                <div class="stat-number"><?php echo $total_users; ?></div>
                <div class="stat-label">Usuarios Totales</div>
            </div>
        </div>

        <div class="admin-card stat-card">
            <div class="stat-icon" style="color: #38a169; background-color: rgba(56, 161, 105, 0.1);"><i class="fa-solid fa-tasks"></i></div>
            <div class="stat-info">
                <div class="stat-number"><?php echo $active_tasks; ?></div>
                <div class="stat-label">Tareas Activas</div>
            </div>
        </div>

        <div class="admin-card stat-card">
            <div class="stat-icon" style="color: #dd6b20; background-color: rgba(221, 107, 32, 0.1);"><i class="fa-solid fa-hourglass-half"></i></div>
            <div class="stat-info">
                <div class="stat-number"><?php echo $pending_submissions; ?></div>
                <div class="stat-label">Envíos Pendientes</div>
            </div>
        </div>

        <div class="admin-card stat-card">
            <div class="stat-icon" style="color: #e53e3e; background-color: rgba(229, 62, 62, 0.1);"><i class="fa-solid fa-flag"></i></div>
            <div class="stat-info">
                <div class="stat-number"><?php echo $total_reports; ?></div>
                <div class="stat-label">Reportes Totales</div>
            </div>
        </div>

    </div>
</div>

<?php require_once 'admin_footer.php'; ?>

