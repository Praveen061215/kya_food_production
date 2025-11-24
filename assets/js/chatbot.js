'use strict';

(function() {
    const apiBase = (window.location.pathname.indexOf('/modules/') !== -1) ? '../../api/chatbot.php' : 'api/chatbot.php';

    document.addEventListener('DOMContentLoaded', function() {
        initChatbotWidget();
    });

    function initChatbotWidget() {
        const launcher = document.getElementById('kyaChatbotLauncher');
        const panel = document.getElementById('kyaChatbotPanel');
        const closeBtn = document.getElementById('kyaChatbotClose');
        const form = document.getElementById('kyaChatbotForm');
        const input = document.getElementById('kyaChatbotInput');
        const messages = document.getElementById('kyaChatbotMessages');

        if (!launcher || !panel || !form || !input || !messages) {
            return;
        }

        launcher.addEventListener('click', function() {
            const isHidden = panel.classList.contains('d-none');
            if (isHidden) {
                panel.classList.remove('d-none');
                launcher.classList.add('active');
                input.focus();
                if (!panel.dataset.initialized) {
                    addBotMessage('Hi, I am your KYA Assistant. Ask me anything about inventory, expiry, processing, reports, or navigation.');
                    panel.dataset.initialized = '1';
                }
            } else {
                panel.classList.add('d-none');
                launcher.classList.remove('active');
            }
        });

        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                panel.classList.add('d-none');
                launcher.classList.remove('active');
            });
        }

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const text = input.value.trim();
            if (!text) return;

            addUserMessage(text);
            input.value = '';
            sendMessage(text);
        });

        messages.addEventListener('click', function(e) {
            const target = e.target.closest('[data-chat-action]');
            if (!target) return;
            const type = target.dataset.chatAction;
            const value = target.dataset.chatValue;
            const url = target.dataset.chatUrl;

            if (type === 'suggest' && value) {
                addUserMessage(value);
                sendMessage(value);
            } else if (type === 'open_url' && url) {
                window.location.href = url;
            }
        });
    }

    function addUserMessage(text) {
        appendMessage({
            from: 'user',
            html: escapeHtml(text)
        });
    }

    function addBotMessage(text, actions) {
        appendMessage({
            from: 'bot',
            html: text,
            actions: actions || []
        });
    }

    function appendMessage(options) {
        const messages = document.getElementById('kyaChatbotMessages');
        if (!messages) return;

        const wrapper = document.createElement('div');
        wrapper.className = 'kya-chat-message ' + (options.from === 'user' ? 'kya-chat-message-user' : 'kya-chat-message-bot');

        const bubble = document.createElement('div');
        bubble.className = 'kya-chat-bubble';
        bubble.innerHTML = options.html;
        wrapper.appendChild(bubble);

        if (options.from === 'bot' && options.actions && options.actions.length) {
            const actionsRow = document.createElement('div');
            actionsRow.className = 'kya-chat-actions mt-2';

            options.actions.forEach(function(action) {
                if (action.type === 'open_url') {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'btn btn-sm btn-outline-light me-1 mb-1';
                    btn.textContent = action.label || 'Open';
                    btn.dataset.chatAction = 'open_url';
                    btn.dataset.chatUrl = action.url;
                    actionsRow.appendChild(btn);
                } else if (action.type === 'suggest') {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'btn btn-sm btn-outline-secondary me-1 mb-1';
                    btn.textContent = action.label || action.value;
                    btn.dataset.chatAction = 'suggest';
                    btn.dataset.chatValue = action.value;
                    actionsRow.appendChild(btn);
                }
            });

            wrapper.appendChild(actionsRow);
        }

        messages.appendChild(wrapper);
        messages.scrollTop = messages.scrollHeight;
    }

    function sendMessage(text) {
        setTyping(true);

        fetch(apiBase, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ message: text })
        })
            .then(function(response) {
                setTyping(false);
                if (!response.ok) {
                    if (response.status === 401) {
                        addBotMessage('Your session has expired. Please log in again.');
                    } else {
                        addBotMessage('Sorry, something went wrong. Please try again.');
                    }
                    return null;
                }
                return response.json();
            })
            .then(function(data) {
                if (!data) return;
                var reply = data.reply || 'I am here to help with navigation, inventory, expiry, processing, quality, and reports.';
                addBotMessage(reply, data.actions || []);
            })
            .catch(function(error) {
                setTyping(false);
                console.error('Chatbot error:', error);
                addBotMessage('Sorry, I could not reach the assistant just now.');
            });
    }

    function setTyping(isTyping) {
        const indicator = document.getElementById('kyaChatbotTyping');
        if (!indicator) return;
        indicator.classList.toggle('d-none', !isTyping);
    }

    function escapeHtml(str) {
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
})();
