<?php
require 'auth.php';
require 'db.php';

$msg = '';
$adminId = $_SESSION['admin_id'] ?? 1;

// Fetch current admin data
$stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$adminId]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';
    $department = trim($_POST['department'] ?? '');
    $program = trim($_POST['program'] ?? '');
    $regNumber = trim($_POST['reg_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $className = trim($_POST['class_name'] ?? '');

    if (empty($fullName)) {
        $msg = "<div class='alert alert-error'>name of leader is not null</div>";
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = "<div class='alert alert-error'>Email address written is wrong. </div>";
    } else {
        $photoPath = $admin['photo'] ?? 'uploads/default_avatar.svg';
        $uploadOk = true;

        // Handle Photo Upload
        if (isset($_FILES['admin_photo']) && $_FILES['admin_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = uploadPhoto($_FILES['admin_photo'], 'admin');
            if ($uploadResult['status'] === 'error') {
                $msg = "<div class='alert alert-error'>" . htmlspecialchars($uploadResult['message']) . "</div>";
                $uploadOk = false;
            } elseif ($uploadResult['status'] === 'success') {
                // Delete old custom photo if it exists
                if (!empty($photoPath) && $photoPath !== 'uploads/default_avatar.svg' && file_exists($photoPath)) {
                    @unlink($photoPath);
                }
                $photoPath = $uploadResult['path'];
            }
        }

        if ($uploadOk) {
            try {
                if (!empty($newPassword)) {
                    if (strlen($newPassword) < 6) {
                        $msg = "<div class='alert alert-error'>Password nshya igomba kugira nibura inyuguti/imibare 6!</div>";
                    } else {
                        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE admins SET full_name = ?, photo = ?, password = ?, department = ?, program = ?, reg_number = ?, email = ?, class_name = ? WHERE id = ?");
                        $stmt->execute([$fullName, $photoPath, $hashed, $department, $program, $regNumber, $email, $className, $adminId]);
                        $_SESSION['admin_name'] = $fullName;
                        $_SESSION['admin_photo'] = $photoPath;
                        $msg = "<div class='alert alert-success'>Umwirondoro, ifoto, na password byavuguruwe neza!</div>";
                    }
                } else {
                    $stmt = $pdo->prepare("UPDATE admins SET full_name = ?, photo = ?, department = ?, program = ?, reg_number = ?, email = ?, class_name = ? WHERE id = ?");
                    $stmt->execute([$fullName, $photoPath, $department, $program, $regNumber, $email, $className, $adminId]);
                    $_SESSION['admin_name'] = $fullName;
                    $_SESSION['admin_photo'] = $photoPath;
                    $msg = "<div class='alert alert-success'>succesfully</div>";
                }

                // Refresh admin record
                $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
                $stmt->execute([$adminId]);
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $msg = "<div class='alert alert-error'>Database Error: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile - KABUSHINGE TSS Library</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .preview-box {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid var(--border);
        }
        .preview-box img {
            width: 95px;
            height: 95px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary);
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="header">
        <h1>Admin Profile & Photo Upload</h1>
    </div>

    <?= $msg ?>

    <div class="card">
        <h2>Update Your Photo & Details</h2>

        <div class="preview-box">
            <img id="photoPreview" src="<?= htmlspecialchars($_SESSION['admin_photo']) ?>" alt="Current Profile Photo">
            <div>
                <h3 style="color: var(--secondary);"><?= htmlspecialchars($admin['full_name'] ?? 'Librarian') ?></h3>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Username: <strong><?= htmlspecialchars($admin['username'] ?? 'admin') ?></strong></p>
                <p style="color: var(--text-muted); font-size: 0.82rem; margin-top: 4px;">Ifoto yemewe: JPG, PNG, WEBP (Ntirenze 2MB)</p>
            </div>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-grid">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" required value="<?= htmlspecialchars($admin['full_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Department</label>
                    <input type="text" name="department" placeholder="e.g. ICT Department" value="<?= htmlspecialchars($admin['department'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Program</label>
                    <input type="text" name="program" placeholder="e.g. Information Technology" value="<?= htmlspecialchars($admin['program'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Reg Number</label>
                    <input type="text" name="reg_number" value="<?= htmlspecialchars($admin['reg_number'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($admin['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Class</label>
                    <input type="text" name="class_name" placeholder="e.g. Year 2" value="<?= htmlspecialchars($admin['class_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Upload New Profile Photo</label>
                    <input type="file" name="admin_photo" accept="image/jpeg,image/png,image/webp" onchange="previewImage(event)">
                </div>
                <div class="form-group">
                    <label>New Password (Leave blank to keep current)</label>
                    <input type="password" name="new_password" placeholder="Min 6 characters">
                </div>
            </div>
            <div style="margin-top: 18px;">
                <button type="submit" class="btn btn-primary">Save Profile Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function previewImage(event) {
    const file = event.target.files[0];
    if (file) {
        document.getElementById('photoPreview').src = URL.createObjectURL(file);
    }
}
</script>
</body>
</html>
