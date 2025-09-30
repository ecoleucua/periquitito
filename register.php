<?php
// Círculo Activo
// Archivo: register.php (Versión Corregida y Robusta)

// 1. Iniciar la conexión y la sesión
require_once 'includes/db_connect.php';
session_start();

// 2. Si el usuario ya está logueado, redirigir.
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$errors = [];
$username = '';
$email = '';

// 3. Procesar el formulario si se envió.
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validaciones
    if (empty($username)) {
        $errors[] = "El nombre de usuario es obligatorio.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        $errors[] = "El nombre de usuario solo puede contener letras, números y guiones bajos (3-20 caracteres).";
    }

    if (empty($email)) {
        $errors[] = "El correo electrónico es obligatorio.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "El formato del correo electrónico no es válido.";
    }

    if (empty($password)) {
        $errors[] = "La contraseña es obligatoria.";
    } elseif (strlen($password) < 8) {
        $errors[] = "La contraseña debe tener al menos 8 caracteres.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Las contraseñas no coinciden.";
    }

    if (empty($errors)) {
        $stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "El correo electrónico ya está registrado.";
        }
        $stmt->close();

        $stmt = $mysqli->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "El nombre de usuario ya está en uso.";
        }
        $stmt->close();

        if (empty($errors)) {
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $mysqli->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $email, $password_hash);
            
            if ($stmt->execute()) {
                header("Location: login.php?status=registered");
                exit();
            } else {
                $errors[] = "Ocurrió un error en el registro. Por favor, inténtalo de nuevo.";
            }
            $stmt->close();
        }
    }
    $mysqli->close();
}

// 4. Incluir la cabecera y mostrar el formulario HTML
require_once 'includes/header.php';
?>

<div class="card" style="max-width: 500px; margin: 2rem auto;">
    <h1 style="text-align: center; margin-bottom: 1.5rem;">Crear una Cuenta</h1>
    
    <?php
    if (!empty($errors)) {
        echo '<div style="background-color: #fdd; border: 1px solid var(--color-error); padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">';
        foreach ($errors as $error) {
            echo '<p style="margin: 0; color: var(--color-error);">' . htmlspecialchars($error) . '</p>';
        }
        echo '</div>';
    }
    ?>

    <form action="register.php" method="post" novalidate>
        <div class="form-group">
            <label for="username">Nombre de usuario</label>
            <input type="text" id="username" name="username" class="form-control" required value="<?php echo htmlspecialchars($username); ?>">
        </div>
        <div class="form-group">
            <label for="email">Correo electrónico</label>
            <input type="email" id="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($email); ?>">
        </div>
        <div class="form-group">
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirmar Contraseña</label>
            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%;">Registrarse</button>
    </form>
    <p style="text-align: center; margin-top: 1.5rem;">
        ¿Ya tienes una cuenta? <a href="login.php" style="color: var(--color-primary); text-decoration: none; font-weight: 600;">Inicia Sesión</a>
    </p>
</div>

<?php
require_once 'includes/footer.php';
?>

