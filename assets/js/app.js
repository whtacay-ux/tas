// Discord Clone - Ana JavaScript Dosyası
// Versiyon: 3.0 - WebRTC Destekli

// ========== AYARLAR ==========
const CONFIG = {
    API_URL: '../api/',
    POLLING_INTERVAL: 3000,
    TIMEOUT: 10000,
    MAX_RETRY: 3
};

// ========== TEMA YÖNETİMİ ==========
const ThemeManager = {
    init() {
        const savedTheme = localStorage.getItem('theme') || 'dark';
        document.documentElement.setAttribute('data-theme', savedTheme);
        this.bindEvents();
    },

    toggle() {
        const current = document.documentElement.getAttribute('data-theme');
        const newTheme = current === 'dark' ? 'light' : 'dark';
        
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        
        if (window.currentUserId) {
            fetch(`${CONFIG.API_URL}update-theme.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ theme: newTheme })
            }).catch(() => {});
        }
    },

    bindEvents() {
        const toggle = document.querySelector('.theme-toggle');
        if (toggle) toggle.addEventListener('click', () => this.toggle());
    }
};

// ========== MODAL YÖNETİMİ ==========
const ModalManager = {
    open(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    },

    close(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    },

    closeAll() {
        document.querySelectorAll('.modal-overlay').forEach(m => {
            m.classList.remove('active');
        });
        document.body.style.overflow = '';
    },

    init() {
        document.querySelectorAll('.modal-close, .modal-cancel').forEach(btn => {
            btn.addEventListener('click', () => {
                const modal = btn.closest('.modal-overlay');
                if (modal) modal.classList.remove('active');
            });
        });

        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) overlay.classList.remove('active');
            });
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') this.closeAll();
        });
    }
};

// ========== FETCH YARDIMCISI ==========
const API = {
    async request(endpoint, options = {}) {
        const url = CONFIG.API_URL + endpoint;
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), CONFIG.TIMEOUT);
        
        try {
            const response = await fetch(url, {
                ...options,
                signal: controller.signal,
                headers: {
                    'Content-Type': 'application/json',
                    ...options.headers
                }
            });
            
            clearTimeout(timeoutId);
            const text = await response.text();
            
            if (!text || text.trim() === '') {
                return { success: true };
            }
            
            let jsonText = text;
            if (text.indexOf('<') !== -1) {
                const start = text.lastIndexOf('{');
                const end = text.lastIndexOf('}') + 1;
                if (start !== -1 && end > start) {
                    jsonText = text.substring(start, end);
                }
            }
            
            return JSON.parse(jsonText);
            
        } catch (error) {
            console.error(`API Hatası (${endpoint}):`, error);
            throw error;
        }
    }
};

// ========== MESAJLAŞMA ==========
const ChatManager = {
    channelId: null,
    messages: [],
    messageIds: new Set(),
    pending: [],
    isLoading: false,
    pollTimer: null,

    init(channelId) {
        this.channelId = channelId;
        this.messages = [];
        this.messageIds.clear();
        this.pending = [];
        
        this.loadMessages();
        this.bindEvents();
        this.startPolling();
    },

    bindEvents() {
        const form = document.getElementById('message-form');
        const input = document.getElementById('message-input');

        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.send();
            });
        }

        if (input) {
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.send();
                }
            });

            input.addEventListener('input', () => {
                input.style.height = 'auto';
                input.style.height = Math.min(input.scrollHeight, 200) + 'px';
            });
        }

        const container = document.getElementById('messages-container');
        if (container) {
            container.addEventListener('scroll', () => {
                if (container.scrollTop < 50 && !this.isLoading) {
                    this.loadOlder();
                }
            });
        }
    },

    async loadMessages() {
        if (!this.channelId || this.isLoading) return;
        this.isLoading = true;
        
        try {
            const data = await API.request(`messages.php?channel_id=${this.channelId}`);
            
            if (data.success) {
                const msgs = data.messages || [];
                
                msgs.forEach(msg => {
                    if (!this.messageIds.has(msg.id)) {
                        this.messageIds.add(msg.id);
                        this.messages.push(msg);
                    }
                });
                
                this.render();
            }
        } catch (error) {
            console.error('Mesajlar yüklenemedi:', error);
        } finally {
            this.isLoading = false;
            const loading = document.querySelector('.loading');
            if (loading) loading.remove();
        }
    },

    async loadOlder() {
        if (!this.channelId || this.messages.length === 0) return;
        
        const oldestId = Math.min(...this.messages.map(m => parseInt(m.id) || 0));
        
        try {
            const data = await API.request(`messages.php?channel_id=${this.channelId}&before=${oldestId}`);
            
            if (data.success && data.messages) {
                const container = document.getElementById('messages-container');
                const oldHeight = container.scrollHeight;
                
                data.messages.reverse().forEach(msg => {
                    if (!this.messageIds.has(msg.id)) {
                        this.messageIds.add(msg.id);
                        this.messages.unshift(msg);
                    }
                });
                
                this.render();
                container.scrollTop = container.scrollHeight - oldHeight;
            }
        } catch (error) {
            console.error('Eski mesajlar yüklenemedi:', error);
        }
    },

    async send() {
        const input = document.getElementById('message-input');
        const text = input.value.trim();
        
        if (!text || !this.channelId) return;
        
        input.value = '';
        input.style.height = 'auto';
        
        const tempId = 'temp_' + Date.now();
        const tempMsg = {
            id: tempId,
            message: text,
            user_id: window.currentUserId,
            username: window.currentUsername || 'Sen',
            avatar: window.currentAvatar || '../assets/uploads/avatars/default-avatar.png',
            user_color: '#fff',
            created_at: new Date().toISOString(),
            is_pending: true
        };
        
        this.pending.push(tempMsg);
        this.render();
        this.scrollToBottom();
        
        try {
            await API.request('send-message.php', {
                method: 'POST',
                body: JSON.stringify({
                    channel_id: this.channelId,
                    message: text
                })
            });
            
            this.pending = this.pending.filter(m => m.id !== tempId);
            setTimeout(() => this.loadMessages(), 300);
            
        } catch (error) {
            this.pending = this.pending.filter(m => m.id !== tempId);
            setTimeout(() => this.loadMessages(), 1000);
        }
    },

    render() {
        const container = document.getElementById('messages-container');
        if (!container) return;
        
        const all = [...this.messages, ...this.pending];
        
        if (all.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-comments"></i>
                    <h3>Henüz mesaj yok</h3>
                    <p>İlk mesajı siz gönderin!</p>
                </div>
            `;
            return;
        }
        
        const unique = [];
        const seen = new Set();
        
        all.sort((a, b) => new Date(a.created_at) - new Date(b.created_at))
           .forEach(msg => {
               if (!seen.has(msg.id)) {
                   seen.add(msg.id);
                   unique.push(msg);
               }
           });
        
        container.innerHTML = unique.map(m => this.html(m)).join('');
    },

    html(msg) {
        const time = new Date(msg.created_at).toLocaleTimeString('tr-TR', {
            hour: '2-digit',
            minute: '2-digit'
        });
        
        const edited = msg.is_edited ? '<span class="edited">(düzenlendi)</span>' : '';
        const pending = msg.is_pending ? '<i class="fas fa-clock fa-spin" style="margin-left:5px;opacity:0.5;"></i>' : '';
        
        const avatar = msg.avatar?.includes('default') || !msg.avatar
            ? '../assets/uploads/avatars/default-avatar.png'
            : msg.avatar;
        
        return `
            <div class="message ${msg.is_pending ? 'pending' : ''}" data-id="${msg.id}">
                <img src="${avatar}" class="avatar-sm" onerror="this.src='../assets/uploads/avatars/default-avatar.png'">
                <div class="message-body">
                    <div class="message-header">
                        <span class="author" style="color:${msg.user_color||'#fff'}">${msg.username}</span>
                        <span class="time">${time}</span>
                        ${edited}${pending}
                    </div>
                    <div class="text">${this.escape(msg.message)}</div>
                </div>
            </div>
        `;
    },

    escape(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },

    scrollToBottom() {
        const c = document.getElementById('messages-container');
        if (c) c.scrollTop = c.scrollHeight;
    },

    startPolling() {
        if (this.pollTimer) clearInterval(this.pollTimer);
        
        this.pollTimer = setInterval(() => {
            if (this.channelId) this.checkNew();
        }, CONFIG.POLLING_INTERVAL);
    },

    stopPolling() {
        if (this.pollTimer) {
            clearInterval(this.pollTimer);
            this.pollTimer = null;
        }
    },

    async checkNew() {
        if (!this.channelId || this.messages.length === 0) return;
        
        const lastId = Math.max(...this.messages.map(m => parseInt(m.id) || 0));
        
        try {
            const data = await API.request(`messages.php?channel_id=${this.channelId}&after=${lastId}`);
            
            if (data.success && data.messages) {
                const container = document.getElementById('messages-container');
                const nearBottom = container && (container.scrollHeight - container.scrollTop - container.clientHeight < 100);
                
                let hasNew = false;
                
                data.messages.forEach(msg => {
                    if (!this.messageIds.has(msg.id)) {
                        this.messageIds.add(msg.id);
                        this.messages.push(msg);
                        hasNew = true;
                    }
                });
                
                if (hasNew) {
                    this.render();
                    if (nearBottom) this.scrollToBottom();
                }
            }
        } catch (error) {
            // Polling hatası sessizce geç
        }
    }
};

// ========== ARKADAŞ YÖNETİMİ ==========
const FriendManager = {
    async sendRequest(username) {
        try {
            const data = await API.request('friend-request.php', {
                method: 'POST',
                body: JSON.stringify({ username })
            });
            
            if (data.success) {
                showToast('Arkadaşlık isteği gönderildi!', 'success');
                const input = document.getElementById('friend-username');
                if (input) input.value = '';
            } else {
                showToast(data.error || 'İstek gönderilemedi', 'error');
            }
        } catch (error) {
            showToast('İstek gönderildi', 'success');
        }
    },

    async respond(id, accept) {
        try {
            const data = await API.request('respond-friend.php', {
                method: 'POST',
                body: JSON.stringify({ friendship_id: id, accept })
            });
            
            if (data.success) {
                location.reload();
            } else {
                showToast(data.error || 'İşlem başarısız', 'error');
            }
        } catch (error) {
            location.reload();
        }
    }
};

// ========== DOSYA YÜKLEME ==========
const FileUploader = {
    async upload(file) {
        const formData = new FormData();
        formData.append('file', file);
        
        try {
            return await API.request('upload.php', {
                method: 'POST',
                body: formData
            });
        } catch (error) {
            return { success: false, error: 'Yükleme hatası' };
        }
    }
};

// ========== BİLDİRİM ==========
const NotificationManager = {
    async load() {
        try {
            const data = await API.request('notifications.php');
            if (data.success) {
                const count = data.unread_count || 0;
                this.setBadge(count);
            }
        } catch (error) {
            console.error('Bildirim hatası:', error);
        }
    },

    setBadge(count) {
        const badge = document.getElementById('notif-badge');
        if (badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'inline' : 'none';
        }
    }
};

// ========== YARDIMCILAR ==========
function showToast(message, type = 'info') {
    const existing = document.querySelector('.toast');
    if (existing) existing.remove();
    
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    const icon = type === 'success' ? 'check-circle' : 
                 type === 'error' ? 'exclamation-circle' : 'info-circle';
    
    toast.innerHTML = `<i class="fas fa-${icon}"></i> ${message}`;
    
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#43b581' : type === 'error' ? '#f04747' : '#5865f2'};
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        z-index: 99999;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

function goHome() {
    window.location.href = 'dashboard.php';
}

// ========== BAŞLATMA ==========
document.addEventListener('DOMContentLoaded', () => {
    ThemeManager.init();
    ModalManager.init();
    NotificationManager.load();
    
    // Global erişim
    window.ChatManager = ChatManager;
    window.FriendManager = FriendManager;
    window.FileUploader = FileUploader;
    window.goHome = goHome;
    window.showToast = showToast;
    
    // CSRF token
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    if (token) {
        const originalFetch = window.fetch;
        window.fetch = function(...args) {
            const [url, opts = {}] = args;
            if (opts.method && opts.method !== 'GET') {
                opts.headers = { ...opts.headers, 'X-CSRF-Token': token };
            }
            return originalFetch(url, opts);
        };
    }
});

// CSS animasyonları
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 1; }
    }
    .message.pending { opacity: 0.6; }
    .message.pending .fa-clock { animation: pulse 1s infinite; }
    @keyframes pulse { 0%, 100% { opacity: 0.3; } 50% { opacity: 1; } }
`;
document.head.appendChild(style);
