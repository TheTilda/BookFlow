<?php
session_start();
require_once __DIR__ . '/db.php';

// Проверка авторизации и роли
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$errors = [];
$success = '';

// Получаем список авторов и категорий для выпадающего списка
$authors = $pdo->query("SELECT * FROM authors")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();

// Обработка загрузки новой книги
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_book'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $publication_year = (int)$_POST['publication_year'];
    $pages = (int)$_POST['pages'];
    $author_id = (int)$_POST['author_id']; // ID выбранного автора
    $new_author = trim($_POST['new_author']); // Имя нового автора
    $selected_categories = $_POST['categories'] ?? []; // Выбранные категории

    // Валидация
    if (empty($title) || empty($description) || empty($publication_year) || empty($pages)) {
        $errors[] = 'Все поля обязательны для заполнения.';
    }

    if ($_FILES['cover_image']['error'] !== UPLOAD_ERR_OK || $_FILES['book_file']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Ошибка при загрузке файлов.';
    }

    if (empty($errors)) {
        // Добавляем книгу в базу данных
        $stmt = $pdo->prepare("
            INSERT INTO books (title, description, publication_year, pages, cover_image, file_path)
            VALUES (:title, :description, :publication_year, :pages, :cover_image, :file_path)
        ");

        $stmt->execute([
            ':title' => $title,
            ':description' => $description,
            ':publication_year' => $publication_year,
            ':pages' => $pages,
            ':cover_image' => '', // Временное значение
            ':file_path' => ''   // Временное значение
        ]);

        $book_id = $pdo->lastInsertId(); // Получаем ID новой книги

        // Создаем папку для книги
        $book_dir = __DIR__ . "/books/{$book_id}";
        if (!mkdir($book_dir, 0777, true)) {
            $errors[] = 'Ошибка при создании папки для книги.';
        } else {
            // Загружаем обложку
            $cover_image = $_FILES['cover_image'];
            $cover_image_name = 'cover.' . pathinfo($cover_image['name'], PATHINFO_EXTENSION);
            $cover_image_path = "{$book_dir}/{$cover_image_name}";

            if (!move_uploaded_file($cover_image['tmp_name'], $cover_image_path)) {
                $errors[] = 'Ошибка при загрузке обложки.';
            }

            // Загружаем файл книги
            $book_file = $_FILES['book_file'];
            $book_file_name = 'book.fb2';
            $book_file_path = "{$book_dir}/{$book_file_name}";

            if (!move_uploaded_file($book_file['tmp_name'], $book_file_path)) {
                $errors[] = 'Ошибка при загрузке файла книги.';
            }

            // Обновляем запись в базе данных
            $stmt = $pdo->prepare("
                UPDATE books 
                SET cover_image = :cover_image, file_path = :file_path 
                WHERE id = :id
            ");

            $stmt->execute([
                ':cover_image' => $cover_image_name,
                ':file_path' => $book_file_name,
                ':id' => $book_id
            ]);

            // Обработка авторов
            if ($author_id > 0) {
                // Используем существующего автора
                $stmt = $pdo->prepare("INSERT INTO book_authors (book_id, author_id) VALUES (?, ?)");
                $stmt->execute([$book_id, $author_id]);
            } elseif (!empty($new_author)) {
                // Добавляем нового автора
                $stmt = $pdo->prepare("INSERT INTO authors (name) VALUES (?)");
                $stmt->execute([$new_author]);
                $author_id = $pdo->lastInsertId();

                // Связываем книгу с новым автором
                $stmt = $pdo->prepare("INSERT INTO book_authors (book_id, author_id) VALUES (?, ?)");
                $stmt->execute([$book_id, $author_id]);
            }

            // Обработка категорий
            if (!empty($selected_categories)) {
                foreach ($selected_categories as $category_id) {
                    $stmt = $pdo->prepare("INSERT INTO book_categories (book_id, category_id) VALUES (?, ?)");
                    $stmt->execute([$book_id, $category_id]);
                }
            }

            $success = 'Книга успешно добавлена!';
        }
    }
}

// Обработка удаления книги
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_book'])) {
    $book_id = (int)$_POST['book_id'];

    // Удаляем папку с книгой
    $book_dir = __DIR__ . "/books/{$book_id}";
    if (is_dir($book_dir)) {
        array_map('unlink', glob("{$book_dir}/*")); // Удаляем все файлы в папке
        rmdir($book_dir); // Удаляем саму папку
    }

    // Удаляем запись из базы данных
    $stmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
    $stmt->execute([$book_id]);

    $success = 'Книга успешно удалена!';
}

