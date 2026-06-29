# NEXUS---An-E-Commerce-Website-with-MSSQL-Database-Backend-
A complete online shopping system with product browsing, cart management, live order placement, admin dashboard, and inventory control —all connected to a real database.  Microsoft SQL Server  13 tables, 5 views, 7 stored procedures, 2 triggers, 2 functions PHP  live backend connection with transaction-based order processing HTML5, CSS3, JavaScript
# NEXUS E-Commerce Platform
## Database Systems Project
### University Final Submission Report

---

## PROJECT OVERVIEW

**Project Title:** NEXUS — Modern E-Commerce Web Platform  
**Group:** 10  
**Technology Stack:** HTML5 · CSS3 · JavaScript · PHP · Microsoft SQL Server  
**Course:** Database Systems

---

## 1. INTRODUCTION

NEXUS is a fully functional e-commerce web platform designed to simulate a real-world online shopping system similar to Shopify or Daraz.pk. The system demonstrates core database design principles including normalization, referential integrity, stored procedures, triggers, and complex queries — all implemented in Microsoft SQL Server.

The platform allows customers to browse products, filter by category, manage a shopping cart, place orders, and track order history. An admin panel provides inventory control, order management, and sales analytics.

---

## 2. OBJECTIVES

- Design and implement a normalized relational database with 12+ tables
- Apply all database constraints: PRIMARY KEY, FOREIGN KEY, CHECK, UNIQUE, DEFAULT
- Implement stored procedures for all major business operations
- Create database triggers for stock validation and audit logging
- Build a modern, responsive frontend connected to the database via PHP
- Demonstrate full CRUD operations across all entities
- Generate sales reports using complex SQL queries and views

---

## 3. SCOPE

The project covers:

| Module              | Description                                      |
|---------------------|--------------------------------------------------|
| Product Catalog     | Browse, search, and filter products              |
| Shopping Cart       | Add/remove items, quantity management            |
| Order Management    | Place orders with payment integration            |
| Customer Accounts   | Registration, login, order history               |
| Admin Dashboard     | Stats, order updates, inventory control          |
| Inventory System    | Stock monitoring, reorder alerts, audit trail    |
| Payment System      | Multiple payment methods (Cash, JazzCash, etc.)  |
| Reviews & Ratings   | Customer product reviews                         |
| Wishlist            | Save products for later                          |
| Reports             | Sales by category, revenue analytics             |

---

## 4. ER DIAGRAM EXPLANATION

### Entities and Relationships:

```
CATEGORIES ──< PRODUCTS >── SUPPLIERS
                  │
                  ├──< INVENTORY
                  ├──< REVIEWS >── CUSTOMERS
                  ├──< CART >────── CUSTOMERS
                  └──< ORDER_DETAILS >── ORDERS >── CUSTOMERS
                                              │
                                         PAYMENTS
```

**Relationships:**
- **Category → Products**: One-to-Many (one category has many products)
- **Supplier → Products**: One-to-Many (one supplier supplies many products)
- **Product → Inventory**: One-to-One (each product has one inventory record)
- **Customer → Orders**: One-to-Many (one customer places many orders)
- **Order → OrderDetails**: One-to-Many (one order has many line items)
- **Product → OrderDetails**: One-to-Many (one product appears in many order details)
- **Order → Payments**: One-to-One (each order has one payment record)
- **Customer → Cart**: One-to-Many (one customer has many cart items)
- **Customer → Reviews**: One-to-Many (one customer writes many reviews)
- **Product → Reviews**: One-to-Many (one product has many reviews)

---

## 5. DATABASE TABLES SUMMARY

| Table        | Primary Key  | Foreign Keys                  | Key Attributes                         |
|--------------|-------------|-------------------------------|----------------------------------------|
| Categories   | CategoryID  | —                             | CategoryName, Description, IsActive    |
| Suppliers    | SupplierID  | —                             | SupplierName, ContactEmail, Country    |
| Products     | ProductID   | CategoryID, SupplierID        | ProductName, Price, DiscountPct, Brand |
| Inventory    | InventoryID | ProductID                     | StockQuantity, ReorderLevel            |
| Customers    | CustomerID  | —                             | Email (UNIQUE), PasswordHash, City     |
| Admin        | AdminID     | —                             | Username (UNIQUE), Role                |
| Orders       | OrderID     | CustomerID                    | TotalAmount, OrderStatus, TrackingNo   |
| OrderDetails | OrderDetailID| OrderID, ProductID           | Quantity, UnitPrice, Discount          |
| Cart         | CartID      | CustomerID, ProductID         | Quantity                               |
| Payments     | PaymentID   | OrderID                       | Method, Status, TransactionRef         |
| Reviews      | ReviewID    | ProductID, CustomerID         | Rating (1-5), ReviewText               |
| Wishlist     | WishlistID  | CustomerID, ProductID         | AddedAt                                |
| InventoryLog | LogID       | ProductID                     | OldQty, NewQty, ChangedBy (audit)      |

