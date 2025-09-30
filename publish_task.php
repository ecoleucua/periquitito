<?php
// Círculo Activo - publish_task.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
require_once 'includes/db_connect.php';

// Obtener saldo actual del usuario
$stmt_balance = $mysqli->prepare("SELECT points_balance FROM users WHERE id = ?");
$stmt_balance->bind_param("i", $user_id);
$stmt_balance->execute();
$user_balance = $stmt_balance->get_result()->fetch_assoc()['points_balance'];
$stmt_balance->close();

$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recolección y Validación de Datos
    $title_option = $_POST['title_option'] ?? '';
    $title_custom = $_POST['title'] ?? '';
    $title = ($title_option === 'Otro') ? trim($title_custom) : trim($title_option);
    $instructions = trim($_POST['instructions'] ?? '');
    $link = filter_input(INPUT_POST, 'link', FILTER_SANITIZE_URL);
    $category = filter_input(INPUT_POST, 'category', FILTER_VALIDATE_INT);
    $time_limit = filter_input(INPUT_POST, 'time_limit', FILTER_VALIDATE_INT);

    if (empty($title)) $errors[] = "El título es obligatorio.";
    if (empty($instructions)) $errors[] = "Las instrucciones son obligatorias.";
    if (empty($link)) $errors[] = "El enlace es obligatorio.";
    if (!$category) $errors[] = "La categoría no es válida.";
    if (!$time_limit) $errors[] = "El límite de tiempo no es válido.";

    if (empty($errors)) {
        // Cálculo de Puntos
        $points_map = [1 => 10, 2 => 25, 3 => 50, 4 => 100];
        $points_value = $points_map[$category] ?? 0;
        $posting_fee = floor($points_value * 0.10);
        $total_cost = $points_value + $posting_fee;

        if ($user_balance < $total_cost) {
            $errors[] = "No tienes suficientes puntos. Necesitas {$total_cost}, pero solo tienes {$user_balance}.";
        } else {
            // --- LÓGICA CRÍTICA: Transacción Atómica ---
            $mysqli->begin_transaction();
            try {
                // 1. Deducir puntos del creador
                $stmt_deduct = $mysqli->prepare("UPDATE users SET points_balance = points_balance - ? WHERE id = ?");
                $stmt_deduct->bind_param("ii", $total_cost, $user_id);
                if (!$stmt_deduct->execute()) throw new Exception("Error al deducir puntos.");
                $stmt_deduct->close();

                // 2. Registrar la transacción de la tarifa
                $stmt_trans = $mysqli->prepare("INSERT INTO transactions (from_user_id, amount, type) VALUES (?, ?, 'posting_fee')");
                $stmt_trans->bind_param("ii", $user_id, $total_cost);
                if (!$stmt_trans->execute()) throw new Exception("Error al registrar transacción.");
                $stmt_trans->close();

                // 3. Crear la tarea (sin proof_requirement)
                $stmt_create = $mysqli->prepare("INSERT INTO tasks (creator_id, title, instructions, link, category, points_value, time_limit) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt_create->bind_param("isssiis", $user_id, $title, $instructions, $link, $category, $points_value, $time_limit);
                if (!$stmt_create->execute()) throw new Exception("Error al crear la tarea.");
                $stmt_create->close();

                $mysqli->commit();
                $success_message = "¡Tarea publicada con éxito! Se han deducido {$total_cost} puntos de tu saldo.";
                // Actualizar el saldo en la página
                $user_balance -= $total_cost;

            } catch (Exception $e) {
                $mysqli->rollback();
                $errors[] = "Ocurrió un error inesperado al publicar la tarea. Por favor, inténtalo de nuevo.";
            }
        }
    }
}

require_once 'includes/header.php';
?>

<div class="main-container">
    <div class="card">
        <h1>Publicar una Nueva Tarea</h1>
        <p>Tu saldo actual: <strong style="color: var(--color-primary);">🪙 <?php echo $user_balance; ?> Puntos</strong></p>

        <?php if (!empty($errors)): ?>
            <div class="message error">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="message success">
                <p><?php echo $success_message; ?></p>
                <a href="index.php" class="btn btn-secondary" style="margin-top: 1rem;">Volver al Inicio</a>
            </div>
        <?php else: ?>
            <form method="POST" action="publish_task.php" id="publish-form" style="margin-top: 1.5rem;">
                <!-- ... (resto del formulario HTML) ... -->
                 <div class="form-group">
                    <label for="title-select">Título de la Tarea</label>
                    <select id="title-select" name="title_option" class="form-control">
                        <option value="La Granja">La Granja</option>
                        <option value="El Acuario">El Acuario</option>
                        <option value="Sombrero Mágico">Sombrero Mágico</option>
                        <option value="Otro">Otro (especificar)</option>
                    </select>
                </div>
                <div class="form-group" id="title-input-container">
                    <input type="text" id="title-input" name="title" class="form-control" placeholder="Escribe el título de tu tarea" style="display: none;">
                </div>
                 <div class="form-group">
                    <label for="link">Enlace (URL donde se realizará la tarea)</label>
                    <input type="url" id="link" name="link" class="form-control" required placeholder="https://ejemplo.com/tarea">
                </div>
                <div class="form-group">
                    <label for="instructions">Instrucciones Detalladas (incluye aquí la prueba que deben enviar)</label>
                    <textarea id="instructions" name="instructions" class="form-control" rows="5" required></textarea>
                </div>
                <!-- CAMBIO: El campo "Requisitos para la Prueba" se ha eliminado -->
                 <div class="form-group">
                    <label for="category">Categoría (Recompensa)</label>
                    <select id="category" name="category" class="form-control" required>
                        <option value="">Selecciona una categoría</option>
                        <option value="1">Categoría 1 (10 Puntos)</option>
                        <option value="2">Categoría 2 (25 Puntos)</option>
                        <option value="3">Categoría 3 (50 Puntos)</option>
                        <option value="4">Categoría 4 (100 Puntos)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="time_limit">Tiempo Límite para Completar (en minutos)</label>
                    <select id="time_limit" name="time_limit" class="form-control" required>
                        <option value="10">10 minutos</option>
                        <option value="15">15 minutos</option>
                        <option value="20">20 minutos</option>
                        <option value="30">30 minutos</option>
                        <option value="60">60 minutos</option>
                    </select>
                </div>
                <div class="card" style="background-color: #f9f9f9;" id="cost-summary">
                    <p>Recompensa al Ejecutor: <span id="reward">--</span></p>
                    <p>Tarifa de Plataforma (10%): <span id="fee">--</span></p>
                    <hr>
                    <p><strong>Total a Deducir de tu Saldo: <span id="total-cost">--</span></strong></p>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1.5rem;">Publicar Tarea</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

