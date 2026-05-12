# Troubleshooting

## Common Issues
- 302 redirect on protected pages means not logged in - check session
- "Invalid request" on form submit means CSRF token mismatch
- "Database connection failed" - check MySQL credentials in config.php
- "Failed to load items" - check database table exists and user has permissions

## Notes
- Always use prepared statements for SQL queries
- Always escape output with htmlEncode()
- Always validate CSRF tokens with hash_equals() (timing-safe)
- Passwords are hashed with password_hash() and verified with password_verify()
- Session is regenerated every 5 minutes for security
- Error reporting is disabled in production