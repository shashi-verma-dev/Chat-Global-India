# 1. Project Overview

**Chat Global India 🇮🇳** is a no-login, anonymous, real-time global chat application. Any visitor who opens the app is instantly assigned a unique anonymous identity (e.g. "Guest 428") via a browser cookie — no registration required. Users can send messages, react to them with emojis, and see live online user counts — all powered by WebSockets via Laravel Reverb. The admin can clear the chat or broadcast site-wide announcements via secret API endpoints.

---

# 2. Tech Stack

| Layer | Technology | Version |
|---|---|---|
| Backend Framework | Laravel | ^13.0 |
| PHP Runtime | PHP | ^8.3 |
| WebSocket Server | Laravel Reverb | ^1.10 |
| HTTP Client (PHP) | Predis | ^3.5 |
| Frontend Styling | Tailwind CSS | ^4.0 |
| Frontend Build Tool | Vite | ^7.0 |
| Real-time Client | Laravel Echo + Pusher-js | ^2.3 / ^8.5 |
| Queue & Cache Backend | Redis | - |
| Database | MySQL | - |
| Templating | Laravel Blade | - |

---

# 3. Project Folder Structure

## Top-Level Overview

```
ChatGlobalIndia/
├── app/                  ← Core application logic
│   ├── Events/           ← Broadcastable WebSocket events
│   ├── Http/
│   │   ├── Controllers/  ← Request handlers
│   │   └── Requests/     ← Form validation classes
│   ├── Models/           ← Eloquent database models
│   ├── Providers/        ← Service providers
│   └── Services/         ← Reusable business logic services
├── resources/            ← Frontend source files
│   ├── css/              ← Tailwind CSS entry point
│   ├── js/               ← JavaScript (Echo, Axios, DOM logic)
│   └── views/            ← Blade HTML templates
├── routes/               ← Web, API, WebSocket channel routes
├── database/             ← Migrations, seeders, factories
├── config/               ← Laravel config files
├── storage/              ← Logs, compiled views, file uploads
├── public/               ← Web root (index.php, Vite hot file)
├── .env                  ← Environment variables
├── composer.json         ← PHP dependency manifest
├── package.json          ← Node dependency manifest
└── vite.config.js        ← Vite bundler configuration
```

---

## `app/` — Detailed Breakdown

### `app/Events/`

These are the **WebSocket broadcast events**. Every class here implements `ShouldBroadcast`, meaning when fired they are pushed onto the Redis queue and eventually delivered to all connected browser clients via Reverb on the `global-chat` public channel.

---

#### `MessageCreated.php`
- **Broadcast name:** `message.created`
- **Channel:** `global-chat` (public)
- **Purpose:** Fired by `MessageController@store` immediately after a new chat message is saved to the database.
- **Payload sent to clients:** `id`, `guest_name`, `message`, `reactions_count`, `created_at` (formatted as `h:i A`)
- **Frontend effect:** The JavaScript listener in `app.js` dynamically builds a new message bubble and injects it into the chat area DOM without a page reload.

---

#### `MessageLiked.php`
- **Broadcast name:** `message.liked`
- **Channel:** `global-chat` (public)
- **Purpose:** Fired by `MessageLikeController@store` after a user reacts to a message with an emoji.
- **Payload sent to clients:** `message_id`, `reactions_count` (updated totals per emoji), `is_popular` (true if total reactions > 3)
- **Frontend effect:** The JS listener updates emoji reaction counters live. If `is_popular` is true, the message bubble turns purple for all users in real time.

---

#### `OnlineUsersUpdated.php`
- **Broadcast name:** `online-users.updated`
- **Channel:** `global-chat` (public)
- **Purpose:** Fired by `PresenceService` whenever a user joins or leaves (detected via heartbeat expiry).
- **Payload sent to clients:** `count` (integer — current number of online sessions)
- **Frontend effect:** The JS listener updates the "X Online" badge displayed in the chat header.

---

#### `ChatCleared.php`
- **Broadcast name:** `chat.cleared`
- **Channel:** `global-chat` (public)
- **Purpose:** Fired by `AdminController@clearChat` after an admin wipes all messages from the database.
- **Payload sent to clients:** `cleared_by` (e.g., `"admin"`), `cleared_at` (ISO-8601 timestamp)
- **Frontend effect:** JS clears the entire messages area DOM instantly and shows a toast notification: "Chat has been cleared by admin."

---

