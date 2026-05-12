<?php
$avatar = null;
$db2 = getDB();
$stmt2 = $db2->prepare("SELECT avatar FROM users WHERE id = ?");
$stmt2->execute([$_SESSION['user_id']]);
$row = $stmt2->fetch();
$sidebarAvatar = $row['avatar'] ?? null;
?>
<div class="sidebar-overlay" id="overlay" onclick="closeSidebar()"></div>

<div class="shell">

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo">
            <div class="sidebar-logo-mark">PKWeb</div>
            <div class="sidebar-logo-sub">Workspace</div>
        </div>

        <div class="sidebar-section">
            <div class="sidebar-section-label">Library</div>
            <nav class="sidebar-nav">
                <a href="/dashboard.php" class="<?= ($page ?? '') === 'dashboard' ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/>
                    </svg>
                    All Items
                </a>
                <a href="#">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                    Recent
                </a>
                <a href="#">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                    </svg>
                    Favorites
                </a>
                <a href="/items/create.php" class="<?= ($page ?? '') === 'create' ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    New Item
                </a>
            </nav>
        </div>

        <div class="sidebar-divider"></div>

        <?php if (isAdmin()): ?>
        <div class="sidebar-section">
            <div class="sidebar-section-label">Platform</div>
            <nav class="sidebar-nav">
                <a href="/admin/admin.php" class="<?= ($page ?? '') === 'admin' ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                    </svg>
                    Overview
                </a>
                <a href="/admin/admin-items.php" class="<?= ($page ?? '') === 'admin-items' ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                    </svg>
                    All Items
                </a>
                <a href="/admin/admin-users.php" class="<?= ($page ?? '') === 'admin-users' ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                    Users
                </a>
            </nav>
        </div>

        <div class="sidebar-divider"></div>
        <?php endif; ?>

        <div class="sidebar-section">
            <div class="sidebar-section-label">Account</div>
            <nav class="sidebar-nav">
                <a href="/auth/profile.php" class="<?= ($page ?? '') === 'profile' ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                    </svg>
                    Profile
                </a>
                <a href="#">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/>
                    </svg>
                    Settings
                </a>
            </nav>
        </div>

        <div class="sidebar-footer">
            <div class="sf-row">
                <a href="/auth/profile.php" class="sf-link">
                    <div class="sf-avatar">
                        <?php if ($sidebarAvatar): ?>
                            <img src="<?= htmlEncode($sidebarAvatar) ?>" alt="">
                        <?php else: ?>
                            <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div class="sf-name"><?= htmlEncode($_SESSION['username']) ?></div>
                        <a href="/auth/logout.php" class="sf-action">Sign out</a>
                    </div>
                </a>
            </div>
        </div>
    </aside>

    <main class="main">

        <header class="topbar">
            <div class="topbar-left">
                <button class="menu-toggle" onclick="toggleSidebar()" aria-label="Toggle menu">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <line x1="3" y1="6"  x2="21" y2="6"/>
                        <line x1="3" y1="12" x2="21" y2="12"/>
                        <line x1="3" y1="18" x2="21" y2="18"/>
                    </svg>
                </button>
                <div>
                    <div class="topbar-title"><?= htmlEncode($pageTitle ?? '') ?></div>
                    <div class="topbar-breadcrumb">
                        <a href="/dashboard.php">PKWeb</a>
                        <span>/</span>
                        <?= htmlEncode($breadcrumb ?? '') ?>
                    </div>
                </div>
            </div>
            <?php if (isset($topbarActions)): ?>
            <div class="topbar-actions">
                <?= $topbarActions ?>
            </div>
            <?php endif; ?>
        </header>

        <div class="content">