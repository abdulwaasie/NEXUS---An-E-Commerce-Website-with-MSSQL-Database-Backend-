<?php
// ============================================================
// php/orders.php
// Handles: place order, fetch order history
// Called by the frontend via fetch() (AJAX / JSON)
// ============================================================
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once '../config/database.php';
session_start();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ──────────────────────────────────────────────────────────
// ACTION: place_order
// Receives: customer_id, address, payment_method, cart (JSON)
// cart format: [{"id":1,"qty":2,"price":79999,"discount":5}, ...]
// ──────────────────────────────────────────────────────────
if ($action === 'place_order') {

    // --- Get POST data ---
    $customerID    = isset($_POST['customer_id'])    ? (int)$_POST['customer_id']   : 0;
    $address       = trim($_POST['address']          ?? '');
    $paymentMethod = trim($_POST['payment_method']   ?? 'Cash');
    $cartJSON      = $_POST['cart']                  ?? '[]';
    $cartItems     = json_decode($cartJSON, true);

    // --- Validate ---
    if (!$customerID) {
        echo json_encode(["success" => false, "message" => "Not logged in"]);
        exit;
    }
    if (empty($address)) {
        echo json_encode(["success" => false, "message" => "Delivery address is required"]);
        exit;
    }
    if (empty($cartItems)) {
        echo json_encode(["success" => false, "message" => "Cart is empty"]);
        exit;
    }

    $conn = getConnection();

    // --- Begin transaction manually ---
    sqlsrv_begin_transaction($conn);

    try {
        // 1. Calculate total from DB prices (don't trust client prices fully — verify from DB)
        $total = 0;
        $verifiedItems = [];

        foreach ($cartItems as $item) {
            $pid = (int)$item['id'];
            $qty = (int)$item['qty'];

            // Fetch real price from DB
            $pstmt = sqlsrv_query($conn,
                "SELECT Price, DiscountPct FROM Products WHERE ProductID = ? AND IsActive = 1",
                [[$pid, SQLSRV_PARAM_IN]]
            );
            if (!$pstmt) throw new Exception("Failed to fetch product #$pid");
            $prod = sqlsrv_fetch_array($pstmt, SQLSRV_FETCH_ASSOC);
            if (!$prod) throw new Exception("Product #$pid not found");

            // Check stock
            $sstmt = sqlsrv_query($conn,
                "SELECT StockQuantity FROM Inventory WHERE ProductID = ?",
                [[$pid, SQLSRV_PARAM_IN]]
            );
            $stock = sqlsrv_fetch_array($sstmt, SQLSRV_FETCH_ASSOC);
            if (!$stock || $stock['StockQuantity'] < $qty) {
                throw new Exception("Insufficient stock for Product #$pid");
            }

            $finalPrice = $prod['Price'] * (1 - $prod['DiscountPct'] / 100);
            $subtotal   = $finalPrice * $qty;
            $total     += $subtotal;

            $verifiedItems[] = [
                'id'       => $pid,
                'qty'      => $qty,
                'price'    => $prod['Price'],
                'discount' => $prod['DiscountPct']
            ];
        }

        // 2. Insert into Orders
        $ostmt = sqlsrv_query($conn,
            "INSERT INTO Orders (CustomerID, TotalAmount, ShippingAddr, OrderStatus)
             OUTPUT INSERTED.OrderID
             VALUES (?, ?, ?, 'Pending')",
            [
                [$customerID,  SQLSRV_PARAM_IN],
                [$total,       SQLSRV_PARAM_IN],
                [$address,     SQLSRV_PARAM_IN]
            ]
        );
        if (!$ostmt) throw new Exception("Failed to create order");
        $orow = sqlsrv_fetch_array($ostmt, SQLSRV_FETCH_ASSOC);
        $orderID = $orow['OrderID'];

        // 3. Insert OrderDetails + reduce stock for each item
        foreach ($verifiedItems as $item) {
            // OrderDetails
            $r = sqlsrv_query($conn,
                "INSERT INTO OrderDetails (OrderID, ProductID, Quantity, UnitPrice, Discount)
                 VALUES (?, ?, ?, ?, ?)",
                [
                    [$orderID,        SQLSRV_PARAM_IN],
                    [$item['id'],     SQLSRV_PARAM_IN],
                    [$item['qty'],    SQLSRV_PARAM_IN],
                    [$item['price'],  SQLSRV_PARAM_IN],
                    [$item['discount'], SQLSRV_PARAM_IN]
                ]
            );
            if (!$r) throw new Exception("Failed to insert order detail");

            // Reduce stock in Inventory
            $r2 = sqlsrv_query($conn,
                "UPDATE Inventory SET StockQuantity = StockQuantity - ?
                 WHERE ProductID = ?",
                [
                    [$item['qty'], SQLSRV_PARAM_IN],
                    [$item['id'],  SQLSRV_PARAM_IN]
                ]
            );
            if (!$r2) throw new Exception("Failed to update stock");
        }

        // 4. Insert Payment record
        $pr = sqlsrv_query($conn,
            "INSERT INTO Payments (OrderID, Amount, Method, Status)
             VALUES (?, ?, ?, 'Pending')",
            [
                [$orderID,       SQLSRV_PARAM_IN],
                [$total,         SQLSRV_PARAM_IN],
                [$paymentMethod, SQLSRV_PARAM_IN]
            ]
        );
        if (!$pr) throw new Exception("Failed to record payment");

        // 5. Commit
        sqlsrv_commit($conn);

        $trackingNo = 'TRK-' . strtoupper(substr(md5($orderID . time()), 0, 8));

        // Update tracking number
        sqlsrv_query($conn,
            "UPDATE Orders SET TrackingNo = ? WHERE OrderID = ?",
            [[$trackingNo, SQLSRV_PARAM_IN], [$orderID, SQLSRV_PARAM_IN]]
        );

        echo json_encode([
            "success"    => true,
            "message"    => "Order placed successfully!",
            "order_id"   => $orderID,
            "tracking"   => $trackingNo,
            "total"      => round($total, 2)
        ]);

    } catch (Exception $e) {
        sqlsrv_rollback($conn);
        echo json_encode([
            "success" => false,
            "message" => $e->getMessage()
        ]);
    }

    sqlsrv_close($conn);
    exit;
}