#### `AnnouncementBroadcasted.php`
- **Broadcast name:** `announcement.broadcasted`
- **Channel:** `global-chat` (public)
- **Purpose:** Fired by `AdminController@announcement` to push a global popup message to all connected users.
- **Payload sent to clients:** `title`, `body`, `timeout` (seconds before the popup auto-dismisses, default 20s)
- **Frontend effect:** A full-screen modal overlay with a progress bar is shown to every user simultaneously.

---

### `app/Http/Controllers/`

These classes handle incoming HTTP requests and orchestrate the flow between services, models, and events.

---

#### `Controller.php`
- The base controller class. All other controllers extend this. Currently empty (boilerplate).

---

#### `ChatController.php`
- **Routes handled:**
  - `GET /` → `index()` — Renders the main chat page.
  - `POST /heartbeat` → `heartbeat()` — Called by the frontend every 30 seconds to keep a user marked as "online."
- **`index()`:**
  - Fetches the latest 100 messages from MySQL, reverses them to chronological order, and passes them to the `chat.blade.php` view.
  - Reads the `online_users_count` from the Redis cache to pass to the view.
  - Gets or creates a unique `client_id` cookie for the anonymous user via `AnonymousClientService`.
- **`heartbeat()`:**
  - Calls `PresenceService::join($clientId)` to refresh the user's timestamp in the Redis presence map.
  - Returns the current live online count as JSON.

---

#### `MessageController.php`
- **Routes handled:** `POST /messages` → `store()`
- **Purpose:** Handles all new message submissions. This is the most important controller in the app.
- **Full processing pipeline inside `store()`:**
  1. **Identity** — Gets or creates the anonymous `client_id` from the cookie via `AnonymousClientService`.
  2. **XSS Check** — `XssProtectionService::containsXssPattern()` scans for script tags, `javascript:` URIs, event handlers (`onerror=`, etc.). If found, returns HTTP 422 with a popup message.
  3. **Rate Limit** — `RateLimitService::allowMessage()` checks if the client has sent more than **40 messages in 60 seconds**. If so, returns HTTP 429 with a popup message.
  4. **Sanitise** — `XssProtectionService::sanitise()` strips tags and HTML-encodes the message. Then `BadWordFilterService::filter()` replaces any banned words with `****`.
  5. **Persist** — Saves the clean message to the `messages` MySQL table with the client UUID and generated guest name.
  6. **Broadcast** — Fires `MessageCreated` event (queued via Redis → processed by queue worker → sent to Reverb → pushed to all browsers).
  7. **Trim** — Calls `trimMessages()` which deletes any messages older than the most recent 100 to keep the database lean.

---

#### `MessageLikeController.php`
- **Routes handled:** `POST /messages/{message}/like` → `store()`
- **Purpose:** Handles emoji reactions on individual messages.
- **Processing pipeline:**
  1. Gets the anonymous `client_id`.
  2. Checks the `message_likes` table for a duplicate reaction (same `message_id` + `client_id` + `type`). If it exists, returns HTTP 422 with a popup.
  3. Creates a new `MessageLike` record in the database.
  4. Updates the `reactions_count` JSON column on the `Message` model.
  5. Fires `MessageLiked` event (broadcast via queue → Reverb → all browsers).
  6. Returns the updated reaction counts and popularity flag as JSON.

---

#### `AdminController.php`
- **Routes handled:**
  - `POST /admin/chat/clear` → `clearChat()` — Requires `code` field matching `ADMIN_SECRET_CODE` from `.env`.
  - `POST /admin/announcement` → `announcement()` — Requires `code` and `message` fields.
- **`clearChat()`:**
  - Validates the secret code, deletes all rows from the `messages` table, and fires `ChatCleared` event.
- **`announcement()`:**
  - Validates the secret code and message body, then fires `AnnouncementBroadcasted` event with a 20-second auto-dismiss timeout.

---

### `app/Http/Requests/`

#### `StoreMessageRequest.php`
- A **Form Request** class dedicated to validating new message POST data before it reaches `MessageController`.
- **Rules:** `message` field is required, must be a string, minimum 1 character, maximum **500 characters**.
- All users are authorized to submit (no login required).
- Provides friendly custom validation error messages.

---

### `app/Models/`

#### `Message.php`
- **Table:** `messages`
- **Fillable fields:** `client_id`, `guest_name`, `message`, `reactions_count`
- **Casts:** `reactions_count` is automatically cast to/from a PHP `array` (stored as JSON in MySQL).
- **Relationships:** Has many `MessageLike` records via `likes()`.
- **`isPopular(): bool`** — Returns `true` if the total of all emoji reactions (`array_sum($reactions_count)`) exceeds **3**. Used by `MessageLikeController` and `MessageLiked` event to trigger the purple bubble effect on the frontend.

