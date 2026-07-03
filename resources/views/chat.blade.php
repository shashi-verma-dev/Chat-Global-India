<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Anonymous live chat room. Join the global conversation — no account needed." />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Global Chat — Anonymous Live Chat</title>

    {{-- Inter Font --}}
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet" />

    {{-- Vite Assets --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* ─── Base ─────────────────────────────────────────── */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        html,
        body {
            height: 100%;
        }

        body {
            font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
        }

        /* ─── Custom Scrollbar ──────────────────────────────── */
        .messages-area::-webkit-scrollbar {
            width: 5px;
        }

        .messages-area::-webkit-scrollbar-track {
            background: transparent;
        }

        .messages-area::-webkit-scrollbar-thumb {
            background: #e2e8f0;
            border-radius: 10px;
        }

        .messages-area::-webkit-scrollbar-thumb:hover {
            background: #cbd5e1;
        }

        /* ─── Global Announcement ───────────────────────────── */
        @keyframes announceFadeOut {
            0% {
                opacity: 1;
                visibility: visible;
            }

            80% {
                opacity: 1;
                visibility: visible;
            }

            100% {
                opacity: 0;
                visibility: hidden;
            }
        }

        /* Progress bar shrinking */
        @keyframes progressShrink {
            from {
                width: 100%;
            }

            to {
                width: 0%;
            }
        }

        /* ─── Popup Notifications ───────────────────────────── */
        @keyframes notifSlide {
            0% {
                opacity: 0;
                transform: translateX(110%);
            }

            12% {
                opacity: 1;
                transform: translateX(0);
            }

            80% {
                opacity: 1;
                transform: translateX(0);
            }

            100% {
                opacity: 0;
                transform: translateX(110%);
            }
        }

        .notif-1 {
            animation: notifSlide 9.5s cubic-bezier(.4, 0, .2, 1) 0.3s both;
        }

        /* ─── Reaction Button Pulse ───────────────────────── */
        @keyframes emojiPop {
            0% {
                transform: scale(1);
            }

            40% {
                transform: scale(1.4);
            }

            70% {
                transform: scale(0.9);
            }

            100% {
                transform: scale(1);
            }
        }

        .reaction-btn:hover .emoji-icon {
            animation: emojiPop 0.35s ease forwards;
        }

        /* ─── Online Dot Pulse ──────────────────────────────── */
        @keyframes ripple {
            0% {
                transform: scale(1);
                opacity: 1;
            }

            100% {
                transform: scale(2.4);
                opacity: 0;
            }
        }

        .online-dot::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 9999px;
            background: #22c55e;
            animation: ripple 1.6s ease-out infinite;
        }

        /* ─── Smooth Scroll ─────────────────────────────────── */
        .messages-area {
            scroll-behavior: smooth;
        }

        /* ─── Manual Css ─────────────────────────────────────── */

        /* ─── Bottom Canvas Drawer ───────────────────────────── */
        .bottom-canvas-overlay {
            position: fixed; inset: 0; z-index: 60;
            background: rgba(0,0,0,0.55);
            opacity: 0; visibility: hidden;
            transition: opacity 0.25s ease, visibility 0.25s ease;
        }
        .bottom-canvas-overlay.open { opacity: 1; visibility: visible; }
        .bottom-canvas {
            position: fixed; bottom: 0; left: 0; right: 0; z-index: 61;
            height: 52vh;
            background: #0f0f13;
            border-top: 1px solid rgba(255,255,255,0.08);
            border-radius: 18px 18px 0 0;
            transform: translateY(100%);
            transition: transform 0.3s cubic-bezier(0.32,0.72,0,1);
            overflow-y: auto;
            padding: 20px 24px 28px;
            font-family: 'Inter', sans-serif;
            color: #e2e8f0;
        }
        .bottom-canvas.open { transform: translateY(0); }
        .bottom-canvas::-webkit-scrollbar { width: 4px; }
        .bottom-canvas::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }
        .canvas-handle {
            width: 40px; height: 4px;
            background: rgba(255,255,255,0.15);
            border-radius: 99px; margin: 0 auto 16px;
        }
        .canvas-title { font-size: 13px; font-weight: 700; color: #a78bfa; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 14px; }
        .canvas-section { margin-bottom: 14px; }
        .canvas-section h3 { font-size: 11px; font-weight: 600; color: #7c3aed; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 6px; }
        .canvas-section p, .canvas-section li { font-size: 12.5px; color: #94a3b8; line-height: 1.6; }
        .canvas-section ul { padding-left: 14px; list-style: disc; }
        .canvas-section code { font-size: 11.5px; background: rgba(255,255,255,0.06); border-radius: 4px; padding: 1px 5px; color: #c084fc; font-family: monospace; }
        .floating-links {
            position: fixed; bottom: 85px; right: 18px; z-index: 55;
            display: flex; flex-direction: column; align-items: flex-end; gap: 6px;
        }
        @media (min-width: 640px) { .floating-links { bottom: 18px; } }
        @media (max-width: 768px) { .floating-links{ display:none;} }
        .floating-link {
            font-size: 11px; font-weight: 600;
            color: rgba(255,255,255,0.55);
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 99px; padding: 4px 12px;
            cursor: pointer; letter-spacing: 0.04em;
            transition: color 0.2s, background 0.2s;
            user-select: none;
        }
        .floating-link:hover { color: #c084fc; background: rgba(139,92,246,0.12); border-color: rgba(139,92,246,0.3); }
        .suggestion-form input, .suggestion-form textarea {
            width: 100%; background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px; padding: 8px 12px;
            font-size: 12.5px; color: #e2e8f0; outline: none;
            font-family: 'Inter', sans-serif;
            resize: none; margin-bottom: 8px;
        }
        .suggestion-form input::placeholder, .suggestion-form textarea::placeholder { color: #475569; }
        .suggestion-form input:focus, .suggestion-form textarea:focus { border-color: #7c3aed; }
        .suggestion-form button {
            font-size: 12px; font-weight: 600; color: #fff;
            background: #7c3aed; border: none; border-radius: 8px;
            padding: 8px 20px; cursor: pointer; transition: background 0.2s;
        }
        .suggestion-form button:hover { background: #6d28d9; }
        .dev-avatar {
            width: 52px; height: 52px; border-radius: 50%;
            background: linear-gradient(135deg, #7c3aed, #06b6d4);
            display: flex; align-items: center; justify-content: center;
            font-size: 22px; font-weight: 800; color: #fff;
            margin-bottom: 12px; flex-shrink: 0;
        }

        /* ─── Shooting Stars Background ──────────────────────── */
        .stars-bg {
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            overflow: hidden;
        }

        .shooting-star {
            position: absolute;
            width: 2px;
            height: 120px;
            background: linear-gradient(to bottom, rgba(255, 255, 255, 0) 0%, rgba(255, 255, 255, 0.8) 100%);
            opacity: 0;
            animation: shoot infinite linear;
            will-change: transform, opacity;
        }

        .shooting-star::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 4px;
            height: 4px;
            background: #fff;
            border-radius: 50%;
            box-shadow: 0 0 10px 2px rgba(255, 255, 255, 0.8);
        }

        .shooting-star:nth-child(1) {
            top: -10%;
            left: 30%;
            animation-duration: 6s;
            animation-delay: 0s;
        }

        .shooting-star:nth-child(2) {
            top: -20%;
            left: 70%;
            animation-duration: 8s;
            animation-delay: 1.5s;
        }

        .shooting-star:nth-child(3) {
            top: 10%;
            left: 90%;
            animation-duration: 7s;
            animation-delay: 3s;
        }

        .shooting-star:nth-child(4) {
            top: 30%;
            left: 110%;
            animation-duration: 6.5s;
            animation-delay: 4.5s;
        }

        .shooting-star:nth-child(5) {
            top: -10%;
            left: 100%;
            animation-duration: 9s;
            animation-delay: 2.2s;
        }

        .shooting-star:nth-child(6) {
            top: -30%;
            left: 50%;
            animation-duration: 7.5s;
            animation-delay: 5s;
        }

        .shooting-star:nth-child(7) {
            top: 20%;
            left: 120%;
            animation-duration: 8.5s;
            animation-delay: 1s;
        }

        .shooting-star:nth-child(8) {
            top: 40%;
            left: 100%;
            animation-duration: 6s;
            animation-delay: 3.8s;
        }

        .shooting-star:nth-child(9) {
            top: 0%;
            left: 130%;
            animation-duration: 7.2s;
            animation-delay: 6s;
        }

        .shooting-star:nth-child(10) {
            top: -10%;
            left: 10%;
            animation-duration: 8.2s;
            animation-delay: 2.8s;
        }

        @keyframes shoot {
            0% {
                opacity: 0;
                transform: rotate(-45deg) translateY(-200px);
            }

            5% {
                opacity: 1;
            }

            40% {
                opacity: 0;
                transform: rotate(-45deg) translateY(2500px);
            }

            100% {
                opacity: 0;
                transform: rotate(-45deg) translateY(2500px);
            }
        }

        #send-btn:hover{
            cursor: pointer;
        }
    </style>
</head>

<body class="bg-slate-100 h-screen flex items-center justify-center p-0 sm:p-3 lg:p-5"
    style="background-color: black !important">

    <div class="stars-bg">
        <div class="shooting-star"></div>
        <div class="shooting-star"></div>
        <div class="shooting-star"></div>
        <div class="shooting-star"></div>
        <div class="shooting-star"></div>
        <div class="shooting-star"></div>
        <div class="shooting-star"></div>
        <div class="shooting-star"></div>
        <div class="shooting-star"></div>
        <div class="shooting-star"></div>
    </div>

    <aside id="notifications-aside" aria-label="Chat notifications"
        class="fixed top-4 right-4 z-40 flex flex-col gap-2 w-64 sm:w-72 pointer-events-none"></aside>

    <div
        class="w-full max-w-4xl h-screen sm:h-[calc(100vh-1.5rem)] lg:h-[calc(100vh-2.5rem)] flex flex-col sm:rounded-2xl sm:shadow-2xl sm:border sm:border-slate-200/80 overflow-hidden relative z-10">

        <header
            class="sticky top-0 z-30 bg-white/90 backdrop-blur-md border-b border-slate-100 px-4 sm:px-6 py-3 sm:py-4 flex items-center justify-between shrink-0 shadow-sm">
            <div class="flex items-center gap-3">
                {{-- <div
                    class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-purple-600 flex items-center justify-center shadow-sm shrink-0">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" fill="currentColor" viewBox="0 0 24 24"
                        aria-hidden="true">
                        <path d="M20 2H4a2 2 0 00-2 2v18l4-4h14a2 2 0 002-2V4a2 2 0 00-2-2z" />
                    </svg>
                </div> --}}
                <div>

                    <h1 class="text-sm sm:text-base font-bold text-gray-800 leading-none">Global Chat</h1>
                    <p class="text-xs font-medium mt-0.5 leading-none">feel free to talk about anything.
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2 bg-green-50 border border-green-100 rounded-full pl-2.5 pr-3.5 py-1.5">
                <span class="relative inline-flex w-2 h-2 online-dot">
                    <span class="w-2 h-2 rounded-full bg-green-500 inline-block relative z-10"></span>
                </span>
                <span id="online-users-count"
                    class="text-xs font-semibold text-green-700 tabular-nums">{{ $onlineUsers }} Online</span>
            </div>
        </header>

        <main id="messages-area"
            class="messages-area flex-1 overflow-y-auto px-3 sm:px-5 lg:px-8 py-4 sm:py-6 space-y-5 flex flex-col">
            @php $emojiList = ['👍', '❤️', '😂', '😮']; @endphp

            @foreach ($messages as $index => $message)
                @php
                    $isEven = $index % 2 === 0; // Alternating left/right
                    $alignCardClass = $isEven ? 'self-start items-start' : 'self-end items-end';
                    $bubbleCornerClass = $isEven ? 'rounded-tl-sm' : 'rounded-tr-sm';
                    $reactions = $message->reactions_count ?? [];
                    $isPopular = array_sum($reactions) >= 3;
                @endphp

                <article
                    class="flex flex-col gap-1.5 message-card {{ $alignCardClass }} max-w-[88%] sm:max-w-[78%] lg:max-w-[70%]"
                    data-id="{{ $message->id }}">
                    <div class="flex items-center gap-2 px-1 {{ $isEven ? 'flex-row' : 'flex-row-reverse' }}">
                        <time class="text-xs text-gray-300">{{ $message->created_at->format('h:i A') }}</time>
                    </div>

                    @if ($isPopular)
                        <div
                            class="message-bubble bg-purple-700 border border-purple-600 rounded-xl {{ $bubbleCornerClass }} px-4 py-1">
                            <p class="text-sm text-white leading-relaxed message-text">{{ $message->message }}</p>
                        </div>
                    @else
                        <div
                            class="message-bubble bg-white border border-slate-100 rounded-xl {{ $bubbleCornerClass }} px-4 py-1">
                            <p class="text-sm text-gray-700 leading-relaxed message-text">{{ $message->message }}</p>
                        </div>
                    @endif

                    <div
                        class="flex items-center gap-1.5 px-1 bg-white border border-slate-100 rounded-full shadow-sm p-1 mt-0.5">
                        @foreach ($emojiList as $emoji)
                            <form class="inline-flex reaction-form m-0" data-message-id="{{ $message->id }}"
                                data-emoji="{{ $emoji }}">
                                <button type="submit"
                                    class="reaction-btn flex items-center gap-1 text-xs hover:bg-slate-50 transition-colors duration-200 cursor-pointer rounded-full px-2 py-0.5"
                                    aria-label="React with {{ $emoji }}">
                                    <span class="emoji-icon inline-block">{{ $emoji }}</span>
                                    <span class="reactions-count font-semibold tabular-nums"
                                        data-emoji-target="{{ $emoji }}">
                                        {{ $reactions[$emoji] ?? 0 }}
                                    </span>
                                </button>
                            </form>
                        @endforeach
                    </div>
                </article>
            @endforeach
        </main>

        <footer class="sticky bottom-0 z-30 px-3 sm:px-5 lg:px-8 py-3 sm:py-4 shrink-0">
            <form id="message-form"
                class="flex items-center gap-0 bg-white/90 backdrop-blur-md rounded-full shadow-[0_4px_24px_rgba(139,92,246,0.13)] border border-slate-200/80 px-2 py-1.5 transition-all duration-300 focus-within:shadow-[0_4px_32px_rgba(139,92,246,0.28)] focus-within:border-purple-300"
                aria-label="Send a message">
                <label for="message-input" class="sr-only">Type your message</label>
                <input type="text" id="message-input" name="message" placeholder="Type something..." maxlength="500"
                    autocomplete="off" required
                    class="flex-1 min-w-0 bg-transparent border-none outline-none px-3 py-1.5 text-sm text-gray-700 placeholder-gray-400 disabled:opacity-50 disabled:cursor-not-allowed" />

                <button type="submit" id="send-btn"
                    class="shrink-0 inline-flex items-center justify-center
                           w-9 h-9
                           bg-transparent hover:bg-transparent active:bg-transparent
                           text-purple-600
                           rounded-full
                           focus:outline-none focus:ring-2 focus:ring-purple-400 focus:ring-offset-2
                           transition-all duration-200
                           disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg xmlns="http://www.w3.org/2000/svg" width="4em" height="4em" viewBox="0 0 32 32">
                        <path d="M0 0h32v32H0z" fill="none" />
                        <g fill="none">
                            <path fill="url(#SVGWnG8Fexh)"
                                d="m4.664 20l1.286-3.999l-1.286-3.999l16.092 3.016c1.087.204 1.087 1.762 0 1.966z" />
                            <path fill="url(#SVGZfSkUdnm)"
                                d="M4.176 2.164c-1.188-.594-2.505.536-2.099 1.8l2.858 8.884a1 1 0 0 0 .787.68l11.869 1.979c.557.092.557.893 0 .986L5.723 18.471a1 1 0 0 0-.788.68l-2.858 8.886c-.407 1.265.91 2.395 2.099 1.8L29.17 17.343c1.106-.552 1.106-2.13 0-2.683z" />
                            <path fill="url(#SVGJ3yrNeTK)"
                                d="M4.176 2.164c-1.188-.594-2.505.536-2.099 1.8l2.858 8.884a1 1 0 0 0 .787.68l11.869 1.979c.557.092.557.893 0 .986L5.723 18.471a1 1 0 0 0-.788.68l-2.858 8.886c-.407 1.265.91 2.395 2.099 1.8L29.17 17.343c1.106-.552 1.106-2.13 0-2.683z" />
                            <defs>
                                <linearGradient id="SVGZfSkUdnm" x1="2.002" x2="25.983" y1="-9.374"
                                    y2="22.488" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#3bd5ff" />
                                    <stop offset="1" stop-color="#0094f0" />
                                </linearGradient>
                                <linearGradient id="SVGJ3yrNeTK" x1="16.001" x2="23.283" y1="9.548"
                                    y2="29.249" gradientUnits="userSpaceOnUse">
                                    <stop offset=".125" stop-color="#dcf8ff" stop-opacity="0" />
                                    <stop offset=".769" stop-color="#ff6ce8" stop-opacity=".7" />
                                </linearGradient>
                                <radialGradient id="SVGWnG8Fexh" cx="0" cy="0" r="1"
                                    gradientTransform="matrix(13.9839 0 0 1.81128 .016 16.001)"
                                    gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#0094f0" />
                                    <stop offset="1" stop-color="#2052cb" />
                                </radialGradient>
                            </defs>
                        </g>
                    </svg>
                </button>
            </form>
        </footer>

    </div>

    {{-- ═══════════════════════════════════════════════════════════
         CANVAS OVERLAY (shared backdrop)
    ═══════════════════════════════════════════════════════════ --}}
    <div id="canvas-overlay" class="bottom-canvas-overlay" onclick="closeAllCanvas()"></div>

    {{-- ═══════ DRAWER 1 — DOCS ═══════ --}}
    <div id="canvas-docs" class="bottom-canvas" role="dialog" aria-label="Project Documentation">
        <div class="canvas-handle"></div>
        <p class="canvas-title">📄 In-Depth Project Documentation</p>

        <div class="canvas-section">
            <h3>💡 Project Concept & Architecture Flow</h3>
            <p><strong>ChalGlobalIndia</strong> is a stateless, anonymous global chat room designed for instant,
                real-time communication without friction. There is no registration, login, or persistent identity. Every
                user is assigned a transient <code>Guest NNN</code> identifier derived from a secure, HTTP-only UUID
                cookie generated upon their first visit.</p>
            <p style="margin-top:8px;"><strong>Data Flow:</strong> User submits a message via the Vanilla JS frontend.
                The POST request hits the Laravel backend (<code>MessageController</code>). The backend validates the
                request, applies Rate Limiting, sanitises for XSS, filters profanity, and saves to MySQL. Immediately
                after, an Eloquent Event triggers a broadcast via <strong>Laravel Reverb</strong> (WebSocket server).
                Connected clients listening on the <code>global-chat</code> channel via <strong>Laravel Echo</strong>
                receive the JSON payload and dynamically inject the message into the DOM without refreshing.</p>
        </div>

        <div class="canvas-section">
            <h3>⚙️ Comprehensive Tech Stack</h3>
            <ul>
                <li><strong>Backend Framework:</strong> Laravel 13 (PHP 8.3+) — Handles routing, validation, Eloquent
                    ORM, and broadcasting.</li>
                <li><strong>Database:</strong> MySQL (via XAMPP) — Stores transient message history and emoji reaction
                    counts.</li>
                <li><strong>In-Memory Data Store:</strong> Redis (via Predis) — Powers the Laravel Cache
                    (<code>CACHE_STORE=redis</code>) and async Queues (<code>QUEUE_CONNECTION=redis</code>) for
                    lightning-fast performance.</li>
                <li><strong>WebSocket Server:</strong> Laravel Reverb v1.10 — First-party PHP WebSocket server handling
                    real-time duplex connections.</li>
                <li><strong>Frontend Styling:</strong> TailwindCSS 4.0 — Utility-first CSS processed via Vite 7. Custom
                    background animations (shooting stars) are hardware-accelerated via raw CSS
                    (<code>transform</code>/<code>opacity</code>).</li>
                <li><strong>Frontend Logic:</strong> Vanilla JavaScript + Axios + Laravel Echo + Pusher-JS 8.5 (client).
                    Avoided heavy reactive frameworks (React/Vue) to keep the bundle size extremely small and rendering
                    instantaneous.</li>
            </ul>
        </div>

        <div class="canvas-section">
            <h3>📂 Detailed Folder Structure & Context</h3>
            <p style="margin-bottom:8px;">If you are replicating this architecture, pay attention to these core
                domains:</p>
            <ul>
                <li><strong><code>app/Events/</code></strong> — The real-time heartbeat.
                    <br><code style="background:rgba(255,255,255,0.1)">MessageCreated</code>: Broadcasts new chat
                    messages.
                    <br><code style="background:rgba(255,255,255,0.1)">MessageLiked</code>: Broadcasts emoji reaction
                    increments.
                    <br><code style="background:rgba(255,255,255,0.1)">OnlineUsersUpdated</code> & <code
                        style="background:rgba(255,255,255,0.1)">AnnouncementBroadcasted</code>: Server-to-client UI
                    administrative updates.
                </li>
                <li><strong><code>app/Http/Controllers/</code></strong> — API entry points.
                    <br><code style="background:rgba(255,255,255,0.1)">ChatController</code>: Renders the initial Blade
                    view and fetches the initial DB state (latest 100 messages).
                    <br><code style="background:rgba(255,255,255,0.1)">MessageController</code>: Validates POST
                    requests, handles persistence, and dispatches events.
                    <br><code style="background:rgba(255,255,255,0.1)">AdminController</code>: Protected endpoints for
                    global system announcements and chat clearing.
                </li>
                <li><strong><code>app/Services/</code></strong> — Extracted business logic layer.
                    <br><code style="background:rgba(255,255,255,0.1)">XssProtectionService</code>: Strips malicious
                    script tags from raw HTML input.
                    <br><code style="background:rgba(255,255,255,0.1)">BadWordFilterService</code>: Regex-based
                    profanity scrubber.
                    <br><code style="background:rgba(255,255,255,0.1)">AnonymousClientService</code>: Manages the UUID
                    cookie assignment lifecycle.
                </li>
                <li><strong><code>resources/views/chat.blade.php</code></strong> — The monolithic frontend. Contains the
                    HTML structure, Blade directives, bottom canvas drawer system, and manual CSS for
                    animations/drawers.</li>
                <li><strong><code>resources/js/app.js</code></strong> — Subscribes to Echo channels, dynamically
                    generates DOM nodes for new messages (via <code>document.createElement</code>), manages Axios form
                    submissions, and controls the custom Toast/Announcement UI logic.</li>
            </ul>
        </div>

        <div class="canvas-section">
            <h3>🔒 Deep-Dive Security Measures</h3>
            <ul>
                <li><strong>XSS & Injection:</strong> Blade's <code>@{{  }}</code> echo statements safely
                    encode output on load. For real-time JS injection, input is sanitised backend-side before
                    broadcasting, and inserted using Vanilla JS <code>textContent</code> rather than
                    <code>innerHTML</code> on the client to strictly prevent DOM-based XSS.
                </li>
                <li><strong>Rate Limiting (Throttle):</strong> Implemented at the route middleware level. Limits IPs to
                    40 messages per minute. Prevents WebSocket spam and database DoS attacks.</li>
                <li><strong>CSRF Token:</strong> Every Axios POST request reads the
                    <code>meta[name="csrf-token"]</code> tag and includes it in headers.</li>
                <li><strong>Data Retention & Privacy:</strong> A scheduled task (or listener) prunes the
                    <code>messages</code> table to keep only the latest 100 entries. Old data is permanently destroyed
                    to protect privacy.
                </li>
            </ul>
        </div>

        <div class="canvas-section">
            <h3>✨ UI/UX & Micro-interactions</h3>
            <ul>
                <li><strong>Hardware-Accelerated Background:</strong> The shooting star effect uses <code>transform:
                        translateY()</code> and <code>opacity</code> alongside <code>will-change</code>, ensuring the
                    browser leverages the GPU to render it at a smooth 60fps without lagging the main JS thread.</li>
                <li><strong>Dynamic Layouts:</strong> Alternating left/right chat bubbles based on an even/odd index
                    logic. "Popular" messages (≥ 3 reactions) dynamically swap CSS classes to glow purple.</li>
                <li><strong>Custom Drawer System:</strong> Built entirely with Vanilla JS and CSS transitions (avoiding
                    heavy libraries like Bootstrap/MUI). Uses <code>transform: translateY(100%)</code> for smooth
                    slide-up animations.</li>
            </ul>
        </div>
    </div>

    {{-- ═══════ DRAWER 2 — SUGGESTION BOX ═══════ --}}
    <div id="canvas-suggestion" class="bottom-canvas" role="dialog" aria-label="Suggestion Box">
        <div class="canvas-handle"></div>
        <p class="canvas-title">💬 Suggestion Box</p>
        <div class="canvas-section">
            <p style="margin-bottom:14px;">Got an idea or feedback? Drop it here. We may or may not read it. 😄</p>
            <form class="suggestion-form" id="suggestion-form" onsubmit="handleSuggestion(event)">
                <input type="text" placeholder="Your name (optional)" maxlength="60" />
                <textarea rows="4" placeholder="Write your suggestion..." maxlength="500" required></textarea>
                <button type="submit">Send Suggestion →</button>
            </form>
        </div>
    </div>

    {{-- ═══════ DRAWER 3 — DEVELOPER INFO ═══════ --}}
    <div id="canvas-dev" class="bottom-canvas" role="dialog" aria-label="Developer Info">
        <div class="canvas-handle"></div>
        <p class="canvas-title">👨‍💻 Developer</p>
        <div style="display:flex; align-items:center; gap:14px; margin-bottom:16px;">
            <div class="dev-avatar">SV</div>
            <div>
                <p style="font-size:15px; font-weight:700; color:#f1f5f9; margin:0;">Shashi Verma</p>
                <p style="font-size:12px; color:#94a3b8; margin:2px 0 0;">PHP · Laravel Developer</p>
                <p style="font-size:11.5px; color:#7c3aed; margin:3px 0 0; font-weight:600;">2+ Years Experience</p>
            </div>
        </div>
        <div class="canvas-section">
            <h3>🛠️ Skills</h3>
            <ul>
                <li>PHP · Laravel · MySQL · REST APIs</li>
                <li>WebSockets · Laravel Reverb · Queues & Events</li>
                <li>HTML · CSS · TailwindCSS · JavaScript</li>
                <li>XAMPP · Git · Vite · Blade Templating</li>
            </ul>
        </div>
        <div class="canvas-section">
            <h3>🚀 This Project</h3>
            <p>Built <strong>ChalGlobalIndia</strong> — an anonymous real-time chat app — from scratch. Implemented
                WebSocket broadcasting, rate limiting, XSS/bad-word filtering, emoji reactions, and admin controls
                independently.</p>
        </div>
        <div class="canvas-section">
            <h3>📌 Motto</h3>
            <p style="font-style:italic; color:#c084fc;">"Write clean code. Ship fast. Break nothing."</p>
        </div>
    </div>

    {{-- ═══════ FLOATING LINK BAR ═══════ --}}
    <nav class="floating-links" aria-label="Info links">
        <span class="floating-link" onclick="openCanvas('canvas-docs')">docs</span>
        <span class="floating-link" onclick="openCanvas('canvas-suggestion')">suggestion</span>
        <span class="floating-link" onclick="openCanvas('canvas-dev')">developer</span>
    </nav>

    <script>
        function openCanvas(id) {
            closeAllCanvas();
            document.getElementById(id).classList.add('open');
            document.getElementById('canvas-overlay').classList.add('open');
        }

        function closeAllCanvas() {
            document.querySelectorAll('.bottom-canvas').forEach(el => el.classList.remove('open'));
            document.getElementById('canvas-overlay').classList.remove('open');
        }
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') closeAllCanvas();
        });

        function handleSuggestion(e) {
            e.preventDefault();
            e.target.reset();
            closeAllCanvas();
            window.showToast('es suggestion ki batti bana ke apne andar daal lo..');
        }
    </script>

</body>

</html>
