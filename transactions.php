<?php
// Círculo Activo - transactions.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];
require_once 'includes/db_connect.php';
require_once 'includes/header.php';

// Obtener TODAS las transacciones del usuario
$stmt = $mysqli->prepare("
    SELECT amount, type, timestamp, from_user_id, to_user_id 
    FROM transactions 
    WHERE from_user_id = ? OR to_user_id = ? 
    ORDER BY timestamp DESC
");
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="main-container">
    <div class="card">
        <h1>Historial de Puntos</h1>
        <p>Aquí puedes ver todos los movimientos de puntos en tu cuenta.</p>

        <div class="table-responsive" style="margin-top: 1.5rem;">
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Descripción</th>
                        <th>Monto</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <?php
                            $is_expense = ($row['from_user_id'] == $user_id);
                            $amount = $is_expense ? -$row['amount'] : +$row['amount'];
                            $color = $is_expense ? 'var(--color-danger)' : 'var(--color-success)';
                            
                            // Descripción más amigable
                            $description = ucfirst(str_replace('_', ' ', $row['type']));
                            switch ($row['type']) {
                                case 'task_completion': $description = $is_expense ? 'Pago por tarea completada' : 'Recompensa por tarea'; break;
                                case 'posting_fee': $description = 'Publicación de tarea'; break;
                                case 'initial_bonus': $description = 'Bono de bienvenida'; break;
                                case 'refund': $description = 'Reembolso por tarea cancelada'; break;
                            }
                            ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($row['timestamp'])); ?></td>
                                <td><?php echo $description; ?></td>
                                <td style="color: <?php echo $color; ?>; font-weight: 600;">
                                    <?php echo ($amount > 0 ? '+' : '') . $amount; ?> Puntos
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" style="text-align: center;">No hay transacciones para mostrar.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php 
$stmt->close();
require_once 'includes/footer.php'; 
?>
