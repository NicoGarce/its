<?php
// Public user chat with pre-chat form (no auth required)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ITS Support - User Chat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="assets/img/favicon.ico" type="image/x-icon">
    <link rel="icon" type="image/png" href="assets/img/its_logo.png">
    <link rel="apple-touch-icon" href="assets/img/its_logo.png">
    <style>
        /* Layout containers */
        .prechat-card { max-width:960px; margin: 24px auto; padding: 0 12px; }
        .chat-panel { max-width:900px; margin: 12px auto; }

        /* Conversation card becomes a flexible column; avoid fixed 100vh heights to play nicer on mobile browsers */
        .chat-panel .login-card {
            display: flex; flex-direction: column; padding: 16px;
            max-height: calc(100vh - 48px); box-sizing: border-box;
            max-width: 900px; margin: 12px auto;
        }

        /* Messages list grows and scrolls; prefer max-height and allow the page to flow on small screens */
        .conversation-messages { flex: 1 1 auto; max-height: calc(100vh - 260px); overflow: auto; padding: 14px; background: #f0f2f5; border-radius: 12px; }

        /* Messenger-like message rows */
        .message-wrapper { display:flex; align-items:flex-end; gap:8px; margin-bottom:12px; }
        .message-wrapper.from-user { justify-content:flex-end; }
        .message-wrapper.from-tech { justify-content:flex-start; }

        .avatar { width:40px; height:40px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-weight:700; color:#fff; background: #6c757d; flex: 0 0 40px; }

        .message-content { display:flex; flex-direction:column; max-width:72%; }
        .message-bubble { padding:10px 14px; border-radius:18px; word-break:break-word; box-shadow: 0 2px 6px rgba(0,0,0,0.08); position: relative; white-space: pre-wrap; }

        /* user (right) bubble */
        .message-wrapper.from-user .message-bubble {
            background: linear-gradient(135deg,var(--accent-color),var(--accent-hover));
            color:#021018;
            border-bottom-right-radius:6px;
        }
        .message-wrapper.from-user .message-bubble:after{
            content: '';
            position: absolute; right: -6px; bottom: 0;
            width: 12px; height: 12px; background: linear-gradient(135deg,var(--accent-color),var(--accent-hover));
            transform: translateY(50%) rotate(45deg); border-radius:2px;
        }

        /* tech (left) bubble */
        .message-wrapper.from-tech .message-bubble { background: #ffffff; color: #111827; }
        .message-wrapper.from-tech .message-bubble:before{
            content: '';
            position: absolute; left: -6px; bottom: 0;
            width: 12px; height: 12px; background: #ffffff;
            transform: translateY(50%) rotate(45deg); border-radius:2px; border:1px solid rgba(0,0,0,0.04);
        }

        .message-meta { font-size:11px; color:rgba(0,0,0,0.45); margin-top:6px; align-self:flex-end; }
        /* Ticket-style card for user view */
        .ticket-card{border:1px solid var(--border-color);border-radius:10px;padding:14px;background:var(--card-bg);box-shadow:0 6px 18px rgba(0,0,0,0.04)}
        .ticket-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px}
        .ticket-title{font-weight:700;font-size:16px}
        .ticket-meta{font-size:13px;color:rgba(0,0,0,0.6)}
        .ticket-body{white-space:pre-wrap;color:rgba(0,0,0,0.85);margin-top:8px}
        .ticket-timeline{margin-top:12px;border-top:1px dashed rgba(0,0,0,0.06);padding-top:8px}
        .timeline-item{font-size:13px;padding:6px 0}

        /* Sticky header like messenger */
        .conversation-header { position: sticky; top: 0; background: transparent; z-index: 10; padding-bottom: 8px; }
        .conversation-header .meta { font-size:13px; color:rgba(0,0,0,0.55); }

        /* Redesigned pre-chat form - single centered column */
        .prechat-grid { display: grid; grid-template-columns: 1fr; gap: 12px; max-width:840px; margin:12px auto; }
        .prechat-field { display:flex; flex-direction:column; }
        .prechat-field .form-label { font-size:13px; color:var(--text-secondary); margin-bottom:6px; }
        .prechat-help { font-size:12px; color:rgba(0,0,0,0.55); margin-top:6px; }
        .prechat-actions { display:flex; gap:12px; align-items:center; justify-content:space-between; margin-top:12px; }
        .contact-optional { font-size:12px; color:rgba(0,0,0,0.55); }

        /* Two-column layout on medium+ screens */
        @media (min-width:768px){
            .prechat-grid{ grid-template-columns: 1fr 1fr; }
            /* Make the issue summary and wide fields span full width for readability */
            .prechat-grid .issue-field,
            .prechat-grid .wide-field { grid-column: 1 / -1; }
        }

        /* Tweak for small screens: stack actions and make button full width */
            .chat-panel.fullscreen { position: fixed; inset: 0; z-index: 1050; background: #fff; padding: 0; display: block; }
            /* ensure body doesn't scroll behind the fullscreen panel on mobile */
            body.chat-fullscreen-active { overflow: hidden; }
            .chat-panel.fullscreen .login-card { max-height: 100vh; border-radius: 0; margin: 0; max-width: 100%; padding: 12px; box-sizing: border-box; }

            /* Responsive conversation header: stack title/meta above action buttons */
            .conversation-header{ flex-direction: column; align-items: flex-start; gap: 8px; }
            .conversation-header > div:first-child{ width:100%; }
            .conversation-header > div:last-child{ width:100%; display:flex; justify-content:space-between; gap:8px; }
            .conversation-header .meta{ font-size:13px; }
            .conversation-header .session-badge{ display:inline-block; }
            /* session id display (visible by default; hidden only on small screens below) */
            #sessionIdDisplay{ display:inline-block; }
            /* make the top banner occupy full width */
            #sessionNoticeBanner{ position: sticky; top: 0; z-index: 1100; border-radius: 0; }
            .prechat-actions .login-btn{ width:100%; }
            .prechat-actions .prechat-help{ text-align:left; }
            .chat-panel .login-card { max-height: calc(100vh - 24px); padding: 12px; }
            .message-content { max-width: 85%; }

            /* mobile: make chat panel full-screen when active */
            .chat-panel.fullscreen { position: fixed; inset: 0; z-index: 1050; background: #fff; padding: 0; }
            .chat-panel.fullscreen .login-card { max-height: 100vh; border-radius: 0; margin: 0; max-width: 100%; }
            .chat-input { padding: 10px; background: #fff; }
            .send-icon { width:18px; height:18px; }
            .send-btn { display: inline-flex; align-items:center; justify-content:center; width:40px; height:40px; border-radius:8px; padding:0; }

        /* Make conversation header wrap on narrow viewports so session id doesn't clip */
        .conversation-header { flex-wrap: wrap; gap:6px; }
        /* Hide the small session id element so only the main panel title shows the id */
        #sessionIdDisplay { display: none !important; }
        /* On very small screens allow the id to break to a second line rather than overflow */
        @media (max-width:420px){
            #sessionIdDisplay { max-width: 100%; white-space: normal; overflow-wrap: anywhere; }
            .conversation-header > div:last-child { width: 100%; display:flex; justify-content:space-between; align-items:center; }
        }

        /* send button and icon (desktop) */
        .send-btn { display:inline-flex; align-items:center; justify-content:center; }
        .send-icon { width:18px; height:18px; }

        /* Mobile fullscreen adjustments: ensure messages area is scrollable and ticket-card fills the viewport */
        .chat-panel.fullscreen .conversation-messages { 
            max-height: calc(100vh - 200px); 
            overflow-y: auto; 
            -webkit-overflow-scrolling: touch;
            padding-bottom: 80px; /* leave space for any controls or overlay */
        }
        /* Ensure messages container on non-fullscreen small screens also has sensible max height */
        @media (max-width:767px){
            #messages.conversation-messages { max-height: calc(100vh - 220px); overflow-y:auto; -webkit-overflow-scrolling: touch; }
            .ticket-card { width:100%; box-sizing:border-box; }
        }

        /* Pre-chat: keep the form centered and responsive on small screens (not fullscreen) */
        @media (max-width:767px){
            .prechat-card { max-width: 540px; margin: 12px auto; padding: 0 12px; }
            .prechat-card .login-card { padding: 20px; box-sizing: border-box; }
            .prechat-grid { gap: 12px; }
            .prechat-actions { display:flex; gap:12px; align-items:center; justify-content:space-between; margin-top:12px; position:relative; }
        }

    </style>
    
</head>
<body class="prechat-body">
    <main class="login-container prechat-card">
        <section class="login-card">
            <div class="d-flex align-items-center gap-3">
                <img id="preChatLogo" src="assets/img/its_logo.png" alt="ITS" style="width:56px;height:56px;border-radius:8px;object-fit:cover;border:1px solid rgba(0,0,0,0.04)">
                <div>
                    <h2 class="login-title" style="margin:0; font-size:14px; font-weight:600; color:var(--text-secondary);">ITS Biñan</h2>
                    <p class="login-subtitle" style="margin:0; font-size:18px; font-weight:700; color:var(--text-color);">Pre-Chat Form</p>
                </div>
            </div>

            <form id="preChatForm" class="mt-3">
                <div class="prechat-grid">
                    <div class="prechat-field">
                        <label class="form-label" for="firstName">First Name</label>
                        <input type="text" id="firstName" maxlength="100" class="form-control login-input" placeholder="First name" required>
                    </div>

                    <div class="prechat-field">
                        <label class="form-label" for="lastName">Last Name</label>
                        <input type="text" id="lastName" maxlength="100" class="form-control login-input" placeholder="Last name" required>
                    </div>

                    <div class="prechat-field">
                        <label class="form-label">Identity type</label>
                        <div>
                            <label class="me-3"><input type="radio" name="userType" value="student" checked> Student</label>
                            <label><input type="radio" name="userType" value="employee"> Employee</label>
                        </div>
                    </div>

                    <div class="prechat-field" id="studentField">
                        <label class="form-label" for="studentNumber">Student Number</label>
                        <input type="text" id="studentNumber" maxlength="30" class="form-control login-input" placeholder="e.g. 25-1234-567">
                        <div class="prechat-help">Format: 25-1234-567 or 1-241-12345</div>
                    </div>

                    <div class="prechat-field" id="employeeField" style="display:none;">
                        <label class="form-label" for="employeeId">Employee Number</label>
                        <input type="text" id="employeeId" maxlength="40" class="form-control login-input" placeholder="e.g. EMP-12345">
                    </div>

                    <div class="prechat-field wide-field">
                        <label class="form-label" for="userLocation">Room / Department</label>
                        <input type="text" id="userLocation" maxlength="100" class="form-control login-input" placeholder="e.g. Lab 201 / IT Dept" required autocomplete="off">
                    </div>

                    <div class="prechat-field wide-field issue-field">
                        <label class="form-label" for="userConcern">Concern</label>
                        <textarea id="userConcern" maxlength="4000" rows="6" class="form-control login-input" placeholder="Describe your concern in detail" required></textarea>
                        <div class="prechat-help">Provide a clear, detailed description to help technicians respond effectively.</div>
                    </div>
                </div>

                <div class="prechat-actions">
                    <div>
                        <div class="prechat-help">All fields are required.</div>
                    </div>
                    <div>
                        <button type="submit" class="btn login-btn">Submit Request</button>
                    </div>
                </div>
            </form>
        </section>
    </main>

    <main class="chat-panel fullwidth" id="chatPanel" style="display:none;">
        <div class="login-card">
            <div class="conversation-header d-flex justify-content-between align-items-center mb-3">
                <div style="display:flex;align-items:center;gap:10px;">
                    <img id="chatLogo" src="assets/img/its_logo.png" alt="ITS" style="width:42px;height:42px;border-radius:8px;object-fit:cover;border:1px solid rgba(0,0,0,0.04)">
                    <div>
                        <h4 id="panelTitle" style="margin:0; font-size:18px;">Chat with ITS Technician</h4>
                        <div class="meta" id="panelMeta">Connecting...</div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <code id="sessionIdDisplay" style="background:transparent;border:none;color:var(--text-secondary);font-weight:600;margin-left:8px"></code>
                    <button id="copySessionBtn" class="btn btn-sm btn-outline-light">Copy</button>
                </div>
            </div>

            <!-- Prominent session notice (always shown near the top) -->
            <div id="sessionNoticeBanner" class="alert alert-info mb-3" role="status" style="display:none; text-align:center; font-weight:600;"></div>

            <div id="messages" class="conversation-messages" style="min-height:320px; max-height:60vh; overflow:auto; padding:14px; border-radius:10px; border:1px solid var(--border-color); background:#fbfdff;"></div>

            <div class="chat-input mt-3">
                <!-- Banner moved to top; keep placeholder for layout consistency -->
                <div style="height:0;visibility:hidden;">&nbsp;</div>
            </div>
        </div>
    </main>

    <script>
        // Simple localStorage-backed demo chat session (hardened)
        function genId() { return 's' + Date.now() + Math.random().toString(36).slice(2,6); }

        const preForm = document.getElementById('preChatForm');
        const chatPanel = document.getElementById('chatPanel');
        const messagesEl = document.getElementById('messages');
        const panelTitle = document.getElementById('panelTitle');
        const panelMeta = document.getElementById('panelMeta');
        const copySessionBtn = document.getElementById('copySessionBtn');

        let sessionId = null;
        let lastSentTs = 0; // rate limit
        const RATE_LIMIT_MS = 3000; // 1 message per 3s
        const MAX_MESSAGE_LEN = 1000;

        function sanitizeInput(str){
            if(!str) return '';
            // remove angle brackets and control chars
            return String(str).replace(/[<>]/g, '').replace(/[\x00-\x1F\x7F]/g, '').trim();
        }

        // toggle identifier fields (also manage disabled state so hidden required controls don't block submission)
        function updateIdentifierFields(){
            const val = (document.querySelector('input[name="userType"]:checked') || {}).value || 'student';
            const studentEl = document.getElementById('studentField');
            const employeeEl = document.getElementById('employeeField');
            const studentInput = document.getElementById('studentNumber');
            const employeeInput = document.getElementById('employeeId');
            if(val === 'student'){
                studentEl.style.display = '';
                employeeEl.style.display = 'none';
                if(studentInput){ studentInput.required = true; studentInput.disabled = false; }
                if(employeeInput){ employeeInput.required = false; employeeInput.disabled = true; }
            } else {
                studentEl.style.display = 'none';
                employeeEl.style.display = '';
                if(studentInput){ studentInput.required = false; studentInput.disabled = true; }
                if(employeeInput){ employeeInput.required = true; employeeInput.disabled = false; }
            }
        }
        document.querySelectorAll('input[name="userType"]').forEach(r => r.addEventListener('change', updateIdentifierFields));
        // initialize on load
        updateIdentifierFields();

        preForm.addEventListener('submit', async function(e){
            e.preventDefault();
            const rawFirst = document.getElementById('firstName').value;
            const rawLast = document.getElementById('lastName').value;
            const rawLocation = document.getElementById('userLocation').value;
            const rawConcern = document.getElementById('userConcern').value;
            const userType = document.querySelector('input[name="userType"]:checked').value;
            const rawStudent = document.getElementById('studentNumber') ? document.getElementById('studentNumber').value : '';
            const rawEmployee = document.getElementById('employeeId') ? document.getElementById('employeeId').value : '';
            const first = sanitizeInput(rawFirst).slice(0,100);
            const last = sanitizeInput(rawLast).slice(0,100);
            const location = sanitizeInput(rawLocation).slice(0,100);
            const concern = sanitizeInput(rawConcern).slice(0,4000);
            const student = sanitizeInput(rawStudent).slice(0,30);
            const employee = sanitizeInput(rawEmployee).slice(0,40);
            // require all fields
            if(!first || !last || !location || !concern) return alert('Please complete all required fields.');
            // validate identifier based on type
            let identifier_type = null, identifier = '';
            if(userType === 'student'){
                const studentRegex = /^(?:\d{2}-\d{4}-\d{3}|\d{1}-\d{3}-\d{5})$/;
                if(!student || !studentRegex.test(student)) return alert('Please provide your student number using an allowed format: 25-1234-567 or 1-241-12345.');
                identifier_type = 'student'; identifier = student;
            } else {
                const empRegex = /^[A-Za-z0-9\-]{3,40}$/;
                if(!employee || !empRegex.test(employee)) return alert('Please provide a valid employee number (3–40 alphanumeric characters).');
                identifier_type = 'employee'; identifier = employee;
            }
            // attempt server-side logging to capture client IP; fallback to client id if server fails
            const name = (first + ' ' + last).trim();
            const payload = { name, location, issue: concern, contact: '', identifier_type, identifier };
            let serverSessionId = null;
            try {
                const resp = await fetch('api/chat_start.php', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                if (resp.ok) {
                    const j = await resp.json();
                    if (j && j.session_id) serverSessionId = j.session_id;
                }
            } catch (err) {
                console.warn('Server logging failed', err);
            }

            sessionId = serverSessionId || genId();
            const meta = { id: sessionId, name, location, issue: concern, contact: '', identifier_type, identifier, created: new Date().toISOString(), ended: false };
            localStorage.setItem('chat_meta_' + sessionId, JSON.stringify(meta));
            const firstText = `Name: ${name}\n${identifier_type === 'student' ? ('Student Number: ' + identifier) : ('Employee Number: ' + identifier)}\nRoom/Department: ${location}\nConcern:\n${concern}`;
            const firstTs = new Date().toISOString();
            localStorage.setItem('chat_messages_' + sessionId, JSON.stringify([{ sender:'user', text:firstText, ts: firstTs }]));
            // post initial message to server so it's visible to technicians/browser instances
            (async ()=>{
                try {
                    await fetch('api/chat_message.php', {
                        method: 'POST', headers: {'Content-Type':'application/json'},
                        body: JSON.stringify({ session_id: sessionId, sender: 'user', text: firstText, ts: firstTs })
                    });
                } catch(e){ console.warn('Failed to post initial message', e); }
                startChat(meta);
            })();
        });

        function startChat(meta){
            // hide the entire pre-chat card (header + form)
            const prechatCard = document.querySelector('.prechat-card');
            if (prechatCard) prechatCard.style.display = 'none';
            // show chat panel and expand to full width
            chatPanel.style.display = '';
            // on small screens, switch to fullscreen mode
            if (window.innerWidth <= 767) {
                chatPanel.classList.add('fullscreen');
                try{ document.body.classList.add('chat-fullscreen-active'); }catch(e){}
            }
            else { chatPanel.style.maxWidth = '100%'; chatPanel.style.width = '100%'; chatPanel.style.margin = '0'; }
            // show session id as the primary title
            panelTitle.textContent = meta.id || 'Chat';
            panelMeta.textContent = meta.name + ' • ' + meta.location;
            try{ const sidEl = document.getElementById('sessionIdDisplay'); if(sidEl) sidEl.textContent = meta.id || ''; }catch(e){}
            // show initial banner immediately so user sees it first
            try{ setBanner('info', 'Your request was submitted. Please wait for the notification that a technician is on the way.'); }catch(e){}
            renderMessages();
            // poll for updates
            window.chatPoll = setInterval(renderMessages, 2000);
                // focus input for user convenience
                setTimeout(()=>{
                    const inp = document.getElementById('messageInput'); if(inp) inp.focus();
                }, 120);

                // session id/copy removed from header
                updateChatStatus();
        }

        function getMeta(){
            try { return JSON.parse(localStorage.getItem('chat_meta_' + sessionId) || 'null'); } catch(e){ return null; }
        }

        function updateChatStatus(){
            const meta = getMeta();
            if(!meta){ panelMeta.textContent = 'Connecting...'; return; }
            let txt = (meta.name && meta.name.length ? meta.name : 'Guest') + ' • ' + (meta.location || '');
            // flagged indicator removed
            panelMeta.textContent = txt;
            // enable input unless session ended
            const ended = !!meta.ended;
            const inp = document.getElementById('messageInput');
            const btn = document.getElementById('sendBtn');
            if(inp) inp.disabled = ended;
            if(btn) btn.disabled = ended || !(inp && inp.value && inp.value.trim());
            return txt;
        }

        function renderMessages(){
            if(!sessionId) return;
            const raw = localStorage.getItem('chat_messages_' + sessionId) || '[]';
            const arr = JSON.parse(raw);
            messagesEl.innerHTML = '';
            const sessionMeta = getMeta();

            // Render a single ticket card summarizing the request
            const ticket = document.createElement('div'); ticket.className = 'ticket-card';
            const header = document.createElement('div'); header.className = 'ticket-header';
            const title = document.createElement('div'); title.className = 'ticket-title'; title.textContent = (sessionMeta && sessionMeta.name) ? sessionMeta.name : 'Guest';
            const meta = document.createElement('div'); meta.className = 'ticket-meta'; meta.textContent = (sessionMeta && sessionMeta.location) ? sessionMeta.location + ' • ' + new Date((sessionMeta.created||new Date()).toString()).toLocaleString() : '';
            header.appendChild(title); header.appendChild(meta);
            ticket.appendChild(header);

            // find the initial user message as the ticket body
            const userMsg = arr.find(m => m.sender === 'user') || arr[0] || { text: '' };
            const body = document.createElement('div'); body.className = 'ticket-body'; body.textContent = userMsg.text || '';
            ticket.appendChild(body);

            // timeline: show tech messages (if any)
            const techMsgs = arr.filter(m => m.sender !== 'user');
            if (techMsgs.length) {
                const timeline = document.createElement('div'); timeline.className = 'ticket-timeline';
                techMsgs.forEach(tm => {
                    const item = document.createElement('div'); item.className = 'timeline-item';
                    const ts = tm.ts ? new Date(tm.ts).toLocaleString() : '';
                    item.textContent = (tm.sender || 'tech') + ' • ' + ts + ' — ' + tm.text;
                    timeline.appendChild(item);
                });
                ticket.appendChild(timeline);
            }

            messagesEl.appendChild(ticket);
            messagesEl.scrollTop = messagesEl.scrollHeight;
            // also periodically ensure server-side messages are merged
            (async ()=>{
                try{
                    const r = await fetch('api/chat_message.php?session_id=' + encodeURIComponent(sessionId), { cache:'no-store' });
                    if(r.ok){
                        const serverMsgs = await r.json();
                        // merge server messages into localStorage if missing
                        const key = 'chat_messages_' + sessionId;
                        const local = JSON.parse(localStorage.getItem(key) || '[]');
                        const ids = new Set(local.map(m=> (m.ts||'') + '::' + (m.text||'') ));
                        let changed = false;
                        serverMsgs.forEach(sm=>{
                            const id = (sm.ts||'') + '::' + (sm.text||'');
                            if(!ids.has(id)){
                                local.push({ sender: sm.sender||'tech', text: sm.text||'', ts: sm.ts||sm.received_at });
                                changed = true; ids.add(id);
                            }
                        });
                        if(changed) localStorage.setItem(key, JSON.stringify(local));
                        // after merging messages, fetch authoritative session status so we can surface
                        // technician notifications like "on the way" without relying on tech messages.
                        try{
                            (async ()=>{
                                try{
                                    const r2 = await fetch('api/chat_sessions.php?session_id=' + encodeURIComponent(sessionId) + '&show_ended=1', { cache:'no-store' });
                                    if(r2.ok){
                                        const j = await r2.json();
                                        if(j && j.sessions && j.sessions[sessionId]){
                                            const s = j.sessions[sessionId];
                                            // update local meta with status/flagged/ended
                                            try{
                                                const metaKey = 'chat_meta_' + sessionId;
                                                const metaRaw = localStorage.getItem(metaKey);
                                                const meta = metaRaw ? JSON.parse(metaRaw) : {};
                                                if(s.status) meta.status = s.status;
                                                // flagged handling removed
                                                if (typeof s.ended !== 'undefined') {
                                                    // DB/JSON may return numeric fields as strings (e.g. "0"),
                                                    // avoid coercing "0" -> true. Only treat explicit 1/true as ended.
                                                    meta.ended = (s.ended === true || s.ended === 1 || s.ended === '1' || s.ended === 'true');
                                                }
                                                // Always prefer server-provided name/location/issue when present
                                                if(s.name && String(s.name).length) meta.name = s.name;
                                                if(s.location && String(s.location).length) meta.location = s.location;
                                                if(s.issue && String(s.issue).length) meta.issue = s.issue;
                                                localStorage.setItem(metaKey, JSON.stringify(meta));
                                                // Handle ended session first and show full-screen overlay then cleanup
                                                if(meta.ended){
                                                    try{ await showOverlay('Session ended. Returning to the request form...', 2000); }catch(e){}
                                                    try{ localStorage.removeItem('chat_meta_' + sessionId); }catch(e){}
                                                    try{ localStorage.removeItem('chat_messages_' + sessionId); }catch(e){}
                                                    try{ closeChatLocal(); }catch(e){}
                                                    return;
                                                }
                                                // Only update banner for known technician statuses
                                                if(s.status === 'on_the_way' || s.status === 'on-the-way' || s.status === 'on the way'){
                                                    setBanner('success', 'A technician is on the way.');
                                                } else if(s.status === 'pending'){
                                                    setBanner('warning', 'Request marked pending by technician.');
                                                }
                                            }catch(e){/* ignore meta write errors */}
                                        }
                                    }
                                }catch(e){/* ignore */}
                            })();
                        }catch(e){}
                    }
                }catch(e){ /* ignore */ }
            })();
            // If a technician sent an "on the way" notification, surface it to the user
            try{
                const hasOnWay = Array.isArray(arr) && arr.some(m => m.sender === 'tech' && /on the way/i.test(m.text));
                if(hasOnWay){ setBanner('success', 'A technician is on the way.'); }
            }catch(e){ /* ignore */ }
            // if session marked ended, disable input
            const metaRaw = localStorage.getItem('chat_meta_' + sessionId);
            if(metaRaw){
                const meta = JSON.parse(metaRaw);
                if(meta.ended){
                    document.getElementById('messageInput') && (document.getElementById('messageInput').disabled = true);
                    document.getElementById('sendBtn') && (document.getElementById('sendBtn').disabled = true);
                    panelMeta.textContent = (meta.name || 'Guest') + ' • ' + meta.location + ' (session ended)';
                    // show a clear ended notice (do not revert to generic wait message)
                    try{
                        const notice = document.getElementById('waitNotice');
                        if(notice){
                            notice.className = 'alert alert-secondary mb-0';
                            notice.textContent = 'Session ended. Returning to the request form...';
                        }
                    }catch(e){}
                    // Clean up local entries and return to pre-chat form
                    setTimeout(function(){
                        try{ localStorage.removeItem('chat_meta_' + sessionId); }catch(e){}
                        try{ localStorage.removeItem('chat_messages_' + sessionId); }catch(e){}
                        try{ closeChatLocal(); }catch(e){}
                    }, 800);
                    return;
                }
                // update status depending on whether technician has replied
                const statusEl = document.getElementById('chatStatus');
                const hasTech = Array.isArray(arr) && arr.some(m => m.sender === 'tech');
                if(statusEl) statusEl.textContent = hasTech ? 'Technician joined' : 'Waiting for technician';
            }
        }

        // Banner and overlay helpers
        function setBanner(type, text){
            try{
                const b = document.getElementById('sessionNoticeBanner'); if(!b) return;
                const cls = (type === 'success') ? 'alert alert-success mb-3' : (type === 'warning') ? 'alert alert-warning mb-3' : (type === 'secondary') ? 'alert alert-secondary mb-3' : 'alert alert-info mb-3';
                b.className = cls; b.textContent = text; b.style.display = ''; 
            }catch(e){}
        }
        function clearBanner(){ try{ const b=document.getElementById('sessionNoticeBanner'); if(b) b.style.display='none'; }catch(e){}
        }
        function showOverlay(text, duration){
            return new Promise((resolve)=>{
                try{
                    const o = document.getElementById('sessionNoticeOverlay'); if(!o) return resolve();
                    const t = document.getElementById('overlayText'); if(t) t.textContent = text;
                    o.style.display = 'flex';
                    setTimeout(()=>{ o.style.display = 'none'; resolve(); }, duration || 1500);
                }catch(e){ resolve(); }
            });
        }

        // Reply system removed: users cannot send follow-up messages.

        function closeChatLocal(){
            try{ if(window.chatPoll) clearInterval(window.chatPoll); }catch(e){}
            sessionId = null;
            try{
                chatPanel.style.display = 'none';
                chatPanel.classList.remove('fullscreen');
                try{ document.body.classList.remove('chat-fullscreen-active'); }catch(e){}
                // restore prechat body styles when returning to the request form
                try{ /* restore prechat-body handled by page load */ }catch(e){}
                chatPanel.style.maxWidth = '';
                chatPanel.style.width = '';
                chatPanel.style.margin = '';
                const prechatCard = document.querySelector('.prechat-card');
                if (prechatCard) prechatCard.style.display = '';
                // reset and clear the pre-chat form so fields are empty when returned
                try{ preForm.reset(); }catch(e){}
                try{ updateIdentifierFields(); }catch(e){}
                try{ document.getElementById('sessionIdDisplay').textContent = ''; }catch(e){}
                // reset wait notice
                try{ const notice = document.getElementById('waitNotice'); if(notice){ notice.className='alert alert-info mb-0'; notice.textContent = 'Your request was submitted. Please wait for the notification that a technician is on the way.'; } }catch(e){}
                preForm.style.display='block';
            }catch(e){ /* ignore DOM errors */ }
        }

        // copy session id handler
        if(copySessionBtn){
            copySessionBtn.addEventListener('click', function(){
                const sid = sessionId || (document.getElementById('sessionIdDisplay') && document.getElementById('sessionIdDisplay').textContent);
                if(!sid) return alert('No session id available');
                navigator.clipboard && navigator.clipboard.writeText(sid).then(()=>{
                    const old = copySessionBtn.textContent; copySessionBtn.textContent = 'Copied'; setTimeout(()=> copySessionBtn.textContent = old, 1500);
                }).catch(()=> alert('Copy failed'));
            });
        }
    </script>
    <style>
        /* Full-screen overlay shown when session ends so user can read message */
        #sessionNoticeOverlay{
            position:fixed; inset:0; background:rgba(0,0,0,0.6); display:none; align-items:center; justify-content:center; z-index:2000;
        }
        #sessionNoticeOverlay .overlay-card{ background:#fff; padding:28px; border-radius:12px; max-width:720px; width:90%; text-align:center; box-shadow:0 10px 30px rgba(0,0,0,0.35); }
        #sessionNoticeOverlay .overlay-card h2{ margin:0 0 8px; }
        #sessionNoticeOverlay .overlay-card p{ margin:0; color:#333; }
    </style>
    <div id="sessionNoticeOverlay"><div class="overlay-card"><h2 id="overlayTitle">Session ended</h2><p id="overlayText">Returning to the request form...</p></div></div>
</body>
</html>