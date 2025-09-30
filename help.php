<?php
// Círculo Activo
// Archivo: help.php

session_start();
require_once 'includes/header.php';
?>

<div class="card" style="max-width: 800px; margin: 2rem auto;">
    <h1><i class="fa-solid fa-circle-question"></i> Centro de Ayuda</h1>
    <p>Encuentra respuestas a las preguntas más comunes sobre Círculo Activo.</p>

    <div class="faq-container">
        <details class="faq-item">
            <summary>¿Qué son los Puntos y cómo los consigo?</summary>
            <p>Los Puntos son la moneda interna de Círculo Activo. No tienen valor monetario. Cada nuevo usuario recibe un bono inicial. Puedes ganar más puntos completando las tareas que otros usuarios publican. A su vez, usas tus puntos para publicar tus propias tareas. Es una economía circular diseñada para fomentar la colaboración.</p>
        </details>
        
        <details class="faq-item">
            <summary>¿Los Puntos se pueden cambiar por dinero real?</summary>
            <p><strong>No.</strong> Los puntos son exclusivamente para su uso dentro de la plataforma y no pueden ser comprados, vendidos o canjeados por dinero real. Su único propósito es facilitar el intercambio de favores y microtareas dentro de la comunidad.</p>
        </details>

        <details class="faq-item">
            <summary>¿Cómo funciona el sistema de Reputación?</summary>
            <p>Tu reputación se calcula automáticamente en base a tus acciones. Hay tres niveles:</p>
            <ul>
                <li><strong>🥉 Bronce:</strong> El nivel inicial para todos los usuarios.</li>
                <li><strong>🥈 Plata:</strong> Se alcanza al completar un número moderado de tareas con una alta tasa de aprobación.</li>
                <li><strong>🥇 Oro:</strong> El nivel más alto, reservado para los usuarios más fiables y justos de la comunidad. Los miembros de Nivel Oro pueden ser parte del jurado en las disputas.</li>
            </ul>
        </details>

        <details class="faq-item">
            <summary>¿Qué hago si mi tarea fue rechazada injustamente?</summary>
            <p>Si crees que un creador de tareas ha rechazado tu trabajo de forma injusta, puedes apelar la decisión. Ve a "Mis Tareas" > "Historial de Tareas Completadas" y haz clic en el botón "Apelar". Tu caso será presentado de forma anónima a un jurado de miembros de Nivel Oro, quienes votarán para decidir el resultado final.</p>
        </details>

        <details class="faq-item">
            <summary>¿Qué tipo de tareas están prohibidas?</summary>
            <p>Está estrictamente prohibido publicar tareas que involucren:</p>
            <ul>
                <li>Contenido ilegal, dañino o para adultos.</li>
                <li>Spam o publicidad engañosa.</li>
                <li>Solicitud de información personal sensible (contraseñas, datos bancarios, etc.).</li>
                <li>Acciones que violen los términos de servicio de otras plataformas.</li>
                <li>Acoso o ataques a otros individuos.</li>
            </ul>
            <p>Usa el botón "Reportar Tarea" si encuentras contenido que rompa estas reglas.</p>
        </details>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>
