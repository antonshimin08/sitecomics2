# 📊 Post-Deployment Monitoring & Maintenance

## Цель
Обеспечить стабильность, безопасность и производительность Comic Universe приложения в production среде.

---

## 1️⃣ Мониторинг в реальном времени

### 1.1 Мониторинг логов приложения

```bash
# Просмотр логов в реальном времени
tail -f /var/www/html/sitecomics2/logs/app_$(date +%Y-%m-%d).log

# Поиск ошибок
grep ERROR /var/www/html/sitecomics2/logs/app_*.log | head -20

# Подсчёт ошибок по типам
grep '\[ERROR\]' /var/www/html/sitecomics2/logs/app_*.log | wc -l
```

### 1.2 Мониторинг Apache логов

```bash
# Ошибки Apache
tail -f /var/log/apache2/error.log

# Доступ к сайту
tail -f /var/log/apache2/access.log

# Поиск 5xx ошибок
grep "5[0-9][0-9]" /var/log/apache2/access.log
```

### 1.3 Мониторинг производительности

```bash
# Использование памяти PHP
ps aux | grep php | grep -v grep

# Использование CPU
top -p $(pgrep -d, apache2|php)

# Проверка места на диске
df -h /var/www/html/sitecomics2/

# Размер папок
du -sh /var/www/html/sitecomics2/{logs,cache,uploads}
```

---

## 2️⃣ Резервное копирование

### 2.1 Резервное копирование БД

```bash
# Создать резервную копию
mysqldump -u comics_user -p comic_universe > backup_$(date +%Y-%m-%d_%H-%M-%S).sql

# Автоматическое резервное копирование (cron)
# Запустить: sudo crontab -e
# Добавить строку:
0 2 * * * mysqldump -u comics_user -p password comic_universe > /backups/db_$(date +\%Y-\%m-\%d).sql 2>/dev/null

# Для множественных дневных резервных копий:
0 */6 * * * mysqldump -u comics_user -ppassword comic_universe > /backups/db_$(date +\%Y-\%m-\%d-\%H).sql
```

### 2.2 Резервное копирование файлов

```bash
# Создать архив проекта
tar -czf backup_files_$(date +%Y-%m-%d).tar.gz \
  --exclude='logs' \
  --exclude='cache' \
  --exclude='vendor' \
  --exclude='.git' \
  /var/www/html/sitecomics2

# Автоматическое резервное копирование файлов (cron)
0 3 * * * tar -czf /backups/files_$(date +\%Y-\%m-\%d).tar.gz \
  --exclude='logs' --exclude='cache' --exclude='vendor' \
  /var/www/html/sitecomics2
```

### 2.3 Проверка целостности резервных копий

```bash
# Проверить размер БД резервной копии
ls -lh backup_*.sql

# Проверить наличие резервной копии в cron
sudo grep -i mysqldump /var/spool/cron/crontabs/*
```

---

## 3️⃣ Обновления и патчи

### 3.1 Обновить PHP

```bash
# Проверить текущую версию
php -v

# Обновить PHP
sudo apt update
sudo apt upgrade php*

# Проверить обновления
sudo apt list --upgradable
```

### 3.2 Обновить Composer зависимости

```bash
# Проверить обновления
composer outdated

# Обновить зависимости
composer update --no-dev

# После обновления - перезагрузить приложение
sudo systemctl reload apache2
```

### 3.3 Обновить исходный код приложения

```bash
# Pull новые версии из git
cd /var/www/html/sitecomics2
git fetch origin
git pull origin main

# Или скачать напрямую
wget https://github.com/user/repo/archive/refs/heads/main.zip
unzip main.zip
```

---

## 4️⃣ Очистка и оптимизация

### 4.1 Очистить старые логи

```bash
# Удалить логи старше 30 дней
find /var/www/html/sitecomics2/logs -name "app_*.log" -mtime +30 -delete

# Или создать скрипт в cron:
0 0 * * * find /var/www/html/sitecomics2/logs -name "*.log" -mtime +30 -delete
```

### 4.2 Очистить кэш

```bash
# Удалить все файлы кэша
rm -rf /var/www/html/sitecomics2/cache/*

# Автоматическая очистка кэша (cron)
0 1 * * * rm -rf /var/www/html/sitecomics2/cache/*
```

### 4.3 Оптимизировать БД

```bash
# MySQL оптимизация
mysql -u comics_user -p -e "OPTIMIZE TABLE comic_universe.*"

# Или все таблицы
mysqlcheck -u comics_user -p comic_universe --optimize

# Автоматическая оптимизация (cron)
0 2 * * 0 mysqlcheck -u comics_user -p password comic_universe --optimize
```

---

## 5️⃣ Проверка здоровья приложения

### 5.1 Скрипт проверки статуса

```bash
#!/bin/bash
# health-check.sh - Проверить здоровье приложения

echo "🔍 Health Check Status"
echo "====================="

# 1. Проверить веб-сайт
echo -n "Website: "
if curl -s -o /dev/null -w "%{http_code}" https://your-domain.com/ | grep -q "200"; then
    echo "✅ OK"
else
    echo "❌ FAIL"
fi

# 2. Проверить API
echo -n "API: "
if curl -s -H "Accept: application/json" https://your-domain.com/api/get.php?type=comics | grep -q "success"; then
    echo "✅ OK"
else
    echo "❌ FAIL"
fi

# 3. Проверить БД
echo -n "Database: "
if mysql -u comics_user -p password -e "SELECT 1" comic_universe &>/dev/null; then
    echo "✅ OK"
else
    echo "❌ FAIL"
fi

# 4. Проверить место на диске
echo -n "Disk Space: "
usage=$(df /var/www/html | awk 'NR==2 {print $5}' | cut -d% -f1)
if [ $usage -lt 90 ]; then
    echo "✅ OK ($usage% used)"
else
    echo "⚠️  WARNING ($usage% used)"
fi

# 5. Проверить ошибки
echo -n "Recent Errors: "
errors=$(grep ERROR /var/www/html/sitecomics2/logs/app_*.log 2>/dev/null | wc -l)
if [ $errors -eq 0 ]; then
    echo "✅ No errors"
else
    echo "⚠️  $errors errors found"
fi

echo "====================="
```

