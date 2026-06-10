# Comic Universe - Knowledge Base

Comprehensive technical documentation for the Comic Universe e-commerce platform.

---

## 1. Project Overview

### What is Comic Universe?
Comic Universe is a modern, production-ready PHP e-commerce platform designed specifically for selling comic books. It provides a complete solution for managing a comic book catalog, user authentication, shopping cart functionality, and order management.

### Project Goals
- Deliver a fully functional e-commerce platform
- Implement enterprise-grade security practices
- Provide a clean, user-friendly interface
- Enable easy deployment to production environments
- Ensure comprehensive documentation

### Target Users
- Comic book retailers and shop owners
- Platform administrators
- End customers (buyers)
- Developers maintaining the platform

---

## 2. Quick Start Guide

### For Developers

#### Installation
```bash
# Clone the repository
git clone https://github.com/yourusername/sitecomics2.git
cd sitecomics2

# Install dependencies
composer install

# Copy environment template
cp .env.example .env

# Initialize database
php setup-db.php

# Start development server
php -S localhost:8000
```

Access the application at: `http://localhost:8000`

#### First Steps
1. Register a new user account at `/register.php`
2. Login with your credentials at `/login.php`
3. Browse the catalog at `/index.php`
4. Add items to cart at `/cart.php`
5. Test the API at `/api/get.php?type=comics`

### For Deployment Teams

1. Prepare production server (PHP 8.1+, MySQL 8.0+)
2. Run deployment script: `bash deploy.sh` (Linux/Mac) or `powershell -ExecutionPolicy Bypass -File deploy.ps1` (Windows)
3. Configure `.env` for production
4. Verify with `/deploy-status.php`
5. Run functional tests with `php tests/functional-test.php`

---

## 3. System Requirements

### Production Requirements
- **OS:** Linux, macOS, or Windows Server
- **Web Server:** Apache 2.4+ with mod_rewrite
- **PHP:** Version 8.1 or later
- **Database:** MySQL 8.0+ or SQLite 3.0+
- **RAM:** Minimum 512MB, recommended 1GB+
- **Storage:** Minimum 1GB, recommended 10GB+

### Required PHP Extensions
- `php-pdo` - Database abstraction
- `php-json` - JSON processing
- `php-curl` - HTTP requests
- `php-mbstring` - String handling
- `php-openssl` - Encryption (HTTPS)

### Development Requirements
- PHP 8.1+ (local or Docker)
- Composer (dependency manager)
- Git (version control)
- Text editor or IDE (VS Code, PHPStorm, etc.)
- Postman (API testing - optional)

---

## 4. Installation & Setup

### Development Environment

#### Step 1: Clone Repository
```bash
git clone https://github.com/yourusername/sitecomics2.git
cd sitecomics2
```

#### Step 2: Install Dependencies
```bash
composer install
```

#### Step 3: Configure Environment
```bash
cp .env.example .env
# Edit .env with your settings
nano .env
```

Key settings:
```
ENVIRONMENT=development
DEBUG_MODE=true
DB_TYPE=sqlite
```

#### Step 4: Initialize Database
```bash
php setup-db.php
```

This command:
- Creates SQLite database (if using SQLite)
- Loads schema.sql
- Verifies table structure

#### Step 5: Start Development Server
```bash
php -S localhost:8000
```

### Production Environment

#### Step 1: Server Preparation
```bash
# Update system packages
sudo apt-get update && apt-get upgrade -y

# Install PHP and extensions
sudo apt-get install php8.1 php8.1-pdo php8.1-json php8.1-curl php8.1-mbstring apache2 mysql-server

# Enable Apache modules
sudo a2enmod rewrite
sudo systemctl reload apache2
```

#### Step 2: Upload Application
```bash
scp -r ./sitecomics2 user@server:/var/www/html/
```

#### Step 3: Configure Database
```bash
# Create database and user
mysql -u root -p
> CREATE DATABASE comic_universe CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
> CREATE USER 'comics_user'@'localhost' IDENTIFIED BY 'strong_password';
> GRANT ALL PRIVILEGES ON comic_universe.* TO 'comics_user'@'localhost';
> FLUSH PRIVILEGES;
```

