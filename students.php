<?php
require 'auth.php';
require 'db.php';
$msg = '';
$editStudent = null;

if (isset($_GET['delete'])) {
    try {
        $stmt = $pdo->prepare("SELECT photo FROM students WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        $oldPhoto = $stmt->fetchColumn();

        $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
        $stmt->execute([$_GET['delete']]);

        if (!empty($oldPhoto) && $oldPhoto !== 'uploads/default_avatar.svg' && file_exists($oldPhoto)) {
            @unlink($oldPhoto);
        }
        $msg = "<div class='alert alert-success'>Student record deleted.</div>";
    } catch (PDOException $e) {
        $msg = "<div class='alert alert-error'>Ntibishoboye gusiba umunyeshuri: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editStudent = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reg = trim($_POST['reg_number'] ?? '');
    $name = trim($_POST['full_name'] ?? '');
    $class = trim($_POST['class_name'] ?? '');
    $gender = $_POST['gender'] ?? 'Male';
    $studentId = $_POST['id'] ?? '';

    if (empty($reg) || empty($name) || empty($class)) {
        $msg = "<div class='alert alert-error'>Uzuza amakuru yose y'umunyeshuri (Reg Number, Izina, n'Ishuri)!</div>";
    } else {
        $photoPath = $_POST['existing_photo'] ?? 'uploads/default_avatar.svg';
        $uploadOk = true;

        // Handle student photo upload with error handling
        if (isset($_FILES['student_photo']) && $_FILES['student_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = uploadPhoto($_FILES['student_photo'], 'student');
            if ($uploadResult['status'] === 'error') {
                $msg = "<div class='alert alert-error'>" . htmlspecialchars($uploadResult['message']) . "</div>";
                $uploadOk = false;
            } elseif ($uploadResult['status'] === 'success') {
                if (!empty($photoPath) && $photoPath !== 'uploads/default_avatar.svg' && file_exists($photoPath)) {
                    @unlink($photoPath);
                }
                $photoPath = $uploadResult['path'];
            }
        }

        if ($uploadOk) {
            try {
                if (!empty($studentId)) {
                    $stmt = $pdo->prepare("UPDATE students SET reg_number=?, full_name=?, class_name=?, gender=?, photo=? WHERE id=?");
                    $stmt->execute([$reg, $name, $class, $gender, $photoPath, $studentId]);
                    $msg = "<div class='alert alert-success'>information updated well!</div>";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO students (reg_number, full_name, class_name, gender, photo) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$reg, $name, $class, $gender, $photoPath]);
                    $msg = "<div class='alert alert-success'>student registered well !</div>";
                }
            } catch (PDOException $e) {
                $msg = "<div class='alert alert-error'>Registration Number already exists!</div>";
            }
        }
    }
}

$students = $pdo->query("SELECT * FROM students ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Students - KABUSHINGE TSS</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="header">
        <h1>Manage Students</h1>
        <div class="header-user">
            <img src="<?= htmlspecialchars($_SESSION['admin_photo']) ?>" alt="Admin">
            <span><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Librarian') ?></span>
        </div>
    </div>

    <?= $msg ?>

    <div class="card">
        <h2><?= $editStudent ? 'Update Student Info & Photo' : 'Register Student (With Photo)' ?></h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= $editStudent['id'] ?? '' ?>">
            <input type="hidden" name="existing_photo" value="<?= htmlspecialchars($editStudent['photo'] ?? 'uploads/default_avatar.svg') ?>">
            <div class="form-grid">
                <div class="form-group">
                    <label>Student Reg Number</label>
                    <input type="text" name="reg_number" placeholder="e.g. GSN-2026-001" required value="<?= htmlspecialchars($editStudent['reg_number'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" required value="<?= htmlspecialchars($editStudent['full_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Class / Level</label>
                    <input type="text" name="class_name" placeholder="e.g. S3 A, S6 MCB" required value="<?= htmlspecialchars($editStudent['class_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Gender</label>
                    <select name="gender" required>
                        <option value="Male" <?= ($editStudent['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= ($editStudent['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Student Photo (JPG/PNG/WEBP, Max 2MB)</label>
                    <input type="file" name="student_photo" accept="image/jpeg,image/png,image/webp">
                </div>
            </div>
            <div style="margin-top: 15px;">
                <button type="submit" class="btn btn-primary"><?= $editStudent ? 'Update Student' : 'Register Student' ?></button>
                <?php if ($editStudent): ?>
                    <a href="students.php" class="btn btn-warning">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="card">
        <h2>Registered Students</h2>
        <div class="search-box">
            <input type="text" id="studentSearch" onkeyup="filterTable('studentSearch', 'studentTable')" placeholder="Search student by name, reg no, or class...">
        </div>
        <div class="table-container">
            <table id="studentTable">
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Reg Number</th>
                        <th>Full Name</th>
                        <th>Class</th>
                        <th>Gender</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $s): 
                        $sPhoto = (!empty($s['photo']) && file_exists($s['photo'])) ? $s['photo'] : 'uploads/default_avatar.svg';
                    ?>
                    <tr>
                        <td><img src="<?= htmlspecialchars($sPhoto) ?>" class="table-avatar" alt="Student"></td>
                        <td><?= htmlspecialchars($s['reg_number']) ?></td>
                        <td><?= htmlspecialchars($s['full_name']) ?></td>
                        <td><?= htmlspecialchars($s['class_name']) ?></td>
                        <td><?= htmlspecialchars($s['gender']) ?></td>
                        <td>
                            <a href="students.php?edit=<?= $s['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                            <a href="students.php?delete=<?= $s['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this student?')">Delete</a>
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
