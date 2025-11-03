# Controllo Domini v4.2 - Installation Guide

Quick installation guide for the new v4.2 features including database, authentication, and API.

## Prerequisites

- PHP 8.0+ with extensions:
  - PDO
  - pdo_pgsql
  - openssl
  - mbstring
  - json
  - redis (optional, for caching)
- PostgreSQL 13+
- Redis 6+ (optional, but recommended)
- Apache 2.4+ or Nginx
- Composer (optional, for future dependencies)

## Quick Start

### 1. Database Setup

```bash
# Create PostgreSQL database
sudo -u postgres createdb controllo_domini
sudo -u postgres createuser controllo_domini_user

# Grant permissions
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE controllo_domini TO controllo_domini_user;"
```

### 2. Configuration

```bash
# Copy environment file
cp .env.example .env

# Edit configuration
nano .env
```

Configure at minimum:
- `DB_NAME=controllo_domini`
- `DB_USER=controllo_domini_user`
- `DB_PASSWORD=your_secure_password`

### 3. Install Database Schema

```bash
# Run installation script
php database/install.php
```

Expected output:
```
==============================================
Controllo Domini - Database Installation
==============================================

Testing database connection...
✓ Connected to PostgreSQL: PostgreSQL 13.x
✓ Schema file loaded

Installing database schema...
This may take a few moments...

✓ Database schema installed successfully!

Verifying installation...

Installed tables (20):
  ✓ users
  ✓ api_keys
  ✓ sessions
  ... (and 17 more)

✓ Created 40 indexes
✓ Created 2 views
✓ Created 7 triggers

==============================================
Installation completed successfully!
==============================================
```

### 4. Set Permissions

```bash
# Ensure directories are writable
chmod 755 logs exports backups
chmod 644 config/*.php

# Secure sensitive files
chmod 600 .env
```

### 5. Create First User

Visit: `https://your-domain.com/register`

Or create via database:

```sql
INSERT INTO users (email, password_hash, full_name, plan, status, email_verified)
VALUES (
    'admin@example.com',
    '$2y$12$...', -- Use password_hash('your_password', PASSWORD_BCRYPT, ['cost' => 12])
    'Admin User',
    'enterprise',
    'active',
    true
);
```

### 6. Generate API Key

1. Log in to dashboard: `/dashboard`
2. Go to API Keys section: `/api-keys`
3. Click "Generate New API Key"
4. Save the key (shown only once!)

### 7. Test API

```bash
# Test DNS endpoint
curl -H "X-API-Key: your_api_key_here" \
  "https://your-domain.com/api/v2/dns?domain=example.com"

# Expected response:
{
  "success": true,
  "data": {
    "A": [...],
    "MX": [...],
    ...
  },
  "from_cache": false
}
```

## Features Enabled

After installation, you'll have access to:

✅ **User Accounts**
- Registration with email verification
- Login with 2FA support
- Password reset
- Session management

✅ **Dashboard**
- Analytics and statistics
- Recent activity
- Alert notifications
- Quick actions

✅ **API v2.1**
- 10+ REST endpoints
- API key authentication
- Rate limiting
- Bulk operations

✅ **Export System**
- PDF reports
- CSV exports
- JSON data dumps
- Excel files

✅ **Dark Mode**
- Automatic system detection
- Manual toggle
- Persistent preference
- Keyboard shortcut (Ctrl+Shift+D)

## Configuration Options

### Database

```php
// config/database.php
define('DB_HOST', 'localhost');
define('DB_PORT', '5432');
define('DB_NAME', 'controllo_domini');
define('DB_USER', 'your_user');
define('DB_PASSWORD', 'your_password');
```

### Caching

```php
// config/performance.php
define('CACHE_ENABLED', true);
define('CACHE_DRIVER', 'redis'); // or 'file'
define('REDIS_HOST', 'localhost');
define('REDIS_PORT', 6379);
```

### API Rate Limits

Default limits by plan:
- **Free**: 100 requests/hour
- **Pro**: 1,000 requests/hour
- **Enterprise**: Unlimited

Configure in API key settings via dashboard.

## Troubleshooting

### Database Connection Failed

```
Error: Database connection failed: SQLSTATE[08006]
```

**Solution:**
1. Check PostgreSQL is running: `sudo systemctl status postgresql`
2. Verify credentials in `config/database.php`
3. Check pg_hba.conf allows connections
4. Test connection: `psql -U your_user -d controllo_domini -h localhost`

### Redis Connection Failed

```
Warning: Redis connection failed
```

**Solution:**
1. Check Redis is running: `sudo systemctl status redis`
2. Verify Redis configuration
3. System will fall back to file caching automatically

### Permission Denied

```
Warning: file_put_contents(logs/error.log): Permission denied
```

**Solution:**
```bash
sudo chown -R www-data:www-data logs exports backups
sudo chmod -R 755 logs exports backups
```

### API Returns 401 Unauthorized

**Solution:**
1. Verify API key is correct
2. Check key hasn't expired: `SELECT * FROM api_keys WHERE key_prefix = 'cd_xxxxxxxx'`
3. Ensure key is active: `status = 'active'`
4. Check rate limits haven't been exceeded

## Upgrade from Previous Versions

If upgrading from v4.1 or earlier:

```bash
# Backup existing data
tar -czf controllo-domini-backup-$(date +%Y%m%d).tar.gz .

# Pull latest code
git pull origin main

# Install database
php database/install.php

# Clear cache
rm -rf cache/*

# Restart web server
sudo systemctl restart apache2  # or nginx
```

## Security Checklist

After installation:

- [ ] Change default database password
- [ ] Set up SSL/TLS (HTTPS)
- [ ] Configure firewall rules
- [ ] Enable fail2ban for brute force protection
- [ ] Set up regular database backups
- [ ] Configure log rotation
- [ ] Review and update `.htaccess` security headers
- [ ] Enable CSP (Content Security Policy)
- [ ] Set up monitoring (optional)
- [ ] Configure email notifications

## Performance Optimization

For production:

```bash
# Enable OPcache
echo "opcache.enable=1" >> /etc/php/8.0/apache2/php.ini

# Increase memory limit
echo "memory_limit=256M" >> /etc/php/8.0/apache2/php.ini

# Install Redis for caching
sudo apt-get install redis-server php-redis

# Enable Apache modules
sudo a2enmod rewrite expires headers deflate http2
```

## Next Steps

1. Read `documents/ARCHITECTURE.md` for system overview
2. Read `documents/API.md` for API documentation
3. Read `documents/SECURITY.md` for security best practices
4. Check `documents/PERFORMANCE.md` for optimization tips
5. Review `ROADMAP.md` for upcoming features

## Support

- Documentation: `/documents`
- Issues: [GitHub Issues](https://github.com/gtechgroup/controllo-domini/issues)
- Email: support@controllodomini.it

## License

See LICENSE file for details.

---

**Congratulations!** Controllo Domini v4.2 is now installed and ready to use! 🎉

Enjoy the new features including user accounts, API v2.1, exports, and dark mode!
