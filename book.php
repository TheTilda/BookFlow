<?php
session_start();
require 'db.php';

// Проверяем наличие ID книги
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("HTTP/1.0 404 Not Found");
    exit("Книга не найдена");
}
$page = 'books';
$book_id = (int)$_GET['id'];

// Получаем основную информацию о книге
$stmt = $pdo->prepare("
    SELECT b.*, 
           GROUP_CONCAT(DISTINCT a.name SEPARATOR ', ') AS authors,
           GROUP_CONCAT(DISTINCT c.name SEPARATOR ', ') AS categories,
           AVG(r.rating) AS avg_rating
    FROM books b
    LEFT JOIN book_authors ba ON b.id = ba.book_id
    LEFT JOIN authors a ON ba.author_id = a.id
    LEFT JOIN book_categories bc ON b.id = bc.book_id
    LEFT JOIN categories c ON bc.category_id = c.id
    LEFT JOIN reviews r ON b.id = r.book_id
    WHERE b.id = ?
    GROUP BY b.id
");
$stmt->execute([$book_id]);
$book = $stmt->fetch();

if (!$book) {
    header("HTTP/1.0 404 Not Found");
    exit("Книга не найдена");
}

// Получаем отзывы
$reviews_stmt = $pdo->prepare("
    SELECT r.*, u.username 
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.book_id = ?
    ORDER BY r.created_at DESC
");
$reviews_stmt->execute([$book_id]);
$reviews = $reviews_stmt->fetchAll();

// Обработка добавления отзыва
$errors = [];
if (isset($_POST['add_review']) && isset($_SESSION['user'])) {
    $rating = (int)$_POST['rating'];
    $review_text = trim($_POST['review_text']);
    
    // Валидация
    if ($rating < 1 || $rating > 5) {
        $errors[] = 'Некорректная оценка';
    }
    
    if (empty($review_text) || mb_strlen($review_text) < 10) {
        $errors[] = 'Отзыв должен содержать минимум 10 символов';
    }
    
    // Проверка существования отзыва
    $check_stmt = $pdo->prepare("SELECT id FROM reviews WHERE user_id = ? AND book_id = ?");
    $check_stmt->execute([$_SESSION['user']['id'], $book_id]);
    
    if ($check_stmt->fetch()) {
        $errors[] = 'Вы уже оставляли отзыв на эту книгу';
    }
    
    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO reviews 
            (user_id, book_id, rating, review_text, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $_SESSION['user']['id'],
            $book_id,
            $rating,
            $review_text
        ]);
        header("Location: book.php?id=$book_id");
        exit;
    }
}
?>



<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($book['title']) ?> | BookFlow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2A4B7C;
            --secondary: #5BA4E6;
            --accent: #FF6B6B;
            --text: #2D3436;
            --bg: #F8F9FA;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
        }

        .book-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            padding: 5rem 0;
            color: white;
            margin-bottom: 3rem;
        }

        .book-cover {
            max-height: 300px;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            transition: transform 0.3s ease;
            background: white;
            padding: 0.1rem;
        }

        .book-cover:hover {
            transform: translateY(-5px);
        }

        .meta-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            backdrop-filter: blur(10px);
            border: none;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .review-card {
            background: white;
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s ease;
            position: relative;
        }

        .review-card:hover {
            transform: translateY(-3px);
        }

        .rating-stars {
            color: var(--accent);
            font-size: 1.2rem;
        }

        .btn-custom {
            background: var(--primary);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(42, 75, 124, 0.2);
        }

        @media (max-width: 768px) {
            .book-header {
                padding: 2rem 0;
            }
            
            .book-cover {
                max-height: 400px;
                margin-bottom: 2rem;
            }
            
            .meta-card {
                margin-top: 0 !important;
            }
        }

        .review-author::before {
            content: "👤";
            margin-right: 8px;
        }

        .read-btn {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 1000;
            box-shadow: 0 8px 24px rgba(42, 75, 124, 0.3);
        }

        .collapse-review {
            max-height: 120px;
            overflow: hidden;
            position: relative;
        }

        .collapse-review::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 40px;
            background: linear-gradient(to bottom, transparent, white 80%));
        }

        .expanded::after {
            display: none;
        }
    </style>
</head>
<body>
<?php if ($_SESSION['user']): ?>
    <?php include 'navbar.php'; // Подключение навигационной панели ?>
<?php else: ?>
    <!-- Навигация -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#" style="color: var(--primary-color);">BookFlow</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="#features">Преимущества</a></li>
                    <li class="nav-item"><a class="nav-link" href="#books">Книги</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contacts">Контакты</a></li>
                </ul>
                <div class="ms-3">
                    <a href="/login.php" class="btn btn-outline-primary">Войти</a>
                    <a href="/register.php" class="btn btn-primary">Регистрация</a>
                </div>
            </div>
        </div>
    </nav>
<?php endif; ?>

