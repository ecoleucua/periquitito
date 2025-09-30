<?php
// Círculo Activo - dispute_center.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
require_once 'includes/db_connect.php';

// CORRECCIÓN: Verificar el tier del usuario directamente desde la BD para asegurar que esté actualizado
$stmt_tier_check = $mysqli->prepare("SELECT reputation_tier FROM users WHERE id = ?");
$stmt_tier_check->bind_param("i", $user_id);
$stmt_tier_check->execute();
$user_tier_result = $stmt_tier_check->get_result();
$user_tier_data = $user_tier_result->fetch_assoc();
$stmt_tier_check->close();

$user_tier = $user_tier_data['reputation_tier'] ?? 'bronze';

require_once 'includes/header.php';

// Seguridad: Usar el tier recién obtenido de la BD
if ($user_tier !== 'gold') {
    echo '<div class="main-container"><div class="card" style="text-align: center;"><h2>Acceso Denegado</h2><p>Solo los miembros de la comunidad con Nivel Oro pueden formar parte del jurado.</p></div></div>';
    require_once 'includes/footer.php';
    exit();
}

// Obtener disputas activas en las que el usuario no ha votado y no es parte implicada
$stmt = $mysqli->prepare("
    SELECT 
        d.id AS dispute_id,
        t.title AS task_title,
        t.instructions AS task_instructions,
        s.proof_data,
        s.rejection_reason,
        creator.username AS creator_username,
        executor.username AS executor_username,
        creator.id AS creator_id,
        executor.id AS executor_id
    FROM disputes d
    JOIN submissions s ON d.submission_id = s.id
    JOIN tasks t ON s.task_id = t.id
    JOIN users creator ON t.creator_id = creator.id
    JOIN users executor ON s.executor_id = executor.id
    LEFT JOIN jury_votes jv ON d.id = jv.dispute_id AND jv.juror_id = ?
    WHERE d.status = 'voting'
      AND t.creator_id != ?
      AND s.executor_id != ?
      AND jv.id IS NULL
    ORDER BY d.created_at ASC
");
$stmt->bind_param("iii", $user_id, $user_id, $user_id);
$stmt->execute();
$disputes = $stmt->get_result();
?>

<div class="main-container">
    <div class="card">
        <h1>Centro de Disputas (Jurado)</h1>
        <p>Gracias por tu servicio a la comunidad. Revisa los siguientes casos y vota de manera justa.</p>
    </div>

    <?php if ($disputes->num_rows > 0): ?>
        <?php while ($case = $disputes->fetch_assoc()): ?>
            <div class="card dispute-case">
                <h3>Caso de Disputa: <?php echo htmlspecialchars($case['task_title']); ?></h3>
                <p>
                    <strong>Creador:</strong> <span class="user-link" data-user-id="<?php echo $case['creator_id']; ?>"><?php echo htmlspecialchars($case['creator_username']); ?></span> vs. 
                    <strong>Ejecutor:</strong> <span class="user-link" data-user-id="<?php echo $case['executor_id']; ?>"><?php echo htmlspecialchars($case['executor_username']); ?></span>
                </p>

                <div class="dispute-evidence">
                    <div class="evidence-section">
                        <h4>Instrucciones Originales de la Tarea</h4>
                        <p><?php echo nl2br(htmlspecialchars($case['task_instructions'])); ?></p>
                    </div>

                    <div class="evidence-section">
                        <h4>Prueba Enviada por el Ejecutor</h4>
                        <div class="proof-display-container" style="background-color: #e6f7ff;">
                            <?php 
                            $proof_parts = parse_proof($case['proof_data']);
                            if ($proof_parts['text']) echo '<p>' . nl2br(htmlspecialchars($proof_parts['text'])) . '</p>';
                            if ($proof_parts['image_url']) echo '<img src="' . htmlspecialchars($proof_parts['image_url']) . '" alt="Prueba del ejecutor" class="proof-image">';
                            ?>
                        </div>
                    </div>

                    <div class="evidence-section">
                        <h4>Razón del Rechazo del Creador</h4>
                        <div class="proof-display-container" style="background-color: #fff1f0;">
                             <?php 
                            $rejection_parts = parse_proof($case['rejection_reason']);
                            if ($rejection_parts['text']) echo '<p>' . nl2br(htmlspecialchars($rejection_parts['text'])) . '</p>';
                            if ($rejection_parts['image_url']) echo '<img src="' . htmlspecialchars($rejection_parts['image_url']) . '" alt="Prueba de rechazo" class="proof-image">';
                            ?>
                        </div>
                    </div>
                </div>

                <div class="vote-actions" style="margin-top: 1.5rem; text-align: center;">
                    <h4>¿Quién tiene la razón?</h4>
                    <p>Tu voto es anónimo y ayudará a resolver este caso.</p>
                    <button class="btn btn-success vote-btn" data-dispute-id="<?php echo $case['dispute_id']; ?>" data-vote="executor">Votar por el Ejecutor (Prueba Válida)</button>
                    <button class="btn btn-danger vote-btn" data-dispute-id="<?php echo $case['dispute_id']; ?>" data-vote="creator">Votar por el Creador (Rechazo Justo)</button>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="card" style="text-align: center;">
            <h3>No hay casos pendientes para tu revisión.</h3>
            <p>¡Gracias por mantenerte al día! Vuelve más tarde para ver si hay nuevos casos.</p>
        </div>
    <?php endif; ?>
    <?php $stmt->close(); ?>
</div>


<!-- Modal para visualizar imágenes -->
<div id="image-viewer-modal" class="modal-overlay">
    <div class="modal-content" style="padding: 0; max-width: 90vw; background: transparent;">
         <span class="modal-close" style="position: absolute; top: 10px; right: 20px; color: white; font-size: 2rem; text-shadow: 1px 1px 3px black;">&times;</span>
        <img src="" alt="Vista ampliada" style="width: 100%; max-height: 90vh; object-fit: contain;">
    </div>
</div>


<?php require_once 'includes/footer.php'; ?>