### 5.2 Автоматическая проверка здоровья (cron)

```bash
# Запустить health check каждый час и отправить результаты на email
0 * * * * bash /var/www/html/sitecomics2/health-check.sh | mail -s "Comic Universe Health Check" admin@domain.com
```

---

## 6️⃣ Безопасность

### 6.1 Обновление безопасности

```bash
# Автоматические обновления безопасности (Ubuntu)
sudo apt install unattended-upgrades

# Проверить конфигурацию
sudo apt-config shell Unattended::Package::Install

# Запустить обновления
sudo unattended-upgrade
```

### 6.2 Проверка уязвимостей

```bash
# Проверить зависимости на уязвимости
composer audit

# Проверить SSL/TLS
ssllabs-scan https://your-domain.com/
```

### 6.3 Мониторинг доступа

```bash
# Поиск подозрительной активности
grep "403\|401\|SQL" /var/log/apache2/access.log

# Мониторинг неудачных попыток входа
grep "login" /var/www/html/sitecomics2/logs/app_*.log | grep "failed"
```

---

## 7️⃣ Алерты и уведомления

### 7.1 Настройка email уведомлений

```bash
# Установить Postfix для email
sudo apt install postfix

# Тестирование email
echo "Test message" | mail -s "Test" admin@domain.com
```

### 7.2 Скрипт отправки уведомлений

```php
<?php
// send-alert.php

function sendAlert($subject, $message) {
    $to = 'admin@domain.com';
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
    
    $htmlMessage = "<html><body>";
    $htmlMessage .= "<h2>$subject</h2>";
    $htmlMessage .= "<p>$message</p>";
    $htmlMessage .= "<p>Time: " . date('Y-m-d H:i:s') . "</p>";
    $htmlMessage .= "</body></html>";
    
    mail($to, $subject, $htmlMessage, $headers);
}

// Пример использования
if (isset($_GET['error_count']) && $_GET['error_count'] > 100) {
    sendAlert('⚠️ High Error Rate', 'More than 100 errors detected');
}
```

---

## 8️⃣ Документация и отчёты

### 8.1 Генерирование отчёта о статусе

```bash
#!/bin/bash
# generate-report.sh

REPORT_FILE="/var/www/html/sitecomics2/reports/status_$(date +%Y-%m-%d).txt"

cat > $REPORT_FILE << EOF
=== Comic Universe Status Report ===
Generated: $(date)

1. System Info
- Uptime: $(uptime)
- Memory: $(free -h | grep Mem)
- Disk: $(df -h /var/www/html | tail -1)

2. Application
- PHP Version: $(php -v | head -1)
- Last Error: $(tail -1 /var/www/html/sitecomics2/logs/app_*.log)

3. Database
- Size: $(mysql -u comics_user -ppassword -e "SELECT ROUND(SUM(data_length + index_length)/1024/1024, 2) FROM INFORMATION_SCHEMA.TABLES WHERE table_schema = 'comic_universe';" | tail -1) MB

4. Website
- Status: $(curl -s -o /dev/null -w "%{http_code}" https://your-domain.com/)
- SSL: $(echo | openssl s_client -servername your-domain.com -connect your-domain.com:443 2>/dev/null | grep "subject=")

EOF

echo "Report saved: $REPORT_FILE"
```

---

## 9️⃣ Чек-лист еженедельного обслуживания

- [ ] Проверить логи на ошибки
- [ ] Проверить место на диске
- [ ] Проверить производительность БД
- [ ] Обновить зависимости (composer update)
- [ ] Создать резервную копию БД и файлов
- [ ] Проверить SSL сертификат (expiration)
- [ ] Запустить тесты функциональности
- [ ] Проверить доступность сайта

---

## 🔟 Чек-лист ежемесячного обслуживания

- [ ] Обновить PHP и системные пакеты
- [ ] Проверить обновления безопасности
- [ ] Оптимизировать БД (OPTIMIZE TABLE)
- [ ] Очистить старые логи (> 60 дней)
- [ ] Очистить кэш
- [ ] Проверить SSL сертификат (за 1 месяц до истечения)
- [ ] Обновить документацию
- [ ] Создать месячный отчёт о статусе
- [ ] Проверить backup файлы

---

## 📞 Команды быстрой помощи

```bash
# Перезагрузить Apache
sudo systemctl reload apache2
sudo systemctl restart apache2

# Проверить статус Apache
sudo systemctl status apache2

# Перезагрузить MySQL
sudo systemctl restart mysql

# Просмотр процессов PHP
ps aux | grep php

# Проверить открытые порты
sudo netstat -tlnp | grep LISTEN

# Проверить конфигурацию Apache
sudo apache2ctl configtest

# Очистить OPcache
php -r "opcache_reset();"
```

---

**Last Updated:** 2024-01-15  
**Maintenance Version:** 1.0
