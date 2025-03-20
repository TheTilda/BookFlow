<?php if ($_SESSION['user']): ?>
    <nav class="navbar navbar-expand-lg navbar-light fixed-top" style="background-color: white;">
    <div class="container">
        <a class="navbar-brand fw-bold" href="/" style="color: var(--primary-color);">BookFlow</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                
                <?php if ($page == 'books'):?>
                    <li class="nav-item"><a style="font-weight: bold;" class="nav-link" href="/books.php">Каталог</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="/books.php">Каталог</a></li>
                <?php endif; ?>

                <?php if ($page == 'my-books'):?>
                    <li class="nav-item"><a style="font-weight: bold;" class="nav-link" href="/my-books.php">Мои книги</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="/my-books.php">Мои книги</a></li>
                <?php endif; ?>

                <?php if ($page == 'profile'):?>
                    <li class="nav-item"><a style="font-weight: bold;" class="nav-link" href="/profile.php">Профиль</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="/profile.php">Профиль</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    </nav>
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