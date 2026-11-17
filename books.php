<?php
require 'auth.php';
require 'db.php';
$msg = '';
$editBook = null;

if (isset($_GET['delete'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        $msg = "<div class='alert alert-success'>Book deleted successfully.</div>";
    } catch (PDOException $e) {
        $msg = "<div class='alert alert-error'>Ntibishoboye gusiba iki gitabo: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editBook = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isbn = trim($_POST['isbn'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $copies = (int)($_POST['total_copies'] ?? 1);

    if (empty($isbn) || empty($title) || empty($author) || empty($category) || $copies < 1) {
        $msg = "<div class='alert alert-error'>Uzuza ibisabwa byose neza kandi umubare w'ibitabo ube nibura 1!</div>";
    } else {
        try {
            if (!empty($_POST['id'])) {
                $stmt = $pdo->prepare("UPDATE books SET isbn=?, title=?, author=?, category=?, total_copies=?, available_copies=? WHERE id=?");
                $stmt->execute([$isbn, $title, $author, $category, $copies, $copies, $_POST['id']]);
                $msg = "<div class='alert alert-success'>Book updated successfully!</div>";
            } else {
                $stmt = $pdo->prepare("INSERT INTO books (isbn, title, author, category, total_copies, available_copies) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$isbn, $title, $author, $category, $copies, $copies]);
                $msg = "<div class='alert alert-success'>New book added to library!</div>";
            }
        } catch (PDOException $e) {
            $msg = "<div class='alert alert-error'>Ikibazo: ISBN wanditse isanzwe ibaho muri sisitemu!</div>";
        }
    }
}

$books = $pdo->query("SELECT * FROM books ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Books - KABUSHINGE TSS</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="header">
        <h1>Manage Library Books</h1>
        <div class="header-user">
            <img src="<?= htmlspecialchars($_SESSION['admin_photo']) ?>" alt="Admin">
            <span><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Librarian') ?></span>
        </div>
    </div>

    <?= $msg ?>

    <div class="card">
        <h2><?= $editBook ? 'Edit Book Details' : 'Add New Book' ?></h2>
        <form method="POST">
            <input type="hidden" name="id" value="<?= $editBook['id'] ?? '' ?>">
            <div class="form-grid">
                <div class="form-group">
                    <label>ISBN / Book Code</label>
                    <input type="text" name="isbn" required value="<?= htmlspecialchars($editBook['isbn'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Book Title</label>
                    <input type="text" name="title" required value="<?= htmlspecialchars($editBook['title'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Author</label>
                    <input type="text" name="author" required value="<?= htmlspecialchars($editBook['author'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Subject / Category</label>
                    <input type="text" name="category" placeholder="e.g. Mathematics, Literature" required value="<?= htmlspecialchars($editBook['category'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Number of Copies</label>
                    <input type="number" name="total_copies" min="1" required value="<?= $editBook['total_copies'] ?? 1 ?>">
                </div>
            </div>
            <div style="margin-top: 15px;">
                <button type="submit" class="btn btn-primary"><?= $editBook ? 'Update Book' : 'Save Book' ?></button>
                <?php if ($editBook): ?>
                    <a href="books.php" class="btn btn-warning">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="card">
        <h2>Book Catalog</h2>
        <div class="search-box">
            <input type="text" id="bookSearch" onkeyup="filterTable('bookSearch', 'bookTable')" placeholder="Search by title, author, or ISBN...">
        </div>
        <div class="table-container">
            <table id="bookTable">
                <thead>
                    <tr>
                        <th>ISBN</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Category</th>
                        <th>Available / Total</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($books as $b): ?>
                    <tr>
                        <td><?= htmlspecialchars($b['isbn']) ?></td>
                        <td><?= htmlspecialchars($b['title']) ?></td>
                        <td><?= htmlspecialchars($b['author']) ?></td>
                        <td><?= htmlspecialchars($b['category']) ?></td>
                        <td><strong><?= $b['available_copies'] ?></strong> / <?= $b['total_copies'] ?></td>
                        <td>
                            <a href="books.php?edit=<?= $b['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                            <a href="books.php?delete=<?= $b['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this book?')">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function filterTable(inputId, tableId) {
    const filter = document.getElementById(inputId).value.toLowerCase();
    const rows = document.getElementById(tableId).getElementsByTagName('tr');
    for (let i = 1; i < rows.length; i++) {
        rows[i].style.display = rows[i].innerText.toLowerCase().includes(filter) ? '' : 'none';
    }
}
</script>
</body>
</html>
