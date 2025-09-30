<?php
// Círculo Activo
// Archivo: ajax/upload_avatar.php
// Procesa la subida del avatar del usuario y actualiza la base de datos.

header('Content-Type: application/json');
require_once '../includes/db_connect.php';
session_start();

// 1. Verificación de seguridad
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Acción no autorizada.']);
    exit();
}

// 2. Obtener la URL de la imagen del POST
$avatar_url = $_POST['avatar_url'] ?? null;

if (empty($avatar_url) || !filter_var($avatar_url, FILTER_VALIDATE_URL)) {
    echo json_encode(['status' => 'error', 'message' => 'URL de imagen no válida.']);
    exit();
}

$user_id = $_SESSION['user_id'];

// 3. Actualizar la base de datos
try {
    $stmt = $mysqli->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
    $stmt->bind_param("si", $avatar_url, $user_id);

    if ($stmt->execute()) {
        // Opcional: Actualizar la URL del avatar en la sesión para que se refleje inmediatamente en todas las páginas
        $_SESSION['avatar_url'] = $avatar_url;
        echo json_encode(['status' => 'success', 'message' => '¡Tu foto de perfil ha sido actualizada!']);
    } else {
        throw new Exception("Error al actualizar la base de datos.");
    }

    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Ocurrió un error en el servidor. Inténtalo de nuevo.']);
}

$mysqli->close();
?>

