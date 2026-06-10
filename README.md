# Comic Universe - E-Commerce Store

Modern PHP e-commerce platform with REST API, user authentication, and SQLite/MySQL support.

**Version:** 1.0.0  
**License:** MIT  
**Author:** Comic Universe Development Team

---

## 🎯 Features

- **Full E-Commerce Catalog** — Browse, search, filter comics by category
- **REST API** — Complete CRUD operations for comics (GET, POST, PUT, DELETE)
- **User Authentication** — Registration, login, role-based access control
- **Shopping Cart** — Add/remove items, persistent session storage
- **Security** — Bcrypt password hashing, XSS protection, SQL injection prevention
- **Multi-Database Support** — SQLite (development) and MySQL (production)
- **Environment Configuration** — Separate configs for dev/staging/production
- **Comprehensive Testing** — Built-in test suite for validation
- **API Documentation** — Full endpoint reference with examples

---

## 📋 Requirements

- PHP 8.1 or higher
- PDO extension (SQLite or MySQL)
- Apache 2.4+ with `mod_rewrite` enabled
- Composer (optional, for dependency management)

---

## 🚀 Quick Start

### Development Setup

#### 1. Clone the repository

```bash
git clone https://github.com/antonshimin08/sitecomics2.git
cd sitecomics2
```

#### 2. Install dependencies

```bash
composer install
```

#### 3. Setup environment

```bash
# Copy environment template
cp .env.example .env

# Edit configuration (optional for development)
nano .env
```

#### 4. Initialize database

```bash
# Run database setup
php setup-db.php

# Verify installation
php setup-db.php verify
```

#### 5. Start development server

```bash
# Using PHP built-in server
php -S localhost:8000

# Or use OpenServer/XAMPP/Docker
# Then access: http://localhost:8000
```

---

## 🌐 Production Deployment

### Quick Deployment (Automated)

#### On Linux/Mac:
```bash
chmod +x deploy.sh
bash deploy.sh
```

#### On Windows (PowerShell):
```powershell
powershell -ExecutionPolicy Bypass -File deploy.ps1
```

### Manual Deployment

See [PRODUCTION_DEPLOYMENT.md](PRODUCTION_DEPLOYMENT.md) for detailed step-by-step instructions.

**Key deployment checklist:**
- ✅ Update `.env` for production environment
- ✅ Set `DEBUG_MODE=false` and `ENVIRONMENT=production`
- ✅ Configure MySQL database credentials
- ✅ Set up Apache virtual host with HTTPS
- ✅ Run `php setup-db.php` to initialize database
- ✅ Visit `/deploy-status.php` to verify deployment
- ✅ Run `php tests/functional-test.php` for testing

---

## 🧪 Testing

### Deployment Status Check

```bash
# Web browser
https://your-domain.com/deploy-status.php

# Command line (JSON response)
curl -H "Accept: application/json" https://your-domain.com/deploy-status.php
```

### Run Functional Tests

```bash
# Test all functionality
php tests/functional-test.php

# Test with custom URL
php tests/functional-test.php http://custom-domain.com
```

### Unit Tests (with PHPUnit)

```bash
# Run PHPUnit tests
php vendor/bin/phpunit

# Run specific test file
php vendor/bin/phpunit tests/ExampleTest.php
```

---

## 📁 Project Structure

```
sitecomics2/
├── .env                          # Environment configuration (production)
├── .env.example                  # Environment template
├── .htaccess                     # Apache rewrite and security rules
├── config.php                    # Application configuration loader
├── db.php                        # Database connection factory
├── schema.sql                    # Database schema definition
├── setup-db.php                  # Database initialization script
├── deploy.sh                     # Deployment script (Linux/Mac)
├── deploy.ps1                    # Deployment script (Windows)
├── deploy-status.php             # Deployment verification page
│
├── api/
│   ├── get.php                   # GET endpoint (retrieve data)
│   ├── create.php                # POST endpoint (create data)
│   ├── update.php                # PUT endpoint (update data)
│   ├── delete.php                # DELETE endpoint (delete data)
│   └── README.md                 # API documentation
│
├── includes/
│   ├── auth.php                  # Authentication functions
│   ├── Logger.php                # Logging class
│   ├── ErrorHandler.php          # Error handling
│   └── Environment.php           # Environment loader
│
├── tests/
│   ├── functional-test.php       # Comprehensive functional tests
│   └── ExampleTest.php           # PHPUnit example
│
├── index.php                     # Home page
├── login.php                     # Login page
├── register.php                  # Registration page
├── cart.php                      # Shopping cart
├── about.php                     # About page
└── logout.php                    # Logout handler
```

---

## 🔌 API Endpoints

### Get Comics

```bash
GET /api/get.php?type=comics&page=1&limit=10

Response:
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Spider-Man",
      "description": "Amazing adventures",
      "price": 29.99,
      "category_id": 1
    }
  ]
}
```

### Create Comic (Requires Authentication)

```bash
POST /api/create.php
Content-Type: application/json

{
  "title": "New Comic",
  "description": "Description",
  "price": 19.99,
  "category_id": 1
}
```

### Update Comic

