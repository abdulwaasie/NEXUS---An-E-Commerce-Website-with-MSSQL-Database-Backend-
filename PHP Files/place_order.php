<?php
include("config/db.php");

/* 1. Get form data */
$customer_id = $_POST['customer_id'];
$product_id  = $_POST['product_id'];
$quantity    = $_POST['quantity'];

/* 2. Get product price */
$sql = "SELECT Price FROM Products WHERE ProductID = ?";
$stmt = sqlsrv_query($conn, $sql, array($product_id));

$product = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if (!$product) {
    die("Product not found!");
}

$price = $product['Price'];
$total = $price * $quantity;

/* 3. Insert into Orders */
$order_sql = "INSERT INTO Orders (CustomerID, TotalAmount, OrderStatus)
              VALUES (?, ?, ?)";

$order_params = array($customer_id, $total, 'Pending');

$stmt_order = sqlsrv_query($conn, $order_sql, $order_params);

if ($stmt_order === false) {
    die(print_r(sqlsrv_errors(), true));
}

/* 4. Get last inserted OrderID (IMPORTANT FIX) */
$order_id = null;

$sql_id = "SELECT SCOPE_IDENTITY() AS OrderID";
$stmt_id = sqlsrv_query($conn, $sql_id);
$row = sqlsrv_fetch_array($stmt_id, SQLSRV_FETCH_ASSOC);

$order_id = $row['OrderID'];

/* 5. Insert into OrderItems */
$item_sql = "INSERT INTO OrderItems (OrderID, ProductID, Quantity, Price)
             VALUES (?, ?, ?, ?)";

$item_params = array($order_id, $product_id, $quantity, $price);

$stmt_item = sqlsrv_query($conn, $item_sql, $item_params);

if ($stmt_item === false) {
    die(print_r(sqlsrv_errors(), true));
}

echo "Order placed successfully!";
?>