---

## 6. FOLDER STRUCTURE

```
EcommerceProject/
│
├── index.html                    ← Main frontend (home + all pages)
├── README.md                     ← This file
│
├── css/
│   └── style.css                 ← (Embedded in HTML for simplicity)
│
├── js/
│   └── app.js                    ← (Embedded in HTML for simplicity)
│
├── images/
│   ├── cat_electronics.jpg
│   ├── cat_clothing.jpg
│   └── ...
│
├── database/
│   └── ecommerce_database.sql    ← Complete MS SQL script ⬅ RUN THIS FIRST
│
├── config/
│   └── database.php              ← DB connection settings
│
├── php/
│   ├── auth.php                  ← Login, register, logout
│   ├── products.php              ← Get products (with filter/search)
│   ├── cart.php                  ← Cart CRUD operations
│   ├── orders.php                ← Place order, order history
│   └── admin.php                 ← Admin stats, order/stock management
│
└── admin/
    └── dashboard.html            ← Admin panel (embedded in main HTML)
```

---

## 7. STEP-BY-STEP SETUP GUIDE

### Step 1: Install Required Software

1. **XAMPP** (for Apache + PHP):  
   Download from: https://www.apachefriends.org/  
   Install and launch → Start **Apache**

2. **Microsoft SQL Server Express** (free):  
   Download from: https://www.microsoft.com/en-us/sql-server/sql-server-downloads  
   Install with default settings

3. **SQL Server Management Studio (SSMS)**:  
   Download from: https://aka.ms/ssmsfullsetup  
   Use to run SQL scripts

### Step 2: Configure PHP for SQL Server

1. Download **sqlsrv PHP driver** from:  
   https://learn.microsoft.com/en-us/sql/connect/php/download-drivers-php-sql-server

2. Copy the `.dll` files to `C:\xampp\php\ext\`

3. Edit `C:\xampp\php\php.ini`, add:
   ```
   extension=php_sqlsrv_81_ts_x64.dll
   extension=php_pdo_sqlsrv_81_ts_x64.dll
   ```

4. Restart Apache in XAMPP

### Step 3: Create the Database

1. Open **SSMS** → Connect to `localhost\SQLEXPRESS`
2. Open file `database/ecommerce_database.sql`
3. Click **Execute** (F5) — this creates all tables, data, views, procedures, and triggers
4. Verify: you should see `ECommerceDB` in Object Explorer

### Step 4: Configure PHP Connection

Open `config/database.php` and update:
```php
define('DB_SERVER',   'localhost\SQLEXPRESS');
define('DB_DATABASE', 'ECommerceDB');
define('DB_USERNAME', 'sa');
define('DB_PASSWORD', 'YourActualPassword');
```

> To enable SQL Server Authentication:  
> SSMS → Right-click server → Properties → Security → SQL Server and Windows Authentication

### Step 5: Deploy Project Files

1. Copy entire project folder to: `C:\xampp\htdocs\ecommerce\`
2. Open browser: `http://localhost/ecommerce/index.html`

---

## 8. TESTING INSTRUCTIONS

### Frontend Testing:
| Test                        | Action                                        | Expected Result               |
|-----------------------------|-----------------------------------------------|-------------------------------|
| View products               | Click "Explore Products"                      | Product grid displays         |
| Search product              | Type "Samsung" in search bar                  | Filtered results show         |
| Filter by category          | Click "Electronics" chip                      | Only electronics shown        |
| View product detail         | Click any product card                        | Detail page opens             |
| Add to cart                 | Click "+" or "Add to Cart"                    | Toast shows, count updates    |
| Cart management             | Go to Cart page                               | Items listed with total       |
| Checkout                    | Fill address, select payment, click Place Order | Success toast shown          |
| Login (demo)                | Email: ali@email.com / Password: pass123      | Welcome toast, name in navbar |
| Admin dashboard             | Click "Admin" in navbar                       | Stats, orders, inventory      |
| Update stock (admin)        | Change quantity in Inventory → Update         | Toast confirms update         |