#### Step 4: Configure Application
```bash
# Edit .env for production
sudo nano /var/www/html/sitecomics2/.env

# Key settings:
ENVIRONMENT=production
DEBUG_MODE=false
DB_TYPE=mysql
DB_HOST=localhost
DB_NAME=comic_universe
DB_USER=comics_user
DB_PASS=strong_password
```

#### Step 5: Initialize Database
```bash
cd /var/www/html/sitecomics2
php setup-db.php
```

#### Step 6: Set Permissions
```bash
sudo chown -R www-data:www-data /var/www/html/sitecomics2
sudo chmod 755 /var/www/html/sitecomics2
sudo chmod 755 /var/www/html/sitecomics2/{logs,cache,uploads}
sudo chmod 644 /var/www/html/sitecomics2/*.php
```

#### Step 7: Configure Apache Virtual Host
```bash
sudo nano /etc/apache2/sites-available/comics.conf
```

Content:
```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /var/www/html/sitecomics2
    
    <Directory /var/www/html/sitecomics2>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/comics-error.log
    CustomLog ${APACHE_LOG_DIR}/comics-access.log combined
</VirtualHost>
```

#### Step 8: Enable Virtual Host
```bash
sudo a2ensite comics.conf
sudo systemctl reload apache2
```

#### Step 9: Setup HTTPS with Let's Encrypt
```bash
sudo apt-get install certbot python3-certbot-apache
sudo certbot --apache -d your-domain.com
```

#### Step 10: Verify Installation
```bash
curl https://your-domain.com/deploy-status.php
```

---

## 5. Application Architecture

### MVC-inspired Pattern

The application follows a simplified MVC pattern:

```
Request → Router (.htaccess) → Controller (Page/API) → Model (Database) → View (HTML/JSON)
```

### Core Components

#### Configuration Layer (`config.php`)
- Loads environment variables from `.env`
- Sets up logging and error handling
- Defines application constants
- Initializes database connection

#### Database Layer (`db.php`)
- Singleton factory pattern for database connections
- Supports both SQLite and MySQL
- Manages connection pooling
- Provides error logging

#### Logging Layer (`includes/Logger.php`)
- 5 severity levels: DEBUG, INFO, WARNING, ERROR, CRITICAL
- Daily log rotation
- JSON-encoded context data
- File-based persistence

#### Error Handling Layer (`includes/ErrorHandler.php`)
- Global error and exception handling
- Production-safe error messages
- Full error logging
- Graceful error recovery

#### Authentication Layer (`includes/auth.php`)
- Session-based authentication
- Password verification with bcrypt
- User role management
- Token-based API access

---

## 6. Database Schema

### Overview
The application uses a normalized relational database with 5 main tables.

### Tables

#### `users` Table
Stores user account information.

```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

Fields:
- `id` - Unique user identifier (primary key)
- `username` - Login username (unique)
- `email` - Contact email address (unique)
- `password` - Hashed password (bcrypt)
- `created_at` - Account creation timestamp

#### `categories` Table
Product categories for organizing comics.

```sql
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### `comics` Table
Comic book products in the catalog.

```sql
CREATE TABLE comics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    category_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);
```

#### `orders` Table
Customer orders and purchases.

```sql
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    total DECIMAL(10, 2) NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

Status values: `pending`, `completed`, `cancelled`, `shipped`

#### `order_items` Table
Individual items within each order (order line items).

```sql
CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    comic_id INT NOT NULL,
    quantity INT DEFAULT 1,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (comic_id) REFERENCES comics(id)
);
```

### Relationships

```
users (1) ──┬─── (N) orders
           └──── (1:N) Many orders per user

categories (1) ──── (N) comics
              └─── One category per comic

orders (1) ──── (N) order_items
         └──── Multiple items per order

comics (N) ──── (1) order_items
         └─── Each comic can be in multiple order items
