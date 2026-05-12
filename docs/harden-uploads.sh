#!/bin/bash
# Upload Directory Hardening Script
# Run as: sudo bash docs/harden-uploads.sh

set -e

WEBROOT="/var/www/html/PKWeb"
UPLOADS="$WEBROOT/uploads"

echo "🔒 Hardening PKWeb upload directories..."

# Set directory permissions (755 = rwxr-xr-x)
echo "Setting directory permissions to 755..."
find "$UPLOADS" -type d -exec chmod 755 {} \;

# Set file permissions (644 = rw-r--r--)
echo "Setting file permissions to 644..."
find "$UPLOADS" -type f -exec chmod 644 {} \;

# Prevent PHP execution in uploads
echo "Creating PHP execution prevention rules..."

# Create .htaccess to deny PHP execution (Apache)
cat > "$UPLOADS/.htaccess" 2>/dev/null << 'HTACCESS'
# Disable PHP execution in uploads directory
<FilesMatch "\.ph(p[3-7]?|tml|py|js)$">
    Order Deny,Allow
    Deny from all
</FilesMatch>

# Prevent directory listing
Options -Indexes

# Block executable uploads
<FilesMatch "\.(exe|sh|bat|cmd|scr|phtml|php\d|shell)$">
    Order Deny,Allow
    Deny from all
</FilesMatch>
HTACCESS

# Create nginx config to prevent PHP execution
cat > "$UPLOADS/nginx.conf" 2>/dev/null << 'NGINX'
# Nginx config to prevent PHP execution in uploads
# Add to server block:
# include /var/www/html/PKWeb/uploads/nginx.conf;

location /uploads/ {
    # Deny PHP files
    location ~ \.php$ {
        deny all;
    }

    # Allow only safe image types
    location ~ \.(jpg|jpeg|png|gif|webp|svg)$ {
        allow all;
    }

    # Deny all other files
    location ~ /\. {
        deny all;
    }
}
NGINX

# Verify permissions
echo ""
echo "✅ Verification:"
echo "Directories:"
ls -ld "$UPLOADS" "$UPLOADS/avatars" 2>/dev/null || true
echo ""
echo "Sample files:"
find "$UPLOADS" -type f -name "*.jpg" -o -name "*.png" 2>/dev/null | head -5 | xargs ls -la 2>/dev/null || echo "No image files found"
echo ""
echo "PHP files in uploads (should be empty):"
find "$UPLOADS" -name "*.php" 2>/dev/null || echo "No PHP files found ✅"
echo ""
echo "Done! Uploads directory is now hardened."