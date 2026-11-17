<?php
require 'auth.php';
require 'db.php';

$filter = $_GET['filter'] ?? 'all';

$sql = "
    SELECT br.*, s.reg_number, s.full_name, s.class_name, b.title, b.isbn 
    FROM borrowings br
    JOIN students s ON br.student_id = s.id
    JOIN books b ON br.book_id = b.id
";

if ($filter === 'borrowed') {
    $sql .= " WHERE br.status = 'Borrowed'";
} elseif ($filter === 'overdue') {
    $sql .= " WHERE br.status = 'Borrowed' AND br.due_date < CURDATE()";
} elseif ($filter === 'returned') {
    $sql .= " WHERE br.status = 'Returned'";
}

$sql .= " ORDER BY br.borrow_date DESC";
$records = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Library Reports - KABUSHINGE TSS</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .filter-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        .filter-links a {
            margin-right: 8px;
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="header">
        <div>
            <h1>KABUSHINGE TSS - Library Circulation Report</h1>
            <small style="color: #64748b;">Generated on: <?= date('Y-m-d H:i') ?></small>
        </div>
        <button onclick="window.print()" class="btn btn-primary no-print">Print Report</button>
    </div>

    <div class="card">
        <div class="filter-bar no-print">
            <div class="filter-links">
                <a href="reports.php?filter=all" class="btn btn-sm <?= $filter === 'all' ? 'btn-primary' : 'btn-warning' ?>">All Records</a>
                <a href="reports.php?filter=borrowed" class="btn btn-sm <?= $filter === 'borrowed' ? 'btn-primary' : 'btn-warning' ?>">Currently Borrowed</a>
                <a href="reports.php?filter=overdue" class="btn btn-sm <?= $filter === 'overdue' ? 'btn-primary' : 'btn-danger' ?>">Overdue Books</a>
                <a href="reports.php?filter=returned" class="btn btn-sm <?= $filter === 'returned' ? 'btn-primary' : 'btn-success' ?>">Returned</a>
            </div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Reg No.</th>
                        <th>Student Name</th>
                        <th>Class</th>
                        <th>Book Title</th>
                        <th>Borrow Date</th>
                        <th>Due Date</th>
                        <th>Return Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($records)): ?>
                        <tr><td colspan="8">No records found for this report.</td></tr>
                    <?php else: ?>
                        <?php foreach ($records as $r): 
                            $isOverdue = ($r['status'] === 'Borrowed' && $r['due_date'] < date('Y-m-d'));
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($r['reg_number']) ?></td>
                            <td><?= htmlspecialchars($r['full_name']) ?></td>
                            <td><?= htmlspecialchars($r['class_name']) ?></td>
                            <td><?= htmlspecialchars($r['title']) ?></td>
                            <td><?= $r['borrow_date'] ?></td>
                            <td><?= $r['due_date'] ?></td>
                            <td><?= $r['return_date'] ?? '-' ?></td>
                            <td>
                                <?php if ($isOverdue): ?>
                                    <span class="badge badge-overdue">Overdue</span>
                                <?php elseif ($r['status'] === 'Borrowed'): ?>
                                    <span class="badge badge-borrowed">Borrowed</span>
                                <?php else: ?>
                                    <span class="badge badge-returned">Returned</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