```

### Data Types

- `INT` - Integer numbers (IDs)
- `VARCHAR(n)` - Variable-length strings
- `TEXT` - Large text fields
- `DECIMAL(10, 2)` - Fixed-precision decimal for prices
- `TIMESTAMP` - Date and time
- `PRIMARY KEY` - Unique identifier constraint
- `FOREIGN KEY` - Referential integrity constraint

---

## 7. REST API Reference

### Base URL
```
Development: http://localhost:8000/api/
Production: https://your-domain.com/api/
```

### Authentication
All requests require valid session or API token in Authorization header:
```
Authorization: Bearer your_api_token
```

### Response Format
All responses are JSON with the following structure:

```json
{
    "success": true,
    "data": [...],
    "message": "Operation successful"
}
```

### GET /get.php

Retrieve data from the database.

**Parameters:**
- `type` - Resource type: `comics`, `categories`, `orders`, `users`
- `id` - (Optional) Specific resource ID
- `page` - (Optional) Page number for pagination
- `limit` - (Optional) Items per page

**Examples:**

Get all comics (page 1, 10 per page):
```bash
GET /api/get.php?type=comics&page=1&limit=10
```

Get specific comic:
```bash
GET /api/get.php?type=comics&id=1
```

Get all categories:
```bash
GET /api/get.php?type=categories
```

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "title": "Spider-Man #1",
            "price": "19.99",
            "category_id": 1
        }
    ],
    "total": 150
}
```

### POST /create.php

Create new resources.

**Body Parameters:**
```json
{
    "type": "comic",
    "title": "New Comic Title",
    "description": "Comic description",
    "price": 29.99,
    "category_id": 1
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 101,
        "title": "New Comic Title"
    }
}
```

### PUT /update.php

Update existing resources.

**Body Parameters:**
```json
{
    "id": 1,
    "title": "Updated Title",
    "price": 24.99
}
```

**Response:**
```json
{
    "success": true,
    "message": "Resource updated successfully"
}
```

### DELETE /delete.php

Delete resources.

**Parameters:**
- `id` - Resource ID to delete
- `type` - Resource type

**Example:**
```bash
DELETE /api/delete.php?id=1&type=comic
```

**Response:**
```json
{
    "success": true,
    "message": "Resource deleted successfully"
}
```

### Error Responses

**400 Bad Request:**
```json
{
    "success": false,
    "error": "Invalid parameters",
    "details": "Required field missing"
}
```

**401 Unauthorized:**
```json
{
    "success": false,
    "error": "Authentication required"
}
```

**404 Not Found:**
```json
{
    "success": false,
    "error": "Resource not found"
}
```

**500 Server Error:**
```json
{
    "success": false,
    "error": "Internal server error",
    "details": "Contact administrator"
}
```

### HTTP Status Codes

- `200 OK` - Request successful
- `201 Created` - Resource created
- `400 Bad Request` - Invalid request
- `401 Unauthorized` - Authentication required
- `403 Forbidden` - Permission denied
- `404 Not Found` - Resource not found
- `500 Server Error` - Server error
- `503 Service Unavailable` - Server unavailable

---

## 8. Security Implementation

### Password Security
- Algorithm: bcrypt
- Cost factor: 10
- Hash function: SHA-512

Example:
```php
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
$verified = password_verify($password, $hash);
```

### SQL Injection Prevention
- Method: PDO Prepared Statements
- All user input is parameterized
- Database values are escaped

Example:
```php
$stmt = $pdo->prepare("SELECT * FROM comics WHERE id = ?");
$stmt->execute([$id]);
```

### XSS Prevention
- Output escaping with `htmlspecialchars()`
- Content Security Policy headers
- No direct HTML rendering of user input

Example:
```php
echo htmlspecialchars($user_input, ENT_QUOTES, 'UTF-8');
```

### CSRF Protection
- Session tokens for form submissions
- SameSite cookie attribute (Strict)
- Token validation on state-changing requests

### File Protection
- `.htaccess` rules deny access to sensitive files
- Protected files: `config.php`, `.env`, database files
- Upload directory restrictions

### HTTPS
- Required in production
- Automatic HTTP→HTTPS redirect
- Strict-Transport-Security header

