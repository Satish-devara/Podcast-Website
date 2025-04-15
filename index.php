<?php
session_start();
include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $username;
            header("Location: dashboard.php");
            exit;
        }
    }
    $error = "Invalid username or password!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-grey flex items-center justify-center h-screen"  style="background-image: url('bg4.jpeg'); background-repeat: no-repeat; background-size: cover; background-position: center; opacity: 0.9;">
    <form method="POST" class="bg-white p-6 rounded-xl shadow-md w-96 space-y-4">
        <h2 class="text-2xl font-bold">Login</h2>
        <?php if (isset($error)) echo "<p class='text-red-500'>$error</p>"; ?>
        <input name="username" placeholder="Username" class="input" required />
        <input name="password" type="password" placeholder="Password" class="input" required />
        <button class="btn w-full">Login</button>
        <p class="text-sm text-center mt-4">No account? <a class="text-blue-500" href="signup.php">Sign up</a></p>
    </form>
</body>
</html>
