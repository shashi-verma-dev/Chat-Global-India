import './bootstrap';

function playNotificationSound() {
    try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioCtx.createOscillator();
        const gainNode = audioCtx.createGain();

        oscillator.connect(gainNode);
        gainNode.connect(audioCtx.destination);

        oscillator.type = 'sine';
        oscillator.frequency.setValueAtTime(800, audioCtx.currentTime);
        oscillator.frequency.exponentialRampToValueAtTime(1200, audioCtx.currentTime + 0.1);

        gainNode.gain.setValueAtTime(0.1, audioCtx.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.1);

        oscillator.start(audioCtx.currentTime);
        oscillator.stop(audioCtx.currentTime + 0.1);
    } catch (e) {
        console.error("Audio playback failed", e);
    }
}

// function playNotificationSound() {
//     try {
//         const audio = new Audio('/audio/notification.wav');
//         audio.play().catch(e => console.error("Audio playback failed", e));
//     } catch (e) {
//         console.error("Audio playback failed", e);
//     }
// }

function showToast(message) {
    const aside = document.getElementById('notifications-aside');
    if (!aside) return;

    const notif = document.createElement('div');
    notif.className = 'notif-1 bg-white/95 backdrop-blur-sm border border-slate-100 rounded-xl shadow-lg px-4 py-3 flex items-start gap-3 pointer-events-auto';
    notif.innerHTML = `
        <span class="text-base mt-0.5 shrink-0" role="img" aria-label="Alert">⚠️</span>
        <div class="min-w-0">
            <p class="text-xs font-bold truncate">System Message</p>
            <p class="text-xs leading-snug mt-0.5 line-clamp-2">${message}</p>
        </div>
    `;

    aside.prepend(notif);
    setTimeout(() => notif.remove(), 10000);
}
window.showToast = showToast;

