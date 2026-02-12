<?php
require_once 'includes/require_login.php';
require_once 'includes/helpers.php';
// Technician chat UI - renamed from previous chat.php

// Load server-side chat sessions from database (if available) and expose to JS
$serverSessions = [];
$serverSessionsDebug = '';
if (is_readable(__DIR__ . '/includes/db.php')){
    try{
        require_once __DIR__ . '/includes/db.php';
        $stmt = $pdo->query("SELECT * FROM chat_sessions ORDER BY created_at DESC LIMIT 1000");
        $rows = $stmt->fetchAll();
        foreach($rows as $r){
            if (!empty($r['auth_user']) && is_string($r['auth_user'])) $r['auth_user'] = json_decode($r['auth_user'], true);
            $serverSessions[$r['session_id']] = $r;
        }
    }catch(Exception $e){
        $serverSessions = [];
        $serverSessionsDebug = 'DB error: ' . $e->getMessage();
    }
} else {
    $serverSessionsDebug = 'DB include not readable: ' . __DIR__ . '/includes/db.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ITS Technician Chat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="assets/img/favicon.ico" type="image/x-icon">
    <link rel="icon" type="image/png" href="assets/img/its_logo.png">
    <link rel="apple-touch-icon" href="assets/img/its_logo.png">
    <style>
        /* Use Bootstrap grid for layout; allow the wrapper to fill available width */
        .tech-wrapper{max-width:none;width:100%;margin:18px 0;padding-right:8px}
        /* let Bootstrap column control the sidebar width; avoid fixed floats */
        .tech-sidebar{width:100%;float:none;margin-right:0}
        .conversation-panel{overflow:visible;min-width:0;width:100%}
        .session-item{padding:12px;border-radius:8px;cursor:pointer;border:1px solid var(--border-color);margin:8px 6px;background:var(--secondary-bg);color:var(--text-color)}
        /* Session list items: stretch to fill the sidebar card for a clean list appearance */
        .tech-sidebar .session-list{display:flex;flex-direction:column;align-items:stretch;padding:0 12px 12px}
        .tech-sidebar .session-list .session-item{width:100%;max-width:none;box-sizing:border-box;margin:8px 0}
        .session-item:hover{box-shadow:0 6px 18px rgba(0,0,0,0.06)}
        .session-item.active{outline:2px solid rgba(0,150,136,0.06);box-shadow:0 8px 22px rgba(0,0,0,0.06)}
        .session-title{font-weight:600;margin-bottom:4px;color:var(--text-color)}
        .session-meta{font-size:12px;color:var(--text-secondary)}
        .message{padding:10px;border-radius:12px;margin-bottom:8px;white-space:pre-wrap}
        .message.sent{text-align:right;background:var(--accent-color);color:#021018;margin-left:auto}
        .message.received{text-align:left;background:var(--secondary-bg)}
        /* Ticket styling for technician view */
        .ticket-card{border-left:4px solid var(--accent-color);padding:16px;border-radius:8px;background:var(--secondary-bg);box-shadow:0 6px 18px rgba(2,16,24,0.04)}
        .ticket-fields{display:grid;grid-template-columns:1fr 1fr;gap:8px 20px;align-items:start}
        .ticket-fields .tf{padding:6px 0;border-bottom:1px solid rgba(0,0,0,0.04)}
        .ticket-fields .tf.full{grid-column:1/-1;border-bottom:none}
        .ticket-fields .label{font-weight:700;color:var(--text-secondary);font-size:13px;margin-bottom:4px}
        .ticket-fields .value{font-weight:600;color:var(--text-color);font-size:14px;word-break:break-word}
        .ticket-fields .value.session-id{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,'Roboto Mono',monospace;color:var(--accent-color)}
        .ticket-fields .value.concern{white-space:pre-wrap;color:var(--text-muted);font-weight:500}
        @media(max-width:576px){.ticket-fields{grid-template-columns:1fr}}
        /* Emphasize Room/Department and Concern in conversation details */
        .ticket-fields .tf.prominent .label{font-weight:800;color:var(--text-secondary);font-size:13px}
        .ticket-fields .tf.prominent .value{font-weight:800;color:var(--text-color);font-size:16px}
        .ticket-fields .value.concern{color:var(--text-color);font-size:15px;font-weight:700}
        /* Compact session notice and badge styling */
        #sessionNoticeBanner{font-size:13px;padding:6px 10px;margin:8px 0;line-height:1.1;border-radius:8px}
        .session-badge .badge{font-size:12px;padding:.25rem .45rem}

        /* Conversation header: improve button responsiveness on small screens */
        .conversation-header{gap:8px}
        .conversation-header > div:last-child{display:flex;gap:8px;align-items:center}
        .conversation-header .btn{padding:.35rem .6rem;font-size:14px;white-space:nowrap}
        .conversation-header .btn svg{height:1em;width:1em}

        /* Make header action buttons subtle gray instead of bright white
           and restore hover/focus/active affordances */
        .conversation-header .btn.btn-outline-light,
        .conversation-header .btn.btn-light {
            background: rgba(0,0,0,0.04);
            color: var(--text-color) !important;
            border-color: rgba(0,0,0,0.06) !important;
            box-shadow: none !important;
            transition: background .12s ease, transform .06s ease;
        }
        .conversation-header .btn.btn-outline-light:hover,
        .conversation-header .btn.btn-light:hover,
        .conversation-header .btn.btn-outline-light:focus,
        .conversation-header .btn.btn-light:focus,
        .conversation-header .btn.btn-outline-light:active,
        .conversation-header .btn.btn-light:active {
            background: rgba(0,0,0,0.08);
            border-color: rgba(0,0,0,0.1) !important;
            transform: translateY(-1px);
        }

        @media (max-width: 576px){
            .conversation-header{flex-direction:column;align-items:stretch;gap:6px;padding:8px}
            .conversation-header > div:first-child{order:1}
            .conversation-header > div:last-child{order:2;justify-content:flex-end;flex-wrap:wrap}
            .conversation-header .btn{padding:.3rem .45rem;font-size:13px}
            /* Make the back button compact and keep it visible at the left on narrow screens */
            #backToSessions{flex:0 0 auto;margin-right:auto}
            /* Reduce visual weight of secondary actions on mobile */
            .conversation-header .btn.btn-outline-light{padding:.28rem .45rem;font-size:13px}
        }

        /* Make conversation panel a column layout so composer sticks to bottom */
        .conversation-panel{display:flex;flex-direction:column;height:100%;min-height:360px;box-sizing:border-box}
        .conversation-panel .conversation-messages{flex:1 1 auto;overflow:auto}
        .conversation-panel .conversation-composer{justify-content:flex-end;align-items:center}
        .conversation-panel .conversation-composer > .d-flex{margin-left:auto}

        /* Ensure the conversation card uses the same background as the sessions card and is not transparent
           The conversation panel element is itself a card; match that and any nested card variants. */
        .card.conversation-panel,
        .conversation-panel.card,
        .conversation-panel > .card {
            background: var(--secondary-bg) !important;
            color: var(--text-color) !important;
            box-shadow: 0 6px 18px rgba(2,16,24,0.04) !important;
            border: 1px solid rgba(0,0,0,0.04) !important;
        }

        /* Messenger-mode view toggles
           - .sessions-only: show the sessions list, hide the conversation panel
           - .conversation-only: hide the sessions list, make conversation full width
           These rules allow JS to switch the UI between a sessions-first list and a conversation-only view. */
        .tech-wrapper.sessions-only .col-md-8,
        .tech-wrapper.sessions-only .conversation-panel { display: none !important; }
        /* Expand the sessions column to use full available width and ensure it overrides the desktop fixed-width rule */
            .tech-wrapper.sessions-only .col-md-4,
            .tech-wrapper.sessions-only .row > .col-md-4 {
                    display: block !important;
                    flex: 1 1 0 !important;
                    -webkit-flex: 1 1 0 !important;
                    -ms-flex: 1 1 0 !important;
                    flex-basis: 0 !important;
                    max-width: none !important;
                    width: 100% !important;
                }
                .tech-wrapper.sessions-only .tech-sidebar,
                .tech-wrapper.sessions-only .col-md-4 .tech-sidebar { width: 100% !important; max-width: none !important; }
                /* Force the card inside the column to stretch */
                .tech-wrapper.sessions-only .col-md-4 .card { width: 100% !important; }
                /* Prevent horizontal overflow inside the sessions list */
                .tech-wrapper.sessions-only .session-list { overflow-x: hidden !important; }
                /* Override inline max-height on the session list so it can grow on mobile */
                @media (max-width: 768px) {
                    .tech-sidebar .session-list, .tech-wrapper.sessions-only .session-list { max-height: none !important; overflow: visible !important; }
                }

        .tech-wrapper.conversation-only .col-md-4 { display: none !important; }
        .tech-wrapper.conversation-only .col-md-4 { display: none !important; }
        /* Make the conversation column occupy the full remaining main-content width */
        .tech-wrapper.conversation-only .row > .col-md-8 {
            display: block !important;
            flex: 1 1 0 !important;
            -webkit-flex: 1 1 0 !important;
            -ms-flex: 1 1 0 !important;
            flex-basis: 0 !important;
            max-width: none !important;
            width: 100% !important;
        }
        .tech-wrapper.conversation-only .conversation-panel {
            display: block !important;
            width: 100% !important;
            max-width: none !important;
            overflow-x: hidden !important;
        }
        .tech-wrapper.conversation-only .col-md-8 .card { width: 100% !important; }
        /* Stronger overrides to ensure conversation column fills available space */
        .tech-wrapper.conversation-only .row > .col-md-8,
        .tech-wrapper.conversation-only .row > .col-md-8 .conversation-panel,
        .tech-wrapper.conversation-only .row > .col-md-8 .card {
            display: block !important;
            flex: 1 1 0 !important;
            -webkit-flex: 1 1 0 !important;
            -ms-flex: 1 1 0 !important;
            flex-basis: 0 !important;
            max-width: none !important;
            width: 100% !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
        }
        /* Remove any centering inside the conversation panel */
        .tech-wrapper.conversation-only .conversation-panel .card { margin-left: 0 !important; margin-right: 0 !important; }

        /* Ensure responsive behavior around Bootstrap breakpoints */
        @media (max-width: 991px) {
            .tech-wrapper .col-md-4, .tech-wrapper .col-md-8 { flex-basis: 100%; max-width: 100%; }
            .tech-wrapper.sessions-only .col-md-8 { display: none !important; }
            .tech-wrapper.conversation-only .col-md-4 { display: none !important; }
            .tech-wrapper.conversation-only .col-md-8 { display: block !important; flex: 1 1 0 !important; max-width: none !important; }
        }
        /* Absolute-position fallback: force sessions panel to cover the full main-content area
           This ensures the sessions list fills the remaining width even if other column rules persist. */
        .tech-wrapper.sessions-only { position: relative !important; }
        .tech-wrapper.sessions-only > .row > .col-md-4,
        .tech-wrapper.sessions-only .tech-sidebar {
            position: absolute !important;
            left: 0 !important;
            right: 0 !important;
            width: auto !important;
            max-width: none !important;
            flex: none !important;
            display: block !important;
            z-index: 2 !important;
        }
        .tech-wrapper.sessions-only > .row > .col-md-8 { display: none !important; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid">
            <header class="page-header d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="page-title">Technician Chat</h1>
                    <p class="text-muted small">Real-time messaging for technicians</p>
                </div>
                <div>
                    <a id="archiveTopBtn" class="btn btn-sm btn-outline-secondary" href="archive_chats.php">Archive Chats</a>
                    <button id="qrBtn" class="btn btn-sm btn-outline-secondary ms-2" title="Generate QR to open user chat">QR</button>
                </div>
            </header>
            <div class="tech-wrapper sessions-only" id="techWrapper">
                <div class="row">
                    <div class="col-md-4">
                        <aside class="card tech-sidebar">
                            <div class="px-3 py-2">
                                <input id="sessionSearch" class="form-control" placeholder="Search sessions...">
                            </div>
                            <!--<div id="serverInfo" class="px-3 py-2 small text-muted">Server sessions: <span id="serverCount">0</span>
                                <?php if(!empty($serverSessionsDebug)): ?>
                                    <div class="text-danger small mt-1"><?php echo htmlspecialchars($serverSessionsDebug); ?></div>
                                <?php endif; ?>
                            </div>-->
                            <div id="sessionList" class="session-list" style="max-height:640px; overflow:auto; margin-top:8px"></div>
                        </aside>
                    </div>

                    <div class="col-md-8">
                        <section class="card conversation-panel">
                            <div class="conversation-header px-3 py-2 d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 id="convTitle">Select a session</h4>
                                    <div class="meta" id="convMeta"></div>
                                </div>
                                <div>
                                    <button id="backToSessions" class="btn btn-sm btn-outline-light me-2 d-none">← Sessions</button>
                                    <button id="endBtn" class="btn btn-outline-light" disabled>End Session</button>
                                </div>
                            </div>

                            <!-- Prominent session notice (shows technician status like On the way) -->
                            <div id="sessionNoticeBanner" class="alert alert-info mb-3" role="status" style="display:none; text-align:center; font-weight:600;"></div>
                            <div id="convMessages" class="conversation-messages p-3" style="min-height:280px; max-height:560px; overflow:auto;"></div>
                            <div class="conversation-composer p-3 border-top d-flex gap-2 align-items-center">
                                <div class="d-flex gap-2">
                                    <button id="notifyOnWayBtn" class="btn btn-primary" disabled title="Notify (On my way)">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4a1.5 1.5 0 10-3 0v.68C7.63 5.36 6 7.92 6 11v5l-1.7 1.7A1 1 0 005 20h14a1 1 0 00.7-1.6L18 16z" fill="currentColor"/></svg>
                                        <span class="visually-hidden">Notify</span>
                                    </button>
                                    <button id="markPendingBtn" class="btn btn-outline-light" disabled title="Mark pending">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M12 8v5l4 2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        <span class="visually-hidden">Pending</span>
                                    </button>
                                    <button id="markDoneBtn" class="btn btn-success" disabled title="Mark done">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        <span class="visually-hidden">Done</span>
                                    </button>
                                    <!-- flagging removed -->
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- QR Modal: shows a QR code linking to the user chat (chat.php/chat) -->
    <div class="modal fade" id="qrModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Open User Chat</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <div id="qrCodeCanvas" style="display:inline-block;margin-bottom:12px;"></div>
                        <div class="input-group">
                            <input id="qrLinkInput" type="text" class="form-control" readonly aria-label="Chat link">
                            <button id="qrCopyBtn" class="btn btn-outline-secondary" type="button">Copy</button>
                        </div>
                        <div class="mt-2 d-flex gap-2 justify-content-center">
                            <a id="qrOpenLink" class="btn btn-sm btn-primary" href="#" target="_blank" rel="noopener">Open Chat</a>
                            <button id="qrDownloadBtn" class="btn btn-sm btn-outline-primary" type="button">Download PNG</button>
                        </div>
                    <small class="text-muted d-block mt-2">Scan this QR code or open/copy the link to open the chat page on the same host.</small>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        // serverSessions: always fetch from API (do not rely on PHP-injected data)
        window.serverSessions = {};
        // serverMessages: in-memory cache of messages pulled from server. Do NOT rely on localStorage for messages.
        window.serverMessages = {};

        // refresh server sessions from API (keeps UI up to date)
        async function refreshServerSessions(){
            try{
                const r = await fetch('api/chat_sessions.php', { cache: 'no-store' });
                const rawText = await r.text();
                let parsed = {};
                try {
                    parsed = JSON.parse(rawText || '{}');
                } catch (err) {
                    console.warn('chat_sessions.php returned non-JSON or parse error', err);
                    window.serverSessions = {};
                    try{ listSessions(); }catch(e){}
                    return;
                }
                // If API returned wrapper with 'sessions', use it
                if (parsed && parsed.sessions && typeof parsed.sessions === 'object'){
                    window.serverSessions = parsed.sessions;
                    // no debug field expected from API; ignore any debug content
                } else {
                    window.serverSessions = parsed;
                }
                // update server count and manage debug display
                // compute active sessions count (exclude ended ones)
                const allSessions = Object.values(window.serverSessions || {});
                const activeCount = allSessions.filter(s => !(s && (s.ended == 1 || s.ended === true || s.status === 'ended'))).length;
                const si = document.getElementById('serverInfo');
                if(si){
                    const span = si.querySelector('#serverCount'); if(span) span.textContent = activeCount;
                }
                // immediately rebuild session list after refresh
                try{ listSessions(); }catch(e){}
            }catch(e){ console.warn('Could not fetch server sessions', e); }
        }

        // No initial debug probe. Only show debug output when the API returns a debug field or when parse fails.

        // initial fetch and periodic refresh
        refreshServerSessions(); setInterval(refreshServerSessions, 5000);

        

        // Technician side reads localStorage keys created by users
        function listSessions(){
            const keys = Object.keys(localStorage).filter(k=>k.startsWith('chat_meta_'));
            const localItems = keys.map(k=>JSON.parse(localStorage.getItem(k))).filter(Boolean);
            // include server-side sessions that may not exist in localStorage
            // Only include active (non-ended) server sessions here so serverItems represents actionable sessions
            const serverItems = Object.keys(window.serverSessions || {}).map(sid => {
                const s = window.serverSessions[sid];
                const ended = s && (s.ended == 1 || s.ended === true || s.status === 'ended');
                if(ended) return null;
                return {
                    id: sid,
                    name: s && s.name ? s.name : '',
                    location: s && s.location ? s.location : '',
                    issue: s && s.issue ? s.issue : '',
                    created: (s && (s.received_at || s.created)) ? (s.received_at || s.created) : new Date().toISOString(),
                    contact: s && s.contact ? s.contact : '',
                    flagged: s && s.flagged ? s.flagged : false,
                    auth_user: s && s.auth_user ? s.auth_user : null,
                    _serverRaw: s
                };
            }).filter(Boolean);
            // merge local and server items by id
            // If server has any sessions, treat server as authoritative and only show server sessions (allow overlay of local fields only for matching ids).
            const map = {};
            if (serverItems.length > 0) {
                serverItems.forEach(it => { if(!it || !it.id) return; map[it.id] = it; });
                // overlay local fields only for existing server session ids
                localItems.forEach(it => { if(!it || !it.id) return; if(map[it.id]) map[it.id] = Object.assign({}, map[it.id], it); });
            } else {
                // no server sessions - do NOT show local sessions.
                // Server is authoritative; showing localStorage-only sessions
                // causes stale/ghost sessions to appear. Leave `map` empty.
            }
            // Apply search filter if present
            let items = Object.values(map).filter(it => !(it.ended || (window.serverSessions && window.serverSessions[it.id] && window.serverSessions[it.id].ended))).sort((a,b)=> new Date(b.created)-new Date(a.created));
            try{
                const q = (document.getElementById('sessionSearch') && (document.getElementById('sessionSearch').value || '') || '').toLowerCase().trim();
                if(q){
                    items = items.filter(it => {
                        const srv = window.serverSessions && window.serverSessions[it.id] ? window.serverSessions[it.id] : {};
                        const parts = [it.name, it.location, it.issue, it.contact, it.id, srv.name, srv.location, srv.issue, srv.client_ip].filter(Boolean).map(s=>String(s).toLowerCase());
                        const hay = parts.join(' ');
                        return hay.indexOf(q) !== -1;
                    });
                }
            }catch(e){ /* ignore search errors */ }
            const list = document.getElementById('sessionList'); list.innerHTML='';
            items.forEach(meta=>{
                const div = document.createElement('div'); div.className='session-item';
                    div.dataset.id = meta.id;
                    const title = document.createElement('div'); title.className = 'session-title';
                        const secondLine = document.createElement('div'); secondLine.className = 'session-meta';
                            const thirdLine = document.createElement('div'); thirdLine.className = 'session-meta';
                // try direct match by session id
                let srv = window.serverSessions[meta.id];
                // fallback: try fuzzy match by name/location/issue + timestamp proximity
                if(!srv){
                    const metaTime = new Date(meta.created).getTime();
                    for(const sid in window.serverSessions){
                        const s = window.serverSessions[sid];
                        const st = Date.parse(s.received_at || s.created || '');
                        if(!st) continue;
                        const delta = Math.abs(st - metaTime);
                        if(delta < 15000 && String(s.name||'').trim() === String(meta.name||'').trim()){
                            srv = s; break;
                        }
                    }
                }
                // determine identifier, room and concern text preferring server data
                const identifierVal = (srv && srv.identifier) ? srv.identifier : (meta.identifier || '');
                const identifierType = (srv && srv.identifier_type) ? srv.identifier_type : (meta.identifier_type || '');
                const roomVal = (srv && srv.location) ? srv.location : (meta.location || '');
                const concernVal = (srv && srv.issue) ? srv.issue : (meta.issue || '');

                // First line: Concern | Room/Department
                const topConcern = (String(concernVal || '')).trim();
                const topShort = topConcern ? (topConcern.length > 120 ? topConcern.slice(0,120).trim() + '…' : topConcern) : '';
                if(roomVal && topShort){
                    title.textContent = topShort + ' | ' + roomVal;
                } else if(topShort){
                    title.textContent = topShort;
                } else if(roomVal){
                    title.textContent = roomVal;
                } else {
                    title.textContent = (meta.name || (srv && srv.name) || 'Session');
                }

                // Second line: Date and Time, IP
                const when = new Date(meta.created).toLocaleString();
                let secondText = when;
                if (srv && srv.client_ip) secondText += ' • IP: ' + srv.client_ip;
                secondLine.textContent = secondText;

                // Third line: Student Number, Name
                let thirdParts = [];
                if(identifierVal) thirdParts.push(identifierVal);
                const displayName = (meta.name || (srv && srv.name) || 'Guest');
                if(displayName) thirdParts.push(displayName);
                thirdLine.textContent = thirdParts.join(' • ');


                const row = document.createElement('div'); row.className='session-row';
                const leftCol = document.createElement('div'); leftCol.style.flex = '1';
                leftCol.appendChild(title); leftCol.appendChild(secondLine); leftCol.appendChild(thirdLine);
                row.appendChild(leftCol);
                const rightCol = document.createElement('div'); rightCol.className='session-badge';
                const badge = document.createElement('div');
                if (srv && (srv.status === 'on_the_way' || srv.status === 'on-the-way' || srv.status === 'on the way')) {
                    badge.className = 'badge bg-success text-white'; badge.textContent = 'On the way';
                } else {
                    const srvEnded = srv && (srv.ended == 1 || srv.ended === true || srv.status === 'ended');
                    badge.className = 'badge bg-info text-dark';
                    badge.textContent = srvEnded || meta.ended ? 'Ended' : 'Active';
                }
                rightCol.appendChild(badge);
                row.appendChild(rightCol);
                div.appendChild(row);
                
                div.addEventListener('click', ()=> openSession(meta.id));
                list.appendChild(div);
            }); // end items.forEach
            // end listSessions
        }
        let activeId = null;
        function openSession(id){
            activeId = id;
            document.querySelectorAll('.session-item').forEach(el=> el.classList.toggle('active', el.dataset.id===id));
            let meta = JSON.parse(localStorage.getItem('chat_meta_' + id) || 'null');
            if(!meta){
                const srv = window.serverSessions && window.serverSessions[id];
                if(srv){
                    meta = { id: id, name: srv.name || 'Guest', location: srv.location || '', issue: srv.issue || '', created: srv.received_at || srv.created || new Date().toISOString(), contact: srv.contact || '', ended: false };
                    localStorage.setItem('chat_meta_' + id, JSON.stringify(meta));
                } else {
                    meta = { id: id, name: 'Session', location: '', issue: '', created: new Date().toISOString(), ended: false };
                }
            }
            document.getElementById('convTitle').textContent = meta.name || 'Session';
            // keep the conversation header concise; detailed fields appear inside the ticket
            document.getElementById('convMeta').textContent = '';
            document.getElementById('endBtn').disabled = !!meta.ended;
            // fetch server-side history and merge with localStorage, then render
            (async ()=>{
                try{
                    const r = await fetch('api/chat_message.php?session_id=' + encodeURIComponent(id), { cache:'no-store' });
                    if(r.ok){
                        const serverMsgs = await r.json();
                        // Make server authoritative: cache messages in-memory
                        const replaced = (Array.isArray(serverMsgs) ? serverMsgs : []).map(sm=>({ sender: sm.sender||'tech', text: sm.text||'', ts: sm.ts||sm.received_at }));
                        try{ window.serverMessages[id] = replaced; }catch(e){ console.warn('Could not set serverMessages cache', e); }
                    }
                }catch(e){ console.warn('Could not fetch message history', e); }
                renderMessages();
                // switch to conversation view (messenger-style)
                try{
                    var tw = document.getElementById('techWrapper');
                    if(tw){ tw.classList.remove('sessions-only'); tw.classList.add('conversation-only'); }
                    var back = document.getElementById('backToSessions'); if(back) back.classList.remove('d-none');
                    // enforce full-width conversation visually in case CSS is overridden
                    try{ ensureConversationFullWidth(); }catch(e){}
                    try{ startLayoutEnforcer(); }catch(e){}
                }catch(e){}
            })();
        }

        // Back button: return to sessions selector view
        (function(){
            try{
                var back = document.getElementById('backToSessions');
                var tw = document.getElementById('techWrapper');
                if(back && tw){
                    back.addEventListener('click', function(){
                        tw.classList.remove('conversation-only');
                        tw.classList.add('sessions-only');
                        back.classList.add('d-none');
                        // clear active highlight
                        document.querySelectorAll('.session-item').forEach(el=> el.classList.remove('active'));
                        try{ stopLayoutEnforcer(); }catch(e){}
                        try{ ensureSessionsFullWidth(); }catch(e){}
                    });
                }
            }catch(e){ }
        })();

        (function(){ try{ var qb = document.getElementById('qrBtn'); if(qb){ qb.addEventListener('click', function(e){ e.preventDefault(); showQrModal(); }); } }catch(e){} })();

        // Helpers to force column sizing when switching views (works around CSS conflicts)
        function ensureConversationFullWidth(){
            try{
                const wrapper = document.getElementById('techWrapper');
                const col4 = wrapper.querySelector('.col-md-4');
                const col8 = wrapper.querySelector('.col-md-8');
                if(col4) col4.style.display = 'none';
                if(col8){
                    col8.style.display = 'block';
                    // hide until correctly sized to avoid a cramped flash
                    try{ col8.style.setProperty('visibility', 'hidden', 'important'); }catch(e){ col8.style.visibility = 'hidden'; }
                    try{ col8.style.setProperty('flex', '1 1 0', 'important'); }catch(e){ col8.style.flex = '1 1 0'; }
                    try{ col8.style.setProperty('max-width', 'none', 'important'); }catch(e){ col8.style.maxWidth = 'none'; }
                    try{ col8.style.setProperty('width', '100%', 'important'); }catch(e){ col8.style.width = '100%'; }
                    // mark as awaiting reveal
                    col8.dataset.awaitReveal = '1';
                }
            }catch(e){ console.warn('ensureConversationFullWidth failed', e); }
            try{ if(typeof forceLayout === 'function') forceLayout(); }catch(e){}
        }
        function restoreColumns(){
            try{
                const wrapper = document.getElementById('techWrapper');
                const col4 = wrapper.querySelector('.col-md-4');
                const col8 = wrapper.querySelector('.col-md-8');
                if(col4) col4.style.display = '';
                if(col8){ col8.style.display = ''; col8.style.flex = ''; col8.style.maxWidth = ''; col8.style.width = ''; }
            }catch(e){ console.warn('restoreColumns failed', e); }
        }

        function ensureSessionsFullWidth(){
            try{
                const wrapper = document.getElementById('techWrapper');
                if(!wrapper) return;
                const col4 = wrapper.querySelector('.col-md-4');
                const col8 = wrapper.querySelector('.col-md-8');
                if(col8) col8.style.display = 'none';
                if(col4){
                    col4.style.display = 'block';
                    try{ col4.style.setProperty('flex', '1 1 0', 'important'); }catch(e){ col4.style.flex = '1 1 0'; }
                    try{ col4.style.setProperty('max-width', 'none', 'important'); }catch(e){ col4.style.maxWidth = 'none'; }
                    try{ col4.style.setProperty('width', '100%', 'important'); }catch(e){ col4.style.width = '100%'; }
                }
            }catch(e){ console.warn('ensureSessionsFullWidth failed', e); }
            try{ if(typeof forceLayout === 'function') forceLayout(); }catch(e){}
        }

        function renderMessages(){
            if(!activeId) return;
            let arr = window.serverMessages[activeId] || [];
            // If no server messages cached, fetch them now (async) and re-render when available
            if ((!arr || arr.length === 0) && activeId){
                (async ()=>{
                    try{
                        const r = await fetch('api/chat_message.php?session_id=' + encodeURIComponent(activeId), { cache:'no-store' });
                        if(r.ok){
                            const serverMsgs = await r.json();
                            const replaced = (Array.isArray(serverMsgs) ? serverMsgs : []).map(sm=>({ sender: sm.sender||'tech', text: sm.text||'', ts: sm.ts||sm.received_at }));
                            try{ window.serverMessages[activeId] = replaced; }catch(e){ console.warn('Could not set serverMessages cache', e); }
                            arr = replaced;
                            // render after fetching
                            renderMessages();
                            return;
                        } else {
                            console.warn('Failed to fetch server messages', r.status);
                        }
                    }catch(e){ console.warn('Error fetching server messages', e); }
                })();
            }
            const box = document.getElementById('convMessages'); box.innerHTML='';
            // renderMessages invoked; suppressed debug logging in production
            // Render as a ticket: header, fields, concern body, timeline
            const metaLocal = (()=>{ try{ return JSON.parse(localStorage.getItem('chat_meta_' + activeId) || 'null'); }catch(e){ return null; } })();
            const srv = window.serverSessions && window.serverSessions[activeId] ? window.serverSessions[activeId] : null;
            const ticket = document.createElement('div'); ticket.className = 'ticket-card';
            // Render fields in the requested order and format:
            // Name, Student Number, Session ID, Room/Department, Concern
            const idType = (metaLocal && metaLocal.identifier_type) ? metaLocal.identifier_type : (srv && srv.identifier_type ? srv.identifier_type : '');
            const idVal = (metaLocal && metaLocal.identifier) ? metaLocal.identifier : (srv && srv.identifier ? srv.identifier : '');
            const loc = (metaLocal && metaLocal.location) ? metaLocal.location : (srv && srv.location) ? srv.location : '';
            // helper: extract concern text, stripping concatenated labeled summaries if present
            function extractConcern(candidate){
                if(!candidate) return '';
                const s = String(candidate || '').trim();
                // detect labeled concatenated summary
                const hasLabels = /Name:|Student Number:|Employee Number:|Room\/Department:|Concern:/i.test(s);
                if(!hasLabels) return s;
                const idx = s.indexOf('Concern:');
                if(idx !== -1){
                    return s.slice(idx + 'Concern:'.length).trim();
                }
                // no explicit 'Concern:' label found — attempt a looser extraction by removing leading labeled sections
                // split on 'Concern' word boundary if present, otherwise give empty to avoid repeating full summary
                const parts = s.split(/\bConcern\b/i);
                if(parts.length > 1){
                    return parts.slice(1).join('Concern').trim();
                }
                return '';
            }

            const foundUserMsg = (Array.isArray(arr) ? arr.find(m=>m.sender === 'user') : null);
            let concernText = '';
            if(foundUserMsg && foundUserMsg.text){
                concernText = extractConcern(foundUserMsg.text);
            } else if(srv && srv.issue){
                concernText = extractConcern(srv.issue);
            } else if(metaLocal && metaLocal.issue){
                concernText = extractConcern(metaLocal.issue);
            }

            // render compact label/value rows for efficiency
            function makeField(label, value, opts){
                const el = document.createElement('div'); el.className = 'tf' + (opts && opts.full ? ' full' : '');
                if(opts && opts.prominent) el.classList.add('prominent');
                const lab = document.createElement('div'); lab.className = 'label'; lab.textContent = label;
                const val = document.createElement('div'); val.className = 'value'; val.textContent = value || '';
                if(opts && opts.mono) val.classList.add('session-id');
                if(opts && opts.concern) val.classList.add('concern');
                el.appendChild(lab); el.appendChild(val); return el;
            }

            const fields = document.createElement('div'); fields.className = 'ticket-fields'; fields.style.marginTop = '8px'; fields.style.marginBottom = '8px';
            const nameVal = (metaLocal && metaLocal.name) ? metaLocal.name : (srv && srv.name ? srv.name : 'Guest');
            const studentVal = (idType === 'student' ? idVal : (idType === 'employee' ? idVal : idVal));
            const sessVal = activeId || '';
            const roomVal = loc || '';
            const concernVal = concernText || '';
            // Make Room/Department and Concern prominent and place them at the top
            const roomField = makeField('Room/Department', roomVal, { prominent: true });
            const concernField = makeField('Concern', concernVal, { full: true, concern: true, prominent: true });
            fields.appendChild(roomField);
            fields.appendChild(concernField);
            // append remaining metadata
            fields.appendChild(makeField('Name', nameVal));
            fields.appendChild(makeField('Student Number', studentVal));
            fields.appendChild(makeField('Session ID', sessVal, { mono: true }));
            ticket.appendChild(fields);

            // timeline
            const timeline = document.createElement('div'); timeline.className = 'ticket-timeline';
            const techMsgs = Array.isArray(arr) ? arr.filter(m=>m.sender !== 'user') : [];
            techMsgs.forEach(tm => {
                const item = document.createElement('div'); item.className = 'timeline-item';
                const ts = tm.ts ? new Date(tm.ts).toLocaleString() : '';
                item.textContent = (tm.sender || 'tech') + ' • ' + ts + ' — ' + tm.text;
                timeline.appendChild(item);
            });
            if(techMsgs.length) ticket.appendChild(timeline);

            box.appendChild(ticket);
            box.scrollTop = box.scrollHeight;
            // Update status banner based on authoritative server session status
            try{
                const banner = document.getElementById('sessionNoticeBanner');
                const s = window.serverSessions && window.serverSessions[activeId] ? window.serverSessions[activeId] : null;
                if(!s){ clearBanner(); }
                else if(s.ended || s.status === 'ended'){
                    setBanner('secondary', 'Session ended.');
                } else if(s.status === 'on_the_way' || s.status === 'on-the-way' || s.status === 'on the way'){
                    setBanner('success', 'A technician is on the way.');
                } else if(s.status === 'pending'){
                    setBanner('warning', 'Request marked pending by technician.');
                } else {
                    clearBanner();
                }
            }catch(e){ /* ignore banner errors */ }
        }

        // Technician actions
        // Banner helpers (mirror chat.php behaviour)
        function setBanner(type, text){
            try{
                const b = document.getElementById('sessionNoticeBanner'); if(!b) return;
                let cls = (type === 'success') ? 'alert alert-success mb-3' : (type === 'warning') ? 'alert alert-warning mb-3' : (type === 'secondary') ? 'alert alert-secondary mb-3' : 'alert alert-info mb-3';
                b.className = cls;
                // Force a readable text color inline with !important to override conflicting styles
                // Use a dark color for contrast on bright backgrounds
                try{ b.style.setProperty('color', '#04211b', 'important'); }catch(e){ b.style.color = '#04211b'; }
                b.textContent = text; b.style.display = '';
            }catch(e){}
        }
        function clearBanner(){ try{ const b=document.getElementById('sessionNoticeBanner'); if(b){ b.style.display='none'; try{ b.style.removeProperty('color'); }catch(e){ b.style.color = ''; } } }catch(e){}
        }

        // QR generation: detect extensionless routing and render a PNG-downloadable QR
        function showQrModal(){
            var modalEl = document.getElementById('qrModal');
            var input = document.getElementById('qrLinkInput');
            var canvasContainer = document.getElementById('qrCodeCanvas');
            var openLink = document.getElementById('qrOpenLink');
            var copyBtn = document.getElementById('qrCopyBtn');
            var downloadBtn = document.getElementById('qrDownloadBtn');

            var origin = window.location.origin;
            var basePath = window.location.pathname.replace(/\/[^/]*$/, '');
            if(basePath === '') basePath = '/';
            var tryChat = origin + (basePath.replace(/\/$/, '') || '') + '/chat';
            var tryChatPhp = origin + (basePath.replace(/\/$/, '') || '') + '/chat.php';

            // show modal immediately; we'll resolve the canonical URL quickly
            canvasContainer.innerHTML = '';
            var bsModal = new bootstrap.Modal(modalEl);
            bsModal.show();

            // Helper: small timeout-aware HEAD probe
            function probeHead(url, timeoutMs){
                return new Promise((resolve) => {
                    var controller = null;
                    try{
                        controller = new AbortController();
                    }catch(e){}
                    var did = false;
                    var timer = setTimeout(function(){ if(controller && controller.abort) controller.abort(); if(!did){ did = true; resolve(false); } }, timeoutMs || 1200);
                    fetch(url, { method: 'HEAD', cache: 'no-store', signal: controller ? controller.signal : undefined }).then(function(r){ clearTimeout(timer); if(did) return; did = true; resolve(!!(r && r.ok)); }).catch(function(){ clearTimeout(timer); if(did) return; did = true; resolve(false); });
                });
            }

            // Render QR for a given URL (lazy-load kjua if needed)
            function renderQRFor(url){
                canvasContainer.innerHTML = '';
                function doRender(){
                    try{
                        var el = window.kjua({ text: url, fill: '#111', rounded: 8, quiet:1, size:320 });
                        canvasContainer.appendChild(el);
                    }catch(e){
                        // fallback loader
                        var s = document.createElement('script'); s.src = 'https://cdn.jsdelivr.net/npm/kjua@0.1.1/dist/kjua.min.js';
                        s.onload = function(){ try{ var el = window.kjua({ text: url, fill: '#111', rounded: 8, quiet:1, size:320 }); canvasContainer.appendChild(el); }catch(err){ canvasContainer.innerText = 'QR render failed'; } };
                        s.onerror = function(){ canvasContainer.innerText = 'Unable to load QR library.'; };
                        document.head.appendChild(s);
                    }
                }
                if(window.kjua) doRender(); else doRender();
            }

            // Export QR element to PNG and trigger download
            function exportQrToPng(filename){
                var el = canvasContainer.firstElementChild;
                if(!el){ return alert('QR not rendered yet'); }

                // 1) canvas element (direct or descendant)
                var canvas = (el.nodeName && el.nodeName.toLowerCase() === 'canvas') ? el : (el.querySelector ? el.querySelector('canvas') : null);
                if(canvas){ try{ var data = canvas.toDataURL('image/png'); downloadDataUrl(data, filename); return; }catch(e){} }

                // 2) svg element (direct or descendant) -> serialize -> draw into canvas
                var svgEl = (el.nodeName && el.nodeName.toLowerCase() === 'svg') ? el : (el.querySelector ? el.querySelector('svg') : null);
                if(svgEl){
                    try{
                        var svg = svgEl.outerHTML;
                        var blob = new Blob([svg], { type: 'image/svg+xml;charset=utf-8' });
                        var URLObj = window.URL || window.webkitURL || window;
                        var url = URLObj.createObjectURL(blob);
                        var img = new Image();
                        img.onload = function(){
                            try{
                                var size = Math.max(img.width, img.height, 512);
                                var canvas2 = document.createElement('canvas'); canvas2.width = size; canvas2.height = size;
                                var ctx = canvas2.getContext('2d'); ctx.fillStyle = '#ffffff'; ctx.fillRect(0,0,canvas2.width,canvas2.height); ctx.drawImage(img, 0, 0, canvas2.width, canvas2.height);
                                URLObj.revokeObjectURL(url);
                                var data2 = canvas2.toDataURL('image/png'); downloadDataUrl(data2, filename);
                            }catch(e){ URLObj.revokeObjectURL(url); alert('Could not export PNG: '+ (e && e.message)); }
                        };
                        img.onerror = function(){ URLObj.revokeObjectURL(url); alert('Could not render QR for PNG export'); };
                        img.src = url;
                        return;
                    }catch(e){}
                }

                // 3) image element (direct or descendant)
                var imgEl = (el.nodeName && el.nodeName.toLowerCase() === 'img') ? el : (el.querySelector ? el.querySelector('img') : null);
                if(imgEl && imgEl.src){
                    // data URL
                    if(imgEl.src.indexOf('data:image') === 0){ downloadDataUrl(imgEl.src, filename); return; }
                    // external URL: attempt to draw with crossorigin anonymous
                    try{
                        var imgObj = new Image(); imgObj.crossOrigin = 'anonymous';
                        imgObj.onload = function(){ try{ var size = Math.max(imgObj.width, imgObj.height, 512); var c = document.createElement('canvas'); c.width = size; c.height = size; var ctx = c.getContext('2d'); ctx.fillStyle = '#ffffff'; ctx.fillRect(0,0,c.width,c.height); ctx.drawImage(imgObj,0,0,c.width,c.height); var d = c.toDataURL('image/png'); downloadDataUrl(d, filename); }catch(e){ alert('Export failed: '+(e && e.message)); } };
                        imgObj.onerror = function(){ alert('Could not load QR image for export (CORS may block this).'); };
                        imgObj.src = imgEl.src;
                        return;
                    }catch(e){}
                }

                // 4) background-image on element (data URL)
                try{
                    var bg = window.getComputedStyle(el).getPropertyValue('background-image');
                    if(bg && bg.indexOf('data:image') !== -1){
                        var m = bg.match(/url\((['"]?)(data:image\/[a-zA-Z]+;base64,[^)]+)\1\)/);
                        if(m && m[2]){ downloadDataUrl(m[2], filename); return; }
                    }
                }catch(e){}

                alert('Unsupported QR element for PNG export');
            }

            function downloadDataUrl(dataurl, filename){ var a = document.createElement('a'); a.href = dataurl; a.download = filename; document.body.appendChild(a); a.click(); a.remove(); }

            // Resolve canonical URL by probing extensionless path first
            (async function(){
                var ok = await probeHead(tryChat, 1200).catch(()=>false);
                var chosen = ok ? tryChat : tryChatPhp;
                // Show the canonical chat URL (consistent every time)
                input.value = chosen;
                if(openLink) openLink.href = chosen;
                // wire copy button
                if(copyBtn){ copyBtn.onclick = function(){ navigator.clipboard && navigator.clipboard.writeText(chosen).then(function(){ copyBtn.innerText = 'Copied'; setTimeout(function(){ copyBtn.innerText = 'Copy'; },1500); }, function(){ alert('Copy failed'); }); }; }
                // wire download button
                if(downloadBtn){ downloadBtn.onclick = function(){ var fn = 'chat-qr.png'; exportQrToPng(fn); }; }
                // render QR with chosen URL
                renderQRFor(chosen);
            })();
        }

        async function notifyOnWay(){
            if(!activeId) return alert('Open a session first.');
            if(!confirm('Send "Technician on the way" notification to user?')) return;
            // Post a server message so the user will receive it
            const now = new Date().toISOString();
            const msg = { session_id: activeId, sender: 'tech', text: 'Technician is on the way', ts: now };
            try{ await fetch('api/chat_message.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(msg) }); }
            catch(e){ console.warn('Failed to send on-the-way message', e); }
            // update server session status
            try{ await fetch('api/chat_update.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ session_id: activeId, status: 'on_the_way', operator: 'tech' }) }); }
            catch(e){ console.warn('Failed to update session status', e); }
            // Immediately show indicator in the technician UI
            try{ setBanner('success', 'A technician is on the way.'); }catch(e){}
            // record locally that technician clicked "On the way" for this session
            try{ localStorage.setItem('chat_ontheway_' + activeId, '1'); }catch(e){}
            // refresh UI
            await refreshServerSessions(); listSessions(); renderMessages();
            try{ updateActionButtons(); }catch(e){}
        }

        async function markPending(){
            if(!activeId) return alert('Open a session first.');
            if(!confirm('Mark this session as pending?')) return;
            try{ await fetch('api/chat_update.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ session_id: activeId, status: 'pending', operator: 'tech' }) }); }
            catch(e){ console.warn('Failed to mark pending', e); }
            await refreshServerSessions(); listSessions();
        }

        async function markDone(){
            if(!activeId) return alert('Open a session first.');
            // require that technician clicked On The Way first
            try{ if(!localStorage.getItem('chat_ontheway_' + activeId)) return alert('You must click "On my way" before ending the session.'); }catch(e){}
            if(!confirm('Mark this session as done? This will end the session.')) return;
            try{ await fetch('api/chat_end.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ session_id: activeId, operator: 'tech' }) }); }
            catch(e){ console.warn('Failed to mark done', e); }
            // clear local entries and in-memory server cache
            localStorage.removeItem('chat_meta_' + activeId);
            try{ delete window.serverMessages[activeId]; }catch(e){}
            activeId = null;
            document.getElementById('convMessages').innerHTML='';
            document.getElementById('convTitle').textContent = 'Select a session';
            document.getElementById('convMeta').textContent = '';
            // hide any session notice/banner immediately when session is ended
            try{ clearBanner(); }catch(e){}
            // switch back to sessions list view
            try{
                var tw = document.getElementById('techWrapper'); if(tw){ tw.classList.remove('conversation-only'); tw.classList.add('sessions-only'); }
                var back = document.getElementById('backToSessions'); if(back) back.classList.add('d-none');
                try{ stopLayoutEnforcer(); }catch(e){}
                try{ ensureSessionsFullWidth(); }catch(e){}
                // clear any active highlight
                try{ document.querySelectorAll('.session-item').forEach(el=> el.classList.remove('active')); }catch(e){}
            }catch(e){}
            await refreshServerSessions(); listSessions();
        }

        // flagging removed

        function sendReply(){
            if(!activeId) return alert('Open a session first.');
            const input = document.getElementById('replyInput');
            const txt = (input.value||'').trim(); if(!txt) return;
            const key = 'chat_messages_' + activeId; const arr = JSON.parse(localStorage.getItem(key) || '[]');
            const msg = { sender: 'tech', text: txt, ts: new Date().toISOString() };
            arr.push(msg);
            try{ window.serverMessages[activeId] = window.serverMessages[activeId] || []; window.serverMessages[activeId].push(msg); }catch(e){}
            // post to server
            (async ()=>{
                try{ await fetch('api/chat_message.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(Object.assign({ session_id: activeId }, msg)) }); }
                catch(e){ console.warn('Failed to post tech reply', e); }
            })();
            input.value = '';
            renderMessages();
        }

        const _replyBtn = document.getElementById('replyBtn');
        if(_replyBtn) _replyBtn.addEventListener('click', sendReply);
        // Send on Enter (Shift+Enter for newline)
        const _replyInput = document.getElementById('replyInput');
        if(_replyInput) _replyInput.addEventListener('keydown', function(e){
            if(e.key === 'Enter' && !e.shiftKey){
                e.preventDefault();
                sendReply();
            }
        });

        document.getElementById('endBtn').addEventListener('click', ()=>{
            if(!activeId) return;
            // require that technician clicked On The Way first
            try{ if(!localStorage.getItem('chat_ontheway_' + activeId)) return alert('You must click "On my way" before ending the session.'); }catch(e){}
            if(!confirm('Mark session as ended?')) return;
            const metaKey = 'chat_meta_' + activeId;
            const msgKey = 'chat_messages_' + activeId;
            const meta = JSON.parse(localStorage.getItem(metaKey) || 'null');
            if(meta){ meta.ended = true; meta.endedAt = new Date().toISOString(); localStorage.setItem(metaKey, JSON.stringify(meta)); }
            // notify server so session is marked ended on server-side too
            (async ()=>{
                try{
                    await fetch('api/chat_end.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ session_id: activeId }) });
                }catch(e){ console.warn('Failed to notify server about end', e); }
                // Remove session from technician list (clear localStorage entries so it no longer appears)
                localStorage.removeItem(metaKey);
                localStorage.removeItem(msgKey);
                // clear active conversation UI
                activeId = null;
                document.getElementById('convMessages').innerHTML = '';
                document.getElementById('convTitle').textContent = 'Select a session';
                document.getElementById('convMeta').textContent = '';
                document.getElementById('endBtn').disabled = true;
                // hide any session notice/banner immediately when session is ended
                try{ clearBanner(); }catch(e){}
                // switch back to sessions list view
                try{
                    var tw = document.getElementById('techWrapper'); if(tw){ tw.classList.remove('conversation-only'); tw.classList.add('sessions-only'); }
                    var back = document.getElementById('backToSessions'); if(back) back.classList.add('d-none');
                    try{ stopLayoutEnforcer(); }catch(e){}
                    try{ ensureSessionsFullWidth(); }catch(e){}
                    try{ document.querySelectorAll('.session-item').forEach(el=> el.classList.remove('active')); }catch(e){}
                }catch(e){}
                // refresh server sessions and list
                await refreshServerSessions();
                listSessions();
            })();
        });

        // poll for external updates
        setInterval(()=>{ listSessions(); if(activeId) renderMessages(); }, 2000);
        listSessions();
        // debounced search input -> rerender session list
        (function(){
            function debounce(fn, ms){ let t; return function(){ clearTimeout(t); const args = arguments; t = setTimeout(()=> fn.apply(this, args), ms); }; }
            const si = document.getElementById('sessionSearch');
            if(si){ si.addEventListener('input', debounce(function(){ try{ listSessions(); }catch(e){} }, 180)); }
        })();
        // If page loaded in sessions-only mode, force the sessions column to fill remaining width
        try{ const tw = document.getElementById('techWrapper'); if(tw && tw.classList.contains('sessions-only')) ensureSessionsFullWidth(); if(tw && tw.classList.contains('conversation-only')){ ensureConversationFullWidth(); try{ startLayoutEnforcer(); }catch(e){} } }catch(e){}

        // enable/disable action buttons based on active selection
        function updateActionButtons(){
            const can = !!activeId;
            const srv = window.serverSessions && window.serverSessions[activeId];
            const srvEnded = srv && (srv.ended == 1 || srv.ended === true || srv.status === 'ended');
            const notifyDisabled = !can || !!srvEnded;
            const notifyBtn = document.getElementById('notifyOnWayBtn');
            if(notifyBtn){
                notifyBtn.disabled = notifyDisabled;
                notifyBtn.title = notifyDisabled ? '' : '';
            }
            document.getElementById('markPendingBtn').disabled = !can || !!srvEnded;
            // Only allow ending/marking done if technician has clicked 'On the way' for this session
            var onTheWayLocal = false;
            try{ onTheWayLocal = !!localStorage.getItem('chat_ontheway_' + activeId); }catch(e){}
            document.getElementById('markDoneBtn').disabled = !can || !!srvEnded || !onTheWayLocal;
            // `endBtn` is the inline End Session control in the conversation header
            var endBtnEl = document.getElementById('endBtn'); if(endBtnEl) endBtnEl.disabled = !can || !!srvEnded || !onTheWayLocal;
        }
        // hook openSession to update buttons
        const _openSession = openSession;
        openSession = function(id){ _openSession(id); setTimeout(updateActionButtons, 120); };
        document.getElementById('notifyOnWayBtn').addEventListener('click', notifyOnWay);
        document.getElementById('markPendingBtn').addEventListener('click', markPending);
        document.getElementById('markDoneBtn').addEventListener('click', markDone);
        // flagging removed
        // if another page requested opening a session, do it now
        try{
            const toOpen = localStorage.getItem('its_open_session');
            if(toOpen){ localStorage.removeItem('its_open_session'); setTimeout(()=>{ try{ openSession(toOpen); }catch(e){} }, 250); }
        }catch(e){}

        // Keyboard shortcut: Shift+A opens the chat archive (unless typing)
        document.addEventListener('keydown', function(e){
            try{
                if(e.key === 'A' && !e.ctrlKey && !e.metaKey && !e.altKey){
                    const active = document.activeElement;
                    const tag = active && active.tagName ? active.tagName.toUpperCase() : '';
                    if(tag === 'INPUT' || tag === 'TEXTAREA' || (active && active.isContentEditable)) return;
                    window.location.href = 'archive_chats.php';
                }
            }catch(err){}
        });

        // Robust layout fixer: compute available content width and force the active column to fill it
        function forceLayout(){
            try{
                const tw = document.getElementById('techWrapper');
                const main = document.querySelector('.main-content');
                const sidebar = document.getElementById('sidebar');
                if(!tw || !main) return;
                const row = tw.querySelector('.row');
                const col4 = row ? row.querySelector('.col-md-4') : null;
                const col8 = row ? row.querySelector('.col-md-8') : null;
                // compute available width inside the tech wrapper (more reliable than main-content)
                const twRect = tw.getBoundingClientRect();
                const available = Math.max(0, Math.floor(twRect.width - 24));

                if(tw.classList.contains('sessions-only')){
                    if(col8) col8.style.display = 'none';
                    if(col4){
                        col4.style.display = 'block';
                        col4.style.position = 'relative';
                        try{ col4.style.setProperty('flex', '1 1 0', 'important'); }catch(e){ col4.style.flex = '1 1 0'; }
                        try{ col4.style.setProperty('width', available + 'px', 'important'); }catch(e){ col4.style.width = available + 'px'; }
                        try{ col4.style.setProperty('max-width', available + 'px', 'important'); }catch(e){ col4.style.maxWidth = available + 'px'; }
                        // also apply directly to the sidebar card to avoid centered/narrow card issues
                        try{
                            const sidebarCard = col4.querySelector('.tech-sidebar') || col4.querySelector('.card');
                            if(sidebarCard){
                                try{ sidebarCard.style.setProperty('width', available + 'px', 'important'); }catch(e){ sidebarCard.style.width = available + 'px'; }
                                try{ sidebarCard.style.setProperty('max-width', available + 'px', 'important'); }catch(e){ sidebarCard.style.maxWidth = available + 'px'; }
                                try{ sidebarCard.style.setProperty('margin-left', 'auto', 'important'); }catch(e){ sidebarCard.style.marginLeft = 'auto'; }
                                try{ sidebarCard.style.setProperty('margin-right', 'auto', 'important'); }catch(e){ sidebarCard.style.marginRight = 'auto'; }
                                try{ sidebarCard.style.setProperty('box-sizing', 'border-box', 'important'); }catch(e){ sidebarCard.style.boxSizing = 'border-box'; }
                            }
                        }catch(e){}
                    }
                } else if(tw.classList.contains('conversation-only')){
                    if(col4) col4.style.display = 'none';
                    if(col8){
                        col8.style.display = 'block';
                        col8.style.position = 'relative';
                        try{ col8.style.setProperty('flex', '1 1 0', 'important'); }catch(e){ col8.style.flex = '1 1 0'; }
                        try{ col8.style.setProperty('width', available + 'px', 'important'); }catch(e){ col8.style.width = available + 'px'; }
                        try{ col8.style.setProperty('max-width', available + 'px', 'important'); }catch(e){ col8.style.maxWidth = available + 'px'; }
                        // also apply sizing directly to the conversation card to override other rules
                        try{
                            const convCard = col8.querySelector('.conversation-panel') || col8.querySelector('.card');
                            if(convCard){
                                try{ convCard.style.setProperty('width', available + 'px', 'important'); }catch(e){ convCard.style.width = available + 'px'; }
                                try{ convCard.style.setProperty('max-width', available + 'px', 'important'); }catch(e){ convCard.style.maxWidth = available + 'px'; }
                                try{ convCard.style.setProperty('margin-left', 'auto', 'important'); }catch(e){ convCard.style.marginLeft = 'auto'; }
                                try{ convCard.style.setProperty('margin-right', 'auto', 'important'); }catch(e){ convCard.style.marginRight = 'auto'; }
                                try{ convCard.style.setProperty('box-sizing', 'border-box', 'important'); }catch(e){ convCard.style.boxSizing = 'border-box'; }
                            }
                            // reveal if we previously hid while awaiting layout (reveal the card itself)
                            if(convCard && convCard.dataset && convCard.dataset.awaitReveal){
                                try{ convCard.style.removeProperty('visibility'); }catch(e){ convCard.style.visibility = ''; }
                                delete convCard.dataset.awaitReveal;
                            }
                            // fallback: reveal column if still marked
                            if(col8.dataset && col8.dataset.awaitReveal){ try{ col8.style.removeProperty('visibility'); }catch(e){ col8.style.visibility=''; } delete col8.dataset.awaitReveal; }
                        }catch(e){}
                    }
                } else {
                    if(col4){ col4.style.display=''; col4.style.position=''; col4.style.flex=''; col4.style.width=''; col4.style.maxWidth=''; }
                    if(col8){ col8.style.display=''; col8.style.position=''; col8.style.flex=''; col8.style.width=''; col8.style.maxWidth=''; }
                }
            }catch(e){ console.warn('forceLayout error', e); }
        }
        // Layout enforcer: repeatedly apply inline styles with priority to counter competing runtime styles
        let __layoutEnforcerId = null;
        function startLayoutEnforcer(intervalMs = 150, maxRunMs = 12000){
            stopLayoutEnforcer();
            const start = Date.now();
            __layoutEnforcerId = setInterval(()=>{
                try{ forceLayout(); }catch(e){}
                if(Date.now() - start > maxRunMs){ stopLayoutEnforcer(); }
            }, intervalMs);
        }
        function stopLayoutEnforcer(){ if(__layoutEnforcerId){ clearInterval(__layoutEnforcerId); __layoutEnforcerId = null; } }
        // run on load/resize and after a short delay to catch dynamic changes
        window.addEventListener('resize', function(){ try{ forceLayout(); }catch(e){} });
        setTimeout(forceLayout, 150);
    </script>
</body>
</html>
