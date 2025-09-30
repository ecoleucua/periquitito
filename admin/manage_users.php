<?php
// Círculo Activo - Gestión de Usuarios
require_once 'admin_header.php';

$search = $_GET['search'] ?? '';
$query = "SELECT id, username, email, points_balance, reputation_tier, status FROM users";

if (!empty($search)) {
    $query .= " WHERE username LIKE ? OR email LIKE ?";
} else {
    $query .= " ORDER BY created_at DESC LIMIT 20";
}

$stmt = $mysqli->prepare($query);
if (!empty($search)) {
    $search_param = "%" . $search . "%";
    $stmt->bind_param("ss", $search_param, $search_param);
}
$stmt->execute();
$users = $stmt->get_result();

?>

<div class="admin-container">
    <h1 style="margin-bottom: 0.5rem;">Gestionar Usuarios</h1>
    <p style="margin-bottom: 2rem; color: var(--admin-text-light);">Busca, visualiza y modera a los usuarios de la plataforma.</p>

    <div class="admin-card">
        <form method="GET" action="manage_users.php" style="display: flex; gap: 1rem; margin-bottom: 1.5rem;">
            <input type="text" name="search" class="form-control" placeholder="Buscar por usuario o email..." value="<?php echo htmlspecialchars($search); ?>" style="flex-grow: 1;">
            <button type="submit" class="btn btn-primary">Buscar</button>
        </form>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Email</th>
                        <th>Puntos</th>
                        <th>Tier</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $users->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo $user['points_balance']; ?></td>
                            <td><?php echo ucfirst($user['reputation_tier']); ?></td>
                            <td><span class="status-badge status-<?php echo $user['status']; ?>"><?php echo ucfirst($user['status']); ?></span></td>
                            <td>
                                <a href="../profile.php?id=<?php echo $user['id']; ?>" class="btn btn-secondary btn-small">Ver</a>
                                <?php if ($user['status'] == 'active'): ?>
                                    <a href="user_action.php?action=suspend&id=<?php echo $user['id']; ?>" class="btn btn-warning btn-small">Suspender</a>
                                <?php elseif ($user['status'] == 'suspended'): ?>
                                    <a href="user_action.php?action=reactivate&id=<?php echo $user['id']; ?>" class="btn btn-success btn-small">Reactivar</a>
                                <?php endif; ?>
                                <a href="user_action.php?action=ban&id=<?php echo $user['id']; ?>" class="btn btn-danger btn-small" onclick="return confirm('¿Estás seguro de que quieres banear a este usuario? Esta acción no se puede deshacer.')">Banear</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php $stmt->close(); require_once 'admin_footer.php'; ?>

