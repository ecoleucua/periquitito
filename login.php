<?php
// Círculo Activo
// Archivo: login.php (Versión Corregida y Robusta)

// 1. Iniciar la sesión y la conexión a la BD ANTES de cualquier salida HTML.
require_once 'includes/db_connect.php';
session_start();

// 2. Si el usuario ya está logueado, redirigir a la página principal.
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error_message = '';

// 3. Procesar el formulario de inicio de sesión si se envió.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username_or_email = $_POST['username_or_email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username_or_email) || empty($password)) {
        $error_message = "Debes completar todos los campos.";
    } else {
        // Buscar al usuario por nombre de usuario O email.
        $stmt = $mysqli->prepare("SELECT id, username, password_hash FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username_or_email, $username_or_email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        // Verificar la contraseña.
        if ($user && password_verify($password, $user['password_hash'])) {
            // Contraseña correcta. Iniciar sesión.
            
            // Regenerar el ID de sesión para prevenir fijación de sesión.
            session_regenerate_id(true);

            // Limpiar variables de sesión antiguas para forzar la recarga de datos en la siguiente página.
            unset($_SESSION['user_data_loaded']);
            unset($_SESSION['points_balance']);
            unset($_SESSION['is_admin']);
            unset($_SESSION['avatar_url']);
            unset($_SESSION['unread_notifications']);

            // Establecer las nuevas variables de sesión.
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            
            // Redirigir al usuario al dashboard.
            header("Location: index.php");
            exit(); // Es crucial salir del script después de una redirección.

        } else {
            // Usuario o contraseña incorrectos.
            $error_message = "El nombre de usuario o la contraseña son incorrectos.";
        }
    }
    $mysqli->close();
}

// 4. Si el script llega hasta aquí, significa que se debe mostrar el formulario.
// Ahora sí, incluimos la cabecera, que ya puede usar la conexión a la BD si es necesario.
require_once 'includes/header.php';
?>

<div class="card" style="max-width: 500px; margin: 2rem auto;">
    <h1 style="text-align: center; margin-bottom: 1.5rem;">Iniciar Sesión</h1>

    <?php if (!empty($error_message)): ?>
        <div style="background-color: #f8d7da; color: #721c24; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
            <p style="margin:0;"><?php echo htmlspecialchars($error_message); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['status']) && $_GET['status'] == 'registered'): ?>
         <div style="background-color: #d4edda; color: #155724; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
            <p style="margin:0;">¡Registro completado! Ya puedes iniciar sesión.</p>
        </div>
    <?php endif; ?>

    <form action="login.php" method="post">
        <div class="form-group">
            <label for="username_or_email">Usuario o Correo</label>
            <input type="text" id="username_or_email" name="username_or_email" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%;">Entrar</button>
    </form>
    <p style="text-align: center; margin-top: 1.5rem;">
        ¿No tienes una cuenta? <a href="register.php" style="color: var(--color-primary); text-decoration: none; font-weight: 600;">Regístrate</a>
    </p>
</div>

<?php
// Incluir el pie de página
require_once 'includes/footer.php';
?>

