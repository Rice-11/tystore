<?php
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('modules/security/login.php');
}

$db = Database::getInstance()->getConnection();

// Get time period from request
$period = isset($_GET['period']) ? sanitize($_GET['period']) : 'month';
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// Determine date range based on period
$date_range = match($period) {
    'week' => 'DATE_SUB(CURRENT_DATE, INTERVAL 1 WEEK)',
    'year' => 'DATE_SUB(CURRENT_DATE, INTERVAL 1 YEAR)',
    default => 'DATE_SUB(CURRENT_DATE, INTERVAL 1 MONTH)' // month is default
};

// Get categories for filter
$categories = $db->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name")->fetchAll();

// Build query conditions
$conditions = ["o.status = 'delivered'", "o.created_at >= $date_range"];
$params = [];

if ($category_id) {
    $conditions[] = "p.category_id = ?";
    $params[] = $category_id;
}

$whereClause = implode(" AND ", $conditions);

// Get top selling products
$query = "SELECT p.*, c.name as category_name,
          COUNT(oi.id) as total_orders,
          SUM(oi.quantity) as total_quantity,
          SUM(oi.quantity * oi.price) as total_revenue
          FROM products p
          JOIN categories c ON p.category_id = c.id
          JOIN order_items oi ON p.id = oi.product_id
          JOIN orders o ON oi.order_id = o.id
          WHERE $whereClause
          GROUP BY p.id
          ORDER BY total_quantity DESC
          LIMIT 10";

$stmt = $db->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get sales trend data
$trend_query = "SELECT DATE(o.created_at) as date,
                SUM(oi.quantity) as daily_sales
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                WHERE o.status = 'delivered'
                AND o.created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
                GROUP BY DATE(o.created_at)
                ORDER BY date";

$trend_data = $db->query($trend_query)->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Top Selling Products</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="report-container">
        <div class="report-header">
            <h2>Top Selling Products</h2>
            <div class="filter-section">
                <form method="GET" class="filter-form">
                    <select name="period" onchange="this.form.submit()">
                        <option value="week" <?php echo $period === 'week' ? 'selected' : ''; ?>>Last Week</option>
                        <option value="month" <?php echo $period === 'month' ? 'selected' : ''; ?>>Last Month</option>
                        <option value="year" <?php echo $period === 'year' ? 'selected' : ''; ?>>Last Year</option>
                    </select>
                    
                    <select name="category" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" 
                                    <?php echo $category_id === $category['id'] ? 'selected' : ''; ?>>
                                <?php echo $category['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        </div>
        
        <div class="report-content">
            <div class="sales-trend">
                <h3>Sales Trend (Last 30 Days)</h3>
                <canvas id="salesChart"></canvas>
            </div>
            
            <div class="top-products">
                <h3>Top 10 Products</h3>
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Orders</th>
                            <th>Quantity Sold</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <div class="product-info">
                                        <img src="../../uploads/products/<?php echo $product['photo']; ?>" 
                                             alt="<?php echo $product['name']; ?>">
                                        <span><?php echo $product['name']; ?></span>
                                    </div>
                                </td>
                                <td><?php echo $product['category_name']; ?></td>
                                <td><?php echo $product['total_orders']; ?></td>
                                <td><?php echo $product['total_quantity']; ?></td>
                                <td>$<?php echo number_format($product['total_revenue'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    // Prepare data for the chart
    const dates = <?php echo json_encode(array_column($trend_data, 'date')); ?>;
    const sales = <?php echo json_encode(array_column($trend_data, 'daily_sales')); ?>;

    // Create sales trend chart
    const ctx = document.getElementById('salesChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: dates,
            datasets: [{
                label: 'Daily Sales',
                data: sales,
                borderColor: '#007bff',
                tension: 0.1,
                fill: false
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
    </script>
</body>
</html> 