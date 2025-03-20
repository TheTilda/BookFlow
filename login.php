<?php
session_start();
$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['errors']); // Очищаем ошибки после отображения
$success = $_SESSION['success'] ?? '';
unset($_SESSION['success']); // Очищаем успешное сообщение после отображения
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход | BookFlow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .login-container {
            background: #fff;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 100%;
        }
        .login-container h2 {
            color: #333;
            font-weight: bold;
            margin-bottom: 1.5rem;
        }
        .form-control {
            border-radius: 8px;
            padding: 0.75rem;
            border: 1px solid #ddd;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.25);
        }
        .btn-primary {
            background-color: #007bff;
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            transition: background-color 0.3s;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .btn-link {
            color: #007bff;
            text-decoration: none;
        }
        .btn-link:hover {
            text-decoration: underline;
        }
        .alert {
            border-radius: 8px;
        }
        @media (max-width: 576px) {
            .login-container {
                padding: 1.5rem;
            }
            .login-container h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2 class="text-center">Вход</h2>
        
        <!-- Уведомления об успехе -->
        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <!-- Уведомления об ошибках -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <p><?= $error ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Форма входа -->
        <form action="/src/actions/auth.php" method="post">
            <div class="mb-3">
                <label class="form-label">Email или имя пользователя</label>
                <input type="text" name="login" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Пароль</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" name="login-btn" class="btn btn-primary w-100">Войти</button>
            <div class="text-center mt-3">
                <a href="register.php" class="btn-link">Нет аккаунта? Зарегистрироваться</a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>