// Обработка удаления пользователя
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id = (int)$_POST['user_id'];

    // Удаляем пользователя из базы данных
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id]);

    $success = 'Пользователь успешно удален!';
}

// Пагинация для книг
$books_per_page = 10;
$current_book_page = isset($_GET['book_page']) ? (int)$_GET['book_page'] : 1;
$book_offset = ($current_book_page - 1) * $books_per_page;

$books_total = $pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();
$books = $pdo->query("
    SELECT b.id, b.title, GROUP_CONCAT(a.name SEPARATOR ', ') AS authors
    FROM books b
    LEFT JOIN book_authors ba ON b.id = ba.book_id
    LEFT JOIN authors a ON ba.author_id = a.id
    GROUP BY b.id
    LIMIT $books_per_page OFFSET $book_offset
")->fetchAll();

// Пагинация для пользователей
$users_per_page = 10;
$current_user_page = isset($_GET['user_page']) ? (int)$_GET['user_page'] : 1;
$user_offset = ($current_user_page - 1) * $users_per_page;

$users_total = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$users = $pdo->query("
    SELECT * FROM users
    LIMIT $users_per_page OFFSET $user_offset
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель | BookFlow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
            background: #fff;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }
        .form-section {
            margin-bottom: 2rem;
        }
        .table-section {
            margin-top: 2rem;
        }
        .pagination {
            justify-content: center;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <h1 class="text-center mb-4">Админ-панель</h1>

        <!-- Уведомления -->
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

        <!-- Форма добавления книги -->
        <div class="form-section">
            <h2>Добавить новую книгу</h2>
            <form method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Название книги</label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Описание</label>
                    <textarea name="description" class="form-control" rows="3" required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Год публикации</label>
                    <input type="number" name="publication_year" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Количество страниц</label>
                    <input type="number" name="pages" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Автор</label>
                    <select name="author_id" class="form-control">
                        <option value="0">Выберите автора</option>
                        <?php foreach ($authors as $author): ?>
                            <option value="<?= $author['id'] ?>"><?= htmlspecialchars($author['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Или введите нового автора:</small>
                    <input type="text" name="new_author" class="form-control mt-2" placeholder="Новый автор">
                </div>
                <div class="mb-3">
                    <label class="form-label">Категории</label>
                    <select name="categories[]" class="form-control" multiple>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Выберите одну или несколько категорий (удерживайте Ctrl для выбора нескольких).</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Обложка</label>
                    <input type="file" name="cover_image" class="form-control" accept="image/*" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Файл книги (FB2)</label>
                    <input type="file" name="book_file" class="form-control" accept=".fb2" required>
                </div>
                <button type="submit" name="add_book" class="btn btn-primary">Добавить книгу</button>
            </form>
        </div>
        <!-- Список книг -->
        <div class="table-section">
            <h2>Список книг</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Название</th>
                        <th>Автор</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($books as $book): ?>
                        <tr>
                            <td><?= $book['id'] ?></td>
                            <td><?= htmlspecialchars($book['title']) ?></td>
                            <td><?= htmlspecialchars($book['authors']) ?></td>
                            <td>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
                                    <button type="submit" name="delete_book" class="btn btn-danger btn-sm">Удалить</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Пагинация для книг -->
            <nav>
                <ul class="pagination">
                    <?php for ($i = 1; $i <= ceil($books_total / $books_per_page); $i++): ?>
                        <li class="page-item <?= $i === $current_book_page ? 'active' : '' ?>">
                            <a class="page-link" href="?book_page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>

        <!-- Список пользователей -->
        <div class="table-section">
            <h2>Список пользователей</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Имя пользователя</th>
                        <th>Email</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= $user['id'] ?></td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <button type="submit" name="delete_user" class="btn btn-danger btn-sm">Удалить</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Пагинация для пользователей -->
            <nav>
                <ul class="pagination">
                    <?php for ($i = 1; $i <= ceil($users_total / $users_per_page); $i++): ?>
                        <li class="page-item <?= $i === $current_user_page ? 'active' : '' ?>">
                            <a class="page-link" href="?user_page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>