<header class="book-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-4 text-center">
                <img src="/books/<?= $book['id']?>/<?= htmlspecialchars($book['cover_image']) ?>" 
                     class="book-cover img-fluid" 
                     alt="Обложка <?= htmlspecialchars($book['title']) ?>">
            </div>
            <div class="col-lg-8 mt-4 mt-lg-0">
                <h1 class="display-4 fw-bold mb-3"><?= htmlspecialchars($book['title']) ?></h1>
                <div class="d-flex gap-3 align-items-center mb-4">
                    <?php if ($book['avg_rating']): ?>
                        <div class="rating-stars">
                            <?= str_repeat('<i class="fas fa-star"></i>', floor($book['avg_rating'])) ?>
                            <?= ($book['avg_rating'] - floor($book['avg_rating']) >= 0.5) ? '<i class="fas fa-star-half-alt"></i>' : '' ?>
                            <span class="ms-2"><?= number_format($book['avg_rating'], 1) ?></span>
                        </div>
                    <?php endif; ?>
                    <a href="/read.php?id=<?= htmlspecialchars($book['id']) ?>" class="btn btn-custom">
                        <i class="fas fa-book-open me-2"></i>Начать чтение
                    </a>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <?php 
                    // Проверяем существование категорий и преобразуем в массив
                    $categories = isset($book['categories']) && !empty($book['categories']) 
                        ? explode(', ', $book['categories']) 
                        : [];
                    
                    // Выводим бейджи только если есть категории
                    if (!empty($categories)) : 
                        foreach($categories as $category): 
                    ?>
                            <span class="badge bg-light text-dark px-3 py-2 rounded-pill">
                                <?= htmlspecialchars(trim($category)) ?>
                            </span>
                    <?php 
                        endforeach;
                    else: 
                    ?>
                        <span class="text-muted small">Категории не указаны</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</header>

<main class="container">
    <div class="row g-4">
        <!-- Основная информация -->
        <div class="col-lg-8">
            <div class="card meta-card mb-4">
                <div class="card-body">
                    <h3 class="h5 fw-bold mb-3">О книге</h3>
                    <p class="lead"><?= nl2br(htmlspecialchars($book['description'])) ?></p>
                    
                    <div class="row g-3 mt-4">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-user-edit text-primary fs-4"></i>
                                <div>
                                    <small class="text-muted">Автор</small>
                                    <div class="fw-bold"><?= $book['authors'] ?? 'Не указан' ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-calendar-alt text-primary fs-4"></i>
                                <div>
                                    <small class="text-muted">Год издания</small>
                                    <div class="fw-bold"><?= $book['publication_year'] ?? 'Не указан' ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Отзывы -->
            <section class="mb-5">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="h4 fw-bold">Отзывы (<?= count($reviews) ?>)</h3>
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#reviewModal">
                        Написать отзыв
                    </button>
                </div>

                <div class="row g-3">
                    <?php foreach ($reviews as $review): ?>
                    <div class="col-12">
                        <div class="review-card p-4">
                            <div class="d-flex justify-content-between mb-3">
                                <div>
                                    <div class="review-author fw-bold"><?= htmlspecialchars($review['username']) ?></div>
                                    <div class="rating-stars">
                                        <?= str_repeat('<i class="fas fa-star"></i>', $review['rating']) ?>
                                    </div>
                                </div>
                                <small class="text-muted">
                                    <?= date('d M Y', strtotime($review['created_at'])) ?>
                                </small>
                            </div>
                            <div class="collapse-review" id="review-<?= $review['id'] ?>">
                                <?= nl2br(htmlspecialchars($review['review_text'])) ?>
                            </div>
                            <button class="btn btn-link p-0 mt-2" 
                                    data-bs-toggle="collapse" 
                                    data-bs-target="#review-<?= $review['id'] ?>" 
                                    aria-expanded="false"
                                    aria-controls="review-<?= $review['id'] ?>">
                                Показать полностью
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>

        <!-- Дополнительная информация -->
        <div class="col-lg-4">
            <div class="card meta-card mb-4">
                <div class="card-body">
                    <h3 class="h5 fw-bold mb-3">Детали</h3>
                    <ul class="list-unstyled">
                        <li class="mb-3">
                            <i class="fas fa-file-alt me-2 text-primary"></i>
                            <?= $book['pages'] ?? '0' ?> страниц
                        </li>
                        <li class="mb-3">
                            <i class="fas fa-language me-2 text-primary"></i>
                            Русский язык
                        </li>
                        <li class="mb-3">
                            <i class="fas fa-database me-2 text-primary"></i>
                            Формат: fb2
                        </li>
                        <li>
                            <i class="fas fa-fingerprint me-2 text-primary"></i>
                            ISBN: <?= $book['isbn'] ?? 'Не указан' ?>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="card meta-card">
                <div class="card-body">
                    <h3 class="h5 fw-bold mb-3">Статистика</h3>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Прочитали:</span>
                        <strong>1,234</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>В библиотеках:</span>
                        <strong>5,678</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Обсуждения:</span>
                        <strong>890</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Модальное окно отзыва -->
<div class="modal fade" id="reviewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Оставить отзыв</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Оценка</label>
                        <!-- В модальном окне отзыва -->
                        <div class="rating-stars fs-3">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>" 
                                class="visually-hidden" required>
                            <label for="star<?= $i ?>" class="star-label">
                                <i class="far fa-star"></i>
                            </label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="mb-3">
                        <textarea class="form-control" name="review_text" rows="5" 
                                  placeholder="Ваш отзыв..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" name="add_review" class="btn btn-primary">Отправить</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    
    //Анимация звезд рейтинга
    document.querySelectorAll('.star-label').forEach((star) => {
        star.addEventListener('click', function() {
            const stars = Array.from(this.parentElement.querySelectorAll('.star-label'));
            const selectedValue = parseInt(this.htmlFor.replace('star', ''));
            
            stars.forEach((s, index) => {
                const starValue = index + 1;
                s.querySelector('i').className = starValue <= selectedValue 
                    ? 'fas fa-star' 
                    : 'far fa-star';
            });
            
            document.getElementById(this.htmlFor).checked = true;
        });
    });

    // Обработка сворачивания отзывов
    document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(btn => {
        btn.addEventListener('click', function() {
            const target = document.querySelector(this.dataset.bsTarget);
            target.classList.toggle('expanded');
            this.textContent = target.classList.contains('show') ? 
                'Свернуть' : 'Показать полностью';
        });
    });
</script>
</body>
</html>