# 🚀 Production Deployment Guide
# Comic Universe E-Commerce Platform

## Этап 1: Подготовка к развёртыванию

### 1.1 Проверка требований сервера

**Системные требования:**
- PHP 8.1+ (проверить: `php -v`)
- Apache с mod_rewrite
- MySQL 5.7+ или SQLite 3
- OpenSSL для HTTPS
- cURL для API запросов

**Проверить установленные расширения:**
```bash
php -m | grep -E "pdo|json|curl|mbstring"
```

### 1.2 Загрузка файлов проекта

```bash
# Загрузить файлы на сервер
scp -r ./sitecomics2 user@server:/var/www/html/

# Подключиться к серверу
ssh user@server

# Перейти в директорию проекта
cd /var/www/html/sitecomics2
```

### 1.3 Установка зависимостей

```bash
# Установить Composer зависимости
composer install --no-dev --optimize-autoloader

# Установить права доступа
chmod 755 .
chmod 644 *.php *.css
chmod 755 includes api logs cache
chmod 644 .htaccess
```

---

## Этап 2: Конфигурация окружения

### 2.1 Создание .env файла

```bash
# Скопировать пример конфигурации
cp .env.example .env

# Отредактировать .env для production
nano .env
```

**Настройки для production (.env):**
```
ENVIRONMENT=production
DEBUG_MODE=false
LOG_ERRORS=true
LOG_PATH=./logs/

# MySQL конфигурация
DB_TYPE=mysql
DB_HOST=localhost
DB_NAME=comic_universe
DB_USER=comics_user
DB_PASS=strong_password_here
DB_PORT=3306
DB_CHARSET=utf8mb4

# Приложение
APP_NAME=Comic Universe
APP_VERSION=1.0.0
SITE_URL=https://your-domain.com/

# Security
SESSION_TIMEOUT=3600
```

### 2.2 Создание MySQL пользователя

```bash
# Подключиться к MySQL
mysql -u root -p

# Выполнить в MySQL
CREATE DATABASE comic_universe;
CREATE USER 'comics_user'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON comic_universe.* TO 'comics_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 2.3 Инициализация базы данных

```bash
# Запустить скрипт инициализации БД
php setup-db.php

# Проверить инициализацию
php setup-db.php verify
```

**Ожидаемый результат:**
```
✓ Connected to mysql database
✓ Database schema created/updated
✓ Found 5 tables: users, comics, categories, orders, order_items
✅ Database initialization successful!
```

---

## Этап 3: Конфигурация веб-сервера (Apache)

### 3.1 Создание Virtual Host

```bash
# Создать конфигурацию
sudo nano /etc/apache2/sites-available/comic-universe.conf
```

**Содержимое конфигурации:**
```apache
<VirtualHost *:80>
    ServerName your-domain.com
    ServerAlias www.your-domain.com
    ServerAdmin admin@your-domain.com
    
    DocumentRoot /var/www/html/sitecomics2
    
    # Enable mod_rewrite
    <Directory /var/www/html/sitecomics2>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    # Logs
    ErrorLog ${APACHE_LOG_DIR}/comic-universe-error.log
    CustomLog ${APACHE_LOG_DIR}/comic-universe-access.log combined
    
    # Redirect HTTP to HTTPS
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</VirtualHost>

<VirtualHost *:443>
    ServerName your-domain.com
    ServerAlias www.your-domain.com
    ServerAdmin admin@your-domain.com
    
    DocumentRoot /var/www/html/sitecomics2
    
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/your-domain.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/your-domain.com/privkey.pem
    
    <Directory /var/www/html/sitecomics2>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/comic-universe-error.log
    CustomLog ${APACHE_LOG_DIR}/comic-universe-access.log combined
</VirtualHost>
```

### 3.2 Включение Virtual Host

```bash
# Включить сайт
sudo a2ensite comic-universe.conf

# Включить mod_rewrite (если ещё не включён)
sudo a2enmod rewrite
sudo a2enmod ssl

# Перезагрузить Apache
sudo systemctl reload apache2
```

### 3.3 Настройка HTTPS с Let's Encrypt

```bash
# Установить Certbot
sudo apt-get install certbot python3-certbot-apache

# Получить сертификат
sudo certbot certonly --apache -d your-domain.com -d www.your-domain.com

# Автоматическая перезагрузка сертификата (добавить в cron)
sudo crontab -e
# Добавить строку:
0 0 1 * * certbot renew --quiet
```

---

## Этап 4: Оптимизация производительности

### 4.1 Включение сжатия Gzip

```bash
# Проверить mod_deflate
sudo a2enmod deflate

# Перезагрузить Apache
sudo systemctl reload apache2
```

### 4.2 Кэширование заголовков

```bash
# Проверить mod_expires
sudo a2enmod expires
sudo systemctl reload apache2
```

### 4.3 Оптимизация PHP

```bash
# Редактировать php.ini
sudo nano /etc/php/8.1/apache2/php.ini

