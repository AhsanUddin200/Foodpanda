<?php
include 'db.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch restaurants
$cuisine_filter = $_GET['cuisine'] ?? '';
$filter_sql = "";
if (!empty($cuisine_filter)) {
    $filter_sql = "WHERE cuisine LIKE '%" . $conn->real_escape_string($cuisine_filter) . "%'";
}
$res = $conn->query("SELECT id, name, cuisine, rating, delivery_time FROM foodpanda_restaurants $filter_sql");
$restaurants = [];
while ($row = $res->fetch_assoc()) {
    $restaurants[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FoodPanda Clone - Homepage</title>
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
        .header-left {
            display: flex;
            align-items: center;
        }
        .header-left img {
            height: 40px;
            margin-right: 10px;
        }
        .header-left span {
            font-size: 20px;
            font-weight: bold;
        }
        .header-right a {
            color: #fff;
            text-decoration: none;
            margin: 0 10px;
            padding: 8px 12px;
            border: 1px solid #fff;
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.1);
            transition: background 0.3s ease;
        }
        .header-right a:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        h1 {
            text-align: center;
            color: #ec008c;
            margin-top: 20px;
        }
        .filter {
            text-align: center;
            margin: 20px;
        }
        .filter input {
            padding: 8px;
            width: 200px;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-right: 10px;
        }
        .filter button {
            background: #ec008c;
            color: #fff;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .filter button:hover {
            background: #d00779;
        }
        .restaurant-list {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            padding: 20px;
            gap: 20px;
        }
        .restaurant {
            background: #fff;
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 15px;
            width: 300px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease;
        }
        .restaurant:hover {
            transform: translateY(-5px);
        }
        .restaurant h3 {
            margin: 0;
            color: #333;
        }
        .restaurant p {
            margin: 5px 0;
            color: #666;
        }
        .restaurant a {
            display: inline-block;
            margin-top: 10px;
            background: #ec008c;
            color: #fff;
            padding: 8px 12px;
            text-decoration: none;
            border-radius: 4px;
            transition: background 0.3s ease;
        }
        .restaurant a:hover {
            background: #d00779;
        }
        @media (max-width: 768px) {
            .restaurant {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <img src="data:image/png;base64,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" alt="Logo"> <!-- Replace 'logo.png' with your logo path -->
            <span>FoodPanda Clone</span>
        </div>
        <div class="header-right">
            <a href="order.php">View Cart</a>
            <a href="history.php">Order History</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <h1>Restaurants</h1>

    <div class="filter">
        <form method="get">
            <input type="text" name="cuisine" placeholder="Filter by cuisine" value="<?php echo htmlspecialchars($cuisine_filter); ?>" />
            <button type="submit">Filter</button>
        </form>
    </div>

    <div class="restaurant-list">
        <?php if (empty($restaurants)): ?>
            <p style="text-align: center; color: #666;">No restaurants found.</p>
        <?php else: ?>
            <?php foreach ($restaurants as $rest): ?>
                <div class="restaurant">
                    <h3><?php echo htmlspecialchars($rest['name']); ?></h3>
                    <p>Cuisine: <?php echo htmlspecialchars($rest['cuisine']); ?></p>
                    <p>Rating: <?php echo htmlspecialchars($rest['rating']); ?></p>
                    <p>Delivery Time: <?php echo htmlspecialchars($rest['delivery_time']); ?> min</p>
                    <a href="menu.php?restaurant_id=<?php echo $rest['id']; ?>">View Menu</a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
