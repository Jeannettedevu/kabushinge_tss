<?php
require 'auth.php';
require 'db.php';
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['issue_book'])) {
    $student_id = $_POST['student_id'] ?? '';
    $book_id = $_POST['book_id'] ?? '';
    $borrow_date = date('Y-m-d');
    $due_date = $_POST['due_date'] ?? '';

    if (empty($student_id) || empty($book_id) || empty($due_date)) {
        $msg = "<div class='alert alert-error'>Hitamo umunyeshuri, igitabo, n'itariki yo kugarura!</div>";
    } elseif ($due_date < $borrow_date) {
        $msg = "<div class='alert alert-error'>Itariki yo kugarura igitabo ntishobora kuba mbere y'uyu munsi!</div>";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT available_copies FROM books WHERE id = ?");
            $stmt->execute([$book_id]);
            $avail = $stmt->fetchColumn();

            if ($avail > 0) {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("INSERT INTO borrowings (student_id, book_id, borrow_date, due_date) VALUES (?, ?, ?, ?)");
                $stmt->execute([$student_id, $book_id, $borrow_date, $due_date]);

                $stmt = $pdo->prepare("UPDATE books SET available_copies = available_copies - 1 WHERE id = ?");
                $stmt->execute([$book_id]);
                $pdo->commit();

                $msg = "<div class='alert alert-success'>Book issued successfully!</div>";
            } else {
                $msg = "<div class='alert alert-error'>Iki gitabo cyashize mu bubiko (Out of stock).</div>";
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $msg = "<div class='alert alert-error'>Database Error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}

if (isset($_GET['return_id'])) {
    try {
        $borrow_id = $_GET['return_id'];
        $stmt = $pdo->prepare("SELECT book_id, status FROM borrowings WHERE id = ?");
        $stmt->execute([$borrow_id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($record && $record['status'] === 'Borrowed') {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE borrowings SET status = 'Returned', return_date = ? WHERE id = ?");
            $stmt->execute([date('Y-m-d'), $borrow_id]);

            $stmt = $pdo->prepare("UPDATE books SET available_copies = available_copies + 1 WHERE id = ?");
            $stmt->execute([$record['book_id']]);
            $pdo->commit();

            $msg = "<div class='alert alert-success'>Book marked as returned and stock updated!</div>";
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $msg = "<div class='alert alert-error'>Database Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

$students = $pdo->query("SELECT * FROM students ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$availableBooks = $pdo->query("SELECT * FROM books WHERE available_copies > 0 ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);

$borrowings = $pdo->query("
    SELECT br.*, s.reg_number, s.full_name, s.class_name, s.photo AS student_photo, b.title, b.isbn 
    FROM borrowings br
    JOIN students s ON br.student_id = s.id
    JOIN books b ON br.book_id = b.id
    ORDER BY br.status ASC, br.id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Issue & Return Books - KABUSHINGE TSS</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="header">
        <h1>Book Circulation (Borrow & Return)</h1>
        <div class="header-user">
            <img src="<?= htmlspecialchars($_SESSION['admin_photo']) ?>" alt="Admin">
            <span><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Librarian') ?></span>
        </div>
    </div>

    <?= $msg ?>

    <div class="card">
        <h2>Issue Book to Student</h2>
        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label>Select Student</label>
                    <select name="student_id" required>
                        <option value="">-- Choose Student --</option>
                        <?php foreach ($students as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['reg_number'] . ' - ' . $s['full_name'] . ' (' . $s['class_name'] . ')') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Select Available Book</label>
                    <select name="book_id" required>
                        <option value="">-- Choose Book --</option>
                        <?php foreach ($availableBooks as $b): ?>
                            <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['title'] . ' (' . $b['available_copies'] . ' left)') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Return Due Date</label>
                    <input type="date" name="due_date" required value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
                </div>
            </div>
            <div style="margin-top: 15px;">
                <button type="submit" name="issue_book" class="btn btn-primary">Issue Book</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h2>Borrowing & Return History</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Student</th>
                        <th>Book</th>
                        <th>Borrowed On</th>
                        <th>Due Date</th>
                        <th>Returned On</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($borrowings as $row): 
                        $sPhoto = (!empty($row['student_photo']) && file_exists($row['student_photo'])) ? $row['student_photo'] : 'uploads/default_avatar.svg';
                    ?>
                    <tr>
                        <td><img src="<?= htmlspecialchars($sPhoto) ?>" class="table-avatar" alt="Student"></td>
                        <td><?= htmlspecialchars($row['full_name']) ?> (<?= htmlspecialchars($row['class_name']) ?>)</td>
                        <td><?= htmlspecialchars($row['title']) ?></td>
                        <td><?= $row['borrow_date'] ?></td>
                        <td><?= $row['due_date'] ?></td>
                        <td><?= $row['return_date'] ?? '-' ?></td>
                        <td>
                            <span class="badge <?= $row['status'] === 'Borrowed' ? 'badge-borrowed' : 'badge-returned' ?>">
                                <?= $row['status'] ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($row['status'] === 'Borrowed'): ?>
                                <a href="borrow.php?return_id=<?= $row['id'] ?>" class="btn btn-success btn-sm" onclick="return confirm('Confirm book return?')">Mark Returned</a>
                            <?php else: ?>
                                <span style="color: #64748b; font-size: 0.85rem;">Completed</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
