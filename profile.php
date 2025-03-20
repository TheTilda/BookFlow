<?php
session_start();
require 'db.php';

$page = "profile";

// Проверка авторизации
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// Получение актуальных данных пользователя
$user_id = $_SESSION['user']['id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$errors = [];
$success = '';

// Обработка формы обновления
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Валидация
    if (empty($username)) {
        $errors[] = 'Имя пользователя обязательно';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Некорректный email';
    }

    // Проверка уникальности email и username
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE (email = ? OR username = ?) AND id != ?");
    $stmt->execute([$email, $username, $user_id]);
    if ($stmt->fetchColumn() > 0) {
        $errors[] = 'Email или имя пользователя уже заняты';
    }

    // Проверка паролей
    if (!empty($new_password)) {
        if (strlen($new_password) < 6) {
            $errors[] = 'Пароль должен быть не менее 6 символов';
        }
        if ($new_password !== $confirm_password) {
            $errors[] = 'Пароли не совпадают';
        }
    }

    if (empty($errors)) {
        $update_data = [
            'username' => $username,
            'email' => $email,
            'id' => $user_id
        ];

        $sql = "UPDATE users SET username = :username, email = :email";

        // Обновление пароля при необходимости
        if (!empty($new_password)) {
            $update_data['password_hash'] = password_hash($new_password, PASSWORD_DEFAULT);
            $sql .= ", password_hash = :password_hash";
        }

        $sql .= " WHERE id = :id";

        $stmt = $pdo->prepare($sql);
        if ($stmt->execute($update_data)) {
            // Обновляем данные в сессии
            $_SESSION['user']['username'] = $username;
            $_SESSION['user']['email'] = $email;
            
            $success = 'Профиль успешно обновлен!';
            // Обновляем данные для отображения
            $user = array_merge($user, $update_data);
        } else {
            $errors[] = 'Ошибка при обновлении профиля';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль пользователя</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .profile-card {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2.5rem;
            background: #fff;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border-radius: 15px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .profile-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.15);
        }
        .avatar {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #fff;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
        }
        .form-control {
            border-radius: 8px;
            padding: 0.75rem;
            border: 1px solid #ddd;
            transition: border-color 0.3s;
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
        .btn-danger {
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            transition: background-color 0.3s;
        }
        .btn-danger:hover {
            background-color: #c82333;
        }
        .list-group-item {
            border: none;
            padding: 0.75rem 1.25rem;
            background-color: #f8f9fa;
            margin-bottom: 0.5rem;
            border-radius: 8px;
        }
        .list-group-item:last-child {
            margin-bottom: 0;
        }
        .alert {
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container" style="padding-top: 30px;">
        <div class="profile-card">
            
            <h2 class="text-center mb-4">Профиль пользователя</h2>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <p><?= $error ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <div class="mb-3">
                    <label class="form-label">Имя пользователя</label>
                    <input type="text" name="username" class="form-control" 
                           value="<?= htmlspecialchars($user['username']) ?>" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" 
                           value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Новый пароль (оставьте пустым, если не хотите менять)</label>
                    <input type="password" name="new_password" class="form-control">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Подтвердите новый пароль</label>
                    <input type="password" name="confirm_password" class="form-control">
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" name="update_profile" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Сохранить изменения
                    </button>
                    <a href="/admin.php" class="btn btn-primary">
                        <i class="fas fa-sign-out-alt me-2"></i>Админ-панель
                    </a>
                    <a href="/src/actions/logout.php" class="btn btn-danger">
                        <i class="fas fa-sign-out-alt me-2"></i>Выйти
                    </a>
                </div>
            </form>

            <div class="mt-4">
                <h4 class="mb-3">Дополнительная информация</h4>
                <ul class="list-group">
                    <li class="list-group-item">
                        <i class="fas fa-calendar-alt me-2"></i>
                        Дата регистрации: <?= date('d.m.Y H:i', strtotime($user['created_at'])) ?>
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-clock me-2"></i>
                        Последний вход: <?= $user['last_login'] ? date('d.m.Y H:i', strtotime($user['last_login'])) : 'Еще не входил' ?>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>