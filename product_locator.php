<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'ecommerce_db');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>

    <title>ShopEase - Supermarket Product Locator</title>
    <style>
body { 
    font-family: Arial, sans-serif; 
    background: url("ASSETS/BG3.jpg") no-repeat center center fixed; 
    background-size: cover; 
    margin: 0; 
    padding: 0;
    overflow: hidden;
    display: block;
    position: relative; 
    overflow-y: auto; /* Enables scrolling */

    
    /* ✅ Dull the background using an overlay */
    position: relative;
}

body::before {
    content: ""; 
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%; 
    background: url("ASSETS/BG3.jpg") no-repeat center center fixed; 
    background-size: cover;
    filter: blur(4px); 
    background-color: rgba(0, 0, 0, 0.25); /* ✅ 25% black overlay to dull the background */
    z-index: -1; /* ✅ Make sure it's behind the content */
}




/* Navbar Container */
.navbar { 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    background-color: #f8f9fa; 
    padding: 12px 40px; 
    border-bottom: 1px solid #ddd;
    height: 90px; /* ✅ Fixed height to avoid resizing */
    box-sizing: border-box; /* ✅ Ensure padding doesn't increase the height */
}

/* Navigation Links Container */
.nav { 
    display: flex;
    gap: 20px; /* Space between links */
}

/* Navigation Links */
.nav a { 
    font-family: 'Arial', sans-serif; 
    font-size: 17px; 
    font-weight: 600; 
    color: #2c3e50; 
    text-decoration: none; 
    padding: 10px 20px; 
    border-radius: 6px; 
    background-color: #e9ecef; 
    display: flex;
    align-items: center;
    gap: 10px; /* Space between icon and text */
    transition: background-color 0.3s ease, color 0.3s ease, box-shadow 0.3s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Icons Inside the Links */
.nav a i { 
    font-size: 18px; 
    color: #2c3e50;
}

/* Hover Effect */
.nav a:hover { 
    background-color: #3498db; 
    color: #fff; 
    box-shadow: 0 4px 8px rgba(52, 152, 219, 0.3);
}

.nav a:hover i {
    color: #fff;
}

/* Active Link Styling */
.nav a.active {
    /*background-color: #2c3e50;*/
    color: #2c3e50;
}

.nav a.active i {
    color: #2c3e50;
}


.navbar h2 {
    display: flex;
    align-items: center;
    gap: 10px; /* ✅ Add space between logo and text */
    color: black;
    font-size: 24px;
}

.logo {
    height: 65px; /* ✅ Adjust logo size */
    width: auto;
    object-fit: contain; /* ✅ Keep image proportion intact */
}



        .container { 
            max-width: 1000px; 
            margin: auto; 
            padding: 30px; 
        }
        h1 { 
    color: white; 
    text-align: center; 
    margin-bottom: 20px; 
    font-size: 48px; /* ✅ Mas malaking font size */
    font-weight: 700; /* ✅ Mas bold na font weight */
    text-shadow: 3px 3px 6px rgba(0, 0, 0, 0.7); /* ✅ Mas malinaw na shadow */
    letter-spacing: 1.5px; /* ✅ Bahagyang spacing para mas readable */
}


        .input-group { 
            display: flex; 
            gap: 10px; 
            justify-content: center; 
            margin-bottom: 20px; 
        }
        input[type="text"] { 
            padding: 20px; 
            width: 70%; 
            border-radius: 5px; 
            border: 1px solid #ddd; 
            font-size: 1rem; 
        }
        button { 
            background-color: #28a745; 
            border: none; 
            padding: 10px 20px; 
            border-radius: 5px; 
            color: white; 
            cursor: pointer; 
        }
        button:hover { 
            background-color: #218838; 
        }
        .product { 
            padding: 10px; 
            order: 1px solid #ddd; 
            border-radius: 5px; 
            margin: 5px; 
            background: white; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        }
        .product button { 
            margin-left: 10px; 
        }
        .cart, .tutorial-container { 
            background-color: white; 
            padding: 20px; 
            border-radius: 3px; 
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); 
           
        }


       .cart-items {
    width: 100%;
}

.item {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr; /* Name | Price | Quantity | Remove */
    align-items: center;
    padding: 10px;
    border-bottom: 1px soli;
    text-align: center;
}

.item .name {
    text-align: left;
    padding-left: 10px;
}

.item .price {
    text-align: center;
}

.item .quantity-controls {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 5px;
}

