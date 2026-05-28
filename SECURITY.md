# Безопасность веб-приложения Comic Universe

## Описание

Документ описывает реализованные меры безопасности в e-commerce приложении Comic Universe: аутентификация пользователей, валидация форм, защита от XSS-атак и система разграничения прав доступа.

---

## 1️⃣ Аутентификация и управление сессией

### Структура данных

**Таблица `users` в БД:**
```sql
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    role TEXT DEFAULT 'user',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

### Функции авторизации

**Файл: `includes/auth.php`**

| Функция | Назначение |
|---------|-----------|
| `isLoggedIn()` | Проверка авторизации пользователя |
| `requireLogin()` | Блокировка доступа неавторизованным |
| `requireRole($role)` | Проверка роли пользователя |
| `logout()` | Выход пользователя |

### Хранение пароля

- ✅ **Bcrypt хеширование:** `password_hash($password, PASSWORD_BCRYPT)`
- ✅ **Проверка:** `password_verify($password, $hash)`
- ❌ **Никогда:** пароли не хранятся в открытом виде

---

## 2️⃣ Валидация форм

### Серверная валидация входных данных

#### login.php - Вход пользователя
```php
// Проверка пустых полей
if (empty($username) || empty($password)) {
    $error = 'Пожалуйста, заполните все поля';
}

// Проверка учётных данных в БД
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password_hash'])) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
}
```

#### register.php - Регистрация
```php
// Валидация имени пользователя
if (!validateUsername($username)) {
    // 3-20 символов, только буквы, цифры, подчёркивание
    $error = 'Имя пользователя: 3-20 символов, буквы, цифры, подчёркивание';
}

// Валидация email
if (!validateEmail($email)) {
    $error = 'Пожалуйста, введите корректный email';
}

// Валидация пароля
if (!validatePassword($password)) {
    // Минимум 6 символов
    $error = 'Пароль должен содержать минимум 6 символов';
}

// Проверка дублей
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$username]);
if ($stmt->fetch()) {
    $error = 'Это имя пользователя уже занято';
}
```

### Функции валидации (auth.php)

```php
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePassword($password) {
    return strlen($password) >= 6;
}

function validateUsername($username) {
    // 3-20 символов, буквы, цифры, подчёркивание
    return strlen($username) >= 3 && 
           strlen($username) <= 20 && 
           preg_match('/^[a-zA-Z0-9_]+$/', $username);
}
```

---

## 3️⃣ Защита от XSS-атак

### htmlspecialchars() - Санитизация вывода

**Используется при выводе данных из БД:**

```php
// В формах
<input value="<?= htmlspecialchars($username, ENT_QUOTES) ?>">

// В списках
<?= htmlspecialchars($comic['title'], ENT_QUOTES) ?>

// В сообщениях об ошибках
<div class="error"><?= htmlspecialchars($error) ?></div>

// В изображениях
<img src="<?= htmlspecialchars($image_path, ENT_QUOTES) ?>">
```

### Параметры функции

| Параметр | Значение | Назначение |
|----------|---------|-----------|
| ENT_QUOTES | HTML сущности | Преобразует ' и " |
| UTF-8 | Кодировка | Корректная обработка русского текста |

### Примеры защиты

**ДО (уязвимо):**
```php
<p><?= $user_input ?></p>
// Если user_input = "<script>alert('XSS')</script>"
// Скрипт выполнится!
```

**ПОСЛЕ (защищено):**
```php
<p><?= htmlspecialchars($user_input, ENT_QUOTES) ?></p>
// Вывод: &lt;script&gt;alert('XSS')&lt;/script&gt;
// Скрипт не выполнится
```

---

## 4️⃣ Разграничение прав доступа

### Система ролей

Таблица ролей (в таблице `users`):

| Роль | Описание | Доступ |
|------|---------|--------|
| `user` | Обычный пользователь | Каталог, корзина, профиль |
| `manager` | Менеджер магазина | Управление товарами |
| `admin` | Администратор | Полный доступ |

### Функции проверки прав

```php
// Проверка авторизации
if (!isLoggedIn()) {
    header('Location: /login.php');
    exit();
}

// Проверка роли
if (!hasRole('admin')) {
    http_response_code(403);
    die('Access Denied');
}

