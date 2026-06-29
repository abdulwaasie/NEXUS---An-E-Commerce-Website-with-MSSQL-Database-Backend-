<?php
// ============================================================
// php/auth.php
// Handles: login, register, logout, session check
// ============================================================
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once '../config/database.php';
session_start();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ──────────────────────────────────────────────────────────
// LOGIN
// ──────────────────────────────────────────────────────────
if ($action === 'login') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$email || !$password) {
        echo json_encode(["success" => false, "message" => "Email and password are required"]);
        exit;
    }

    $conn  = getConnection();
    $stmt  = sqlsrv_query($conn,
        "SELECT CustomerID, FirstName, LastName, Email, PasswordHash, City
         FROM Customers WHERE Email = ? AND IsActive = 1",
        [[$email, SQLSRV_PARAM_IN]]
    );
    $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    if ($user && password_verify($password, $user['PasswordHash'])) {
        $_SESSION['customer_id']    = $user['CustomerID'];
        $_SESSION['customer_name']  = $user['FirstName'] . ' ' . $user['LastName'];
        $_SESSION['customer_email'] = $user['Email'];

        echo json_encode([
            "success"     => true,
            "message"     => "Login successful",
            "customer_id" => $user['CustomerID'],
            "name"        => $user['FirstName'] . ' ' . $user['LastName'],
            "email"       => $user['Email'],
            "city"        => $user['City']
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Invalid email or password"]);
    }
    sqlsrv_close($conn);
    exit;
}

// ──────────────────────────────────────────────────────────
// REGISTER
// ──────────────────────────────────────────────────────────
if ($action === 'register') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName  = trim($_POST['last_name']  ?? '');
    $email     = trim($_POST['email']      ?? '');
    $password  = trim($_POST['password']   ?? '');
    $phone     = trim($_POST['phone']      ?? '');
    $address   = trim($_POST['address']    ?? '');
    $city      = trim($_POST['city']       ?? '');

    if (!$firstName || !$email || !$password) {
        echo json_encode(["success" => false, "message" => "First name, email and password are required"]);
        exit;
    }
    if (strlen($password) < 6) {
        echo json_encode(["success" => false, "message" => "Password must be at least 6 characters"]);
        exit;
    }

    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
    $conn  = getConnection();

    // Check if email already exists
    $check = sqlsrv_query($conn,
        "SELECT CustomerID FROM Customers WHERE Email = ?",
        [[$email, SQLSRV_PARAM_IN]]
    );
    if (sqlsrv_fetch_array($check, SQLSRV_FETCH_ASSOC)) {
        echo json_encode(["success" => false, "message" => "This email is already registered"]);
        sqlsrv_close($conn);
        exit;
    }

    $stmt = sqlsrv_query($conn,
        "INSERT INTO Customers (FirstName, LastName, Email, PasswordHash, Phone, Address, City)
         OUTPUT INSERTED.CustomerID
         VALUES (?, ?, ?, ?, ?, ?, ?)",
        [
            [$firstName,    SQLSRV_PARAM_IN],
            [$lastName,     SQLSRV_PARAM_IN],
            [$email,        SQLSRV_PARAM_IN],
            [$passwordHash, SQLSRV_PARAM_IN],
            [$phone,        SQLSRV_PARAM_IN],
            [$address,      SQLSRV_PARAM_IN],
            [$city,         SQLSRV_PARAM_IN]
        ]
    );

    if ($stmt) {
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        echo json_encode([
            "success"     => true,
            "message"     => "Account created successfully!",
            "customer_id" => $row['CustomerID']
        ]);
    } else {
        $errors = sqlsrv_errors();
        echo json_encode(["success" => false, "message" => "Registration failed", "details" => $errors]);
    }
    sqlsrv_close($conn);
    exit;
}

// ──────────────────────────────────────────────────────────
// LOGOUT
// ──────────────────────────────────────────────────────────
if ($action === 'logout') {
    session_destroy();
    echo json_encode(["success" => true, "message" => "Logged out"]);
    exit;
}

// ──────────────────────────────────────────────────────────
// SESSION CHECK (call on page load to restore session)
// ──────────────────────────────────────────────────────────
if ($action === 'check') {
    if (isset($_SESSION['customer_id'])) {
        echo json_encode([
            "success"     => true,
            "logged_in"   => true,
            "customer_id" => $_SESSION['customer_id'],
            "name"        => $_SESSION['customer_name'],
            "email"       => $_SESSION['customer_email']
        ]);
    } else {
        echo json_encode(["success" => true, "logged_in" => false]);
    }
    exit;
}

echo json_encode(["success" => false, "message" => "Invalid action"]);
?>
