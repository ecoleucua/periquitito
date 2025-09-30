<?php
// Círculo Activo - Panel de Administración: Gestionar Tareas
require_once 'admin_header.php';

// Lógica de filtros y búsqueda
$filter = $_GET['filter'] ?? 'recent';
$sql = "
    SELECT t.id, t.title, t.status, t.report_count, u.username as creator_username, u.id as creator_id
    FROM tasks t
    JOIN users u ON t.creator_id = u.id
";

// Aplicar filtros a la consulta
$where_clauses = [];
switch ($filter) {
    case 'reported':
        $where_clauses[] = "t.report_count > 0";
        $sql .= " WHERE " . implode(' AND ', $where_clauses) . " ORDER BY t.report_count DESC, t.created_at DESC";
        break;
    case 'active':
        $where_clauses[] = "t.status = 'active'";
        $sql .= " WHERE " . implode(' AND ', $where_clauses) . " ORDER BY t.created_at DESC";
        break;
    case 'paused':
        $where_clauses[] = "t.status = 'paused'";
        $sql .= " WHERE " . implode(' AND ', $where_clauses) . " ORDER BY t.created_at DESC";
        break;
     case 'reviewing':
        $where_clauses[] = "t.status = 'reviewing'";
        $sql .= " WHERE " . implode(' AND ', $where_clauses) . " ORDER BY t.created_at DESC";
        break;
    default: // 'recent'
        $sql .= " ORDER BY t.created_at DESC";
}
$sql .= " LIMIT 50";

$tasks = $mysqli->query($sql);
?>

<div class="admin-container">
    <h1 style="margin-bottom: 0.5rem;">Gestionar Tareas</h1>
    <p style="margin-bottom: 2rem; color: var(--admin-text-light);">Modera el contenido, revisa reportes y mantén la calidad de la plataforma.</p>

    <div class="admin-card">
        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1.5rem;">
            <a href="?filter=recent" class="btn btn-secondary <?php echo ($filter == 'recent') ? 'active' : ''; ?>">Más Recientes</a>
            <a href="?filter=reported" class="btn btn-danger <?php echo ($filter == 'reported') ? 'active' : ''; ?>">Más Reportadas</a>
            <a href="?filter=active" class="btn btn-success <?php echo ($filter == 'active') ? 'active' : ''; ?>">Activas</a>
            <a href="?filter=reviewing" class="btn btn-warning <?php echo ($filter == 'reviewing') ? 'active' : ''; ?>">En Revisión</a>
            <a href="?filter=paused" class="btn btn-secondary <?php echo ($filter == 'paused') ? 'active' : ''; ?>">Pausadas</a>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Título</th>
                        <th>Creador</th>
                        <th>Estado</th>
                        <th>Reportes</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($tasks && $tasks->num_rows > 0): ?>
                        <?php while ($task = $tasks->fetch_assoc()): ?>
                        <tr class="<?php echo ($task['report_count'] > 0) ? 'highlight-row' : ''; ?>">
                            <td><?php echo $task['id']; ?></td>
                            <td><?php echo htmlspecialchars($task['title']); ?></td>
                            <td><span class="user-link" data-user-id="<?php echo $task['creator_id']; ?>"><?php echo htmlspecialchars($task['creator_username']); ?></span></td>
                            <td><span class="status-badge status-<?php echo str_replace('_', '-', $task['status']); ?>"><?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?></span></td>
                            <td><?php echo $task['report_count']; ?></td>
                            <td>
                                <a href="../task_view.php?id=<?php echo $task['id']; ?>" class="btn btn-secondary btn-small" target="_blank">Ver</a>
                                <?php if ($task['status'] == 'active' || $task['status'] == 'reviewing'): ?>
                                    <a href="task_action.php?action=pause&id=<?php echo $task['id']; ?>" class="btn btn-warning btn-small">Pausar</a>
                                <?php elseif ($task['status'] == 'paused'): ?>
                                    <a href="task_action.php?action=activate&id=<?php echo $task['id']; ?>" class="btn btn-success btn-small">Activar</a>
                                <?php endif; ?>
                                 <a href="task_action.php?action=delete&id=<?php echo $task['id']; ?>" class="btn btn-danger btn-small" onclick="return confirm('¿Estás seguro de ELIMINAR esta tarea? Esta acción es irreversible.')">Eliminar</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">No se encontraron tareas con los filtros seleccionados.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php 
require_once 'admin_footer.php'; 
?>

