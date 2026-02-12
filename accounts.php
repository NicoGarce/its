<?php
require_once 'includes/require_login.php';
require_once 'includes/db.php';
require_once 'includes/helpers.php';

if (!isset($user['role']) || $user['role'] !== 'admin') {
    http_response_code(403);
    echo "<h3 style='padding:20px;color:#fff;background:#8b0000'>403 Forbidden — admins only</h3>";
    exit;
}

// Non-JS fallback
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['noscript_action'])) {
    $action = $_POST['noscript_action'];
    $flashMsg = null; $flashErr = null;
    try {
        if ($action === 'add') {
            $username = sanitizeInput($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = sanitizeInput($_POST['role'] ?? 'user');
            if (!$username || !$password) { $flashErr = 'Username and password are required.'; }
            else {
                $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?'); $stmt->execute([$username]);
                if ($stmt->fetch()) $flashErr = 'Username already exists.';
                else { $ins = $pdo->prepare('INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)'); $ins->execute([$username, hashPassword($password), $role]); $flashMsg = 'User created.'; }
            }
        } elseif ($action === 'edit') {
            $id = (int)($_POST['id'] ?? 0); $username = sanitizeInput($_POST['username'] ?? ''); $role = sanitizeInput($_POST['role'] ?? 'user'); $password = $_POST['password'] ?? '';
            if (!$id || !$username) $flashErr = 'Invalid data.';
            else {
                $chk = $pdo->prepare('SELECT id FROM users WHERE username = ? AND id != ?'); $chk->execute([$username, $id]);
                if ($chk->fetch()) $flashErr = 'Username already exists.';
                else { $params = [$username, $role]; $sql='UPDATE users SET username = ?, role = ?'; if ($password) { $sql .= ', password_hash = ?'; $params[] = hashPassword($password); } $sql .= ' WHERE id = ?'; $params[] = $id; $upd = $pdo->prepare($sql); $upd->execute($params); $flashMsg = 'User updated.'; }
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) $flashErr = 'Invalid user id.';
            elseif ($id == $user['userId']) $flashErr = 'You cannot delete your own account.';
            else { $del = $pdo->prepare('DELETE FROM users WHERE id = ?'); $del->execute([$id]); $flashMsg = 'User deleted.'; }
        }
    } catch (PDOException $e) { error_log('accounts.php fallback: ' . $e->getMessage()); $flashErr = 'Database error.'; }
    if (session_status() === PHP_SESSION_NONE) session_start(); if ($flashMsg) $_SESSION['flash_message']=$flashMsg; if ($flashErr) $_SESSION['flash_error']=$flashErr;
    header('Location: ' . $_SERVER['PHP_SELF']); exit;
}

if (session_status() === PHP_SESSION_NONE) session_start(); $message = $_SESSION['flash_message'] ?? null; if (isset($_SESSION['flash_message'])) unset($_SESSION['flash_message']); $error = $_SESSION['flash_error'] ?? null; if (isset($_SESSION['flash_error'])) unset($_SESSION['flash_error']);

$users = $pdo->query('SELECT id, username, role FROM users ORDER BY id ASC')->fetchAll();
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$apiUrl = $base . '/api/accounts.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Accounts Management</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="icon" href="assets/img/favicon.ico" type="image/x-icon">
  <link rel="icon" type="image/png" href="assets/img/its_logo.png">
  <link rel="apple-touch-icon" href="assets/img/its_logo.png">
  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    /* Page-scoped modern, muted palette */
    :root{--acct-text:#cfd8e3;--muted:#9aa4b2;--row-bg:rgba(255,255,255,0.02);--row-hover:rgba(255,255,255,0.03);--primary-50:rgba(59,130,246,0.07);--primary-200:rgba(59,130,246,0.18);--danger-10:rgba(235,87,87,0.06);--danger-60:#ff6b6b}
    /* Slightly more compact table spacing for denser display */
    .table-modern{width:100%;border-collapse:separate;border-spacing:0 6px}
    .table-modern thead th{background:transparent;color:var(--muted);font-weight:600;border-bottom:0;padding:8px 10px;text-align:left}
    .table-modern tbody tr{background:var(--row-bg);border-radius:8px;box-shadow:0 1px 0 rgba(0,0,0,0.18);transition:transform .08s ease,box-shadow .12s ease}
    .table-modern tbody tr td{border:0;padding:8px;vertical-align:middle;color:var(--acct-text);font-size:14px}
    .table-modern tbody tr:hover{transform:translateY(-1px);box-shadow:0 6px 12px rgba(0,0,0,0.35);background:var(--row-hover)}
    .btn-modern{background:transparent;border:1px solid rgba(255,255,255,0.04);color:var(--acct-text);padding:.4rem .7rem;border-radius:10px;font-weight:600;letter-spacing:0.2px}
    .btn-modern:hover{background:var(--primary-50);border-color:var(--primary-200);color:var(--acct-text)}
    .btn-modern.primary{background:var(--primary-50);border-color:var(--primary-200)}
    /* Icon-only small buttons used inside table */
    .btn-icon{width:30px;height:30px;padding:0;display:inline-flex;align-items:center;justify-content:center;border-radius:6px}
    .btn-icon svg{display:block}
    /* Muted danger: outline with subtle red tint rather than bright filled gradient */
    .btn-modern.danger{background:transparent;border:1px solid rgba(235,87,87,0.14);color:var(--danger-60)}
    .btn-modern.danger:hover{background:var(--danger-10);color:var(--danger-60);border-color:rgba(235,87,87,0.28)}
    .cell-username,.cell-role{font-weight:600;color:var(--acct-text)}
    #page-alerts .alert{margin-bottom:12px}
    /* Cell label/value helpers */
    .cell{display:flex;align-items:center;gap:10px}
    .cell-label{display:none;font-size:12px;color:var(--muted);font-weight:600}
    .cell-value{display:inline-block}

    /* Compact spacing for actions on narrow screens: show labels above values */
    @media (max-width:700px){
      .table-modern thead th{display:none}
      .table-modern tbody tr td{display:block;padding:8px}
      .cell{display:block}
      .cell-label{display:block;margin-bottom:6px}
      .cell-value{display:block}
    }
  </style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="main-content">
  <div class="container-fluid">
    <header class="page-header">
      <div>
        <h1 class="page-title">Accounts</h1>
        <p class="text-muted small">Manage user accounts and roles</p>
      </div>
      <div class="page-actions">
        <button class="btn-modern" data-bs-toggle="modal" data-bs-target="#addModal">New User</button>
      </div>
    </header>
  <div id="page-alerts">
    <?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
  </div>

  <div class="card"><div class="card-body"><div class="table-responsive"><table class="table-modern"><thead><tr><th>ID</th><th>Username</th><th>Role</th><th>Actions</th></tr></thead><tbody id="accounts-tbody">
  <?php foreach ($users as $u): ?>
    <tr data-id="<?php echo $u['id']; ?>">
      <td>
        <div class="cell"><span class="cell-label">ID</span><span class="cell-value"><?php echo $u['id']; ?></span></div>
      </td>
      <td>
        <div class="cell"><span class="cell-label">Username</span><span class="cell-value cell-username"><?php echo htmlspecialchars($u['username']); ?></span></div>
      </td>
      <td>
        <div class="cell"><span class="cell-label">Role</span><span class="cell-value cell-role"><?php echo htmlspecialchars($u['role']); ?></span></div>
      </td>
      <td>
        <button class="btn-modern btn-icon btn-edit" data-user='<?php echo json_encode($u); ?>' aria-label="Edit user <?php echo htmlspecialchars($u['username']); ?>">
          <i class="fa-solid fa-pen" aria-hidden="true"></i>
        </button>
        <?php if ($u['id'] != $user['userId']): ?>
          <button class="btn-modern btn-icon danger btn-delete" data-id="<?php echo $u['id']; ?>" aria-label="Delete user <?php echo htmlspecialchars($u['username']); ?>">
            <i class="fa-solid fa-trash" aria-hidden="true"></i>
          </button>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody></table></div></div></div>
</div></div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content bg-dark"><div class="modal-header"><h5 class="modal-title">Create</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <form id="addForm"><div class="modal-body"><div class="mb-3"><label>Username</label><input name="username" class="form-control" required></div><div class="mb-3"><label>Password</label><input type="password" name="password" class="form-control" required></div><div class="mb-3"><label>Role</label><select name="role" class="form-control"><option value="user">user</option><option value="admin">admin</option></select></div></div><div class="modal-footer"><button type="button" class="btn-modern" data-bs-dismiss="modal">Cancel</button><button class="btn-modern" type="submit">Create</button></div></form>
</div></div></div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content bg-dark"><div class="modal-header"><h5 class="modal-title">Edit</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <form id="editForm"><div class="modal-body"><input type="hidden" name="id" id="edit-id"><div class="mb-3"><label>Username</label><input name="username" id="edit-username" class="form-control" required></div><div class="mb-3"><label>New password</label><input type="password" name="password" class="form-control"></div><div class="mb-3"><label>Role</label><select name="role" id="edit-role" class="form-control"><option value="user">user</option><option value="admin">admin</option></select></div></div><div class="modal-footer"><button type="button" class="btn-modern" data-bs-dismiss="modal">Cancel</button><button class="btn-modern" type="submit">Save</button></div></form>
</div></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const apiUrl = '<?php echo htmlspecialchars($apiUrl, ENT_QUOTES); ?>';
function showAlert(type,text){const el=document.createElement('div');el.className='alert alert-'+(type==='ok'?'success':'danger');el.textContent=text;const container=document.getElementById('page-alerts')||document.querySelector('.container-fluid');container.prepend(el);setTimeout(()=>el.remove(),4000);}

document.getElementById('addForm').addEventListener('submit',async function(e){e.preventDefault();const fd=new FormData(this);fd.append('action','add');try{const res=await fetch(apiUrl,{method:'POST',body:fd,credentials:'same-origin'});const data=await res.json();if(data.success){ showAlert('ok',data.message||'Created'); if(data.id){ addRowToTable(data.id,data.username,data.role); } setTimeout(()=>{ var m = document.querySelector('#addModal .btn-close'); if(m) m.click(); },200); } else showAlert('err',data.message||'Error'); }catch(e){console.error(e);showAlert('err','Network error');}});

function bindRowEvents(row){
  const editBtn = row.querySelector('.btn-edit');
  const deleteBtn = row.querySelector('.btn-delete');
  if(editBtn){ editBtn.addEventListener('click', function(){ const u = JSON.parse(this.getAttribute('data-user')); document.getElementById('edit-id').value = u.id; document.getElementById('edit-username').value = u.username; document.getElementById('edit-role').value = u.role || 'user'; new bootstrap.Modal(document.getElementById('editModal')).show(); }); }
  if(deleteBtn){ deleteBtn.addEventListener('click', async function(){ if(!confirm('Delete this user?')) return; const id = this.getAttribute('data-id'); const fd = new FormData(); fd.append('action','delete'); fd.append('id',id); try{ const res = await fetch(apiUrl,{method:'POST',body:fd,credentials:'same-origin'}); const data = await res.json(); if(data.success){ showAlert('ok',data.message||'Deleted'); removeRowFromTable(id); } else showAlert('err',data.message||'Error'); }catch(e){ console.error(e); showAlert('err','Network error'); } }); }
}

document.querySelectorAll('tbody#accounts-tbody tr').forEach(r=>bindRowEvents(r));


document.getElementById('editForm').addEventListener('submit',async function(e){e.preventDefault();const fd=new FormData(this);fd.append('action','edit');try{const res=await fetch(apiUrl,{method:'POST',body:fd,credentials:'same-origin'});const data=await res.json();if(data.success){ showAlert('ok',data.message||'Saved'); if(data.id){ updateRowInTable(data.id,data.username,data.role); } setTimeout(()=>{ var m = document.querySelector('#editModal .btn-close'); if(m) m.click(); },200); } else showAlert('err',data.message||'Error'); }catch(e){console.error(e);showAlert('err','Network error');}});

// utility functions to mutate table without reload
function addRowToTable(id, username, role){
  const tbody = document.getElementById('accounts-tbody');
  if(!tbody) return;
  const tr = document.createElement('tr'); tr.setAttribute('data-id', id);
  tr.innerHTML = `
    <td><div class="cell"><span class="cell-label">ID</span><span class="cell-value">${id}</span></div></td>
    <td><div class="cell"><span class="cell-label">Username</span><span class="cell-value cell-username">${escapeHtml(username)}</span></div></td>
    <td><div class="cell"><span class="cell-label">Role</span><span class="cell-value cell-role">${escapeHtml(role)}</span></div></td>
    <td>
      <button class="btn-modern btn-icon btn-edit" data-user='${JSON.stringify({id: id, username: username, role: role})}' aria-label="Edit user ${escapeHtml(username)}"><i class="fa-solid fa-pen" aria-hidden="true"></i></button>
      <button class="btn-modern btn-icon danger btn-delete" data-id="${id}" aria-label="Delete user ${escapeHtml(username)}"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
    </td>`;
  tbody.appendChild(tr);
  bindRowEvents(tr);
}

function updateRowInTable(id, username, role){
  const tr = document.querySelector(`tbody#accounts-tbody tr[data-id="${id}"]`);
  if(!tr) return;
  const userBtn = tr.querySelector('.btn-edit');
  tr.querySelector('.cell-username').textContent = username;
  tr.querySelector('.cell-role').textContent = role;
  if(userBtn){ userBtn.setAttribute('data-user', JSON.stringify({id: id, username: username, role: role})); }
}

function removeRowFromTable(id){
  const tr = document.querySelector(`tbody#accounts-tbody tr[data-id="${id}"]`);
  if(tr && tr.parentNode) tr.parentNode.removeChild(tr);
}

function escapeHtml(str){ return String(str).replace(/[&<>"]+/g, function(s){ return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[s]); }); }
</script>
</body>
</html>
