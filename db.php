<?php
// db.php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'kabushinge_tss_library';

try {
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbname`");

    // Admins Table (with profile photo)
    $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        photo VARCHAR(255) DEFAULT 'uploads/default_avatar.svg',
        department VARCHAR(120) NULL,
        program VARCHAR(120) NULL,
        reg_number VARCHAR(50) NULL,
        email VARCHAR(120) NULL,
        class_name VARCHAR(50) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Auto-add academic profile columns to existing admins table (upgrade-safe)
    foreach (['department' => 120, 'program' => 120, 'reg_number' => 50, 'email' => 120, 'class_name' => 50] as $col => $len) {
        $exists = $pdo->query("SHOW COLUMNS FROM admins LIKE '$col'")->fetchAll();
        if (empty($exists)) {
            $pdo->exec("ALTER TABLE admins ADD COLUMN $col VARCHAR($len) NULL");
        }
    }

    // Insert default admin if not exists
    $checkAdmin = $pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();
    if ($checkAdmin == 0) {
        $defaultPass = password_hash('kabushinge2026', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO admins (username, password, full_name, photo) VALUES (?, ?, ?, ?)");
        $stmt->execute(['admin', $defaultPass, 'KABUSHINGE TSS Librarian', 'uploads/default_avatar.svg']);
    }

    // Books Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS books (
        id INT AUTO_INCREMENT PRIMARY KEY,
        isbn VARCHAR(50) UNIQUE NOT NULL,
        title VARCHAR(150) NOT NULL,
        author VARCHAR(100) NOT NULL,
        category VARCHAR(80) NOT NULL,
        total_copies INT NOT NULL DEFAULT 1,
        available_copies INT NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Students Table (with student photo)
    $pdo->exec("CREATE TABLE IF NOT EXISTS students (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reg_number VARCHAR(50) UNIQUE NOT NULL,
        full_name VARCHAR(120) NOT NULL,
        class_name VARCHAR(50) NOT NULL,
        gender VARCHAR(15) NOT NULL,
        photo VARCHAR(255) DEFAULT 'uploads/default_avatar.svg',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Auto-add photo column to existing students table if upgrading from v1
    $cols = $pdo->query("SHOW COLUMNS FROM students LIKE 'photo'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE students ADD COLUMN photo VARCHAR(255) DEFAULT 'uploads/default_avatar.svg'");
    }

    // Borrowings Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS borrowings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        book_id INT NOT NULL,
        borrow_date DATE NOT NULL,
        due_date DATE NOT NULL,
        return_date DATE NULL,
        status ENUM('Borrowed', 'Returned') DEFAULT 'Borrowed',
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
    )");

} catch (PDOException $e) {
    die("<div style='font-family:sans-serif;padding:20px;background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;margin:20px;border-radius:6px;'>
        <h3>Database Error</h3>
        <p>Ntibishoboye guhura na MySQL Database. Reba niba MySQL iri kuri <strong>Start</strong> muri XAMPP.</p>
        <small>Technical details: " . htmlspecialchars($e->getMessage()) . "</small>
    </div>");
}

/**
 * Reusable Secure Image Upload Function with Full Error Handling
 */
function uploadPhoto($fileInput, $prefix = 'user') {
    if (!isset($fileInput) || $fileInput['error'] === UPLOAD_ERR_NO_FILE) {
        return ['status' => 'no_file', 'path' => null];
    }

    // Check PHP built-in upload errors
    if ($fileInput['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE   => "Ifoto irarengeje ubunini bwemewe na server (php.ini).",
            UPLOAD_ERR_FORM_SIZE  => "Ifoto irarengeje ubunini bwemewe.",
            UPLOAD_ERR_PARTIAL    => "Ifoto ntiyabashije koherezwa yose. Ongera ugerageze.",
            UPLOAD_ERR_NO_TMP_DIR => "Temporary folder yabuze kuri server.",
            UPLOAD_ERR_CANT_WRITE => "Server yananiwe kwandika ifoto kuri disk.",
            UPLOAD_ERR_EXTENSION  => "Upload yahagaritswe na PHP extension."
        ];
        $msg = $uploadErrors[$fileInput['error']] ?? "Habaye ikibazo mu kohereza ifoto (Error code: {$fileInput['error']}).";
        return ['status' => 'error', 'message' => $msg];
    }

    // Validate file size (Max 2MB)
    $maxSize = 2 * 1024 * 1024;
    if ($fileInput['size'] > $maxSize) {
        return ['status' => 'error', 'message' => "Ifoto ni nini cyane! Ntishobora kurenza 2MB."];
    }

    // Validate real MIME type using finfo (prevents fake renamed files)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($fileInput['tmp_name']);
    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif'
    ];

    if (!array_key_exists($mimeType, $allowedMimes)) {
        return ['status' => 'error', 'message' => "Ubwoko bw'ifoto ntibwemewe! Hitamo JPG, PNG, cyangwa WEBP gusa."];
    }

    // Ensure uploads directory exists and is writable
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            return ['status' => 'error', 'message' => "Folder ya 'uploads/' yanze gukorwa."];
        }
    }

    // Generate safe unique filename
    $ext = $allowedMimes[$mimeType];
    $newFileName = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $destination = $uploadDir . $newFileName;

    if (!move_uploaded_file($fileInput['tmp_name'], $destination)) {
        return ['status' => 'error', 'message' => "Ntibishoboye kubika ifoto muri folder ya uploads."];
    }

    return ['status' => 'success', 'path' => $destination];
}
?>