document.addEventListener('DOMContentLoaded', () => {
    const messagesArea = document.getElementById('messages-area');
    const messageForm = document.getElementById('message-form');
    const messageInput = document.getElementById('message-input');
    const sendBtn = document.getElementById('send-btn');
    const onlineUsersCount = document.getElementById('online-users-count');
    const emojiList = ['👍', '❤️', '😂', '😮'];

    if (messagesArea) messagesArea.scrollTop = messagesArea.scrollHeight;

    function scrollToBottom() {
        if (messagesArea) {
            messagesArea.scrollTo({ top: messagesArea.scrollHeight, behavior: 'smooth' });
        }
    }

    if (messageForm) {
        messageForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const text = messageInput.value.trim();
            if (!text) return;

            messageInput.disabled = true;
            sendBtn.disabled = true;

            try {
                await axios.post('/messages', { message: text });
                messageInput.value = '';
            } catch (error) {
                if (error.response?.data?.popup) showToast(error.response.data.popup);
            } finally {
                messageInput.disabled = false;
                sendBtn.disabled = false;
                messageInput.focus();
            }
        });
    }

    if (messagesArea) {
        messagesArea.addEventListener('submit', async (e) => {
            if (e.target && e.target.classList.contains('reaction-form')) {
                e.preventDefault();

                const form = e.target;
                const messageId = form.dataset.messageId;
                const emoji = form.dataset.emoji;
                const btn = form.querySelector('.reaction-btn');
                btn.disabled = true;

                try {
                    await axios.post(`/messages/${messageId}/like`, { type: emoji });
                } catch (error) {
                    if (error.response?.data?.popup) showToast(error.response.data.popup);
                } finally {
                    btn.disabled = false;
                }
            }
        });
    }

    setInterval(() => axios.post('/heartbeat').catch(() => { }), 30000);
    axios.post('/heartbeat').catch(() => { });

    Echo.channel('global-chat')
        .listen('.message.created', (e) => {
            if (!messagesArea) return;

            const isEven = e.id % 2 === 0;

            const alignCardClass = isEven ? 'self-start items-start' : 'self-end items-end';
            const bubbleCornerClass = isEven ? 'rounded-tl-sm' : 'rounded-tr-sm';
            const rowClass = isEven ? 'flex-row' : 'flex-row-reverse';

            const article = document.createElement('article');
            article.className = `flex flex-col gap-1.5 message-card ${alignCardClass} max-w-[88%] sm:max-w-[78%] lg:max-w-[70%]`;
            article.dataset.id = e.id;

            let reactionsHtml = '';
            emojiList.forEach(emoji => {
                reactionsHtml += `
                    <form class="inline-flex reaction-form m-0" data-message-id="${e.id}" data-emoji="${emoji}">
                        <button type="submit" class="reaction-btn group flex items-center justify-center gap-1.5 text-xs transition-all duration-200 cursor-pointer rounded-full px-2.5 py-1 hover:bg-purple-50 text-slate-500 hover:text-purple-600" aria-label="React with ${emoji}">
                            <span class="emoji-icon inline-block transform group-hover:scale-125 group-hover:-translate-y-0.5 transition-all duration-200">${emoji}</span>
                            <span class="reactions-count font-semibold tabular-nums" data-emoji-target="${emoji}">
                                0
                            </span>
                        </button>
                    </form>
                `;
            });

            article.innerHTML = `
                <div class="flex items-center gap-2 px-1 ${rowClass}">
                    <time class="text-xs text-gray-300">${e.created_at}</time>
                </div>
                <div class="message-bubble bg-white border border-slate-100 rounded-xl ${bubbleCornerClass} px-4 py-1 shadow-sm">
                    <p class="text-sm text-gray-700 leading-relaxed message-text"></p>
                </div>
                <div class="flex items-center gap-1 mt-1 bg-white/70 backdrop-blur-md border border-slate-200/60 rounded-full shadow-sm p-1 transition-all duration-300 hover:bg-white hover:shadow-md hover:border-slate-200"  style="background: black; border: none;">
                    ${reactionsHtml}
                </div>
            `;

            article.querySelector('.message-text').textContent = e.message;
            messagesArea.appendChild(article);

            const cards = messagesArea.querySelectorAll('.message-card');
            if (cards.length > 100) cards[0].remove();

            scrollToBottom();
            playNotificationSound();
        })
        .listen('.message.liked', (e) => {
            if (!messagesArea) return;
            const article = messagesArea.querySelector(`.message-card[data-id="${e.message_id}"]`);
            if (!article) return;

            const bubble = article.querySelector('.message-bubble');
            const text = article.querySelector('.message-text');
            const countSpans = article.querySelectorAll('.reactions-count');

            countSpans.forEach(span => {
                const emoji = span.dataset.emojiTarget;
                span.textContent = e.reactions_count[emoji] || 0;

                if (e.is_popular) {
                    span.classList.remove('text-gray-400');
                    span.classList.add('text-purple-400');
                }
            });

            if (e.is_popular) {
                bubble.classList.remove('bg-white', 'border-slate-100', 'shadow-sm');
                bubble.classList.add('bg-purple-700', 'border-purple-600', 'shadow-md', 'shadow-purple-200');
                text.classList.remove('text-gray-700');
                text.classList.add('text-white');
            }
        })
        .listen('.online-users.updated', (e) => {
            if (onlineUsersCount) onlineUsersCount.textContent = e.count + ' Online';
        })
        .listen('.chat.cleared', () => {
            if (messagesArea) messagesArea.innerHTML = '';
            showToast('Chat has been cleared by admin.');
        })
        .listen('.announcement.broadcasted', (e) => {
            let overlay = document.getElementById('announcement-overlay');
            if (overlay) overlay.remove();

            overlay = document.createElement('div');
            overlay.id = 'announcement-overlay';
            overlay.setAttribute('role', 'dialog');
            overlay.setAttribute('aria-modal', 'true');
            overlay.className = 'announcement-overlay fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm px-4';
            overlay.style.animation = `announceFadeOut ${e.timeout + 0.5}s ease forwards`;

            overlay.innerHTML = `
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-7 text-center">
                    <div class="text-5xl mb-4" role="img">📢</div>
                    <h2 class="text-xl font-extrabold text-gray-800 mb-1 tracking-tight announcement-title"></h2>
                    <p class="text-sm text-gray-400 leading-relaxed mb-5 announcement-body"></p>
                    <div class="h-1 rounded-full bg-slate-100 overflow-hidden">
                        <div class="progress-bar h-full bg-purple-500 rounded-full" style="animation: progressShrink ${e.timeout}s linear forwards;"></div>
                    </div>
                    <p class="text-xs text-gray-300 mt-2">Closing automatically…</p>
                </div>
            `;

            overlay.querySelector('.announcement-title').textContent = e.title;
            overlay.querySelector('.announcement-body').textContent = e.body;

            document.body.appendChild(overlay);
            setTimeout(() => document.getElementById('announcement-overlay')?.remove(), (e.timeout + 1) * 1000);
        });
});
