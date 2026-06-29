<?php
require_once '../config/database.php';

$conn = getConnection();
$stmt = sqlsrv_query($conn, "SELECT CustomerID, Email, PasswordHash FROM Customers WHERE Email = 'ali@email.com'");
$user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if ($user) {
    echo "User found!<br>";
    echo "Email: " . $user['Email'] . "<br>";
    echo "Hash: " . $user['PasswordHash'] . "<br>";
    
    $test = password_verify('pass123', $user['PasswordHash']);
    echo "Password match: " . ($test ? "YES ✅" : "NO ❌") . "<br>";
} else {
    echo "User NOT found in database!";
}
?>