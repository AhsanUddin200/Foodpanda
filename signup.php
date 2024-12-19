<?php
include 'db.php';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if(!empty($username) && !empty($password)) {
        // Hash password
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $conn->prepare("INSERT INTO foodpanda_users (username, password) VALUES (?, ?)");
        $stmt->bind_param("ss", $username, $hash);
        if ($stmt->execute()) {
            $message = "Sign up successful! <a href='login.php'>Login here</a>";
        } else {
            $message = "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $message = "Please fill all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Sign Up</title>
<style>
    body { font-family: Arial; background: #f4f4f4; }
    .container { width:300px; margin:50px auto; background:#fff; padding:20px; border-radius:5px; }
    input[type=text], input[type=password] { width:100%; padding:10px; margin:5px 0; }
    button { width:100%; padding:10px; background:green; color:#fff; border:none; cursor:pointer; }
    .message { margin-top:10px; color:red; }
</style>
</head>
<body>
<div class="container">
    <h2>Create an Account</h2>
    <form method="post">
        <input type="text" name="username" placeholder="Username" required/>
        <input type="password" name="password" placeholder="Password" required/>
        <button type="submit">Sign Up</button>
    </form>
    <div class="message"><?php echo $message; ?></div>
</div>
<script>
// Add any JavaScript validations if you want
</script>
</body>
</html>
