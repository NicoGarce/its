<?php
require_once 'includes/require_login.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ITS System - Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="assets/img/favicon.ico" type="image/x-icon">
    <link rel="icon" type="image/png" href="assets/img/its_logo.png">
    <link rel="apple-touch-icon" href="assets/img/its_logo.png">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="container-fluid">
            <header class="page-header">
                <div>
                    <h1 class="page-title">Inventory Management</h1>
                    <p class="text-muted small">Track items, quantities and documentation</p>
                </div>
                <div class="page-actions d-flex align-items-center gap-2">
                    <!-- actions intentionally left blank -->
                </div>
            </header>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card inventory-card">
                        <div class="card-body">
                            <h5 class="card-title">Inventory</h5>
                            <p class="text-muted small">Quick links for inventory items (e.g. HDMI, Mouse, Keyboard).</p>
                            <input id="inventorySearch" class="form-control form-control-sm mb-2" placeholder="Search inventory...">
                            <div id="inventory-items" class="d-flex flex-wrap gap-2"></div>
                            <div class="mt-3">
                                <button id="editInventoryItems" class="btn btn-primary btn-sm">Edit Inventory Items</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card documents-card">
                        <div class="card-body">
                            <h5 class="card-title">Documents</h5>
                            <p class="text-muted small">Quick links for documents (e.g. Request Forms).</p>
                            <input id="documentsSearch" class="form-control form-control-sm mb-2" placeholder="Search documents...">
                            <div id="documents-items" class="d-flex flex-wrap gap-2"></div>
                            <div class="mt-3">
                                <button id="editDocumentList" class="btn btn-primary btn-sm">Edit Document List</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

                                <!-- Add/edit item modal -->
                                <div class="modal fade" id="itemModal" tabindex="-1" aria-labelledby="itemModalLabel" aria-hidden="true">
                                    <div class="modal-dialog modal-sm">
                                        <div class="modal-content bg-white text-dark">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="itemModalLabel">Add Item</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-2">
                                                    <label for="itemLabel" class="form-label">Label</label>
                                                    <input type="text" id="itemLabel" class="form-control" placeholder="e.g. HDMI">
                                                </div>
                                                <div class="mb-2">
                                                    <label for="itemUrl" class="form-label">URL</label>
                                                    <input type="url" id="itemUrl" class="form-control" placeholder="https://...">
                                                    <input type="hidden" id="itemCategory" value="">
                                                    <input type="hidden" id="itemIndex" value="-1">
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="button" id="saveItemBtn" class="btn btn-primary">Save</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Bulk edit modal: editable two-column list for labels and links -->
                                <div class="modal fade" id="bulkModal" tabindex="-1" aria-labelledby="bulkModalLabel" aria-hidden="true">
                                    <div class="modal-dialog modal-lg modal-dialog-centered">
                                        <div class="modal-content bg-dark text-light">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="bulkModalLabel">Edit Items</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p class="small text-muted">Edit labels and links inline. Use <strong>Add Row</strong> to create new entries, and <strong>Save All</strong> to apply changes.</p>
                                                <div class="d-flex gap-2 mb-2">
                                                            <input id="bulkSearch" class="form-control form-control-sm" placeholder="Search rows...">
                                                            <select id="bulkPageSize" class="form-select form-select-sm" style="width:110px">
                                                        <option value="5">5 / page</option>
                                                        <option value="10" selected>10 / page</option>
                                                        <option value="25">25 / page</option>
                                                    </select>
                                                            <div class="ms-auto">
                                                                <button id="addBulkRowTop" type="button" class="btn btn-primary" style="min-width:140px">Add Row</button>
                                                            </div>
                                                </div>
                                                <div id="bulkList" class="bulk-list">
                                                    <!-- populated dynamically: .bulk-row elements -->
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center mt-2">
                                                    <div class="small text-muted" id="bulkMeta">Showing <span id="bulkRange">0</span> of <span id="bulkTotal">0</span></div>
                                                    <div>
                                                        <button id="bulkPrev" class="btn btn-sm btn-outline-secondary me-1">Prev</button>
                                                        <span id="bulkPageList" class="mx-1"></span>
                                                        <button id="bulkNext" class="btn btn-sm btn-outline-secondary ms-1">Next</button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <div class="me-auto d-flex gap-2">
                                                    <button type="button" id="addBulkRowBtn" class="btn btn-primary" style="display:none; min-width:140px">Add Row</button>
                                                </div>
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="button" id="saveBulkBtn" class="btn btn-primary">Save All</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="assets/js/main.js"></script>
        <script>
            // Server-backed item management for categories: inventory and documents
            function escapeHtml(s){ return (s+'').replace(/[&<>"']/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

            async function apiGet(category){
                const res = await fetch('api/items.php?category='+encodeURIComponent(category));
                return await res.json();
            }
            async function apiCreate(obj){
                const res = await fetch('api/items.php', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(obj)});
                return await res.json();
            }
            async function apiUpdate(id,obj){
                const res = await fetch('api/items.php?id='+encodeURIComponent(id), { method: 'PUT', headers: {'Content-Type':'application/json'}, body: JSON.stringify(obj)});
                return await res.json();
            }
            async function apiDelete(id){
                const res = await fetch('api/items.php?id='+encodeURIComponent(id), { method: 'DELETE' });
                return await res.json();
            }

            // Cache fetched items per kind to enable fast client-side searching
            const itemsCache = { inventory: [], documents: [] };

            async function renderItems(){
                for (const kind of ['inventory','documents']){
                    // fetch and cache
                    try{
                        itemsCache[kind] = await apiGet(kind) || [];
                    }catch(e){ itemsCache[kind] = []; }
                    renderKind(kind);
                }
                bindItemButtons();
            }

            // Render a single kind using cache and current search filter
            function renderKind(kind){
                const container = document.getElementById(kind + '-items');
                container.innerHTML = '';
                const qEl = document.getElementById(kind === 'inventory' ? 'inventorySearch' : 'documentsSearch');
                const q = (qEl && qEl.value || '').toLowerCase().trim();
                const items = itemsCache[kind] || [];
                const filtered = q ? items.filter(it => (((it.label||'') + ' ' + (it.url||'')).toLowerCase().includes(q))) : items;
                filtered.forEach(it => {
                    const id = it.id;
                    const btnWrap = document.createElement('div');
                    btnWrap.className = 'd-inline-block';
                    btnWrap.innerHTML = `
                        <div class="btn-group">
                            <button class="btn btn-outline-light btn-sm open-item" data-id="${id}" data-label="${escapeHtml(it.label)}" data-url="${escapeHtml(it.url || '')}">${escapeHtml(it.label)}</button>
                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="visually-hidden">Toggle</span>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item edit-item" href="#" data-id="${id}" data-kind="${kind}">Edit</a></li>
                                <li><a class="dropdown-item delete-item" href="#" data-id="${id}" data-kind="${kind}">Delete</a></li>
                            </ul>
                        </div>
                    `;
                    container.appendChild(btnWrap);
                });
                bindItemButtons();
            }

            function bindItemButtons(){
                document.querySelectorAll('.open-item').forEach(b=>{
                    b.onclick = e => {
                        const url = b.getAttribute('data-url');
                        if (url) window.open(url,'_blank','noopener');
                    };
                });
                document.querySelectorAll('.edit-item').forEach(a=>{
                    a.onclick = async e => {
                        e.preventDefault();
                        const id = a.getAttribute('data-id');
                        const kind = a.getAttribute('data-kind');
                        // find item from DOM button data attributes
                        const btn = document.querySelector('.open-item[data-id="'+id+'"]');
                        if (!btn) return;
                        document.getElementById('itemLabel').value = btn.getAttribute('data-label') || '';
                        document.getElementById('itemUrl').value = btn.getAttribute('data-url') || '';
                        document.getElementById('itemCategory').value = kind;
                        document.getElementById('itemIndex').value = id;
                        document.getElementById('itemModalLabel').textContent = 'Edit Item';
                        new bootstrap.Modal(document.getElementById('itemModal')).show();
                    };
                });
                document.querySelectorAll('.delete-item').forEach(a=>{
                    a.onclick = async e => {
                        e.preventDefault();
                        const id = a.getAttribute('data-id');
                        if (!confirm('Delete this item?')) return;
                        await apiDelete(id);
                        renderItems();
                    };
                });
            }

            // Bulk-edit modal population for inventory/documents (clean flex rows)
            // Bulk modal state for filtering & pagination
            let bulkItems = [];
            let bulkFiltered = [];
            let bulkPage = 1;
            let bulkPageSize = 10;

            async function populateBulkModal(category, title) {
                bulkItems = await apiGet(category) || [];
                bulkPage = 1;
                bulkPageSize = parseInt(document.getElementById('bulkPageSize').value, 10) || 10;
                document.getElementById('bulkSearch').value = '';
                // set category for save handler
                document.getElementById('bulkModal').dataset.category = category;
                document.getElementById('bulkModalLabel').textContent = title;
                renderBulkPage(1);
                // ensure top add button is visible and footer add is hidden initially
                const topBtn = document.getElementById('addBulkRowTop');
                const footerBtn = document.getElementById('addBulkRowBtn');
                if (topBtn) topBtn.style.display = '';
                if (footerBtn) footerBtn.style.display = 'none';
                new bootstrap.Modal(document.getElementById('bulkModal')).show();
            }

            function applyBulkFilter(){
                const q = (document.getElementById('bulkSearch').value || '').toLowerCase().trim();
                if(!q) bulkFiltered = bulkItems.slice();
                else bulkFiltered = bulkItems.filter(it => ((it.label||'') + ' ' + (it.url||'')).toLowerCase().includes(q));
                bulkPage = 1;
            }

            function renderBulkPage(page){
                applyBulkFilter();
                bulkPageSize = parseInt(document.getElementById('bulkPageSize').value, 10) || 10;
                const total = bulkFiltered.length;
                const pages = Math.max(1, Math.ceil(total / bulkPageSize));
                if(page < 1) page = 1; if(page > pages) page = pages;
                bulkPage = page;
                const start = (page-1)*bulkPageSize;
                const end = Math.min(total, start + bulkPageSize);
                const list = document.getElementById('bulkList');
                list.innerHTML = '';
                for(let i=start;i<end;i++){
                    const it = bulkFiltered[i];
                    list.appendChild(createBulkRow(it.id || '', it.label || '', it.url || ''));
                }
                document.getElementById('bulkRange').textContent = (total===0?0: (start+1) + '–' + end);
                document.getElementById('bulkTotal').textContent = total;
                buildBulkPageList(pages, bulkPage);
            }

            function buildBulkPageList(pages, active){
                const el = document.getElementById('bulkPageList'); el.innerHTML = '';
                const maxButtons = 5;
                let start = Math.max(1, active - Math.floor(maxButtons/2));
                let end = Math.min(pages, start + maxButtons - 1);
                if(end - start < maxButtons -1) start = Math.max(1, end - maxButtons + 1);
                for(let p=start;p<=end;p++){
                    const btn = document.createElement('button');
                    btn.className = 'btn btn-sm ' + (p===active ? 'btn-primary' : 'btn-outline-secondary');
                    btn.textContent = p;
                    btn.addEventListener('click', ()=> renderBulkPage(p));
                    el.appendChild(btn);
                }
            }

            document.getElementById('editInventoryItems').addEventListener('click', ()=>{
                populateBulkModal('inventory','Edit Inventory Items');
            });

            document.getElementById('editDocumentList').addEventListener('click', ()=>{
                populateBulkModal('documents','Edit Document List');
            });

            function createBulkRow(id, label, url) {
                const row = document.createElement('div');
                row.className = 'bulk-row d-flex gap-2 align-items-center py-2';
                if (id) row.dataset.id = id;

                const badgeWrap = document.createElement('div');
                badgeWrap.className = 'me-2';
                const badge = document.createElement('span');
                badge.className = 'badge';
                badge.textContent = id ? id : 'new';
                badgeWrap.appendChild(badge);

                const inpLabel = document.createElement('input');
                inpLabel.type = 'text';
                inpLabel.className = 'form-control form-control-sm flex-grow-1';
                inpLabel.value = label;

                const inpLink = document.createElement('input');
                inpLink.type = 'url';
                inpLink.className = 'form-control form-control-sm flex-grow-1';
                inpLink.value = url;

                const actionWrap = document.createElement('div');
                actionWrap.style.width = '48px';
                const delBtn = document.createElement('button');
                delBtn.type = 'button';
                delBtn.className = 'btn btn-sm btn-outline-danger row-delete';
                delBtn.title = 'Delete';
                delBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path></svg>';
                actionWrap.appendChild(delBtn);

                delBtn.addEventListener('click', async (e)=>{
                    const rowEl = e.currentTarget.closest('.bulk-row');
                    const rid = rowEl.dataset.id;
                    if (rid && rid.length) {
                        if (!confirm('Delete this item?')) return;
                        await apiDelete(rid);
                        rowEl.remove();
                        return;
                    }
                    rowEl.remove();
                });

                row.appendChild(badgeWrap);
                row.appendChild(inpLabel);
                row.appendChild(inpLink);
                row.appendChild(actionWrap);
                return row;
            }

            // Add row helper: append an empty editable row, scroll to it and focus
            function addBulkRow(){
                const list = document.getElementById('bulkList');
                const r = createBulkRow('', '', '');
                list.appendChild(r);
                // scroll modal body to bottom so new row and Save button are visible
                const modalBody = document.querySelector('#bulkModal .modal-body');
                if(modalBody){ modalBody.scrollTop = modalBody.scrollHeight; }
                // focus the label input for the new row
                try{ const inp = r.querySelector('input[type="text"]'); if(inp) inp.focus(); }catch(e){}
                return r;
            }

            // Top button: adds a row and then moves the add control to the footer
            const topAdd = document.getElementById('addBulkRowTop');
            if(topAdd){
                topAdd.addEventListener('click', ()=>{
                    addBulkRow();
                    // hide top button and show footer button so user sees Save nearby
                    const footerBtn = document.getElementById('addBulkRowBtn');
                    topAdd.style.display = 'none';
                    if(footerBtn) footerBtn.style.display = 'inline-block';
                });
            }

            // Footer add button remains available after first click
            const footerAdd = document.getElementById('addBulkRowBtn');
            if(footerAdd){ footerAdd.addEventListener('click', addBulkRow); }

            // wire bulk modal search/pagination controls
            document.getElementById('bulkSearch').addEventListener('input', ()=> renderBulkPage(1));
            document.getElementById('bulkPageSize').addEventListener('change', ()=> renderBulkPage(1));
            document.getElementById('bulkPrev').addEventListener('click', ()=> renderBulkPage(bulkPage-1));
            document.getElementById('bulkNext').addEventListener('click', ()=> renderBulkPage(bulkPage+1));

            // Save all changes: iterate rows and create/update as needed
            document.getElementById('saveBulkBtn').addEventListener('click', async ()=>{
                const modal = document.getElementById('bulkModal');
                const category = modal.dataset.category || 'inventory';
                const rows = Array.from(document.querySelectorAll('#bulkList .bulk-row'));
                const promises = [];
                for (const r of rows) {
                    const id = r.dataset.id || '';
                    const label = (r.querySelector('input[type="text"]') || {}).value || '';
                    const url = (r.querySelector('input[type="url"]') || {}).value || '';
                    if (!label.trim()) continue; // skip empty rows
                    if (id && id.length) {
                        promises.push(apiUpdate(id, { label: label.trim(), url: url.trim() }));
                    } else {
                        promises.push(apiCreate({ category: category, label: label.trim(), url: url.trim() }));
                    }
                }
                await Promise.all(promises);
                bootstrap.Modal.getInstance(modal).hide();
                renderItems();
            });

            document.getElementById('saveItemBtn').addEventListener('click', async ()=>{
                const label = document.getElementById('itemLabel').value.trim();
                const url = document.getElementById('itemUrl').value.trim();
                const cat = document.getElementById('itemCategory').value;
                const id = document.getElementById('itemIndex').value;
                if (!label) { alert('Label is required'); return; }
                if (id && parseInt(id) > 0) {
                    await apiUpdate(id, { label: label, url: url });
                } else {
                    await apiCreate({ category: cat, label: label, url: url });
                }
                bootstrap.Modal.getInstance(document.getElementById('itemModal')).hide();
                renderItems();
            });

            // Top-right search and refresh removed from header.

            // Wire per-card search inputs
            document.getElementById('inventorySearch').addEventListener('input', ()=> renderKind('inventory'));
            document.getElementById('documentsSearch').addEventListener('input', ()=> renderKind('documents'));

            // Initialize
            renderItems();

        </script>
</body>
</html>