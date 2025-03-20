<?php
session_start();
require_once __DIR__ . '/db.php';

// Проверка авторизации
if (isset($_SESSION['user'])) {
    header('Location: books.php');
    exit;
}

// Получаем список книг для отображения на главной странице
$stmt = $pdo->query("SELECT * FROM books LIMIT 4"); // Ограничиваем количество книг для главной страницы
$books = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookFlow - Читайте с удовольствием</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/styles/index.css">
    <style>
        /* Стили для улучшения внешнего вида */
        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('assets/images/hero-bg.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 150px 0;
        }
        .feature-card {
            border: none;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }
        .feature-card:hover {
            transform: translateY(-10px);
        }
        .book-card {
            border: none;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }
        .book-card:hover {
            transform: translateY(-10px);
        }
        .book-card img {
            height: 300px;
            object-fit: cover;
        }
        footer {
            background-color: #343a40;
            color: white;
        }
    </style>
</head>
<body>
    <!-- Навигация -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top bg-white">
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

    <!-- Герой секция -->
    <section class="hero-section">
        <div class="container text-center">
            <h1 class="display-4 fw-bold mb-4">Откройте мир книг с BookFlow</h1>
            <p class="lead mb-4">Читайте лучшие произведения в любом месте и в любое время</p>
            <a href="/register.php" class="btn btn-light btn-lg px-5">Начать чтение</a>
        </div>
    </section>

    <!-- Преимущества -->
    <section id="features" class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2>Почему выбирают нас</h2>
                <p class="text-muted">Лучшая платформа для любителей чтения</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card feature-card h-100 p-4">
                        <div class="card-body text-center">
                            <i class="fas fa-book-open fa-3x text-primary mb-3"></i>
                            <h5>100 000+ книг</h5>
                            <p class="text-muted">Классика и современные бестселлеры</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card h-100 p-4">
                        <div class="card-body text-center">
                            <i class="fas fa-mobile-alt fa-3x text-primary mb-3"></i>
                            <h5>Чтение на любом устройстве</h5>
                            <p class="text-muted">Доступно на смартфонах, планшетах и компьютерах</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card h-100 p-4">
                        <div class="card-body text-center">
                            <i class="fas fa-clock fa-3x text-primary mb-3"></i>
                            <h5>Чтение офлайн</h5>
                            <p class="text-muted">Скачивайте книги и читайте без интернета</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Популярные книги -->
    <section id="books" class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2>Популярные книги</h2>
                <p class="text-muted">Самые читаемые произведения</p>
            </div>
            <div class="row g-4">
                <?php foreach($books as $value): ?>
                    <div class="col-md-3">
                        <div class="book-card card h-100">
                            <img src="/books/<?= $value['id']?>/<?= $value['cover_image']?>" class="card-img-top" alt="Обложка книги">
                            <div class="card-body">
                                <h5 class="card-title"><?= $value['title'] ?></h5>
                                <p class="card-text text-muted">Автор</p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Футер -->
    <footer id="contacts" class="py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <h4>BookFlow</h4>
                    <p>© 2023 Все права защищены</p>
                </div>
                <div class="col-md-4">
                    <h5>Контакты</h5>
                    <ul class="list-unstyled">
                        <li>Email: info@bookflow.com</li>
                        <li>Телефон: +7 (999) 123-45-67</li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Социальные сети</h5>
                    <div class="d-flex gap-3">
                        <a href="#" class="text-white"><i class="fab fa-vk fa-2x"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-telegram fa-2x"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-youtube fa-2x"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Скрипты -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Плавный скролл
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>