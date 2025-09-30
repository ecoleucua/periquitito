Círculo Activo - Plataforma de Intercambio de Microtareas
Círculo Activo es una plataforma web completa construida desde cero con PHP vanilla, diseñada para funcionar en entornos de hosting compartido restrictivos. Permite a los usuarios crear una comunidad basada en una economía circular de puntos, donde pueden publicar microtareas para que otros las completen a cambio de una recompensa.

Tecnologías Utilizadas
Backend: PHP 8.1+ (Vanilla, sin frameworks)

Base de Datos: MySQL con extensión MySQLi

Frontend: HTML5, CSS3, JavaScript (Vanilla)

APIs Externas: ImgBB para el alojamiento de imágenes.

Características Principales
Sistema de Puntos de Economía Circular: Los usuarios reciben un bono inicial y utilizan puntos para publicar y completar tareas. Una tarifa del 10% en cada publicación mantiene la economía sostenible.

Gestión de Tareas Completa: Los usuarios pueden publicar tareas con descripciones, enlaces, categorías y un límite de tiempo.

Sistema de Reserva de Tareas: Un usuario puede "reservar" una tarea, ocultándola temporalmente de la lista pública mientras la completa dentro del tiempo límite.

Envío de Pruebas con Imágenes: Los ejecutores pueden enviar pruebas de su trabajo, ya sea como texto, subiendo una imagen, o pegando una captura desde el portapapeles.

Sistema de Reputación Automático: Los usuarios ganan reputación y suben de nivel (Bronce, Plata, Oro) basándose en su fiabilidad y justicia al aprobar o completar tareas.

Sistema de Mensajería Privada: Un chat tipo foro integrado en cada tarea permite la comunicación directa entre el creador y el ejecutor.

Sistema de Disputas basado en Jurado: Si un envío es rechazado, el ejecutor puede apelar. Un jurado de usuarios con Nivel Oro vota para decidir el resultado de forma justa.

Panel de Administración: Una sección segura para administradores donde pueden ver estadísticas, gestionar usuarios (suspender, banear) y moderar tareas (pausar, eliminar).

Notificaciones y Paneles Interactivos: Un sistema de notificaciones en tiempo real y paneles flotantes para el historial de puntos y alertas, mejorando la experiencia del usuario.

Perfiles de Usuario Personalizables: Los usuarios pueden subir su propia foto de perfil.

Instrucciones de Instalación (Paso a Paso)
Sigue estas instrucciones para instalar la plataforma en un servidor con cPanel.

1. Crear la Base de Datos
Inicia sesión en tu cPanel.

Ve a "Bases de datos MySQL" o al "Asistente de bases de datos MySQL".

Crea una nueva base de datos (ej: usuario_circulo).

Crea un nuevo usuario de base de datos y asígnale una contraseña segura.

Añade el usuario a la base de datos y otórgale "TODOS LOS PRIVILEGIOS".

Anota el nombre de la base de datos, el nombre de usuario y la contraseña.

2. Subir los Archivos
Ve al "Administrador de Archivos" en cPanel.

Navega a la carpeta public_html (o el directorio raíz de tu sitio web).

Sube todos los archivos y carpetas del proyecto.

3. Configurar la Conexión
Dentro del "Administrador de Archivos", busca y edita el archivo includes/db_connect.php.

Introduce el nombre de la base de datos, el nombre de usuario y la contraseña que anotaste en el Paso 1. Guarda los cambios.

4. Instalar la Plataforma
Abre tu navegador y ve a http://tudominio.com/install.php.

Deberías ver un mensaje de "Instalación completada con éxito". Si ves un error, revisa los datos de conexión del paso anterior.

5. ¡IMPORTANTE! Proteger tu Sitio
Después de una instalación exitosa, ELIMINA INMEDIATAMENTE el archivo install.php de tu servidor usando el "Administrador de Archivos". Este es un paso de seguridad crucial.

6. Configurar tu Cuenta de Administrador
Para convertir a tu primer usuario en administrador, necesitas acceder a tu base de datos (usando phpMyAdmin en cPanel) y ejecutar la siguiente consulta SQL:

UPDATE users SET is_admin = 1 WHERE id = 1;

(Reemplaza id = 1 por el ID de tu usuario si es diferente).

¡Listo! Ya puedes acceder a http://tudominio.com y empezar a usar la plataforma.

Estructura de la Base de Datos
Este es el esquema SQL completo que install.php utilizará para crear la base de datos.

SET FOREIGN_KEY_CHECKS=0;

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

CREATE TABLE `disputes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `submission_id` int(11) NOT NULL,
  `status` enum('voting','resolved_executor','resolved_creator') NOT NULL DEFAULT 'voting',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `submission_id` (`submission_id`),
  CONSTRAINT `disputes_ibfk_1` FOREIGN KEY (`submission_id`) REFERENCES `submissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

SET FOREIGN_KEY_CHECKS=1;
