<?php
require_once 'includes/require_login.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Archived Chats</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="assets/img/favicon.ico" type="image/x-icon">
    <link rel="icon" type="image/png" href="assets/img/its_logo.png">
    <link rel="apple-touch-icon" href="assets/img/its_logo.png">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content p-3">
        <div class="container-fluid">
            <header class="page-header mb-3 d-flex align-items-start">
                <div>
                    <h1 class="page-title">Chat Archive</h1>
                    <p class="text-muted small">Browse and delete chat sessions in bulk.</p>
                </div>
                <div class="ms-auto">
                    <a href="tech_chat.php" class="btn btn-sm btn-outline-light me-2">Back to Tech Chat</a>
                </div>
            </header>

            <div class="card mb-3">
                        <div class="card-body">
                            <style>
                                /* Polished dark table styles matching app UI */
                                .card { background: #0f1720; border: none; box-shadow: 0 6px 18px rgba(2,16,24,0.5); color: #e6eef8; border-radius:8px; }
                                .card .card-body{ padding:1rem; }

                                /* container for the table keeps subtle inset padding */
                                .table-responsive{ background: transparent; padding:6px; border-radius:6px; }

                                /* table base */
                                #archiveTable{ width:100%; border-collapse:separate; border-spacing:0; background:transparent !important; }
                                #archiveTable thead th{ padding:10px 12px; font-weight:600; font-size:14px; text-align:left; background: linear-gradient(180deg,#0b3b6f,#0b5ed7) !important; color:#ffffff !important; border-bottom:2px solid rgba(255,255,255,0.06); }
                                #archiveTable tbody td{ padding:10px 12px; color: rgba(255,255,255,0.95) !important; }

                                /* Force dark background for cells (high specificity + !important to override global rules) */
                                .card #archiveTable.table tbody td, .card #archiveTable tbody td, #archiveTable tbody td {
                                    background: transparent !important;
                                }

                                /* row backgrounds & dividers */
                                #archiveTable tbody tr{ background: transparent !important; }
                                #archiveTable tbody tr:nth-child(odd){ background: rgba(255,255,255,0.01) !important; }
                                #archiveTable tbody tr:hover{ background: rgba(255,255,255,0.03) !important; }
                                #archiveTable td, #archiveTable th{ vertical-align:middle; border-top:1px solid rgba(255,255,255,0.03); }

                                /* compact controls */
                                .pagination-controls{ display:flex; align-items:center; gap:8px; margin-top:8px; }
                                .page-btn{ min-width:40px; }
                                .muted-small{ font-size:12px; color:rgba(255,255,255,0.85); }

                                /* page-local button styles: stronger filled variants for visibility */
                                .card .btn-primary{ background: linear-gradient(90deg,#1773e6,#0b5ed7); border: none; color: #fff; box-shadow: 0 6px 18px rgba(11,94,215,0.12); }
                                .card .btn-primary:hover{ transform: translateY(-1px); box-shadow: 0 10px 26px rgba(11,94,215,0.12); }
                                .card .btn-danger{ background: linear-gradient(90deg,#d9534f,#c9302c); border: none; color: #fff; box-shadow: 0 6px 18px rgba(217,83,79,0.08); }
                                .card .btn-danger:hover{ transform: translateY(-1px); box-shadow: 0 10px 26px rgba(217,83,79,0.12); }
                                .btn-outline-danger{ color:#ffb3b3; border-color:rgba(255,80,80,0.12); }
                                .btn-outline-danger:hover{ color:#fff; background:rgba(255,80,80,0.08); border-color:rgba(255,80,80,0.22); }
                                /* Archive modal - Option A: Tidy + subtle */
                                #archiveModal .modal-body{ padding-top:0.5rem; }
                                /* Compact metadata card */
                                #archiveModalMeta.modal-meta-card{ background: rgba(255,255,255,0.02); padding:8px; border-radius:6px; border:1px solid rgba(255,255,255,0.03); display:grid; grid-template-columns:1fr 1fr; gap:8px; }
                                #archiveModalMeta .meta-label{ font-size:12px; color: rgba(255,255,255,0.62); }
                                #archiveModalMeta .meta-value{ font-size:13px; color: #ffffff; font-weight:600; }

                                /* Message list: simple rows with subtle dividers */
                                #archiveMessages .archive-msg{ display:flex; gap:12px; align-items:flex-start; padding:8px 0; border-bottom:1px solid rgba(255,255,255,0.03); }
                                #archiveMessages .archive-meta{ width:170px; flex-shrink:0; font-size:12px; color:rgba(255,255,255,0.6); }
                                #archiveMessages .archive-body{ flex:1; color: rgba(230,238,248,0.95); white-space:pre-wrap; }

                                #archiveModal .modal-header { border-bottom: 1px solid rgba(255,255,255,0.06); }
                                #archiveModalTitle { font-weight:600; }
                            </style>
                    <div class="d-flex gap-2 mb-2">
                        <button id="refreshBtn" class="btn btn-sm btn-primary">Refresh</button>
                        <button id="deleteSelectedBtn" class="btn btn-sm btn-danger" disabled>Delete Selected</button>
                        <button id="flushBtn" class="btn btn-sm btn-outline-danger">Flush Archive</button>
                        <div class="form-check ms-auto">
                            <input class="form-check-input" type="checkbox" id="showEnded" checked>
                            <label class="form-check-label" for="showEnded">Show ended sessions</label>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-hover" id="archiveTable">
                            <thead>
                                <tr>
                                    <th style="width:36px"><input id="selectAll" type="checkbox"></th>
                                    <th>Session ID</th>
                                    <th>Name</th>
                                    <th class="hide-mobile">Location</th>
                                    <th class="hide-mobile">Created</th>
                                    <th>Status</th>
                                    <th style="width:140px">Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="muted-small">Showing <span id="metaRange">0</span> of <span id="metaTotal">0</span></div>
                        <div class="pagination-controls">
                            <button id="prevPage" class="btn btn-sm btn-outline-secondary page-btn">Prev</button>
                            <div id="pageList" class="d-flex gap-1"></div>
                            <button id="nextPage" class="btn btn-sm btn-outline-secondary page-btn">Next</button>
                            <select id="pageSize" class="form-select form-select-sm" style="width:86px">
                                <option value="10">10 / page</option>
                                <option value="25">25 / page</option>
                                <option value="50">50 / page</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Modal to preview archived chat (placed here so it's a top-level element, not inside scroll containers) -->
    <div class="modal fade" id="archiveModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header">
                    <h5 class="modal-title" id="archiveModalTitle">Archived Chat</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="archiveModalMeta" class="text-muted small mb-2"></div>
                    <div id="archiveMessages" style="max-height:60vh; overflow:auto;">
                        <div class="text-muted small">Loading...</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-light" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <script>
            const tableBody = document.querySelector('#archiveTable tbody');
        const archiveModalEl = document.getElementById('archiveModal');
        const archiveModalTitleEl = document.getElementById('archiveModalTitle');
        const archiveModalMetaEl = document.getElementById('archiveModalMeta');
        const archiveMessagesEl = document.getElementById('archiveMessages');
        const bsArchiveModal = new bootstrap.Modal(archiveModalEl, { keyboard: true });
        const refreshBtn = document.getElementById('refreshBtn');
        const deleteBtn = document.getElementById('deleteSelectedBtn');
        const flushBtn = document.getElementById('flushBtn');
        const selectAll = document.getElementById('selectAll');
        const showEnded = document.getElementById('showEnded');

        let sessionsData = [];
        let currentPage = 1;
        let pageSizeVal = parseInt(document.getElementById('pageSize').value, 10) || 10;

        async function fetchSessions(){
            const qs = showEnded.checked ? '?show_ended=1' : '';
            const r = await fetch('api/chat_sessions.php' + qs, { cache: 'no-store' });
            if(!r.ok) return [];
            const j = await r.json();
            return j && j.sessions ? Object.values(j.sessions) : [];
        }

        function renderRow(s){
            const tr = document.createElement('tr');
            tr.dataset.sid = s.session_id;
            const tdSelect = document.createElement('td');
            tdSelect.innerHTML = `<input class="row-select" type="checkbox" value="${s.session_id}">`;
            tr.appendChild(tdSelect);
            tr.appendChild(tdCell(s.session_id));
            tr.appendChild(tdCell(s.name || 'Guest'));
            // Location and Created columns are less important on small screens
            tr.appendChild(tdCell(s.location || '', 'hide-mobile'));
            tr.appendChild(tdCell(s.received_at || s.created || '', 'hide-mobile'));
            tr.appendChild(tdCell((s.status || 'open')));
            const actions = document.createElement('td');
            actions.innerHTML = `<div class="d-flex gap-1"><button class="btn btn-sm btn-outline-light open-btn" data-sid="${encodeURIComponent(s.session_id)}">Open</button><button class="btn btn-sm btn-danger del-btn">Delete</button></div>`;
            tr.appendChild(actions);
            return tr;
        }

        function tdCell(txt, cls){ const td = document.createElement('td'); if(cls) td.className = cls; td.textContent = txt || ''; return td; }

        async function load(){
            tableBody.innerHTML = '';
            selectAll.checked = false;
            sessionsData = await fetchSessions();
            sessionsData.sort((a,b)=> new Date(b.received_at||b.created||0)-new Date(a.received_at||a.created||0));
            currentPage = 1;
            pageSizeVal = parseInt(document.getElementById('pageSize').value, 10) || 10;
            renderPage(currentPage);
        }

        function renderPage(page){
            const total = sessionsData.length;
            const size = pageSizeVal;
            const pages = Math.max(1, Math.ceil(total / size));
            if(page < 1) page = 1; if(page > pages) page = pages;
            currentPage = page;
            tableBody.innerHTML = '';
            const start = (page-1)*size;
            const end = Math.min(total, start + size);
            const pageItems = sessionsData.slice(start, end);
            pageItems.forEach(s=> tableBody.appendChild(renderRow(s)));
            wireRowEvents();
            updateButtons();
            document.getElementById('metaRange').textContent = (total===0?0:(start+1) + '–' + end);
            document.getElementById('metaTotal').textContent = total;
            buildPageList(pages, page);
        }

        function buildPageList(pages, active){
            const pageList = document.getElementById('pageList'); pageList.innerHTML = '';
            const maxButtons = 7;
            let start = Math.max(1, active - Math.floor(maxButtons/2));
            let end = Math.min(pages, start + maxButtons - 1);
            if(end - start < maxButtons -1) start = Math.max(1, end - maxButtons + 1);
            for(let p=start;p<=end;p++){
                const btn = document.createElement('button');
                btn.className = 'btn btn-sm ' + (p===active ? 'btn-primary' : 'btn-outline-secondary');
                btn.textContent = p;
                btn.addEventListener('click', ()=> renderPage(p));
                pageList.appendChild(btn);
            }
        }

        function wireRowEvents(){
            document.querySelectorAll('.row-select').forEach(cb=> cb.addEventListener('change', updateButtons));
            document.querySelectorAll('.open-btn').forEach(btn=> btn.addEventListener('click', async function(){
                const sid = this.dataset && this.dataset.sid ? decodeURIComponent(this.dataset.sid) : null;
                if(!sid) return;
                // set modal title/meta from session list if available
                const sessionObj = sessionsData.find(it => it.session_id === sid) || null;
                archiveModalTitleEl.textContent = sessionObj && sessionObj.name ? `Archived — ${sessionObj.name}` : `Archived — ${sid}`;
                archiveModalMetaEl.textContent = sessionObj ? (`Session: ${sessionObj.session_id} · ${sessionObj.location || ''} · ${sessionObj.received_at || sessionObj.created || ''}`) : `Session: ${sid}`;
                archiveMessagesEl.innerHTML = '<div class="text-muted small">Loading messages...</div>';
                bsArchiveModal.show();
                try{
                    const r = await fetch('api/chat_messages_view.php?session_id=' + encodeURIComponent(sid), { cache: 'no-store' });
                    if(!r.ok) throw new Error('Fetch failed');
                    const msgs = await r.json();
                    renderArchiveMessages(msgs, sessionObj);
                }catch(e){
                    archiveMessagesEl.innerHTML = '<div class="text-danger">Error loading messages</div>';
                }
            }));
            document.querySelectorAll('.del-btn').forEach(btn=> btn.addEventListener('click', async function(){
                const tr = this.closest('tr'); const sid = tr && tr.dataset && tr.dataset.sid; if(!sid) return;
                if(!confirm('Delete session ' + sid + ' and its messages? This cannot be undone.')) return;
                await deleteSessions([sid]);
                load();
            }));
        }

        function renderArchiveMessages(msgs, sessionObj){
            if(!Array.isArray(msgs) || msgs.length === 0){ archiveMessagesEl.innerHTML = '<div class="text-muted">No messages found for this session.</div>'; return; }
            archiveMessagesEl.innerHTML = '';

            // Parse first message metadata if present and render as compact meta card
            const first = msgs[0];
            let startIndex = 0;
            if(first && typeof first.text === 'string'){
                const metadataRegex = /Name:\s*(.*?)\s+Student Number:\s*(.*?)\s+Room\/Department:\s*(.*?)\s+Concern:\s*(.*)/i;
                const m = first.text.match(metadataRegex);
                if(m){
                    const metaWrap = document.createElement('div');
                    metaWrap.id = 'archiveModalMeta';
                    metaWrap.className = 'modal-meta-card mb-3';
                    metaWrap.innerHTML = `
                        <div>
                            <div class="meta-label">Name</div>
                            <div class="meta-value">${escapeHtml(m[1])}</div>
                        </div>
                        <div>
                            <div class="meta-label">Student #</div>
                            <div class="meta-value">${escapeHtml(m[2])}</div>
                        </div>
                        <div>
                            <div class="meta-label">Room / Dept</div>
                            <div class="meta-value">${escapeHtml(m[3])}</div>
                        </div>
                        <div style="grid-column:1/-1">
                            <div class="meta-label">Concern</div>
                            <div class="meta-value">${escapeHtml(m[4])}</div>
                        </div>
                    `;
                    archiveMessagesEl.appendChild(metaWrap);
                    startIndex = 1;
                }
            }

            for(let i = startIndex; i < msgs.length; i++){
                const m = msgs[i];
                const wrap = document.createElement('div');
                wrap.className = 'archive-msg';
                const time = document.createElement('div');
                time.className = 'archive-meta';
                time.textContent = (m.ts || m.received_at || '') + (m.sender ? (' — ' + m.sender) : '');
                const body = document.createElement('div');
                body.className = 'archive-body';
                body.textContent = m.text || '';
                wrap.appendChild(time);
                wrap.appendChild(body);
                archiveMessagesEl.appendChild(wrap);
            }
        }

        function escapeHtml(s){
            if(!s) return '';
            return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
        }

        function updateButtons(){
            const any = Array.from(document.querySelectorAll('.row-select')).some(cb=>cb.checked);
            deleteBtn.disabled = !any;
            const all = document.querySelectorAll('.row-select').length > 0 && Array.from(document.querySelectorAll('.row-select')).every(cb=>cb.checked);
            selectAll.checked = all;
        }

        flushBtn.addEventListener('click', async function(){
            if(!confirm('Are you sure you want to permanently delete ALL archived chats? This cannot be undone.')) return;
            const token = prompt('Type FLUSH to confirm permanent deletion of all archived chats:');
            if(token !== 'FLUSH'){ alert('Flush aborted.'); return; }
            flushBtn.disabled = true;
            try{
                const r = await fetch('api/chat_flush.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ confirm: 'FLUSH' }) });
                if(!r.ok) throw new Error('Flush failed');
                const j = await r.json();
                if(!j || !j.ok) throw new Error(j && j.error ? j.error : 'Flush failed');
                alert('Flushed archive: deleted ' + (j.deleted_sessions||0) + ' sessions and ' + (j.deleted_messages||0) + ' messages.');
                load();
            }catch(e){ alert('Flush error: ' + e.message); }
            flushBtn.disabled = false;
        });

        selectAll.addEventListener('change', function(){ document.querySelectorAll('.row-select').forEach(cb=> cb.checked = selectAll.checked); updateButtons(); });
        deleteBtn.addEventListener('click', async function(){
            const ids = Array.from(document.querySelectorAll('.row-select')).filter(cb=>cb.checked).map(cb=>cb.value);
            if(!ids.length) return;
            if(!confirm('Delete ' + ids.length + ' sessions? This will remove their messages permanently.')) return;
            await deleteSessions(ids);
            load();
        });

        async function deleteSessions(ids){
            try{
                const r = await fetch('api/chat_delete.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ session_ids: ids }) });
                if(!r.ok) throw new Error('Delete failed');
                const j = await r.json();
                if(!j || !j.ok) throw new Error(j && j.error ? j.error : 'Delete failed');
                return true;
            }catch(e){ alert('Delete error: ' + e.message); return false; }
        }

        document.getElementById('prevPage').addEventListener('click', ()=> renderPage(currentPage-1));
        document.getElementById('nextPage').addEventListener('click', ()=> renderPage(currentPage+1));
        document.getElementById('pageSize').addEventListener('change', function(){ pageSizeVal = parseInt(this.value,10) || 10; renderPage(1); });
        refreshBtn.addEventListener('click', load);
        showEnded.addEventListener('change', load);

        // initial
        load();
    </script>
</body>
</html>
