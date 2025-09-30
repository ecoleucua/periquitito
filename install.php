<?php
// Círculo Activo - Script de Instalación de la Base de Datos
// install.php

// --- INSTRUCCIONES IMPORTANTES ---
// 1. Edita el archivo 'includes/db_connect.php' con tus credenciales de la base de datos.
// 2. Sube todos los archivos a tu servidor.
// 3. Accede a este archivo en tu navegador (ej. http://tusitio.com/install.php).
// 4. UNA VEZ COMPLETADA LA INSTALACIÓN, ¡ELIMINA ESTE ARCHIVO INMEDIATAMENTE!

require_once 'includes/db_connect.php';

// Mensajes para el usuario
$error_message = '';
$success_message = '';

// --- ESQUEMA COMPLETO DE LA BASE DE DATOS ---
$sql_schema = "
-- Desactivar la verificación de claves foráneas temporalmente
SET FOREIGN_KEY_CHECKS=0;

-- Tabla de Usuarios
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `avatar_url` varchar(512) NULL DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `points_balance` int(11) NOT NULL DEFAULT 40,
  `reputation_tier` enum('bronze','silver','gold') NOT NULL DEFAULT 'bronze',
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `reliability_score` float NOT NULL DEFAULT 100.0,
  `fairness_score` float NOT NULL DEFAULT 100.0,
  `status` enum('active','suspended','banned') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de Tareas
DROP TABLE IF EXISTS `tasks`;
CREATE TABLE `tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `creator_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `instructions` text NOT NULL,
  `link` varchar(512) NULL DEFAULT NULL,
  `category` int(11) NOT NULL,
  `points_value` int(11) NOT NULL,
  `status` enum('active','paused','completed','deleted','reviewing') NOT NULL DEFAULT 'active',
  `report_count` int(11) NOT NULL DEFAULT 0,
  `time_limit` int(11) NULL DEFAULT NULL,
  `reserved_by` int(11) NULL DEFAULT NULL,
  `reserved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `creator_id` (`creator_id`),
  CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`creator_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de Envíos (Submissions)
DROP TABLE IF EXISTS `submissions`;
CREATE TABLE `submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL,
  `executor_id` int(11) NOT NULL,
  `proof_data` text NOT NULL,
  `status` enum('pending_review','approved','rejected','in_dispute') NOT NULL DEFAULT 'pending_review',
  `rejection_reason` text NULL DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `task_id` (`task_id`),
  KEY `executor_id` (`executor_id`),
  CONSTRAINT `submissions_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `submissions_ibfk_2` FOREIGN KEY (`executor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de Transacciones
DROP TABLE IF EXISTS `transactions`;
CREATE TABLE `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `from_user_id` int(11) DEFAULT NULL,
  `to_user_id` int(11) DEFAULT NULL,
  `amount` int(11) NOT NULL,
  `type` enum('task_completion','posting_fee','initial_bonus','refund') NOT NULL,
  `related_submission_id` int(11) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de Notificaciones
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `message` varchar(255) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `link` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de Reportes
DROP TABLE IF EXISTS `reports`;
CREATE TABLE `reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL,
  `reporter_id` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `task_id` (`task_id`),
  KEY `reporter_id` (`reporter_id`),
  CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reports_ibfk_2` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de Disputas
DROP TABLE IF EXISTS `disputes`;
CREATE TABLE `disputes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `submission_id` int(11) NOT NULL,
  `status` enum('voting','resolved_executor','resolved_creator') NOT NULL DEFAULT 'voting',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `submission_id` (`submission_id`),
  CONSTRAINT `disputes_ibfk_1` FOREIGN KEY (`submission_id`) REFERENCES `submissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de Votos del Jurado
DROP TABLE IF EXISTS `jury_votes`;
CREATE TABLE `jury_votes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dispute_id` int(11) NOT NULL,
  `juror_id` int(11) NOT NULL,
  `vote` enum('executor','creator') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_vote` (`dispute_id`,`juror_id`),
  KEY `dispute_id` (`dispute_id`),
  KEY `juror_id` (`juror_id`),
  CONSTRAINT `jury_votes_ibfk_1` FOREIGN KEY (`dispute_id`) REFERENCES `disputes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `jury_votes_ibfk_2` FOREIGN KEY (`juror_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de Mensajes de Tareas
DROP TABLE IF EXISTS `task_messages`;
CREATE TABLE `task_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message_text` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `task_id` (`task_id`),
  KEY `sender_id` (`sender_id`),
  KEY `receiver_id` (`receiver_id`),
  CONSTRAINT `task_messages_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_messages_ibfk_3` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Reactivar la verificación de claves foráneas
SET FOREIGN_KEY_CHECKS=1;
";

// Ejecutar la consulta múltiple
if ($mysqli->multi_query($sql_schema)) {
    // Es necesario limpiar los resultados de cada consulta
    do {
        if ($result = $mysqli->store_result()) {
            $result->free();
        }
    } while ($mysqli->next_result());
    $success_message = "¡Instalación completada con éxito! Todas las tablas han sido creadas.";
} else {
    $error_message = "Error durante la instalación: " . $mysqli->error;
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalación - Círculo Activo</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #F8F8F8; color: #333; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 1rem;}
        .container { background-color: #fff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); padding: 2rem; max-width: 600px; text-align: center; }
        h1 { color: #FF6B00; }
        .message { padding: 1rem; border-radius: 8px; margin-top: 1.5rem; }
        .success { background-color: #d4edda; color: #155724; }
        .error { background-color: #f8d7da; color: #721c24; }
        .warning { background-color: #fff3cd; color: #856404; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Instalación de Círculo Activo</h1>
        <?php if ($success_message): ?>
            <div class="message success">
                <p><?php echo $success_message; ?></p>
            </div>
            <div class="message warning">
                <p>¡MUY IMPORTANTE! Por razones de seguridad, debes eliminar el archivo <code>install.php</code> de tu servidor AHORA MISMO.</p>
            </div>
            <a href="index.php" style="display: inline-block; margin-top: 1.5rem; background-color: #FF6B00; color: white; padding: 0.75rem 1.5rem; border-radius: 8px; text-decoration: none;">Ir a la página principal</a>
        <?php elseif ($error_message): ?>
            <div class="message error">
                <p><?php echo $error_message; ?></p>
                <p>Por favor, revisa tus credenciales en <code>includes/db_connect.php</code> y asegúrate de que la base de datos exista y el usuario tenga permisos.</p>
            </div>
        <?php else: ?>
            <p>Error inesperado. No se pudo determinar el estado de la instalación.</p>
        <?php endif; ?>
    </div>
</body>
</html>

