<?php
/**
 * accounts.php — System admin: create Kapitan and Member accounts.
 */

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/users.php';
require_once __DIR__ . '/includes/layout.php';

$user = auth_require_admin();
$repo = new UserRepository();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate()) {
        csrf_fail_redirect('accounts.php');
    }

    $action = $_POST['action'] ?? 'create';

    if ($action === 'create') {
        $fullName = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $role = trim($_POST['role'] ?? '');

        if ($fullName === '' || $username === '' || $password === '' || !isset(USER_ROLES[$role])) {
            flash_set('error', 'All fields are required.');
        } elseif (strlen($password) < 6) {
            flash_set('error', 'Password must be at least 6 characters.');
        } elseif ($repo->usernameExists($username)) {
            flash_set('error', 'Username already exists.');
        } elseif ($repo->create($fullName, $username, $password, $role)) {
            flash_set('success', 'Account created for ' . USER_ROLES[$role] . '.');
        } else {
            flash_set('error', 'Could not create account.');
        }
    }

    if ($action === 'toggle') {
        $id = (int) ($_POST['user_id'] ?? 0);
        $newStatus = $_POST['new_status'] ?? '';
        if ($id === (int) $user['id']) {
            flash_set('error', 'You cannot disable your own admin account.');
        } elseif ($repo->setStatus($id, $newStatus)) {
            flash_set('success', 'Account status updated.');
        } else {
            flash_set('error', 'Could not update status.');
        }
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['user_id'] ?? 0);
        if ($id === (int) $user['id']) {
            flash_set('error', 'You cannot delete your own account.');
        } elseif ($repo->delete($id)) {
            flash_set('success', 'Account deleted.');
        } else {
            flash_set('error', 'Could not delete account.');
        }
    }

    header('Location: accounts.php');
    exit;
}

$accounts = $repo->listAll();
$staff = array_values(array_filter($accounts, static fn ($a) => ($a['role'] ?? '') !== 'admin'));

$pending = 0;
try {
    require_once __DIR__ . '/includes/repository.php';
    $pending = (new DocumentRepository())->pendingApprovalCount();
} catch (Throwable $e) {
    app_log_exception($e, 'accounts pending count');
}

layout_begin('Manage Accounts', 'accounts', $user, $pending);
layout_topbar('Manage Accounts');
?>
<div class="content">
    <?= flash_render() ?>

    <p class="content-lead">
        Create accounts for the Kapitan and barangay members. They use the same sign-in page with the usernames you assign.
        <a href="search.php">Open All Documents</a> to use filing, approval, and archive features (same as the design mockup).
    </p>

    <div class="accounts-grid">
        <div class="panel-box">
            <h3>Create new account</h3>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create">
                <div class="form-group">
                    <label for="full_name">Full name</label>
                    <input type="text" id="full_name" name="full_name" required>
                </div>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role" required>
                        <?php foreach (USER_ROLES as $key => $label): ?>
                            <option value="<?= e($key) ?>"><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-primary"><i class="ti ti-user-plus"></i> Create account</button>
            </form>
        </div>

        <div class="panel-box" style="border:none;padding:0;">
            <h3>Staff accounts (<?= count($staff) ?>)</h3>
            <?php if ($staff === []): ?>
                <div class="empty-state">No staff accounts yet. Create Kapitan and member accounts above.</div>
            <?php else: ?>
                <div class="table-responsive">
                <table class="doc-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($staff as $acc): ?>
                        <tr>
                            <td><?= e($acc['full_name'] ?? '') ?></td>
                            <td><?= e($acc['username'] ?? '') ?></td>
                            <td><?= e(USER_ROLES[$acc['role'] ?? ''] ?? $acc['role']) ?></td>
                            <td>
                                <span class="status-badge <?= ($acc['status'] ?? '') === 'active' ? 'status-approved' : 'status-returned' ?>">
                                    <?= e($acc['status'] ?? '') ?>
                                </span>
                            </td>
                            <td>
                                <form method="post" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="user_id" value="<?= (int) $acc['id'] ?>">
                                    <?php if (($acc['status'] ?? '') === 'active'): ?>
                                        <input type="hidden" name="new_status" value="disabled">
                                        <button type="submit" class="btn-reject" style="font-size:11px;">Disable</button>
                                    <?php else: ?>
                                        <input type="hidden" name="new_status" value="active">
                                        <button type="submit" class="btn-approve" style="font-size:11px;">Enable</button>
                                    <?php endif; ?>
                                </form>
                                <form method="post" style="display:inline;margin-left:6px;" onsubmit="return confirm('Delete this account?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="user_id" value="<?= (int) $acc['id'] ?>">
                                    <button type="submit" class="btn-reject" style="font-size:11px;">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php layout_end(); ?>
