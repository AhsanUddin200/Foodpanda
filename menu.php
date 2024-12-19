<?php
include 'db.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$restaurant_id = $_GET['restaurant_id'] ?? 0;
$restaurant_id = (int)$restaurant_id;

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $menu_id = (int)$_POST['menu_id'];
    $quantity = (int)$_POST['quantity'];
    if ($quantity > 0) {
        $_SESSION['cart'][] = ['menu_id' => $menu_id, 'quantity' => $quantity];
    }
}

$stmt = $conn->prepare("SELECT name FROM foodpanda_restaurants WHERE id=?");
$stmt->bind_param("i", $restaurant_id);
$stmt->execute();
$stmt->bind_result($rest_name);
$stmt->fetch();
$stmt->close();

$res = $conn->query("SELECT id, item_name, price FROM foodpanda_menu WHERE restaurant_id=$restaurant_id");
$menu_items = [];
while ($row = $res->fetch_assoc()) {
    $menu_items[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - <?php echo htmlspecialchars($rest_name); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #f9f9f9;
        }
        .header {
            background: #ec008c;
            color: #fff;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .header a {
            color: #fff;
            text-decoration: none;
            margin: 0 10px;
            padding: 8px 12px;
            border: 1px solid #fff;
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.1);
            transition: background 0.3s ease;
        }
        .header a:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        h1 {
            text-align: center;
            color: #ec008c;
            margin-top: 20px;
        }
        .menu-list {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            padding: 20px;
            gap: 20px;
        }
        .menu-item {
            background: #fff;
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 15px;
            width: 300px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease;
        }
        .menu-item:hover {
            transform: translateY(-5px);
        }
        .menu-item h3 {
            margin: 0;
            color: #333;
        }
        .menu-item p {
            margin: 5px 0;
            color: #666;
        }
        .menu-item form {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 10px;
        }
        .menu-item input[type="number"] {
            width: 60px;
            padding: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .menu-item button {
            background: #ec008c;
            color: #fff;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .menu-item button:hover {
            background: #d00779;
        }
        @media (max-width: 768px) {
            .menu-item {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="index.php">Back to Restaurants</a>
        <a href="order.php">View Cart</a>
    </div>

    <h1><?php echo htmlspecialchars($rest_name); ?></h1>

    <div class="menu-list">
        <?php if (empty($menu_items)): ?>
            <p style="text-align: center; color: #666;">No menu items available.</p>
        <?php else: ?>
            <?php foreach ($menu_items as $item): ?>
                <div class="menu-item">
                    <h3><?php echo htmlspecialchars($item['item_name']); ?></h3>
                    <p>Price: $<?php echo $item['price']; ?></p>
                    <form method="post">
                        <input type="hidden" name="menu_id" value="<?php echo $item['id']; ?>">
                        <input type="number" name="quantity" value="1" min="1"/>
                        <button type="submit">Add to Cart</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
