<?php
session_start();

// Error reporting for development
ini_set("display_errors", 1);
error_reporting(E_ALL);

// -------------------------
// ✅ DATABASE CONNECTION (TEMPORARY MOCK FOR UI DESIGN)
// -------------------------
// Mock database connection for UI design purposes
$conn = null; // Temporarily disabled for UI redesign

// Mock data for UI design
$totalProducts = 150;
$totalAisles = 12;
$totalAdmins = 5;
$current_role = 'Main Admin';
$is_main_admin = true;

// -------------------------
// ✅ SESSION & SECURITY (SIMPLIFIED FOR UI DESIGN)
// -------------------------
// Temporarily simplified for UI design purposes
/*
if (!isset($_SESSION['admin'])) {
    session_destroy();
    if ($_SERVER['PHP_SELF'] != '/dashboard.php') {
        header("Location: login.php");
        exit();
    }
}
*/

/*
// ---- COMMENTING OUT ALL POST HANDLERS FOR UI DESIGN ----
// Edit account via modal
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action']==='edit_account_modal' && $is_main_admin) {
    // ... (commented out for UI design)
}
// ... (all other POST handlers commented out for UI design)
*/

// Mock accounts for UI display
$accounts = [
    ['id' => 1, 'first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john@example.com', 'contact' => '+1234567890', 'username' => 'johndoe', 'role' => 'Main Admin'],
    ['id' => 2, 'first_name' => 'Jane', 'last_name' => 'Smith', 'email' => 'jane@example.com', 'contact' => '+1234567891', 'username' => 'janesmith', 'role' => 'Manager'],
];

/*
// -------------------------
// ALL POST HANDLERS COMMENTED OUT FOR UI DESIGN 
// -------------------------

// 🔑 LOGIN HANDLER
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    // ... (all login logic commented out for UI design)
}

// AJAX ENDPOINT FOR SUPER ADMIN ACCOUNT CREATION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'superadmin_create_account') {
    // ... (all account creation logic commented out for UI design)
}

// Handle admin account edit (for Main Admin only)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['edit_account']) && isset($_POST['edit_account_id']) && $current_role === 'Main Admin') {
    // ... (all edit logic commented out for UI design)
}

// 🗂️ AISLE HANDLERS
// All aisle handlers commented out for UI design

// 📦 PRODUCT HANDLER
// All product handlers commented out for UI design

// All other database operations commented out for UI design

*/ 

// -------------------------
// ✅ MOCK DATA FOR UI DESIGN
// -------------------------