---

#### `MessageLike.php`
- **Table:** `message_likes`
- **Fillable fields:** `message_id`, `client_id`, `type` (the emoji string, e.g. `"👍"`)
- **Relationships:** Belongs to a `Message` via `message()`.
- **Purpose:** Records individual emoji reactions. One client can only react with the same emoji once per message (enforced in `MessageLikeController`).

---

#### `User.php`
- Standard Laravel auth user model. Exists as boilerplate infrastructure.
- **Note:** This app is currently **anonymous-only**. There is no login system in active use. This model is a placeholder for future authentication features if needed.

---

### `app/Providers/`

#### `AppServiceProvider.php`
- The primary service provider that runs at application boot. Currently contains minimal boilerplate (no custom bindings registered yet).

---

### `app/Services/`

These are reusable, injectable classes that encapsulate focused business logic, keeping controllers thin and testable.

---

#### `AnonymousClientService.php`
- **Purpose:** Manages the anonymous identity of every visitor using a browser cookie named `chat_client_id`.
- **`getOrCreateClientId(): string`** — Reads the UUID from the cookie. If missing or invalid, generates a new `Str::uuid()` and queues it to be set in the HTTP response. The cookie lasts **1 year** (525,600 minutes) so the same browser always maps to the same identity.
- **`buildGuestName(string $clientId): string`** — Derives a deterministic display name (e.g. "Guest 428") from the first 6 hex characters of the UUID. The number is always in the range 100–999 and is consistent across all their messages, giving a sense of identity without any account.

---

#### `PresenceService.php`
- **Purpose:** Tracks which anonymous sessions are currently online using the Redis cache.
- **Storage:** Keeps a `presence:active_sessions` key in Redis — a map of `sessionId → last-seen Unix timestamp`.
- **TTL:** Sessions are considered active for **120 seconds** after their last heartbeat. The frontend calls `/heartbeat` every 30 seconds, so there is a comfortable 90-second buffer.
- **`join(string $sessionId)`** — Updates the session's timestamp, saves the map back to Redis, and immediately fires `OnlineUsersUpdated` to broadcast the new count.
- **`leave(string $sessionId)`** — Removes the session from the map and broadcasts the updated count.
- **`count(): int`** — Prunes stale sessions (older than 120 seconds) then returns the live count.
- **`prune(): int`** — Can be called from a scheduled command to keep the cache clean without relying on disconnect events.

---

#### `RateLimitService.php`
- **Purpose:** Prevents message spam using Laravel's built-in `RateLimiter`.
- **Limit:** **40 messages per 60 seconds** per anonymous client UUID.
- **`allowMessage(string $clientId): bool`** — Returns `true` if the client is under the limit, increments their counter, and returns `false` (HTTP 429) if the limit is exceeded.
- **Rate key format:** `rate:msg:{clientId}` — stored in Redis.

---

#### `BadWordFilterService.php`
- **Purpose:** Scans and censors banned words in message text before it is stored in the database.
- **`filter(string $text): string`** — Uses whole-word regex matching (`\b...\b` with `iu` flags for case-insensitive Unicode support) to replace each bad word with `****`. Does **not** block partial matches (e.g., the word "class" would not be caught by a rule for "ass").
- **`containsBadWord(string $text): bool`** — Returns `true` if any banned word is found.
- The banned word list lives directly in the service class as a private array. For a persistent list, load from the database in the constructor.

---

#### `XssProtectionService.php`
- **Purpose:** Provides two layers of XSS defence for raw user input.
- **`containsXssPattern(string $input): bool`** — A heuristic scanner that checks for known dangerous patterns: `<script>`, `javascript:`, event handlers (`onerror=`, `onclick=`), `<iframe>`, `<object>`, `<embed>`, CSS `expression()`, `vbscript:`, and `data:text/html` URIs. Used for hard-blocking before storage.
- **`sanitise(string $input): string`** — A defence-in-depth pipeline: (1) `strip_tags()` removes all HTML, (2) `htmlspecialchars()` encodes `&`, `<`, `>`, `"`, `'`, (3) `trim()` strips whitespace. This ensures the stored value is safe even if the heuristic check is ever bypassed.

---

## `resources/` — Detailed Breakdown

### `resources/css/app.css`
- The Tailwind CSS entry point. Tailwind 4's `@import "tailwindcss"` directive processes this file via the `@tailwindcss/vite` plugin during `npm run dev` / `npm run build`.

---

### `resources/js/`

