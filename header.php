<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['valid']);

// Define menu items and their corresponding pages
$menuItems = [
    'profile' => [
        'title' => $isLoggedIn ? 'Account Settings' : 'Login',
        'url' => $isLoggedIn ? 'profile.php' : 'account/login.php',
        'icon' => 'settings.svg'
    ],
    'orders' => [
        'title' => 'Order History',
        'url' => $isLoggedIn ? 'orders.php' : 'account/login.php',
        'icon' => 'orders.svg'
    ],
    'membership' => [
        'title' => 'Membership Status',
        'url' => $isLoggedIn ? 'membership.php' : 'account/login.php',
        'icon' => 'membership.svg'
    ],
    'family' => [
        'title' => 'Family Management',
        'url' => $isLoggedIn ? 'family.php' : 'account/login.php',
        'icon' => 'family.svg'
    ]
];

// Add logout option only for logged-in users
if ($isLoggedIn) {
    $menuItems['logout'] = [
        'title' => 'Logout',
        'url' => 'account/logout.php',
        'icon' => 'logout.svg'
    ];
}
?>

<header>
    
    <a href="index.php">
        <div class="logo">
            <img src="images/logo.svg" alt="company_logo" width="50" height="50">
        </div>
    </a>
    <div class="user-section">
        <div class="dropdown">
            <a href="#" class="user-menu">
                <div class="user">
                    <img src="images/user.svg" alt="user icon" width="50" height="50">
                </div>
            </a>
            <div class="dropdown-content">
                <?php foreach ($menuItems as $key => $item): ?>
                    <a href="<?php echo htmlspecialchars($item['url']); ?>" class="dropdown-item">
                        <img src="images/<?php echo htmlspecialchars($item['icon']); ?>" alt="<?php echo htmlspecialchars($key); ?> icon" width="20" height="20">
                        <span><?php echo htmlspecialchars($item['title']); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</header>

<style>
.dropdown {
    position: relative;
    display: inline-block;
}

.dropdown-content {
    display: none;
    position: absolute;
    right: 0;
    background-color: #f9f9f9;
    min-width: 200px;
    box-shadow: 0 8px 16px rgba(0,0,0,0.2);
    z-index: 1;
    border-radius: 8px;
    overflow: hidden;
}

.dropdown:hover .dropdown-content {
    display: block;
}

.dropdown-item {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    text-decoration: none;
    color: #333;
    transition: background-color 0.3s;
}

.dropdown-item:hover {
    background-color: #f1f1f1;
}

.dropdown-item img {
    margin-right: 10px;
}

.dropdown-item span {
    font-size: 14px;
}

/* Add separator between items */
.dropdown-item:not(:last-child) {
    border-bottom: 1px solid #eee;
}
</style>