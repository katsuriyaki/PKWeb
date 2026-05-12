# Database

## Connection
- **Database**: pkweb
- **User**: pkweb (password: pkweb123)
- **Host**: localhost

## Tables
- `users` (id, username, email, password, avatar, role, created_at)
- `items` (id, user_id, title, description, created_at, updated_at)

## Schema
```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## Common Queries

### Adding a New Database Query
```php
$db = getDB();
$stmt = $db->prepare("SELECT * FROM table WHERE id = ?");
$stmt->execute([$value]);
$result = $stmt->fetch();
```

## Commands
```bash
# MySQL CLI
mysql -u pkweb -ppkweb123 pkweb

# Create database
mysql -u root -e "CREATE DATABASE pkweb; CREATE USER 'pkweb'@'localhost' IDENTIFIED BY 'pkweb123'; GRANT ALL ON pkweb.* TO 'pkweb'@'localhost';"

# Make admin
mysql -u pkweb -ppkweb123 pkweb -e "UPDATE users SET role='admin' WHERE username='youruser';"
```

## Related Docs
- `docs/security.md` - SQL injection prevention (prepared statements)
- `docs/troubleshooting.md` - Common DB issues