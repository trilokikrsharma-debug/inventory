<?php
/**
 * Profile Page View
 * 
 * Displays user profile information with edit capability.
 */
$pageTitle = 'My Profile';
$initials = '';
if (!empty($user['full_name'])) {
    $parts = explode(' ', $user['full_name']);
    $initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
}
?>

<style>
    .profile-hero {
        background: linear-gradient(135deg, #1e3a5f 0%, #0d1b2a 50%, #1b2838 100%);
        border-radius: 20px;
        padding: 2.5rem;
        color: #fff;
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
    }
    .profile-hero::before {
        content: '';
        position: absolute;
        top: -60%;
        right: -15%;
        width: 350px;
        height: 350px;
        background: radial-gradient(circle, rgba(78, 115, 223, 0.15) 0%, transparent 70%);
        border-radius: 50%;
    }
    .profile-hero::after {
        content: '';
        position: absolute;
        bottom: -50%;
        left: -8%;
        width: 250px;
        height: 250px;
        background: radial-gradient(circle, rgba(40, 167, 69, 0.1) 0%, transparent 70%);
        border-radius: 50%;
    }
    .profile-hero-content {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        position: relative;
        z-index: 1;
    }
    .profile-avatar-lg {
        width: 90px;
        height: 90px;
        border-radius: 22px;
        background: linear-gradient(135deg, #4e73df, #224abe);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        font-weight: 700;
        color: #fff;
        flex-shrink: 0;
        box-shadow: 0 8px 25px rgba(78, 115, 223, 0.35);
        letter-spacing: 1px;
    }
    .profile-hero-info h2 {
        font-weight: 700;
        font-size: 1.6rem;
        margin-bottom: 0.25rem;
    }
    .profile-hero-info .role-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        background: rgba(255,255,255,0.12);
        border: 1px solid rgba(255,255,255,0.15);
        padding: 0.3rem 0.9rem;
        border-radius: 20px;
        font-size: 0.82rem;
        font-weight: 600;
        text-transform: capitalize;
        backdrop-filter: blur(5px);
    }
    .profile-hero-info .member-since {
        font-size: 0.85rem;
        opacity: 0.65;
        margin-top: 0.4rem;
    }

    .profile-card {
        background: var(--card-bg, #fff);
        border-radius: 16px;
        border: 1px solid var(--border-color, #e3e6f0);
        overflow: hidden;
        margin-bottom: 1.5rem;
    }
    .profile-card .card-head {
        padding: 1.15rem 1.5rem;
        border-bottom: 1px solid var(--border-color, #e3e6f0);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .profile-card .card-head h5 {
        margin: 0;
        font-weight: 700;
        font-size: 1rem;
        display: flex;
        align-items: center;
        gap: 0.6rem;
        color: var(--text-primary, #2d3436);
    }
    .profile-card .card-content {
        padding: 1.5rem;
    }

    .info-row {
        display: flex;
        align-items: center;
        padding: 0.85rem 0;
        border-bottom: 1px solid var(--border-color, rgba(0,0,0,0.05));
    }
    .info-row:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }
    .info-row:first-child {
        padding-top: 0;
    }
    .info-row .info-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.95rem;
        flex-shrink: 0;
        margin-right: 1rem;
    }
    .info-row .info-icon.icon-blue {
        background: rgba(78, 115, 223, 0.1);
        color: #4e73df;
    }
    .info-row .info-icon.icon-green {
        background: rgba(40, 167, 69, 0.1);
        color: #28a745;
    }
    .info-row .info-icon.icon-orange {
        background: rgba(253, 126, 20, 0.1);
        color: #fd7e14;
    }
    .info-row .info-icon.icon-purple {
        background: rgba(111, 66, 193, 0.1);
        color: #6f42c1;
    }
    .info-row .info-icon.icon-teal {
        background: rgba(23, 162, 184, 0.1);
        color: #17a2b8;
    }
    .info-label {
        font-size: 0.78rem;
        color: var(--text-secondary, #636e72);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
    }
    .info-value {
        font-size: 0.95rem;
        font-weight: 600;
        color: var(--text-primary, #2d3436);
    }

    .quick-action-card {
        background: var(--card-bg, #fff);
        border-radius: 14px;
        border: 1px solid var(--border-color, #e3e6f0);
        padding: 1.25rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        transition: all 0.3s ease;
        text-decoration: none;
        color: var(--text-primary, #2d3436);
    }
    .quick-action-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        color: var(--text-primary, #2d3436);
        border-color: #4e73df;
    }
    .quick-action-card .qa-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        flex-shrink: 0;
    }
    .quick-action-card .qa-icon.bg-primary-soft {
        background: rgba(78, 115, 223, 0.1);
        color: #4e73df;
    }
    .quick-action-card .qa-icon.bg-warning-soft {
        background: rgba(253, 126, 20, 0.1);
        color: #fd7e14;
    }
    .quick-action-card .qa-icon.bg-danger-soft {
        background: rgba(220, 53, 69, 0.1);
        color: #dc3545;
    }
    .quick-action-card h6 {
        margin: 0;
        font-weight: 700;
        font-size: 0.92rem;
    }
    .quick-action-card p {
        margin: 0;
        font-size: 0.78rem;
        color: var(--text-secondary, #636e72);
    }

    .btn-edit-profile {
        background: linear-gradient(135deg, #4e73df, #224abe);
        border: none;
        color: #fff;
        padding: 0.55rem 1.3rem;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.85rem;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        transition: all 0.3s ease;
    }
    .btn-edit-profile:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(78, 115, 223, 0.35);
        color: #fff;
    }

    .btn-save-profile {
        background: linear-gradient(135deg, #28a745, #20c997);
        border: none;
        color: #fff;
        padding: 0.65rem 2rem;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.92rem;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
    }
    .btn-save-profile:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        color: #fff;
    }

    .btn-cancel {
        background: var(--card-bg, #f8f9fa);
        border: 1px solid var(--border-color, #e3e6f0);
        color: var(--text-primary, #636e72);
        padding: 0.65rem 1.5rem;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.92rem;
        transition: all 0.2s ease;
    }
    .btn-cancel:hover {
        background: var(--border-color, #e3e6f0);
    }

    .edit-form .form-control {
        border-radius: 10px;
        padding: 0.6rem 1rem;
        border: 1px solid var(--border-color, #e3e6f0);
    }
    .edit-form .form-control:focus {
        border-color: #4e73df;
        box-shadow: 0 0 0 3px rgba(78, 115, 223, 0.15);
    }
    .edit-form .form-label {
        font-weight: 600;
        font-size: 0.85rem;
        color: var(--text-secondary, #636e72);
    }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-in {
        animation: fadeInUp 0.4s ease forwards;
    }
    .delay-1 { animation-delay: 0.05s; }
    .delay-2 { animation-delay: 0.1s; }
    .delay-3 { animation-delay: 0.15s; }
</style>

<!-- Breadcrumb -->
<div class="page-header">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li>
            <li class="breadcrumb-item active">My Profile</li>
        </ol>
    </nav>
</div>

<!-- Profile Hero -->
<div class="profile-hero animate-in">
    <div class="profile-hero-content">
        <div class="profile-avatar-lg"><?= Helper::escape($initials) ?></div>
        <div class="profile-hero-info">
            <h2><?= Helper::escape($user['full_name'] ?? 'User') ?></h2>
            <div class="role-badge">
                <i class="fas fa-<?= ($user['role'] ?? 'staff') === 'admin' ? 'shield-halved' : 'user' ?>"></i>
                <?= Helper::escape(ucfirst($user['role'] ?? 'staff')) ?>
            </div>
            <?php if (!empty($user['created_at'])): ?>
            <div class="member-since">
                <i class="fas fa-calendar-alt me-1"></i>
                Member since <?= date('F Y', strtotime($user['created_at'])) ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Profile Info -->
    <div class="col-lg-8">
        <!-- View Mode -->
        <div class="profile-card animate-in delay-1" id="profileViewCard">
            <div class="card-head">
                <h5>
                    <i class="fas fa-user-circle" style="color: #4e73df;"></i>
                    Personal Information
                </h5>
                <button type="button" class="btn-edit-profile" onclick="toggleEditMode()">
                    <i class="fas fa-pen"></i> Edit
                </button>
            </div>
            <div class="card-content">
                <div class="info-row">
                    <div class="info-icon icon-blue"><i class="fas fa-user"></i></div>
                    <div>
                        <div class="info-label">Full Name</div>
                        <div class="info-value"><?= Helper::escape($user['full_name'] ?? '—') ?></div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-icon icon-purple"><i class="fas fa-at"></i></div>
                    <div>
                        <div class="info-label">Username</div>
                        <div class="info-value"><?= Helper::escape($user['username'] ?? '—') ?></div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-icon icon-green"><i class="fas fa-envelope"></i></div>
                    <div>
                        <div class="info-label">Email Address</div>
                        <div class="info-value"><?= Helper::escape($user['email'] ?? '—') ?></div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-icon icon-orange"><i class="fas fa-phone"></i></div>
                    <div>
                        <div class="info-label">Phone Number</div>
                        <div class="info-value"><?= Helper::escape($user['phone'] ?? '—') ?></div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-icon icon-teal"><i class="fas fa-clock"></i></div>
                    <div>
                        <div class="info-label">Last Login</div>
                        <div class="info-value">
                            <?php if (!empty($user['last_login'])): ?>
                                <?= date('d M Y, h:i A', strtotime($user['last_login'])) ?>
                                <span style="font-size: 0.78rem; color: var(--text-secondary); font-weight: 400; margin-left: 0.5rem;">
                                    (<?= Helper::timeAgo($user['last_login']) ?>)
                                </span>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Mode (Hidden by default) -->
        <div class="profile-card animate-in delay-1" id="profileEditCard" style="display: none;">
            <div class="card-head">
                <h5>
                    <i class="fas fa-pen-to-square" style="color: #28a745;"></i>
                    Edit Profile
                </h5>
            </div>
            <div class="card-content">
                <form method="POST" action="<?= APP_URL ?>/index.php?page=profile&action=update" class="edit-form">
                    <?= CSRF::field() ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control" 
                                   value="<?= Helper::escape($user['full_name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" 
                                   value="<?= Helper::escape($user['username'] ?? '') ?>" disabled
                                   style="opacity: 0.6; cursor: not-allowed;">
                            <small class="text-muted">Username cannot be changed</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?= Helper::escape($user['email'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control" 
                                   value="<?= Helper::escape($user['phone'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn-save-profile">
                            <i class="fas fa-check"></i> Save Changes
                        </button>
                        <button type="button" class="btn-cancel" onclick="toggleEditMode()">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Right Column: Quick Actions & Account Info -->
    <div class="col-lg-4">
        <!-- Account Details Card -->
        <div class="profile-card animate-in delay-2">
            <div class="card-head">
                <h5>
                    <i class="fas fa-info-circle" style="color: #17a2b8;"></i>
                    Account Details
                </h5>
            </div>
            <div class="card-content">
                <div class="info-row">
                    <div class="info-icon icon-blue"><i class="fas fa-id-badge"></i></div>
                    <div>
                        <div class="info-label">User ID</div>
                        <div class="info-value">#<?= $user['id'] ?? '—' ?></div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-icon icon-green"><i class="fas fa-shield-halved"></i></div>
                    <div>
                        <div class="info-label">Role</div>
                        <div class="info-value"><?= ucfirst($user['role'] ?? 'staff') ?></div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-icon icon-orange"><i class="fas fa-circle-check"></i></div>
                    <div>
                        <div class="info-label">Status</div>
                        <div class="info-value">
                            <?php if (($user['is_active'] ?? 0) == 1): ?>
                                <span style="color: #28a745;"><i class="fas fa-circle" style="font-size: 0.5rem; vertical-align: middle;"></i> Active</span>
                            <?php else: ?>
                                <span style="color: #dc3545;"><i class="fas fa-circle" style="font-size: 0.5rem; vertical-align: middle;"></i> Inactive</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-icon icon-teal"><i class="fas fa-calendar-plus"></i></div>
                    <div>
                        <div class="info-label">Account Created</div>
                        <div class="info-value">
                            <?= !empty($user['created_at']) ? date('d M Y', strtotime($user['created_at'])) : '—' ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="animate-in delay-3">
            <h6 style="font-weight: 700; color: var(--text-secondary, #636e72); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.75rem; padding-left: 0.25rem;">
                Quick Actions
            </h6>
            <div class="d-grid gap-2">
                <a href="<?= APP_URL ?>/index.php?page=profile&action=password" class="quick-action-card">
                    <div class="qa-icon bg-warning-soft">
                        <i class="fas fa-key"></i>
                    </div>
                    <div>
                        <h6>Change Password</h6>
                        <p>Update your account password</p>
                    </div>
                </a>
                <?php if (Session::isAdmin()): ?>
                <a href="<?= APP_URL ?>/index.php?page=settings" class="quick-action-card">
                    <div class="qa-icon bg-primary-soft">
                        <i class="fas fa-gear"></i>
                    </div>
                    <div>
                        <h6>System Settings</h6>
                        <p>Manage application configuration</p>
                    </div>
                </a>
                <a href="<?= APP_URL ?>/index.php?page=users" class="quick-action-card">
                    <div class="qa-icon bg-danger-soft">
                        <i class="fas fa-users-cog"></i>
                    </div>
                    <div>
                        <h6>Manage Users</h6>
                        <p>Add or edit user accounts</p>
                    </div>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function toggleEditMode() {
    const viewCard = document.getElementById('profileViewCard');
    const editCard = document.getElementById('profileEditCard');

    if (viewCard.style.display === 'none') {
        viewCard.style.display = '';
        editCard.style.display = 'none';
    } else {
        viewCard.style.display = 'none';
        editCard.style.display = '';
        // Focus on first input
        editCard.querySelector('input[name="full_name"]').focus();
    }
}
</script>
