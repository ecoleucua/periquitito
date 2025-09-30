<?php
// Círculo Activo
// Archivo: settings.php

require_once 'includes/db_connect.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$errors = [];
$success_message = '';

// Procesar cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_new_password = $_POST['confirm_new_password'];

    // Obtener hash de la contraseña actual
    $stmt = $mysqli->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user && password_verify($current_password, $user['password_hash'])) {
        if (!empty($new_password) && $new_password === $confirm_new_password) {
            if (strlen($new_password) >= 8) {
                $new_password_hash = password_hash($new_password, PASSWORD_BCRYPT);
                $stmt_update = $mysqli->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $stmt_update->bind_param("si", $new_password_hash, $user_id);
                if ($stmt_update->execute()) {
                    $success_message = "Tu contraseña ha sido actualizada con éxito.";
                } else {
                    $errors[] = "Error al actualizar la contraseña.";
                }
                $stmt_update->close();
            } else {
                $errors[] = "La nueva contraseña debe tener al menos 8 caracteres.";
            }
        } else {
            $errors[] = "Las nuevas contraseñas no coinciden o están vacías.";
        }
    } else {
        $errors[] = "La contraseña actual es incorrecta.";
    }
}

// Obtener info del usuario para la foto de perfil
$stmt_user = $mysqli->prepare("SELECT username, email, avatar_url FROM users WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_info = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

require_once 'includes/header.php';
?>

<div class="main-container">
    <h1>Configuración de la Cuenta</h1>
    <div class="profile-grid" style="align-items: flex-start;">
        
        <!-- Columna de Foto de Perfil -->
        <div class="card">
             <h3>Foto de Perfil</h3>
             <div class="profile-sidebar" style="margin-bottom: 1rem;">
                <div class="profile-avatar-xl-wrapper">
                    <img src="<?php echo htmlspecialchars($user_info['avatar_url'] ?? 'icon/perfil.png'); ?>" alt="Avatar de <?php echo htmlspecialchars($user_info['username']); ?>" class="profile-avatar-xl" id="current-avatar-img">
                </div>
             </div>
             <div id="avatar-uploader" class="image-uploader" style="padding: 1rem;">
                <i class="fa-solid fa-camera image-uploader-icon" style="font-size: 2rem;"></i>
                <p style="margin-top: 0.5rem; font-size: 0.9rem;">Haz clic para cambiar tu foto</p>
             </div>
             <input type="file" id="avatar_input" accept="image/png, image/jpeg, image/gif, image/bmp" style="display: none;">
             <div id="avatar-form-message" style="margin-top: 1rem; text-align: center;"></div>
        </div>

        <!-- Columna de Cambio de Contraseña -->
        <div class="card">
            <h3>Cambiar Contraseña</h3>

            <?php if (!empty($success_message)): ?>
                <div style="background-color: #d4edda; color: #155724; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;"><?php echo $success_message; ?></div>
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
                <div style="background-color: #f8d7da; color: #721c24; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                    <?php foreach ($errors as $error): ?>
                        <p style="margin:0;"><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form action="settings.php" method="post">
                <input type="hidden" name="change_password" value="1">
                <div class="form-group">
                    <label for="current_password">Contraseña Actual</label>
                    <input type="password" name="current_password" id="current_password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="new_password">Nueva Contraseña</label>
                    <input type="password" name="new_password" id="new_password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="confirm_new_password">Confirmar Nueva Contraseña</label>
                    <input type="password" name="confirm_new_password" id="confirm_new_password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

