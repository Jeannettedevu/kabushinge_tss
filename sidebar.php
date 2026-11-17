<?php
// sidebar.php - Reusable Sidebar with Logged-in Admin Photo
$currentPage = basename($_SERVER['PHP_SELF']);
$adminPhoto = $_SESSION['admin_photo'] ?? 'uploads/default_avatar.svg';
$adminName  = $_SESSION['admin_name'] ?? 'Librarian';
?>
<div class="sidebar">
    <div class="brand">
        <h2>KABUSHINGE TSS</h2>
        <p>Library Management</p>
    </div>

    <div class="admin-profile">
        <img src="<?= htmlspecialchars($adminPhoto) ?>" alt="Admin Photo">
        <div class="admin-name"><?= htmlspecialchars($adminName) ?></div>
        <div class="admin-role">System Administrator</div>
    </div>

    <ul class="nav-links">
        <li><a href="index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">Dashboard</a></li>
        <li><a href="books.php" class="<?= $currentPage === 'books.php' ? 'active' : '' ?>">Manage Books</a></li>
        <li><a href="students.php" class="<?= $currentPage === 'students.php' ? 'active' : '' ?>">Manage Students</a></li>
        <li><a href="borrow.php" class="<?= $currentPage === 'borrow.php' ? 'active' : '' ?>">Issue & Return Books</a></li>
        <li><a href="reports.php" class="<?= $currentPage === 'reports.php' ? 'active' : '' ?>">Reports</a></li>
        <li><a href="profile.php" class="<?= $currentPage === 'profile.php' ? 'active' : '' ?>">My Profile & Photo</a></li>
        <li><a href="logout.php" style="color: #f87171;">Logout</a></li>
    </ul>
</div>
