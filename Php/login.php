<?php
ob_start(); 
session_start();
require_once 'connection.php';

// Initialize variables to store messages
$message = "";
$messageType = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action']; // either 'login' or 'signup'
    
    // Common fields
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($action === 'signup') {
        $full_name = $_POST['full_name'] ?? '';
        
        // Validate that all fields are filled
        if (empty($full_name) || empty($email) || empty($password)) {
            $message = "All fields are required";
            $messageType = "error";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Check if email already exists
            $check = $con->prepare("SELECT * FROM users WHERE email = ?");
            $check->bind_param("s", $email);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $message = "Email already registered.";
                $messageType = "error";
            } else {
                $stmt = $con->prepare("INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $full_name, $email, $hashedPassword);
                if ($stmt->execute()) {
                    $message = "Signup successful. Please login.";
                    $messageType = "success";
                } else {
                    $message = "Signup failed. Please try again.";
                    $messageType = "error";
                }
            }
        }
    } else if ($action === 'login') {
        // Validate that fields are filled
        if (empty($email) || empty($password)) {
            $message = "All fields are required";
            $messageType = "error";
        } else {
            $stmt = $con->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($user = $result->fetch_assoc()) {
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user'] = $user['full_name'];
                    $_SESSION['user_id'] = $user['id'];
                    header("Location: index.php");
                    exit;
                } else {
                    $message = "Invalid password.";
                    $messageType = "error";
                }
            } else {
                $message = "User not found. Please sign up first.";
                $messageType = "error";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Travel Login & Sign Up</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        .close-button {
            position: absolute;
            top: 10px;
            right: 10px;
            cursor: pointer;
            font-size: 1.5em;
            color: #888;
        }
        .close-button:hover {
            color: #333;
        }
        .message {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .error {
            background-color: #FECACA;
            color: #991B1B;
        }
        .success {
            background-color: #D1FAE5;
            color: #065F46;
        }
    </style>
    <script defer src="dark-mode.js"></script>
</head>
<body class="bg-gray-100 dark:bg-gray-900 flex items-center justify-center min-h-screen">
    <div class="w-full max-w-4xl bg-white dark:bg-gray-800 rounded-lg shadow-lg flex overflow-hidden relative">
        <div class="hidden md:flex w-1/2 bg-gray-200 dark:bg-gray-700 relative">
            <div class="absolute inset-0 flex items-center justify-center">
                <h2 class="text-white text-2xl font-semibold text-center px-6" style="font-family: 'Playfair Display', serif;">
                    Discover New Places 🌍 <br> Travel Beyond Limits!
                </h2>
            </div>
            <img src="images/log1.jpg" id="carousel-img" class="w-full h-full object-cover opacity-70">
        </div>

        <div class="w-full md:w-1/2 p-8 text-center">
            <a href="index.php" class="close-button">&times;</a>
            <h2 class="text-3xl font-semibold text-gray-900 dark:text-white" style="font-family: 'Playfair Display', serif;" id="form-title">
                Login
            </h2>
            <div class="w-16 border-t-2 border-orange-500 mx-auto my-4"></div>
            
            <?php if (!empty($message)): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form id="auth-form" method="POST" action="login.php">
                <input type="hidden" name="action" id="form-action" value="login">
                
                <div id="full-name-field" class="hidden">
                    <input type="text" name="full_name" placeholder="Full Name" class="w-full p-3 mt-3 bg-gray-200 dark:bg-gray-700 rounded-lg text-gray-900 dark:text-white focus:outline-none">
                </div>
                
                <input type="email" name="email" placeholder="Email" required class="w-full p-3 mt-3 bg-gray-200 dark:bg-gray-700 rounded-lg text-gray-900 dark:text-white focus:outline-none">
                <input type="password" name="password" placeholder="Password" required class="w-full p-3 mt-3 bg-gray-200 dark:bg-gray-700 rounded-lg text-gray-900 dark:text-white focus:outline-none">
                
                <button type="submit" class="mt-6 w-full bg-gray-900 dark:bg-gray-600 text-white py-3 rounded-lg font-semibold hover:bg-gray-700 dark:hover:bg-gray-500 transition">
                    Login
                </button>
            </form>

            <p class="text-gray-600 dark:text-gray-300 mt-4">
                <span id="toggle-text">Don't have an account?</span> 
                <button onclick="toggleForm()" class="text-orange-500 font-semibold hover:underline" id="toggle-button">
                    Sign up
                </button>
            </p>
        </div>
    </div>
    
    <script>
        let isLogin = true;
        let images = ["images/log.jpg", "images/log2.jpg", "images/paris2.jpg"];
        let currentIndex = 0;
        
        function toggleForm() {
            isLogin = !isLogin;
            document.getElementById("form-title").textContent = isLogin ? "Login" : "Sign Up";
            document.getElementById("form-action").value = isLogin ? "login" : "signup";
            document.getElementById("toggle-text").textContent = isLogin ? "Don't have an account?" : "Already have an account?";
            document.getElementById("toggle-button").textContent = isLogin ? "Sign up" : "Log in";
            
            // Toggle full name field visibility
            const fullNameField = document.getElementById("full-name-field");
            if (isLogin) {
                fullNameField.classList.add("hidden");
                fullNameField.querySelector("input").removeAttribute("required");
            } else {
                fullNameField.classList.remove("hidden");
                fullNameField.querySelector("input").setAttribute("required", "");
            }
            
            // Update submit button text
            const submitButton = document.querySelector("button[type=submit]");
            submitButton.textContent = isLogin ? "Login" : "Sign Up";
        }
        
        function changeImage() {
            currentIndex = (currentIndex + 1) % images.length;
            document.getElementById("carousel-img").src = images[currentIndex];
        }
        
        setInterval(changeImage, 3000);
        
        // If there's a theme toggle button
        const themeToggle = document.getElementById("theme-toggle");
        if (themeToggle) {
            themeToggle.addEventListener("click", function() {
                document.documentElement.classList.toggle("dark");
            });
        }
    </script>
</body>
</html>