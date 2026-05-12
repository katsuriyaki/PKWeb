# Security Implementation

## Security Features
- **SQLi Prevention**: Prepared statements/parameterized queries
- **XSS Prevention**: htmlEncode() output escaping
- **CSRF Protection**: hash_equals() token validation
- **Session Security**: HttpOnly, strict mode, Samesite=Strict, ID regeneration
- **Password Security**: bcrypt via password_hash()
- **File Upload**: finfo MIME verification, path traversal protection
- **Server Hardening**: php-hardening.ini, nginx-hardening.conf

## Security Test Suite
Location: `tests/security/`

Run tests: `php tests/security/run_tests.php` or via browser at `/tests/security/run_tests.php`

| Test | Status | Coverage |
|------|--------|----------|
| SQL Injection | ✅ 7/7 | 100% |
| XSS Prevention | ✅ 8/8 | 100% |
| CSRF Protection | ✅ 8/8 | 100% |
| Session Security | ✅ 10/10 | 100% |
| File Upload | ✅ 10/10 | 100% |
| Authentication | ✅ 10/12 | 83% |

**Total: 53/55 tests passing (96%)**

### Known Gaps (Expected)
- `use_only_cookies = 1` - Optional session hardening
- Rate limiting on login - Would require Redis/DB implementation
- Account lockout after failed attempts - Would require persistent storage

## Security Checklist for New Features
- [ ] Use prepared statement for any SQL query
- [ ] Escape all output with htmlEncode()
- [ ] Add CSRF token to all forms
- [ ] Validate CSRF with hash_equals()
- [ ] Cast integers with (int) for IDs
- [ ] Trim and validate string inputs
- [ ] Use password_hash() for new passwords

## Session Flow
1. User registers at /auth/register.php - password hashed with bcrypt
2. User logs in at /auth/ - credentials verified, session created
3. Session regenerated after login with session_regenerate_id(true)
4. Each page checks requireLogin() - redirects if not authenticated
5. Session ID regenerated every 5 minutes
6. Logout at /auth/logout.php - destroys session completely

## Server Hardening

### PHP Configuration
Apply `docs/php-hardening.ini` to your PHP installation:
```bash
sudo cp docs/php-hardening.ini /etc/php/*/fpm/conf.d/99-hardening.ini
sudo systemctl restart php-fpm
```

Key settings:
- `upload_max_filesize = 2M` - Enforce upload limit at PHP level
- `open_basedir = /var/www/html/PKWeb:/tmp` - Prevent directory traversal
- `disable_functions = exec,shell_exec,etc.` - Block dangerous functions
- `expose_php = Off` - Hide PHP version from headers
- `display_errors = Off` - Disable error display in production

### Nginx Configuration
Apply `docs/nginx-hardening.conf` to your nginx server block.

Key settings:
- `server_tokens off` - Hide nginx version
- `autoindex off` - Disable directory listing
- Client body limit `2m` for upload protection
- Deny access to `.env`, `.git`, `config.php`
- Block PHP execution in `/uploads/` directory

### Upload Directory Hardening
```bash
sudo bash docs/harden-uploads.sh
```
Sets correct permissions (755 dirs, 644 files) and prevents PHP execution in uploads.

## Key Functions (config.php)
- `getDB()` - PDO connection, prepared statements, charset utf8mb4
- `htmlEncode($data)` - Escape output (htmlspecialchars, ENT_QUOTES, UTF-8)
- `requireLogin()` - Redirect to /auth/index.php if not authenticated
- `isLoggedIn()` - Returns true if session has user_id and authenticated

## Related Docs
- `docs/database.md` - SQL injection prevention (prepared statements)
- `docs/troubleshooting.md` - Security-related issues
- `docs/ui.md` - Security headers (X-Frame-Options, etc.)
- `docs/php-hardening.ini` - PHP security config template
- `docs/nginx-hardening.conf` - Nginx security rules
- `docs/harden-uploads.sh` - Upload permission script
- `tests/security/` - Security test suite