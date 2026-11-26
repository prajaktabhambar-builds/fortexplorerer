/**
 * FORT EXPLORER - AI Heritage Assistant Floating Chatbot Controller
 * Multilingual tourism assistant supporting English, Marathi, and Hindi.
 */

class AIHeritageAssistant {
    constructor() {
        this.currentLang = 'en';
        this.sessionId = this.getOrCreateSessionId();
        this.isOpen = false;
        this.isSpeaking = false;
        this.synth = window.speechSynthesis || null;

        this.initDOM();
        this.bindEvents();
        this.loadChatHistory();
    }

    getOrCreateSessionId() {
        let sid = sessionStorage.getItem('fe_ai_session_id');
        if (!sid) {
            sid = 'sess_' + Math.random().toString(36).substring(2, 12) + '_' + Date.now();
            sessionStorage.setItem('fe_ai_session_id', sid);
        }
        return sid;
    }

    initDOM() {
        // Floating Launcher Button
        const launcher = document.createElement('div');
        launcher.id = 'fe-ai-launcher';
        launcher.className = 'fe-ai-launcher shadow-lg';
        launcher.setAttribute('title', 'AI Heritage Assistant');
        launcher.innerHTML = `
            <div class="fe-ai-launcher-icon">
                <i class="fa-solid fa-crown text-warning"></i>
            </div>
            <span class="fe-ai-launcher-pulse"></span>
            <span class="fe-ai-launcher-badge">AI Assistant</span>
        `;
        document.body.appendChild(launcher);

        // Chat Container Box
        const chatBox = document.createElement('div');
        chatBox.id = 'fe-ai-chat-box';
        chatBox.className = 'fe-ai-chat-box shadow-lg';
        chatBox.style.display = 'none';
        chatBox.innerHTML = `
            <div class="fe-ai-header">
                <div class="d-flex align-items-center gap-2">
                    <div class="fe-ai-avatar">
                        <i class="fa-solid fa-shield-halved text-warning"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-bold font-heritage text-white" id="aiHeaderTitle">AI Heritage Assistant</h6>
                        <span class="text-white-50 small" style="font-size: 0.72rem;">Maharashtra Tourism & History</span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-1">
                    <!-- Language Switcher -->
                    <div class="btn-group btn-group-sm me-1" role="group">
                        <button type="button" class="btn btn-outline-light btn-xs fe-ai-lang-btn active" data-lang="en">EN</button>
                        <button type="button" class="btn btn-outline-light btn-xs fe-ai-lang-btn" data-lang="mr">मराठी</button>
                        <button type="button" class="btn btn-outline-light btn-xs fe-ai-lang-btn" data-lang="hi">हिंदी</button>
                    </div>
                    <button type="button" class="btn btn-link text-white p-1 text-decoration-none" id="fe-ai-close-btn" title="Minimize">
                        <i class="fa-solid fa-xmark fs-5"></i>
                    </button>
                </div>
            </div>

            <div class="fe-ai-messages" id="feAiMessagesList">
                <!-- Welcome Message -->
                <div class="fe-ai-msg fe-ai-msg-bot">
                    <div class="fe-ai-bubble">
                        <div class="small fw-bold text-success mb-1"><i class="fa-solid fa-crown me-1 text-warning"></i>FortExplorer Assistant</div>
                        <p class="mb-2" id="aiWelcomeText">
                            Jai Shivray! I am your AI Heritage Guide. Ask me about Chhatrapati Shivaji Maharaj's forts, history, trekking routes, visiting seasons, and travel tips.
                        </p>
                        <div class="fe-ai-quick-pills" id="feAiQuickPills">
                            <button type="button" class="fe-ai-pill-btn" data-query="Tell me about Raigad Fort">Raigad Fort</button>
                            <button type="button" class="fe-ai-pill-btn" data-query="Birthplace of Shivaji Maharaj">Shivneri Fort</button>
                            <button type="button" class="fe-ai-pill-btn" data-query="Best time to visit Sinhagad">Sinhagad Timing</button>
                        </div>
                    </div>
                    <span class="fe-ai-msg-time">Just now</span>
                </div>
            </div>

            <div class="fe-ai-footer">
                <form id="feAiChatForm" class="d-flex align-items-center gap-2">
                    <input type="text" id="feAiInput" class="form-control form-control-sm" placeholder="Ask about forts, history, timings..." autocomplete="off" required>
                    <button type="button" id="feAiVoiceBtn" class="btn btn-outline-secondary btn-sm" title="Voice Input">
                        <i class="fa-solid fa-microphone"></i>
                    </button>
                    <button type="submit" class="btn btn-fe-primary btn-sm" id="feAiSubmitBtn">
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        `;
        document.body.appendChild(chatBox);
    }