### Database Testing (SSMS):
```sql
-- Test 1: All products with final price
SELECT * FROM vw_ProductDetails;

-- Test 2: Admin stats
EXEC sp_AdminStats;

-- Test 3: Search products
EXEC sp_SearchProducts @SearchTerm = 'Samsung';

-- Test 4: Low stock alert
SELECT * FROM vw_LowStock;

-- Test 5: Sales by category
SELECT * FROM vw_SalesByCategory;

-- Test 6: Best-selling products
SELECT TOP 5 p.ProductName, SUM(od.Quantity) AS TotalSold
FROM OrderDetails od JOIN Products p ON od.ProductID = p.ProductID
GROUP BY p.ProductName ORDER BY TotalSold DESC;
```

---

## 9. FEATURES LIST

### Customer Features:
- ✅ Product browsing with category grid
- ✅ Product search and category filtering
- ✅ Product detail view with quantity selector
- ✅ Add to cart / remove from cart
- ✅ Shopping cart with subtotal and discount
- ✅ Multi-step checkout with address form
- ✅ Multiple payment methods (Cash, JazzCash, EasyPaisa, Credit Card)
- ✅ Order placement with transaction (stored procedure)
- ✅ Order history with status tracking
- ✅ Wishlist functionality
- ✅ Customer registration and login (password hashing)
- ✅ Session-based authentication

### Admin Features:
- ✅ Dashboard with 6 KPI cards (revenue, orders, customers, etc.)
- ✅ Order management with status updates
- ✅ Inventory control with real-time stock update
- ✅ Low stock alerts
- ✅ Customer database view
- ✅ Sales reports by category
- ✅ Revenue analytics

### Database Features:
- ✅ 12 normalized tables (3NF)
- ✅ 5 views (ProductDetails, OrderSummary, SalesByCategory, CustomerOrders, LowStock)
- ✅ 7 stored procedures
- ✅ 3 triggers (stock check, order total update, inventory audit log)
- ✅ 2 scalar functions (FinalPrice, AvgRating)
- ✅ Sample data (5 categories, 4 suppliers, 11 products, 5 customers, 5 orders)
- ✅ Complex JOIN queries, GROUP BY, HAVING, subqueries

---

## 10. ADVANCED SQL CONCEPTS DEMONSTRATED

| Concept             | Where Used                                                    |
|---------------------|---------------------------------------------------------------|
| Normalization (3NF) | All 12 tables                                                 |
| Primary Keys        | Every table                                                   |
| Foreign Keys        | Products→Categories, Products→Suppliers, Orders→Customers, etc.|
| CHECK Constraints   | Price≥0, Rating 1-5, OrderStatus enum, StockQuantity≥0        |
| UNIQUE Constraints  | Customers.Email, Admin.Username                               |
| DEFAULT Values      | GETDATE(), 'Pending', 0                                       |
| Stored Procedures   | sp_GetProducts, sp_PlaceOrder, sp_RegisterCustomer, etc.      |
| Views               | vw_ProductDetails, vw_OrderSummary, vw_SalesByCategory        |
| Triggers            | Stock check (INSTEAD OF), total recalc, audit log (AFTER)     |
| Transactions        | sp_PlaceOrder with BEGIN/COMMIT/ROLLBACK                      |
| Scalar Functions    | fn_GetFinalPrice, fn_GetAvgRating                             |
| Complex Queries     | TOP 5 sellers, monthly revenue, LEFT JOIN for non-orders      |
| Aggregate Functions | SUM, COUNT, AVG, MAX with GROUP BY                            |

---

## 11. CONCLUSION

The NEXUS E-Commerce platform successfully demonstrates a complete database system implementing all key concepts of the Database Systems course. The project showcases proper schema design, robust SQL programming, and a polished professional frontend — making it suitable for a real commercial deployment with minimal modifications.

The use of stored procedures encapsulates business logic at the database level, triggers enforce data integrity automatically, and views simplify complex queries for the application layer. The PHP backend bridges the modern frontend with the MS SQL Server database using parameterized queries to prevent SQL injection.

---

## 12. FUTURE IMPROVEMENTS

- Implement product image upload system
- Add real-time order tracking with SMS/email notifications
- Integrate actual payment gateways (JazzCash, EasyPaisa APIs)
- Add product recommendation engine using ML
- Implement Redis caching for frequently accessed product data
- Add GraphQL API layer for mobile app support
- Implement A/B testing for UI conversion optimization
- Add multi-vendor marketplace functionality
- Build mobile app using React Native

---

*Group 10 · Database Systems · University Project · 2026*
