<?php
/**
 * Login Handler
 *
 * Accepts a username, email, OR numeric ID from a single login field,
 * verifies the password, starts a session, and redirects on success.
 */
require_once __DIR__ . '/db.php';
session_start();

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.html');
    exit;
}

$login    = trim($_POST['user_login'] ?? '');
$password = $_POST['user_password'] ?? '';

$errors = [];

// Validate required fields
if ($login === '') {
    $errors[] = 'Login / Email field is required.';
}
if ($password === '') {
    $errors[] = 'Password field is required.';
}

if (!empty($errors)) {
    header('Location: ../login.html?error=' . urlencode(implode("\n", $errors)));
    exit;
}

// --- Authenticate by login, email, OR numeric ID ---
// The single input field can contain a username, an email address, or a
// numeric user ID. A single prepared statement covers all three cases.
// Binding all three parameters as strings is safe — MySQL implicitly
// converts the string to an integer for the `id` column comparison.
$sql = 'SELECT id, fullname, login, email, password, credit_balance FROM users WHERE login = ? OR email = ? OR id = ?';
$stmt = $conn->prepare($sql);
$stmt->bind_param('sss', $login, $login, $login);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    // Verify the hashed password
    if (password_verify($password, $user['password'])) {
        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);

        $_SESSION['user_id']          = $user['id'];
        $_SESSION['user_fullname']    = $user['fullname'];
        $_SESSION['user_login']       = $user['login'];
        $_SESSION['user_email']       = $user['email'];
        $_SESSION['credit_balance']   = $user['credit_balance'];
        $_SESSION['logged_in']        = true;

        $stmt->close();

        // Redirect to shop page on success
        header('Location: shop.php');
        exit;
    }
}

// Invalid credentials (user not found or password mismatch)
$stmt->close();
header('Location: ../login.html?error=' . urlencode('Invalid login ID or password. Please try again.'));
exit;
?>
