<?php
require 'auth.php';
require 'db.php';

$totalBooks = $pdo->query("SELECT COALESCE(SUM(total_copies), 0) FROM books")->fetchColumn();
$availableBooks = $pdo->query("SELECT COALESCE(SUM(available_copies), 0) FROM books")->fetchColumn();
$totalStudents = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$activeBorrows = $pdo->query("SELECT COUNT(*) FROM borrowings WHERE status = 'Borrowed'")->fetchColumn();

$stmt = $pdo->prepare("SELECT full_name, department, program, reg_number, email, class_name FROM admins WHERE id = ?");
$stmt->execute([$_SESSION['admin_id'] ?? 1]);
$me = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
$myFields = [
    'Full Name'     => $me['full_name'] ?? '',
    'Department'    => $me['department'] ?? '',
    'Program'       => $me['program'] ?? '',
    'Reg Number'    => $me['reg_number'] ?? '',
    'Email Address' => $me['email'] ?? '',
    'Class'         => $me['class_name'] ?? '',
];

$recentActivity = $pdo->query("
    SELECT br.id, s.full_name, s.class_name, s.photo AS student_photo, b.title, br.borrow_date, br.due_date, br.status 
    FROM borrowings br
    JOIN students s ON br.student_id = s.id
    JOIN books b ON br.book_id = b.id
    ORDER BY br.id DESC LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - KABUSHINGE TSS Library</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* My Details shown as cards */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-top: 10px;
        }
        .info-item {
            display: flex;
            flex-direction: column;
            gap: 6px;
            background: #f5f7fb;
            border-left: 5px solid #1e3a8a;
            border-radius: 8px;
            padding: 16px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.15s, box-shadow 0.15s;
        }
        .info-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }
        .info-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b7280;
        }
        .info-value {
            font-size: 17px;
            font-weight: bold;
            color: #111827;
            word-break: break-word;
        }
        .info-value .not-set {
            font-weight: normal;
            color: #9ca3af;
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="header">
        <h1>Library Dashboard</h1>
        <div class="header-user">
            <img src="<?= htmlspecialchars($_SESSION['admin_photo']) ?>" alt="Admin">
            <span><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Librarian') ?></span>
            <a href="profile.php" class="btn btn-primary btn-sm">Change Photo</a>
        </div>
    </div>

    <div class="card">
        <h2>My Details</h2>
        <div class="info-grid">
            <?php foreach ($myFields as $label => $value): ?>
                <div class="info-item">
                    <span class="info-label"><?= htmlspecialchars($label) ?></span>
                    <span class="info-value"><?= trim($value) !== '' ? htmlspecialchars($value) : '<em class="not-set">Not set</em>' ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <div style="margin-top: 15px;">
            <a href="profile.php" class="btn btn-primary btn-sm">Edit My Details</a>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Books (Copies)</h3>
            <div class="number"><?= $totalBooks ?></div>
        </div>
        <div class="stat-card green">
            <h3>Available on Shelf</h3>
            <div class="number"><?= $availableBooks ?></div>
        </div>
        <div class="stat-card">
            <h3>Registered Students</h3>
            <div class="number"><?= $totalStudents ?></div>
        </div>
        <div class="stat-card orange">
            <h3>Currently Borrowed</h3>
            <div class="number"><?= $activeBorrows ?></div>
        </div>
    </div>

    <div class="card">
        <h2>Recent Borrowing Activity</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Student</th>
                        <th>Class</th>
                        <th>Book Title</th>
                        <th>Borrow Date</th>
                        <th>Due Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentActivity)): ?>
                        <tr><td colspan="7">No borrowing records yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentActivity as $row): 
                            $sPhoto = (!empty($row['student_photo']) && file_exists($row['student_photo'])) ? $row['student_photo'] : 'uploads/default_avatar.svg';
                        ?>
                        <tr>
                            <td><img src="<?= htmlspecialchars($sPhoto) ?>" class="table-avatar" alt="Student"></td>
                            <td><?= htmlspecialchars($row['full_name']) ?></td>
                            <td><?= htmlspecialchars($row['class_name']) ?></td>
                            <td><?= htmlspecialchars($row['title']) ?></td>
                            <td><?= $row['borrow_date'] ?></td>
                            <td><?= $row['due_date'] ?></td>
                            <td>
                                <span class="badge <?= $row['status'] === 'Borrowed' ? 'badge-borrowed' : 'badge-returned' ?>">
                                    <?= $row['status'] ?>
                                </span>
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