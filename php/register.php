<?php
/**
 * Registration Handler
 * Validates all fields server-side (required fields, formats, duplicates),
 * hashes passwords with bcrypt, and stores user data in the database.
 *
 * Fields saved: fullname, address, login, email, password (hashed), credit_balance
 * Fields validated but NOT stored (security): credit_card_number
 */
require_once __DIR__ . '/db.php';
session_start();

// --- Only accept POST requests ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../create-account.html');
    exit;
}

// --- Collect and trim form data ---
$first_name   = trim($_POST['first_name'] ?? '');
$last_name    = trim($_POST['last_name'] ?? '');
$email        = trim($_POST['email_username'] ?? '');
$login_id     = trim($_POST['login_id'] ?? '');
$address      = trim($_POST['address'] ?? '');
$password     = $_POST['password'] ?? '';
$confirm_pass = $_POST['confirm_password'] ?? '';
$credit_card  = trim($_POST['credit_card_number'] ?? '');

$errors = [];

// --- Validate required fields ---
if ($first_name === '')   { $errors[] = 'First name is required.'; }
if ($last_name === '')    { $errors[] = 'Last name is required.'; }
if ($email === '')        { $errors[] = 'Email is required.'; }
if ($login_id === '')     { $errors[] = 'Login ID is required.'; }
if ($address === '')      { $errors[] = 'Address is required.'; }
if ($password === '')     { $errors[] = 'Password is required.'; }
if ($confirm_pass === '') { $errors[] = 'Confirm password is required.'; }
if ($credit_card === '')  { $errors[] = 'Credit card number is required.'; }

// Redirect immediately if any required field is missing
if (!empty($errors)) {
    header('Location: ../create-account.html?error=' . urlencode(implode("\n", $errors)));
    exit;
}

// --- Validate field formats ---

// Name fields: only letters and spaces allowed
if (!preg_match('/^[A-Za-z\s]+$/', $first_name)) {
    $errors[] = 'First name: only letters and spaces allowed.';
}
if (!preg_match('/^[A-Za-z\s]+$/', $last_name)) {
    $errors[] = 'Last name: only letters and spaces allowed.';
}

// Login ID: exactly 5 chars, must start with a letter, then letters, numbers, _ or $
if (!preg_match('/^[A-Za-z][A-Za-z0-9_$]{4}$/', $login_id)) {
    $errors[] = 'Login ID must be exactly 5 characters, start with a letter, and contain only letters, numbers, _ and $.';
}

// Email format: use PHP's built-in validator
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address.';
}

// Credit card: digits only (validated but NOT stored for security)
if (!preg_match('/^[0-9]+$/', $credit_card)) {
    $errors[] = 'Credit card number: digits only.';
}

// Password confirmation must match
if ($password !== $confirm_pass) {
    $errors[] = 'Password and confirm password must match.';
}

// Redirect back with all format errors at once
if (!empty($errors)) {
    header('Location: ../create-account.html?error=' . urlencode(implode("\n", $errors)));
    exit;
}

// --- Check for duplicate login ---
$stmt = $conn->prepare('SELECT id FROM users WHERE login = ?');
$stmt->bind_param('s', $login_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->close();
    header('Location: ../create-account.html?error=' . urlencode('This Login ID is already taken. Please choose another.'));
    exit;
}
$stmt->close();

// --- Check for duplicate email ---
$stmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->close();
    header('Location: ../create-account.html?error=' . urlencode('This email is already registered. Please use a different email or log in with your existing account.'));
    exit;
}
$stmt->close();

// --- Insert new user ---
$fullname       = $first_name . ' ' . $last_name;
$hashed_pw      = password_hash($password, PASSWORD_DEFAULT);
$credit_balance = 1000.00;

$stmt = $conn->prepare('INSERT INTO users (fullname, address, login, email, password, credit_balance) VALUES (?, ?, ?, ?, ?, ?)');
$stmt->bind_param('sssssd', $fullname, $address, $login_id, $email, $hashed_pw, $credit_balance);

if ($stmt->execute()) {
    $stmt->close();
    // Success — redirect to login page with a friendly message
    header('Location: ../login.html?registered=1');
    exit;
} else {
    // Catch any unexpected database error (e.g. duplicate from race condition)
    $stmt->close();
    header('Location: ../create-account.html?error=' . urlencode('Registration failed due to a server error. Please try again.'));
    exit;
}
?>
