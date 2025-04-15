<?php
include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fileName = $_FILES['profile_pic']['name'];
    $target = "uploads/" . basename($fileName);
    move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target);

    $stmt = $conn->prepare("INSERT INTO users (name, email, dob, gender, username, password, profile_pic) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $_POST['name'], $_POST['email'], $_POST['dob'], $_POST['gender'], $_POST['username'], password_hash($_POST['password'], PASSWORD_DEFAULT), $target);
    $stmt->execute();
    header("Location: index.php");
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Signup</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen" style="background-image: url('bg1.jpeg'); background-repeat: no-repeat; background-size: cover; background-position: center; opacity: 0.9;">
    <form method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded-xl shadow-md w-96 space-y-4">
        <h2 class="text-2xl font-bold">Sign Up</h2>
        <input name="name" placeholder="Name" class="input" required />
        <input name="email" type="email" placeholder="Email" class="input" required />
        <input name="dob" type="date" class="input" required />
        <select name="gender" class="input" required>
            <option disabled selected>Gender</option>
            <option>Male</option><option>Female</option><option>Other</option>
        </select>
        <input name="username" placeholder="Username" class="input" required />
        <input name="password" type="password" placeholder="Password" class="input" required />
        <input type="file" name="profile_pic" accept="image/*" class="input" required />
        <button class="btn w-full">Sign Up</button>
        <p class="text-sm text-center mt-4">Already have an account? <a class="text-blue-500" href="index.php">Login</a></p>
    </form>
</body>
</html>
