<?php
include 'db.php';
session_start();

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$drivers = ["Ali", "Sara", "Ahmed", "Fatima"];

$message = '';

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    if(!empty($_SESSION['cart'])) {
        $menu_id = $_SESSION['cart'][0]['menu_id'];
        $stmt = $conn->prepare("SELECT restaurant_id FROM foodpanda_menu WHERE id=?");
        $stmt->bind_param("i", $menu_id);
        $stmt->execute();
        $stmt->bind_result($restaurant_id);
        $stmt->fetch();
        $stmt->close();

        $user_id = $_SESSION['user_id'];
        $delivery_lat = (float)$_POST['delivery_lat'];
        $delivery_lng = (float)$_POST['delivery_lng'];
        
        // Assign random driver
        $driver_name = $drivers[array_rand($drivers)];

        // Initial ETA = 15 minutes
        $estimated_time = 15;
        $status = 'pending';

        $stmt = $conn->prepare("INSERT INTO foodpanda_orders (user_id, restaurant_id, status, delivery_lat, delivery_lng, driver_name, estimated_time, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("iissdsi", $user_id, $restaurant_id, $status, $delivery_lat, $delivery_lng, $driver_name, $estimated_time);
        $stmt->execute();
        $order_id = $stmt->insert_id;
        $stmt->close();

        // Insert order items
        $stmt = $conn->prepare("INSERT INTO foodpanda_order_items (order_id, menu_id, quantity) VALUES (?, ?, ?)");
        foreach($_SESSION['cart'] as $item) {
            $stmt->bind_param("iii", $order_id, $item['menu_id'], $item['quantity']);
            $stmt->execute();
        }
        $stmt->close();

        $_SESSION['cart'] = [];
        header("Location: order.php?order_id=".$order_id);
        exit();
    } else {
        $message = "Your cart is empty!";
    }
}

$order_id = $_GET['order_id'] ?? '';
$order_info = null;

if(!empty($order_id)) {
    $order_id = (int)$order_id;
    $stmt = $conn->prepare("SELECT o.status, o.delivery_lat, o.delivery_lng, o.driver_name, o.estimated_time, o.created_at, r.latitude AS rest_lat, r.longitude AS rest_lng FROM foodpanda_orders o JOIN foodpanda_restaurants r ON o.restaurant_id = r.id WHERE o.id=? AND o.user_id=?");
    $stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
    $stmt->execute();
    $res = $stmt->get_result();
    $order_info = $res->fetch_assoc();
    $stmt->close();
}
?>
<style>
/* General Body Styles */
body {
    font-family: 'Roboto', Arial, sans-serif;
    background-color: #fafafa;
    color: #333;
    margin: 0;
    padding: 0;
    line-height: 1.6;
}

/* Header Section */
h1 {
    background-color: #e91e63;
    color: white;
    text-align: center;
    padding: 20px;
    margin: 0;
    font-size: 24px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

/* Links */
a {
    color: #e91e63;
    text-decoration: none;
    margin: 0 10px;
    font-weight: bold;
}

a:hover {
    text-decoration: underline;
    color: #c2185b;
}

/* Map Container */
#map, #selectMap {
    width: 100%;
    height: 300px;
    margin-top: 20px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

/* Tables */
table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

table th, table td {
    border: 1px solid #ddd;
    padding: 15px;
    text-align: center;
    font-size: 14px;
}

table th {
    background-color: #e91e63;
    color: white;
    font-weight: bold;
    text-transform: uppercase;
}

table tr:hover {
    background-color: #fce4ec;
}

/* Buttons */
button {
    display: block;
    width: 100%;
    background-color: #e91e63;
    color: white;
    border: none;
    padding: 12px;
    font-size: 16px;
    border-radius: 8px;
    cursor: pointer;
    margin-top: 15px;
    transition: background-color 0.3s ease;
    text-transform: uppercase;
    font-weight: bold;
}

button:disabled {
    background-color: #ccc;
    cursor: not-allowed;
}

button:hover:not(:disabled) {
    background-color: #d81b60;
}

/* Cards */
.card {
    background-color: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 6px 10px rgba(0, 0, 0, 0.1);
    margin: 20px auto;
    max-width: 600px;
    animation: fadeIn 0.5s ease-in-out;
}

.card-header {
    text-align: center;
    border-bottom: 1px solid #ddd;
    padding-bottom: 10px;
    margin-bottom: 20px;
    font-size: 20px;
    font-weight: bold;
    color: #e91e63;
}

.card p {
    margin: 10px 0;
    font-size: 16px;
    line-height: 1.5;
}

.card strong {
    color: #e91e63;
}

/* Animations */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
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
    box-shadow: 0 -4px 8px rgba(0, 0, 0, 0.1);
}