// Комбинированная проверка
requireRole('admin'); // Вход + проверка роли
```

### Примеры использования

**Защита администраторской страницы:**
```php
<?php
require_once 'includes/auth.php';
requireRole('admin');

// Код только для администраторов
?>
```

**Защита пользовательского профиля:**
```php
<?php
require_once 'includes/auth.php';
requireLogin();

// Код только для авторизованных пользователей
?>
```

---

## 5️⃣ SQL-инъекции - Защита через PDO

### Подготовленные запросы (Prepared Statements)

**ДО (уязвимо):**
```php
$sql = "SELECT * FROM users WHERE username = '" . $username . "'";
// SQL-инъекция: username = "' OR '1'='1"
// Результат: SELECT * FROM users WHERE username = '' OR '1'='1'
```

**ПОСЛЕ (защищено):**
```php
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
// Параметр обрабатывается как данные, не код
```

### Примеры в коде

```php
// Login.php
$stmt = $pdo->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ?");
$stmt->execute([$username]);

// Register.php
$stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
$stmt->execute([$username, $email, $passwordHash, 'user']);

// Cart.php
$placeholders = implode(',', array_fill(0, count($productIds), '?'));
$sql = "SELECT * FROM comics WHERE id IN ($placeholders)";
$stmt = $pdo->prepare($sql);
$stmt->execute($productIds);
```

---

## 6️⃣ Защита сессии

### Инициализация сессии

```php
// В каждом защищённом файле
session_start();

// Проверка авторизации
if (!isLoggedIn()) {
    // Перенаправление на вход
}
```

### Данные в сессии

```php
$_SESSION['user_id']   // ID пользователя
$_SESSION['username']  // Имя для отображения
$_SESSION['role']      // Роль для проверки прав
```

### Выход из системы

```php
// logout.php
function logout() {
    session_destroy();
    header('Location: /index.php');
    exit();
}
```

---

## 7️⃣ Интеграция в шаблоны

### Навигация с проверкой авторизации

```php
<nav class="site-nav">
    <a href="index.php">Каталог</a>
    
    <?php if (isLoggedIn()): ?>
        <span>👤 <?= htmlspecialchars(getCurrentUsername()) ?></span>
        <a href="logout.php">Выход</a>
    <?php else: ?>
        <a href="login.php">Вход</a>
        <a href="register.php">Регистрация</a>
    <?php endif; ?>
</nav>
```

### Сообщения об ошибках

```php
<?php if ($error): ?>
    <div class="error-message">
        <?= htmlspecialchars($error, ENT_QUOTES) ?>
    </div>
<?php endif; ?>
```

---

## 📋 Чек-лист безопасности

- ✅ Пароли хешируются Bcrypt
- ✅ Валидация всех входных данных на сервере
- ✅ Санитизация вывода htmlspecialchars()
- ✅ Защита от SQL-инъекций (PDO prepared statements)
- ✅ Система ролей и разграничение доступа
- ✅ Сессии для управления состоянием пользователя
- ✅ Проверка авторизации на каждой защищённой странице

---

## 🔐 Рекомендации для production

1. **HTTPS** — Всегда использовать защищённое соединение
2. **CSRF tokens** — Добавить токены для защиты от CSRF-атак
3. **Rate limiting** — Ограничить попытки входа
4. **Логирование** — Записывать попытки неавторизованного доступа
5. **Двухфакторная аутентификация** — Для админов
6. **Content Security Policy** — Заголовок для защиты от XSS
7. **Regular updates** — Обновлять зависимости и PHP

---

## Результаты тестирования

✅ Регистрация: пользователи создаются с хешированным паролем  
✅ Вход: проверка учётных данных и сохранение в сессии  
✅ Валидация: пустые поля, слабые пароли, дублирующиеся email  
✅ XSS-защита: специальные символы отображаются как текст  
✅ Авторизация: неавторизованные перенаправляются на вход  
✅ Разграничение: разные роли имеют разный доступ  

---

**Файлы реализации:**
- `includes/auth.php` — Функции безопасности
- `login.php` — Форма входа с валидацией
- `register.php` — Форма регистрации с проверками
- `logout.php` — Выход пользователя
- `schema.sql` — Таблица users с ролями
