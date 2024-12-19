<?php
include 'db.php';
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$res = $conn->query("SELECT o.id, r.name AS restaurant_name, o.status, o.order_time FROM foodpanda_orders o JOIN foodpanda_restaurants r ON o.restaurant_id = r.id WHERE o.user_id=$user_id ORDER BY o.order_time DESC");
$orders = [];
while($row = $res->fetch_assoc()) {
    $orders[] = $row;
}
?>

<style>
    /* General Body Styling */
    body {
        font-family: 'Roboto', Arial, sans-serif;
        background-color: #f9f9f9;
        color: #333;
        margin: 0;
        padding: 0;
        line-height: 1.6;
    }

    /* Header Styling */
    h1 {
        background-color: #e91e63;
        color: white;
        text-align: center;
        padding: 20px;
        margin: 0;
        font-size: 24px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    /* Navigation Link */
    a {
        display: inline-block;
        text-decoration: none;
        color: white;
        background-color: #e91e63;
        padding: 10px 20px;
        margin: 20px auto;
        font-size: 16px;
        font-weight: bold;
        border-radius: 25px;
        transition: background-color 0.3s ease, transform 0.2s ease;
        text-align: center;
        text-transform: uppercase;
        display: block;
        width: 200px;
        text-align: center;
    }

    a:hover {
        background-color: #d81b60;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    /* Table Styling */
    table {
        width: 90%;
        margin: 20px auto;
        border-collapse: collapse;
        background: white;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        border-radius: 8px;
        overflow: hidden;
    }

    table th {
        background-color: #e91e63;
        color: white;
        padding: 15px;
        text-align: left;
        font-size: 16px;
        text-transform: uppercase;
    }

    table td {
        padding: 15px;
        border-bottom: 1px solid #ddd;
        font-size: 14px;
        color: #555;
    }

    table tr:hover {
        background-color: #fce4ec;
    }

    /* Footer */
    footer {
        text-align: center;
        padding: 15px;
        background-color: #e91e63;
        color: white;
        margin-top: 20px;
        font-size: 14px;
        position: fixed;
        bottom: 0;
        width: 100%;
    }
</style>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Order History</title>
<style>
body { font-family: Arial; }
table { border-collapse: collapse; width:80%; margin:auto; }
table th, table td { border:1px solid #ccc; padding:10px; text-align:left; }
</style>
</head>
<body>
<h1>Order History</h1>
<a href="index.php">Back to Home</a>
<table>
<tr>
<th>Order ID</th>
<th>Restaurant</th>
<th>Status</th>
<th>Order Time</th>
</tr>
<?php foreach($orders as $o): ?>
<tr>
<td><?php echo $o['id']; ?></td>
<td><?php echo htmlspecialchars($o['restaurant_name']); ?></td>
<td><?php echo htmlspecialchars($o['status']); ?></td>
<td><?php echo $o['order_time']; ?></td>
</tr>
<?php endforeach; ?>
</table>
</body>
</html>
