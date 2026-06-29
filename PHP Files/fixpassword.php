<?php
require_once '../config/database.php';

$newHash = password_hash('pass123', PASSWORD_BCRYPT);

$conn = getConnection();
$stmt = sqlsrv_query($conn,
    "UPDATE Customers SET PasswordHash = ?",
    [[$newHash, SQLSRV_PARAM_IN]]
);

if ($stmt) {
    echo "✅ All passwords updated! Hash used: " . $newHash;
} else {
    echo "❌ Failed";
    print_r(sqlsrv_errors());
}
?>