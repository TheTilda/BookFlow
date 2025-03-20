<?php
session_start();
require_once __DIR__ . '/db.php';

// Проверка авторизации
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// Получение параметров фильтрации
$category_id = $_GET['category'] ?? null;
$search = $_GET['search'] ?? '';

// Основной запрос для книг
$query = "SELECT 
    b.id,
    b.title,
    b.description,
    b.cover_image,
    b.pages,
    b.publication_year,
    GROUP_CONCAT(DISTINCT a.name SEPARATOR ', ') AS authors,
    GROUP_CONCAT(DISTINCT c.name SEPARATOR ', ') AS categories,
    AVG(r.rating) AS avg_rating
FROM books b
LEFT JOIN book_authors ba ON b.id = ba.book_id
LEFT JOIN authors a ON ba.author_id = a.id
LEFT JOIN book_categories bc ON b.id = bc.book_id
LEFT JOIN categories c ON bc.category_id = c.id
LEFT JOIN reviews r ON b.id = r.book_id";

$where = [];
$params = [];

// Фильтр по категории
if ($category_id && is_numeric($category_id)) {
    $where[] = "bc.category_id = ?";
    $params[] = $category_id;
}

// Фильтр по поиску
if (!empty($search)) {
    $where[] = "(b.title LIKE ? OR b.description LIKE ? OR a.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Сборка условия WHERE
if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
}

$query .= " GROUP BY b.id ORDER BY b.created_at DESC LIMIT 12";

// Выполнение запроса
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$books = $stmt->fetchAll();

// Получение категорий для фильтра
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Временные баннеры (заглушки)
$banners = [
    [
        'title' => 'Премиум доступ',
        'description' => 'Получите расширенные возможности за 299₽/мес',
        'image_url' => 'https://via.placeholder.com/800x200/2A4B7C/FFFFFF?text=Premium+Access',
        'link_url' => '#',
        'button_text' => 'Подробнее'
    ],
    [
        'title' => 'Новинки недели',
        'description' => 'Самые свежие книги уже доступны!',
        'image_url' => 'https://via.placeholder.com/800x200/5BA4E6/FFFFFF?text=New+Books',
        'link_url' => '#',
        'button_text' => 'Смотреть'
    ]
];

$page = 'books';
?>


