<?php
include 'db.php';
session_start();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    if(!empty($username) && !empty($password)) {
        $stmt = $conn->prepare("SELECT id, password FROM foodpanda_users WHERE username=?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->bind_result($user_id, $hash);
        if($stmt->fetch()) {
            if(password_verify($password, $hash)) {
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                header("Location: index.php");
                exit();
            } else {
                $message = "Invalid credentials.";
            }
        } else {
            $message = "Invalid credentials.";
        }
        $stmt->close();
    } else {
        $message = "Please enter username and password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Login</title>
<style>
    body { font-family: Arial; background: #f4f4f4; }
    .container { width:300px; margin:50px auto; background:#fff; padding:20px; border-radius:5px; }
    input[type=text], input[type=password] { width:100%; padding:10px; margin:5px 0; }
    button { width:100%; padding:10px; background:blue; color:#fff; border:none; cursor:pointer; }
    .message { margin-top:10px; color:red; }
</style>
</head>
<body>
<div class="container">
    <h2>Login</h2>
    <form method="post">
        <input type="text" name="username" placeholder="Username" required/>
        <input type="password" name="password" placeholder="Password" required/>
        <button type="submit">Login</button>
    </form>
    <div class="message"><?php echo $message; ?></div>
</div>
<script>
// JS code if needed
</script>
</body>
</html>