```bash
PUT /api/update.php
Content-Type: application/json

{
  "id": 1,
  "title": "Updated Title",
  "price": 24.99
}
```

### Delete Comic

```bash
DELETE /api/delete.php?id=1
```

See [API Documentation](api/README.md) for more endpoints.

---

## 🔒 Security Features

- **Password Hashing:** bcrypt algorithm
- **SQL Injection Prevention:** Prepared statements
- **XSS Protection:** HTML escaping
- **CORS Protection:** Configurable
- **HTTPS Enforcement:** In production
- **Security Headers:** X-Frame-Options, X-Content-Type-Options, etc.
- **File Access Control:** .htaccess restrictions

---

## 📊 Database Configuration

### Development (SQLite)

```
DB_TYPE=sqlite
DB_PATH=./database.sqlite
```

### Production (MySQL)

```
DB_TYPE=mysql
DB_HOST=localhost
DB_NAME=comic_universe
DB_USER=comics_user
DB_PASS=strong_password
DB_PORT=3306
DB_CHARSET=utf8mb4
```

---

## 🛠️ Troubleshooting

### Common Issues

**404 Errors on API Requests**
```bash
# Ensure mod_rewrite is enabled
sudo a2enmod rewrite
sudo systemctl reload apache2
```

**Database Connection Failed**
```bash
# Check .env configuration
cat .env | grep DB_

# Test MySQL connection
mysql -h localhost -u comics_user -p
```

**Permission Denied Errors**
```bash
# Fix file permissions
chmod 755 . logs cache uploads
chmod 644 *.php .htaccess
```

**Debug Mode**
```bash
# Enable debug logging
# In .env, set DEBUG_MODE=true
# Check logs in: ./logs/app_YYYY-MM-DD.log
```

---

## 📝 Configuration Files

### `.env` - Environment Variables

```bash
ENVIRONMENT=production          # development|staging|production
DEBUG_MODE=false                # Enable/disable debug mode
DB_TYPE=mysql                   # sqlite|mysql
DB_HOST=localhost               # MySQL host
DB_NAME=comic_universe          # Database name
DB_USER=comics_user             # Database user
DB_PASS=password                # Database password
SITE_URL=https://domain.com/    # Public URL
```

### `.htaccess` - Apache Configuration

- URL rewriting (remove .php extension)
- Security rules (block sensitive files)
- Compression (gzip)
- Caching headers
- HTTPS redirect

---

## 📚 Documentation

- [Deployment Guide](PRODUCTION_DEPLOYMENT.md) — Complete deployment instructions
- [Security Guidelines](SECURITY.md) — Security best practices
- [API Reference](api/README.md) — API endpoints documentation
- [Postman Collection](comic-universe-api.postman_collection.json) — API testing

---

## 🐛 Logging

All application logs are stored in `logs/` directory:

```bash
# View today's logs
tail -f logs/app_2024-01-15.log

# Search for errors
grep ERROR logs/app_*.log

# Clear old logs (30+ days)
find logs -name "app_*.log" -mtime +30 -delete
```

---

## 🤝 Contributing

1. Create a feature branch (`git checkout -b feature/amazing-feature`)
2. Commit changes (`git commit -m 'Add amazing feature'`)
3. Push to branch (`git push origin feature/amazing-feature`)
4. Open a Pull Request

---

## 📄 License

This project is licensed under the MIT License - see [LICENSE](LICENSE) file for details.

---

## 📞 Support

- 📧 Email: support@comic-universe.local
- 📖 Documentation: See docs/ folder
- 🐛 Issues: Report via GitHub Issues
- 💬 Discussions: Check GitHub Discussions

---

**Last Updated:** 2024-01-15  
**Current Version:** 1.0.0  
**Deployment Status:** Ready for Production

#### 3. Start PHP server

```bash
php -S localhost:8000
```

#### 4. Access the application

- **Main site:** http://localhost:8000/
- **Test suite:** http://localhost:8000/test.php
- **API docs:** http://localhost:8000/api/README.md

---

## 🗂️ Project Structure

```
comic-universe/
├── config.php                 # Configuration (dev/production)
├── db.php                     # Database connection
├── test.php                   # Test suite
├── .htaccess                  # Apache rules
├── .env.example               # Environment variables template
│
├── includes/
│   └── auth.php              # Authentication functions
│
├── api/
│   ├── read.php              # GET list of comics
│   ├── get.php               # GET single comic
│   ├── create.php            # POST create comic
│   ├── update.php            # PUT update comic
│   ├── delete.php            # DELETE comic
│   └── README.md             # API documentation
│
├── pages/
│   ├── index.php             # Main catalog
│   ├── login.php             # Login form
│   ├── register.php          # Registration form
│   ├── cart.php              # Shopping cart
│   ├── about.php             # About page
│   └── logout.php            # Logout handler
│
├── schema.sql                # Database schema
├── styles.css                # Stylesheet
├── database.sqlite           # SQLite database (auto-created)
│
├── logs/                     # Application logs
├── cache/                    # Cache storage
├── uploads/                  # User uploads
│
└── docs/
    ├── DEPLOYMENT_CHECKLIST.md    # Pre-deployment checklist
    ├── POSTMAN_TESTS.md           # API testing guide
    └── SECURITY.md                # Security features
```

