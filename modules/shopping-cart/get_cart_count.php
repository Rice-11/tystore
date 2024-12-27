<?php
require_once '../../includes/config.php';

if (!isset($_SESSION['cart'])) {
    echo '0';
    exit;
}

$total = 0;
foreach ($_SESSION['cart'] as $item) {
    $total += $item['quantity'];
}

echo $total; 