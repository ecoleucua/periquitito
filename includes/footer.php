<?php
// Círculo Activo - Pie de página de la web
// includes/footer.php

// Si la conexión a la base de datos está abierta, la cerramos aquí.
// Esto asegura que la conexión se cierre al final de la carga de cada página.
if (isset($mysqli)) {
    $mysqli->close();
}

$current_page_footer = basename($_SERVER['PHP_SELF']);
?>

    </main> <!-- Cierre del .main-container o similar abierto en header/página -->

    <nav class="bottom-nav">
        <a href="index.php" class="nav-item <?php echo ($current_page_footer == 'index.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-house"></i> Inicio
        </a>
        <a href="publish_task.php" class="nav-item <?php echo ($current_page_footer == 'publish_task.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-plus-circle"></i> Publicar
        </a>
        <a href="my_tasks.php" class="nav-item <?php echo ($current_page_footer == 'my_tasks.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-list-check"></i> Mis Tareas
        </a>
        <a href="profile.php" class="nav-item <?php echo ($current_page_footer == 'profile.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-user"></i> Perfil
        </a>
        <!-- ID para el hook de JS -->
        <button id="more-menu-btn" class="nav-item">
            <i class="fa-solid fa-ellipsis-h"></i> Más
        </button>
    </nav>

    <!-- Menú "Más" (inicialmente oculto) -->
    <div id="more-menu-panel" class="floating-panel bottom-panel">
        <a href="dispute_center.php" class="panel-item">
            <i class="fa-solid fa-gavel"></i> Centro de Disputas
        </a>
        <!-- CAMBIO: Añadido enlace a Configuración -->
        <a href="settings.php" class="panel-item">
            <i class="fa-solid fa-cog"></i> Configuración
        </a>
        <a href="help.php" class="panel-item">
            <i class="fa-solid fa-question-circle"></i> Ayuda
        </a>
         <a href="#" class="panel-item disabled">
            <i class="fa-solid fa-envelope"></i> Contáctanos (Próximamente)
        </a>
    </div>

    <script src="js/main.js"></script>
</body>
</html>

        <!-- Modal de Mensajería (Reutilizable y Centralizado) -->
        <div id="chat-modal" class="modal-overlay">
            <div class="modal-content chat-modal-content">
                <div class="modal-header">
                    <h2 id="chat-modal-title">Conversación</h2>
                    <button type="button" class="modal-close">&times;</button>
                </div>
                <div class="chat-messages" id="chat-messages-container">
                    <!-- Los mensajes se cargarán aquí dinámicamente -->
                </div>
                <form id="chat-form" class="chat-form">
                    <input type="text" id="chat-message-input" class="form-control" placeholder="Escribe un mensaje..." autocomplete="off" required>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i></button>
                </form>
            </div>
        </div>

    </body>
    </html>