?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ShopEase Admin Dashboard</title>
  <!-- Google Fonts - Inter for modern typography -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <!-- Lucide Icons for modern iconography -->
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
  <style>
    /* ====== MODERN RESET & BASE STYLES ====== */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    :root {
      /* Modern Color Palette */
      --primary-50: #f0f9ff;
      --primary-100: #e0f2fe;
      --primary-500: #0ea5e9;
      --primary-600: #0284c7;
      --primary-700: #0369a1;
      
      --gray-50: #f8fafc;
      --gray-100: #f1f5f9;
      --gray-200: #e2e8f0;
      --gray-300: #cbd5e1;
      --gray-400: #94a3b8;
      --gray-500: #64748b;
      --gray-600: #475569;
      --gray-700: #334155;
      --gray-800: #1e293b;
      --gray-900: #0f172a;

      --success-500: #10b981;
      --warning-500: #f59e0b;
      --error-500: #ef4444;

      --sidebar-width: 280px;
      --topbar-height: 80px;
    }

    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      background: var(--gray-50);
      color: var(--gray-800);
      line-height: 1.6;
      overflow-x: hidden;
    }

    /* ====== TOP NAVIGATION BAR ====== */
    .topbar {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      height: var(--topbar-height);
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border-bottom: 1px solid var(--gray-200);
      z-index: 1000;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 2rem;
    }

    .topbar-left {
      display: flex;
      align-items: center;
      gap: 2rem;
    }

    .logo {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      font-weight: 800;
      font-size: 1.5rem;
      color: var(--primary-600);
    }

    .logo-icon {
      width: 40px;
      height: 40px;
      background: linear-gradient(135deg, var(--primary-500), var(--primary-600));
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
    }

    .search-container {
      position: relative;
      width: 400px;
    }

    .search-input {
      width: 100%;
      padding: 0.75rem 1rem 0.75rem 3rem;
      border: 2px solid var(--gray-200);
      border-radius: 12px;
      font-size: 0.95rem;
      font-weight: 500;
      transition: all 0.2s ease;
      background: var(--gray-50);
    }

    .search-input:focus {
      outline: none;
      border-color: var(--primary-500);
      background: white;
      box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
    }

    .search-icon {
      position: absolute;
      left: 1rem;
      top: 50%;
      transform: translateY(-50%);
      color: var(--gray-400);
      width: 20px;
      height: 20px;
    }

    .topbar-right {
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .quick-actions {
      display: flex;
      gap: 0.5rem;
    }

    .quick-action-btn {
      width: 44px;
      height: 44px;
      border: none;
      background: var(--gray-100);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.2s ease;
      color: var(--gray-600);
    }

    .quick-action-btn:hover {
      background: var(--primary-100);
      color: var(--primary-600);
      transform: translateY(-1px);
    }

    .user-profile {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.5rem;
      border-radius: 12px;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .user-profile:hover {
      background: var(--gray-100);
    }

    .user-avatar {
      width: 40px;
      height: 40px;
      border-radius: 10px;
      background: linear-gradient(135deg, var(--primary-500), var(--primary-600));
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: 600;
      font-size: 0.9rem;
    }

    .user-info h4 {
      font-size: 0.9rem;
      font-weight: 600;
      color: var(--gray-800);
    }

    .user-info p {
      font-size: 0.8rem;
      color: var(--gray-500);
    }

    /* ====== MODERN SIDEBAR ====== */
    .sidebar {
      position: fixed;
      top: var(--topbar-height);
      left: 0;
      width: var(--sidebar-width);
      height: calc(100vh - var(--topbar-height));
      background: white;
      border-right: 1px solid var(--gray-200);
      padding: 2rem 0;
      z-index: 900;
      transition: all 0.3s ease;
    }

    .sidebar-section {
      margin-bottom: 2rem;
    }

    .sidebar-label {
      font-size: 0.75rem;
      font-weight: 700;
      color: var(--gray-400);
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-bottom: 0.75rem;
      padding: 0 1.5rem;
    }

    .nav-item {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.75rem 1.5rem;
      color: var(--gray-600);
      text-decoration: none;
      font-weight: 500;
      font-size: 0.95rem;
      transition: all 0.2s ease;
      position: relative;
    }

    .nav-item::before {
      content: '';
      position: absolute;
      left: 0;
      top: 0;
      bottom: 0;
      width: 4px;
      background: var(--primary-500);
      transform: scaleY(0);
      transition: transform 0.2s ease;
    }

    .nav-item:hover {
      background: var(--primary-50);
      color: var(--primary-700);
    }

    .nav-item.active {
      background: var(--primary-50);
      color: var(--primary-700);
      font-weight: 600;
    }

    .nav-item.active::before {
      transform: scaleY(1);
    }

    .nav-icon {
      width: 20px;
      height: 20px;
      color: currentColor;
    }

    /* ====== MAIN CONTENT AREA ====== */
    .main-wrapper {
      margin-left: var(--sidebar-width);
      margin-top: var(--topbar-height);
      min-height: calc(100vh - var(--topbar-height));
      background: var(--gray-50);
    }

    .main-content {
      padding: 2rem;
    }

    .section {
      display: none;
    }

    .section.active {
      display: block;
    }

    /* ====== MODERN HOME SECTION ====== */
    .dashboard-header {
      margin-bottom: 2rem;
    }

    .dashboard-title {
      font-size: 2rem;
      font-weight: 800;
      color: var(--gray-900);
      margin-bottom: 0.5rem;
    }

    .dashboard-subtitle {
      font-size: 1.1rem;
      color: var(--gray-500);
      font-weight: 500;
    }

    /* ====== MODERN METRIC CARDS ====== */
    .metrics-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 1.5rem;
      margin-bottom: 2rem;
    }

    .metric-card {
      background: white;
      border-radius: 20px;
      padding: 2rem;
      border: 1px solid var(--gray-200);
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .metric-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, var(--primary-500), var(--primary-600));
    }

    .metric-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
      border-color: var(--primary-200);
    }

    .metric-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 1rem;
    }

    .metric-title {
      font-size: 0.9rem;
      font-weight: 600;
      color: var(--gray-600);
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    .metric-icon {
      width: 48px;
      height: 48px;
      border-radius: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
    }

    .metric-icon.products {
      background: linear-gradient(135deg, #10b981, #059669);
    }

    .metric-icon.aisles {
      background: linear-gradient(135deg, #3b82f6, #2563eb);
    }

    .metric-icon.admins {
      background: linear-gradient(135deg, #8b5cf6, #7c3aed);
    }

    .metric-value {
      font-size: 2.5rem;
      font-weight: 800;
      color: var(--gray-900);
      margin-bottom: 0.5rem;
    }

    .metric-label {
      font-size: 0.95rem;
      color: var(--gray-500);
      font-weight: 500;
    }

    .metric-trend {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      margin-top: 1rem;
      font-size: 0.85rem;
      font-weight: 600;
      color: var(--success-500);
    }

    /* ====== QUICK ACTIONS SECTION ====== */
    .quick-actions-section {
      margin-bottom: 2rem;
    }

    .section-title {
      font-size: 1.25rem;
      font-weight: 700;
      color: var(--gray-900);
      margin-bottom: 1rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .quick-actions-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 1rem;
    }

    .quick-action-card {
      background: white;
      border-radius: 16px;
      padding: 1.5rem;
      border: 1px solid var(--gray-200);
      cursor: pointer;
      transition: all 0.2s ease;
      text-decoration: none;
      color: inherit;
    }

    .quick-action-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 24px rgba(0, 0, 0, 0.08);
      border-color: var(--primary-300);
    }

    .quick-action-header {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      margin-bottom: 0.75rem;
    }

    .quick-action-icon {
      width: 40px;
      height: 40px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: var(--primary-100);
      color: var(--primary-600);
    }

    .quick-action-title {
      font-size: 1rem;
      font-weight: 600;
      color: var(--gray-900);
    }

    .quick-action-desc {
      font-size: 0.9rem;
      color: var(--gray-500);
      line-height: 1.5;
    }

    /* ====== RESPONSIVE DESIGN ====== */
    @media (max-width: 1024px) {
      .search-container {
        width: 300px;
      }
      
      .metrics-grid {
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      }
    }

    @media (max-width: 768px) {
      :root {
        --sidebar-width: 0px;
      }
      
      .topbar {
        padding: 0 1rem;
      }
      
      .search-container {
        display: none;
      }
      
      .sidebar {
        transform: translateX(-100%);
      }
      
      .main-wrapper {
        margin-left: 0;
      }
      
      .main-content {
        padding: 1rem;
      }
      
      .metrics-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
      }
      
      .metric-card {
        padding: 1.5rem;
      }
    }

    /* ====== SMOOTH ANIMATIONS ====== */
    * {
      transition: color 0.2s ease, background-color 0.2s ease, border-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
    }

    /* Hide original styles that might interfere */
    .side-widgets {
      display: none;
    }
  </style>
</head>
<body>
  <!-- Top Navigation Bar -->
  <div class="topbar">
    <div class="topbar-left">
      <div class="logo">
        <div class="logo-icon">
          <i data-lucide="shopping-cart"></i>
        </div>
        ShopEase
      </div>
      
      <div class="search-container">
        <input type="text" class="search-input" placeholder="Search products, categories, or orders...">
        <i data-lucide="search" class="search-icon"></i>
      </div>
    </div>
    
    <div class="topbar-right">
      <div class="quick-actions">
        <button class="quick-action-btn" title="Notifications">
          <i data-lucide="bell"></i>
        </button>
        <button class="quick-action-btn" title="Settings">
          <i data-lucide="settings"></i>
        </button>
        <button class="quick-action-btn" title="Help">
          <i data-lucide="help-circle"></i>
        </button>
      </div>
      
      <div class="user-profile">
        <div class="user-avatar">
          JD
        </div>
        <div class="user-info">
          <h4>John Doe</h4>
          <p>Main Admin</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Modern Sidebar -->
  <div class="sidebar">
    <div class="sidebar-section">
      <div class="sidebar-label">Main</div>
      <a href="#home" onclick="showSection('home', this)" class="nav-item active">
        <i data-lucide="home" class="nav-icon"></i>
        Dashboard
      </a>
      <a href="#product" onclick="showSection('product', this)" class="nav-item">
        <i data-lucide="package" class="nav-icon"></i>
        Products
      </a>
      <a href="#aisle" onclick="showSection('aisle', this)" class="nav-item">
        <i data-lucide="layout-grid" class="nav-icon"></i>
        Categories
      </a>
    </div>
    
    <div class="sidebar-section">
      <div class="sidebar-label">Management</div>
      <a href="#admin" onclick="showSection('admin', this)" class="nav-item">
        <i data-lucide="users" class="nav-icon"></i>
        Admin
      </a>
      <a href="#" class="nav-item">
        <i data-lucide="bar-chart" class="nav-icon"></i>
        Reports
      </a>
      <a href="#" class="nav-item">
        <i data-lucide="settings" class="nav-icon"></i>
        Settings
      </a>
    </div>
    
    <div class="sidebar-section">
      <div class="sidebar-label">Account</div>
      <a href="login.php?logout=true" class="nav-item">
        <i data-lucide="log-out" class="nav-icon"></i>
        Logout
      </a>
    </div>
  </div>

  <!-- Main Content Wrapper -->
  <div class="main-wrapper">
    <div class="main-content">
      
      <!-- Home Section -->
      <div id="home" class="section active">
        <div class="dashboard-header">
          <h1 class="dashboard-title">Welcome back, John! 👋</h1>
          <p class="dashboard-subtitle">Here's what's happening with your store today.</p>
        </div>
        
        <!-- Key Metrics -->
        <div class="metrics-grid">
          <div class="metric-card">
            <div class="metric-header">
              <span class="metric-title">Total Products</span>
              <div class="metric-icon products">
                <i data-lucide="package"></i>
              </div>
            </div>
            <div class="metric-value"><?php echo $totalProducts; ?></div>
            <div class="metric-label">Active inventory items</div>
            <div class="metric-trend">
              <i data-lucide="trending-up"></i>
              <span>+12% from last month</span>
            </div>
          </div>
          
          <div class="metric-card">
            <div class="metric-header">
              <span class="metric-title">Categories</span>
              <div class="metric-icon aisles">
                <i data-lucide="layout-grid"></i>
              </div>
            </div>
            <div class="metric-value"><?php echo $totalAisles; ?></div>
            <div class="metric-label">Product categories</div>
            <div class="metric-trend">
              <i data-lucide="trending-up"></i>
              <span>+2 new this week</span>
            </div>
          </div>
          
          <div class="metric-card">
            <div class="metric-header">
              <span class="metric-title">Staff Members</span>
              <div class="metric-icon admins">
                <i data-lucide="users"></i>
              </div>
            </div>
            <div class="metric-value"><?php echo $totalAdmins; ?></div>
            <div class="metric-label">Active admin accounts</div>
            <div class="metric-trend">
              <i data-lucide="minus"></i>
              <span>No change</span>
            </div>
          </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="quick-actions-section">
          <h2 class="section-title">
            <i data-lucide="zap"></i>
            Quick Actions
          </h2>
          <div class="quick-actions-grid">
            <a href="#product" onclick="showSection('product', this)" class="quick-action-card">
              <div class="quick-action-header">
                <div class="quick-action-icon">
                  <i data-lucide="plus"></i>
                </div>
                <h3 class="quick-action-title">Add New Product</h3>
              </div>
              <p class="quick-action-desc">Quickly add a new product to your inventory with pricing and category information.</p>
            </a>
            
            <a href="#product" onclick="showSection('product', this)" class="quick-action-card">
              <div class="quick-action-header">
                <div class="quick-action-icon">
                  <i data-lucide="edit"></i>
                </div>
                <h3 class="quick-action-title">Manage Products</h3>
              </div>
              <p class="quick-action-desc">Edit product details, update pricing, and manage inventory availability.</p>
            </a>
            
            <a href="#" class="quick-action-card">
              <div class="quick-action-header">
                <div class="quick-action-icon">
                  <i data-lucide="bar-chart"></i>
                </div>
                <h3 class="quick-action-title">View Reports</h3>
              </div>
              <p class="quick-action-desc">Access detailed analytics and reports on sales, inventory, and performance metrics.</p>
            </a>
            
            <a href="#aisle" onclick="showSection('aisle', this)" class="quick-action-card">
              <div class="quick-action-header">
                <div class="quick-action-icon">
                  <i data-lucide="grid-3x3"></i>
                </div>
                <h3 class="quick-action-title">Organize Categories</h3>
              </div>
              <p class="quick-action-desc">Create and manage product categories to keep your inventory well-organized.</p>
            </a>
          </div>
        </div>
      </div>

      <!-- Category Management Section -->
      <div id="aisle" class="section">
        <div class="dashboard-header">
          <h1 class="dashboard-title">Category Management</h1>
          <p class="dashboard-subtitle">Organize your products into categories for better navigation.</p>
        </div>
        
        <!-- Add Category Form -->
        <div style="background: white; border-radius: 20px; padding: 2rem; margin-bottom: 2rem; border: 1px solid var(--gray-200);">
          <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--gray-900); margin-bottom: 1.5rem;">Add New Category</h3>
          <form method="post" style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 1rem; align-items: end;">
            <div>
              <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--gray-700);">Category Name</label>
              <input type="text" name="category" placeholder="e.g., Electronics, Groceries" required 
                style="width: 100%; padding: 0.75rem 1rem; border: 2px solid var(--gray-200); border-radius: 12px; font-size: 0.95rem; transition: border-color 0.2s ease;">
            </div>
            <div>
              <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--gray-700);">Aisle Number</label>
              <input type="text" name="aisle" placeholder="e.g., A1, B2" required 
                style="width: 100%; padding: 0.75rem 1rem; border: 2px solid var(--gray-200); border-radius: 12px; font-size: 0.95rem; transition: border-color 0.2s ease;">
            </div>
            <button type="submit" name="add_aisle" 
              style="background: linear-gradient(135deg, var(--primary-500), var(--primary-600)); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 12px; font-weight: 600; cursor: pointer; transition: all 0.2s ease;">
              Add Category
            </button>
          </form>
        </div>
        
        <!-- Mock Categories Display -->
        <div style="background: white; border-radius: 20px; padding: 2rem; border: 1px solid var(--gray-200);">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--gray-900);">Existing Categories</h3>
            <button type="button" style="background: var(--primary-100); color: var(--primary-600); border: none; padding: 0.5rem 1rem; border-radius: 8px; font-weight: 500; cursor: pointer;">
              Edit Categories
            </button>
          </div>
          
          <!-- Mock Category List -->
          <div style="display: grid; gap: 1rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: var(--gray-50); border-radius: 12px;">
              <div>
                <span style="font-weight: 600; color: var(--gray-900);">Electronics</span>
                <span style="margin-left: 1rem; color: var(--gray-500);">Aisle A1</span>
              </div>
              <span style="background: var(--success-500); color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.8rem; font-weight: 500;">12 Products</span>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: var(--gray-50); border-radius: 12px;">
              <div>
                <span style="font-weight: 600; color: var(--gray-900);">Groceries</span>
                <span style="margin-left: 1rem; color: var(--gray-500);">Aisle B2</span>
              </div>
              <span style="background: var(--success-500); color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.8rem; font-weight: 500;">8 Products</span>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: var(--gray-50); border-radius: 12px;">
              <div>
                <span style="font-weight: 600; color: var(--gray-900);">Clothing</span>
                <span style="margin-left: 1rem; color: var(--gray-500);">Aisle C3</span>
              </div>
              <span style="background: var(--success-500); color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.8rem; font-weight: 500;">25 Products</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Product Management Section -->
      <div id="product" class="section">
        <div class="dashboard-header">
          <h1 class="dashboard-title">Product Management</h1>
          <p class="dashboard-subtitle">Add, edit, and organize your product inventory.</p>
        </div>
        
        <!-- Add Product Form -->
        <div style="background: white; border-radius: 20px; padding: 2rem; margin-bottom: 2rem; border: 1px solid var(--gray-200);">
          <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--gray-900); margin-bottom: 1.5rem;">Add New Product</h3>
          <form method="post" enctype="multipart/form-data" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
            <div>
              <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--gray-700);">Product Name</label>
              <input type="text" name="name" placeholder="Enter product name" required 
                style="width: 100%; padding: 0.75rem 1rem; border: 2px solid var(--gray-200); border-radius: 12px; font-size: 0.95rem;">
            </div>
            <div>
              <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--gray-700);">Price (₱)</label>
              <input type="number" step="0.01" name="price" placeholder="0.00" required 
                style="width: 100%; padding: 0.75rem 1rem; border: 2px solid var(--gray-200); border-radius: 12px; font-size: 0.95rem;">
            </div>
            <div>
              <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--gray-700);">Barcode</label>
              <input type="text" name="barcode" placeholder="Enter barcode" required 
                style="width: 100%; padding: 0.75rem 1rem; border: 2px solid var(--gray-200); border-radius: 12px; font-size: 0.95rem;">
            </div>
            <div>
              <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--gray-700);">Category</label>
              <select name="category" required style="width: 100%; padding: 0.75rem 1rem; border: 2px solid var(--gray-200); border-radius: 12px; font-size: 0.95rem;">
                <option value="">Select Category</option>
                <option value="Electronics">Electronics</option>
                <option value="Groceries">Groceries</option>
                <option value="Clothing">Clothing</option>
              </select>
            </div>
            <div style="grid-column: 1/-1;">
              <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--gray-700);">Description</label>
              <textarea name="description" placeholder="Enter product details" 
                style="width: 100%; padding: 0.75rem 1rem; border: 2px solid var(--gray-200); border-radius: 12px; min-height: 80px; font-size: 0.95rem;"></textarea>
            </div>
            <div style="grid-column: 1/-1; text-align: right;">
              <button type="submit" name="add_product" 
                style="background: linear-gradient(135deg, var(--primary-500), var(--primary-600)); color: white; border: none; padding: 0.75rem 2rem; border-radius: 12px; font-weight: 600; cursor: pointer; font-size: 0.95rem;">
                Add Product
              </button>
            </div>
          </form>
        </div>
        
        <!-- Mock Products Display -->
        <div style="background: white; border-radius: 20px; padding: 2rem; border: 1px solid var(--gray-200);">
          <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--gray-900); margin-bottom: 1.5rem;">Product Inventory</h3>
          
          <!-- Sample products -->
          <div style="display: grid; gap: 1rem;">
            <div style="display: grid; grid-template-columns: auto 1fr auto auto auto; gap: 1rem; align-items: center; padding: 1rem; background: var(--gray-50); border-radius: 12px;">
              <div style="width: 40px; height: 40px; background: var(--primary-100); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--primary-600); font-weight: 600;">📱</div>
              <div>
                <div style="font-weight: 600; color: var(--gray-900);">iPhone 14 Pro</div>
                <div style="font-size: 0.9rem; color: var(--gray-500);">Electronics • A1</div>
              </div>
              <div style="text-align: right;">
                <div style="font-weight: 600; color: var(--gray-900);">₱59,999.00</div>
                <div style="font-size: 0.85rem; color: var(--gray-500);">SKU: IP14P128</div>
              </div>
              <span style="background: var(--success-500); color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.8rem; font-weight: 500;">In Stock</span>
              <button style="background: var(--gray-100); color: var(--gray-600); border: none; padding: 0.5rem; border-radius: 8px; cursor: pointer;">
                ⋯
              </button>
            </div>
            
            <div style="display: grid; grid-template-columns: auto 1fr auto auto auto; gap: 1rem; align-items: center; padding: 1rem; background: var(--gray-50); border-radius: 12px;">
              <div style="width: 40px; height: 40px; background: var(--warning-500); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600;">🥛</div>
              <div>
                <div style="font-weight: 600; color: var(--gray-900);">Fresh Milk 1L</div>
                <div style="font-size: 0.9rem; color: var(--gray-500);">Groceries • B2</div>
              </div>
              <div style="text-align: right;">
                <div style="font-weight: 600; color: var(--gray-900);">₱89.50</div>
                <div style="font-size: 0.85rem; color: var(--gray-500);">SKU: MILK1L</div>
              </div>
              <span style="background: var(--warning-500); color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.8rem; font-weight: 500;">Low Stock</span>
              <button style="background: var(--gray-100); color: var(--gray-600); border: none; padding: 0.5rem; border-radius: 8px; cursor: pointer;">
                ⋯
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Admin Management Section -->
      <div id="admin" class="section">
        <div class="dashboard-header">
          <h1 class="dashboard-title">Admin Management</h1>
          <p class="dashboard-subtitle">Manage administrator accounts and permissions.</p>
        </div>
        
        <!-- Add Admin Form (for Main Admin only) -->
        <?php if ($is_main_admin): ?>
        <div style="background: white; border-radius: 20px; padding: 2rem; margin-bottom: 2rem; border: 1px solid var(--gray-200);">
          <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--gray-900); margin-bottom: 1.5rem;">Add New Administrator</h3>
          <form method="post" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
            <div>
              <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--gray-700);">First Name</label>
              <input type="text" name="first_name" required 
                style="width: 100%; padding: 0.75rem 1rem; border: 2px solid var(--gray-200); border-radius: 12px; font-size: 0.95rem;">
            </div>
            <div>
              <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--gray-700);">Last Name</label>
              <input type="text" name="last_name" required 
                style="width: 100%; padding: 0.75rem 1rem; border: 2px solid var(--gray-200); border-radius: 12px; font-size: 0.95rem;">
            </div>
            <div>
              <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--gray-700);">Email</label>
              <input type="email" name="email" required 
                style="width: 100%; padding: 0.75rem 1rem; border: 2px solid var(--gray-200); border-radius: 12px; font-size: 0.95rem;">
            </div>
            <div>
              <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--gray-700);">Username</label>
              <input type="text" name="username" required 
                style="width: 100%; padding: 0.75rem 1rem; border: 2px solid var(--gray-200); border-radius: 12px; font-size: 0.95rem;">
            </div>
            <div>
              <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--gray-700);">Role</label>
              <select name="role" required style="width: 100%; padding: 0.75rem 1rem; border: 2px solid var(--gray-200); border-radius: 12px; font-size: 0.95rem;">
                <option value="">Select Role</option>
                <option value="Main Admin">Main Admin</option>
                <option value="Manager">Manager</option>
                <option value="Inventory Staff">Inventory Staff</option>
              </select>
            </div>
            <div>
              <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--gray-700);">Password</label>
              <input type="password" name="password" required 
                style="width: 100%; padding: 0.75rem 1rem; border: 2px solid var(--gray-200); border-radius: 12px; font-size: 0.95rem;">
            </div>
            <div style="grid-column: 1/-1; text-align: right;">
              <button type="submit" name="add_admin" 
                style="background: linear-gradient(135deg, var(--primary-500), var(--primary-600)); color: white; border: none; padding: 0.75rem 2rem; border-radius: 12px; font-weight: 600; cursor: pointer; font-size: 0.95rem;">
                Add Administrator
              </button>
            </div>
          </form>
        </div>
        <?php endif; ?>
        
        <!-- Admin List -->
        <div style="background: white; border-radius: 20px; padding: 2rem; border: 1px solid var(--gray-200);">
          <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--gray-900); margin-bottom: 1.5rem;">Administrator Accounts</h3>
          
          <div style="display: grid; gap: 1rem;">
            <?php foreach ($accounts as $acc): ?>
            <div style="display: grid; grid-template-columns: auto 1fr auto auto; gap: 1rem; align-items: center; padding: 1rem; background: var(--gray-50); border-radius: 12px;">
              <div style="width: 48px; height: 48px; background: linear-gradient(135deg, var(--primary-500), var(--primary-600)); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600;">
                <?= strtoupper(substr($acc['first_name'], 0, 1) . substr($acc['last_name'], 0, 1)) ?>
              </div>
              <div>
                <div style="font-weight: 600; color: var(--gray-900);"><?= htmlspecialchars($acc['first_name'] . ' ' . $acc['last_name']) ?></div>
                <div style="font-size: 0.9rem; color: var(--gray-500);"><?= htmlspecialchars($acc['email']) ?></div>
                <div style="font-size: 0.85rem; color: var(--gray-400);">Contact: <?= htmlspecialchars($acc['contact']) ?></div>
              </div>
              <div style="text-align: center;">
                <div style="background: var(--primary-100); color: var(--primary-700); padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.8rem; font-weight: 500;">
                  <?= htmlspecialchars($acc['role']) ?>
                </div>
              </div>
              <?php if ($is_main_admin): ?>
              <button style="background: var(--gray-100); color: var(--gray-600); border: none; padding: 0.5rem; border-radius: 8px; cursor: pointer;">
                ⋯
              </button>
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Initialize Lucide Icons -->
  <script>
    // Initialize Lucide icons
    lucide.createIcons();
    
    // Section switching functionality
    function showSection(sectionId, element) {
      // Hide all sections
      const sections = document.querySelectorAll('.section');
      sections.forEach(section => section.classList.remove('active'));
      
      // Show selected section
      document.getElementById(sectionId).classList.add('active');
      
      // Update navigation active state
      const navItems = document.querySelectorAll('.nav-item');
      navItems.forEach(item => item.classList.remove('active'));
      
      if (element) {
        element.classList.add('active');
      }
      
      // Store active section in sessionStorage
      sessionStorage.setItem('activeSection', sectionId);
    }
    
    // Restore active section on page load
    document.addEventListener('DOMContentLoaded', function() {
      const activeSection = sessionStorage.getItem('activeSection') || 'home';
      const navItem = document.querySelector(`[href="#${activeSection}"]`);
      if (navItem) {
        showSection(activeSection, navItem);
      }
    });
    
    // Search functionality
    document.querySelector('.search-input')?.addEventListener('keypress', function(e) {
      if (e.key === 'Enter') {
        const query = this.value.trim();
        if (query) {
          console.log('Searching for:', query);
          // Add search functionality here
        }
      }
    });
    
    // Quick action notifications
    document.querySelectorAll('.quick-action-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        const title = this.getAttribute('title');
        console.log(`${title} clicked`);
        // Add notification functionality here
      });
    });
  </script>
</body>
</html>