### Security Headers
```
X-Frame-Options: SAMEORIGIN
X-Content-Type-Options: nosniff
X-XSS-Protection: 1; mode=block
Content-Security-Policy: frame-ancestors 'self'
Referrer-Policy: same-origin
```

---

## 9. Deployment & DevOps

### Automated Deployment

#### Linux/Mac Deployment
```bash
bash deploy.sh
```

Script performs:
- PHP version verification (8.1+)
- Extension checking (pdo, json, curl, mbstring)
- Copy `.env.example` to `.env`
- Create required directories
- Install Composer dependencies
- Initialize database
- Display status report

#### Windows Deployment
```powershell
powershell -ExecutionPolicy Bypass -File deploy.ps1
```

Same checks as Linux, using PowerShell cmdlets.

### Health Checks

#### Deployment Status Endpoint
```bash
curl https://your-domain.com/deploy-status.php
```

Checks performed:
- PHP version compatibility
- Required extensions
- Database connectivity
- Directory permissions
- Configuration validity
- HTTPS enforcement (production)

#### Functional Tests
```bash
php tests/functional-test.php
```

Tests:
- Database connection
- Page loading
- API endpoints
- Authentication flow
- Form validation

### Monitoring

#### Log Viewing
```bash
# View today's logs
tail -f logs/app_$(date +%Y-%m-%d).log

# Search for errors
grep ERROR logs/app_*.log

# Count errors by level
grep "ERROR\|WARNING\|CRITICAL" logs/app_*.log | wc -l
```

#### Database Monitoring
```sql
-- Check table row counts
SELECT TABLE_NAME, TABLE_ROWS 
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = 'comic_universe';

-- Monitor slow queries
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 2;
```

### Backup Strategy

#### Daily Backups
```bash
# Backup database
mysqldump -u comics_user -p comic_universe > backup_$(date +%Y%m%d).sql

# Backup files
tar -czf backup_files_$(date +%Y%m%d).tar.gz /var/www/html/sitecomics2

# Upload to cloud storage
aws s3 cp backup_*.sql s3://your-bucket/backups/
```

#### Restore from Backup
```bash
# Restore database
mysql -u comics_user -p comic_universe < backup_20240115.sql

# Restore files
tar -xzf backup_files_20240115.tar.gz -C /
```

---

## 10. Troubleshooting

### Common Issues & Solutions

#### Issue: "404 Not Found" on API requests

**Cause:** Apache mod_rewrite not enabled

**Solution:**
```bash
sudo a2enmod rewrite
sudo systemctl reload apache2
```

#### Issue: "Connection refused" database error

**Cause:** Database server not running

**Solution:**
```bash
# Check MySQL status
systemctl status mysql

# Start MySQL
sudo systemctl start mysql

# Verify connection
mysql -u comics_user -p
```

#### Issue: "Permission denied" on file uploads

**Cause:** Wrong directory permissions

**Solution:**
```bash
chmod 755 /var/www/html/sitecomics2/uploads
chown www-data:www-data /var/www/html/sitecomics2/uploads
```

#### Issue: PHP extensions missing

**Cause:** Required extensions not installed

**Solution:**
```bash
# Check installed extensions
php -m | grep pdo

# Install missing extensions
sudo apt-get install php8.1-pdo php8.1-json php8.1-curl php8.1-mbstring

# Restart web server
sudo systemctl reload apache2
```

#### Issue: Slow performance

**Cause:** High server load or database queries

**Solutions:**
```bash
# Check server load
uptime

# Monitor disk space
df -h

# Check database performance
# Enable slow query log (see Monitoring section)

# Clear application cache
rm -rf /var/www/html/sitecomics2/cache/*
```

#### Issue: Session issues / Logged out unexpectedly

**Cause:** Session timeout or configuration

**Solution:**
```bash
# Extend session timeout in .env
SESSION_TIMEOUT=7200  # 2 hours

# Clear session files
rm -rf /var/lib/php/sessions/*

# Restart Apache
sudo systemctl reload apache2
```

#### Issue: File size limit exceeded on uploads

**Cause:** PHP upload limits

