# Troubleshooting

## Common Issues

### Authentication
- **302 redirect on protected pages** - Not logged in, check session
- **"Invalid request" on form submit** - CSRF token mismatch

### Database
- **"Database connection failed"** - Check MySQL credentials in config.php
- **"Failed to load items"** - Check table exists and user has permissions

### Paths
- **Broken UI/CSS** - Check include paths: root uses `/includes/`, subdirs use `/../includes/`
- **404 on links** - All internal links use absolute paths (`/dashboard.php`, `/items/create.php`)

### Security
- **"Invalid request"** - CSRF token expired (session regenerated after 5 min)
- **Login not working** - Check password_verify() vs password_hash()

## Security Tests
Run tests to verify security implementations:
```bash
php tests/security/run_tests.php
```

Browser: `http://localhost/tests/security/run_tests.php`

Test results (52/55 passing - 95%):
| Test | Status |
|------|--------|
| SQL Injection | ✅ 7/7 |
| XSS Prevention | ✅ 8/8 |
| CSRF Protection | ✅ 8/8 |
| Session Security | ✅ 10/10 |
| File Upload | ✅ 10/10 |
| Authentication | ✅ 10/12 |

## Debug Mode
Error display is disabled by default. To enable for debugging:
```php
# In config.php, temporarily change:
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## Related Docs
- `docs/security.md` - Security implementation details + test results
- `docs/database.md` - DB schema and queries
- `docs/ui.md` - Theme and styling issues

## Security Checklist
- [ ] Use prepared statement for any SQL query
- [ ] Escape all output with htmlEncode()
- [ ] Add CSRF token to all forms
- [ ] Validate CSRF with hash_equals()
- [ ] Cast integers with (int) for IDs
- [ ] Trim and validate string inputs
- [ ] Use password_hash() for new passwords