// Círculo Activo - Archivo Principal de JavaScript
// js/main.js

document.addEventListener('DOMContentLoaded', () => {

    // --- 1. Lógica Universal de Modales y Paneles Flotantes ---
    const reserveModal = document.getElementById('reserve-modal');
    const reserveForm = document.getElementById('reserve-form');
    let currentTaskIdToReserve = null;
    
    const panels = {
        notifications: { btn: document.getElementById('notifications-btn'), panel: document.getElementById('notifications-panel'), url: 'ajax/get_notifications.php' },
        points: { btn: document.getElementById('points-history-btn'), panel: document.getElementById('points-history-panel'), url: 'ajax/get_transactions.php' },
        more: { btn: document.getElementById('more-menu-btn'), panel: document.getElementById('more-menu-panel') }
    };

    const closeAllPanels = (except) => {
        for (const key in panels) {
            if (panels[key].panel && key !== except) {
                panels[key].panel.classList.remove('active');
            }
        }
    };

    const togglePanel = async (panelKey) => {
        const { btn, panel, url } = panels[panelKey];
        if (!btn || !panel) return;

        const isActive = panel.classList.contains('active');
        closeAllPanels(panelKey);

        if (!isActive) {
            panel.classList.add('active');
            if (url) {
                panel.innerHTML = '<div class="panel-body"><p style="text-align:center; padding: 1rem;">Cargando...</p></div>';
                try {
                    const response = await fetch(url);
                    const result = await response.json();
                    if (result.status === 'success') {
                        renderPanelContent(panelKey, result);
                    } else {
                        panel.innerHTML = `<div class="panel-body"><p style="text-align:center; color:red;">${result.message}</p></div>`;
                    }
                } catch (error) {
                    panel.innerHTML = '<div class="panel-body"><p style="text-align:center; color:red;">Error de red.</p></div>';
                }
            }
        }
    };

    if(panels.notifications.btn) panels.notifications.btn.addEventListener('click', (e) => { e.preventDefault(); togglePanel('notifications'); });
    if(panels.points.btn) panels.points.btn.addEventListener('click', (e) => { e.preventDefault(); togglePanel('points'); });
    if(panels.more.btn) panels.more.btn.addEventListener('click', (e) => { e.preventDefault(); togglePanel('more'); });

    document.addEventListener('click', (e) => {
        const isPanelClick = Object.values(panels).some(p => p.panel && p.panel.contains(e.target));
        const isBtnClick = Object.values(panels).some(p => p.btn && p.btn.contains(e.target));
        if (!isPanelClick && !isBtnClick) {
            closeAllPanels(null);
        }
    });

    function renderPanelContent(panelKey, data) {
        const panel = panels[panelKey].panel;
        let content = '';

        if (panelKey === 'notifications') {
            content += '<div class="panel-header">Notificaciones</div><div class="panel-body">';
            if (data.notifications && data.notifications.length > 0) {
                data.notifications.forEach(n => {
                    content += `<a href="${n.link || '#'}" class="panel-item notification-item-panel ${!n.is_read ? 'unread' : ''}">
                        <div class="msg"><p>${n.message}</p><span class="time">${new Date(n.created_at).toLocaleString()}</span></div>
                    </a>`;
                });
            } else {
                content += '<p style="text-align:center; padding:1rem;">No hay notificaciones.</p>';
            }
            content += '</div><div class="panel-footer"><a href="notifications.php">Ver todas</a></div>';
        } else if (panelKey === 'points') {
            content += '<div class="panel-header">Historial de Puntos</div><div class="panel-body">';
            if (data.transactions && data.transactions.length > 0) {
                data.transactions.forEach(t => {
                    const isExpense = t.amount < 0;
                    const amountClass = isExpense ? 'expense' : 'income';
                    let desc = t.type.replace(/_/g, ' ');
                    if(t.type === 'task_completion' && isExpense) desc = 'Pago por tarea';
                    else if(t.type === 'task_completion') desc = 'Recompensa de tarea';

                    content += `<div class="panel-item transaction-item-panel">
                        <span class="desc">${desc}</span><span class="amount ${amountClass}">${t.amount > 0 ? '+' : ''}${t.amount}</span>
                    </div>`;
                });
            } else {
                content += '<p style="text-align:center; padding:1rem;">No hay transacciones.</p>';
            }
            content += '</div><div class="panel-footer"><a href="transactions.php">Ver historial completo</a></div>';
        }
        panel.innerHTML = content;
    }

    document.body.addEventListener('click', (e) => {
        if (e.target.classList.contains('reserve-task-btn')) {
            e.preventDefault();
            currentTaskIdToReserve = e.target.dataset.taskId;
            if (reserveModal) reserveModal.classList.add('active');
        }
    });

    if (reserveForm) {
        reserveForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!currentTaskIdToReserve) return;
            const submitButton = reserveForm.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.textContent = 'Reservando...';
            const formData = new FormData();
            formData.append('task_id', currentTaskIdToReserve);
            try {
                const response = await fetch('ajax/reserve_task.php', { method: 'POST', body: formData });
                if (!response.ok) throw new Error(`Error del servidor: ${response.status}`);
                const result = await response.json();
                if (result.status === 'success') {
                    window.location.href = result.redirect_url;
                } else {
                    alert(result.message);
                    if (reserveModal) reserveModal.classList.remove('active');
                }
            } catch (error) {
                console.error("Error detallado al reservar:", error);
                alert('Ocurrió un error al procesar tu solicitud. Por favor, inténtalo de nuevo.');
                if (reserveModal) reserveModal.classList.remove('active');
            } finally {
                submitButton.disabled = false;
                submitButton.textContent = 'Sí, comenzar ahora';
                currentTaskIdToReserve = null;
            }
        });
    }

    document.body.addEventListener('click', (e) => {
        if (e.target.classList.contains('modal-overlay') || e.target.classList.contains('modal-close')) {
            e.target.closest('.modal-overlay').classList.remove('active');
        }
    });


    // --- 2. Convertir spans de usuario en enlaces de perfil ---
    document.body.addEventListener('click', (e) => {
        if (e.target.classList.contains('user-link') && e.target.dataset.userId) {
            window.location.href = `profile.php?id=${e.target.dataset.userId}`;
        }
    });
    
    
    // --- 3. Lógica para el formulario de Título Dinámico en publish_task.php ---
    const titleSelect = document.getElementById('title-select');
    const titleInput = document.getElementById('title-input');
    if (titleSelect && titleInput) {
        titleSelect.addEventListener('change', () => {
            if (titleSelect.value === 'Otro') {
                titleInput.style.display = 'block';
                titleInput.setAttribute('required', 'required');
            } else {
                titleInput.style.display = 'none';
                titleInput.removeAttribute('required');
            }
        });
    }

    // --- 4. Lógica para el formulario de subida de Pruebas ---
    const setupImageUploader = (form) => {
        if (!form) return;
        const imageUploader = form.querySelector('.image-uploader');
        const imagePreviewContainer = form.querySelector('.image-preview-container');
        const imagePreview = form.querySelector('.image-preview');
        const imageUrlInput = form.querySelector('input[name$="_image_url"]');
        let fileToUpload = null;
        const apiKey = 'b4bb4f3b0f0a6aaa0fb4e86d12a43c67';

        const handleFileSelect = (file) => {
            if (!file) return;
            const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/bmp'];
            if (!validTypes.includes(file.type)) {
                alert('Tipo de archivo no válido.'); return;
            }
            if (file.size > 30 * 1024 * 1024) {
                alert('La imagen es demasiado grande (Máx 30MB).'); return;
            }
            
            fileToUpload = file;
            if(imagePreview && imagePreviewContainer) {
                imagePreview.src = URL.createObjectURL(file);
                imagePreviewContainer.style.display = 'block';
            }
        };

        const uploadFile = async () => {
            if (!fileToUpload) return true;
            
            const formData = new FormData();
            formData.append('image', fileToUpload);
            try {
                const response = await fetch(`https://api.imgbb.com/1/upload?key=${apiKey}`, { method: 'POST', body: formData });
                const result = await response.json();
                if (result.success) {
                    if (imageUrlInput) imageUrlInput.value = result.data.url;
                    return true;
                } else { 
                    throw new Error(result.error.message); 
                }
            } catch (error) {
                alert(`Error al subir la imagen: ${error.message}`);
                return false;
            }
        };

        if(imageUploader) {
            imageUploader.addEventListener('click', () => {
                const fileInput = document.createElement('input');
                fileInput.type = 'file'; fileInput.accept = 'image/*';
                fileInput.onchange = e => handleFileSelect(e.target.files[0]);
                fileInput.click();
            });
             ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                imageUploader.addEventListener(eventName, e => { e.preventDefault(); e.stopPropagation(); }, false);
            });
            imageUploader.addEventListener('drop', e => handleFileSelect(e.dataTransfer.files[0]));
            
            form.addEventListener('paste', e => {
                 const items = (e.clipboardData || e.originalEvent.clipboardData).items;
                 for (const item of items) {
                     if (item.type.indexOf('image') !== -1) handleFileSelect(item.getAsFile());
                 }
            });
        }
        
        return uploadFile;
    };
    
    const proofForm = document.getElementById('proof-form');
    const rejectForm = document.getElementById('reject-form');
    const uploadProofFile = setupImageUploader(proofForm);
    const uploadRejectFile = setupImageUploader(rejectForm);

    if (proofForm) {
        proofForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formMessage = document.getElementById('form-message');
            const submitButton = proofForm.querySelector('button[type="submit"]');
            const textInput = proofForm.querySelector('textarea[name="proof_data_text"]');
            if (textInput.value.trim() === '' && !proofForm.querySelector('.image-preview').src.startsWith('blob:')) {
                formMessage.innerHTML = `<div class='message error'>Debes escribir una prueba o subir una imagen.</div>`;
                return;
            }
            submitButton.disabled = true;
            submitButton.textContent = 'Procesando...';
            const uploadSuccess = await uploadProofFile();
            if (!uploadSuccess) {
                submitButton.disabled = false;
                submitButton.textContent = 'Enviar Prueba para Revisión';
                return;
            }
            try {
                const response = await fetch('ajax/submit_proof.php', { method: 'POST', body: new FormData(proofForm) });
                if (!response.ok) throw new Error(`Error del servidor: ${await response.text()}`);
                const result = await response.json();
                if (result.status === 'success') {
                    formMessage.innerHTML = `<div class='message success'>${result.message}</div>`;
                    setTimeout(() => window.location.href = 'my_tasks.php?tab=completed', 1500);
                } else {
                    formMessage.innerHTML = `<div class='message error'>${result.message}</div>`;
                    submitButton.disabled = false;
                }
            } catch (error) {
                console.error("Error al enviar prueba:", error);
                formMessage.innerHTML = `<div class='message error'>Ocurrió un error inesperado. Revisa la consola para más detalles.</div>`;
                submitButton.disabled = false;
            } finally {
                if (!submitButton.disabled) submitButton.textContent = 'Enviar Prueba para Revisión';
            }
        });
    }

    // --- 5. Lógica para la subida de Avatar en settings.php ---
    const avatarForm = document.getElementById('avatar-form');
    if (avatarForm) {
        const avatarInput = document.getElementById('avatar-upload');
        const avatarPreview = document.getElementById('avatar-preview');
        avatarInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                avatarPreview.src = URL.createObjectURL(file);
                avatarForm.submit();
            }
        });
    }

    // --- 6. Lógica para botones de acción y modales ---
    const rejectModal = document.getElementById('reject-modal');
    let currentSubmissionIdToReject = null;

    document.body.addEventListener('click', async (e) => {
        const target = e.target;
        if (target.classList.contains('reject-btn')) {
            e.preventDefault();
            currentSubmissionIdToReject = target.dataset.submissionId;
            if (rejectModal) rejectModal.classList.add('active');
        }
        let action = '';
        if (target.classList.contains('review-btn') && target.classList.contains('approve')) action = 'approve';
        else if (target.classList.contains('appeal-btn')) action = 'appeal';
        else if (target.classList.contains('vote-btn')) action = 'vote';
        else if (target.classList.contains('cancel-task-btn')) action = 'cancel';
        else if (target.id === 'report-task-btn') action = 'report';
        if (action) {
            e.preventDefault();
            let url = '', body = new FormData(), confirmMsg = '';
            switch (action) {
                case 'approve':
                    url = 'ajax/review_submission.php';
                    body.append('submission_id', target.dataset.submissionId);
                    body.append('action', 'approve');
                    break;
                case 'appeal':
                    url = 'ajax/appeal_submission.php';
                    body.append('submission_id', target.dataset.submissionId);
                    break;
                case 'vote':
                    url = 'ajax/cast_vote.php';
                    body.append('dispute_id', target.dataset.disputeId);
                    body.append('vote', target.dataset.vote);
                    break;
                case 'cancel':
                    confirmMsg = '¿Estás seguro de que quieres cancelar esta tarea? Los puntos serán devueltos a tu saldo.';
                    if (!confirm(confirmMsg)) return;
                    url = 'ajax/cancel_task.php';
                    body.append('task_id', target.dataset.taskId);
                    break;
                case 'report':
                    confirmMsg = '¿Estás seguro de que quieres reportar esta tarea como inapropiada?';
                    if (!confirm(confirmMsg)) return;
                    url = 'ajax/report_task.php';
                    body.append('task_id', target.dataset.taskId);
                    break;
            }
            try {
                const response = await fetch(url, { method: 'POST', body: body });
                const result = await response.json();
                alert(result.message);
                if (result.status === 'success') {
                    window.location.reload();
                }
            } catch (error) {
                alert('Error de red. Por favor, inténtalo de nuevo.');
            }
        }
    });

    if (rejectForm) {
        rejectForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!currentSubmissionIdToReject) return;
            const submitButton = rejectForm.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            const uploadSuccess = await uploadRejectFile();
            if (!uploadSuccess) {
                submitButton.disabled = false;
                return;
            }
            const formData = new FormData(rejectForm);
            formData.append('submission_id', currentSubmissionIdToReject);
            formData.append('action', 'reject');
            try {
                const response = await fetch('ajax/review_submission.php', { method: 'POST', body: formData });
                const result = await response.json();
                alert(result.message);
                if (result.status === 'success') {
                    window.location.reload();
                }
            } catch (error) {
                alert('Error de red. Por favor, inténtalo de nuevo.');
            } finally {
                submitButton.disabled = false;
                if (rejectModal) rejectModal.classList.remove('active');
                currentSubmissionIdToReject = null;
            }
        });
    }

    // --- 7. Lógica del Menú de Administración en Móviles ---
    const menuToggle = document.getElementById('menu-toggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.querySelector('.sidebar-overlay');

    if (menuToggle && sidebar && sidebarOverlay) {
        menuToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            sidebar.classList.toggle('open');
            sidebarOverlay.classList.toggle('active');
        });

        sidebarOverlay.addEventListener('click', () => {
            sidebar.classList.remove('open');
            sidebarOverlay.classList.remove('active');
        });
    }

    // --- 8. Temporizador de cuenta regresiva en task_view.php ---
    const timerElement = document.getElementById('timer');
    if (timerElement) {
        let timeLeft = parseInt(timerElement.dataset.timeLeft, 10);
        const timerInterval = setInterval(() => {
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                timerElement.textContent = "Tiempo agotado";
                const proofFormSubmitBtn = document.querySelector('#proof-form button[type="submit"]');
                if (proofFormSubmitBtn) {
                    proofFormSubmitBtn.disabled = true;
                    proofFormSubmitBtn.textContent = 'El tiempo ha expirado';
                }
                return;
            }
            timeLeft--;
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            timerElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        }, 1000);
    }

    // --- 9. Visor de imágenes ---
    const imageViewerModal = document.getElementById('image-viewer-modal');
    if (imageViewerModal) {
        const modalImage = imageViewerModal.querySelector('img');
        document.body.addEventListener('click', (e) => {
            if (e.target.classList.contains('proof-image')) {
                modalImage.src = e.target.src;
                imageViewerModal.classList.add('active');
            }
        });
    }

    // --- 10. Lógica del Sistema de Mensajería ---
    const chatModal = document.getElementById('chat-modal');
    const chatMessagesContainer = document.getElementById('chat-messages-container');
    const chatForm = document.getElementById('chat-form');
    const chatMessageInput = document.getElementById('chat-message-input');
    const chatModalTitle = document.getElementById('chat-modal-title');
    let currentChatTaskId = null;

    const openChat = async (taskId) => {
        if (!chatModal || !taskId) return;
        currentChatTaskId = taskId;
        
        chatMessagesContainer.innerHTML = '<p style="text-align:center;">Cargando mensajes...</p>';
        chatModal.classList.add('active');

        const formData = new FormData();
        formData.append('task_id', taskId);

        try {
            const response = await fetch('ajax/get_task_messages.php', { method: 'POST', body: formData });
            const result = await response.json();

            if (result.status === 'success') {
                chatModalTitle.textContent = `Conversación Tarea #${taskId}`;
                renderMessages(result.messages, result.current_user_id);
            } else {
                chatMessagesContainer.innerHTML = `<p style="text-align:center; color:red;">${result.message}</p>`;
            }
        } catch (error) {
            chatMessagesContainer.innerHTML = '<p style="text-align:center; color:red;">Error de red al cargar mensajes.</p>';
        }
    };
    
    const renderMessages = (messages, currentUserId) => {
        chatMessagesContainer.innerHTML = '';
        if (messages.length === 0) {
            chatMessagesContainer.innerHTML = '<p style="text-align:center; color:#888;">Aún no hay mensajes. ¡Inicia la conversación!</p>';
            return;
        }
        messages.forEach(msg => {
            const isSent = msg.sender_id == currentUserId;
            const avatarSrc = msg.avatar_url || 'icon/perfil.png';
            const bubble = document.createElement('div');
            bubble.classList.add('message-bubble', isSent ? 'sent' : 'received');
            
            bubble.innerHTML = `
                <img src="${avatarSrc}" alt="Avatar" class="chat-avatar">
                <div class="message-content-wrapper">
                    <div class="message-sender-name">${isSent ? 'Tú' : htmlspecialchars(msg.username)}</div>
                    <div class="message-content">${htmlspecialchars(msg.message_text)}</div>
                </div>
            `;
            chatMessagesContainer.appendChild(bubble);
        });
        chatMessagesContainer.scrollTop = chatMessagesContainer.scrollHeight;
    };

    function htmlspecialchars(str) {
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return str ? str.replace(/[&<>"']/g, m => map[m]) : '';
    }

    document.body.addEventListener('click', (e) => {
        const chatButton = e.target.closest('.open-chat-btn');
        if (chatButton) {
            e.preventDefault();
            e.stopPropagation();
            const taskId = chatButton.dataset.taskId;
            openChat(taskId);
        }
    });

    if (chatForm) {
        chatForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const messageText = chatMessageInput.value.trim();
            if (!messageText || !currentChatTaskId) return;

            const submitButton = chatForm.querySelector('button[type="submit"]');
            submitButton.disabled = true;

            const formData = new FormData();
            formData.append('task_id', currentChatTaskId);
            formData.append('message_text', messageText);

            try {
                const response = await fetch('ajax/send_task_message.php', { method: 'POST', body: formData });
                const result = await response.json();

                if (result.status === 'success') {
                    openChat(currentChatTaskId);
                    chatMessageInput.value = '';
                } else {
                    alert(result.message);
                }
            } catch (error) {
                alert('Error de red al enviar el mensaje.');
            } finally {
                submitButton.disabled = false;
                chatMessageInput.focus();
            }
        });
    }
    
    // --- 11. Nueva Lógica para Abrir Chat desde URL ---
    const checkUrlForChat = () => {
        const urlParams = new URLSearchParams(window.location.search);
        const taskIdToOpen = urlParams.get('open_chat_for_task');
        if (taskIdToOpen) {
            openChat(taskIdToOpen);
            const newUrl = window.location.pathname + window.location.search.replace(/&?open_chat_for_task=\d+/, '');
            history.replaceState({}, document.title, newUrl);
        }
    };
    checkUrlForChat();

    // --- Actualización automática de contadores de chat en botones ---
    function updateChatBadges() {
        document.querySelectorAll('.open-chat-btn').forEach(function(btn) {
            var taskId = btn.dataset.taskId;
            fetch('ajax/get_task_messages.php', {
                method: 'POST',
                body: new URLSearchParams({ task_id: taskId })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    var unread = data.messages.filter(m => m.receiver_id == data.current_user_id && m.is_read == 0).length;
                    var badge = btn.querySelector('.chat-badge');
                    if (unread > 0) {
                        if (!badge) {
                            var span = document.createElement('span');
                            span.className = 'chat-badge';
                            span.textContent = unread;
                            btn.appendChild(span);
                        } else {
                            badge.textContent = unread;
                        }
                    } else if (badge) {
                        badge.remove();
                    }
                }
            });
        });
    }
    setInterval(updateChatBadges, 30000); // Actualiza cada 30 segundos
    window.addEventListener('DOMContentLoaded', updateChatBadges);
});

