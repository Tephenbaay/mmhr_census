<?php
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $email = $_POST["email"];
    $password = password_hash($_POST["password"], PASSWORD_BCRYPT);

    // Check if email already exists
    $check_stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        echo "<script>
                alert('Account already created!');
                window.location.href = 'index.php';
              </script>";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $email, $password);

        if ($stmt->execute()) {
            echo "<script>
                    alert('Registration successful! Redirecting to login...');
                    window.location.href = 'index.php';
                  </script>";
        } else {
            echo "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
        }
        $stmt->close();
    }

    $check_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="icon" href="templates/download-removebg-preview.png">
    <link rel="stylesheet" href="sige/register.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex justify-content-center align-items-center vh-100" style="background-image: url('sige/bgg.png'); background-size: cover; background-repeat: no-repeat;">

<div class="p-10 rounded-lg shadow-md w-full sm:w-96" style="background-color: #afc9a2;">
    <div class="flex items-center space-x-2">
        <img src="sige/download-removebg-preview.png" alt="Uniwork Logo" class="h-20 w-20 object-contain">
        <h2 class="text-2xl font-bold text-left text-stone-950 mb-1" style="font-family: 'Libre Baskerville', Georgia, serif;">Bicutan Medical Center, Inc.</h2>
    </div>
    <form method="POST" class="space-y-6">
        <div>
            <label for="username" class="block text-sm text-black font-bold mt-8">Username:</label>
            <input type="text" name="username" id="username" class="mt-3 p-1 block w-full border rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
        </div>
        <div>
            <label for="email" class="block text-sm text-black font-bold">Email:</label>
            <input type="email" name="email" id="email" class="mt-3 p-1 block w-full border rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
        </div>
        <div>
            <label for="password" class="block text-sm text-black font-bold">Password:</label>
            <input type="password" name="password" id="password" class="mt-3 p-1 block w-full border rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
        </div>

        <button type="submit" class="w-64 bg-blue-50 text-black font-bold content-center py-2 rounded-xl hover:bg-green-600 transition duration-300" style="margin-top: 10%; margin-left: 30px">Register</button>

        <p class="mt-3 text-center">
            <span>Already have an account? </span><a href="index.php" class="login-link">Login here</a>
        </p>
        <div class="footer">
            <small>&copy; Bicutan Medical Center Inc. All rights reserved.</small>
        </div>
    </form>
</div>

<!-- Bootstrap JS and dependencies -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>

</body>
</html>