.cart-items {
    width: 100%;
}

.item {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr; /* Name | Price | Quantity | Remove */
    align-items: center;
    padding: 10px;
    /*border-bottom: 1px solid #ddd;*/
    
}

.item .name {
    text-align: left;
    padding-left: 10px;
}

.item .price {
    text-align: center;
}

.item .quantity-controls {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 5px;
}

.quantity-controls button {
    background-color: #007bff;
    border: none;
    color: white;
    padding: 5px 10px;
    border-radius: 5px;
    cursor: pointer;
}

.quantity-controls button:disabled {
    background-color: #ddd;
    color: #888;
    cursor: not-allowed;
}

.item .remove {
    text-align: center;
}

.total {
    font-weight: bold;
    margin-top: 10px;
    text-align: center;
}


      
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .error-message { color: red; text-align: center; font-weight: bold; margin-top: 20px; }
        /* Tutorial Styles */
        
        .aisle { position: absolute; padding: 10px; background: #007bff; color: white; border-radius: 5px; }
        .aisle.highlight { background: #ff5722; animation: bounce 1s infinite; }
        

        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }


        /* Add to Cart Button Animation */
        .cart-button {
    position: relative;
    padding: 10px;
    width: 150px;
    border: 0;
    border-radius: 10px;
    background-color: #28a745;
    color: white;
    cursor: pointer;
    overflow-y: auto; /* Enables scrolling */
    transition: 0.3s ease-in-out;
    margin-left: 10px;
}

.cart-button .fa-shopping-cart, .cart-button .fa-box {
    position: absolute;
    font-size: 1.5em;
    top: 50%;
    transform: translateY(-50%);
}

.cart-button .fa-shopping-cart { left: -10%; z-index: 2; }
.cart-button .fa-box { top: -20%; left: 52%; z-index: 3; }

.cart-button span {
    position: absolute;
    z-index: 3;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
    font-size: 1rem;
}

.cart-button span.add-to-cart { opacity: 1; }
.cart-button span.added { opacity: 0; }

/* Animation Effects */
.cart-button.clicked .fa-shopping-cart {
    animation: cart 1.5s ease-in-out forwards;
}

.cart-button.clicked .fa-box {
    animation: box 1.5s ease-in-out forwards;
}

.cart-button.clicked span.add-to-cart {
    animation: txt1 1.5s ease-in-out forwards;
}

.cart-button.clicked span.added {
    animation: txt2 1.5s ease-in-out forwards;
}

@keyframes cart {
    0% { left: -10%; }
    40%, 60% { left: 50%; }
    100% { left: 110%; }
}

@keyframes box {
    0%, 40% { top: -20%; }
    60% { top: 50%; left: 52%; }
    100% { top: 50%; left: 112%; }
}

@keyframes txt1 { 0% { opacity: 1; } 20%, 100% { opacity: 0; } }
@keyframes txt2 { 0%, 80% { opacity: 0; } 100% { opacity: 1; } }

.cart-button {
            position: relative;
            padding: 10px;
            width: 200px;
            height: 60px;
            border: 0;
            border-radius: 10px;
            background-color:rgb(140, 216, 140);
            outline: none;
            cursor: pointer;
            color: #fff;
            transition: .3s ease-in-out;
            overflow: hidden;
        }

        .cart-button:hover {
            background-color:rgb(50, 170, 56);
        }

        .cart-button:active {
            transform: scale(.9);
        }

        .cart-button .fa-shopping-cart {
            position: absolute;
            z-index: 2;
            top: 50%;
            left: -10%;
            font-size: 2em;
            transform: translate(-50%, -50%);
        }

        .cart-button .fa-box {
            position: absolute;
            z-index: 3;
            top: -20%;
            left: 52%;
            font-size: 1.2em;
            transform: translate(-50%, -50%);
        }

        .cart-button span {
            position: absolute;
            z-index: 3;
            left: 50%;
            top: 50%;
            font-size: 1.2em;
            color: #fff;
            transform: translate(-50%, -50%);
        }

        .cart-button span.add-to-cart {
            opacity: 1;
        }

        .cart-button span.added {
            opacity: 0;
        }

        .cart-button.clicked .fa-shopping-cart {
            animation: cart 1.5s ease-in-out forwards;
        }

        .cart-button.clicked .fa-box {
            animation: box 1.5s ease-in-out forwards;
        }

        .cart-button.clicked span.add-to-cart {
            animation: txt1 1.5s ease-in-out forwards;
        }

        .cart-button.clicked span.added {
            animation: txt2 1.5s ease-in-out forwards;
        }

        @keyframes cart {
            0% {
                left: -10%;
            }
            40%, 60% {
                left: 50%;
            }
            100% {
                left: 110%;
            }
        }

        @keyframes box {
            0%, 40% {
                top: -20%;
            }
            60% {
                top: 40%;
                left: 52%;
            }
            100% {
                top: 40%;
                left: 112%;
            }
        }

        @keyframes txt1 {
            0% {
                opacity: 1;
            }
            20%, 100% {
                opacity: 0;
            }
        }

        @keyframes txt2 {
            0%, 80% {
                opacity: 0;
            }
            100% {
                opacity: 1;
            }
        }

        .product {
    position: relative;
    transition: transform 0.3s ease;
}

.product.adding-to-cart {
    transform: translateX(300px) scale(0.5);
    opacity: 0;
}

.cart .item {
    animation: cartItemEntry 0.3s ease-out;
}

@keyframes cartItemEntry {
    from {
        opacity: 0;
        transform: scale(0.8);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

/* Add to the existing CSS */
.product {
    position: relative;
}



/* ------------------------------ */
/* Modal Styling */
/* ------------------------------ */
/* Remove list marker for specific items */
.no-marker {
    list-style-type: none;
    padding-left: 0;
}

.modal {
    display: none;
    position: fixed;
    z-index: 10;
    left: 0;
    top: 0;
    width: 100%; 
    height: 100%; 
    background-color: rgba(0, 0, 0, 0.7); 
    overflow-y: auto; /* Enable scrolling inside the container */
}

/* ------------------------------ */
/* Modal Content Styling */
/* ------------------------------ */
.modal-content {
    background-color: #fff;
    margin: 5% auto;
    padding: 30px;
    border-radius: 14px;
    width: 90%;
    max-width: 600px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
    position: relative;
}

/* ------------------------------ */
/* Close Button Styling */
/* ------------------------------ */
.close-btn {
    position: absolute;
    top: 15px;
    right: 20px;
    font-size: 28px;
    color: #777;
    cursor: pointer;
    transition: color 0.2s;
}

.close-btn:hover {
    color: #e74c3c;
}

/* ------------------------------ */
/* Header Styling */
/* ------------------------------ */
h2 {
    font-size: 24px;
    color: #2c3e50;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

/* ------------------------------ */
/* Step Container Styling */
/* ------------------------------ */
.step {
    display: flex;
    align-items: flex-start;
    gap: 20px;
    padding: 16px;

    transition: background-color 0.2s ease;
}

.step:last-child {
    border-bottom: none;
}

.step:hover {
    background-color: #f9f9f9;
}

/* ------------------------------ */
/* Icon Styling */
/* ------------------------------ */
.step i {
    font-size: 30px;
    color: #3498db;
}

/* Container to keep the tutorial within the screen */
.tutorial {
    position: fixed;
    top: 50%; 
    left: 50%; 
    transform: translate(-50%, -50%);
    width: 90%; /* Adjust to fit the screen width */
    max-width: 600px;
    max-height: 80vh; /* Prevent it from exceeding the screen height */
    overflow-y: auto; /* Enable scrolling inside the container */
    background-color: #fff;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    z-index: 999;
}

/* ------------------------------ */
/* Step Content Styling */
/* ------------------------------ */
.step-content h3 {
    font-size: 18px;
    color: #34495e;
    margin-bottom: 8px;
}

.step-content ul {
    padding-left: 20px;
}

.step-content li {
    font-size: 16px;
    color: #555;
    margin-bottom: 5px;
}

/* ------------------------------ */
/* Hover Effect */
/* ------------------------------ */
.step-content li::marker {
    color: #2ecc71;
}

/* Step icon */
.step i {
    font-size: 28px;
    color: #3498db;
}

/* Step content */
.step-content h3 {
    margin: 0;
    font-size: 18px;
    color: #2c3e50;
}

.step-content ul {
    margin: 8px 0 0;
    padding-left: 20px;
}

.step-content li {
    font-size: 16px;
    color: #555;
    margin-bottom: 5px;
}

/* Remove list marker */
.no-marker {
    list-style-type: none;
    padding-left: 0;
}

/* Close button */
.close-btn {
    position: absolute;
    top: 10px;
    right: 15px;
    font-size: 40px;
    cursor: pointer;
    color: #888;
}

.close-btn:hover {
    color: #e74c3c;
}



.keyboard {
            display: none;
            position: fixed;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            background: #ddd;
            padding: 10px;
            border-radius: 10px;
            box-shadow: 0px -2px 10px rgba(0,0,0,0.2);
            z-index: 1000;
        }
        .keyboard button {
            padding: 15px;
            margin: 5px;
            font-size: 18px;
            cursor: pointer;
            background-color: #ffffff;
            color: #333;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: background-color 0.3s, box-shadow 0.3s;
        }

        .keyboard button {
            padding: 10px;
            margin: 5px;
            font-size: 16px;
            cursor: pointer;
            background-color: #fff;
            color: #333;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: background-color 0.3s, box-shadow 0.3s;
        }

        .keyboard button:focus {
            outline: none;
        }
        .keyboard button:hover {
            background-color: #f1f1f1;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }
        .keyboard-row {
            display: flex;
            justify-content: center;
        }
        .keyboard-row button {
            width: 50px;
        }
        .keyboard-row .wide-button {
            width: 180px; padding: 10px 30px; /* Make the space bar wider */
        }
        .keyboard-row button:focus {
            outline: none;
        }
        .keyboard-row button:hover {
            background-color: #f0f0f0;
        }

        .wide-button1 {
    flex: 2; /* Makes it twice as wide as a normal button */
    padding: 10px 30px; /* Adjust padding for a better appearance */
}

.cart-link {
    position: relative; /* Ensures badge is positioned relative to this */
    display: inline-block;
    padding-right: 10px; /* Prevents overlap */
}

.badge {
    background-color: red;
    color: white;
    font-size: 12px;
    font-weight: bold;
    padding: 3px 6px;
    border-radius: 50%;
    position: absolute;
    top: -10px;
    right: -10px; /* Adjust to align properly */
    display: none; /* Hide when count is 0 */
    min-width: 15px;
    text-align: center;
}



    </style>
</head>
<body>

  <!-- Navigation -->
<div class="navbar">
    <h2> 
    <img src="ASSETS/Logo3.png" alt="Logo" class="logo"> 
        ShopEase</h2>
    <div class="nav">
        <a href="#" onclick="showTab('home')" class="active">
            <i class="fas fa-home"></i> Home
        </a>
        <a href="#" onclick="showTab('cart')" class="cart-link">
    <i class="fas fa-shopping-cart"></i> Cart 
    <span id="cart-badge" class="badge">0</span>
</a>

        <a href="#" onclick="openTutorial()">
    <i class="fas fa-book"></i> Tutorial
</a>

    </div>
</div>

    

    <div class="container">
        
        <!-- Start Screen -->
    <div id="startScreen" class="start-screen">
        <h1>Welcome to ShopEase</h1>
        <p>Tap the screen to start</p>
    </div>


    <!-- Map Modal -->
<div id="map-modal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="document.getElementById('map-modal').style.display='none'">&times;</span>
        <h2 id="map-title">Store Map</h2>
        <canvas id="store-map-canvas" width="500" height="400" style="background:#f7f7f7;border-radius:12px;width:100%;max-width:500px;display:block;margin:auto;"></canvas>
    </div>
</div>
<script>
    function drawStoreMap(aisle) {
    const canvas = document.getElementById('store-map-canvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    for (const [aisleName, pos] of Object.entries(aisles)) {
        ctx.beginPath();
        ctx.arc(pos.x, pos.y, 30, 0, 2 * Math.PI);
        ctx.fillStyle = aisleName === aisle ? '#ff5722' : '#007bff';
        ctx.fill();
        ctx.strokeStyle = '#333';
        ctx.stroke();
        ctx.fillStyle = '#fff';
        ctx.font = "bold 16px Arial";
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(aisleName, pos.x, pos.y);
    }
    ctx.beginPath();
    ctx.arc(entrance.x, entrance.y, 20, 0, 2 * Math.PI);
    ctx.fillStyle = '#2ecc40';
    ctx.fill();
    ctx.strokeStyle = '#333';
    ctx.stroke();
    ctx.fillStyle = '#fff';
    ctx.font = "bold 14px Arial";
    ctx.fillText("Entrance", entrance.x, entrance.y);
}
</script>

<style>
.start-screen {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: url("ASSETS/BG3.jpg") no-repeat center center fixed; 
    background-size: cover;
    display: flex;
    justify-content: center;
    align-items: center;
    flex-direction: column;
    z-index: 9999;
    cursor: pointer;
    text-align: center;
    animation: slideshow 25s infinite;
}

.start-screen h1 {
    font-size: 7rem;
    margin-bottom: 10px;
}

.start-screen p {
    font-size: 3rem;
    color: #fff; /* white text */
    text-shadow: 2px 2px 8px rgba(0,0,0,0.8);  
    animation: pulse 1.5s infinite; 
}

@keyframes pulse {
    0%   { opacity: 0.6; transform: scale(1); }
    50%  { opacity: 1;   transform: scale(1.05); }
    100% { opacity: 0.6; transform: scale(1); }
}

.hidden {
    display: none;
}
/* Slideshow keyframes */
@keyframes slideshow {
    25%  { background-image: url("ASSETS/BG9.jpg"); }
    50%  { background-image: url("ASSETS/BG4.jpg"); }
    75%  { background-image: url("ASSETS/BG8.jpg"); }
    100%  { background-image: url("ASSETS/BG5.jpg"); } /* loop back */
    125%  { background-image: url("ASSETS/BG6.jpg"); }
    150%  { background-image: url("ASSETS/BG7.jpg"); }
}
</style>

<script>
document.getElementById("startScreen").addEventListener("click", function() {
    document.getElementById("startScreen").style.display = "none";
    document.getElementById("home").classList.remove("hidden");
});
</script>
        <div class="container">
        <!-- Home Tab -->
        <div id="home" class="tab-content active">
            <h1>Welcome to ShopEase</h1>
            <div class="input-group">
        <input type="text" id="search" placeholder="Enter product name..." onfocus="showKeyboard()">
        <button onclick="closeKeyboard()">Search</button>
    </div>
    <div class="result-container" id="result"></div>
<button id="exitBtn" class="exit-btn">Done Shopping</button>
            </div>

            <!-- Exit Confirmation Popup -->
            <div id="exitPopup" class="popup hidden">
                <div class="popup-content">
                    <p>Are you sure you want to exit?</p>
                    <button id="yesExit">YES</button>
                    <button id="noExit">NO</button>
                </div>
            </div>

            <style>
            .start-screen {
                position: fixed;
                top: 0; left: 0; right: 0; bottom: 0;
                background: url("ASSETS/BG3.jpg") no-repeat center center fixed; 
                background-size: cover;
                display: flex;
                justify-content: center;
                align-items: center;
                flex-direction: column;
                z-index: 9999;
                cursor: pointer;
                text-align: center;
                animation: slideshow 25s infinite;
            }

            .start-screen h1 {
                font-size: 7rem;
                margin-bottom: 10px;
                color: #fff;
                text-shadow: 3px 3px 10px rgba(0,0,0,0.8);
            }

            .start-screen p {
                font-size: 3rem;
                color: #fff; /* white text */
                text-shadow: 2px 2px 8px rgba(0,0,0,0.8);  
                animation: pulse 1.5s infinite; 
            }

            /* Exit Button */
            .exit-btn {
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: crimson;
                color: #fff;
                border: none;
                padding: 12px 25px;
                font-size: 1.2rem;
                border-radius: 12px;
                cursor: pointer;
                transition: 0.3s;
            }

            .exit-btn:hover {
                background: darkred;
            }

            /* Popup */
            .popup {
                position: fixed;
                top: 0; left: 0; right: 0; bottom: 0;
                background: rgba(0,0,0,0.6);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 10000;
            }

            .popup-content {
                background: #fff;
                padding: 25px;
                border-radius: 15px;
                text-align: center;
                box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            }

            .popup-content p {
                font-size: 1.5rem;
                margin-bottom: 20px;
            }

            .popup-content button {
                margin: 0 10px;
                padding: 10px 20px;
                font-size: 1.2rem;
                border: none;
                border-radius: 10px;
                cursor: pointer;
            }

            #yesExit {
                background: green;
                color: #fff;
            }

            #noExit {
                background: #ccc;
                color: #000;
            }

            .hidden {
                display: none;
            }

            /* Slideshow keyframes */
            @keyframes slideshow {
                25%  { background-image: url("ASSETS/BG9.jpg"); }
                50%  { background-image: url("ASSETS/BG4.jpg"); }
                75%  { background-image: url("ASSETS/BG8.jpg"); }
                100% { background-image: url("ASSETS/BG5.jpg"); } 
                125% { background-image: url("ASSETS/BG6.jpg"); }
                150% { background-image: url("ASSETS/BG7.jpg"); }
            }

            @keyframes pulse {
                0%   { opacity: 0.6; transform: scale(1); }
                50%  { opacity: 1;   transform: scale(1.05); }
                100% { opacity: 0.6; transform: scale(1); }
            }
            </style>

<script>
            document.getElementById("startScreen").addEventListener("click", function() {
                document.getElementById("startScreen").style.display = "none";
                document.getElementById("home").classList.remove("hidden");
            });

            // Exit button click
            document.getElementById("exitBtn").addEventListener("click", function() {
                document.getElementById("exitPopup").classList.remove("hidden");
            });

            // NO button
            document.getElementById("noExit").addEventListener("click", function() {
                document.getElementById("exitPopup").classList.add("hidden");
            });

            // YES button (reset to Welcome)
            document.getElementById("yesExit").addEventListener("click", function() {
            document.getElementById("exitPopup").classList.add("hidden");
            document.getElementById("home").classList.add("hidden");
            document.getElementById("startScreen").style.display = "flex";

            // ✅ Reset the cart display
            if (typeof cart !== "undefined") {
                cart.length = 0;
            }

            // ✅ Force reload so everything resets
            location.reload();
        });
    </script>

    <div class="keyboard" id="keyboard">
        <!-- Letter Keyboard -->
        <div id="letterKeyboard">
            <div class="keyboard-row">
                <button onclick="typeKey('Q')">Q</button>
                <button onclick="typeKey('W')">W</button>
                <button onclick="typeKey('E')">E</button>
                <button onclick="typeKey('R')">R</button>
                <button onclick="typeKey('T')">T</button>
                <button onclick="typeKey('Y')">Y</button>
                <button onclick="typeKey('U')">U</button>
                <button onclick="typeKey('I')">I</button>
                <button onclick="typeKey('O')">O</button>
                <button onclick="typeKey('P')">P</button>
            </div>
            <div class="keyboard-row">
                <button onclick="typeKey('A')">A</button>
                <button onclick="typeKey('S')">S</button>
                <button onclick="typeKey('D')">D</button>
                <button onclick="typeKey('F')">F</button>
                <button onclick="typeKey('G')">G</button>
                <button onclick="typeKey('H')">H</button>
                <button onclick="typeKey('J')">J</button>
                <button onclick="typeKey('K')">K</button>
                <button onclick="typeKey('L')">L</button>
            </div>
            <div class="keyboard-row">
                <button onclick="typeKey('Z')">Z</button>
                <button onclick="typeKey('X')">X</button>
                <button onclick="typeKey('C')">C</button>
                <button onclick="typeKey('V')">V</button>
                <button onclick="typeKey('B')">B</button>
                <button onclick="typeKey('N')">N</button>
                <button onclick="typeKey('M')">M</button>
                <button onclick="backspace()">⌫</button>
            </div>
            <div class="keyboard-row">
                <button class="wide-button" onclick="typeKey(' ')">Space</button>
                <button onclick="toggleNumberPad()">123</button>
            </div>
        </div>
        
           <!-- Number Pad Keyboard -->
    <div id="numPad" style="display: none; flex-direction: row; gap: 10px;">
        
        <!-- Left Side: Special Characters -->
        <div class="special-keys">
            <div class="keyboard-row">
                <button onclick="typeKey('-')">-</button>
                <button onclick="typeKey('+')">+</button>
                <button onclick="typeKey('.')">.</button>
            </div>
            <div class="keyboard-row">
                <button onclick="typeKey('*')">*</button>
                <button onclick="typeKey('/')">/</button>
                <button onclick="typeKey(',')">,</button>
            </div>
            <div class="keyboard-row">
                <button onclick="typeKey('(')">(</button>
                <button onclick="typeKey(')')">)</button>
                <button onclick="typeKey('=')">=</button>
            </div>
            <div class="keyboard-row">
            <button class="wide-button1" onclick="typeKey('')">Space</button>
                <button onclick="toggleLetterKeyboard()">ABC</button>
            </div>
        </div>

        <!-- Right Side: Number Keys -->
        <div class="number-keys">
            <div class="keyboard-row">
                <button onclick="typeKey('1')">1</button>
                <button onclick="typeKey('2')">2</button>
                <button onclick="typeKey('3')">3</button>
            </div>
            <div class="keyboard-row">
                <button onclick="typeKey('4')">4</button>
                <button onclick="typeKey('5')">5</button>
                <button onclick="typeKey('6')">6</button>
            </div>
            <div class="keyboard-row">
                <button onclick="typeKey('7')">7</button>
                <button onclick="typeKey('8')">8</button>
                <button onclick="typeKey('9')">9</button>
            </div>
            <div class="keyboard-row">
                <button onclick="typeKey('*')">*</button>
                <button onclick="typeKey('0')">0</button>
                <button onclick="backspace()">⌫</button>
            </div>
           
        </div>

    </div>

</div>

        </div>

        <!-- Cart Tab -->
        <div id="cart" class="tab-content">
    <div class="cart">
        <h2>Shopping Cart</h2>
        <ul class="cart-items" id="cart-items"></ul>
        <div class="total">
            <strong>Total:</strong> <span id="cart-total">₱0.00</span>
        </div>
    </div>
</div>



<!-- Tutorial Section -->
<div id="tutorial-btn" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeTab('tutorial-btn')">&times;</span>
        <h2><i class="fas fa-shopping-cart"></i> How to Use ShopEase</h2>

        <!-- Step 1: Search for Product Location -->
        <div class="step">
            <i class="fas fa-search-location"></i>
            <div class="step-content">
                <h3>📍 Step 1: Search for Product Location</h3>
                <ul>
                    <li>Use the <strong>Search Bar</strong> to find an item.</li>
                    <li>Tap the <strong>Location Button</strong> to open a map.</li>
                    <li>The map will display the product’s location and guide you to it.</li>
                </ul>
            </div>
        </div>

        <!-- Step 2: Add Items to Cart -->
        <div class="step">
            <i class="fas fa-cart-plus"></i>
            <div class="step-content">
                <h3>➕ Step 2: Add Items to Cart</h3>
                <ul>
                    <li>Click the <strong>Add to Cart</strong> button then,</li>
                    <li>Item will be added to your cart immediately.</li>
                </ul>
            </div>
        </div>

        <!-- Step 3: View Cart and Expenses -->
        <div class="step">
            <i class="fas fa-shopping-basket"></i>
            <div class="step-content">
                <h3>🧾 Step 3: View Cart and Expenses</h3>
                <ul>
                    <li>In the Cart, you will see:</li>
                    <li class="no-marker">✔️ List of added items</li>
                    <li class="no-marker">✔️ Total expenses</li>
                </ul>
            </div>
        </div>

        <!-- Step 4: Use the Scanner for Price Checking -->
        <div class="step">
            <i class="fas fa-barcode"></i>
            <div class="step-content">
                <h3>🔍 Step 4: Use the Scanner for Price Checking</h3>
                <ul>
                    <li>If you prefer not to use the search bar, use the <strong>Scanner</strong> located at the bottom.</li>
                    <li>Scan the product’s barcode to check its price.</li>
                </ul>
            </div>
        </div>
    </div>
</div>




<script>
   let cart = [];
let total = 0;

function showTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
    // Show the selected tab
    document.getElementById(tabName).classList.add('active');
    
    // ✅ Hide the dropdown list when switching to the Cart tab
    if (tabName === 'cart') {
        closeCartDropdown(); 
    }
}

function showKeyboard() {
    document.getElementById('keyboard').style.display = 'block';
}

function typeKey(key) {
    document.getElementById('search').value += key;
    searchProduct();
}

function backspace() {
    let input = document.getElementById('search');
    input.value = input.value.slice(0, -1);
    searchProduct();
}

function closeKeyboard() {
    document.getElementById('keyboard').style.display = 'none';
}

function searchProduct() {
    const searchInput = document.getElementById('search');
    const search = searchInput.value.trim();
    searchInput.focus();

    if (search === '') {
        document.getElementById('result').innerHTML = '';
        return;
    }

    fetch(`search_product.php?search=${search}`)
        .then(response => response.text())
        .then(data => {
            document.getElementById('result').innerHTML = data || `<div class="error-message">Item not found</div>`;
        });
}

function toggleNumberPad() {
    document.getElementById("letterKeyboard").style.display = "none";
    document.getElementById("numPad").style.display = "flex";
}


function toggleLetterKeyboard() {
    document.getElementById('numPad').style.display = 'none';
    document.getElementById('letterKeyboard').style.display = 'grid'; // Use grid to keep alignment
}

// Close keyboard when clicking outside, but allow key clicks
document.addEventListener('click', function(event) {
    const keyboard = document.getElementById('keyboard');
    const searchInput = document.getElementById('search');

    if (!keyboard.contains(event.target) && event.target !== searchInput && !event.target.closest('.key')) {
        keyboard.style.display = 'none';
    }
});


function updateCartBadge() {
    const badge = document.getElementById('cart-badge');
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);

    badge.innerText = totalItems;
    badge.style.display = totalItems > 0 ? 'inline-block' : 'none';
}


function addToCart(name, price, button) {
    const existingProduct = cart.find(item => item.name === name);
    if (existingProduct) {
        existingProduct.quantity += 1;
    } else {
        cart.push({ 
            name, 
            price: parseFloat(price), // Ensure price is a number
            quantity: 1 
        });
    }
    cartClick(button); 
    updateCart(); 
    updateCartBadge(); // ✅ Fixed typo
}


function cartClick(button) {
    button.classList.add('clicked');
    setTimeout(() => {
        button.classList.remove('clicked');
    }, 1500);
}

function updateCart() {
    const cartItemsDiv = document.getElementById('cart-items');
    const cartTotalSpan = document.getElementById('cart-total');
    cartItemsDiv.innerHTML = '';
    let total = 0;

    cart.forEach((item, index) => {
        // Use promo price if available
        const usePromo = item.promoPrice !== undefined && item.promoPrice !== null && !isNaN(item.promoPrice);
        const displayPrice = usePromo ? item.promoPrice : item.price;
        total += displayPrice * item.quantity;

        const priceHtml = usePromo
            ? `<span style="color:#ef4444;font-weight:700;">₱${Number(item.promoPrice).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
               <span style="text-decoration:line-through;color:#888;font-size:0.95em;margin-left:5px;">₱${Number(item.price).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>`
            : `₱${Number(item.price).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

        cartItemsDiv.innerHTML += `
            <li class="item">
                ${item.name} (${priceHtml}) 
                <div class="quantity-controls">
                    <button onclick="updateQuantity(${index}, -1)" ${item.quantity === 1 ? 'disabled' : ''}>-</button>
                    <span>${item.quantity}</span>
                    <button onclick="updateQuantity(${index}, 1)">+</button>
                </div>
                <button onclick="removeFromCart(${index})" style="background-color:red;color:white;border:none;padding:5px;">Remove</button>
            </li>
        `;
    });

    cartTotalSpan.innerText = `₱${Number(total).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    updateCartBadge();
}










function updateQuantity(index, change) {
    cart[index].quantity += change;
    if (cart[index].quantity <= 0) {
        cart.splice(index, 1);
    }
    updateCart(); 
    
}

function removeFromCart(index) {
    cart.splice(index, 1);
    updateCart();
  
}



function showTab(tabId) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });

    // Hide modal when switching tabs
    document.getElementById('tutorial-btn').style.display = 'none';

    // Show selected tab
    const selectedTab = document.getElementById(tabId);
    if (selectedTab) {
        selectedTab.classList.add('active');
    }
}

function closeTab(tabId) {
    document.getElementById(tabId).style.display = 'none';
}



// Open tutorial in modal format
function openTutorial() {
    document.getElementById('tutorial-btn').style.display = 'block';
    // Hide home when tutorial is shown
    document.getElementById('home').classList.remove('active');
}


let barcode = '';

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Enter') {
        barcode += event.key;
    } else {
        event.preventDefault();

        console.log(`Scanned Barcode: ${barcode}`);

        if (barcode) {
            fetch(`search_product.php?search=${barcode}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('result').innerHTML = data;
                })
                .catch(error => console.error('Error:', error));
        }

        // Reset barcode after processing
        barcode = '';
    }
});


/* MAP*/
// Store layout configuration
const aisles = {
    "Aisle 1": {x: 100, y: 100},
    "Aisle 2": {x: 200, y: 100},
    "Aisle 3": {x: 300, y: 100},
    "Aisle 4": {x: 100, y: 300},
    "Aisle 5": {x: 200, y: 300},
};
const entrance = {x: 50, y: 450};

function showProductLocation(aisle) {
    document.getElementById('map-modal').style.display = 'flex';
    document.getElementById('map-title').innerText = "Store Map - " + aisle;
    drawStoreMap(aisle);


        // Also trigger Pi BLE map to update
 fetch('http://192.168.24.27:5050/show_location?aisle=' + encodeURIComponent(aisle), { method: 'POST' })
      .then(res => res.text())
      .then(msg => console.log(msg))
}



    </script>
    

</body>
</html>
