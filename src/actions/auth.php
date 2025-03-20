<?php
session_start();
require_once __DIR__ .'/../../db.php';

// Обработка регистрации
if (isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Валидация
    $errors = [];
    
    if (empty($username)) {
        $errors[] = 'Введите имя пользователя';
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Некорректный email';
    }
    
    if (strlen($password) < 6) {
        $errors[] = 'Пароль должен быть не менее 6 символов';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Пароли не совпадают';
    }

    // Проверка уникальности
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetchColumn() > 0) {
        $errors[] = 'Пользователь с таким именем или email уже существует';
    }

    if (empty($errors)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $password_hash]);
        
        $_SESSION['success'] = 'Регистрация прошла успешно! Теперь войдите в систему.';
        header('Location: /login.php');
        exit;
    }
    print_r($errors);
}

// Обработка входа
if (isset($_POST['login'])) {
    $login = $_POST['login'];
    $password = $_POST['password'];


    // Ищем пользователя по email или username
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$login, $login]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);



    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role']
        ];
        header('Location: /books.php');
        echo "Вы успешно вошли в аккаунт";
        exit;
    } else {
        $errors[] = 'Неверные учетные данные';
        print_r($user);

    }
    print_r($errors);
    if ($user) {
        echo'юзер есть';
    }else{
        echo'юзера нет';
    }
}

// Выход
if (isset($_GET['logout'])) {
    unset($_SESSION['user']);
    header('Location: index.php');
    exit;
}
?>