    bindEvents() {
        const launcher = document.getElementById('fe-ai-launcher');
        const closeBtn = document.getElementById('fe-ai-close-btn');
        const form = document.getElementById('feAiChatForm');
        const input = document.getElementById('feAiInput');
        const langBtns = document.querySelectorAll('.fe-ai-lang-btn');
        const voiceBtn = document.getElementById('feAiVoiceBtn');

        // Toggle chat box
        launcher.addEventListener('click', () => this.toggleChat());
        closeBtn.addEventListener('click', () => this.toggleChat(false));

        // Language toggle
        langBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                langBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                this.currentLang = btn.getAttribute('data-lang');
                this.updateLanguageUI();
            });
        });

        // Form submit
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const query = input.value.trim();
            if (!query) return;
            input.value = '';
            this.sendUserMessage(query);
        });

        // Quick pill clicks
        document.addEventListener('click', (e) => {
            if (e.target && e.target.classList.contains('fe-ai-pill-btn')) {
                const query = e.target.getAttribute('data-query');
                if (query) {
                    this.sendUserMessage(query);
                }
            }
        });

        // Voice Recognition Support (Web Speech API)
        if (voiceBtn) {
            const SpeechRec = window.SpeechRecognition || window.webkitSpeechRecognition;
            if (SpeechRec) {
                const recognition = new SpeechRec();
                recognition.continuous = false;
                recognition.interimResults = false;

                voiceBtn.addEventListener('click', () => {
                    const langCode = this.currentLang === 'mr' ? 'mr-IN' : (this.currentLang === 'hi' ? 'hi-IN' : 'en-IN');
                    recognition.lang = langCode;
                    voiceBtn.classList.add('btn-danger', 'animate-pulse');
                    try {
                        recognition.start();
                    } catch (e) {
                        voiceBtn.classList.remove('btn-danger', 'animate-pulse');
                    }
                });

                recognition.onresult = (event) => {
                    const transcript = event.results[0][0].transcript;
                    input.value = transcript;
                    voiceBtn.classList.remove('btn-danger', 'animate-pulse');
                    this.sendUserMessage(transcript);
                };

                recognition.onerror = () => {
                    voiceBtn.classList.remove('btn-danger', 'animate-pulse');
                };

                recognition.onend = () => {
                    voiceBtn.classList.remove('btn-danger', 'animate-pulse');
                };
            } else {
                voiceBtn.style.display = 'none';
            }
        }
    }

    toggleChat(show = null) {
        const chatBox = document.getElementById('fe-ai-chat-box');
        const launcher = document.getElementById('fe-ai-launcher');
        if (show === null) {
            this.isOpen = !this.isOpen;
        } else {
            this.isOpen = show;
        }

        if (this.isOpen) {
            chatBox.style.display = 'flex';
            launcher.style.display = 'none';
            const input = document.getElementById('feAiInput');
            if (input) setTimeout(() => input.focus(), 200);
        } else {
            chatBox.style.display = 'none';
            launcher.style.display = 'flex';
        }
    }

    updateLanguageUI() {
        const welcomeText = document.getElementById('aiWelcomeText');
        const input = document.getElementById('feAiInput');
        const pillsContainer = document.getElementById('feAiQuickPills');

        if (this.currentLang === 'mr') {
            welcomeText.textContent = "जय शिवराय! मी आपला एआय वारसा मार्गदर्शक आहे. छत्रपती शिवाजी महाराजांचे किल्ले, इतिहास, ट्रेकिंग मार्ग आणि पर्यटन माहितीबद्दल मला विचारा.";
            input.placeholder = "किल्ले, इतिहास किंवा वेळेबद्दल विचारा...";
            pillsContainer.innerHTML = `
                <button type="button" class="fe-ai-pill-btn" data-query="किल्ले रायगडाचा इतिहास सांगा">किल्ले रायगड</button>
                <button type="button" class="fe-ai-pill-btn" data-query="शिवनेरीवर जन्म कधी झाला?">किल्ले शिवनेरी</button>
                <button type="button" class="fe-ai-pill-btn" data-query="सिंहगडाची वेळ काय आहे?">सिंहगड वेळ</button>
            `;
        } else if (this.currentLang === 'hi') {
            welcomeText.textContent = "जय शिवराय! मैं आपका एआई हेरिटेज गाइड हूँ। छत्रपति शिवाजी महाराज के किलों, इतिहास, ट्रेकिंग और यात्रा योजना के बारे में मुझसे पूछें।";
            input.placeholder = "किलों, इतिहास या समय के बारे में पूछें...";
            pillsContainer.innerHTML = `
                <button type="button" class="fe-ai-pill-btn" data-query="रायगढ़ किले का इतिहास">रायगढ़ किला</button>
                <button type="button" class="fe-ai-pill-btn" data-query="शिवनेरी किला जन्मस्थान">शिवनेरी किला</button>
                <button type="button" class="fe-ai-pill-btn" data-query="सिंहगढ़ किला कब जाएं?">सिंहगढ़ समय</button>
            `;
        } else {
            welcomeText.textContent = "Jai Shivray! I am your AI Heritage Guide. Ask me about Chhatrapati Shivaji Maharaj's forts, history, trekking routes, visiting seasons, and travel tips.";
            input.placeholder = "Ask about forts, history, timings...";
            pillsContainer.innerHTML = `
                <button type="button" class="fe-ai-pill-btn" data-query="Tell me about Raigad Fort">Raigad Fort</button>
                <button type="button" class="fe-ai-pill-btn" data-query="Birthplace of Shivaji Maharaj">Shivneri Fort</button>
                <button type="button" class="fe-ai-pill-btn" data-query="Best time to visit Sinhagad">Sinhagad Timing</button>
            `;
        }
    }

    sendUserMessage(text) {
        this.appendMessage('user', text);
        this.showTypingIndicator();

        // AJAX POST to /api/ai-chat.php
        fetch('api/ai-chat.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                message: text,
                lang: this.currentLang,
                session_id: this.sessionId
            })
        })
        .then(res => res.json())
        .then(data => {
            this.removeTypingIndicator();
            if (data.status === 'success') {
                this.appendMessage('bot', data.reply, data.related_destination, data.suggestions);
                this.saveChatHistory();
            } else {
                this.appendMessage('bot', data.reply || 'Sorry, I encountered an issue retrieving that information.');
            }
        })
        .catch(() => {
            this.removeTypingIndicator();
            this.appendMessage('bot', 'Network error. Please ensure you are connected to the server.');
        });
    }

    appendMessage(sender, text, relatedDest = null, suggestions = []) {
        const container = document.getElementById('feAiMessagesList');
        const msgDiv = document.createElement('div');
        msgDiv.className = `fe-ai-msg fe-ai-msg-${sender}`;

        let destHtml = '';
        if (relatedDest && relatedDest.slug) {
            destHtml = `
                <div class="mt-2 pt-2 border-top border-secondary border-opacity-25">
                    <a href="destination.php?slug=${encodeURIComponent(relatedDest.slug)}" class="btn btn-sm btn-fe-outline py-0.5 px-2" style="font-size:0.75rem;">
                        <i class="fa-solid fa-shield-halved me-1 text-warning"></i>View ${this.escapeHtml(relatedDest.name)} Page
                    </a>
                </div>
            `;
        }

        let suggestionsHtml = '';
        if (suggestions && suggestions.length > 0) {
            suggestionsHtml = '<div class="fe-ai-quick-pills mt-2">';
            suggestions.forEach(s => {
                suggestionsHtml += `<button type="button" class="fe-ai-pill-btn" data-query="${this.escapeHtml(s)}">${this.escapeHtml(s)}</button>`;
            });
            suggestionsHtml += '</div>';
        }

        // Text-to-speech button for bot messages
        let ttsBtn = '';
        if (sender === 'bot') {
            ttsBtn = `
                <button type="button" class="btn btn-link p-0 text-muted fe-ai-tts-btn ms-2" title="Listen" onclick="feAiChat.speakText(${JSON.stringify(text)})">
                    <i class="fa-solid fa-volume-high" style="font-size:0.75rem;"></i>
                </button>
            `;
        }

        // Format basic Markdown bold and bullets
        const formattedText = this.formatMarkdown(text);

        msgDiv.innerHTML = `
            <div class="fe-ai-bubble">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="small fw-bold ${sender === 'bot' ? 'text-success' : 'text-primary'}">
                        ${sender === 'bot' ? '<i class="fa-solid fa-shield-halved text-warning me-1"></i>Heritage AI' : 'You'}
                    </span>
                    ${ttsBtn}
                </div>
                <div class="fe-ai-text">${formattedText}</div>
                ${destHtml}
                ${suggestionsHtml}
            </div>
            <span class="fe-ai-msg-time">${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>
        `;

        container.appendChild(msgDiv);
        container.scrollTop = container.scrollHeight;
    }

    showTypingIndicator() {
        const container = document.getElementById('feAiMessagesList');
        const typing = document.createElement('div');
        typing.id = 'feAiTypingIndicator';
        typing.className = 'fe-ai-msg fe-ai-msg-bot';
        typing.innerHTML = `
            <div class="fe-ai-bubble py-2 px-3">
                <span class="fe-ai-typing-dot"></span>
                <span class="fe-ai-typing-dot"></span>
                <span class="fe-ai-typing-dot"></span>
            </div>
        `;
        container.appendChild(typing);
        container.scrollTop = container.scrollHeight;
    }

    removeTypingIndicator() {
        const el = document.getElementById('feAiTypingIndicator');
        if (el) el.remove();
    }

    speakText(text) {
        if (!this.synth) return;
        if (this.synth.speaking) {
            this.synth.cancel();
            return;
        }

        // Clean text of markdown asterisks and bullets
        const clean = text.replace(/[*#•]/g, '');
        const utterance = new SpeechSynthesisUtterance(clean);
        utterance.rate = 1.0;
        utterance.lang = this.currentLang === 'mr' ? 'mr-IN' : (this.currentLang === 'hi' ? 'hi-IN' : 'en-IN');
        this.synth.speak(utterance);
    }

    formatMarkdown(str) {
        let text = this.escapeHtml(str);
        text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        text = text.replace(/\*(.*?)\*/g, '<em>$1</em>');
        text = text.replace(/•\s+(.*)/g, '<li class="small">$1</li>');
        text = text.replace(/\n/g, '<br>');
        return text;
    }

    escapeHtml(str) {
        return (str || '')
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    saveChatHistory() {
        const container = document.getElementById('feAiMessagesList');
        if (container) {
            sessionStorage.setItem('fe_ai_history', container.innerHTML);
        }
    }

    loadChatHistory() {
        const saved = sessionStorage.getItem('fe_ai_history');
        if (saved) {
            const container = document.getElementById('feAiMessagesList');
            if (container) container.innerHTML = saved;
        }
    }
}

// Global initialization
let feAiChat = null;
document.addEventListener('DOMContentLoaded', () => {
    feAiChat = new AIHeritageAssistant();
});
