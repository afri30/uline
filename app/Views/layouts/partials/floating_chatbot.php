<!-- FLOATING AI CHATBOT GLOBAL WIDGET (TIPE 1) -->
<div id="ulineChatbotContainer" style="position: fixed !important; bottom: 24px !important; right: 24px !important; z-index: 99999 !important; font-family: inherit; pointer-events: auto !important;">
    <!-- 1. FLOATING ACTION BUTTON (TRIGGER) - HANYA IKON -->
    <button id="ulineChatTrigger" type="button" onclick="toggleGlobalChatbot(event)" class="btn btn-primary rounded-circle shadow-lg d-flex align-items-center justify-content-center position-relative border-2 border-white" style="width: 56px; height: 56px; min-width: 56px; min-height: 56px; padding: 0 !important; cursor: pointer !important; z-index: 99999 !important; background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%) !important; box-shadow: 0 10px 25px rgba(13, 110, 253, 0.4) !important;" aria-label="U-Line AI Assistant" title="U-Line AI Assistant">
        <div class="position-relative d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
            <i class="bi bi-robot text-white" style="font-size: 1.55rem;"></i>
            <!-- Green Online Dot -->
            <span class="position-absolute bottom-0 start-0 p-1 bg-success border border-white rounded-circle" style="transform: translate(-3px, 3px);" title="Online">
                <span class="visually-hidden">Online</span>
            </span>
        </div>
    </button>

    <!-- 2. COLLAPSIBLE CHAT WINDOW -->
    <div id="ulineChatCard" class="card border-0 shadow-lg rounded-4 overflow-hidden" style="display: none; width: 380px; max-width: calc(100vw - 32px); height: 530px; max-height: calc(100vh - 100px); flex-direction: column; box-shadow: 0 20px 40px rgba(0,0,0,0.2) !important;">
        <!-- HEADER -->
        <div class="card-header bg-primary text-white p-3 border-0 d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%) !important;">
            <div class="d-flex align-items-center gap-2">
                <div class="avatar-circle bg-white text-primary fw-bold" style="width: 38px; height: 38px; font-size: 18px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 6px rgba(0,0,0,0.15);">
                    <i class="bi bi-robot"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-0 text-white" style="font-size: 0.95rem;">U-Line AI Assistant</h6>
                    <div class="d-flex align-items-center gap-1 mt-1">
                        <span class="badge bg-success-subtle text-success extra-small py-0 px-1" style="font-size: 0.68rem;">● Online</span>
                        <span class="badge bg-white text-primary extra-small py-0 px-1 font-monospace" style="font-size: 0.68rem;"><?= htmlspecialchars((string)(role() ?? 'User')) ?></span>
                    </div>
                </div>
            </div>
            <div class="d-flex gap-1">
                <button type="button" class="btn btn-sm btn-link text-white text-decoration-none p-1" onclick="clearGlobalChatHistory(event)" title="Bersihkan Percakapan">
                    <i class="bi bi-trash3"></i>
                </button>
                <button type="button" class="btn btn-sm btn-link text-white text-decoration-none p-1" onclick="toggleGlobalChatbot(event)" title="Tutup">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>

        <!-- MESSAGE STREAM BODY -->
        <div class="card-body p-3 flex-grow-1 overflow-y-auto bg-light" id="ulineChatMessages" style="font-size: 0.88rem; scroll-behavior: smooth;">
            <!-- WELCOME CARD -->
            <div class="bg-white p-3 rounded-3 shadow-sm border mb-3">
                <div class="d-flex align-items-center gap-2 text-primary fw-semibold mb-1">
                    <i class="bi bi-stars"></i>
                    <span>Halo, <?= htmlspecialchars(auth()['name'] ?? 'Pengguna') ?>!</span>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-auto" style="font-size: 0.72rem;">AI Assistant</span>
                </div>
                <p class="text-muted small mb-2">
                    Saya adalah <strong>U-Line AI Assistant</strong> yang terhubung langsung dengan <strong>database realtime website U-Line</strong>. Anda dapat bertanya mengenai data 15 pengguna, 4 guru, 10 siswa, 5 mata pelajaran, kelas, jadwal ujian, hingga panduan fitur aplikasi.
                </p>
                <div class="extra-small text-secondary fw-semibold mb-1">Pertanyaan Cepat Database:</div>
                <div class="d-flex flex-wrap gap-1" id="chatQuickPrompts">
                    <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2 extra-small rounded-pill" onclick="sendQuickPrompt('Sebutkan daftar 15 pengguna di database web ini!')">👥 15 Pengguna</button>
                    <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2 extra-small rounded-pill" onclick="sendQuickPrompt('Sebutkan 5 mata pelajaran yang ada beserta guru pengampunya!')">📚 5 Mata Pelajaran</button>
                    <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2 extra-small rounded-pill" onclick="sendQuickPrompt('Siapa saja 4 guru yang terdaftar dan apa mata pelajarannya?')">👨‍🏫 4 Guru</button>
                    <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2 extra-small rounded-pill" onclick="sendQuickPrompt('Ujian apa saja yang terdaftar di database saat ini?')">📝 Jadwal Ujian</button>
                </div>
            </div>
        </div>

        <!-- TYPING INDICATOR -->
        <div id="ulineChatTyping" class="px-3 py-2 bg-light d-none align-items-center gap-2 text-muted small border-top">
            <div class="spinner-grow spinner-grow-sm text-primary" role="status" style="width: 0.6rem; height: 0.6rem;"></div>
            <span>AI Assistant sedang berpikir...</span>
        </div>

        <!-- FOOTER INPUT -->
        <div class="card-footer bg-white p-2 border-top">
            <form id="ulineChatForm" onsubmit="handleGlobalChatSubmit(event)" class="d-flex gap-2">
                <input type="text" id="ulineChatInput" class="form-control form-control-sm rounded-pill px-3" placeholder="Tanyakan data database atau fitur web..." autocomplete="off" maxlength="800">
                <button type="submit" id="ulineChatSendBtn" class="btn btn-primary btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; flex-shrink: 0;" aria-label="Kirim">
                    <i class="bi bi-send-fill"></i>
                </button>
            </form>
            <div class="text-center mt-1">
                <span class="text-muted extra-small" style="font-size: 0.68rem;"><i class="bi bi-shield-lock me-1"></i>Dilindungi Guardrails Keamanan AI • Terhubung ke Database U-Line</span>
            </div>
        </div>
    </div>