<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Моя библиотека | BookFlow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .library-header {
            background: linear-gradient(135deg, #2A4B7C, #5BA4E6);
            padding: 4rem 0;
            padding-top: 7rem;
            color: white;
        }

        .book-card {
            transition: transform 0.3s, box-shadow 0.3s;
            border: none;
            border-radius: 12px;
            overflow: hidden;
        }

        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .banner-card {
            border-radius: 12px;
            overflow: hidden;
            position: relative;
            min-height: 200px;
            background-size: cover;
            background-position: center;
        }

        .category-filter {
            position: sticky;
            top: 1rem;
        }
        .toast {
            min-width: 250px;
            margin-bottom: 1rem;
            opacity: 1;
        }

        .toast-success {
            background-color: #28a745 !important;
        }

        .toast-warning {
            background-color: #ffc107 !important;
        }

        .toast-danger {
            background-color: #dc3545 !important;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <header class="library-header">
        <div class="container">
            <h1 class="display-4 fw-bold mb-4">Добро пожаловать, <?= htmlspecialchars($_SESSION['user']['username']) ?>!</h1>
            
            <!-- Поиск и фильтры -->
            <form class="row g-3">
                <div class="col-md-8">
                    <input type="text" 
                           name="search" 
                           class="form-control form-control-lg" 
                           placeholder="Поиск по книгам, авторам..."
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-3">
                    <select name="category" class="form-select form-select-lg">
                        <option value="">Все категории</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" 
                                <?= $cat['id'] == $category_id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-light btn-lg w-100">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </header>

    <main class="py-5">
        <div class="container">
            <div class="row g-4">
                <!-- Боковая панель с категориями -->
                <div class="col-lg-3">
                    <div class="card category-filter">
                        <div class="card-body">
                            <h5 class="mb-3">Категории</h5>
                            <div class="list-group">
                                <?php foreach($categories as $cat): ?>
                                    <a href="?category=<?= $cat['id'] ?>" 
                                       class="list-group-item list-group-item-action <?= $cat['id'] == $category_id ? 'active' : '' ?>">
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Основной контент -->
                <div class="col-lg-9">
                    <!-- Рекламные баннеры -->
                        <?php if(!empty($banners)): ?>
                                <div class="row mb-4 g-3">
                                    <?php foreach($banners as $banner): ?>
                                        <div class="col-md-6">
                                            <div class="banner-card" 
                                                style="background-image: url('<?= htmlspecialchars($banner['image_url']) ?>')">
                                                <div class="p-3 text-white" style="background: rgba(0,0,0,0.5)">
                                                    <h4><?= htmlspecialchars($banner['title']) ?></h4>
                                                    <p><?= htmlspecialchars($banner['description']) ?></p>
                                                    <a href="<?= htmlspecialchars($banner['link_url']) ?>" 
                                                    class="btn btn-primary">
                                                        <?= htmlspecialchars($banner['button_text']) ?>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                        <?php endif; ?>

                    <!-- Список книг -->
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                        <?php foreach($books as $book): ?>
                            <div class="col">
                                <div class="card book-card h-100 position-relative" 
                                    onclick="window.location.href='/book.php?id=<?= $book['id'] ?>'"
                                    style="cursor: pointer;">
                                    
                                    <!-- Верхняя часть карточки -->
                                    <img src="/books/<?= $book['id']?>/<?= htmlspecialchars($book['cover_image']) ?>" 
                                        class="card-img-top" 
                                        alt="<?= htmlspecialchars($book['title']) ?>"
                                        style="height: 300px; object-fit: cover;">
                                    
                                    <!-- Тело карточки -->
                                    <div class="card-body">
                                        <h5 class="card-title"><?= htmlspecialchars($book['title']) ?></h5>
                                        <p class="text-muted small mb-1"><?= $book['authors'] ?></p>
                                        
                                        <!-- Блок с рейтингом и кнопкой -->
                                        <div class="d-flex justify-content-between align-items-center mb-3"
                                            style="pointer-events: none;"> <!-- Отключаем события для внутренних элементов -->
                                            
                                            <span class="badge bg-primary">
                                                <?php if (!empty($book['avg_rating']) && $book['avg_rating'] > 0): ?>
                                                    <?= round($book['avg_rating'], 1) ?> ★
                                                <?php else: ?>
                                                    Нет рейтинга
                                                <?php endif; ?>
                                            </span>
                                            
                                            <span class="text-muted small">
                                                <?= $book['pages'] ?> стр.
                                            </span>
                                        </div>

                                        <!-- Кнопки действий -->
                                        <div class="d-grid gap-2" 
                                            style="pointer-events: auto;"> <!-- Включаем события для кнопок -->
                                            
                                            <button class="btn btn-outline-primary" 
                                                    onclick="event.stopPropagation(); addToLibrary(<?= $book['id'] ?>)">
                                                <i class="fas fa-plus me-2"></i>
                                                <span class="button-text">В библиотеку</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
    async function addToLibrary(bookId) {
        try {
            const response = await fetch(`/src/actions/add_to_library.php?id=${bookId}`);
            const data = await response.json();

            // Проверяем флаг success в данных
            if (data.success) {
                showToast('success', data.message);
            } else {
                showToast('danger', data.error || 'Произошла ошибка');
            }

        } catch (error) {
            console.error('Ошибка:', error);
            showToast('danger', 'Ошибка сети или сервера');
        }
    }
    // Функция для показа уведомлений
    function showToast(type, message) {
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        const toastContainer = document.getElementById('toast-container') || createToastContainer();
        toastContainer.appendChild(toast);
        
        new bootstrap.Toast(toast, { autohide: true, delay: 3000 }).show();
    }

    function createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        container.style.zIndex = 9999;
        document.body.appendChild(container);
        return container;
    }
    </script>                                                   
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>