---

## ⚙️ Configuration

### Development (SQLite)

File: `config.php`

```php
define('ENVIRONMENT', 'development');
define('DB_TYPE', 'sqlite');
define('DEBUG_MODE', true);
define('SITE_URL', 'http://localhost:8000/');
```

### Production (MySQL)

File: `config.php`

```php
define('ENVIRONMENT', 'production');
define('DB_TYPE', 'mysql');
define('DEBUG_MODE', false);
define('DB_HOST', 'your-db-host.com');
define('DB_NAME', 'comic_universe');
define('DB_USER', 'your-db-user');
define('DB_PASS', 'your-db-password');
define('SITE_URL', 'https://yourdomain.com/');
```

---

## 🧪 Testing

### Run Test Suite

```bash
# Command line
php test.php

# Or via browser
http://localhost:8000/test.php
```

### Postman API Testing

1. Import collection: `comic-universe-api.postman_collection.json`
2. Set environment variable: `base_url = http://localhost:8000/api`
3. Run Collection Runner
4. All 30+ tests should pass

---

## 🔐 Security Features

✅ **Bcrypt Password Hashing** — `password_hash()` with PASSWORD_BCRYPT  
✅ **SQL Injection Prevention** — PDO prepared statements  
✅ **XSS Protection** — `htmlspecialchars()` on all outputs  
✅ **CSRF Protection** — Session-based token validation  
✅ **Role-Based Access Control** — user/manager/admin roles  
✅ **HTTPOnly Sessions** — Secure cookie settings  

See [SECURITY.md](SECURITY.md) for detailed information.

---

## 📡 API Endpoints

### Comics

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/read.php?limit=10` | Get list of comics |
| GET | `/api/get.php?id=1` | Get single comic |
| POST | `/api/create.php` | Create new comic |
| PUT | `/api/update.php` | Update comic |
| DELETE | `/api/delete.php` | Delete comic |
| GET | `/api/categories.php` | Get all categories |

**Example Request:**

```bash
curl -X GET "http://localhost:8000/api/read.php?limit=5" \
  -H "Content-Type: application/json"
```

**Example Response:**

```json
{
  "status": "success",
  "count": 5,
  "data": [
    {
      "id": 1,
      "title": "Мстители",
      "price": 4500,
      "image": "url-to-image.jpg",
      "category": "Marvel"
    }
  ]
}
```

See [api/README.md](api/README.md) for complete API documentation.

---

## 📦 Deployment

### Production Deployment

See [DEPLOYMENT_CHECKLIST.md](DEPLOYMENT_CHECKLIST.md) for complete deployment guide.

**Quick Steps:**

1. **Prepare server** — PHP 8.1+, MySQL 5.7+, Apache 2.4+
2. **Clone repository** — `git clone ...`
3. **Configure environment** — Update `config.php`
4. **Migrate database** — Import schema to MySQL
5. **Set permissions** — `chmod 755 logs cache uploads`
6. **Enable HTTPS** — Install SSL certificate
7. **Run tests** — Verify everything works

---

## 🛠️ Development

### Adding New Features

1. Create branch: `git checkout -b feature/new-feature`
2. Make changes
3. Test locally: `php test.php`
4. Commit: `git commit -m "Add new feature"`
5. Push: `git push origin feature/new-feature`
6. Create Pull Request

### Database Migrations

**SQLite to MySQL:**

```bash
sqlite3 database.sqlite ".dump" > backup.sql
mysql -u root -p comic_universe < backup.sql
```

---

## 🐛 Troubleshooting

### Database Connection Error

```php
// Check config.php
echo config('DB_TYPE'); // Should output: sqlite or mysql
echo config('DB_HOST'); // Should output correct host
```

### Permission Denied

```bash
chmod 777 logs cache uploads
chown -R www-data:www-data /var/www/comic-universe
```

### 404 Errors

- Verify `.htaccess` is in root directory
- Check Apache `mod_rewrite` is enabled
- Ensure URLs don't have `.php` extension

---

## 📚 Documentation

- [API Reference](api/README.md) — Complete API documentation
- [Security Guide](SECURITY.md) — Authentication & authorization
- [Deployment Checklist](DEPLOYMENT_CHECKLIST.md) — Production deployment
- [Postman Testing](POSTMAN_TESTS.md) — API testing guide

---

## 📝 License

This project is open source and licensed under the MIT License.

---

## 👥 Support

For issues and questions:
- GitHub Issues: [Submit Issue](https://github.com/antonshimin08/sitecomics2/issues)
- Email: support@comicuniverse.com
- Documentation: [Read Docs](docs/)

---

## 🙏 Credits

**Development Team:**
- Anton Shimin — Lead Developer
- Comic Universe Contributors

**Technologies Used:**
- PHP 8.1
- SQLite / MySQL
- Apache 2.4
- HTML5 / CSS3
- Postman

---

**Last Updated:** 28 May 2026  
**Current Version:** 1.0.0
