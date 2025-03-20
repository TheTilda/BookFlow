<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/add_to_library_errors.log');
// Включите буферизацию вывода в самом начале
ob_start();

// Установите заголовки
header('Content-Type: application/json; charset=utf-8');
session_start();

try {
    require __DIR__ . '/../../db.php';

    // Проверка авторизации
    if (!isset($_SESSION['user'])) {
        throw new Exception('Требуется авторизация', 401);
    }

    // Валидация ID книги
    if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
        throw new Exception('Неверный ID книги', 400);
    }
    $book_id = (int)$_GET['id'];
    $user_id = $_SESSION['user']['id']; // Используйте user_id вместо id

    // Проверка существования книги
    $stmt = $pdo->prepare("SELECT id FROM books WHERE id = ?");
    $stmt->execute([$book_id]);
    if (!$stmt->fetch()) {
        throw new Exception('Книга не найдена', 404);
    }

    // Проверка дубликата
    $stmt = $pdo->prepare("SELECT user_id FROM user_library WHERE user_id = ? AND book_id = ?");
    $stmt->execute([$user_id, $book_id]);
    if ($stmt->fetch()) {
        throw new Exception('Книга уже в библиотеке', 409);
    }

    // Добавление в библиотеку
    $stmt = $pdo->prepare("INSERT INTO user_library (user_id, book_id) VALUES (?, ?)");
    $stmt->execute([$user_id, $book_id]);

    // Успешный ответ
    echo json_encode([
        'success' => true,
        'message' => 'Книга успешно добавлена!'
    ]);

} catch (PDOException $e) {
    error_log('Ошибка БД: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Ошибка сервера'
    ]);
} catch (Exception $e) {
    http_response_code($e->getCode());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} finally {
    // Очистите буфер и завершите выполнение
    ob_end_flush();
    exit();
}