<?php
session_start();
require 'db.php';

// Проверка авторизации
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// Проверка наличия ID книги
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID книги не указан.");
}

$book_id = (int)$_GET['id'];

// Получаем информацию о книге
$stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
$stmt->execute([$book_id]);
$book = $stmt->fetch();

if (!$book) {
    die("Книга не найдена.");
}

// Путь к файлу книги
$book_path = __DIR__ . "/books/{$book_id}/book.fb2";

if (!file_exists($book_path)) {
    die("Файл книги не найден.");
}

// Парсим FB2 файл
$xml = simplexml_load_file($book_path);
if (!$xml) {
    die("Ошибка при чтении файла книги.");
}

// Извлекаем заголовок и содержимое книги
$title = (string)$xml->description->{'title-info'}->{'book-title'};
$content = '';

// Обрабатываем body книги
foreach ($xml->body->section as $section) {
    foreach ($section->p as $paragraph) {
        $content .= "<p>" . htmlspecialchars((string)$paragraph) . "</p>";
    }
}

// Разбиваем текст на страницы
$lines = explode("\n", wordwrap($content, 10000, "\n")); // 1000 символов на страницу
$total_pages = count($lines);
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

if ($current_page < 1 || $current_page > $total_pages) {
    $current_page = 1;
}

$page_content = $lines[$current_page - 1];
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Читалка: <?= htmlspecialchars($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px;
        }
        .reader-container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }
        .reader-container h1 {
            font-size: 2rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .content {
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 1rem;
        }
        .navigation {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
        }
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
        }
        .page-info {
            text-align: center;
            margin: 1rem 0;
            font-size: 1rem;
            color: #666;
        }
    </style>
</head>
<body>
    <a href="/books.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> На главную
    </a>
    <div class="reader-container">
        <h1><?= htmlspecialchars($title) ?></h1>
        <!-- Содержимое книги -->
        <div class="content">
            <?= $page_content ?>
        </div>

        <!-- Информация о странице -->
        <div class="page-info">
            Страница <?= $current_page ?> из <?= $total_pages ?>
        </div>

        <!-- Навигация -->
        <div class="navigation">
            <?php if ($current_page > 1): ?>
                <a href="read.php?id=<?= $book_id ?>&page=<?= $current_page - 1 ?>" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Назад
                </a>
            <?php else: ?>
                <button class="btn btn-primary" disabled>
                    <i class="fas fa-arrow-left"></i> Назад
                </button>
            <?php endif; ?>

            <?php if ($current_page < $total_pages): ?>
                <a href="read.php?id=<?= $book_id ?>&page=<?= $current_page + 1 ?>" class="btn btn-primary">
                    Вперед <i class="fas fa-arrow-right"></i>
                </a>
            <?php else: ?>
                <button class="btn btn-primary" disabled>
                    Вперед <i class="fas fa-arrow-right"></i>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/js/all.min.js"></script>
</body>
</html>