<style>
/* Navigation Links */
a {
    display: inline-block;
    text-decoration: none;
    color: white;
    background-color: #e91e63;
    padding: 10px 20px;
    margin: 5px;
    font-size: 16px;
    font-weight: bold;
    border-radius: 25px;
    transition: background-color 0.3s ease, transform 0.2s ease;
    text-align: center;
}

a:hover {
    background-color: #d81b60;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

a:active {
    transform: translateY(0);
    box-shadow: none;
}

/* Navigation Container */
.nav-container {
    text-align: center;
    margin: 20px 0;
}
</style>

</style>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Your Orders</title>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
body { font-family: Arial; }
#map, #selectMap { width: 100%; height: 300px; margin-top: 20px; }
</style>
</head>
<body>
<h1>Your Orders</h1>
<div class="nav-container">
    <a href="index.php">Back to Home</a>
    <a href="history.php">Order History</a>
</div>


<?php if($order_info): ?>
    <h2>Order #<?php echo $order_id; ?></h2>
    <p>Status: <span id="orderStatus"><?php echo htmlspecialchars($order_info['status']); ?></span></p>
    <p>Driver: <strong id="driverName"><?php echo htmlspecialchars($order_info['driver_name']); ?></strong></p>
    <p>ETA: <strong id="eta"><?php echo (int)$order_info['estimated_time']; ?> min</strong></p>
    <div id="map"></div>
    <script>
    var map = L.map('map').setView([<?php echo $order_info['rest_lat']; ?>, <?php echo $order_info['rest_lng']; ?>], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom:19
    }).addTo(map);

    var restaurantLatLng = [<?php echo $order_info['rest_lat']; ?>, <?php echo $order_info['rest_lng']; ?>];
    var deliveryLatLng = [<?php echo $order_info['delivery_lat']; ?>, <?php echo $order_info['delivery_lng']; ?>];

    var restaurantMarker = L.marker(restaurantLatLng).bindPopup('Restaurant').addTo(map);
    var deliveryMarker = L.marker(deliveryLatLng).bindPopup('Delivery Location').addTo(map);
    var driverMarker = L.marker(restaurantLatLng).bindPopup('Driver').addTo(map);

    function updateTracking() {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'track.php?order_id=<?php echo $order_id; ?>', true);
        xhr.onload = function() {
            if(xhr.status === 200) {
                var data = JSON.parse(xhr.responseText);
                if(!data.error) {
                    document.getElementById('orderStatus').innerText = data.status;
                    document.getElementById('driverName').innerText = data.driver_name;
                    document.getElementById('eta').innerText = data.eta + " min";

                    if(data.lat && data.lng) {
                        driverMarker.setLatLng([data.lat, data.lng]).bindPopup('Driver');
                    }

                    if(data.status === 'delivered') {
                        clearInterval(intervalId);
                    }
                }
            }
        };
        xhr.send();
    }

    var intervalId = setInterval(updateTracking, 5000);
    updateTracking();

    setInterval(function() {
        fetch('track.php?order_id=<?php echo $order_id; ?>')
        .then(response => response.json())
        .then(data => {
            if(!data.error) {
                document.getElementById('orderStatus').innerText = data.status;
                // Update map or ETA if needed
            }
        });
    }, 5000); // every 5 seconds
    </script>
<?php else: ?>
    <h2>Your Cart</h2>
    <?php if(!empty($_SESSION['cart'])): ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <tr><th>Menu ID</th><th>Quantity</th></tr>
            <?php foreach($_SESSION['cart'] as $c): ?>
            <tr>
                <td><?php echo $c['menu_id']; ?></td>
                <td><?php echo $c['quantity']; ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <p>Select your delivery location in Karachi by clicking on the map:</p>
        <div id="selectMap"></div>
        <form method="post">
            <input type="hidden" name="delivery_lat" id="delivery_lat">
            <input type="hidden" name="delivery_lng" id="delivery_lng">
            <button type="submit" name="place_order" id="placeOrderBtn" disabled>Place Order</button>
        </form>
        <div><?php echo htmlspecialchars($message); ?></div>

        <script>
        var selectMap = L.map('selectMap').setView([24.8607, 67.0011], 13); // Karachi
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
            maxZoom:19
        }).addTo(selectMap);

        var deliverySelectMarker = null;
        selectMap.on('click', function(e) {
            if(deliverySelectMarker) {
                selectMap.removeLayer(deliverySelectMarker);
            }
            deliverySelectMarker = L.marker(e.latlng).addTo(selectMap).bindPopup('Delivery Location').openPopup();
            document.getElementById('delivery_lat').value = e.latlng.lat;
            document.getElementById('delivery_lng').value = e.latlng.lng;
            document.getElementById('placeOrderBtn').disabled = false;
        });
        </script>
    <?php else: ?>
        <p>Cart is empty.</p>
        <div><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
<?php endif; ?>
</body>
</html>
