<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: /index.php');
    exit();
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate input
    if (empty($username) || empty($password)) {
        $error = 'Пожалуйста, заполните все поля';
    } else {
        try {
            // Prepare query with PDO
            $stmt = $pdo->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verify user exists and password is correct
            if ($user && verifyPassword($password, $user['password_hash'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                header('Location: /index.php');
                exit();
            } else {
                $error = 'Неверное имя пользователя или пароль';
            }
        } catch (PDOException $e) {
            $error = 'Ошибка подключения к БД: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход - Comic Universe</title>
    <link rel="stylesheet" href="/styles.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 30px;
            background: #f5f5f5;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .login-container h1 {
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
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .form-group button:hover {
            background: #0056b3;
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
        
        .login-footer {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        
        .login-footer a {
            color: #007bff;
            text-decoration: none;
        }
        
        .login-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <nav style="background: #333; color: white; padding: 15px; text-align: center;">
        <a href="/index.php" style="color: white; text-decoration: none; font-weight: bold;">← Вернуться в каталог</a>
    </nav>
    
    <div class="login-container">
        <h1>🔐 Вход в Comic Universe</h1>
        
        <?php if ($error): ?>
            <div class="error-message"><?php echo sanitizeOutput($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Имя пользователя:</label>
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
                <label for="password">Пароль:</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required 
                    placeholder="Введите пароль"
                >
            </div>
            
            <div class="form-group">
                <button type="submit">Войти</button>
            </div>
        </form>
        
        <div class="login-footer">
            Нет аккаунта? <a href="/register.php">Зарегистрироваться</a>
        </div>
    </div>
</body>
</html>
