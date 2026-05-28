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
