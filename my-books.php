<?php
// my-books.php
session_start();
require_once __DIR__ . '/db.php';

// Проверка авторизации
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$page = 'my-books';

$user_id = $_SESSION['user']['id'];

// Обработка удаления книги
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_book'])) {
    $book_id = (int)$_POST['book_id'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM user_library WHERE user_id = ? AND book_id = ?");
        $stmt->execute([$user_id, $book_id]);
        
        $_SESSION['success'] = 'Книга успешно удалена из библиотеки';
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Ошибка при удалении книги: ' . $e->getMessage();
    }
    
    header('Location: my-books.php');
    exit;
}

// Получение книг пользователя
try {
    $stmt = $pdo->prepare("
        SELECT b.id AS book_id, b.title, b.description, b.cover_image, 
               GROUP_CONCAT(a.name SEPARATOR ', ') AS authors
        FROM user_library ul
        JOIN books b ON ul.book_id = b.id
        LEFT JOIN book_authors ba ON b.id = ba.book_id
        LEFT JOIN authors a ON ba.author_id = a.id
        WHERE ul.user_id = ?
        GROUP BY b.id
    ");

    $stmt->execute([$user_id]);
    $books = $stmt->fetchAll();

} catch (PDOException $e) {
    $error = 'Ошибка загрузки библиотеки: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Моя библиотека | BookFlow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .book-card {
            transition: transform 0.3s, box-shadow 0.3s;
            border: none;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 20px;
            height: 450px; /* Увеличиваем высоту карточки */
            display: flex;
            flex-direction: column;
        }
        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .card-img-top {
            height: 300px; /* Увеличиваем высоту изображения */
            object-fit: cover; /* Сохраняем пропорции изображения */
            width: 100%; /* Занимает всю ширину карточки */
        }
        .card-body {
            flex: 1; /* Растягиваем тело карточки на оставшееся пространство */
            display: flex;
            flex-direction: column;
            justify-content: space-between; /* Равномерное распределение элементов */
            padding: 15px;
        }
        .card-title {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }
        .text-muted {
            font-size: 0.9rem;
        }
        .btn {
            font-size: 0.9rem;
        }
        @media (max-width: 767.98px) {
            .book-card {
                height: 400px; /* Уменьшаем высоту для мобильных устройств */
            }
            .card-img-top {
                height: 250px; /* Уменьшаем высоту изображения для мобильных устройств */
            }
            .card-title {
                font-size: 1rem;
            }
            .text-muted {
                font-size: 0.8rem;
            }
            .btn {
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container" style="padding-top: 60px;">
        <h1 class="mb-3">Моя библиотека</h1>
        
        <!-- Уведомления -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <!-- Список книг -->
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
            <?php if (!empty($books)): ?>
                <?php foreach ($books as $book): ?>
                    <div class="col">
                        <div class="card book-card h-100">
                            <!-- Верхняя часть карточки -->
                            <img src="/books/<?= htmlspecialchars($book['book_id'])?>/<?= htmlspecialchars($book['cover_image']) ?>" 
                                class="card-img-top" 
                                alt="<?= htmlspecialchars($book['title']) ?>">

                            <!-- Тело карточки -->
                            <div class="card-body">
                                <h5 class="card-title" onclick="window.location.href='/book.php?id=<?= htmlspecialchars($book['book_id'] ?? '') ?>'" style="cursor: pointer;"><?= htmlspecialchars($book['title']) ?></h5>
                                <p class="text-muted small mb-1"><?= $book['authors'] ?></p>
                                
                                <!-- Кнопка удаления из библиотеки -->
                                <div class="d-grid gap-2">
                                    <form method="POST" 
                                        onsubmit="event.stopPropagation(); return confirm('Вы уверены, что хотите удалить книгу из библиотеки?');">
                                        <input type="hidden" name="book_id" value="<?= htmlspecialchars($book['book_id']) ?>">
                                        <button type="submit" 
                                                name="delete_book" 
                                                class="btn btn-outline-danger w-100">
                                            <i class="fas fa-trash me-2"></i>
                                            <span class="button-text">Удалить из библиотеки</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        В вашей библиотеке пока нет книг. 
                        <a href="/books.php" class="alert-link">Найти книги</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Подтверждение удаления
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                if (!confirm('Вы уверены, что хотите удалить эту книгу из библиотеки?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>