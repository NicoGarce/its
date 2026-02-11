<?php
require_once 'includes/require_login.php';
require_once 'includes/helpers.php';

// Only admin may access
if (!isset($user['role']) || $user['role'] !== 'admin') {
    http_response_code(403);
    echo "<h3 style='padding:20px;color:#fff;background:#8b0000'>403 Forbidden — admins only</h3>";
    exit;
}

// Use Post-Redirect-Get to avoid resubmission prompts
$message = null;
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $flashMsg = null;
    $flashErr = null;
    if ($action === 'add') {
        $username = sanitizeInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = sanitizeInput($_POST['role'] ?? 'user');
        if (!$username || !$password) {
            $flashErr = 'Username and password are required.';
        } else {
            // check uniqueness
            $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $flashErr = 'Username already exists.';
            } else {
                $hash = hashPassword($password);
                $ins = $pdo->prepare('INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)');
                $ins->execute([$username, $hash, $role]);
                $flashMsg = 'User created.';
            }
        }
    } elseif ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $username = sanitizeInput($_POST['username'] ?? '');
        $role = sanitizeInput($_POST['role'] ?? 'user');
        $password = $_POST['password'] ?? '';
        if (!$id || !$username) {
            $flashErr = 'Invalid data.';
        } else {
            $params = [];
            $sql = 'UPDATE users SET username = ?, role = ?';
            $params[] = $username;
            $params[] = $role;
            if ($password) {
                $sql .= ', password_hash = ?';
                $params[] = hashPassword($password);
            }
            $sql .= ' WHERE id = ?';
            $params[] = $id;
            $upd = $pdo->prepare($sql);
            $upd->execute($params);
            $flashMsg = 'User updated.';
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            $flashErr = 'Invalid user id.';
        } elseif ($id == $user['userId']) {
            $flashErr = 'You cannot delete your own account.';
        } else {
            $del = $pdo->prepare('DELETE FROM users WHERE id = ?');
            $del->execute([$id]);
            $flashMsg = 'User deleted.';
        }
    }

    // Store flash messages and redirect to avoid duplicate POST on refresh
    if (session_status() === PHP_SESSION_NONE) session_start();
    if ($flashMsg) $_SESSION['flash_message'] = $flashMsg;
    if ($flashErr) $_SESSION['flash_error'] = $flashErr;
    header('Location: accounts.php');
    exit;
}

// Retrieve any flash messages set after redirect
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['flash_message'])) { $message = $_SESSION['flash_message']; unset($_SESSION['flash_message']); }
if (isset($_SESSION['flash_error'])) { $error = $_SESSION['flash_error']; unset($_SESSION['flash_error']); }

$users = $pdo->query('SELECT id, username, role FROM users ORDER BY id ASC')->fetchAll();

?>
<!DOCTYPE html>
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
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">New User</button>
                </div>
            </header>

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-dark table-hover">
                            <thead>
                                <tr>
                                    <th class="hide-mobile">ID</th>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td class="hide-mobile"><?php echo $u['id']; ?></td>
                                    <td><?php echo htmlspecialchars($u['username']); ?></td>
                                    <td><?php echo htmlspecialchars($u['role']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-secondary" data-edit='<?php echo json_encode($u); ?>'>Edit</button>
                                        <?php if ($u['id'] != $user['userId']): ?>
                                            <form method="post" action="accounts.php" style="display:inline-block;margin-left:6px" onsubmit="return confirm('Delete this user?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                                <button class="btn btn-sm btn-danger">Delete</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Modal -->
    <div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content bg-dark">
          <div class="modal-header">
            <h5 class="modal-title">Create New User</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form method="post" action="accounts.php">
          <div class="modal-body">
                <input type="hidden" name="action" value="add">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input name="username" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-control">
                        <option value="user">user</option>
                        <option value="admin">admin</option>
                    </select>
                </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Create</button>
          </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content bg-dark">
          <div class="modal-header">
            <h5 class="modal-title">Edit User</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form method="post" action="accounts.php">
          <div class="modal-body">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit-id">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input name="username" id="edit-username" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">New password (leave blank to keep)</label>
                    <input type="password" name="password" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Role</label>
                    <select name="role" id="edit-role" class="form-control">
                        <option value="user">user</option>
                        <option value="admin">admin</option>
                    </select>
                </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Save</button>
          </div>
          </form>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('[data-edit]').forEach(function(btn){
            btn.addEventListener('click', function(){
                var data = JSON.parse(this.getAttribute('data-edit'));
                document.getElementById('edit-id').value = data.id;
                document.getElementById('edit-username').value = data.username;
                document.getElementById('edit-role').value = data.role || 'user';
                var editModal = new bootstrap.Modal(document.getElementById('editModal'));
                editModal.show();
            });
        });
    </script>
</body>
</html>