**Solution:**
```bash
# Edit PHP configuration
sudo nano /etc/php/8.1/apache2/php.ini

# Update settings
upload_max_filesize = 100M
post_max_size = 100M
max_file_uploads = 20

# Restart Apache
sudo systemctl reload apache2
```

### Getting Support

1. Check the logs: `logs/app_YYYY-MM-DD.log`
2. Run deployment status: `/deploy-status.php`
3. Run functional tests: `php tests/functional-test.php`
4. Check documentation: `README.md`, `MAINTENANCE.md`
5. Open issue on GitHub with logs and details

---

## 11. Performance Optimization

### Database Optimization

```sql
-- Add indexes for frequently searched fields
CREATE INDEX idx_comics_category ON comics(category_id);
CREATE INDEX idx_comics_price ON comics(price);
CREATE INDEX idx_orders_user ON orders(user_id);
CREATE INDEX idx_orders_status ON orders(status);

-- Check index usage
SELECT * FROM INFORMATION_SCHEMA.STATISTICS 
WHERE TABLE_SCHEMA = 'comic_universe';

-- Analyze query execution
EXPLAIN SELECT * FROM comics WHERE category_id = 1;
```

### Caching Strategy

```php
// Cache database results
$cache_key = 'comics_page_1';
$cached = apcu_fetch($cache_key);

if (!$cached) {
    $comics = fetchFromDatabase();
    apcu_store($cache_key, $comics, 3600); // Cache 1 hour
} else {
    $comics = $cached;
}
```

### Asset Optimization

- Minify CSS and JavaScript
- Enable Gzip compression in `.htaccess`
- Use CDN for static assets
- Optimize images (JPG, WebP formats)

---

## 12. Development Workflow

### Git Workflow

```bash
# Create feature branch
git checkout -b feature/new-feature

# Make changes and commit
git add .
git commit -m "feat: Add new feature description"

# Push to remote
git push origin feature/new-feature

# Create pull request
# Review and merge to main
```

### Testing Workflow

```bash
# Run unit tests
php vendor/bin/phpunit

# Run functional tests
php tests/functional-test.php

# Test API endpoints
# Use Postman collection: postman/collections/Comic Universe API/
```

### Deployment Workflow

```bash
# 1. Tag release
git tag -a v1.0.1 -m "Release version 1.0.1"

# 2. Push tags
git push origin --tags

# 3. Deploy to staging
./deploy-staging.sh

# 4. Run tests on staging
curl https://staging.your-domain.com/deploy-status.php

# 5. Deploy to production
./deploy-production.sh

# 6. Verify production
curl https://your-domain.com/deploy-status.php
```

---

## 13. Frequently Asked Questions

**Q: Can I use this with shared hosting?**  
A: Yes, if your host provides PHP 8.1+ and MySQL/SQLite. Check host compatibility.

**Q: How do I upgrade to a newer version?**  
A: Follow release notes, run migrations if needed, test thoroughly before production.

**Q: Is this suitable for high-traffic sites?**  
A: Yes, with proper optimization (caching, CDN, database indexing, load balancing).

**Q: Can I customize the design?**  
A: Yes, modify `styles.css` and HTML templates in `.php` files.

**Q: How do I add new features?**  
A: See CONTRIBUTING.md for development guidelines.

---

## 14. Related Documentation

- [README.md](README.md) - Main documentation
- [CHANGELOG.md](CHANGELOG.md) - Version history
- [CONTRIBUTING.md](CONTRIBUTING.md) - Development guide
- [SECURITY.md](SECURITY.md) - Security details
- [MAINTENANCE.md](MAINTENANCE.md) - Operations guide
- [DEPLOYMENT_CHECKLIST.md](DEPLOYMENT_CHECKLIST.md) - Pre-launch checklist

---

## 15. Getting Help

**Documentation:** See links above  
**Issues:** GitHub Issues tracker  
**Email:** support@your-domain.com  
**Community:** Stack Overflow tag `comic-universe`  

---

**Last Updated:** 2024-01-15  
**Version:** 1.0.0  
**Status:** Production Ready ✅
