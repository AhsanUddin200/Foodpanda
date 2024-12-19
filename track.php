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

        if (empty($delivery_lat) || empty($delivery_lng)) {
            $message = "Please select a delivery location on the map.";
        } else {
            // Assign random driver
            $driver_name = $drivers[array_rand($drivers)];

            // Initial ETA = 15 minutes
            $estimated_time = 15;
            $status = 'pending';

            $stmt = $conn->prepare("
                INSERT INTO foodpanda_orders (user_id, restaurant_id, status, delivery_lat, delivery_lng, driver_name, estimated_time, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
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
        }
    } else {
        $message = "Your cart is empty!";
    }
}

$order_id = $_GET['order_id'] ?? '';
$order_info = null;

if(!empty($order_id)) {
    $order_id = (int)$order_id;
    $stmt = $conn->prepare("
        SELECT o.status, o.delivery_lat, o.delivery_lng, o.driver_name, o.estimated_time, o.created_at,
               r.latitude AS rest_lat, r.longitude AS rest_lng
        FROM foodpanda_orders o
        JOIN foodpanda_restaurants r ON o.restaurant_id = r.id
        WHERE o.id=? AND o.user_id=?
    ");
    $stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
    $stmt->execute();
    $res = $stmt->get_result();
    $order_info = $res->fetch_assoc();
    $stmt->close();

    // Dynamically update status based on time elapsed since order
    $orderTime = strtotime($order_info['created_at']);
    $currentTime = time();
    $timeDiff = $currentTime - $orderTime;

    if ($timeDiff < 120) {
        $status = 'pending';
    } elseif ($timeDiff < 300) {
        $status = 'confirmed';
    } elseif ($timeDiff < 600) {
        $status = 'preparing';
    } elseif ($timeDiff < 1200) {
        $status = 'in_transit';
    } else {
        $status = 'delivered';
    }

    if ($status !== $order_info['status']) {
        $stmt = $conn->prepare("UPDATE foodpanda_orders SET status=? WHERE id=?");
        $stmt->bind_param("si", $status, $order_id);
        $stmt->execute();
        $stmt->close();
        $order_info['status'] = $status;
    }
}
?>
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
.status-badge {
    display: inline-block;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: bold;
    text-transform: uppercase;
    font-size: 14px;
}
.status-badge.pending { background-color: #FFF3CD; color: #856404; }
.status-badge.confirmed { background-color: #CCE5FF; color: #004085; }
.status-badge.preparing { background-color: #D4EDDA; color: #155724; }
.status-badge.in_transit { background-color: #E2E3E5; color: #383D41; animation: pulse 2s infinite; }
.status-badge.delivered { background-color: #D1ECF1; color: #0C5460; }
.driver-marker { font-size: 24px; text-align: center; line-height: 30px; }
.delivered-message {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    text-align: center;
    z-index: 1000;
}
@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}
</style>
</head>
<body>
<h1>Your Orders</h1>
<a href="index.php">Back to Home</a> | <a href="history.php">Order History</a>

<?php if($order_info): ?>
    <h2>Order #<?php echo $order_id; ?></h2>
    <p>Status: <span id="orderStatus" class="status-badge <?php echo htmlspecialchars($order_info['status']); ?>"><?php echo htmlspecialchars($order_info['status']); ?></span></p>
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
                        showDeliveredMessage();
                    }
                }
            }
        };
        xhr.send();
    }

    function showDeliveredMessage() {
        const messageDiv = document.createElement('div');
        messageDiv.className = 'delivered-message';
        messageDiv.innerHTML = `
            <h3>Order Delivered! 🎉</h3>
            <p>Thank you for ordering with us.</p>
            <button onclick="location.href='history.php'">View Order History</button>
        `;
        document.body.appendChild(messageDiv);
    }

    var intervalId = setInterval(updateTracking, 5000);
    updateTracking();
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

        document.getElementById('placeOrderBtn').addEventListener('click', function(event) {
            if (!document.getElementById('delivery_lat').value || !document.getElementById('delivery_lng').value) {
                event.preventDefault();
                alert("Please select a delivery location.");
            }
        });
        </script>
    <?php else: ?>
        <p>Cart is empty.</p>
        <div><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
<?php endif; ?>
</body>
</html>