# Рекомендованные значения для production:
memory_limit = 256M
max_execution_time = 30
upload_max_filesize = 50M
post_max_size = 50M
display_errors = Off
log_errors = On
error_log = /var/log/php_errors.log
```

---

## Этап 5: Финальное тестирование

### 5.1 Проверка статуса развёртывания

```bash
# Локальная проверка
curl -H "Accept: application/json" https://your-domain.com/deploy-status.php

# Браузер
https://your-domain.com/deploy-status.php
```

### 5.2 Проверка функциональности

```bash
# Главная страница
curl https://your-domain.com/

# API endpoint
curl -H "Content-Type: application/json" https://your-domain.com/api/get.php?type=comics

# Логирование
tail -f /var/www/html/sitecomics2/logs/app_$(date +%Y-%m-%d).log
```

### 5.3 Проверка безопасности

```bash
# Проверить доступ к конфиг файлам
curl https://your-domain.com/config.php       # 403 Forbidden (ожидается)
curl https://your-domain.com/.env             # 403 Forbidden (ожидается)
curl https://your-domain.com/database.sqlite  # 403 Forbidden (ожидается)

# Проверить HTTPS
curl -I https://your-domain.com/
# Должно быть: HTTP/2 200 или HTTP/1.1 200
```

---

## Этап 6: Мониторинг и обслуживание

### 6.1 Мониторинг логов

```bash
# Просмотр ошибок в реальном времени
tail -f /var/www/html/sitecomics2/logs/app_*.log

# Поиск ошибок за период
grep ERROR /var/www/html/sitecomics2/logs/app_*.log

# Очистка старых логов
find /var/www/html/sitecomics2/logs -name "*.log" -mtime +30 -delete
```

### 6.2 Резервное копирование

```bash
# Создать резервную копию БД
mysqldump -u comics_user -p comic_universe > backup_$(date +%Y-%m-%d).sql

# Создать резервную копию файлов
tar -czf backup_files_$(date +%Y-%m-%d).tar.gz /var/www/html/sitecomics2

# Автоматическое резервное копирование (cron)
sudo crontab -e
# Добавить:
0 2 * * * mysqldump -u comics_user -p password comic_universe > /backups/db_$(date +\%Y-\%m-\%d).sql
```

### 6.3 Обновления и патчи

```bash
# Обновить зависимости
composer update --no-dev

# Проверить обновления PHP
php -v

# Перезагрузить приложение после обновлений
sudo systemctl reload apache2
```

---

## Чек-лист финального развёртывания

- [ ] PHP версия 8.1+ установлена
- [ ] Все расширения PHP установлены
- [ ] .env файл создан и настроен для production
- [ ] DEBUG_MODE = false
- [ ] ENVIRONMENT = production
- [ ] MySQL база данных создана
- [ ] Таблицы инициализированы (setup-db.php)
- [ ] Virtual Host Apache настроен
- [ ] HTTPS/SSL сертификат установлен
- [ ] .htaccess файл загружен
- [ ] Права доступа установлены правильно
- [ ] gzip сжатие включено
- [ ] Кэширование включено
- [ ] deploy-status.php возвращает OK
- [ ] Все страницы загружаются
- [ ] API endpoints работают
- [ ] Логирование функционирует
- [ ] Резервные копии настроены
- [ ] Мониторинг настроен

---

## Решение типичных проблем

### Проблема: 404 при запросе к API endpoints

**Решение:**
```bash
# Проверить mod_rewrite
sudo a2enmod rewrite

# Перезагрузить Apache
sudo systemctl reload apache2

# Проверить .htaccess
curl -I https://your-domain.com/.htaccess
# Должно быть 403 Forbidden (файл защищён)
```

### Проблема: 500 Internal Server Error

**Решение:**
```bash
# Проверить логи Apache
sudo tail -f /var/log/apache2/error.log

# Проверить логи приложения
tail -f /var/www/html/sitecomics2/logs/app_*.log

# Проверить права доступа
chmod 755 /var/www/html/sitecomics2
chmod 755 /var/www/html/sitecomics2/logs
```

### Проблема: Ошибка подключения к БД

**Решение:**
```bash
# Проверить конфигурацию .env
cat /var/www/html/sitecomics2/.env | grep DB_

# Проверить MySQL
mysql -u comics_user -p -e "SELECT 1"

# Проверить статус MySQL
sudo systemctl status mysql
```

---

## Контакты и поддержка

- **Email:** admin@your-domain.com
- **Документация:** README.md
- **Логирование:** `/logs/app_YYYY-MM-DD.log`
- **Статус:** `https://your-domain.com/deploy-status.php`

---

**Дата развёртывания:** {{ deployment_date }}
**Версия приложения:** 1.0.0
**Окружение:** Production