#### `bootstrap.js`
- Sets up the global `axios` instance and configures the CSRF token header (`X-XSRF-TOKEN`) to be sent with every request automatically. This is standard Laravel boilerplate.

#### `echo.js`
- Initialises the global `window.Echo` instance using **Laravel Echo** with the **Reverb broadcaster**.
- Reads connection settings from Vite env variables (`VITE_REVERB_APP_KEY`, `VITE_REVERB_HOST`, `VITE_REVERB_PORT`, `VITE_REVERB_SCHEME`) which are injected from `.env` at build time.
- Registers `window.Pusher = Pusher` (required by the Echo Pusher driver under the hood, even when using Reverb).

#### `app.js`
- The main JavaScript entry point. Imports `./bootstrap` (which also pulls in `echo.js`).
- **`playNotificationSound()`** — Uses the Web Audio API to generate a short synthetic ping sound whenever a new message arrives.
- **`showToast(message)`** — Creates a floating toast notification element and injects it into `#notifications-aside`. Auto-removes after 10 seconds.
- **Message Form Submit** — Intercepts `#message-form` submit, sends `POST /messages` via Axios, disables the input during request, shows toast on error (XSS/rate limit popups from the server).
- **Reaction Form Submit** — Uses event delegation on `#messages-area` to catch clicks on `.reaction-form` elements, sends `POST /messages/{id}/like` via Axios.
- **Heartbeat** — Calls `POST /heartbeat` immediately on page load and then on a `setInterval` every **30 seconds** to keep the user in the online presence tracker.
- **`Echo.channel('global-chat')`** — Subscribes to 5 real-time events:
  - `.message.created` → Builds and appends a new message bubble to the DOM. Limits the DOM to 100 visible messages (removes the oldest if exceeded).
  - `.message.liked` → Updates emoji reaction counts on the target message. If `is_popular` is true, switches the message bubble to a purple theme.
  - `.online-users.updated` → Updates the `#online-users-count` element text.
  - `.chat.cleared` → Clears `messagesArea.innerHTML` and shows a toast.
  - `.announcement.broadcasted` → Renders a full-screen modal overlay with a progress bar that auto-dismisses.

---

### `resources/views/`

#### `welcome.blade.php`
- The landing/welcome page (72KB). Contains the visual entry point of the application before the user enters the chat.

#### `chat.blade.php`
- The main real-time chat interface (35KB). Receives `$messages`, `$onlineUsers`, and `$clientId` from `ChatController@index`.
- Renders the last 100 messages from the server on initial page load (SSR), alternating them left/right.
- Includes the message input form `#message-form`, the emoji reaction UI, the online user count badge, and the notifications aside container.
- Loads compiled Vite assets (`@vite(['resources/css/app.css', 'resources/js/app.js'])`).

---

## `.env` — Key Variables Explained

```env
# The public URL of the Laravel backend. Used for internal redirects.
APP_URL=http://192.168.1.17:8000

# Tell Laravel to use Reverb for broadcasting (not Pusher cloud).
BROADCAST_CONNECTION=reverb

# Use Redis for the background job queue (broadcasting is queued).
QUEUE_CONNECTION=redis

# Use Redis for application caching (presence tracking, rate limiting).
CACHE_STORE=redis

# --- Reverb Server Config ---
# CRITICAL: This must match the IP your Reverb server is actually running on.
# The backend (queue worker) POSTs events to this IP:PORT to broadcast them.
REVERB_APP_ID=your_id
REVERB_APP_KEY=your_key
REVERB_APP_SECRET=your_secret
REVERB_HOST="192.168.1.17"   # ← Must be your LAN IP for mobile device testing
REVERB_PORT=8080
REVERB_SCHEME=http

# --- Vite / Frontend Config ---
# These are injected into the compiled JS bundle by Vite at build time.
# The browser uses these to know where to open the WebSocket connection.
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"

# Admin secret — required in POST body for /admin/* routes.
ADMIN_SECRET_CODE=your_admin_secret
```

---

# 4. Complete Chat Message Request Lifecycle

This section traces a single chat message from the moment the user presses "Send" all the way to it appearing on every other user's screen.

