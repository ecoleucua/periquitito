<?php
// Círculo Activo - Archivo de conexión a la base de datos
// includes/db_connect.php

// Forzamos que todas las operaciones de fecha y hora en PHP se realicen en UTC.
date_default_timezone_set('UTC');


// --- Credenciales de la Base de Datos ---
define('DB_HOST', 'sql303.infinityfree.com');      // Por lo general, 'localhost' o un valor proporcionado por tu host.
define('DB_USER', 'if0_40019340');            // El nombre de usuario que creaste para la base de datos.
define('DB_PASSWORD', 'Rodando12345'); // La contraseña para ese usuario.
define('DB_NAME', 'if0_40019340_intercambiador');        // El nombre de la base de datos que creaste.

// Intentar conectar a la base de datos usando MySQLi
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

// Comprobar si la conexión falló
if ($mysqli->connect_error) {
    die('Error de Conexión (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
}

// Establecer el juego de caracteres a UTF-8 para soportar acentos.
$mysqli->set_charset('utf8mb4');

/**
 * --- CORRECCIÓN DE PARSEO DE PRUEBAS ---
 * Parsea una cadena de texto que puede contener texto y una URL de imagen.
 * Extrae la URL de la imagen y devuelve el texto limpio y la URL por separado.
 * @param string|null $proof_data La cadena de texto a parsear.
 * @return array Un array con las claves 'text' y 'image_url'.
 */
function parse_proof($proof_data) {
    $text = '';
    $image_url = '';

    if (!is_string($proof_data) || empty(trim($proof_data))) {
        return ['text' => '', 'image_url' => ''];
    }

    // Patrón para encontrar URLs de imágenes comunes
    $url_pattern = '/https?:\/\/[^\s]+(\.jpg|\.jpeg|\.png|\.gif|\.bmp)/i';
    
    if (preg_match($url_pattern, $proof_data, $matches)) {
        // Se encontró una URL de imagen
        $image_url = $matches[0];
        
        // El texto es todo lo que NO es la URL
        $text = trim(str_replace($image_url, '', $proof_data));
        
        // Limpiar prefijos que añadimos nosotros mismos
        $text = preg_replace('/^Texto de prueba:\s*/i', '', $text);
        $text = preg_replace('/^Imagen de prueba:\s*/i', '', $text);

    } else {
        // No se encontró URL, todo es texto
        $text = $proof_data;
    }
    
    // Limpieza final del texto por si acaso
    $text = trim(preg_replace('/^Texto de prueba:\s*/i', '', $text));

    return ['text' => $text, 'image_url' => $image_url];
}
?>

