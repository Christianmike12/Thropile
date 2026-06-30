<?php
// core.php - Core Security & Utility Functions

// 1. XSS Escaping Helper
function e($string) {
    if (is_null($string)) return '';
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// 2. CSRF Protection
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

function verify_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Pengecualian untuk AJAX req (bisa pass via header atau data POST khusus)
        // Kita harapkan form HTML regular mengirimkan `csrf_token` via hidden input.
        if (isset($_POST['is_ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
            // Untuk AJAX sederhana di project ini, jika mereka tidak mengirim CSRF kita biarkan dulu, 
            // ATAU kita bisa enforce dan perbaiki AJAX callnya. 
            // Lebih baik kita update AJAX calls untuk mengirim token juga.
        }

        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
            header('HTTP/1.1 403 Forbidden');
            die("Kesalahan Keamanan: Token CSRF tidak valid. Silakan muat ulang halaman.");
        }
    }
}

// 3. Database Helper for Prepared Statements
// Mengembalikan mysqli_result untuk SELECT, atau integer affected rows untuk query lainnya
function db_query($conn, $sql, $types = "", ...$params) {
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        die("Query Error: " . mysqli_error($conn) . " | SQL: " . $sql);
    }
    
    if ($types && count($params) > 0) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    if (!mysqli_stmt_execute($stmt)) {
        die("Execute Error: " . mysqli_stmt_error($stmt));
    }
    
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result !== false) {
        // Ini adalah SELECT (atau query yang mengembalikan result set)
        return $result;
    }
    
    // Ini adalah INSERT/UPDATE/DELETE
    $affected = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    return $affected;
}
?>
