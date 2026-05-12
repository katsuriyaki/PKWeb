# CLAUDE.md

Project guidance for Claude Code.

## Project Overview
Secure PHP CRUD app with warm editorial UI (cream/dark palette). Security-first design.

## Tech Stack
- PHP 8.3 (native), Nginx, MySQL, Bootstrap 5.3 (layout only), custom CSS

## UI Theme
- **Editorial warm palette**: cream background, white cards, dark ink accent
- **Borders**: 1px solid `#DDD8CF`, 2px border-radius (sharp corners)
- **Fonts**: DM Serif Display (headings) + DM Sans (body) — Google Fonts
- **Accent**: `#1C1915` (dark ink)
- **Hover**: `#2C2825`

## CSS Variables
```css
:root {
    --bg:        #F5F1EA;  /* warm cream page bg */
    --surface:   #FDFAF5;  /* white card surface */
    --border:    #DDD8CF;  /* subtle warm border */
    --ink:       #1A1714;  /* primary text */
    --ink-2:     #4A4540;  /* secondary text */
    --ink-3:     #8A8480;  /* muted/placeholder text */
    --accent:    #1C1915;  /* dark ink accent */
    --accent-2:  #2C2825;  /* hover state */
}
```

### Typography
- Base: DM Sans, 14px body
- Headings: DM Serif Display, 20-22px, letter-spacing -0.3px
- Labels: 10.5px, 600 weight, 1.8px letter-spacing, uppercase

### Buttons
- **.btn-primary**: `--accent` fill, white text, 2px radius, hover `#2C2825`
- **.btn-ghost**: transparent, `--border` outline, `--ink-2` text
- **.btn-danger-ghost**: transparent, red tint on hover
- Font: DM Sans 12.5px, 500 weight, slight letter-spacing
- Padding: 9px 18px, icon gap 7px

### Layout Pattern
- Sidebar: 260px fixed, dark ink bg, nav sections, user footer
- Topbar: 72px sticky, surface bg, breadcrumb
- Content: 36px padding (20px on mobile)
- Sidebar slides in as drawer on mobile (<992px)

## Dev Commands
- Access: `http://localhost/`
- PHP syntax: `php -l <filename>`

## Adding a New Page
1. Copy existing page (e.g., create.php or view.php)
2. Include config.php
3. Use requireLogin() for protected pages
4. Use htmlEncode() for all output

## Adding a Form with CSRF
```php
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>";
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
    die('Invalid request');
}
```

## Detailed Docs
- [Security](docs/security.md) - Implementations, checklist, session flow
- [UI](docs/ui.md) - Theme, CSS classes, styling
- [Database](docs/database.md) - Schema, queries, commands
- [Troubleshooting](docs/troubleshooting.md) - Common issues

## File Structure
```
/var/www/html/PKWeb/
├── auth/
│   ├── index.php      - Login
│   ├── register.php   - Registration
│   ├── profile.php    - Profile + avatar upload
│   └── logout.php     - Session destroy
├── admin/
│   ├── admin.php      - Admin dashboard
│   ├── admin-items.php - Manage all items
│   └── admin-users.php - User management
├── items/
│   ├── create.php      - Create item
│   ├── edit.php        - Edit item
│   └── view.php        - View item
├── includes/
│   ├── shared-styles.php - Shared CSS variables + base styles
│   ├── page-header.php   - Sidebar + topbar
│   └── page-footer.php   - Content close + JS
├── actions/
│   ├── avatar.php        - Avatar upload/remove (AJAX handler)
│   └── delete-item.php   - Item deletion
├── uploads/
│   └── avatars/          - User avatar images
├── docs/
│   ├── database.md
│   ├── security.md
│   ├── troubleshooting.md
│   └── ui.md
├── CLAUDE.md
├── config.php      - DB & helpers
└── dashboard.php   - Dashboard
```

## Page Variables
Pages set these variables before including `page-header.php`:
- `$page` — used for sidebar active state (e.g. 'dashboard', 'create', 'profile')
- `$pageTitle` — topbar title text
- `$breadcrumb` — breadcrumb path (e.g. "username / View")
- `$topbarActions` — optional HTML for right-side topbar buttons

Example:
```php
$page = 'create';
$pageTitle = 'New Item';
$breadcrumb = $_SESSION['username'] . ' / New Item';
$topbarActions = '<a href="../dashboard.php" class="btn btn-ghost">Back</a>';
require_once 'config.php';
requireLogin();
```

## Role System
Two roles: `user` (default) and `admin`. Admin pages require `requireAdmin()`.
- `isAdmin()` — check if current user is admin
- `requireAdmin()` — redirect to auth/index.php (not logged in) or dashboard.php (not admin)
- Admin login: `auth/index.php` — admins are redirected to admin/admin.php on login
- Set admin role: `UPDATE users SET role='admin' WHERE username='...';`

## Delete Modal Pattern
Use modal popups instead of browser `confirm()` for consistent UI. Includes:
- `.modal-backdrop` overlay with blur backdrop
- `.modal-box` centered card with title, body, actions
- JS handlers: `openDeleteModal(id, title)`, `closeDeleteModal()`
- Escape key closes modal

## Testing
1. Register at /auth/register.php
2. Login at /auth/
3. Create/view/edit/delete items
4. Logout at /auth/logout.php