```
[Browser]                [Laravel]              [Redis]        [Reverb]       [Other Browsers]
   │                         │                     │               │                │
   │── POST /messages ───────▶                     │               │                │
   │   { message: "Hello" }  │                     │               │                │
   │                         │ 1. Validate          │               │                │
   │                         │    StoreMessageRequest              │                │
   │                         │ 2. XSS check         │               │                │
   │                         │    XssProtectionService             │                │
   │                         │ 3. Rate limit        │               │                │
   │                         │    RateLimitService  │               │                │
   │                         │ 4. Sanitise + Filter │               │                │
   │                         │    XssProtection + BadWordFilter     │                │
   │                         │ 5. Message::create() │               │                │
   │                         │    → saved to MySQL  │               │                │
   │                         │ 6. broadcast(new MessageCreated())  │                │
   │◀── 200 { ok: true } ────│    → pushed to ──────▶              │                │
   │                         │       Redis Queue    │               │                │
   │                         │ 7. trimMessages()    │               │                │
   │                         │                      │               │                │
   │                         │                      │ queue:work    │                │
   │                         │                      │ picks up job  │                │
   │                         │                      │───────────────▶               │
   │                         │                      │  HTTP POST    │ WebSocket push │
   │                         │                      │  to Reverb    │───────────────▶│
   │                         │                      │               │  .message.created event
   │                         │                      │               │                │
   │                         │                      │               │                │ Echo listener fires
   │                         │                      │               │                │ → build DOM bubble
   │                         │                      │               │                │ → scrollToBottom()
   │                         │                      │               │                │ → playNotificationSound()
```

### Step-by-step 

1. **User presses Send** → `app.js` intercepts the form, disables the input, and calls `POST /messages` via Axios.
2. **`StoreMessageRequest` validates** the raw input (required, string, 1–500 chars).
3. **XSS check** (`XssProtectionService::containsXssPattern`) — blocks script injection attempts immediately with a 422 popup.
4. **Rate limit** (`RateLimitService::allowMessage`) — blocks if user has sent > 40 messages in 60 seconds with a 429 popup.
5. **Sanitise + Filter** — `XssProtectionService::sanitise()` strips all HTML tags and encodes special characters. `BadWordFilterService::filter()` replaces banned words with `****`.
6. **`Message::create()`** — the clean message is saved to MySQL. The `guest_name` is deterministically generated from the UUID cookie (`"Guest 428"`).
7. **`broadcast(new MessageCreated(...))`** — serializes the event and pushes it onto the **Redis queue**. The HTTP request returns `200 { ok: true }` immediately — the user doesn't wait for broadcasting.
8. **Queue worker** (`php artisan queue:work`) picks up the `BroadcastEvent` job from Redis.
9. **Worker sends HTTP POST to Reverb** — at `http://REVERB_HOST:REVERB_PORT/apps/{id}/events`. This is why `REVERB_HOST` in `.env` must be the correct IP.
10. **Reverb receives the event** and identifies subscribers to the `global-chat` channel.
11. **Reverb pushes via WebSocket** — the `message.created` payload is instantly delivered to all connected browsers (including the sender's own browser).
12. **`Echo.channel('global-chat').listen('.message.created', ...)`** fires in every browser. JavaScript builds the HTML message bubble with the timestamp, guest name, and emoji reaction buttons, appends it to `#messages-area`, scrolls to the bottom, and plays the notification sound.

---

# 5. How to Run the Project

### First-time Setup

```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Copy .env and generate app key
cp .env.example .env
php artisan key:generate

# Run database migrations
php artisan migrate
```

Update `.env` with your database credentials and your local IP address for Reverb.

---

### Running (requires 4 terminals)

**Terminal 1 — Laravel Backend**
```bash
php artisan serve --host=192.168.1.17 --port=8000
```

**Terminal 2 — Vite (Frontend Assets)**
```bash
npm run dev -- --host 192.168.1.17
```
> If your mobile device shows a broken UI, either open port **5173** in Windows Firewall, or use `npm run build` instead (no hot reload but no firewall issue).

**Terminal 3 — Laravel Reverb (WebSockets)**
```bash
php artisan reverb:start --host=192.168.1.17 --port=8080
```

**Terminal 4 — Queue Worker (required for broadcasting)**
```bash
php artisan queue:work
```
> Without this, messages are saved to the database but **never broadcast** to other users.

---

### Access on Mobile

Once all four processes are running, open on your mobile device (same Wi-Fi):

- **App:** `http://192.168.1.17:8000`

Ensure `REVERB_HOST="192.168.1.17"` is set in your `.env`. If you change it, restart all four processes.

---

### Admin API Endpoints

```bash
# Clear all chat messages (broadcasts to all users)
curl -X POST http://192.168.1.17:8000/admin/chat/clear \
     -d "code=your_admin_secret"

# Send a global popup announcement (auto-dismisses after 20s)
curl -X POST http://192.168.1.17:8000/admin/announcement \
     -d "code=your_admin_secret&message=Server going down in 5 minutes"
```

---

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
