<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'ecommerce_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// Fetch categories with aisle numbers
$categories = [];
$cat_query = $conn->query("SELECT category, aisle FROM aisles ORDER BY category ASC");
if ($cat_query) {
    while ($row = $cat_query->fetch_assoc()) {
        $categories[] = $row;
    }
}
// Fetch items per category (for sidebar)
$items_by_category = [];
$item_query = $conn->query("SELECT category, name FROM products ORDER BY category, name ASC");
if ($item_query) {
    while ($row = $item_query->fetch_assoc()) {
        $cat = $row['category'];
        if (!isset($items_by_category[$cat])) $items_by_category[$cat] = [];
        $items_by_category[$cat][] = $row['name'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ShopEase - Supermarket Product Locator</title>
  <!-- Google Fonts: Inter & Poppins -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <!-- Font Awesome 6 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"/>
  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        fontFamily: {
          'sans': ['Inter', 'Poppins', 'sans-serif'],
        },
        extend: {
          colors: {
            accent: '#2563eb',
            accentgreen: '#10b981',
            dark: '#1e293b',
            neutral: '#f7fafc',
            subtle: '#e5eaf1',
            sidebarbg: '#f5f7fa',
            sidebarborder: '#e5eaf1',
            sidebaractive: '#e5eaf1',
            sidebaricon: '#2563eb',
            sidebarhighlight: '#e0e8fa',
          },
          borderRadius: {
            primary: '12px',
            secondary: '8px',
            full: '9999px',
          },
          boxShadow: {
            card: '0 6px 32px 0 rgba(37,99,235,0.10)',
            neu: '4px 4px 16px #e0e4ea, -4px -4px 16px #fff',
            modal: '0 10px 40px 0 rgba(30,41,59,0.18)',
            nav: '0 2px 12px 0 rgba(30,41,59,0.06)',
          },
          transitionDuration: {
            DEFAULT: '250ms'
          },
          keyframes: {
            fadeIn: { '0%':{opacity:0,transform:'scale(.97)'}, '100%':{opacity:1,transform:'scale(1)'} },
            fadeOut: { '0%':{opacity:1,transform:'scale(1)'}, '100%':{opacity:0,transform:'scale(.97)'} },
            bouncein: { '0%,100%':{transform:'scale(1)'}, '30%':{transform:'scale(1.18)'} },
            shake: {
              '10%, 90%': { transform: 'translateX(-1px)' },
              '20%, 80%': { transform: 'translateX(2px)' },
              '30%, 50%, 70%': { transform: 'translateX(-4px)' },
              '40%, 60%': { transform: 'translateX(4px)' }
            }
          },
          animation: {
            fadeIn: 'fadeIn .26s',
            fadeOut: 'fadeOut .22s',
            bouncein: 'bouncein .6s',
            shake: 'shake .4s'
          }
        }
      }
    }
  </script>
  <style>
    .product-card {
      transition: box-shadow .15s;
    }
    .product-card:hover {
      box-shadow: 0 8px 32px 0 rgba(37,99,235,0.14);
      transform: translateY(-2px) scale(1.01);
    }
    .fade-in {
      animation: fadeIn .26s;
    }
    .fade-out {
      animation: fadeOut .22s;
    }
    body { background: #f7fafc; }
    .scrollbar-hide::-webkit-scrollbar { display: none; }
    .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
    
    .kbd-neu {
      background: #f3f6fb;
      box-shadow: 2px 2px 12px #e5e9f3, -2px -2px 12px #fff;
      border-radius: 14px;
      border: none;
      color: #2563eb;
      font-size: 1.1rem;
      font-weight: 600;
      transition: box-shadow .2s, background .2s, color .2s, transform .1s;
      min-width: 44px;
      min-height: 44px;
      margin: 0 2px;
      outline: none;
    }
    .kbd-neu:hover, .kbd-neu:focus {
      background: #e7f0fd;
      color: #1742a8;
      box-shadow: 0 4px 18px #2563eb22;
      transform: translateY(-2px) scale(1.04);
    }
    .kbd-wide { min-width: 110px; }
    .modal-fade { animation: fadeIn .25s; }
    .modal-fade-out { animation: modalFadeOut .21s forwards; }
    @keyframes modalFadeOut { 0%{opacity:1;transform:scale(1);}100%{opacity:0;transform:scale(.98);} }
    .cart-badge-animate { animation: bouncein .6s; }
    .addtocart-animate {
      animation: bouncein .5s;
    }
    .addtocart-shake {
      animation: shake .4s;
    }
    @media (max-width: 700px) {
      .keyboard { max-width: 100vw !important; }
      .sidebar { display: none !important; }
      .maincontent { margin-left: 0 !important; }
    }
    .addtocart-fly {
      position: absolute;
      z-index: 99;
      pointer-events: none;
      transition: all .8s cubic-bezier(.4,0,.2,1);
      will-change: transform, opacity;
    }
    .bg-blur {
      background: linear-gradient(to bottom right, #2563eb 0%, #f7fafc 100%);
      filter: blur(14px) brightness(0.7);
    }
    .start-screen {
      position: fixed;
      inset: 0;
      z-index: 50;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      background: linear-gradient(135deg, #2563eb 70%, #fff 100%);
      cursor: pointer;
      animation: fadeIn 0.7s;
    }
    .start-screen h1 {
      font-size: 5rem;
      font-family: 'Poppins', 'Inter', sans-serif;
      font-weight: 900;
      color: #fff;
      text-shadow: 3px 3px 16px #2563eb55;
      margin-bottom: 0.5em;
    }
    .start-screen p {
      font-size: 2.2rem;
      color: #fff;
      text-shadow: 2px 2px 10px #2563eb55;
      opacity: 0.8;
      animation: pulse 1.5s infinite;
    }
    @keyframes pulse {
      0%   { opacity: 0.6; transform: scale(1);}
      50%  { opacity: 1;   transform: scale(1.03);}
      100% { opacity: 0.6; transform: scale(1);}
    }
    /* --- SIDEBAR UI REFACTOR --- */
    .sidebar {
      position: fixed;
      top: 0;
      left: 0;
      height: 100vh;
      width: 240px;
      background: #f5f7fa;
      border-right: 1px solid #e5eaf1;
      z-index: 40;
      display: flex;
      flex-direction: column;
      box-shadow: 2px 0 24px rgba(30,41,59,0.06);
      transition: none;
      will-change: unset;
    }
    .sidebar-logo {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 0.5em;
      padding: 2.2rem 1.4rem 1.2rem 1.4rem;
      border-bottom: 1px solid #e5eaf1;
      background: transparent;
    }
    .sidebar-logo img {
      width: 52px;
      height: 52px;
      object-fit: contain;
      border-radius: 16px;
      box-shadow: 0 4px 16px #2563eb22;
      background: #e5eaf1;
    }
    .sidebar-logo .brand {
      font-family: 'Poppins', 'Inter', sans-serif;
      font-weight: 900;
      font-size: 1.55rem;
      color: #2563eb;
      letter-spacing: -0.01em;
      margin-top: 0.12em;
      text-shadow: 1px 2px 10px #2563eb11;
    }
    .sidebar-header {
      font-family: 'Poppins', sans-serif;
      font-weight: 700;
      font-size: 1.12rem;
      color: #2563eb;
      padding: 0.75rem 1.4rem 0.5rem 2.1rem;
      border-bottom: 1px solid #e5eaf1;
      letter-spacing: 0.01em;
      background: transparent;
      display: flex;
      align-items: center;
      gap: 0.7em;
    }
    .sidebar-list {
      flex: 1;
      overflow-y: auto;
      padding: 0.3rem 0.7rem 1.5rem 0.2rem;
      background: none;
    }
    .sidebar-list ul {
      list-style: none;
      margin: 0;
      padding: 0;
    }
    .sidebar-list li.category-li {
      display: flex;
      align-items: center;
      padding: 0.70rem 1.1rem 0.70rem 1.1rem;
      margin-bottom: 0.12rem;
      border-radius: 9px;
      background: none;
      color: #1e293b;
      font-weight: 500;
      font-family: 'Inter', sans-serif;
      font-size: 1.04rem;
      cursor: pointer;
      gap: 0.8em;
      transition: background 0.17s, color 0.17s;
      outline: none;
      border: none;
      box-sizing: border-box;
      position: relative;
      user-select: none;
    }
    .sidebar-list li.category-li .sidebar-icon {
      color: #2563eb;
      font-size: 1.14em;
      width: 22px;
      display: inline-flex;
      justify-content: center;
      align-items: center;
      min-width: 22px;
      margin-right: 0.18em;
    }
    .sidebar-list li.category-li .sidebar-label {
      font-family: 'Inter', 'Poppins', sans-serif;
      font-weight: 500;
      font-size: 1rem;
      color: inherit;
      flex: 1;
      display: flex;
      align-items: center;
      gap: 0.15em;
    }
    .sidebar-list li.category-li .aisle-badge {
      background: #e5eaf1;
      color: #2563eb;
      border-radius: 6px;
      padding: 3px 9px;
      font-size: 0.93em;
      font-weight: 600;
      margin-left: auto;
      letter-spacing: 0.01em;
    }
    .sidebar-list li.category-li.active, .sidebar-list li.category-li:active, .sidebar-list li.category-li:focus {
      background: #e0e8fa;
      color: #2563eb;
    }
    .sidebar-list li.text-gray-400 {
      color: #aeb3be;
      font-size: 1em;
      text-align: center;
      justify-content: flex-start !important;
    }
    .sidebar-list .category-arrow {
      margin-left: 0.5rem;
      color: #8da0c9;
      font-size: 1.1em;
      transition: transform 0.25s;
      will-change: transform;
      display: inline-block;
    }
    .sidebar-list li.category-li.active .category-arrow {
      color: #2563eb;
      transform: rotate(90deg);
    }
    .category-items-list {
      background: none;
      padding: 0 0 0.18rem 2.5rem;
      margin: 0;
      overflow: hidden;
      transition: max-height 0.32s cubic-bezier(.5,0,.2,1), opacity 0.25s;
      max-height: 0;
      opacity: 0;
      pointer-events: none;
      font-size: 0.96em;
      border-left: 2px solid #e5eaf1;
    }
    .category-items-list.open {
      opacity: 1;
      pointer-events: auto;
      max-height: 300px;
      margin-bottom: 0.15em;
    }
    .category-items-list li {
      font-size: 0.96em;
      color: #526086;
      padding: 0.4em 0 0.2em 0.4em;
      border-radius: 6px;
      background: none;
      margin: 0;
      transition: background 0.16s, color 0.16s;
      cursor: pointer;
      position: relative;
      left: 0;
    }
    .category-items-list li:hover, .category-items-list li:focus {
      background: #e5eaf1;
      color: #2563eb;
    }
    .maincontent {
      margin-left: 240px;
      transition: none;
      min-height: 100vh;
      background: #f7fafc;
      display: flex;
      flex-direction: column;
    }
    @media (max-width: 700px) {
      .maincontent { margin-left: 0!important; }
      .sidebar { display: none !important; }
    }


    @keyframes fadeIn {
    0% {opacity:0; transform:translateY(20px);}
    100% {opacity:1; transform:translateY(0);}
}
.animate-fadeIn {
    animation: fadeIn 0.42s;
}


.fade-in {
  animation: fadeIn .26s;
  opacity: 1 !important;
  pointer-events: auto !important;
}
.fade-out {
  animation: fadeOut .22s;
  opacity: 0 !important;
  pointer-events: none !important;
}
@keyframes fadeIn { 0% {opacity:0; transform:scale(.97);} 100%{opacity:1; transform:scale(1);} }
@keyframes fadeOut { 0% {opacity:1; transform:scale(1);} 100%{opacity:0; transform:scale(.97);} }

  </style>
</head>
<body class="bg-neutral min-h-screen flex flex-col font-sans text-dark relative">

<?php
// Sort categories by aisle number before displaying
usort($categories, function($a, $b) {
    return $a['aisle'] <=> $b['aisle'];
});
?>
<aside id="sidebar" class="sidebar">
  <!-- Icon/Brand always visible at the very top -->
  <div class="sidebar-logo">
    <i class="fas fa-store-alt" style="font-size: 2.8rem; color: #2563eb; background: #e5eaf1; border-radius: 16px; padding: 0.65em 0.79em; box-shadow: 0 4px 16px #2563eb22;"></i>
    <span class="brand">ShopEase</span>
  </div>
  <div class="sidebar-header flex items-center gap-2">
    <i class="fas fa-list-ul sidebar-icon"></i>
    <span class="sidebar-label">Categories</span>
  </div>
  <div class="sidebar-list scrollbar-hide">
    <ul id="category-list">
      <?php foreach($categories as $cat):
        $cat_name = $cat['category'];
        $cat_id = 'cat_' . md5($cat_name);
        $cat_items = $items_by_category[$cat_name] ?? [];
      ?>
        <li class="category-li" tabindex="0"
            data-category="<?= htmlspecialchars($cat_name) ?>"
            data-cat-id="<?= $cat_id ?>">
          <i class="fas fa-tag sidebar-icon"></i>
          <span class="sidebar-label"><?= htmlspecialchars($cat_name) ?></span>
          <span class="ml-auto text-xs text-accent font-semibold italic" style="margin-left:auto; opacity:0.85;">
            (Aisle <?= htmlspecialchars($cat['aisle']) ?>)
          </span>
          <span class="category-arrow"><i class="fas fa-chevron-right"></i></span>
        </li>
        <ul class="category-items-list" id="<?= $cat_id ?>">
          <?php foreach($cat_items as $item): ?>
            <li tabindex="0"><?= htmlspecialchars($item) ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endforeach; ?>
      <?php if (empty($categories)): ?>
        <li class="text-gray-400"><i class="fas fa-exclamation-circle sidebar-icon"></i> No categories found.</li>
      <?php endif; ?>
    </ul>
  </div>
</aside>

<div id="maincontent" class="maincontent flex-grow flex flex-col">
<!-- NAVBAR -->
<nav class="sticky top-0 w-full z-30 bg-white/90 shadow-nav flex items-center px-4 sm:px-8 py-2 md:py-3 transition-all">
  <div class="flex items-center gap-3 flex-1">
    <img src="ASSETS/Logo3.png" alt="Logo" class="w-11 h-11 rounded-2xl shadow-md object-contain bg-subtle">
    <span class="font-extrabold text-2xl md:text-3xl text-accent tracking-tight font-poppins">ShopEase</span>
  </div>
  <div class="flex gap-1 md:gap-4 items-center">
    <button onclick="showTab('home')" id="nav-home" class="group px-3 py-2 rounded-primary transition-all font-semibold flex items-center gap-2 text-dark hover:bg-accent/10 hover:text-accent focus:bg-accent/10 focus:text-accent text-lg md:text-base">
      <i class="fas fa-home text-xl"></i>
      <span class="hidden sm:inline font-medium">Home</span>
    </button>
    <button onclick="showTab('cart')" id="nav-cart" class="relative group px-3 py-2 rounded-primary transition-all font-semibold flex items-center gap-2 text-dark hover:bg-accent/10 hover:text-accent focus:bg-accent/10 focus:text-accent text-lg md:text-base">
      <i class="fas fa-shopping-cart text-xl"></i>
      <span class="hidden sm:inline font-medium">Cart</span>
      <span id="cart-badge" class="absolute -top-2 -right-2 bg-accent text-white rounded-full px-2 text-xs font-bold shadow border-2 border-white transition-all">0</span>
    </button>
    <button onclick="openTutorial()" id="nav-tutorial" class="group px-3 py-2 rounded-primary transition-all font-semibold flex items-center gap-2 text-dark hover:bg-accent/10 hover:text-accent focus:bg-accent/10 focus:text-accent text-lg md:text-base">
      <i class="fas fa-book text-xl"></i>
      <span class="hidden sm:inline font-medium">Tutorial</span>
    </button>
  </div>
</nav>

<main class="flex-grow relative flex flex-col pt-4 pb-6 px-2 sm:px-0 items-center bg-neutral">

  <!-- Start Screen -->
  <div id="startScreen" class="start-screen" onclick="startShopEase()">
    <h1 class="font-poppins font-extrabold">Welcome to ShopEase</h1>
    <p class="font-sans">Tap anywhere to start</p>
  </div>

  <!-- Home Tab -->
  <section id="home" class="tab-content w-full max-w-2xl mx-auto transition-all duration-300">
    <h1 class="text-3xl md:text-4xl font-extrabold text-accent mb-4 font-poppins">Find a Product</h1>
    <div class="flex flex-col gap-6">
  <!-- Search Bar -->
  <div class="mx-auto w-full">
    <div class="relative flex flex-col sm:flex-row items-center justify-center gap-3 sm:gap-0">
      <i class="fas fa-search absolute left-5 top-1/2 -translate-y-1/2 text-accent text-2xl"></i>
      <input type="text" id="search" placeholder="Enter product name..."
        class="w-full py-4 pl-16 pr-28 rounded-primary bg-white shadow-card border border-subtle text-lg font-medium focus:ring-2 focus:ring-accent focus:border-accent outline-none transition-all duration-300 placeholder:text-gray-400 focus:shadow-lg"
        onfocus="showKeyboard()" autocomplete="off"
      />
      <button onclick="closeKeyboard()" class="absolute right-3 sm:right-3 top-1/2 -translate-y-1/2 px-7 py-2 bg-accent text-white rounded-primary shadow hover:bg-accent/90 font-bold text-base transition-all duration-300 focus:outline-none focus:scale-105 active:scale-95">Search</button>
    </div>
  </div>
  <div class="result-container transition-all duration-200" id="result"></div>

  <div id="search-results-container"
     class="transition-all duration-200 opacity-0 pointer-events-none absolute left-0 right-0 z-10 mt-6 w-full max-w-4xl mx-auto"></div>
<div id="category-products"
     class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-10 transition-all"></div>
</div>
    <button id="exitBtn" class="exit-btn fixed bottom-8 right-5 md:bottom-10 md:right-14 px-7 py-3 bg-accentgreen text-white font-bold rounded-primary shadow-lg hover:bg-accentgreen/90 active:scale-95 transition-all duration-200 flex items-center gap-2 text-lg z-30"><i class="fas fa-sign-out-alt text-xl"></i>Done</button>
    <button
  id="showCartLocationBtn"
  onclick="showCartLocation()"
  class="fixed bottom-8 left-5 md:bottom-10 md:left-[230px] px-7 py-3 bg-accent text-white font-bold rounded-primary shadow-lg hover:bg-accent/90 active:scale-95 transition-all duration-200 flex items-center gap-2 text-lg z-50 hidden"
>
  <i class="fas fa-map-marker-alt text-xl"></i>
  Show My Cart Location
</button>

</section>

  <!-- Cart Tab -->
  <section id="cart" class="tab-content w-full max-w-2xl mx-auto transition-all duration-300 hidden">
    <div class="cart bg-white rounded-primary shadow-card p-7 mt-5">
      <h2 class="text-2xl font-bold text-accent mb-6 flex items-center gap-2 font-poppins"><i class="fas fa-shopping-cart text-2xl"></i> Shopping Cart</h2>
      <ul class="cart-items flex flex-col gap-4 mb-6" id="cart-items"></ul>
      <div class="total flex justify-between items-center bg-subtle rounded-secondary py-4 px-6 font-bold text-accent text-xl shadow-inner border border-neutral-200">
        <span>Total:</span> <span id="cart-total">₱0.00</span>
      </div>
    </div>
  </section>
</main>
</div> <!-- End maincontent wrapper -->

<!-- Exit Confirmation Modal -->
<div id="exitPopup" class="fixed inset-0 flex items-center justify-center bg-black/40 z-50 hidden">
  <div class="bg-white rounded-2xl shadow-modal p-10 w-[92vw] max-w-sm modal-fade flex flex-col items-center gap-7 relative">
    <button class="absolute top-4 right-5 text-gray-400 hover:text-accent text-3xl font-bold transition" onclick="closeTab('exitPopup')"><i class="fas fa-times"></i></button>
    <i class="fas fa-sign-out-alt text-5xl text-accent mb-2"></i>
    <p class="font-semibold text-lg text-dark mb-4 text-center">Are you sure you want to exit?</p>
    <div class="flex gap-5">
      <button id="yesExit" class="px-8 py-2.5 bg-accent text-white rounded-primary font-semibold shadow hover:bg-accent/90 transition focus:scale-105">YES</button>
      <button id="noExit" class="px-8 py-2.5 bg-subtle text-accent rounded-primary font-semibold shadow hover:bg-neutral/90 transition focus:scale-105">NO</button>
    </div>
  </div>
</div>

<!-- Keyboard (Neumorphic) -->
<div class="keyboard fixed left-1/2 bottom-3 -translate-x-1/2 z-50 bg-white/90 rounded-xl shadow-neu px-5 py-4 flex flex-col items-center gap-1 w-full max-w-lg hidden" id="keyboard">
  <div id="letterKeyboard" class="flex flex-col gap-2 w-full items-center">
    <div class="keyboard-row flex gap-2 justify-center">
      <button onclick="typeKey('Q')" class="kbd-neu">Q</button>
      <button onclick="typeKey('W')" class="kbd-neu">W</button>
      <button onclick="typeKey('E')" class="kbd-neu">E</button>
      <button onclick="typeKey('R')" class="kbd-neu">R</button>
      <button onclick="typeKey('T')" class="kbd-neu">T</button>
      <button onclick="typeKey('Y')" class="kbd-neu">Y</button>
      <button onclick="typeKey('U')" class="kbd-neu">U</button>
      <button onclick="typeKey('I')" class="kbd-neu">I</button>
      <button onclick="typeKey('O')" class="kbd-neu">O</button>
      <button onclick="typeKey('P')" class="kbd-neu">P</button>
    </div>
    <div class="keyboard-row flex gap-2 justify-center">
      <button onclick="typeKey('A')" class="kbd-neu">A</button>
      <button onclick="typeKey('S')" class="kbd-neu">S</button>
      <button onclick="typeKey('D')" class="kbd-neu">D</button>
      <button onclick="typeKey('F')" class="kbd-neu">F</button>
      <button onclick="typeKey('G')" class="kbd-neu">G</button>
      <button onclick="typeKey('H')" class="kbd-neu">H</button>
      <button onclick="typeKey('J')" class="kbd-neu">J</button>
      <button onclick="typeKey('K')" class="kbd-neu">K</button>
      <button onclick="typeKey('L')" class="kbd-neu">L</button>
    </div>
    <div class="keyboard-row flex gap-2 justify-center">
      <button onclick="typeKey('Z')" class="kbd-neu">Z</button>
      <button onclick="typeKey('X')" class="kbd-neu">X</button>
      <button onclick="typeKey('C')" class="kbd-neu">C</button>
      <button onclick="typeKey('V')" class="kbd-neu">V</button>
      <button onclick="typeKey('B')" class="kbd-neu">B</button>
      <button onclick="typeKey('N')" class="kbd-neu">N</button>
      <button onclick="typeKey('M')" class="kbd-neu">M</button>
      <button onclick="backspace()" class="kbd-neu" title="Backspace"><i class="fas fa-delete-left"></i></button>
    </div>
    <div class="keyboard-row flex gap-2 justify-center">
      <button class="kbd-neu kbd-wide" onclick="typeKey(' ')"><i class="fas fa-space-bar"></i> Space</button>
      <button onclick="toggleNumberPad()" class="kbd-neu">123</button>
    </div>
  </div>
  <div id="numPad" style="display:none;" class="flex flex-row gap-6 w-full justify-center">
    <div class="special-keys flex flex-col gap-2">
      <div class="keyboard-row flex gap-2">
        <button onclick="typeKey('-')" class="kbd-neu">-</button>
        <button onclick="typeKey('+')" class="kbd-neu">+</button>
        <button onclick="typeKey('.') "class="kbd-neu">.</button>
      </div>
      <div class="keyboard-row flex gap-2">
        <button onclick="typeKey('*')" class="kbd-neu">*</button>
        <button onclick="typeKey('/')" class="kbd-neu">/</button>
        <button onclick="typeKey(',')" class="kbd-neu">,</button>
      </div>
      <div class="keyboard-row flex gap-2">
        <button onclick="typeKey('(')" class="kbd-neu">(</button>
        <button onclick="typeKey(')')" class="kbd-neu">)</button>
        <button onclick="typeKey('=')" class="kbd-neu">=</button>
      </div>
      <div class="keyboard-row flex gap-2">
        <button class="kbd-neu kbd-wide" onclick="typeKey(' ')"><i class="fas fa-space-bar"></i> Space</button>
        <button onclick="toggleLetterKeyboard()" class="kbd-neu">ABC</button>
      </div>
    </div>
    <div class="number-keys flex flex-col gap-2">
      <div class="keyboard-row flex gap-2">
        <button onclick="typeKey('1')" class="kbd-neu">1</button>
        <button onclick="typeKey('2')" class="kbd-neu">2</button>
        <button onclick="typeKey('3')" class="kbd-neu">3</button>
      </div>
      <div class="keyboard-row flex gap-2">
        <button onclick="typeKey('4')" class="kbd-neu">4</button>
        <button onclick="typeKey('5')" class="kbd-neu">5</button>
        <button onclick="typeKey('6')" class="kbd-neu">6</button>
      </div>
      <div class="keyboard-row flex gap-2">
        <button onclick="typeKey('7')" class="kbd-neu">7</button>
        <button onclick="typeKey('8')" class="kbd-neu">8</button>
        <button onclick="typeKey('9')" class="kbd-neu">9</button>
      </div>
      <div class="keyboard-row flex gap-2">
        <button onclick="typeKey('*')" class="kbd-neu">*</button>
        <button onclick="typeKey('0')" class="kbd-neu">0</button>
        <button onclick="backspace()" class="kbd-neu" title="Backspace"><i class="fas fa-delete-left"></i></button>
      </div>
    </div>
  </div>
</div>

<!-- Tutorial Modal -->
<div id="tutorial-btn" class="fixed inset-0 bg-black/40 z-50 hidden flex items-center justify-center">
  <div class="modal-content bg-white rounded-2xl shadow-modal p-9 w-[95vw] max-w-xl modal-fade relative flex flex-col gap-6">
    <button class="absolute top-4 right-5 text-gray-400 hover:text-accent text-3xl font-bold transition" onclick="closeTab('tutorial-btn')"><i class="fas fa-times"></i></button>
    <h2 class="text-2xl font-bold text-accent mb-4 flex items-center gap-3 font-poppins"><i class="fas fa-book text-2xl"></i> How to Use ShopEase</h2>
    <div class="flex flex-col gap-6">
      <div class="step flex items-start gap-5">
        <i class="fas fa-search-location text-3xl text-accent"></i>
        <div class="step-content">
          <h3 class="font-semibold text-lg">📍 Step 1: Search for Product Location</h3>
          <ul class="list-disc ml-6 text-dark/70 text-base">
            <li>Use the <strong>Search Bar</strong> to find an item.</li>
            <li>Tap the <strong>Location Button</strong> to open a map.</li>
            <li>The map will display the product’s location and guide you to it.</li>
          </ul>
        </div>
      </div>
      <div class="step flex items-start gap-5">
        <i class="fas fa-cart-plus text-3xl text-accent"></i>
        <div class="step-content">
          <h3 class="font-semibold text-lg">➕ Step 2: Add Items to Cart</h3>
          <ul class="list-disc ml-6 text-dark/70 text-base">
            <li>Click the <strong>Add to Cart</strong> button,</li>
            <li>Item will be added to your cart immediately.</li>
          </ul>
        </div>
      </div>
      <div class="step flex items-start gap-5">
        <i class="fas fa-shopping-basket text-3xl text-accent"></i>
        <div class="step-content">
          <h3 class="font-semibold text-lg">🧾 Step 3: View Cart and Expenses</h3>
          <ul class="list-disc ml-6 text-dark/70 text-base">
            <li>Go to the Cart to see:</li>
            <li class="no-marker">✔️ List of added items</li>
            <li class="no-marker">✔️ Total expenses</li>
          </ul>
        </div>
      </div>
      <div class="step flex items-start gap-5">
        <i class="fas fa-barcode text-3xl text-accent"></i>
        <div class="step-content">
          <h3 class="font-semibold text-lg">🔍 Step 4: Use the Scanner for Price Checking</h3>
          <ul class="list-disc ml-6 text-dark/70 text-base">
            <li>Use the <strong>Scanner</strong> at the bottom if you prefer not to type.</li>
            <li>Scan the product’s barcode to check its price.</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>

<div id="map-modal" class="fixed inset-0 bg-black/40 z-50 hidden flex items-center justify-center">
  <div class="modal-content bg-white rounded-2xl shadow-modal p-8 w-[97vw] max-w-xl modal-fade relative flex flex-col gap-5">
    <button class="absolute top-4 right-5 text-gray-400 hover:text-accent text-3xl font-bold transition" onclick="document.getElementById('map-modal').style.display='none'"><i class="fas fa-times"></i></button>
    <h2 id="map-title" class="text-2xl font-bold text-accent mb-3 font-poppins">Store Map</h2>
    <canvas id="store-map-canvas" width="500" height="500" class="bg-neutral rounded-xl w-full max-w-[500px] block mx-auto"></canvas>
  </div>
</div>


<div class="sidebar-list scrollbar-hide">
  <ul id="category-list">
    <?php foreach($categories as $cat): ?>
      <li tabindex="0"
          class="category-item"
          data-category="<?= htmlspecialchars($cat['category']) ?>">
        <i class="fas fa-tag sidebar-icon"></i>
        <span class="sidebar-label"><?= htmlspecialchars($cat['category']) ?></span>
        <span class="aisle-badge">Aisle <?= htmlspecialchars($cat['aisle']) ?></span>
      </li>
    <?php endforeach; ?>
    <?php if (empty($categories)): ?>
      <li class="text-gray-400"><i class="fas fa-exclamation-circle sidebar-icon"></i> No categories found.</li>
    <?php endif; ?>
  </ul>
</div>


<script>
    function startShopEase() {
  document.getElementById('startScreen').style.display = 'none';
  showTab('home');
  document.getElementById('showCartLocationBtn').classList.remove('hidden');
}
let cart = [];

function showTab(tabId) {
  document.querySelectorAll('.tab-content').forEach(tab => tab.classList.add('hidden'));
  document.getElementById(tabId).classList.remove('hidden');
  document.getElementById('nav-home').classList.remove('text-accent');
  document.getElementById('nav-cart').classList.remove('text-accent');
  document.getElementById('nav-tutorial').classList.remove('text-accent');
  if(tabId === 'home') document.getElementById('nav-home').classList.add('text-accent');
  if(tabId === 'cart') document.getElementById('nav-cart').classList.add('text-accent');
  closeTab('tutorial-btn');
  closeTab('exitPopup');
}
function openTutorial() {
  document.getElementById('tutorial-btn').classList.remove('hidden');
  document.getElementById('tutorial-btn').classList.add('flex');
}
function closeTab(id) {
  let el = document.getElementById(id);
  if(!el) return;
  el.classList.add('modal-fade-out');
  setTimeout(() => {
    el.classList.add('hidden');
    el.classList.remove('flex');
    el.classList.remove('modal-fade');
    el.classList.remove('modal-fade-out');
  }, 210);
}

function showKeyboard() { document.getElementById('keyboard').classList.remove('hidden'); }
function closeKeyboard() { document.getElementById('keyboard').classList.add('hidden'); }
function toggleNumberPad() {
  document.getElementById("letterKeyboard").style.display = "none";
  document.getElementById("numPad").style.display = "flex";
}
function toggleLetterKeyboard() {
  document.getElementById('numPad').style.display = 'none';
  document.getElementById('letterKeyboard').style.display = 'flex';
}
function typeKey(key) {
  let input = document.getElementById('search');
  input.value += key;
  input.focus();
  searchProduct();
}
function backspace() {
  let input = document.getElementById('search');
  input.value = input.value.slice(0, -1);
  input.focus();
  searchProduct();
}
document.addEventListener('click', function(event) {
  const keyboard = document.getElementById('keyboard');
  const searchInput = document.getElementById('search');
  if (!keyboard.contains(event.target) && event.target !== searchInput && !event.target.closest('.kbd-neu')) {
    keyboard.classList.add('hidden');
  }
});

function updateCartBadge(animate=false) {
  const badge = document.getElementById('cart-badge');
  const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
  badge.innerText = totalItems;
  badge.style.display = totalItems > 0 ? 'inline-block' : 'none';
  if(animate) {
    badge.classList.add('cart-badge-animate');
    setTimeout(()=>badge.classList.remove('cart-badge-animate'), 600);
  }
}
function addToCart(name, price, button) {
  const existingProduct = cart.find(item => item.name === name);
  if (existingProduct) {
    existingProduct.quantity += 1;
  } else {
    cart.push({ name, price: parseFloat(price), quantity: 1 });
  }
  cartClick(button);
  updateCart();
  updateCartBadge(true);
}
function cartClick(button) {
  if (!button) return;
  button.classList.add('clicked');
  setTimeout(() => {
    button.classList.remove('clicked');
  }, 900);
}
function updateCart() {
  const cartItemsDiv = document.getElementById('cart-items');
  const cartTotalSpan = document.getElementById('cart-total');
  cartItemsDiv.innerHTML = '';
  let total = 0;
  cart.forEach((item, index) => {
    const priceHtml = `₱${Number(item.price).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    total += item.price * item.quantity;
    cartItemsDiv.innerHTML += `
      <li class="flex items-center justify-between bg-neutral rounded-primary px-4 py-4 shadow-card transition mb-2">
        <div class="flex flex-col gap-1">
          <span class="font-semibold text-dark text-lg">${item.name}</span>
          <span class="text-accent font-bold">${priceHtml}</span>
        </div>
        <div class="flex items-center gap-2">
          <button onclick="updateQuantity(${index}, -1)" class="bg-accent text-white rounded-full px-3 py-1 font-bold text-lg transition hover:bg-accent/90 focus:scale-105" ${item.quantity === 1 ? 'disabled' : ''}><i class="fas fa-minus"></i></button>
          <span class="min-w-[1.6em] text-center font-semibold">${item.quantity}</span>
          <button onclick="updateQuantity(${index}, 1)" class="bg-accent text-white rounded-full px-3 py-1 font-bold text-lg transition hover:bg-accent/90 focus:scale-105"><i class="fas fa-plus"></i></button>
        </div>
        <button onclick="removeFromCart(${index})" class="text-red-500 hover:text-white hover:bg-red-500 transition rounded-full p-2 ml-2 text-xl focus:scale-110" title="Remove"><i class="fas fa-trash"></i></button>
      </li>
    `;
  });
  cartTotalSpan.innerText = `₱${Number(total).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
  updateCartBadge();
}
function updateQuantity(index, change) {
  cart[index].quantity += change;
  if (cart[index].quantity <= 0) cart.splice(index, 1);
  updateCart();
}
function removeFromCart(index) {
  cart.splice(index, 1);
  updateCart();
}

document.getElementById("exitBtn").addEventListener("click", function() {
  document.getElementById("exitPopup").classList.remove('hidden');
  document.getElementById("exitPopup").classList.add('flex');
});
document.getElementById("noExit").addEventListener("click", function() {
  closeTab("exitPopup");
});
document.getElementById("yesExit").addEventListener("click", function() {
  closeTab("exitPopup");
  document.getElementById("home").classList.add('hidden');
  document.getElementById("startScreen").style.display = "flex";
  if (typeof cart !== "undefined") cart.length = 0;
  location.reload();
});

function searchProduct() {
  const searchInput = document.getElementById('search');
  const search = searchInput.value.trim();
  if (!search) {
    document.getElementById('result').innerHTML = '';
    return;
  }
  fetch(`search_product.php?search=${encodeURIComponent(search)}`)
    .then(response => response.text())
    .then(data => {
      document.getElementById('result').innerHTML = data || `<div class="text-center text-red-500 font-semibold">Item not found</div>`;
    });
}
document.getElementById("search").addEventListener("input", searchProduct);

/* Barcode scanner */
let barcode = '';
document.addEventListener('keydown', (event) => {
  if (event.key !== 'Enter') {
    barcode += event.key;
  } else {
    event.preventDefault();
    if (barcode) {
      fetch(`search_product.php?search=${barcode}`)
        .then(response => response.text())
        .then(data => {
          document.getElementById('result').innerHTML = data;
        });
    }
    barcode = '';
  }
});
</script>


<script>
// Sidebar expand/collapse logic
(function() {
  const categoryList = document.getElementById('category-list');
  let openCategoryId = null;
  if (!categoryList) return;

  // Attach click listeners to each category
  categoryList.querySelectorAll('.category-li').forEach(function(catLi) {
    catLi.addEventListener('click', function(e) {
      const catId = catLi.getAttribute('data-cat-id');
      const dropdown = document.getElementById(catId);
      const wasOpen = dropdown.classList.contains('open');
      // Close all
      categoryList.querySelectorAll('.category-li').forEach(li => li.classList.remove('active'));
      categoryList.querySelectorAll('.category-items-list').forEach(ul => ul.classList.remove('open'));
      // Toggle
      if (!wasOpen) {
        dropdown.classList.add('open');
        catLi.classList.add('active');
      }
      openCategoryId = !wasOpen ? catId : null;

      // Fetch and update main product display
      const category = catLi.getAttribute('data-category');
      fetch(`fetch_category_products.php?category=${encodeURIComponent(category)}`)
        .then(res => res.text())
        .then(html => {
          document.getElementById('category-products').innerHTML = html;
        });
    });
    // Accessible: allow keyboard enter/space
    catLi.addEventListener('keydown', function(e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        catLi.click();
      }
    });
  });
  // Collapse dropdown if clicked elsewhere
  document.addEventListener('click', function(e) {
    if (!e.target.closest('.category-li')) {
      categoryList.querySelectorAll('.category-li').forEach(li => li.classList.remove('active'));
      categoryList.querySelectorAll('.category-items-list').forEach(ul => ul.classList.remove('open'));
      openCategoryId = null;
    }
  });
  // Prevent collapse when clicking inside dropdown & enable search when sub-item clicked
  categoryList.querySelectorAll('.category-items-list').forEach(function(subList) {
    subList.addEventListener('click', function(e) {
      e.stopPropagation();
      // Product search when sub-item clicked
      if (e.target.tagName === 'LI') {
        document.getElementById('search').value = e.target.textContent;
        document.getElementById('search').focus();
        if(typeof searchProduct === 'function') searchProduct();
      }
    });
  });
})();
</script>
<script>
// Fade/slide animation when switching category
function fadeInProducts() {
    const container = document.getElementById('category-products');
    if (!container) return;
    container.style.opacity = 0;
    container.style.transform = 'translateY(24px)';
    setTimeout(() => {
        container.style.transition = 'opacity .32s, transform .4s';
        container.style.opacity = 1;
        container.style.transform = 'translateY(0)';
    }, 80);
}

// Quantity handling and Add to Cart animation
function changeQty(btn, delta) {
    const numSpan = btn.parentElement.querySelector('.qty-num');
    let qty = parseInt(numSpan.textContent);
    qty += delta;
    if (qty < 1) qty = 1;
    numSpan.textContent = qty;
}

function addProductToCart(btn, name, price, enabled) {
    if (!enabled) return;
    // Get current qty
    const qty = parseInt(btn.parentElement.querySelector('.qty-num').textContent) || 1;
    for (let i = 0; i < qty; i++) {
        addToCart(name, price, btn);
    }
    // Animation: bounce/flash
    btn.classList.add('animate-bounce');
    setTimeout(() => btn.classList.remove('animate-bounce'), 500);
}

// Animate-bounce (minimal)
const style = document.createElement('style');
style.textContent = `
@keyframes bounce {
    0%, 100% { transform: scale(1);}
    40% { transform: scale(1.08);}
    70% { transform: scale(0.96);}
    85% { transform: scale(1.04);}
}
.animate-bounce { animation: bounce 0.44s; }
`;
document.head.appendChild(style);

// When loading new products, fade in
const origFetchCategoryProducts = window.fetch;
window.fetch = function(...args) {
    return origFetchCategoryProducts.apply(this, args).then(res => {
        if (args[0] && args[0].includes('fetch_category_products.php')) {
            setTimeout(fadeInProducts, 80);
        }
        return res;
    });
};

// Hide category cards if search bar is in use, show them if empty
const searchInput = document.getElementById('search');
const categoryProducts = document.getElementById('category-products');
const resultDiv = document.getElementById('result');

searchInput.addEventListener('input', function () {
    if (this.value.trim() !== '') {
        categoryProducts.style.display = 'none';
        resultDiv.style.display = '';
    } else {
        categoryProducts.style.display = '';
        // Optionally: Clear results
        // resultDiv.innerHTML = '';
    }
});
</script>

<script>
document.addEventListener("DOMContentLoaded", function () {
  const searchInput = document.getElementById('search');
  const categoryProducts = document.getElementById('category-products');
  const searchResults = document.getElementById('search-results-container');
  let lastCategoryHtml = "";
  
  // Helper functions for smooth fade
  function fadeOut(elem) {
    elem.classList.remove('fade-in');
    elem.classList.add('fade-out');
    elem.style.opacity = 0;
    elem.style.pointerEvents = "none";
    setTimeout(() => { elem.style.display = 'none'; elem.classList.remove('fade-out'); }, 200);
  }
  function fadeIn(elem, display = 'block') {
    elem.style.display = display;
    setTimeout(() => {
      elem.classList.add('fade-in');
      elem.style.opacity = 1;
      elem.style.pointerEvents = "";
    }, 10);
  }

  // Cache products
  function cacheCategoryCards() {
    lastCategoryHtml = categoryProducts ? categoryProducts.innerHTML : "";
  }
  cacheCategoryCards();

  // Whenever a category is clicked, update cache
  document.querySelectorAll('.category-li').forEach(function(catLi) {
    catLi.addEventListener('click', function() {
      setTimeout(cacheCategoryCards, 200);
    });
  });

  // On focus: Hide category products, show search results (empty at first)
  searchInput.addEventListener('focus', function () {
    fadeOut(categoryProducts);
    searchResults.innerHTML = "";
    fadeIn(searchResults, "block");
  });

  // On input: Fetch and show results
  searchInput.addEventListener('input', function () {
    const query = this.value.trim();
    if (query !== '') {
      fadeOut(categoryProducts);
      searchResults.innerHTML = "";
      fadeIn(searchResults, "block");
      fetch(`search_product.php?search=${encodeURIComponent(query)}`)
        .then(res => res.text())
        .then(data => {
          if (!data || !data.trim() || data.includes('No products found')) {
            searchResults.innerHTML = `
              <div class="bg-white rounded-xl shadow text-center py-14 text-gray-400 font-semibold text-lg fade-in">
                <i class="fas fa-box-open text-4xl mb-4 block text-gray-300"></i>
                No products found
              </div>
            `;
          } else {
            searchResults.innerHTML = `<div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-10 fade-in">${data}</div>`;
          }
        });
    } else {
      // If search is cleared, show category cards again
      fadeOut(searchResults);
      setTimeout(() => {
        if (categoryProducts) {
          categoryProducts.innerHTML = lastCategoryHtml;
          fadeIn(categoryProducts, "grid");
        }
      }, 200);
    }
  });

  // On blur: if input is empty, restore category grid and hide search results
  searchInput.addEventListener('blur', function () {
    if (this.value.trim() === "") {
      fadeOut(searchResults);
      setTimeout(() => {
        if (categoryProducts) {
          categoryProducts.innerHTML = lastCategoryHtml;
          fadeIn(categoryProducts, "grid");
        }
      }, 200);
    }
  });
});
</script>
<script src="https://cdn.socket.io/4.7.5/socket.io.min.js"></script>
<script>
// --- MAP AND GUIDANCE LOGIC ---
const MAP_PHYSICAL_SIZE = 300; // 3m x 3m in cm
const CANVAS_SIZE = 500;
const scaleX = CANVAS_SIZE / MAP_PHYSICAL_SIZE;
const scaleY = CANVAS_SIZE / MAP_PHYSICAL_SIZE;

const shelfRects = [
  [50, 50, 250, 80],
  [50, 130, 250, 160],
  [50, 210, 250, 240],
];

// Four corners for aisles
const aisles = {
  "Aisle 1": {x: 0, y: 0},         // Top-left
  "Aisle 2": {x: 300, y: 0},       // Top-right
  "Aisle 3": {x: 300, y: 300},     // Bottom-right
  "Aisle 4": {x: 0, y: 300},       // Bottom-left
};
const entrance = {x: 150, y: 300}; // Bottom center

let cartXY = null;
let currentHeading = null;
let guidanceAisle = null;
let cartXYHistory = [];
const MAX_XY_HISTORY = 5;

function updateCartXYWithSmoothing(newXY) {
  cartXYHistory.push(newXY);
  if (cartXYHistory.length > MAX_XY_HISTORY) cartXYHistory.shift();
  let avgX = cartXYHistory.reduce((sum, pt) => sum + pt.x, 0) / cartXYHistory.length;
  let avgY = cartXYHistory.reduce((sum, pt) => sum + pt.y, 0) / cartXYHistory.length;
  cartXY = { x: avgX, y: avgY };
}

// --- SIMPLE ASTAR PATHFINDING FOR GUIDANCE ---
function toCell(x, y) {
  const cellSize = 25; // 300/12 ~ 25, for a 12x12 grid
  return { x: Math.floor(x / cellSize), y: Math.floor(y / cellSize) };
}
function fromCell(cell) {
  const cellSize = 25;
  return { x: cell.x * cellSize + cellSize/2, y: cell.y * cellSize + cellSize/2 };
}
function heuristic(a, b) {
  return Math.abs(a.x - b.x) + Math.abs(a.y - b.y);
}
function createGuidanceGrid() {
  const gridSize = 12;
  let grid = Array.from({length: gridSize}, () => Array(gridSize).fill(0));
  // Block out shelves as obstacles
  for (const [x1, y1, x2, y2] of shelfRects) {
    const cellSize = 25;
    const gx1 = Math.floor(x1 / cellSize), gy1 = Math.floor(y1 / cellSize);
    const gx2 = Math.floor(x2 / cellSize), gy2 = Math.floor(y2 / cellSize);
    for (let x = gx1; x <= gx2; x++) {
      for (let y = gy1; y <= gy2; y++) {
        if (x >= 0 && x < gridSize && y >= 0 && y < gridSize) grid[x][y] = 1;
      }
    }
  }
  return grid;
}
function astar(grid, start, goal) {
  let open = [start];
  let cameFrom = {};
  let gScore = {};
  let key = (p) => `${p.x},${p.y}`;
  gScore[key(start)] = 0;
  let fScore = {};
  fScore[key(start)] = heuristic(start, goal);

  while (open.length > 0) {
    open.sort((a, b) => (fScore[key(a)] || 999999) - (fScore[key(b)] || 999999));
    let current = open.shift();
    if (current.x === goal.x && current.y === goal.y) {
      let path = [current];
      while (key(current) in cameFrom) {
        current = cameFrom[key(current)];
        path.push(current);
      }
      return path.reverse();
    }
    for (let [dx, dy] of [[0,1],[1,0],[0,-1],[-1,0]]) {
      let nx = current.x + dx, ny = current.y + dy;
      if (nx < 0 || ny < 0 || nx >= grid.length || ny >= grid[0].length) continue;
      if (grid[nx][ny] === 1) continue;
      let neighbor = {x: nx, y: ny};
      let tentative = gScore[key(current)] + 1;
      if (tentative < (gScore[key(neighbor)] || 999999)) {
        cameFrom[key(neighbor)] = current;
        gScore[key(neighbor)] = tentative;
        fScore[key(neighbor)] = tentative + heuristic(neighbor, goal);
        if (!open.some(p => p.x === nx && p.y === ny)) open.push(neighbor);
      }
    }
  }
  return null;
}

function drawStoreMap(highlightAisle = null) {
  const canvas = document.getElementById('store-map-canvas');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  ctx.clearRect(0, 0, canvas.width, canvas.height);

  // Draw shelves
  ctx.fillStyle = "#444";
  for (const [x1, y1, x2, y2] of shelfRects) {
    ctx.fillRect(x1 * scaleX, y1 * scaleY, (x2 - x1) * scaleX, (y2 - y1) * scaleY);
  }
  // Draw aisles (corners)
  for (const [aisleName, pos] of Object.entries(aisles)) {
    ctx.beginPath();
    ctx.arc(pos.x * scaleX, pos.y * scaleY, 20, 0, 2 * Math.PI);
    ctx.fillStyle = (highlightAisle && aisleName === highlightAisle) ? '#ff5722' : '#2196f3';
    ctx.fill();
    ctx.strokeStyle = '#fff';
    ctx.lineWidth = 2;
    ctx.stroke();
    ctx.fillStyle = '#fff';
    ctx.font = "bold 10px Arial";
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText(aisleName, pos.x * scaleX, pos.y * scaleY - 25);
  }
  // Draw entrance
  ctx.beginPath();
  ctx.arc(entrance.x * scaleX, entrance.y * scaleY, 18, 0, 2 * Math.PI);
  ctx.fillStyle = '#2ecc40';
  ctx.fill();
  ctx.strokeStyle = '#fff';
  ctx.lineWidth = 2;
  ctx.stroke();
  ctx.fillStyle = '#fff';
  ctx.font = "bold 9px Arial";
  ctx.fillText("ENTRANCE", entrance.x * scaleX, entrance.y * scaleY - 22);
}

function drawCartOnMapWithAisle(cartX, cartY, highlightAisle) {
  drawStoreMap(highlightAisle);
  const canvas = document.getElementById('store-map-canvas');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');

  // Draw guidance path if guidanceAisle is set
  if (guidanceAisle && aisles[guidanceAisle]) {
    let grid = createGuidanceGrid();
    let startCell = toCell(cartX, cartY);
    let goalCell = toCell(aisles[guidanceAisle].x, aisles[guidanceAisle].y);
    let path = astar(grid, startCell, goalCell);
    if (path && path.length > 1) {
      ctx.beginPath();
      let first = fromCell(path[0]);
      ctx.moveTo(first.x * scaleX, first.y * scaleY);
      for (let pt of path) {
        let {x, y} = fromCell(pt);
        ctx.lineTo(x * scaleX, y * scaleY);
      }
      ctx.strokeStyle = "#FFD700";
      ctx.lineWidth = 4;
      ctx.setLineDash([10, 8]);
      ctx.stroke();
      ctx.setLineDash([]);
    }
  }

  // Draw cart
  ctx.beginPath();
  ctx.arc(cartX * scaleX, cartY * scaleY, 13, 0, 2 * Math.PI);
  ctx.fillStyle = "#FFD700";
  ctx.fill();
  ctx.strokeStyle = "#000";
  ctx.lineWidth = 2;
  ctx.stroke();
  ctx.font = "bold 13px Arial";
  ctx.textAlign = 'center';
  ctx.textBaseline = 'bottom';
  ctx.fillStyle = "#000";
  ctx.fillText("Cart", cartX * scaleX, cartY * scaleY - 16);

  // Draw facing direction arrow if heading is available
  if (typeof currentHeading === "number" && !isNaN(currentHeading)) {
    const len = 28;
    const rad = ((360 - currentHeading + 90) * Math.PI) / 180;
    const arrowX = cartX * scaleX + len * Math.cos(rad);
    const arrowY = cartY * scaleY + len * Math.sin(rad);

    ctx.beginPath();
    ctx.moveTo(cartX * scaleX, cartY * scaleY);
    ctx.lineTo(arrowX, arrowY);
    ctx.strokeStyle = "#2563eb";
    ctx.lineWidth = 4;
    ctx.stroke();

    ctx.beginPath();
    ctx.arc(arrowX, arrowY, 5, 0, 2 * Math.PI);
    ctx.fillStyle = "#2563eb";
    ctx.fill();

    ctx.font = "bold 10px Arial";
    ctx.fillStyle = "#2563eb";
    ctx.textAlign = "left";
    ctx.textBaseline = "middle";
    ctx.fillText("Facing: " + Math.round(currentHeading) + "°", cartX * scaleX + 20, cartY * scaleY + 20);
  }
}

function redrawCartAndGuidance() {
  if (!cartXY) return;
  if (guidanceAisle && aisles[guidanceAisle]) {
    drawCartOnMapWithAisle(cartXY.x, cartXY.y, guidanceAisle);
  } else {
    drawCartOnMapWithAisle(cartXY.x, cartXY.y, null);
  }
}

function showCartLocation() {
  document.getElementById('map-modal').style.display = 'flex';
  document.getElementById('map-title').innerText = "Store Map - Cart Location";
  guidanceAisle = null;
  redrawCartAndGuidance();
}

window.addEventListener('deviceorientationabsolute', handleOrientation, true);
window.addEventListener('deviceorientation', handleOrientation, true);
function handleOrientation(event) {
  let heading = event.alpha;
  if (typeof event.webkitCompassHeading !== "undefined") {
    heading = event.webkitCompassHeading;
  }
  if (typeof heading === "number") {
    currentHeading = heading;
  }
  redrawCartAndGuidance();
}

// BLE trilateration cart position via Socket.IO
const socket = io('https://10.255.146.244:5050', { transports: ['websocket'] });
socket.on('cart_position', function(data) {
  if (typeof data.x === "number" && typeof data.y === "number") {
    updateCartXYWithSmoothing({x: data.x, y: data.y});
    redrawCartAndGuidance();
  }
});

// "Show Cart Location" button
document.getElementById('showCartLocationBtn').addEventListener('click', function() {
  guidanceAisle = null;
  document.getElementById('map-modal').style.display = 'flex';
  document.getElementById('map-title').innerText = "Store Map - Cart Location";
  redrawCartAndGuidance();
});

// Modal close handler (optional)
document.querySelector('#map-modal .fa-times').addEventListener('click', function() {
  // Optionally clear cartXYHistory if needed
});

// "Locate Product" handler (for aisle guidance)
window.showProductLocation = function(aisle) {
  guidanceAisle = aisle;
  document.getElementById('map-modal').style.display = 'flex';
  document.getElementById('map-title').innerText = "Store Map - " + aisle;
  redrawCartAndGuidance();
};
</script>

</body>
</html>