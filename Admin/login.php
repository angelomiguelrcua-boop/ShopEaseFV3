<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'ecommerce_db');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = "";

// ✅ Handle Login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["login"])) {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row["password"])) {
            session_regenerate_id(true);
            $_SESSION["admin"] = $username;
            $_SESSION["admin_id"] = $row["id"];
            $_SESSION["role"] = $row["role"];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "❌ Invalid password!";
        }
    } else {
        $error = "⚠️ User not found!";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShopEase | Admin Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        /* Reset */
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Poppins', sans-serif; }

        /* Background */
        body { 
    height: 100vh; 
    display: flex; 
    justify-content: center; 
    align-items: center; 
    background: linear-gradient(135deg, #087500, #009176); 
    position: relative;
    overflow: hidden;
}

/* Faded cart icon in background */
body::before {
    content: "\f07a"; /* shopping cart icon from FontAwesome */
    font-family: "Font Awesome 6 Free";
    font-weight: 900;
    font-size: 350px;
    color: rgba(255, 255, 255, 0.07);
    position: absolute;
    bottom: -50px;
    right: -30px;
    z-index: 0;
}

.container {
    position: relative;
    z-index: 1; /* keeps the login box above overlay */
}


        /* Card */
        .container { 
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(10px);
            padding: 40px 30px; 
            border-radius: 20px; 
            width: 360px; 
            box-shadow: 0 8px 25px rgba(0,0,0,0.2); 
            text-align: center;
            animation: fadeIn 0.8s ease-in-out;
        }

        /* Logo / Title */
        .logo { font-size: 30px; font-weight: bold; color: #087500; margin-bottom: 10px; }
        .subtitle { font-size: 14px; color: #555; margin-bottom: 20px; }

        /* Input Group */
        .input-group {
            position: relative;
            margin-bottom: 20px;
        }
        .input-group i {
            position: absolute;
            top: 50%;
            left: 12px;
            transform: translateY(-50%);
            color: #888;
        }
     .input-group input {
    width: 100%;
    padding: 12px 45px 12px 40px;  /* 👈 added 45px on the right */
    border: 1px solid #ccc;
    border-radius: 10px;
    outline: none;
    font-size: 14px;
    transition: 0.3s;
}
        .input-group input:focus {
            border-color: #087500;
            box-shadow: 0 0 5px rgba(8,117,0,0.3);
        }

        /* Password Eye */
        .toggle-password {
    position: absolute;
    top: 50%;
    right: -220px;   /* 👈 flush near the edge of the input */
    transform: translateY(-50%);
    cursor: pointer;
    color: #666;
    font-size: 18px; /* make it slightly bigger for balance */
}

        /* Button */
        button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 10px;
            background: linear-gradient(135deg, #087500, #009176);
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }
        button:hover {
            background: linear-gradient(135deg, #065b00, #00785d);
        }

        /* Error Message */
        .error {
            background: #ffe0e0;
            color: #d9534f;
            padding: 8px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 15px;
            animation: shake 0.3s ease-in-out;
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes shake {
            0% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            50% { transform: translateX(5px); }
            75% { transform: translateX(-5px); }
            100% { transform: translateX(0); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">ShopEase</div>
        <div class="subtitle">Admin Panel Login</div>

        <?php if (!empty($error)) { echo "<div class='error'>$error</div>"; } ?>

        <form method="post" action="">
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="username" placeholder="Username" required>
            </div>
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" id="password" name="password" placeholder="Password" required>
                <i class="fas fa-eye toggle-password" onclick="togglePassword()"></i>
            </div>
            <button type="submit" name="login"><i class="fas fa-sign-in-alt"></i> Login</button>
        </form>
    </div>

    <script>
        function togglePassword() {
            const pass = document.getElementById("password");
            pass.type = pass.type === "password" ? "text" : "password";
        }
    </script>
</body>
</html>