</div>

<style>
#ulineChatTrigger {
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) !important;
}
#ulineChatTrigger:hover {
    transform: translateY(-4px) scale(1.08) !important;
    box-shadow: 0 14px 28px rgba(13, 110, 253, 0.5) !important;
}
.chat-bubble-user {
    background-color: #0d6efd;
    color: #ffffff;
    border-radius: 16px 16px 4px 16px;
    padding: 8px 12px;
    max-width: 82%;
    margin-left: auto;
    word-break: break-word;
}
.chat-bubble-ai {
    background-color: #ffffff;
    color: #212529;
    border-radius: 16px 16px 16px 4px;
    padding: 10px 14px;
    max-width: 88%;
    margin-right: auto;
    border: 1px solid #e9ecef;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    word-break: break-word;
}
.chat-bubble-ai p:last-child {
    margin-bottom: 0;
}
.chat-bubble-ai ul, .chat-bubble-ai ol {
    padding-left: 18px;
    margin-bottom: 6px;
}
</style>

<script>
(function() {
    let globalChatHistory = [];

    window.toggleGlobalChatbot = function(e) {
        if (e && typeof e.stopPropagation === 'function') e.stopPropagation();
        
        const trigger = document.getElementById('ulineChatTrigger');
        const card = document.getElementById('ulineChatCard');
        if (!trigger || !card) return;

        const isCurrentlyHidden = (card.style.display === 'none' || card.style.display === '');
        if (isCurrentlyHidden) {
            trigger.style.setProperty('display', 'none', 'important');
            card.style.setProperty('display', 'flex', 'important');
            setTimeout(function() {
                document.getElementById('ulineChatInput')?.focus();
            }, 80);
        } else {
            card.style.setProperty('display', 'none', 'important');
            trigger.style.setProperty('display', 'flex', 'important');
        }
    };

    window.clearGlobalChatHistory = function(e) {
        if (e && typeof e.stopPropagation === 'function') e.stopPropagation();
        globalChatHistory = [];
        const messages = document.getElementById('ulineChatMessages');
        if (messages) {
            const cards = messages.querySelectorAll('.chat-message-item');
            cards.forEach(c => c.remove());
        }
    };

    window.sendQuickPrompt = function(promptText) {
        const input = document.getElementById('ulineChatInput');
        if (input) {
            input.value = promptText;
            document.getElementById('ulineChatForm')?.dispatchEvent(new Event('submit', { cancelable: true }));
        }
    };

    window.handleGlobalChatSubmit = async function(e) {
        if (e) e.preventDefault();
        const input = document.getElementById('ulineChatInput');
        const sendBtn = document.getElementById('ulineChatSendBtn');
        const typing = document.getElementById('ulineChatTyping');
        const messageContainer = document.getElementById('ulineChatMessages');
        if (!input || !messageContainer) return;

        const text = input.value.trim();
        if (!text) return;

        // 1. Render User Message
        input.value = '';
        input.disabled = true;
        sendBtn.disabled = true;

        appendMessageUI('user', text);
        typing.classList.remove('d-none');
        typing.classList.add('d-flex');
        messageContainer.scrollTop = messageContainer.scrollHeight;

        try {
            const targetUrl = (window.APP_URL ? window.APP_URL.replace(/\/$/, '') : '') + '/api/chat/global';
            const res = await fetch(targetUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    message: text,
                    history: globalChatHistory
                })
            });

            const data = await res.json();
            typing.classList.remove('d-flex');
            typing.classList.add('d-none');

            if (data.status === 'success' && data.reply) {
                appendMessageUI('assistant', data.reply);
                globalChatHistory.push({ role: 'user', content: text });
                globalChatHistory.push({ role: 'assistant', content: data.reply });
            } else {
                appendMessageUI('assistant', data.message || 'Maaf, terjadi kendala saat memproses permintaan Anda.');
            }
        } catch (err) {
            typing.classList.remove('d-flex');
            typing.classList.add('d-none');
            appendMessageUI('assistant', 'Tidak dapat terhubung ke server asisten AI. Periksa koneksi server atau internet.');
        } finally {
            input.disabled = false;
            sendBtn.disabled = false;
            input.focus();
            messageContainer.scrollTop = messageContainer.scrollHeight;
        }
    };

    function appendMessageUI(role, content) {
        const messageContainer = document.getElementById('ulineChatMessages');
        if (!messageContainer) return;

        const wrapper = document.createElement('div');
        wrapper.className = 'd-flex mb-3 chat-message-item ' + (role === 'user' ? 'justify-content-end' : 'justify-content-start');

        const bubble = document.createElement('div');
        bubble.className = role === 'user' ? 'chat-bubble-user' : 'chat-bubble-ai';

        if (role === 'user') {
            bubble.textContent = content;
        } else {
            bubble.innerHTML = formatMarkdownSimple(content);
        }

        wrapper.appendChild(bubble);
        messageContainer.appendChild(wrapper);
        messageContainer.scrollTop = messageContainer.scrollHeight;
    }

    function formatMarkdownSimple(text) {
        if (!text) return '';
        let escaped = text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;");

        // Bold
        escaped = escaped.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        // Italic
        escaped = escaped.replace(/\*(.*?)\*/g, '<em>$1</em>');
        // Bullet lists
        escaped = escaped.replace(/^\* (.*$)/gim, '<li>$1</li>');
        escaped = escaped.replace(/(<li>.*<\/li>)/s, '<ul>$1</ul>');
        // Newlines
        escaped = escaped.replace(/\n\n/g, '<br><br>').replace(/\n/g, '<br>');

        return escaped;
    }
})();
</script>
