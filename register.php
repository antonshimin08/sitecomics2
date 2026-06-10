<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/myauth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: /index.php');
    exit();
}

$error = '';
$success = '';
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    
    // Validate username
    if (empty($username)) {
        $error = 'Пожалуйста, введите имя пользователя';
    } elseif (!validateUsername($username)) {
        $error = 'Имя пользователя: 3-20 символов, только буквы, цифры и подчёркивание';
    }
    
    // Validate email
    if (!$error && empty($email)) {
        $error = 'Пожалуйста, введите email';
    } elseif (!$error && !validateEmail($email)) {
        $error = 'Пожалуйста, введите корректный email';
    }
    
    // Validate password
    if (!$error && empty($password)) {
        $error = 'Пожалуйста, введите пароль';
    } elseif (!$error && !validatePassword($password)) {
        $error = 'Пароль должен содержать минимум 6 символов';
    } elseif (!$error && $password !== $passwordConfirm) {
        $error = 'Пароли не совпадают';
    }
    
    // Register user if validation passed
    if (!$error) {
        try {
            // Check if username already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = 'Это имя пользователя уже занято';
            } else {
                // Check if email already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = 'Этот email уже зарегистрирован';
                } else {
                    // Insert new user
                    $passwordHash = hashPassword($password);
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$username, $email, $passwordHash, 'user']);
                    
                    $success = 'Регистрация успешна! Теперь вы можете <a href="/login.php">войти</a>';
                    $username = '';
                    $email = '';
                }
            }
        } catch (PDOException $e) {
            $error = 'Ошибка при регистрации: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация - Comic Universe</title>
   <link rel="stylesheet" href="http://comic-universe.xo.je/main.css?v=<?= time() ?>">
    <style>
        .register-container {
            max-width: 450px;
            margin: 50px auto;
            padding: 30px;
            background: #f5f5f5;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .register-container h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 5px rgba(0,123,255,0.5);
        }
        
        .form-group button {
            width: 100%;
            padding: 12px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .form-group button:hover {
            background: #218838;
        }
        
        .error-message {
            color: #d32f2f;
            background: #ffebee;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #d32f2f;
        }
        
        .success-message {
            color: #388e3c;
            background: #e8f5e9;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #388e3c;
        }
        
        .success-message a {
            color: #388e3c;
            font-weight: bold;
        }
        
        .register-footer {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        
        .register-footer a {
            color: #007bff;
            text-decoration: none;
        }
        
        .register-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <nav style="background: #333; color: white; padding: 15px; text-align: center;">
        <a href="/index.php" style="color: white; text-decoration: none; font-weight: bold;">← Вернуться в каталог</a>
    </nav>
    
    <div class="register-container">
        <h1>📝 Регистрация</h1>
        
        <?php if ($error): ?>
            <div class="error-message"><?php echo sanitizeOutput($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Имя пользователя (3-20 символов):</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    value="<?php echo sanitizeOutput($username); ?>"
                    required 
                    placeholder="Введите имя пользователя"
                >
            </div>
            
            <div class="form-group">
                <label for="email">Email:</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    value="<?php echo sanitizeOutput($email); ?>"
                    required 
                    placeholder="example@email.com"
                >
            </div>
            
            <div class="form-group">
                <label for="password">Пароль (минимум 6 символов):</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required 
                    placeholder="Введите пароль"
                >
            </div>
            
            <div class="form-group">
                <label for="password_confirm">Подтвердите пароль:</label>
                <input 
                    type="password" 
                    id="password_confirm" 
                    name="password_confirm" 
                    required 
                    placeholder="Повторите пароль"
                >
            </div>
            
            <div class="form-group">
                <button type="submit">Зарегистрироваться</button>
            </div>
        </form>
        
        <div class="register-footer">
            Уже есть аккаунт? <a href="/login.php">Войти</a>
        </div>
    </div>
</body>
</html>
