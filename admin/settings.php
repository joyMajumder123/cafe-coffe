<?php
include 'includes/auth.php';
include 'includes/db.php';
require_permission('roles.view');

// Must have at least one RBAC permission to see this page
if (!has_permission('roles.view') && !has_permission('admin_users.view') && !has_permission('audit.view')) {
    $_SESSION['flash_error'] = 'You do not have permission to access settings.';
    header('Location: dashboard.php');
    exit();
}

$csrf = csrf_token();

// Fetch roles for dropdowns
$all_roles = [];
$rr = $conn->query("SELECT id, name, hierarchy_level FROM roles ORDER BY hierarchy_level DESC");
while ($r = $rr->fetch_assoc()) { $all_roles[] = $r; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings & Access Control | Cafe Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar { background-color: #2c3e50; min-height: 100vh; padding: 20px 0; }
        .sidebar .nav-link { color: #ecf0f1; padding: 12px 20px; margin: 5px 0; }
        .sidebar .nav-link:hover { background-color: #34495e; border-radius: 5px; }
        .sidebar .nav-link.active { background-color: #3498db; border-radius: 5px; }
        .perm-group { background: #f1f3f5; border-radius: 8px; padding: 12px 16px; margin-bottom: 12px; }
        .perm-group h6 { margin-bottom: 8px; color: #495057; }
        .toast-container { z-index: 1090; }
        .tab-pane { min-height: 400px; }
        .badge-level { font-size: .7rem; }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <span class="navbar-brand">☕ Cafe Admin - Settings & Access Control</span>
            <div class="d-flex align-items-center gap-3">
                <span class="text-light small"><i class="fas fa-user-circle me-1"></i><?= htmlspecialchars(rbac_display_name()) ?> <span class="badge bg-secondary"><?= htmlspecialchars(rbac_role_name()) ?></span></span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <div class="col-md-10 p-4">
                <h2 class="mb-4"><i class="fas fa-cogs me-2"></i>Settings & Access Control</h2>

                <!-- Flash messages -->
                <?php if (!empty($_SESSION['flash_error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($_SESSION['flash_error']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                    <?php unset($_SESSION['flash_error']); ?>
                <?php endif; ?>
                <?php if (!empty($_SESSION['flash_success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($_SESSION['flash_success']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                    <?php unset($_SESSION['flash_success']); ?>
                <?php endif; ?>

                <!-- TABS -->
                <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
                    <?php if (has_permission('roles.view')): ?>
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-roles" type="button"><i class="fas fa-shield-alt me-1"></i> Roles</button>
                    </li>
                    <?php endif; ?>
                    <?php if (has_permission('roles.manage')): ?>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-permissions" type="button"><i class="fas fa-key me-1"></i> Permissions</button>
                    </li>
                    <?php endif; ?>
                    <?php if (has_permission('admin_users.view')): ?>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-users" type="button"><i class="fas fa-users-cog me-1"></i> Admin Users</button>
                    </li>
                    <?php endif; ?>
                    <?php if (has_permission('invite_codes.manage')): ?>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-invites" type="button"><i class="fas fa-ticket-alt me-1"></i> Invite Codes</button>
                    </li>
                    <?php endif; ?>
                    <?php if (has_permission('audit.view')): ?>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-audit" type="button"><i class="fas fa-history me-1"></i> Audit Log</button>
                    </li>
                    <?php endif; ?>
                </ul>

                <div class="tab-content border border-top-0 rounded-bottom p-4 bg-white" id="settingsTabContent">

                    <!-- ═══════════════════════════════════════════ -->
                    <!--  TAB: ROLES                                -->
                    <!-- ═══════════════════════════════════════════ -->
                    <?php if (has_permission('roles.view')): ?>
                    <div class="tab-pane fade show active" id="tab-roles">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Roles</h5>
                            <?php if (has_permission('roles.manage')): ?>
                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createRoleModal"><i class="fas fa-plus me-1"></i> New Role</button>
                            <?php endif; ?>
                        </div>
                        <div id="roles-table-container">
                            <div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- ═══════════════════════════════════════════ -->
                    <!--  TAB: PERMISSIONS                          -->
                    <!-- ═══════════════════════════════════════════ -->
                    <?php if (has_permission('roles.manage')): ?>
                    <div class="tab-pane fade" id="tab-permissions">
                        <h5 class="mb-3">Assign Permissions to Role</h5>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <select class="form-select" id="perm-role-select">
                                    <option value="">— Select Role —</option>
                                    <?php foreach ($all_roles as $r): ?>
                                        <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?> (Level <?= $r['hierarchy_level'] ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-outline-primary" id="btn-load-perms"><i class="fas fa-sync me-1"></i> Load</button>
                            </div>
                        </div>
                        <div id="permissions-grid" class="d-none">
                            <div id="perm-groups-container"></div>
                            <div class="mt-3">
                                <button class="btn btn-success" id="btn-save-perms"><i class="fas fa-save me-1"></i> Save Permissions</button>
                                <button class="btn btn-outline-secondary ms-2" id="btn-select-all-perms">Select All</button>
                                <button class="btn btn-outline-secondary ms-1" id="btn-deselect-all-perms">Deselect All</button>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- ═══════════════════════════════════════════ -->
                    <!--  TAB: ADMIN USERS                          -->
                    <!-- ═══════════════════════════════════════════ -->
                    <?php if (has_permission('admin_users.view')): ?>
                    <div class="tab-pane fade" id="tab-users">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Admin Users</h5>
                            <?php if (has_permission('admin_users.manage')): ?>
                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createUserModal"><i class="fas fa-user-plus me-1"></i> New User</button>
                            <?php endif; ?>
                        </div>
                        <div id="users-table-container">
                            <div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- ═══════════════════════════════════════════ -->
                    <!--  TAB: INVITE CODES                         -->
                    <!-- ═══════════════════════════════════════════ -->
                    <?php if (has_permission('invite_codes.manage')): ?>
                    <div class="tab-pane fade" id="tab-invites">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Invite Codes</h5>
                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createInviteModal"><i class="fas fa-plus me-1"></i> Generate Code</button>
                        </div>
                        <div id="invites-table-container">
                            <div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- ═══════════════════════════════════════════ -->
                    <!--  TAB: AUDIT LOG                            -->
                    <!-- ═══════════════════════════════════════════ -->
                    <?php if (has_permission('audit.view')): ?>
                    <div class="tab-pane fade" id="tab-audit">
                        <h5 class="mb-3">Audit Log</h5>
                        <div id="audit-table-container">
                            <div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>
                        </div>
                        <nav id="audit-pagination" class="mt-3"></nav>
                    </div>
                    <?php endif; ?>

                </div><!-- /tab-content -->
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════ -->
    <!--  MODALS                                                    -->
    <!-- ═══════════════════════════════════════════════════════════ -->

    <!-- Create Role Modal -->
    <div class="modal fade" id="createRoleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title"><i class="fas fa-shield-alt me-2"></i>Create Role</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Role Name <span class="text-danger">*</span></label><input type="text" class="form-control" id="cr-name" required></div>
                    <div class="mb-3"><label class="form-label">Description</label><input type="text" class="form-control" id="cr-desc"></div>
                    <div class="mb-3"><label class="form-label">Hierarchy Level <small class="text-muted">(higher = more authority, must be below yours: <?= $_SESSION['admin_hierarchy'] ?? 0 ?>)</small></label><input type="number" class="form-control" id="cr-level" value="10" min="0" max="<?= max(0, ($_SESSION['admin_hierarchy'] ?? 0) - 1) ?>"></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" id="btn-create-role"><i class="fas fa-plus me-1"></i> Create</button></div>
            </div>
        </div>
    </div>

    <!-- Edit Role Modal -->
    <div class="modal fade" id="editRoleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Role</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="hidden" id="er-id">
                    <div class="mb-3"><label class="form-label">Role Name</label><input type="text" class="form-control" id="er-name"></div>
                    <div class="mb-3"><label class="form-label">Description</label><input type="text" class="form-control" id="er-desc"></div>
                    <div class="mb-3"><label class="form-label">Hierarchy Level</label><input type="number" class="form-control" id="er-level" min="0"></div>
                    <div id="er-system-note" class="alert alert-info d-none"><small>System roles: only the description can be changed.</small></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" id="btn-update-role"><i class="fas fa-save me-1"></i> Save</button></div>
            </div>
        </div>
    </div>

    <!-- Create User Modal -->
    <div class="modal fade" id="createUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Create Admin User</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Username <span class="text-danger">*</span></label><input type="text" class="form-control" id="cu-username" required></div>
                    <div class="mb-3"><label class="form-label">Email <span class="text-danger">*</span></label><input type="email" class="form-control" id="cu-email" required></div>
                    <div class="mb-3"><label class="form-label">Full Name</label><input type="text" class="form-control" id="cu-fullname"></div>
                    <div class="mb-3"><label class="form-label">Password <span class="text-danger">*</span> <small class="text-muted">(min 6 chars)</small></label><input type="password" class="form-control" id="cu-password" required></div>
                    <div class="mb-3"><label class="form-label">Role</label>
                        <select class="form-select" id="cu-role">
                            <?php foreach ($all_roles as $r): if ($r['hierarchy_level'] < ($_SESSION['admin_hierarchy'] ?? 0)): ?>
                                <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
                            <?php endif; endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" id="btn-create-user"><i class="fas fa-user-plus me-1"></i> Create</button></div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title"><i class="fas fa-user-edit me-2"></i>Edit Admin User</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="hidden" id="eu-id">
                    <div class="mb-3"><label class="form-label">Username</label><input type="text" class="form-control" id="eu-username" disabled></div>
                    <div class="mb-3"><label class="form-label">Email</label><input type="email" class="form-control" id="eu-email"></div>
                    <div class="mb-3"><label class="form-label">Full Name</label><input type="text" class="form-control" id="eu-fullname"></div>
                    <div class="mb-3"><label class="form-label">New Password <small class="text-muted">(leave blank to keep current)</small></label><input type="password" class="form-control" id="eu-password"></div>
                    <div class="mb-3"><label class="form-label">Role</label>
                        <select class="form-select" id="eu-role">
                            <?php foreach ($all_roles as $r): if ($r['hierarchy_level'] < ($_SESSION['admin_hierarchy'] ?? 0)): ?>
                                <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
                            <?php endif; endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Status</label>
                        <select class="form-select" id="eu-status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="locked">Locked</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" id="btn-update-user"><i class="fas fa-save me-1"></i> Save</button></div>
            </div>
        </div>
    </div>

    <!-- Create Invite Modal -->
    <div class="modal fade" id="createInviteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title"><i class="fas fa-ticket-alt me-2"></i>Generate Invite Code</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Role to Assign</label>
                        <select class="form-select" id="ci-role">
                            <?php foreach ($all_roles as $r): if ($r['hierarchy_level'] < ($_SESSION['admin_hierarchy'] ?? 0)): ?>
                                <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
                            <?php endif; endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Max Uses</label><input type="number" class="form-control" id="ci-maxuses" value="1" min="1" max="100"></div>
                    <div class="mb-3"><label class="form-label">Expires After (hours)</label><input type="number" class="form-control" id="ci-expires" value="48" min="1" max="720"></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" id="btn-create-invite"><i class="fas fa-ticket-alt me-1"></i> Generate</button></div>
            </div>
        </div>
    </div>

    <!-- Toast container for notifications -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" id="settings-toasts"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/order-notifications.js"></script>
    <script>
    (function() {
        'use strict';
        var CSRF = '<?= $csrf ?>';
        var MY_HIERARCHY = <?= $_SESSION['admin_hierarchy'] ?? 0 ?>;
        var MY_USER_ID = <?= $_SESSION['admin_user_id'] ?? 0 ?>;

        // ── Utility ──────────────────────────────────────────────
        function api(action, data, method) {
            method = method || 'POST';
            if (method === 'GET') {
                var qs = new URLSearchParams(data || {});
                qs.set('action', action);
                return fetch('rbac_api.php?' + qs.toString()).then(function(r) { return r.json(); });
            }
            var fd = new FormData();
            fd.append('action', action);
            fd.append('_csrf_token', CSRF);
            if (data) { Object.keys(data).forEach(function(k) { fd.append(k, data[k]); }); }
            return fetch('rbac_api.php', { method: 'POST', body: fd }).then(function(r) { return r.json(); });
        }

        function toast(msg, type) {
            type = type || 'success';
            var c = document.getElementById('settings-toasts');
            var el = document.createElement('div');
            el.className = 'toast show align-items-center text-white bg-' + (type === 'error' ? 'danger' : 'success');
            el.setAttribute('role', 'alert');
            el.innerHTML = '<div class="d-flex"><div class="toast-body">' + esc(msg) + '</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>';
            c.appendChild(el);
            setTimeout(function() { el.remove(); }, 5000);
        }

        function esc(s) {
            var d = document.createElement('div'); d.textContent = s; return d.innerHTML;
        }

        function closeModal(id) {
            var m = bootstrap.Modal.getInstance(document.getElementById(id));
            if (m) m.hide();
        }

        // ── ROLES ────────────────────────────────────────────────
        function loadRoles() {
            api('get_roles', {}, 'GET').then(function(d) {
                if (!d.success) return;
                var html = '<table class="table table-hover"><thead class="table-light"><tr><th>ID</th><th>Name</th><th>Description</th><th>Level</th><th>Users</th><th>Type</th><th>Actions</th></tr></thead><tbody>';
                d.roles.forEach(function(r) {
                    html += '<tr>';
                    html += '<td>' + r.id + '</td>';
                    html += '<td><strong>' + esc(r.name) + '</strong></td>';
                    html += '<td>' + esc(r.description) + '</td>';
                    html += '<td><span class="badge bg-info badge-level">' + r.hierarchy_level + '</span></td>';
                    html += '<td>' + r.user_count + '</td>';
                    html += '<td>' + (r.is_system == 1 ? '<span class="badge bg-secondary">System</span>' : '<span class="badge bg-primary">Custom</span>') + '</td>';
                    html += '<td>';
                    if (r.hierarchy_level < MY_HIERARCHY) {
                        html += '<button class="btn btn-sm btn-outline-primary me-1 edit-role-btn" data-id="' + r.id + '" data-name="' + esc(r.name) + '" data-desc="' + esc(r.description) + '" data-level="' + r.hierarchy_level + '" data-system="' + r.is_system + '"><i class="fas fa-edit"></i></button>';
                        if (r.is_system == 0) {
                            html += '<button class="btn btn-sm btn-outline-danger delete-role-btn" data-id="' + r.id + '" data-name="' + esc(r.name) + '"><i class="fas fa-trash"></i></button>';
                        }
                    } else {
                        html += '<span class="text-muted small">—</span>';
                    }
                    html += '</td></tr>';
                });
                html += '</tbody></table>';
                document.getElementById('roles-table-container').innerHTML = html;
                bindRoleButtons();
            });
        }

        function bindRoleButtons() {
            document.querySelectorAll('.edit-role-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    document.getElementById('er-id').value = this.dataset.id;
                    document.getElementById('er-name').value = this.dataset.name;
                    document.getElementById('er-desc').value = this.dataset.desc;
                    document.getElementById('er-level').value = this.dataset.level;
                    var isSystem = this.dataset.system == '1';
                    document.getElementById('er-name').disabled = isSystem;
                    document.getElementById('er-level').disabled = isSystem;
                    document.getElementById('er-system-note').classList.toggle('d-none', !isSystem);
                    new bootstrap.Modal(document.getElementById('editRoleModal')).show();
                });
            });
            document.querySelectorAll('.delete-role-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    if (!confirm('Delete role "' + this.dataset.name + '"? This cannot be undone.')) return;
                    api('delete_role', { role_id: this.dataset.id }).then(function(d) {
                        toast(d.message, d.success ? 'success' : 'error');
                        if (d.success) loadRoles();
                    });
                });
            });
        }

        document.getElementById('btn-create-role').addEventListener('click', function() {
            api('create_role', {
                name: document.getElementById('cr-name').value,
                description: document.getElementById('cr-desc').value,
                hierarchy_level: document.getElementById('cr-level').value
            }).then(function(d) {
                toast(d.message, d.success ? 'success' : 'error');
                if (d.success) { closeModal('createRoleModal'); loadRoles(); }
            });
        });

        document.getElementById('btn-update-role').addEventListener('click', function() {
            api('update_role', {
                role_id: document.getElementById('er-id').value,
                name: document.getElementById('er-name').value,
                description: document.getElementById('er-desc').value,
                hierarchy_level: document.getElementById('er-level').value
            }).then(function(d) {
                toast(d.message, d.success ? 'success' : 'error');
                if (d.success) { closeModal('editRoleModal'); loadRoles(); }
            });
        });

        // ── PERMISSIONS ──────────────────────────────────────────
        document.getElementById('btn-load-perms').addEventListener('click', loadPermissions);

        function loadPermissions() {
            var roleId = document.getElementById('perm-role-select').value;
            if (!roleId) { toast('Please select a role.', 'error'); return; }
            api('get_role_permissions', { role_id: roleId }, 'GET').then(function(d) {
                if (!d.success) { toast(d.message, 'error'); return; }
                var container = document.getElementById('perm-groups-container');
                var groups = {};
                d.permissions.forEach(function(p) {
                    if (!groups[p.group_name]) groups[p.group_name] = [];
                    groups[p.group_name].push(p);
                });
                var html = '';
                Object.keys(groups).forEach(function(g) {
                    html += '<div class="perm-group"><h6><i class="fas fa-folder-open me-1"></i>' + esc(g) + '</h6>';
                    groups[g].forEach(function(p) {
                        var checked = d.assigned.indexOf(parseInt(p.id)) !== -1 ? 'checked' : '';
                        html += '<div class="form-check form-check-inline">';
                        html += '<input class="form-check-input perm-checkbox" type="checkbox" id="perm-' + p.id + '" value="' + p.id + '" ' + checked + '>';
                        html += '<label class="form-check-label" for="perm-' + p.id + '">' + esc(p.label) + '</label>';
                        html += '</div>';
                    });
                    html += '</div>';
                });
                container.innerHTML = html;
                document.getElementById('permissions-grid').classList.remove('d-none');
            });
        }

        document.getElementById('btn-save-perms').addEventListener('click', function() {
            var roleId = document.getElementById('perm-role-select').value;
            var ids = [];
            document.querySelectorAll('.perm-checkbox:checked').forEach(function(cb) { ids.push(parseInt(cb.value)); });
            api('save_role_permissions', { role_id: roleId, permission_ids: JSON.stringify(ids) }).then(function(d) {
                toast(d.message, d.success ? 'success' : 'error');
            });
        });

        document.getElementById('btn-select-all-perms').addEventListener('click', function() {
            document.querySelectorAll('.perm-checkbox').forEach(function(cb) { cb.checked = true; });
        });
        document.getElementById('btn-deselect-all-perms').addEventListener('click', function() {
            document.querySelectorAll('.perm-checkbox').forEach(function(cb) { cb.checked = false; });
        });

        // ── ADMIN USERS ──────────────────────────────────────────
        function loadUsers() {
            api('get_admin_users', {}, 'GET').then(function(d) {
                if (!d.success) return;
                var html = '<table class="table table-hover"><thead class="table-light"><tr><th>ID</th><th>Username</th><th>Full Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last Login</th><th>Actions</th></tr></thead><tbody>';
                d.users.forEach(function(u) {
                    var sc = u.status === 'active' ? 'success' : (u.status === 'locked' ? 'danger' : 'secondary');
                    html += '<tr>';
                    html += '<td>' + u.id + '</td>';
                    html += '<td><strong>' + esc(u.username) + '</strong></td>';
                    html += '<td>' + esc(u.full_name || '-') + '</td>';
                    html += '<td>' + esc(u.email) + '</td>';
                    html += '<td><span class="badge bg-info">' + esc(u.role_name) + '</span></td>';
                    html += '<td><span class="badge bg-' + sc + '">' + esc(u.status.charAt(0).toUpperCase() + u.status.slice(1)) + '</span></td>';
                    html += '<td>' + (u.last_login || 'Never') + '</td>';
                    html += '<td>';
                    if (u.hierarchy_level < MY_HIERARCHY || parseInt(u.id) === MY_USER_ID) {
                        html += '<button class="btn btn-sm btn-outline-primary me-1 edit-user-btn" data-id="' + u.id + '" data-username="' + esc(u.username) + '" data-email="' + esc(u.email) + '" data-fullname="' + esc(u.full_name || '') + '" data-role="' + u.role_id + '" data-status="' + u.status + '"><i class="fas fa-edit"></i></button>';
                        if (parseInt(u.id) !== MY_USER_ID) {
                            html += '<button class="btn btn-sm btn-outline-danger delete-user-btn" data-id="' + u.id + '" data-username="' + esc(u.username) + '"><i class="fas fa-trash"></i></button>';
                        }
                    } else {
                        html += '<span class="text-muted small">—</span>';
                    }
                    html += '</td></tr>';
                });
                html += '</tbody></table>';
                document.getElementById('users-table-container').innerHTML = html;
                bindUserButtons();
            });
        }

        function bindUserButtons() {
            document.querySelectorAll('.edit-user-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    document.getElementById('eu-id').value = this.dataset.id;
                    document.getElementById('eu-username').value = this.dataset.username;
                    document.getElementById('eu-email').value = this.dataset.email;
                    document.getElementById('eu-fullname').value = this.dataset.fullname;
                    document.getElementById('eu-role').value = this.dataset.role;
                    document.getElementById('eu-status').value = this.dataset.status;
                    document.getElementById('eu-password').value = '';
                    new bootstrap.Modal(document.getElementById('editUserModal')).show();
                });
            });
            document.querySelectorAll('.delete-user-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    if (!confirm('Delete user "' + this.dataset.username + '"? This cannot be undone.')) return;
                    api('delete_admin_user', { user_id: this.dataset.id }).then(function(d) {
                        toast(d.message, d.success ? 'success' : 'error');
                        if (d.success) loadUsers();
                    });
                });
            });
        }

        document.getElementById('btn-create-user').addEventListener('click', function() {
            api('create_admin_user', {
                username: document.getElementById('cu-username').value,
                email: document.getElementById('cu-email').value,
                full_name: document.getElementById('cu-fullname').value,
                password: document.getElementById('cu-password').value,
                role_id: document.getElementById('cu-role').value
            }).then(function(d) {
                toast(d.message, d.success ? 'success' : 'error');
                if (d.success) { closeModal('createUserModal'); loadUsers(); }
            });
        });

        document.getElementById('btn-update-user').addEventListener('click', function() {
            api('update_admin_user', {
                user_id: document.getElementById('eu-id').value,
                email: document.getElementById('eu-email').value,
                full_name: document.getElementById('eu-fullname').value,
                password: document.getElementById('eu-password').value,
                role_id: document.getElementById('eu-role').value,
                status: document.getElementById('eu-status').value
            }).then(function(d) {
                toast(d.message, d.success ? 'success' : 'error');
                if (d.success) { closeModal('editUserModal'); loadUsers(); }
            });
        });

        // ── INVITE CODES ─────────────────────────────────────────
        function loadInvites() {
            api('get_invite_codes', {}, 'GET').then(function(d) {
                if (!d.success) return;
                var html = '<table class="table table-hover"><thead class="table-light"><tr><th>Code</th><th>Role</th><th>Uses</th><th>Expires</th><th>Status</th><th>Created By</th><th>Actions</th></tr></thead><tbody>';
                if (d.codes.length === 0) {
                    html += '<tr><td colspan="7" class="text-center text-muted">No invite codes yet.</td></tr>';
                }
                d.codes.forEach(function(c) {
                    var expired = c.expires_at && new Date(c.expires_at) < new Date();
                    var active = c.is_active == 1 && !expired && (c.times_used < c.max_uses);
                    html += '<tr>';
                    html += '<td><code class="fs-6">' + esc(c.code) + '</code> <button class="btn btn-sm btn-link copy-code-btn" data-code="' + esc(c.code) + '" title="Copy"><i class="fas fa-copy"></i></button></td>';
                    html += '<td><span class="badge bg-info">' + esc(c.role_name) + '</span></td>';
                    html += '<td>' + c.times_used + ' / ' + c.max_uses + '</td>';
                    html += '<td>' + (c.expires_at || 'Never') + '</td>';
                    html += '<td>' + (active ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>') + '</td>';
                    html += '<td>' + esc(c.created_by_name || '-') + '</td>';
                    html += '<td>';
                    if (active) {
                        html += '<button class="btn btn-sm btn-outline-danger deactivate-invite-btn" data-id="' + c.id + '"><i class="fas fa-ban me-1"></i>Deactivate</button>';
                    }
                    html += '</td></tr>';
                });
                html += '</tbody></table>';
                document.getElementById('invites-table-container').innerHTML = html;
                bindInviteButtons();
            });
        }

        function bindInviteButtons() {
            document.querySelectorAll('.copy-code-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    navigator.clipboard.writeText(this.dataset.code).then(function() { toast('Code copied!'); });
                });
            });
            document.querySelectorAll('.deactivate-invite-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    if (!confirm('Deactivate this invite code?')) return;
                    api('deactivate_invite', { invite_id: this.dataset.id }).then(function(d) {
                        toast(d.message, d.success ? 'success' : 'error');
                        if (d.success) loadInvites();
                    });
                });
            });
        }

        document.getElementById('btn-create-invite').addEventListener('click', function() {
            api('create_invite_code', {
                role_id: document.getElementById('ci-role').value,
                max_uses: document.getElementById('ci-maxuses').value,
                expires_hours: document.getElementById('ci-expires').value
            }).then(function(d) {
                if (d.success) {
                    toast('Invite code created: ' + d.code);
                    closeModal('createInviteModal');
                    loadInvites();
                } else {
                    toast(d.message, 'error');
                }
            });
        });

        // ── AUDIT LOG ────────────────────────────────────────────
        function loadAudit(page) {
            page = page || 1;
            api('get_audit_log', { page: page }, 'GET').then(function(d) {
                if (!d.success) return;
                var html = '<table class="table table-sm table-hover"><thead class="table-light"><tr><th>Time</th><th>User</th><th>Action</th><th>Target</th><th>Details</th><th>IP</th></tr></thead><tbody>';
                if (d.logs.length === 0) {
                    html += '<tr><td colspan="6" class="text-center text-muted">No audit entries yet.</td></tr>';
                }
                d.logs.forEach(function(l) {
                    html += '<tr>';
                    html += '<td><small>' + esc(l.created_at) + '</small></td>';
                    html += '<td>' + esc(l.username) + '</td>';
                    html += '<td><span class="badge bg-dark">' + esc(l.action) + '</span></td>';
                    html += '<td>' + esc(l.target || '-') + '</td>';
                    html += '<td><small>' + esc(l.details || '-') + '</small></td>';
                    html += '<td><small>' + esc(l.ip_address) + '</small></td>';
                    html += '</tr>';
                });
                html += '</tbody></table>';
                document.getElementById('audit-table-container').innerHTML = html;

                // Pagination
                var totalPages = Math.ceil(d.total / d.per_page);
                var pHtml = '';
                if (totalPages > 1) {
                    pHtml = '<ul class="pagination pagination-sm">';
                    for (var i = 1; i <= totalPages; i++) {
                        pHtml += '<li class="page-item ' + (i === d.page ? 'active' : '') + '"><a class="page-link audit-page-link" href="#" data-page="' + i + '">' + i + '</a></li>';
                    }
                    pHtml += '</ul>';
                }
                document.getElementById('audit-pagination').innerHTML = pHtml;
                document.querySelectorAll('.audit-page-link').forEach(function(a) {
                    a.addEventListener('click', function(e) {
                        e.preventDefault();
                        loadAudit(parseInt(this.dataset.page));
                    });
                });
            });
        }

        // ── Tab activation → load data ───────────────────────────
        document.querySelectorAll('#settingsTabs button[data-bs-toggle="tab"]').forEach(function(tab) {
            tab.addEventListener('shown.bs.tab', function(e) {
                var target = e.target.getAttribute('data-bs-target');
                if (target === '#tab-roles') loadRoles();
                if (target === '#tab-users') loadUsers();
                if (target === '#tab-invites') loadInvites();
                if (target === '#tab-audit') loadAudit();
            });
        });

        // Initial load
        loadRoles();
    })();
    </script>
</body>
</html>