// ──────────────────────────────────────────────────────────
// ACTION: history
// Returns all orders for a given customer_id
// ──────────────────────────────────────────────────────────
if ($action === 'history') {
    $customerID = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;

    if (!$customerID) {
        echo json_encode(["success" => false, "message" => "Customer ID required"]);
        exit;
    }

    $conn = getConnection();

    $stmt = sqlsrv_query($conn,
        "SELECT
            o.OrderID,
            o.OrderDate,
            o.TotalAmount,
            o.OrderStatus,
            o.TrackingNo,
            o.ShippingAddr,
            p.Method   AS PaymentMethod,
            p.Status   AS PaymentStatus
         FROM Orders o
         LEFT JOIN Payments p ON o.OrderID = p.OrderID
         WHERE o.CustomerID = ?
         ORDER BY o.OrderDate DESC",
        [[$customerID, SQLSRV_PARAM_IN]]
    );

    $orders = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        // Format DateTime object
        if ($row['OrderDate'] instanceof DateTime) {
            $row['OrderDate'] = $row['OrderDate']->format('Y-m-d H:i');
        }
        // Fetch order items for this order
        $istmt = sqlsrv_query($conn,
            "SELECT pr.ProductName, od.Quantity, od.UnitPrice, od.Discount
             FROM OrderDetails od
             JOIN Products pr ON od.ProductID = pr.ProductID
             WHERE od.OrderID = ?",
            [[$row['OrderID'], SQLSRV_PARAM_IN]]
        );
        $row['items'] = [];
        while ($item = sqlsrv_fetch_array($istmt, SQLSRV_FETCH_ASSOC)) {
            $row['items'][] = $item;
        }
        $orders[] = $row;
    }

    echo json_encode(["success" => true, "data" => $orders]);
    sqlsrv_close($conn);
    exit;
}

echo json_encode(["success" => false, "message" => "Invalid action"]);
?>
