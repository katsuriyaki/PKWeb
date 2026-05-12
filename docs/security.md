# Security Implementation

## Security Implementations
- **SQLi Prevention**: Prepared statements/parameterized queries in all database operations
- **XSS Prevention**: htmlEncode() function for output escaping
- **Session Security**: HttpOnly cookies, strict mode, session regeneration every 5 minutes
- **CSRF Protection**: Token-based CSRF tokens on all forms using hash_equals()
- **Input Validation**: Server-side validation with whitelist patterns
- **Password Security**: bcrypt hashing via password_hash()
- **Server Config**: Security headers (X-Frame-Options, X-Content-Type-Options, X-XSS-Protection, Referrer-Policy)

## Security Checklist for New Features
- [ ] Use prepared statement for any SQL query
- [ ] Escape all output with htmlEncode()
- [ ] Add CSRF token to all forms
- [ ] Validate CSRF with hash_equals()
- [ ] Cast integers with (int) for IDs
- [ ] Trim and validate string inputs
- [ ] Use password_hash() for new passwords

## Session Flow
1. User registers at /register.php - password hashed with bcrypt
2. User logs in at /index.php - credentials verified, session created
3. Session regenerated after login with session_regenerate_id(true)
4. Each page checks requireLogin() - redirects if not authenticated
5. Session ID regenerated every 5 minutes
6. Logout at /logout.php - destroys session completely

## Key Functions (config.php)
- `getDB()` - Returns PDO connection with prepared statements, emulates prepares false, charset utf8mb4
- `htmlEncode($data)` - Escapes output using htmlspecialchars with ENT_QUOTES and UTF-8
- `requireLogin()` - Redirects to index.php if user not authenticated
- `isLoggedIn()` - Returns true if session has user_id and authenticated