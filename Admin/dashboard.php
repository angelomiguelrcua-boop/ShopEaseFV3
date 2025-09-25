<?php
session_start();

// Error reporting for development
ini_set("display_errors", 1);
error_reporting(E_ALL);

// -------------------------
// ✅ DATABASE CONNECTION
// -------------------------
$conn = new mysqli('localhost', 'root', '', 'ecommerce_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}



// -------------------------
// ✅ SESSION & SECURITY
// -------------------------
if (!isset($_SESSION['admin'])) {
    session_destroy();
    if ($_SERVER['PHP_SELF'] != '/dashboard.php') {
        header("Location: login.php");
        exit();
    }
}

// ---- FIX: define role vars BEFORE POST handlers ----
$current_role = $_SESSION['role'] ?? '';
$is_main_admin = ($current_role === 'Main Admin');

// -------------------------
// LOG ACTIVITY FUNCTION
// -------------------------


// Edit account via modal (no ID in log)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action']==='edit_account_modal' && $is_main_admin) {
    $id = intval($_POST['edit_account_id']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $stmt = $conn->prepare("UPDATE admins SET first_name=?, last_name=?, email=?, contact=?, role=? WHERE id=?");
    $stmt->bind_param("sssssi", $first_name, $last_name, $_POST['email'], $_POST['contact'], $_POST['role'], $id);
    if ($stmt->execute()) {
        log_activity($conn, "Edited admin account: {$first_name} {$last_name}");
        echo json_encode(['success'=>true]);
    } else {
        echo json_encode(['success'=>false, 'message'=>'Database error.']);
    }
    exit;
}

// Delete account via modal (no ID in log)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action']==='delete_account_modal' && $is_main_admin) {
    $id = intval($_POST['delete_account_id']);
    $stmt = $conn->prepare("SELECT first_name, last_name FROM admins WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($fname, $lname);
    $stmt->fetch();
    $stmt->close();
    $stmt = $conn->prepare("DELETE FROM admins WHERE id=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        log_activity($conn, "Deleted admin account: {$fname} {$lname}");
        echo json_encode(['success'=>true]);
    } else {
        echo json_encode(['success'=>false, 'message'=>'Database error.']);
    }
    exit;
}

// -------------------------
// 🔑 LOGIN HANDLER
// -------------------------
// LOGIN HANDLER (log with time)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['admin_id'] = $row['id'];
            $_SESSION['admin'] = $row['username'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];
            $full_name = trim($row['first_name'] . ' ' . $row['last_name']);
            $time = date("M d, Y H:i:s");
            log_activity($conn, "$full_name logged in at $time");
            header("Location: dashboard.php");
            exit();
        } else {
            echo "<script>alert('❌ Invalid password!');</script>";
            // Optionally log failed login attempts here
        }
    } else {
        echo "<script>alert('⚠️ User not found!');</script>";
        // Optionally log failed login attempts here
    }
    $stmt->close();
}

// AJAX ENDPOINT FOR SUPER ADMIN ACCOUNT CREATION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'superadmin_create_account') {
    header('Content-Type: application/json');
    $required = ['first_name','last_name','birthdate','age','email','contact','role','username','password'];
    $errors = [];
    foreach($required as $field) if (empty($_POST[$field])) $errors[] = "$field is required";

    // Email/Username uniqueness
    $email = $_POST['email'];
    $username = $_POST['username'];
    $stmt = $conn->prepare("SELECT id FROM admins WHERE email = ? OR username = ?");
    $stmt->bind_param("ss", $email, $username);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) $errors[] = "Email/Username already exists.";
    $stmt->close();

    // ID picture upload
    $id_picture_path = '';
    if (isset($_FILES['id_picture']) && $_FILES['id_picture']['error'] == UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['id_picture']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($ext, $allowed)) $errors[] = 'Invalid image format.';
        else {
            if (!file_exists('uploads')) mkdir('uploads', 0777, true);
            $id_picture_path = 'uploads/id_' . time() . '_' . basename($_FILES['id_picture']['name']);
            move_uploaded_file($_FILES['id_picture']['tmp_name'], $id_picture_path);
        }
    } else $errors[] = 'ID Picture is required.';

    if ($errors) {
        echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
        exit;
    }

    $first_name = $_POST['first_name'];
    $last_name  = $_POST['last_name'];
    $birthdate  = $_POST['birthdate'];
    $age        = $_POST['age'];
    $contact    = $_POST['contact'];
    $role       = $_POST['role'];
    $password   = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $full_name  = $first_name . ' ' . $last_name;

    $stmt = $conn->prepare("INSERT INTO admins 
        (username, first_name, last_name, full_name, age, birthday, contact, email, id_picture, password, role) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param(
        'sssssssssss',
        $username, $first_name, $last_name, $full_name, $age, $birthdate, $contact, $email, $id_picture_path, $password, $role
    );
    $stmt->execute();

    if ($stmt->error) {
        echo json_encode(['success' => false, 'message' => $stmt->error]);
        exit;
    }
    log_activity($conn, "Created admin account: {$first_name} {$last_name} ({$username})");
    echo json_encode(['success' => true]);
    exit;
}

// Handle admin account edit (for Main Admin only)
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST['edit_account']) &&
    isset($_POST['edit_account_id']) &&
    $current_role === 'Main Admin'
) {
    $edit_id = intval($_POST['edit_account_id']);
    $email = trim($_POST['email']);
    $contact = trim($_POST['contact']);
    $role = $_POST['role'];

    // Only allow valid roles
    $valid_roles = ['Main Admin', 'Manager', 'Inventory Staff'];
    if (!in_array($role, $valid_roles)) {
        $_SESSION['popup_message'] = "❌ Invalid role selected!";
        $_SESSION['popup_type'] = "error";
        header("Location: " . $_SERVER['PHP_SELF'] . "#admin");
        exit;
    }

    $stmt = $conn->prepare("UPDATE admins SET email=?, contact=?, role=? WHERE id=?");
    $stmt->bind_param("sssi", $email, $contact, $role, $edit_id);
    if ($stmt->execute()) {
        log_activity($conn, "Edited admin account (ID: $edit_id)");
        $_SESSION['popup_message'] = "✅ Account updated successfully!";
        $_SESSION['popup_type'] = "success";
    } else {
        $_SESSION['popup_message'] = "❌ Error updating account!";
        $_SESSION['popup_type'] = "error";
    }
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF'] . "#admin");
    exit;
}

// ===== GET LOGGED-IN ADMIN INFO =====
$admin_id = $_SESSION['admin_id'] ?? null;
if ($admin_id) {
    $stmt = $conn->prepare("SELECT * FROM admins WHERE id=?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $admin_result = $stmt->get_result();
    $admin_info = $admin_result->fetch_assoc();
    $stmt->close();
} else {
    $admin_info = [
        'first_name' => 'Unknown',
        'last_name' => '',
        'email' => '',
        'contact' => '',
        'id_picture' => 'https://via.placeholder.com/80'
    ];
}

// ---- AJAX: Full inbox message list for modal ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_inbox_full') {
    $my_id = $_SESSION['admin_id'];
    $res = $conn->prepare("SELECT m.*, a.username AS sender_username FROM messages m JOIN admins a ON m.sender_id=a.id WHERE receiver_id=? ORDER BY created_at DESC");
    $res->bind_param("i", $my_id);
    $res->execute();
    $result = $res->get_result();
    $msgs = [];
    while ($row = $result->fetch_assoc()) {
        $msgs[] = [
            'sender_username' => $row['sender_username'],
            'subject' => htmlspecialchars($row['subject']),
            'message' => nl2br(htmlspecialchars($row['message'])),
            'created_at' => $row['created_at'],
            'attachment' => $row['attachment'] ? $row['attachment'] : null
        ];
    }
    echo json_encode($msgs);
    exit;
}

// ---- AJAX: Get active accounts (you can define "active" as all logged-in admins, or all admins) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_active_accounts') {
    // Example: show all admins except yourself
    $my_id = $_SESSION['admin_id'];
    $res = $conn->prepare("SELECT username, role, id_picture FROM admins WHERE id<>?");
    $res->bind_param("i", $my_id);
    $res->execute();
    $result = $res->get_result();
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    echo json_encode($users);
    exit;
}

// -------------------------
// 🗂️ AISLE HANDLERS
// -------------------------

// Add Aisle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_aisle'])) {
    $category = ucfirst(trim($_POST['category']));
    $aisle = ucfirst(trim($_POST['aisle']));
    $check = $conn->query("SELECT * FROM aisles WHERE category = '$category' OR aisle = '$aisle'");
    if ($check->num_rows == 0) {
        $conn->query("INSERT INTO aisles (category, aisle) VALUES ('$category', '$aisle')");
        $time = date("M d, Y H:i:s");
        log_activity($conn, "Added Aisle Number $aisle with category $category at $time");
        $_SESSION['popup_message'] = "Aisle added successfully!";
        $_SESSION['popup_type'] = "success";
    } else {
        $_SESSION['popup_message'] = "Category or Aisle number already exists!";
        $_SESSION['popup_type'] = "error";
    }
    $_SESSION['active_section'] = 'aisle';
    header("Location: dashboard.php");
    exit();
}

// ✏️ Update Single Aisle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_aisle'])) {
    $id = $_POST['aisle_id'];
    $new_aisle = ucfirst(trim($_POST['new_aisle']));
    $stmt = $conn->prepare("SELECT aisle, category FROM aisles WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($old_aisle, $category);
    $stmt->fetch();
    $stmt->close();
    $conn->query("UPDATE aisles SET aisle = '$new_aisle' WHERE id = '$id'");
    $time = date("M d, Y H:i:s");
    log_activity($conn, "Edited Aisle Number from $old_aisle to $new_aisle (Category: $category) at $time");

    $check = $conn->query("SELECT * FROM aisles WHERE aisle = '$new_aisle' AND id != '$id'");
    if ($check->num_rows > 0) {
        $_SESSION['popup_message'] = "⚠️ Aisle number already exists!";
        $_SESSION['popup_type'] = "error";
    } else {
        $conn->query("UPDATE aisles SET aisle = '$new_aisle' WHERE id = '$id'");
        log_activity($conn, "Updated aisle ID: $id to $new_aisle");
        $_SESSION['popup_message'] = "✅ Aisle updated successfully!";
        $_SESSION['popup_type'] = "success";
    }
    $_SESSION['active_section'] = 'aisle';
    header("Location: dashboard.php");
    exit();
}

// 💾 Save All Aisles (unique strategy: temp values then final)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_all'])) {
    if (isset($_POST['aisle_id'], $_POST['new_aisle'])) {
        $ids = $_POST['aisle_id'];
        $new_aisles = $_POST['new_aisle'];
        // Step 1: Assign temp values
        foreach ($ids as $index => $id) {
            $temp_val = "temp_{$id}";
            $stmt = $conn->prepare("UPDATE aisles SET aisle = ? WHERE id = ?");
            $stmt->bind_param("si", $temp_val, $id);
            $stmt->execute();
            $stmt->close();
        }
        // Step 2: Assign final values
        foreach ($ids as $index => $id) {
            $aisle_val = ucfirst(trim($new_aisles[$index]));
            $stmt = $conn->prepare("UPDATE aisles SET aisle = ? WHERE id = ?");
            $stmt->bind_param("si", $aisle_val, $id);
            $stmt->execute();
            $stmt->close();
        }
        log_activity($conn, "Saved all aisle changes.");
        $_SESSION['popup_message'] = "All aisle numbers updated successfully!";
        $_SESSION['popup_type'] = "success";
        $_SESSION['active_section'] = 'aisle';
        header("Location: dashboard.php");
        exit();
    }
}

// ✅ Update Aisle (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_aisle'])) {
    $id = intval($_POST['id']);
    $category = ucfirst(trim($_POST['category']));
    $aisle = ucfirst(trim($_POST['aisle']));
    $check = $conn->query("SELECT * FROM aisles WHERE (category = '$category' OR aisle = '$aisle') AND id != $id");
    if ($check->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "Aisle or Category already exists!"]);
        exit;
    }
    $stmt = $conn->prepare("UPDATE aisles SET category = ?, aisle = ? WHERE id = ?");
    $stmt->bind_param("ssi", $category, $aisle, $id);
    if ($stmt->execute()) {
        log_activity($conn, "Updated aisle ID: $id to $aisle (Category: $category)");
        echo json_encode(["status" => "success", "message" => "Aisle updated successfully!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Update failed!"]);
    }
    exit();
}

// ❌ Delete Aisle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_aisle_id'])) {
    $delete_id = intval($_POST['delete_aisle_id']);
    $stmt = $conn->prepare("SELECT aisle, category FROM aisles WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->bind_result($aisle, $category);
    $stmt->fetch();
    $stmt->close();
    $stmt = $conn->prepare("DELETE FROM aisles WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $time = date("M d, Y H:i:s");
        log_activity($conn, "Deleted Aisle Number $aisle (Category: $category) at $time");

   
        log_activity($conn, "Deleted aisle ID: $delete_id");
        $_SESSION['popup_message'] = "Aisle deleted successfully!";
        $_SESSION['popup_type'] = "success";
    } else {
        $_SESSION['popup_message'] = "Failed to delete aisle!";
        $_SESSION['popup_type'] = "error";
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// -------------------------
// 📦 PRODUCT HANDLER
// -------------------------

// Save category edits (including availability toggle)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["save_category"])) {
    $cat_id = key($_POST["save_category"]);
    $any_updated = false;
    foreach ($_POST["name"] as $pid => $pname) {
        $pname = trim($pname);
        $price = floatval($_POST["price"][$pid]);
        $promo_price = isset($_POST["promo_price"][$pid]) && $_POST["promo_price"][$pid] !== '' ? floatval($_POST["promo_price"][$pid]) : null;
        $barcode = trim($_POST["barcode"][$pid]);
        $description = trim($_POST["description"][$pid]);
        $available = isset($_POST["available"][$pid]) ? 1 : 0;

        $stmt = $conn->prepare("UPDATE products SET name=?, price=?, promo_price=?, barcode=?, description=?, available=? WHERE id=?");
        $stmt->bind_param("sdsssii", $pname, $price, $promo_price, $barcode, $description, $available, $pid);
        if ($stmt->execute()) {
            log_activity($conn, "Updated product: $pname (ID: $pid)");
            $any_updated = true;
        }
        $stmt->close();
    }
    $_SESSION['redirect_to_product'] = true;
    $_SESSION['popup_message'] = $any_updated
        ? "✅ Product(s) updated successfully!"
        : "⚠️ No changes detected.";
    $_SESSION['popup_type'] = $any_updated ? "success" : "info";
    header("Location: ".$_SERVER['PHP_SELF']."#product");
    exit;
}

// ... your existing code above ...

// LOG ACTIVITY FUNCTION
function log_activity($conn, $description) {
    if (isset($_SESSION['admin_id'])) {
        $stmt = $conn->prepare("INSERT INTO activity_logs (admin_id, activity) VALUES (?, ?)");
        $stmt->bind_param("is", $_SESSION['admin_id'], $description);
        $stmt->execute();
        $stmt->close();
    }
}

// Example: get formatted date/time for logs if needed
function now_time() {
    return date('Y-m-d H:i:s');
}

// Edit account via modal (no ID in log)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action']==='edit_account_modal' && $is_main_admin) {
    $id = intval($_POST['edit_account_id']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $stmt = $conn->prepare("UPDATE admins SET first_name=?, last_name=?, email=?, contact=?, role=? WHERE id=?");
    $stmt->bind_param("sssssi", $first_name, $last_name, $_POST['email'], $_POST['contact'], $_POST['role'], $id);
    if ($stmt->execute()) {
        log_activity($conn, "Edited admin account: {$first_name} {$last_name}");
        echo json_encode(['success'=>true]);
    } else {
        echo json_encode(['success'=>false, 'message'=>'Database error.']);
    }
    exit;
}

// Delete account via modal (no ID in log)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action']==='delete_account_modal' && $is_main_admin) {
    $id = intval($_POST['delete_account_id']);
    $stmt = $conn->prepare("SELECT first_name, last_name FROM admins WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($fname, $lname);
    $stmt->fetch();
    $stmt->close();
    $stmt = $conn->prepare("DELETE FROM admins WHERE id=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        log_activity($conn, "Deleted admin account: {$fname} {$lname}");
        echo json_encode(['success'=>true]);
    } else {
        echo json_encode(['success'=>false, 'message'=>'Database error.']);
    }
    exit;
}

// LOGIN HANDLER (log with time)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    // ...your code...
    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['admin_id'] = $row['id'];
            $_SESSION['admin'] = $row['username'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];
            $time = date("M d, Y H:i:s");
            log_activity($conn, "Logged in at $time");
            header("Location: dashboard.php");
            exit();
        }
        // ...
    }
    // ...
}

// Add Aisle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_aisle'])) {
    $category = ucfirst(trim($_POST['category']));
    $aisle = ucfirst(trim($_POST['aisle']));
    $check = $conn->query("SELECT * FROM aisles WHERE category = '$category' OR aisle = '$aisle'");
    if ($check->num_rows == 0) {
        $conn->query("INSERT INTO aisles (category, aisle) VALUES ('$category', '$aisle')");
        log_activity($conn, "Added Aisle Number $aisle with category $category");
        // ...
    }
    // ...
}

// Edit Aisle (single)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_aisle'])) {
    $id = $_POST['aisle_id'];
    $new_aisle = ucfirst(trim($_POST['new_aisle']));
    $stmt = $conn->prepare("SELECT aisle FROM aisles WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($old_aisle);
    $stmt->fetch();
    $stmt->close();
    $conn->query("UPDATE aisles SET aisle = '$new_aisle' WHERE id = '$id'");
    log_activity($conn, "Edited Aisle Number from $old_aisle to $new_aisle");
    // ...
}

// Delete Aisle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_aisle_id'])) {
    $delete_id = intval($_POST['delete_aisle_id']);
    $stmt = $conn->prepare("SELECT aisle, category FROM aisles WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->bind_result($aisle, $category);
    $stmt->fetch();
    $stmt->close();
    $stmt = $conn->prepare("DELETE FROM aisles WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        log_activity($conn, "Deleted Aisle Number $aisle (Category: $category)");
        // ...
    }
    // ...
}

// Add Product
if (isset($_POST['add_product'])) {
    $name = trim($_POST['name']);
    // ...your code...
    if ($stmt->execute()) {
        log_activity($conn, "Added \"$name\" as new product.");
        // ...
    }
    // ...
}

// Save category edits: product price, promo, description, availability
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["save_category"])) {
    foreach ($_POST["name"] as $pid => $pname) {
        $changes = [];
        // compare old/new values as needed (optional)
        $desc = trim($_POST["description"][$pid]);
        $promo_price = $_POST["promo_price"][$pid] ?? '';
        $available = isset($_POST["available"][$pid]) ? "Available" : "Not Available";
        $logstr = "Updated product \"$pname\"";
        if($promo_price) $logstr .= " (Promo Price: $promo_price)";
        $logstr .= " (Description: $desc)";
        $logstr .= " ($available)";
        log_activity($conn, $logstr);
    }
}



// Set available from not available list
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["make_available"])) {
    $id = intval($_POST["make_available"]);
    $stmt = $conn->prepare("UPDATE products SET available=1 WHERE id=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        log_activity($conn, "Marked product ID $id as available.");
    }
    $stmt->close();
    $_SESSION['redirect_to_product'] = true;
    $_SESSION['popup_message'] = "✅ Product marked as available!";
    $_SESSION['popup_type'] = "success";
    header("Location: ".$_SERVER['PHP_SELF']."#product");
    exit;
}

// Add Product handler
if (isset($_POST['add_product'])) {
    $name = trim($_POST['name']);
    $time = date("M d, Y H:i:s");
    $price = floatval($_POST['price']);
    $barcode = trim($_POST['barcode']);
    $category = trim($_POST['category']);
    $description = trim($_POST['description']);
    $promo_status = $_POST['promo_status'];
    $promo_type = $_POST['promo_type'] ?? '';
    $promo_start = !empty($_POST['promo_start']) ? $_POST['promo_start'] : null;
    $promo_end = !empty($_POST['promo_end']) ? $_POST['promo_end'] : null;
    $promo_details = trim($_POST['promo_details'] ?? '');
    $promo_price = isset($_POST['promo_price']) && $_POST['promo_price'] !== '' ? floatval($_POST['promo_price']) : null;

    // Get BOTH aisle_id AND aisle number based on category
    $aisle_id = null;
    $aisle_number = null; // Store the actual aisle number

    if ($category !== '') {
        // Modified query to get both id AND aisle number
        $get_aisle = $conn->prepare("SELECT id, aisle FROM aisles WHERE category = ? LIMIT 1");
        $get_aisle->bind_param("s", $category);
        $get_aisle->execute();
        $get_aisle->bind_result($aid, $aisle_val);
        if ($get_aisle->fetch()) {
            $aisle_id = $aid;
            $aisle_number = $aisle_val; // Capture the aisle number
        }
        $get_aisle->close();
    }

    $imgData = '';
    if (isset($_FILES['image']) && $_FILES['image']['tmp_name']) {
        $imgTmp = $_FILES['image']['tmp_name'];
        $imgType = mime_content_type($imgTmp);
        $imgContent = file_get_contents($imgTmp);
        $imgData = 'data:' . $imgType . ';base64,' . base64_encode($imgContent);
    }

    $stmt = $conn->prepare("INSERT INTO products
        (name, price, barcode, category, aisle_id, aisle, description, image,
         promo_status, promo_type, promo_start, promo_end, promo_details, promo_price)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param("sdssissssssssd",
        $name, $price, $barcode, $category, $aisle_id, $aisle_number, $description, $imgData,
        $promo_status, $promo_type, $promo_start, $promo_end, $promo_details, $promo_price);

    if ($stmt->execute()) {
        log_activity($conn, "Added \"$name\" as new product in category \"$category\" (Aisle: $aisle_number) at $time.");
        $_SESSION['popup_message'] = "✅ Product \"$name\" added successfully!";
        $_SESSION['popup_type'] = "success";
    } else {
        $_SESSION['popup_message'] = "❌ Error adding product: " . $stmt->error;
        $_SESSION['popup_type'] = "error";
    }
    $stmt->close();
    header("Location: ".$_SERVER['PHP_SELF']."#product");
    exit;
}
    



// Similarly, update the product update handler
if (isset($_POST['update_product']) && isset($_POST['edit_product_id']) && $_POST['edit_product_id'] !== '') {
    $id = $_POST['edit_product_id'];
    $name = $_POST['name'];
    $price = $_POST['price'];
    $barcode = $_POST['barcode'];
    $description = $_POST['description'];
    $category = $_POST['category'];
    $promo_status = $_POST['promo_status'];
    $promo_type = $_POST['promo_type'];
    $promo_details = $_POST['promo_details'];
    $promo_price = !empty($_POST['promo_price']) ? floatval($_POST['promo_price']) : null;
    $promo_start = !empty($_POST['promo_start']) ? $_POST['promo_start'] : null;
    $promo_end = !empty($_POST['promo_end']) ? $_POST['promo_end'] : null;

    // Get both aisle_id AND aisle number
    $aisle_id = null;
    $aisle_number = null;
    
    if ($category !== '') {
        $get_aisle = $conn->prepare("SELECT id, aisle FROM aisles WHERE category = ? LIMIT 1");
        $get_aisle->bind_param("s", $category);
        $get_aisle->execute();
        $get_aisle->bind_result($aid, $aisle_val);
        if ($get_aisle->fetch()) {
            $aisle_id = $aid;
            $aisle_number = $aisle_val;
        }
        $get_aisle->close();
    }

    // Update statement including aisle column
    $stmt = $conn->prepare("UPDATE products SET 
                          name=?, price=?, barcode=?, description=?,
                          category=?, aisle_id=?, aisle=?,
                          promo_status=?, promo_type=?, promo_details=?,
                          promo_price=?, promo_start=?, promo_end=?
                          WHERE id=?");
                          
    $stmt->bind_param("sdsssississssi", 
                    $name, $price, $barcode, $description,
                    $category, $aisle_id, $aisle_number,
                    $promo_status, $promo_type, $promo_details,
                    $promo_price, $promo_start, $promo_end,
                    $id);
                    
    if ($stmt->execute()) {
        log_activity($conn, "Updated product: $name (ID: $id)");
        $_SESSION['popup_message'] = "✅ Product updated successfully!";
        $_SESSION['popup_type'] = "success";
    }
    $stmt->close();
    header("Location: ".$_SERVER['PHP_SELF']."#product");
    exit;
}

// Update product handler - also needs to update category and aisle_id
if (isset($_POST['update_product']) && isset($_POST['edit_product_id']) && $_POST['edit_product_id'] !== '') {
    $id = $_POST['edit_product_id'];
    $name = $_POST['name'];
    $price = $_POST['price'];
    $barcode = $_POST['barcode'];
    $description = $_POST['description'];
    $category = $_POST['category'];
    $promo_status = $_POST['promo_status'];
    $promo_type = $_POST['promo_type'];
    $promo_details = $_POST['promo_details'];
    $promo_price = !empty($_POST['promo_price']) ? floatval($_POST['promo_price']) : null;
    $promo_start = !empty($_POST['promo_start']) ? $_POST['promo_start'] : null;
    $promo_end = !empty($_POST['promo_end']) ? $_POST['promo_end'] : null;

    // Get aisle_id for the selected category (important!)
    $aisle_id = null;
    if ($category !== '') {
        $get_aisle = $conn->prepare("SELECT id FROM aisles WHERE category = ? LIMIT 1");
        $get_aisle->bind_param("s", $category);
        $get_aisle->execute();
        $get_aisle->bind_result($aid);
        if ($get_aisle->fetch()) {
            $aisle_id = $aid;
        }
        $get_aisle->close();
    }

    // Check for duplicate barcode (excluding this product)
    $stmt = $conn->prepare("SELECT id FROM products WHERE barcode = ? AND id != ?");
    $stmt->bind_param("si", $barcode, $id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $_SESSION['popup_message'] = "⚠️ Barcode already exists for another product!";
        $_SESSION['popup_type'] = "warning";
    } else {
        // Include category and aisle_id in the UPDATE statement
        $stmt = $conn->prepare("UPDATE products SET name=?, price=?, barcode=?, description=?, 
                              category=?, aisle_id=?, 
                              promo_status=?, promo_type=?, promo_details=?, 
                              promo_price=?, promo_start=?, promo_end=? 
                              WHERE id=?");
        $stmt->bind_param("sdssissssdsssi", 
                        $name, $price, $barcode, $description, 
                        $category, $aisle_id, 
                        $promo_status, $promo_type, $promo_details, 
                        $promo_price, $promo_start, $promo_end, 
                        $id);
        if ($stmt->execute()) {
            log_activity($conn, "Updated product: $name (ID: $id)");
            $_SESSION['popup_message'] = "✅ Product updated successfully!";
            $_SESSION['popup_type'] = "success";
        }
    }
    $stmt->close();
    header("Location: ".$_SERVER['PHP_SELF']."#product");
    exit;
}

// -------------------------
// ✅ COUNTS FOR DASHBOARD
// -------------------------
$resultAisles = $conn->query("SELECT COUNT(*) AS total_aisles FROM aisles");
$rowAisles = $resultAisles->fetch_assoc();
$totalAisles = $rowAisles['total_aisles'];

$resultProducts = $conn->query("SELECT COUNT(*) AS total_products FROM products");
$rowProducts = $resultProducts->fetch_assoc();
$totalProducts = $rowProducts['total_products'];

$resultAdmins = $conn->query("SELECT COUNT(*) AS total_admins FROM admins");
$rowAdmins = $resultAdmins->fetch_assoc();
$totalAdmins = $rowAdmins['total_admins'];

// AJAX: List not-available products (for modal and print)
if (isset($_GET['action']) && $_GET['action'] === 'fetch_not_available') {
    $result = $conn->query("SELECT products.*, aisles.aisle, aisles.category 
        FROM products 
        LEFT JOIN aisles ON products.aisle_id = aisles.id 
        WHERE products.available=0
        ORDER BY aisles.category ASC, products.name ASC");
    $not_available = [];
    while ($row = $result->fetch_assoc()) {
        $not_available[$row['category']][] = $row;
    }
    header('Content-Type: application/json');
    echo json_encode($not_available);
    exit;
}

// AJAX: List available products (for print)
if (isset($_GET['action']) && $_GET['action'] === 'fetch_available') {
    $result = $conn->query("SELECT products.*, aisles.aisle, aisles.category 
        FROM products 
        LEFT JOIN aisles ON products.aisle_id = aisles.id 
        WHERE products.available=1
        ORDER BY aisles.category ASC, products.name ASC");
    $available = [];
    while ($row = $result->fetch_assoc()) {
        $available[$row['category']][] = $row;
    }
    header('Content-Type: application/json');
    echo json_encode($available);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["bulk_make_available"])) {
    $ids = isset($_POST["not_available_ids"]) ? $_POST["not_available_ids"] : [];
    if (!empty($ids) && is_array($ids)) {
        // Prepare the SQL for bulk update
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids));
        $stmt = $conn->prepare("UPDATE products SET available=1 WHERE id IN ($placeholders)");
        $stmt->bind_param($types, ...$ids);
        $stmt->execute();
        $stmt->close();
        log_activity($conn, "Bulk marked products as available: " . implode(",", $ids));
        $_SESSION['redirect_to_product'] = true;
        $_SESSION['popup_message'] = "✅ Selected products are now available!";
        $_SESSION['popup_type'] = "success";
        header("Location: ".$_SERVER['PHP_SELF']."#product");
        exit;
    } else {
        $_SESSION['popup_message'] = "⚠️ No products selected!";
        $_SESSION['popup_type'] = "warning";
        header("Location: ".$_SERVER['PHP_SELF']."#product");
        exit;
    }
}

// Disable promo handler (put this before your HTML output)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["disable_promo"])) {
    $promo_ids = array_keys($_POST["disable_promo"]);
    foreach ($promo_ids as $id) {
        $stmt = $conn->prepare("UPDATE products SET promo_status='off', promo_type=NULL, promo_start=NULL, promo_end=NULL, promo_details=NULL, promo_price=NULL WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
    log_activity($conn, "Disabled promo for products: " . implode(",", $promo_ids));
    $_SESSION['popup_message'] = "Promo disabled for selected item(s)!";
    $_SESSION['popup_type'] = "success";
    $_SESSION['redirect_to_product'] = true;
    header("Location: ".$_SERVER['PHP_SELF']."#product");
    exit;
}

// -------------------------
// AJAX: GET RECENT ACTIVITY LOGS (for pretty display)
// -------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_recent_activity') {
    // Fetch the 10 most recent logs and JOIN admin info for name/role
    $logs = [];
    $stmt = $conn->prepare("
      SELECT l.activity, l.created_at, a.first_name, a.last_name, a.role
      FROM activity_logs l
      LEFT JOIN admins a ON l.admin_id = a.id
      ORDER BY l.created_at DESC
      LIMIT 10
    ");
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $row['full_name'] = trim(($row['role']=='Main Admin' ? 'Main Admin' : $row['first_name'].' '.$row['last_name']));
        $row['role'] = $row['role'] ?? '';
        $logs[] = $row;
    }
    header('Content-Type: application/json');
    echo json_encode($logs);
    exit;
}



// Fetch admin info, metrics, etc. (already present in your code)
$photo = (!empty($admin_info['id_picture']) && file_exists($admin_info['id_picture'])) ? $admin_info['id_picture'] : 'https://via.placeholder.com/80';
$full_name = htmlspecialchars($admin_info['first_name'] . ' ' . $admin_info['last_name']);
$email = htmlspecialchars($admin_info['email']);
$contact = htmlspecialchars($admin_info['contact']);
$username = htmlspecialchars($admin_info['username'] ?? 'admin');
$role = htmlspecialchars($admin_info['role']);
$last_updated = date("M d, Y H:i"); // You can fetch from DB if you log updates

// Metrics (make sure these are set in your PHP)
$totalAisles = $totalAisles ?? 0;
$totalProducts = $totalProducts ?? 0;
$totalAdmins = $totalAdmins ?? 0;
$todayAct = $todayAct ?? 0;
$availableProducts = $availableProducts ?? 0;
$onPromo = $onPromo ?? 0;
$outOfStock = $outOfStock ?? 0;
$pendingActions = $pendingActions ?? 0;
$productsAdded = $productsAdded ?? 0;
$productsUpdated = $productsUpdated ?? 0;
$promosToday = $promosToday ?? 0;
$inventoryChange = $inventoryChange ?? 0;


// AJAX: Get dashboard metrics in real time
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_dashboard_metrics') {
    $resp = [];
    $resp['totalProducts'] = (int)$conn->query("SELECT COUNT(*) FROM products")->fetch_row()[0];
    $resp['totalAisles'] = (int)$conn->query("SELECT COUNT(*) FROM aisles")->fetch_row()[0];
    $resp['totalAdmins'] = (int)$conn->query("SELECT COUNT(*) FROM admins")->fetch_row()[0];
    $resp['onPromo'] = (int)$conn->query("SELECT COUNT(*) FROM products WHERE promo_status='on'")->fetch_row()[0];
    $resp['availableProducts'] = (int)$conn->query("SELECT COUNT(*) FROM products WHERE available=1")->fetch_row()[0];
    $resp['outOfStock'] = (int)$conn->query("SELECT COUNT(*) FROM products WHERE available=0")->fetch_row()[0];
    $resp['pendingActions'] = (int)$conn->query("SELECT COUNT(*) FROM activity_logs WHERE created_at >= CURDATE()")->fetch_row()[0];
    $resp['todayAct'] = (int)$conn->query("SELECT COUNT(*) FROM activity_logs WHERE DATE(created_at)=CURDATE()")->fetch_row()[0];
    $resp['productsAdded'] = (int)$conn->query("SELECT COUNT(*) FROM products WHERE DATE(created_at)=CURDATE()")->fetch_row()[0];
    $resp['productsUpdated'] = (int)$conn->query("SELECT COUNT(*) FROM products WHERE DATE(updated_at)=CURDATE()")->fetch_row()[0];
    $resp['promosToday'] = (int)$conn->query("SELECT COUNT(*) FROM products WHERE promo_status='on' AND DATE(promo_start)=CURDATE()")->fetch_row()[0];
    $resp['aislesAdded'] = (int)$conn->query("SELECT COUNT(*) FROM aisles WHERE DATE(created_at)=CURDATE()")->fetch_row()[0];
    $resp['last_updated'] = date("M d, Y H:i");
    header('Content-Type: application/json');
    echo json_encode($resp);
    exit;
}

//end
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    /* ====== IMPROVED SIDE WIDGETS ====== */
.side-widgets {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 32px; /* increased gap */
  min-width: 320px;   /* give a more prominent width */
  max-width: 420px;   /* but not too wide */
  margin-left: 30px;  /* push away from main content if space allows */
}

.side-widgets > .widget,
.side-widgets > .log-history {
  background: rgba(255,255,255,0.95);
  backdrop-filter: blur(18px);
  -webkit-backdrop-filter: blur(18px);
  padding: 30px 28px;            /* more padding for relaxed feel */
  border-radius: 22px;           /* rounder corners */
  box-shadow: 0 12px 32px rgba(0,0,0,0.10); /* softer, stronger shadow */
  transition: transform 0.3s, box-shadow 0.3s;
}

.side-widgets > .widget:hover,
.side-widgets > .log-history:hover {
  transform: translateY(-5px) scale(1.02);
  box-shadow: 0 18px 36px rgba(0,0,0,0.13);
}

.log-history {
  max-height: 300px;
  overflow-y: auto;
  font-size: 1rem;
}

.clock, .calendar {
  text-align: center;
  font-size: 2.1rem;
  font-weight: 700;
  color: #111827;
  letter-spacing: 2px;
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-bottom: 5px;
}
.clock i { font-size: 1.6rem; margin-bottom: 8px; color: #4ade80;}
.calendar i { font-size: 1.5rem; margin-bottom: 8px; color: #22d3ee;}
.calendar {
  font-size: 1.15rem;
  font-weight: 600;
}

/* Responsive: Stack side-widgets below main content on small screens */
@media (max-width: 1100px) {
  .content {
    flex-direction: column;
  }
  .side-widgets {
    min-width: unset;
    max-width: unset;
    width: 100%;
    margin-left: 0;
    gap: 18px;
  }
  .side-widgets > .widget,
  .side-widgets > .log-history {
    padding: 18px 10px;
    border-radius: 14px;
  }
}
    /* ====== RESET & BASE ====== */
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    body { display: flex; min-height: 100vh; background: #f4f6f9; }
    h1, h2, h3, h4, h5 { font-weight: 700; }
    .section { display: none; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
    .section.active { display: block; }
    /* ====== SIDEBAR ====== */
    .sidebar {
      width: 250px;
      background: #2c3e50;
      color: white;
      display: flex;
      flex-direction: column;
      padding-top: 20px;
      position: fixed;
      height: 100%;
      box-shadow: 2px 0 8px rgba(0,0,0,0.2);
      transition: width 0.3s;
    }
    .sidebar h2 { text-align: center; margin-bottom: 30px; font-size: 1.4rem; letter-spacing: 1px; }
    .sidebar a {
      padding: 15px 20px; text-decoration: none; color: white;
      display: flex; align-items: center; gap: 10px;
      font-size: 1rem; transition: background 0.3s, padding-left 0.3s;
    }
    .sidebar a:hover { background: #34495e; padding-left: 30px; }
    .sidebar a.active { background: #1abc9c; }
    /* ====== MAIN CONTENT ====== */
    .content { margin-left: 250px; padding: 30px; width: 100%; display: flex; gap: 20px; animation: fadeIn 0.4s ease-in-out;}
    .main-section { flex: 3; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    /* ====== PROFILE CARD ====== */
    .profile-card { display: flex; align-items: center; gap: 20px; }
    .profile-card img { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid #1abc9c; }
    .profile-info h3 { margin-bottom: 5px; color: #2c3e50; }
    .profile-info p { margin: 2px 0; font-size: 0.9rem; color: #555; }
    .actions { margin-top: 15px; }
    .actions button {
      background: #1abc9c; border: none; padding: 10px 15px; color: white;
      font-size: 0.9rem; border-radius: 5px; margin-right: 10px; cursor: pointer; transition: background 0.3s;
    }
    .actions button:hover { background: #16a085; }
    /* ====== DASHBOARD CARDS ====== */
    .card-container {
      display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 20px; margin-top: 20px;
    }
    .card {
      background: #fff; padding: 20px; border-radius: 10px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1); transition: transform 0.3s;
    }
    .card:hover { transform: translateY(-5px); }
    /* ====== WIDGETS ====== */
    .side-widgets { flex: 1; display: flex; flex-direction: column; gap: 20px; }
    .widget, .log-history {
      background: rgba(255,255,255,0.75); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
      padding: 20px; border-radius: 16px; box-shadow: 0 8px 28px rgba(0,0,0,0.08);
      transition: transform 0.3s, box-shadow 0.3s;
    }
    .widget:hover, .log-history:hover { transform: translateY(-3px); box-shadow: 0 12px 32px rgba(0,0,0,0.12); }
    .log-history { max-height: 250px; overflow-y: auto; }
    .log-history h3 { margin-bottom: 12px; color: #1f2937; font-size: 1.1rem; font-weight: 700; display: flex; align-items: center; gap: 8px; }
    .log-history ul { list-style: none; padding: 0; }
    .log-history ul li { padding: 10px; margin-bottom: 8px; background: rgba(243,244,246,0.8); border-radius: 10px; font-size: 0.9rem; color: #374151; display: flex; align-items: center; gap: 8px; }
    .log-history ul li::before { content: "⏺"; font-size: 0.6rem; color: #10b981; }
    /* Scrollbar styling */
    .log-history ul::-webkit-scrollbar { width: 6px;}
    .log-history ul::-webkit-scrollbar-thumb { background: #9ca3af; border-radius: 6px;}
    .log-history ul::-webkit-scrollbar-track { background: transparent;}
    .clock { text-align: center; font-size: 2rem; font-weight: 700; color: #111827; letter-spacing: 2px; display: flex; flex-direction: column; align-items: center; }
    .clock i { font-size: 1.3rem; margin-bottom: 6px; color: #4ade80;}
    .calendar { text-align: center; font-size: 1rem; font-weight: 600; color: #374151; display: flex; flex-direction: column; align-items: center; }
    .calendar i { font-size: 1.2rem; margin-bottom: 6px; color: #22d3ee;}
    /* ====== PRODUCT TABLE ====== */
    .modern-table th, .modern-table td { padding: 8px 10px; text-align: left; }
    .modern-table th { background: #f7fafc; }
    .modern-table td input, .modern-table td textarea { width: 100%; padding: 5px; border: 1px solid #dcdde1; border-radius: 5px; }
    .category-block { margin-bottom: 25px; }
    .category-title { font-size:1.08rem; }
    /* ====== NOTIFICATION TOASTS ====== */
    #notificationContainer { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 9999; display: flex; flex-direction: column; gap: 16px; width: 380px; max-width: 90%; align-items: center; }
    .toast {
      position: relative; display: flex; align-items: center; gap: 12px;
      background: rgba(255,255,255,0.65); backdrop-filter: blur(14px);
      color: #374151; padding: 18px 24px; border-radius: 16px; font-size: 1rem; font-weight: 500;
      box-shadow: 0 10px 28px rgba(0,0,0,0.08); cursor: pointer; opacity: 0; transform: scale(0.92);
      transition: all 0.35s ease; overflow: hidden; text-align: center;
    }
    .toast.show { opacity: 1; transform: scale(1);}
    .toast.success { border-left: 5px solid #86efac; color: #166534; background: rgba(134,239,172,0.2); }
    .toast.error   { border-left: 5px solid #fca5a5; color: #7f1d1d; background: rgba(252,165,165,0.2); }
    .toast.warning { border-left: 5px solid #fde68a; color: #78350f; background: rgba(253,230,138,0.2); }
    .toast.info    { border-left: 5px solid #93c5fd; color: #1e3a8a; background: rgba(147,197,253,0.2); }


    .print-btn {
      background: #6366f1;
      color: white;
      border: none;
      border-radius: 8px;
      padding: 10px 18px;
      font-weight: 600;
      margin: 0 8px 18px 0;
      cursor: pointer;
      transition: background .2s;
    }
    .print-btn:hover { background: #4338ca; }
    .not-available-table th, .not-available-table td {
      padding: 10px 8px;
      text-align: left;
      border-bottom: 1px solid #e5e7eb;
    }
    .not-available-table th { background: #f7fafc; }
    .not-available-table td input, .not-available-table td textarea { width: 100%; }
    .notif-popup { position:fixed; left:50%; top:30px; transform:translateX(-50%); min-width:280px; z-index:9999; }
  

    

  </style>
</head>
<body>
<?php if (isset($_SESSION['redirect_to_product'])): ?>
<script>
window.addEventListener("DOMContentLoaded",function(){
  sessionStorage.setItem('activeSection', 'product');
});
</script>
<?php unset($_SESSION['redirect_to_product']); endif; ?>
<?php
if (isset($_SESSION['popup_message'])) {
    $type = $_SESSION['popup_type'] ?? "info";
    $msg = addslashes($_SESSION['popup_message']);
    echo "<script>window.addEventListener('DOMContentLoaded',function(){showNotification('{$msg}','{$type}');});</script>";
    unset($_SESSION['popup_message'], $_SESSION['popup_type']);
}
?>



  <div class="sidebar">
    
    <h2><i class="fa-solid fa-chart-line"></i> Dashboard</h2>
    <a href="#home" onclick="showSection('home', this)" class="active"><i class="fa-solid fa-house"></i> Home</a>
    <a href="#aisle" onclick="showSection('aisle', this)"><i class="fa-solid fa-boxes-stacked"></i> Aisle Management</a>
    <a href="#product" onclick="showSection('product', this)"><i class="fa-solid fa-tags"></i> Product Management</a>
    <a href="#admin" onclick="showSection('admin', this)"><i class="fa-solid fa-user-gear"></i> Admin</a>
    <a href="login.php?logout=true"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>

  </div>

<div class="content">
  


    <div class="main-section">
<?php

// ===== Fetch Admin Info FIRST =====
$admin_id = $_SESSION['admin_id'] ?? null;
if ($admin_id) {
    $stmt = $conn->prepare("SELECT * FROM admins WHERE id=?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $admin_result = $stmt->get_result();
    $admin_info = $admin_result->fetch_assoc();
    $stmt->close();
} else {
    $admin_info = [
        'first_name' => 'Unknown',
        'last_name' => '',
        'email' => '',
        'contact' => '',
        'id_picture' => 'https://via.placeholder.com/80',
        'role' => 'Admin'
    ];
}
$is_main_admin = strtolower($admin_info['role']) === 'admin';
// ===== DB COUNTERS =====
$resultAisles = $conn->query("SELECT COUNT(*) AS total_aisles FROM aisles");
$rowAisles = $resultAisles->fetch_assoc();
$totalAisles = $rowAisles['total_aisles'];

$resultProducts = $conn->query("SELECT COUNT(*) AS total_products FROM products");
$rowProducts = $resultProducts->fetch_assoc();
$totalProducts = $rowProducts['total_products'];

$resultAdmins = $conn->query("SELECT COUNT(*) AS total_admins FROM admins");
$rowAdmins = $resultAdmins->fetch_assoc();
$totalAdmins = $rowAdmins['total_admins'];

// Admin photo
$photo = (!empty($admin_info['id_picture']) && file_exists($admin_info['id_picture'])) ? $admin_info['id_picture'] : 'https://via.placeholder.com/80';
$full_name = htmlspecialchars($admin_info['first_name'] . ' ' . $admin_info['last_name']);
$email = htmlspecialchars($admin_info['email']);
$contact = htmlspecialchars($admin_info['contact']);// Get Admin Info
$admin_id = $_SESSION['admin_id'] ?? null;
if ($admin_id) {
    $stmt = $conn->prepare("SELECT * FROM admins WHERE id=?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $admin_result = $stmt->get_result();
    $admin_info = $admin_result->fetch_assoc();
    $stmt->close();
} else {
    $admin_info = [
        'first_name' => 'Unknown',
        'last_name' => '',
        'email' => '',
        'contact' => '',
        'id_picture' => 'https://via.placeholder.com/80',
        'role' => 'Admin'
    ];
}

$photo = (!empty($admin_info['id_picture']) && file_exists($admin_info['id_picture'])) ? $admin_info['id_picture'] : 'https://via.placeholder.com/80';
$full_name = htmlspecialchars($admin_info['first_name'] . ' ' . $admin_info['last_name']);
$email = htmlspecialchars($admin_info['email']);
$contact = htmlspecialchars($admin_info['contact']);

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}


?>

<div id="home" class="section active" style="background:#f6f7fa;padding:0;">
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@700;800&display=swap');
:root {
  --primary: #22c55e;
  --primary-bg: #e7faee;
  --blue: #3b82f6;
  --orange: #f59e42;
  --purple: #a78bfa;
  --gray: #f3f4f6;
  --shadow: 0 8px 32px rgba(44,68,80,0.09);
  --radius-lg: 30px;
  --radius-md: 18px;
  --radius-sm: 13px;
}
.shp-topbar {
  display:flex; align-items:center; justify-content:space-between;
  padding:32px 40px 18px 40px; background:#fff; box-shadow:var(--shadow);
  border-radius:0 0 var(--radius-lg) var(--radius-lg); margin-bottom:35px;
  font-family:'Inter',sans-serif; position:relative; z-index:2;
}
.shp-logo-block {
  display:flex; flex-direction:column; align-items:flex-start;
}
.shp-logo {
  font-size:2.7rem; color:var(--primary); font-weight:800; letter-spacing:1px; font-family:'Inter',sans-serif;
}
.shp-logo-sub {
  font-size:1.1rem; color:#7dd3fc; font-weight:600; margin-top:2px; letter-spacing:0.6px;
}
.shp-topbar-right {
  display:flex; align-items:center; gap:28px;
}
.shp-lastupdated {
  font-size:0.96rem; color:#64748b; font-weight:500; margin-right:8px;
}
.shp-bell {
  font-size:1.5rem; color:var(--primary); position:relative; cursor:pointer; margin-right:11px;
  transition: color .16s;
}
.shp-bell:hover { color:var(--blue);}
.shp-avatar-block {
  display:flex; align-items:center; gap:13px; background:var(--primary-bg);
  padding:6px 18px 6px 7px; border-radius:100px;
  box-shadow:0 2px 8px #22c55e1a;
}
.shp-avatar-block img {
  width:46px; height:46px; border-radius:50%; object-fit:cover; border:2.5px solid var(--primary);
}
.shp-username-role {
  display:flex; flex-direction:column; align-items:flex-start;
}
.shp-user {
  font-weight:700; font-size:1.15rem; color:#22223b; font-family:'Inter',sans-serif;
}
.shp-role {
  font-size:0.97rem; color:var(--primary); font-weight:700; letter-spacing:0.2px; margin-top:-2px;
}

/* --- Metrics Card Row --- */
.shp-metrics-row {
  display: grid; grid-template-columns: repeat(4,1fr); gap:36px; margin-bottom:20px;
}
.shp-metric-card {
  background:#fff; border-radius:var(--radius-lg); box-shadow: var(--shadow);
  padding:36px 32px 34px 32px; display:flex; flex-direction:column; align-items:flex-start;
  min-height:154px; position:relative; overflow:visible; font-family:'Inter',sans-serif;
}
.shp-metric-icon {
  width:44px;height:44px;display:flex;align-items:center;justify-content:center;
  border-radius:16px; font-size:2.1rem; margin-bottom:18px; color:#fff;
}
.mic-green { background:linear-gradient(135deg,#22c55e 60%,#bbf7d0 98%);}
.mic-blue { background:linear-gradient(135deg,#3b82f6 60%,#dbeafe 98%);}
.mic-orange { background:linear-gradient(135deg,#f59e42 60%,#fdba74 98%);}
.mic-purple { background:linear-gradient(135deg,#a78bfa 60%,#ddd6fe 98%);}
.shp-metric-num {
  font-size:2.7rem; font-weight:800; color:#18181b; margin-bottom:2px; line-height:1.19;
}
.shp-metric-context {
  font-size:1.1rem; font-weight:500; color:#64748b; margin-top:3px;
}
/* --- Secondary Metrics Row --- */
.shp-metrics-row2 {
  display: grid; grid-template-columns: repeat(4,1fr); gap:22px; margin-bottom:34px;
}
.shp-metric2-card {
  background:#fff; border-radius:var(--radius-md); box-shadow:0 4px 14px #0001;
  padding:25px 22px 24px 22px; display:flex; align-items:center; gap:20px; font-family:'Inter',sans-serif;
}
.shp-metric2-icon {
  width:36px;height:36px;display:flex;align-items:center;justify-content:center;
  border-radius:12px; font-size:1.45rem; color:#fff; flex-shrink:0;
}
.mic2-green { background:linear-gradient(135deg,#22c55e 70%,#bbf7d0 98%);}
.mic2-blue { background:linear-gradient(135deg,#3b82f6 70%,#bae6fd 98%);}
.mic2-orange { background:linear-gradient(135deg,#f59e42 80%,#fdba74 98%);}
.mic2-purple { background:linear-gradient(135deg,#a78bfa 80%,#ddd6fe 98%);}
.shp-metric2-content { display:flex; flex-direction:column;}
.shp-metric2-num { font-size:1.45rem; font-weight:700; color:#18181b;}
.shp-metric2-label { font-size:1rem; font-weight:500; color:#64748b; }

/* --- Main 3-Column Grid --- */
.shp-main-grid {
  display: grid;
  grid-template-columns: 1fr 1fr; /* 2 columns */
  gap: 32px;
  margin-bottom: 36px;
}

.shp-recent-act,
.shp-mgmt-hub {
  min-width: 0; /* Prevent overflow */
  width: 100%;
}



/* --- Recent Activity --- */
.shp-recent-act {
  background:#fff; border-radius:var(--radius-md); box-shadow:var(--shadow); font-family:'Inter',sans-serif;
  padding:28px 27px 18px 27px; display:flex; flex-direction:column; min-height:300px;
}
.shp-recent-title {
  font-size:1.23rem; font-weight:700; color:var(--blue); margin-bottom:13px; display:flex; align-items:center; gap:10px;
}
.shp-recent-title i { font-size:1.45rem; animation:shpPulse 1.2s infinite;}
@keyframes shpPulse {
  0%,100%{color:var(--blue);}
  50%{color:#60a5fa;}
}
.shp-activity-list {
  display:flex; flex-direction:column; gap:9px; margin-bottom:10px;
}
.shp-activity-item {
  display:flex; align-items:center; gap:10px; font-size:1.01rem; color:#374151;
}
.shp-activity-dot {
  width:11px;height:11px;background:var(--primary);border-radius:50%;margin-right:3px;display:inline-block;
  box-shadow:0 0 0 2px #bbf7d0;
}
.shp-activity-user { font-weight:600; color:#22c55e; margin-right:3px;}
.shp-activity-time { font-size:0.96em; color:#64748b; margin-left:6px;}
.shp-activity-completed { color:var(--primary);}
.shp-all-activity-link {
  margin-top:7px; color:var(--blue); font-weight:600; font-size:1.02em; text-decoration:none; transition: color .16s;
}
.shp-all-activity-link:hover { color:#2563eb; text-decoration:underline;}

/* --- Management Hub --- */
.shp-mgmt-hub {
  background:#fff; border-radius:var(--radius-md); box-shadow:var(--shadow); font-family:'Inter',sans-serif;
  padding:28px 27px 28px 27px; display:flex; flex-direction:column; align-items:stretch;
}
.shp-mgmt-title {
  font-size:1.23rem; font-weight:700; color:var(--primary); margin-bottom:15px; display:flex; align-items:center; gap:10px;
}
.shp-mgmt-title i {font-size:1.5rem;}
.shp-mgmt-btn {
  background:var(--primary); color:#fff; border:none; border-radius:14px; font-size:1.09em; font-weight:700;
  padding:17px 0; margin-bottom:15px; transition: background .18s, color .12s; cursor:pointer; width:100%; box-shadow:0 2px 8px #22c55e1a;
  display:flex; align-items:center; gap:13px; justify-content:center;
}
.shp-mgmt-btn:last-child {margin-bottom:0;}
.shp-mgmt-btn:hover { background:#16a34a; }
.shp-mgmt-btn.white {
  background:#fff; color:var(--primary); border:2px solid var(--primary); box-shadow:none; margin-bottom:10px;
}
.shp-mgmt-btn.white:hover { background:var(--primary-bg); color:#15803d; border-color:#22c55e; }

/* --- Footer Metrics Card --- */
.shp-footer-metrics {
  background:#fff; border-radius:var(--radius-lg); box-shadow:var(--shadow); font-family:'Inter',sans-serif;
  padding:26px 34px; margin-top:25px; margin-bottom:20px; display:flex; flex-direction:column;
}
.shp-footer-title {
  font-size:1.08rem; color:#22223b; font-weight:700; margin-bottom:15px; letter-spacing:0.3px;
}
.shp-footer-metrics-row {
  display:grid; grid-template-columns:repeat(4,1fr); gap:32px;
}
.shp-footer-metric {
  display:flex; flex-direction:column; align-items:center; gap:5px;
}
.shp-footer-num { font-size:1.6rem; font-weight:800; line-height:1.12;}
.shp-footer-label { font-size:1.02rem; font-weight:600; letter-spacing:0.3px;}
.num-blue { color:var(--blue);}
.num-green { color:var(--primary);}
.num-orange { color:var(--orange);}
.num-purple { color:var(--purple);}
@media (max-width: 1050px) {
  .shp-metrics-row, .shp-metrics-row2, .shp-footer-metrics-row { grid-template-columns:1fr 1fr; gap:16px;}
  .shp-main-grid { grid-template-columns:1fr; gap:18px;}
}
@media (max-width: 660px) {
  .shp-metrics-row, .shp-metrics-row2, .shp-footer-metrics-row { grid-template-columns:1fr; }
  .shp-topbar { flex-direction:column; align-items:flex-start; padding:22px; }
  .shp-main-grid { grid-template-columns: 1fr; gap: 18px; }
}
</style>
<!-- ==== TOP BAR ==== -->
<div class="shp-topbar">
  <div class="shp-logo-block">
    <span class="shp-logo">ShopEase Admin</span>
    <span class="shp-logo-sub">Centralized Store Management</span>
  </div>
  <div class="shp-topbar-right">
    <span class="shp-lastupdated"><i class="fa-regular fa-clock"></i> Last updated: <?= $last_updated ?></span>
    <span class="shp-bell"><i class="fa-regular fa-bell"></i></span>
    <div class="shp-avatar-block">
      <img src="<?= $photo ?>" alt="Profile" />
      <div class="shp-username-role">
        <span class="shp-user"><?= $username ?></span>
        <span class="shp-role"><?= $role ?></span>
      </div>
    </div>
  </div>
</div>

<!-- ==== METRICS ROW ==== -->
<div class="shp-metrics-row">
  <div class="shp-metric-card">
    <div class="shp-metric-icon mic-green"><i class="fa-solid fa-cubes"></i></div>
    <div class="shp-metric-num"><?= $totalProducts ?></div>
    <div class="shp-metric-context"><?= ($productsAdded>0) ? "+$productsAdded today" : "All active" ?></div>
    <div class="shp-metric-context" style="font-size:.97em;color:#a7f3d0;">Total Products</div>
  </div>
  <div class="shp-metric-card">
    <div class="shp-metric-icon mic-blue"><i class="fa-solid fa-bars-staggered"></i></div>
    <div class="shp-metric-num"><?= $totalAisles ?></div>
    <div class="shp-metric-context">Store Aisles</div>
    <div class="shp-metric-context" style="font-size:.97em;color:#bae6fd;">Organized</div>
  </div>
  <div class="shp-metric-card">
    <div class="shp-metric-icon mic-orange"><i class="fa-solid fa-users"></i></div>
    <div class="shp-metric-num"><?= $totalAdmins ?></div>
    <div class="shp-metric-context"><?= $onPromo?> admins on promo ops</div>
    <div class="shp-metric-context" style="font-size:.97em;color:#fed7aa;">Admin Users</div>
  </div>
  <div class="shp-metric-card">
    <div class="shp-metric-icon mic-purple"><i class="fa-solid fa-chart-line"></i></div>
    <div class="shp-metric-num"><?= $todayAct ?></div>
    <div class="shp-metric-context"><?= ($todayAct>0) ? "Activity today" : "No logs yet" ?></div>
    <div class="shp-metric-context" style="font-size:.97em;color:#ddd6fe;">Today's Activity</div>
  </div>
</div>
<!-- ==== SECONDARY METRICS ==== -->
<div class="shp-metrics-row2">
  <div class="shp-metric2-card">
    <span class="shp-metric2-icon mic2-green"><i class="fa-solid fa-check-circle"></i></span>
    <div class="shp-metric2-content">
      <span class="shp-metric2-num"><?= $availableProducts ?></span>
      <span class="shp-metric2-label">Available Products</span>
    </div>
  </div>
  <div class="shp-metric2-card">
    <span class="shp-metric2-icon mic2-blue"><i class="fa-solid fa-bolt"></i></span>
    <div class="shp-metric2-content">
      <span class="shp-metric2-num"><?= $onPromo ?></span>
      <span class="shp-metric2-label">On Promo</span>
    </div>
  </div>
  <div class="shp-metric2-card">
    <span class="shp-metric2-icon mic2-orange"><i class="fa-solid fa-box-open"></i></span>
    <div class="shp-metric2-content">
      <span class="shp-metric2-num"><?= $outOfStock ?></span>
      <span class="shp-metric2-label">Out of Stock</span>
    </div>
  </div>
  <div class="shp-metric2-card">
    <span class="shp-metric2-icon mic2-purple"><i class="fa-solid fa-list"></i></span>
    <div class="shp-metric2-content">
      <span class="shp-metric2-num"><?= $pendingActions ?></span>
      <span class="shp-metric2-label">Pending Actions</span>
    </div>
  </div>
</div>
<!-- ==== MAIN GRID ==== -->
<div class="shp-main-grid">

  <!-- Recent Activity -->
  <div class="shp-recent-act">
    <div class="shp-recent-title"><i class="fa-solid fa-wave-square"></i> Recent Activity</div>
    <div class="shp-activity-list" id="shpActivityList">
      <div class="shp-activity-item"><span class="shp-activity-dot"></span>Loading recent activity...</div>
    </div>
    <a href="#" class="shp-all-activity-link" onclick="showSection('admin')">View All Activity</a>
  </div>
  <!-- Management Hub -->
  <div class="shp-mgmt-hub">
    <div class="shp-mgmt-title"><i class="fa-solid fa-gear"></i> Management Hub</div>
    <button class="shp-mgmt-btn" onclick="showSection('product')"><i class="fa-solid fa-tags"></i> Product Management</button>
    <button class="shp-mgmt-btn" onclick="showSection('aisle')"><i class="fa-solid fa-boxes-stacked"></i> Aisle Management</button>
    <button class="shp-mgmt-btn" onclick="showSection('admin')"><i class="fa-solid fa-users"></i> User Management</button>
    <button class="shp-mgmt-btn white" onclick="showSection('admin')"><i class="fa-solid fa-list"></i> Activity Logs</button>
  </div>
</div>
<!-- ==== FOOTER METRICS ==== -->
<div class="shp-footer-metrics">
  <div class="shp-footer-title">Today's Metrics</div>
  <div class="shp-footer-metrics-row">
    <div class="shp-footer-metric">
      <span class="shp-footer-num num-blue"><?= $productsAdded ?></span>
      <span class="shp-footer-label">Products Added</span>
    </div>
    <div class="shp-footer-metric">
      <span class="shp-footer-num num-green"><?= $productsUpdated ?></span>
      <span class="shp-footer-label">Products Updated</span>
    </div>
    <div class="shp-footer-metric">
      <span class="shp-footer-num num-orange"><?= $promosToday ?></span>
      <span class="shp-footer-label">Promos Started</span>
    </div>
    <div class="shp-footer-metric">
      <span class="shp-footer-num num-purple"><?= $aislesAddedToday ?></span>
      <span class="shp-footer-label">Aisle Added</span>
    </div>
  </div>
</div>
<script>

  function updateDashboardMetrics() {
  fetch('dashboard.php', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: 'action=get_dashboard_metrics'
  })
  .then(r => r.json())
  .then(data => {
    // Top bar
    document.querySelector('.shp-lastupdated').innerHTML =
      `<i class="fa-regular fa-clock"></i> Last updated: ${data.last_updated}`;

    // Top metrics row
    document.querySelector('.shp-metric-num').textContent = data.totalProducts;
    document.querySelectorAll('.shp-metric-num')[1].textContent = data.totalAisles;
    document.querySelectorAll('.shp-metric-num')[2].textContent = data.totalAdmins;
    document.querySelectorAll('.shp-metric-num')[3].textContent = data.todayAct;

    document.querySelector('.shp-metric-context').textContent =
      (data.productsAdded > 0) ? `+${data.productsAdded} today` : "All active";
    document.querySelectorAll('.shp-metric-context')[3].textContent =
      (data.todayAct > 0) ? "Activity today" : "No logs yet";
    // Admins on promo ops
    document.querySelectorAll('.shp-metric-context')[2].textContent =
      `${data.onPromo} admins on promo ops`;

    // Secondary metrics
    document.querySelector('.shp-metric2-num').textContent = data.availableProducts;
    document.querySelectorAll('.shp-metric2-num')[1].textContent = data.onPromo;
    document.querySelectorAll('.shp-metric2-num')[2].textContent = data.outOfStock;
    document.querySelectorAll('.shp-metric2-num')[3].textContent = data.pendingActions;

    // Footer metrics
    document.querySelector('.shp-footer-num.num-blue').textContent = data.productsAdded;
    document.querySelector('.shp-footer-num.num-green').textContent = data.productsUpdated;
    document.querySelector('.shp-footer-num.num-orange').textContent = data.promosToday;
   document.querySelector('.shp-footer-num.num-purple').textContent = data.aislesAdded;
  });
}

// Call every 15s
window.addEventListener('DOMContentLoaded', updateDashboardMetrics);
setInterval(updateDashboardMetrics, 15000);


// --- AJAX for Recent Activity ---
// Helper: human time ago
function timeAgo(date) {
  const now = new Date();
  const seconds = Math.floor((now - date) / 1000);
  if (seconds < 60) return "just now";
  const intervals = [
    { label: "year", seconds: 31536000 },
    { label: "month", seconds: 2592000 },
    { label: "day", seconds: 86400 },
    { label: "hour", seconds: 3600 },
    { label: "minute", seconds: 60 }
  ];
  for (const interval of intervals) {
    const count = Math.floor(seconds / interval.seconds);
    if (count >= 1)
      return count + " " + interval.label + (count > 1 ? "s" : "") + " ago";
  }
  return "just now";
}

// Overwrite loadRecentActivity to match the new format
function loadRecentActivity() {
  fetch('dashboard.php', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: 'action=get_recent_activity'
  })
  .then(r => r.json())
  .then(logs => {
    let html = '';
    if (!logs.length) {
      html = `<div class="shp-activity-item"><span class="shp-activity-dot"></span>No recent activity.</div>`;
    } else {
      html = `
        <table class="recent-activity-table" style="width:100%;border-collapse:collapse;">
          <thead>
            <tr style="background:#f3f4f6;">
              <th style="padding:8px 6px; color:#22223b;">Activity</th>
              <th style="padding:8px 6px; color:#22223b;">User</th>
              <th style="padding:8px 6px; color:#22223b;">Role</th>
              <th style="padding:8px 6px; color:#22223b;">Time</th>
            </tr>
          </thead>
          <tbody>
      `;
      logs.slice(0, 8).forEach(log => {
        const time = new Date(log.created_at.replace(' ', 'T').replace(/-/g, '/'));
        const ago = timeAgo(time);
        const formatted = time.getFullYear() + '-' +
                          String(time.getMonth()+1).padStart(2,'0') + '-' +
                          String(time.getDate()).padStart(2,'0') + ' ' +
                          String(time.getHours()).padStart(2,'0') + ':' +
                          String(time.getMinutes()).padStart(2,'0') + ':' +
                          String(time.getSeconds()).padStart(2,'0');
        html += `
          <tr style="border-bottom:1px solid #e5e7eb;">
            <td style="padding:8px 6px;">${log.activity}</td>
            <td style="padding:8px 6px;">${log.full_name}</td>
            <td style="padding:8px 6px;">${log.role || '-'}</td>
            <td style="padding:8px 6px; color:#64748b;">
              <span title="${formatted}">${formatted}</span>
              <div style="font-size:0.95em;color:#94a3b8;">(${ago})</div>
            </td>
          </tr>
        `;
      });
      html += '</tbody></table>';
    }
    document.getElementById('shpActivityList').innerHTML = html;
  });
}
window.addEventListener('DOMContentLoaded', loadRecentActivity);
setInterval(loadRecentActivity, 16000);
</script>
</div>



      <!-- ==== AISLE MANAGEMENT ==== -->
      <div id="aisle" class="section">
        <h2 style="color:#1f2937; margin-bottom:25px; font-size:1.8rem;">Aisle Management</h2>
        <div id="notificationContainer"></div>
        <!-- Add Aisle Form -->
        <form id="addAisleForm" method="post" class="form-group" style="display:flex; flex-wrap:wrap; gap:20px; margin-bottom:30px; background:rgba(255,255,255,0.8); padding:20px; border-radius:12px; box-shadow:0 8px 20px rgba(0,0,0,0.05); backdrop-filter:blur(8px);">
          <input type="text" id="newCategory" name="category" placeholder="Category" required style="flex:2; padding:14px; border-radius:12px; border:1px solid #d1d5db; font-size:1rem;">
          <input type="text" id="newAisle" name="aisle" placeholder="Aisle Number" required style="flex:1; padding:14px; border-radius:12px; border:1px solid #d1d5db; font-size:1rem;">
          <button type="submit" name="add_aisle" style="background:#4ade80; color:white; border:none; padding:14px 24px; border-radius:12px; cursor:pointer; font-weight:600; font-size:1rem;">Add Aisle</button>
        </form>
        <!-- Edit & Save Buttons -->
        <div style="margin-bottom:20px;">
          <button type="button" id="editBtn" style="background:#3b82f6; color:white; border:none; padding:10px 20px; border-radius:12px; cursor:pointer; font-weight:600; font-size:1rem;">🖉 Edit Aisles</button>
          <button type="submit" name="save_all" id="saveBtn" form="aisleForm" style="display:none; background:#22c55e; color:white; border:none; padding:10px 20px; border-radius:12px; cursor:pointer; font-weight:600; font-size:1rem; margin-left:15px;">💾 Save Changes</button>
        </div>
        <!-- Existing Aisles Table -->
        <form method="post" id="aisleForm">
          <table style="width:100%; border-collapse:separate; border-spacing:0 10px; font-size:1rem;">
            <thead>
              <tr>
                <th style="padding:12px 15px; text-align:left; color:#374151;">Aisle Number</th>
                <th style="padding:12px 15px; text-align:left; color:#374151;">Category</th>
              </tr>
            </thead>
            <tbody>
            <?php 
            $aisle_list = $conn->query("SELECT * FROM aisles ORDER BY aisle + 0 ASC");
            while ($row = $aisle_list->fetch_assoc()) { 
                $id = $row['id'];
                $aisle = htmlspecialchars($row['aisle']);
                $category = htmlspecialchars($row['category']);
            ?>
            <tr style="background:#fff;">
              <td style="padding:12px 15px;">
                <input type="hidden" name="aisle_id[]" value="<?= $id ?>">
                <input type="text" name="new_aisle[]" value="<?= $aisle ?>" class="aisle-input" disabled required style="padding:8px 12px; border-radius:8px; border:1px solid #d1d5db; width:70px; font-size:1rem;">
              </td>
              <td style="padding:12px 15px; color:#4b5563;"><?= $category ?></td>
              <td style="padding:12px 15px; text-align:right;">
                <button type="button" class="delete-btn" data-id="<?= $id ?>" style="background:#ef4444; color:white; border:none; padding:6px 12px; border-radius:8px; cursor:pointer; display:none; font-weight:600;">🗑️ Delete</button>
              </td>
            </tr>
            <?php } ?>
            </tbody>
          </table>
        </form>
      </div>

<!-- PRODUCT MANAGEMENT SECTION (with all 3 requested features) -->
<div id="product" class="section" style="padding:40px; background:#f5f6fa;">
  <h2 style="font-size:2rem; color:#2c3e50; margin-bottom:5px;">🛍️ Product Management</h2>
  <p style="color:#7f8c8d; margin-bottom:30px;">Add, organize, and manage products efficiently</p>
  
  
  <div id="notAvailableModal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; z-index:9999; background:rgba(0,0,0,0.4); align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:10px; max-width:900px; width:95%; margin:auto; padding:30px; position:relative; box-shadow:0 8px 28px rgba(0,0,0,0.14);">
      <button onclick="hideNotAvailable()" style="position:absolute; top:10px; right:15px; background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
      <h3 style="margin-bottom:18px; color:#c2410c;">Not Available Products</h3>
      <form method="post" id="bulkMakeAvailableForm">
  <div id="notAvailableContent">Loading...</div>
  <div style="margin-top:12px;" id="notAvailActions">
    <button type="button" onclick="selectAllNotAvailable(true)" class="print-btn" style="background:#22c55e;">Select All</button>
    <button type="button" onclick="selectAllNotAvailable(false)" class="print-btn" style="background:#ef4444;">Deselect All</button>
    <button type="submit" class="print-btn" name="bulk_make_available" style="background:#10b981;">Make Selected Available</button>
  </div>
</form>
    </div>
  </div>
  
   <!-- Add New Product -->
  <div style="background:#fff; padding:30px; border-radius:15px; box-shadow:0 8px 25px rgba(0,0,0,0.08); margin-bottom:40px;">
    <h3 style="font-size:1.2rem; color:#34495e; margin-bottom:20px;">➕ Add New Product</h3>
    <form method="post" enctype="multipart/form-data" id="addProductForm" style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
     <div>
        <label style="display:block; margin-bottom:5px;">Product Name</label>
        <input type="text" name="name" placeholder="Enter product name" required style="width:100%; padding:10px; border:1px solid #dcdde1; border-radius:8px;">
      </div>
      <div>
        <label style="display:block; margin-bottom:5px;">Price (₱)</label>
        <input type="number" step="0.01" name="price" id="priceInput" placeholder="0.00" required style="width:100%; padding:10px; border:1px solid #dcdde1; border-radius:8px;">
      </div>
      <div>
        <label style="display:block; margin-bottom:5px;">Barcode</label>
        <input type="text" name="barcode" placeholder="Enter barcode" required style="width:100%; padding:10px; border:1px solid #dcdde1; border-radius:8px;">
      </div>
      <div>
        <label style="display:block; margin-bottom:5px;">Category</label>
        <select name="category" required style="width:100%; padding:10px; border:1px solid #dcdde1; border-radius:8px;">
          <option value="">Select Category</option>
          <?php 
          $category_list = $conn->query("SELECT DISTINCT category FROM aisles ORDER BY category ASC");
          while ($row = $category_list->fetch_assoc()) { 
              echo "<option value='".htmlspecialchars($row['category'])."'>".htmlspecialchars($row['category'])."</option>"; 
          } 
          ?>
        </select>
      </div>
      <div>
        <label style="display:block; margin-bottom:5px;">Image</label>
        <input type="file" name="image" id="imageInput" accept="image/*" style="width:100%; padding:5px;" onchange="showPreview(event);">
        <div id="imagePreview" style="margin-top:10px;"></div>
      </div>
      <div style="grid-column:1/-1;">
        <label style="display:block; margin-bottom:5px;">Description</label>
        <textarea name="description" placeholder="Enter product details" style="width:100%; padding:10px; border:1px solid #dcdde1; border-radius:8px; min-height:80px;"></textarea>
      </div>

<!-- ==== PROMO MANAGEMENT SECTION ==== -->
      <div style="grid-column:1/-1; margin-top:16px;">
        <fieldset style="border:1px solid #e5e7eb; border-radius:10px; padding:20px;">
          <legend style="padding:0 8px; color:#2c3e50; font-weight:600;">Promo Management</legend>
          <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr; gap:18px;">
            <div>
              <label style="display:block; margin-bottom:5px;">Promo Status</label>
              <select name="promo_status" required style="width:100%; padding:8px; border-radius:7px; border:1px solid #d1d5db;">
                <option value="off">Not on Promo</option>
                <option value="on">On Promo</option>
              </select>
            </div>
            <div>
              <label style="display:block; margin-bottom:5px;">Promo Type</label>
              <select name="promo_type" id="promoTypeSelect" style="width:100%; padding:8px; border-radius:7px; border:1px solid #d1d5db;">
                <option value="">Select Type</option>
                <option value="discount_percent">🔹 Discount by Percentage</option>
                <option value="discount_fixed">🔹 Discount by Fixed Amount</option>
                <option value="bogo">🔹 Buy One Get One (BOGO)</option>
                <option value="bundle">🔹 Bundle Promo / Combo Deal</option>
                <option value="category">🔹 Category-Wide Promo</option>
                <option value="seasonal">🔹 Seasonal Promo</option>
              </select>
            </div>
            <div>
              <label style="display:block; margin-bottom:5px;">Promo Start</label>
              <input type="date" name="promo_start" style="width:100%; padding:8px; border-radius:7px; border:1px solid #d1d5db;">
            </div>
            <div>
              <label style="display:block; margin-bottom:5px;">Promo End</label>
              <input type="date" name="promo_end" style="width:100%; padding:8px; border-radius:7px; border:1px solid #d1d5db;">
            </div>
          </div>
          <div id="dynamicPromoFields" style="margin-top:16px;"></div>
          <div style="margin-top:16px;">
            <label style="display:block; margin-bottom:5px;">Promo Details / Description</label>
            <textarea name="promo_details" placeholder="E.g. Buy 1 Take 1 until Sept. 30" style="width:100%; padding:8px; border-radius:7px; border:1px solid #d1d5db; min-height:48px;"></textarea>
          </div>
          <div style="margin-top:16px;">
            <label style="display:block; margin-bottom:5px;">Promo Price (Optional)</label>
            <input type="number" step="0.01" name="promo_price" id="promoPriceInput" placeholder="e.g. 99.00" style="width:100%; padding:8px; border-radius:7px; border:1px solid #d1d5db;">
          </div>
        </fieldset>
      </div>
      <!-- ==== END PROMO MANAGEMENT ==== -->

      <form method="post" enctype="multipart/form-data" id="addProductForm" style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
  <!-- ...your fields... -->
  <input type="hidden" name="edit_product_id" value="">
  <!-- ...other fields... -->
  <div style="grid-column:1/-1; text-align:right;">
    <button type="submit" name="add_product" style="background:#3498db; color:#fff; padding:12px 25px; border:none; border-radius:8px; font-weight:600; cursor:pointer;">Add Product</button>
    <!-- Update button will be created by JS if needed -->
  </div>
</form>
  </div>
        
  
<!-- PRODUCT LIST (Available) -->
<!-- Show Not Available Button -->
<button type="button" id="showNotAvailableBtn" class="print-btn" style="background:#f59e42; color:#fff;">
  <i class="fa-solid fa-eye-slash"></i> Show Not Available Items
</button>
<div class="card" style="padding:30px; border-radius:15px; background:#fff; box-shadow:0 8px 25px rgba(0,0,0,0.08);">
  <h3 style="font-size:1.3rem; margin-bottom:20px; color:#2c3e50;">📦 Product List</h3>
  <div class="table-wrapper">
    <form method="post" enctype="multipart/form-data" id="productForm">
      <?php
      $product_result = $conn->query("SELECT products.*, aisles.aisle, aisles.category 
        FROM products 
        LEFT JOIN aisles ON products.aisle_id = aisles.id 
        WHERE products.available=1
        ORDER BY aisles.aisle + 0 ASC, aisles.category ASC, products.name ASC");
      $product_list = [];
      if ($product_result) {
        while ($row = $product_result->fetch_assoc()) {
          $row['available'] = $row['available'] ?? 1;
          $product_list[] = $row;
        }
      }
      $organized_data = [];
      foreach ($product_list as $row) {
        $organized_data[$row['aisle']][$row['category']][] = $row;
      }
      foreach ($organized_data as $aisle => $categories) {
        foreach ($categories as $category => $products) {
          $cat_id = preg_replace("/\s+/", "_", $category);
          echo "<div class='category-block' style='margin-bottom:20px;'>";
          // Only one edit/save button per category
          echo "<div style='margin-bottom:8px;'></div>";
          echo "<h5 class='category-title' onclick=\"toggleCategory('$cat_id')\" style='cursor:pointer; background:#ecf0f1; padding:10px 15px; border-radius:8px;'>🛒 Aisle $aisle: $category  ▼</h5>
              <div id='cat_$cat_id' class='category-table' style='display:none; margin-top:10px;'>
                <table class='modern-table' style='width:100%; border-collapse:collapse;'>
                  <thead>
                    <tr style='background:#f1f2f6; font-weight:600; color:#2c3e50;'>
                      <th style='padding:10px; text-align:center;'>Image</th>
                      <th style='padding:10px;'>Name</th>
                      <th style='padding:10px;'>Price</th>
                      <th style='padding:10px;'>Promo</th>
                      <th style='padding:10px;'>Barcode</th>
                      <th style='padding:10px;'>Description</th>
                      <th style='padding:10px; text-align:center;'>Available</th>
                      <th style='padding:10px; text-align:center;'>
                      <button type='button' class='editBtn editBtnCat' data-cat='$cat_id' style='background:#3498db;color:#fff;padding:8px 18px;border:none;border-radius:6px; cursor:pointer;'>Edit $category</button>
              <button type='submit' class='saveBtn' name='save_category[$cat_id]' data-cat='$cat_id' style='display:none;background:#2ecc71;color:#fff;padding:8px 18px;border:none;border-radius:6px; cursor:pointer;'>Save</button>
            </th></th>
                    </tr>
                  </thead>
                  <tbody>";
          foreach($products as $p){
            $available = isset($p['available']) && $p['available'] ? "checked" : "";
            // PROMO DISPLAY
            if ($p['promo_status'] === "on") {
  $promo = "<div style='line-height:1.1;'>
    <span style='color:#22c55e;font-weight:600;'>On Promo</span><br>
    <span style='font-size:.98em;'>Type: <b>".htmlspecialchars($p['promo_type'] ?: 'N/A')."</b></span><br>
    <span style='font-size:.97em;'>".htmlspecialchars($p['promo_details'])."</span><br>";
  if ($p['promo_price'] !== null && $p['promo_price']!=="")
    $promo .= "<span style='color:#f59e42;font-weight:600;'>₱".number_format($p['promo_price'],2)."</span>";
  // Add Disable Promo button
  $promo .= "<br>
      <button 
        type='button' 
        class='disablePromoBtn'
        data-id='{$p['id']}'
        style='background:#ef4444;color:#fff;padding:3px 10px;border:none;border-radius:6px;cursor:pointer;font-size:0.97em;margin-top:6px;'
      >Disable Promo</button>";
  $promo .= "</div>";

}
else {
  $promo = '<span style="color:#64748b;font-style:italic;">No promo</span>';
  $promo .= "<br><button type='button' class='enablePromoBtn'
    data-id='{$p['id']}'
    data-name='".htmlspecialchars($p['name'], ENT_QUOTES)."'
    data-price='".htmlspecialchars($p['price'], ENT_QUOTES)."'
    data-barcode='".htmlspecialchars($p['barcode'], ENT_QUOTES)."'
    data-description='".htmlspecialchars($p['description'], ENT_QUOTES)."'
    data-category='".htmlspecialchars($category, ENT_QUOTES)."'
    data-image='".(strpos($p['image'], 'data:image')===0 ? $p['image'] : 'data:image/jpeg;base64,'.base64_encode($p['image']))."'
    style='background:#f59e42;color:#fff;padding:3px 10px;border:none;border-radius:6px;cursor:pointer;font-size:0.97em;'
    >Enable Promo</button>";
}
            if (!empty($p['image'])) {
              if (strpos($p['image'], 'data:image') === 0) {
                  $imgTag = "<img src='{$p['image']}' style='width:60px;height:60px;border-radius:10px;object-fit:cover;'/>";
              } else {
                  $imgTag = "<img src='data:image/jpeg;base64," . base64_encode($p['image']) . "' style='width:60px;height:60px;border-radius:10px;object-fit:cover;'/>";
              }
            } else {
              $imgTag = "-";
            }
            echo "<tr style='border-bottom:1px solid #ecf0f1;'>
                    <td style='text-align:center; padding:8px;'>$imgTag</td>
                    <td style='padding:8px;'><input type='text' name='name[{$p['id']}]' value='".htmlspecialchars($p['name'])."' disabled></td>
                    <td style='padding:8px;'><input type='number' step='0.01' name='price[{$p['id']}]' value='".htmlspecialchars($p['price'])."' disabled></td>
                    <td style='padding:8px;'>$promo</td>
                    <td style='padding:8px;'><input type='text' name='barcode[{$p['id']}]' value='".htmlspecialchars($p['barcode'])."' disabled></td>
                    <td style='padding:8px;'><textarea name='description[{$p['id']}]' disabled>".htmlspecialchars($p['description'])."</textarea></td>
                    <td style='text-align:center; padding:8px;'><input type='checkbox' name='available[{$p['id']}]' $available disabled></td>
                    <td style='text-align:center; padding:8px;'></td>
                  </tr>";
          }
          echo "      </tbody>
                    </table>
                  </div>
                </div>";
        }
      }
      ?>
    </form>
    <div style="margin-top:15px; text-align:right;">
      <button type="button" class="print-btn" onclick="printProductList('available')"><i class="fa fa-print"></i> Print Available</button>
      <button type="button" class="print-btn" onclick="printProductList('not_available')"><i class="fa fa-print"></i> Print Not Available</button>
    </div>
  </div>
</div>
<!-- END PRODUCT LIST -->


</div>

      <!-- NOT AVAILABLE ITEMS MODAL -->
<div id="notAvailableModal"
     style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; z-index:9999; background:rgba(0,0,0,0.4); align-items:center; justify-content:center;">
  <div style="background:#fff; border-radius:10px; max-width:900px; width:95%; margin:auto; padding:30px; position:relative; box-shadow:0 8px 28px rgba(0,0,0,0.14);">
    <button onclick="hideNotAvailable()" style="position:absolute; top:10px; right:15px; background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
    <h3 style="margin-bottom:18px; color:#c2410c;">Not Available Products</h3>
    <form method="post" id="bulkMakeAvailableForm" style="margin-bottom:0;">
      <div id="notAvailableContent">Loading...</div>
      <div id="notAvailActions" style="margin-top:12px; display:none;">
        <button type="button" onclick="selectAllNotAvailable(true)" class="print-btn" style="background:#22c55e;">Select All</button>
        <button type="button" onclick="selectAllNotAvailable(false)" class="print-btn" style="background:#ef4444;">Deselect All</button>
        <button type="submit" class="print-btn" name="bulk_make_available" style="background:#10b981;">Make Selected Available</button>
      </div>
    </form>
  </div>
</div>


<!-- ShopEase: Super Admin Create Account Section -->
<div id="admin" class="section">

<?php
$current_role = $_SESSION['role'] ?? '';
$is_main_admin = ($current_role === 'Main Admin');

// Fetch all accounts for Main Admin
$accounts = [];
if ($is_main_admin) {
    $acc_res = $conn->query("SELECT id, first_name, last_name, email, contact, username, role FROM admins ORDER BY id ASC");
    while ($row = $acc_res->fetch_assoc()) $accounts[] = $row;
}
?>
<style>
/* --- Simple, Solid Color Modern Admin UI --- */
#admin-solid {
  max-width: 820px;
  margin: 0 auto;
  font-family: 'Segoe UI', Arial, sans-serif;
  padding: 32px 0;
}
.admin-card {
  background: #fff;
  border-radius: 16px;
  border: 1.5px solid #e1e4eb;
  padding: 28px 32px 18px 32px;
  margin-bottom: 28px;
}
@media (max-width: 700px) {
  .admin-card { padding: 12px 1vw 8px 1vw; }
}
.admin-card h2 {
  font-size: 1.35rem;
  font-weight: bold;
  color: #234678;
  margin-bottom: 18px;
  display: flex; gap: 0.4em; align-items: center;
}
.admin-card form {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 18px 20px;
}
.admin-card label {
  font-weight: 600;
  color: #234678;
  font-size: 1em;
  margin-bottom: 2px;
}
.admin-card input, 
.admin-card select {
  margin-top: 3px;
  padding: 9px 10px;
  font-size: 1em;
  border: 1.5px solid #e1e4eb;
  border-radius: 8px;
  background: #f4f6fa;
  transition: border-color 0.2s;
  outline: none;
  width: 100%;
}
.admin-card input:focus, 
.admin-card select:focus {
  border-color: #2359a7;
  background: #fff;
}
.admin-card input[type="file"] {padding: 2px 0 0 0;}
.admin-card .password-row { display: flex; gap: 10px; grid-column: span 2; }
.admin-card .password-row label { flex: 1; }
.admin-card button[type="submit"] {
  grid-column: span 2;
  background: #2359a7;
  color: #fff; border: none; border-radius: 7px; padding: 10px;
  font-size: 1.06em; font-weight: 700; cursor: pointer;
  margin-top: 8px;
  box-shadow: none;
  letter-spacing: .01em;
  transition: background 0.15s;
}
.admin-card button[type="submit"]:hover { background: #173c6a; }
.admin-card .info-message {
  font-weight: 600;
  margin-top: 10px;
  min-height: 18px;
  color: #2359a7;
  grid-column: span 2;
  font-size: 1em;
}
.admin-card .info-message.error { color: #d13a3a; }
.admin-card .role-description {
  font-size: 0.91em; color: #687086; margin: 4px 0 0 2px; grid-column: span 2;
}
#show-accounts-btn {
  font-size: 1.05em; font-weight: 600;
  margin: 0 0 15px 0; background: #f4f6fa;
  color: #2359a7; border: none; border-radius: 7px;
  padding: 10px 18px;
  cursor: pointer; display: flex; align-items: center; gap: 8px;
  transition: background 0.15s;
}
#show-accounts-btn:hover { background: #2359a7; color: #fff; }
#accounts-list { margin-top: 10px; }
.accounts-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0 4px;
  font-size: 1em;
  background: transparent;
}
.accounts-table th, .accounts-table td {
  padding: 9px 9px;
  text-align: left;
}
.accounts-table th {
  background: #f4f6fa;
  color: #234678;
  font-weight: 700;
  border-radius: 6px 6px 0 0;
  letter-spacing: .01em;
  font-size: 1em;
}
.accounts-table tr {
  background: #fff;
  border-radius: 0 0 8px 8px;
  cursor: pointer;
  transition: background 0.13s;
}
.accounts-table tr:hover, .accounts-table tr:focus-within {
  background: #e9f0fb;
}
.accounts-table input, .accounts-table select {
  font-size: 1em;
  border: 1.2px solid #e1e4eb;
  border-radius: 7px;
  padding: 6px 8px;
  background: #f4f6fa;
  transition: border-color .2s;
}
.accounts-table input:focus, .accounts-table select:focus {
  border-color: #2359a7;
  background: #fff;
}
.modal-bg {
  display: none;
  position: fixed; z-index: 10000; left: 0; top: 0;
  width: 100vw; height: 100vh;
  background: rgba(44, 62, 80, 0.12);
  align-items: center; justify-content: center;
  animation: fadeModalBg .19s;
}
@keyframes fadeModalBg {
  from { opacity: 0; } to { opacity: 1; }
}
.modal-box {
  background: #fff;
  border-radius: 12px;
  min-width: 330px; max-width: 98vw;
  padding: 28px 24px 20px 24px;
  box-shadow: 0 2px 24px 0 rgba(44, 62, 80, 0.10);
  border: 1.2px solid #e1e4eb;
  position: relative;
  font-size: 1em;
}
.modal-close {
  position: absolute; top: 12px; right: 16px;
  background: none; border: none; font-size: 1.7em; color: #234678; cursor: pointer;
}
.modal-box h3 { font-size: 1.13em; font-weight: 700; color: #173c6a; margin-bottom: 12px;}
.modal-box label { font-weight: 600; display: block; margin: 10px 0 5px 0; }
.modal-box input, .modal-box select {
  width: 100%; font-size: 1em; margin-top: 3px; padding: 7px 9px;
  border-radius: 7px; border: 1.3px solid #e1e4eb; background: #f4f6fa;
}
.modal-box input:focus, .modal-box select:focus { border-color:#2359a7; background:#fff;}
.modal-actions { margin-top: 18px; display: flex; gap: 13px; }
.modal-actions button {
  padding: 8px 17px; font-size: 1em; border-radius: 7px; border: none;
  font-weight: 700; cursor: pointer;
}
#modal-save-btn { background: #2359a7; color: #fff; }
#modal-save-btn:hover { background: #173c6a; }
#modal-delete-btn { background: #d13a3a; color: #fff;}
#modal-delete-btn:hover { background: #882828;}
#modal-cancel-btn { background: #f4f6fa; color: #234678;}
#modal-cancel-btn:hover { background: #b9c6d8;}
::-webkit-scrollbar { width: 7px; background: #f4f6fa; }
::-webkit-scrollbar-thumb { background: #e1e4eb; border-radius: 5px;}
::-webkit-scrollbar-thumb:hover { background: #b3bfd9;}
@media (max-width: 540px) {
  .accounts-table th, .accounts-table td { padding: 7px 4px; font-size: 0.94em; }
  .admin-card { padding: 8px 1vw 5px 1vw; }
  .modal-box { min-width: 90vw;}
}
</style>

<div id="admin-solid">
<?php if ($is_main_admin): ?>
  <div class="admin-card" id="superadmin-create-section">
    <h2>New Admin Account</h2>
    <form id="superadmin-create-form" autocomplete="off" enctype="multipart/form-data">
      <label>First Name<span style="color:#d13a3a">*</span>
        <input type="text" name="first_name" required />
      </label>
      <label>Last Name<span style="color:#d13a3a">*</span>
        <input type="text" name="last_name" required />
      </label>
      <label>Birthdate<span style="color:#d13a3a">*</span>
        <input type="date" name="birthdate" id="birthdate" required onchange="generateAge()" />
      </label>
      <label>Age
        <input type="number" name="age" id="age" readonly style="background:#e1e4eb;" />
      </label>
      <label>Email<span style="color:#d13a3a">*</span>
        <input type="email" name="email" required />
      </label>
      <label>Contact Number<span style="color:#d13a3a">*</span>
        <input type="tel" name="contact" required pattern="[0-9\-+\s()]{7,}" />
      </label>
      <label style="grid-column:span 2;">Role<span style="color:#d13a3a">*</span>
        <select name="role" id="role" required onchange="showRoleDesc()">
          <option value="" disabled selected>Select role</option>
          <option value="Main Admin">Main Admin</option>
          <option value="Manager">Manager</option>
          <option value="Inventory Staff">Inventory Staff</option>
        </select>
        <div class="role-description" id="role-desc"></div>
      </label>
      <label>ID Picture<span style="color:#d13a3a">*</span>
        <input type="file" name="id_picture" accept="image/*" required style="background:none;" />
      </label>
      <div class="password-row">
        <label>Username<span style="color:#d13a3a">*</span>
          <input type="text" name="username" required />
        </label>
        <label>Password<span style="color:#d13a3a">*</span>
          <input type="password" name="password" required autocomplete="new-password" />
        </label>
      </div>
      <button type="submit">
        Create
      </button>
      <div class="info-message" id="superadmin-create-message"></div>
    </form>
  </div>

  <button id="show-accounts-btn">Show/Hide Accounts</button>

  <div id="accounts-list" style="display:none;">
    <div class="admin-card" style="margin-bottom:0;">
      <h2>Admin Accounts</h2>
      <div style="overflow-x:auto;">
        <table class="accounts-table" id="accounts-table">
          <thead>
            <tr>
              <th>Name</th><th>Email</th><th>Contact</th><th>Username</th><th>Role</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($accounts as $acc): ?>
          <tr class="account-row"
              data-id="<?= $acc['id'] ?>"
              data-first_name="<?= htmlspecialchars($acc['first_name']) ?>"
              data-last_name="<?= htmlspecialchars($acc['last_name']) ?>"
              data-email="<?= htmlspecialchars($acc['email']) ?>"
              data-contact="<?= htmlspecialchars($acc['contact']) ?>"
              data-username="<?= htmlspecialchars($acc['username']) ?>"
              data-role="<?= htmlspecialchars($acc['role']) ?>"
              tabindex="0"
          >
            <td><?=htmlspecialchars($acc['first_name']." ".$acc['last_name'])?></td>
            <td><?=htmlspecialchars($acc['email'])?></td>
            <td><?=htmlspecialchars($acc['contact'])?></td>
            <td><?=htmlspecialchars($acc['username'])?></td>
            <td><?=htmlspecialchars($acc['role'])?></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Modal -->
  <div class="modal-bg" id="edit-modal-bg">
    <div class="modal-box" id="edit-modal-box">
      <button class="modal-close" onclick="closeModal()">&times;</button>
      <div id="modal-step-choose">
        <h3>Account Options</h3>
        <div style="margin-bottom:15px;">What would you like to do with this account?</div>
        <div class="modal-actions">
          <button id="modal-edit-btn" style="background:#2359a7;color:#fff;">Edit</button>
          <button id="modal-delete-btn" style="background:#d13a3a;color:#fff;">Delete</button>
          <button id="modal-cancel-btn" onclick="closeModal()">Cancel</button>
        </div>
      </div>
      <form id="modal-edit-form" style="display:none;">
        <h3>Edit Account</h3>
        <input type="hidden" name="edit_account_id" id="modal-account-id">
        <label>First Name
          <input type="text" name="first_name" id="modal-account-fn" required>
        </label>
        <label>Last Name
          <input type="text" name="last_name" id="modal-account-ln" required>
        </label>
        <label>Email
          <input type="email" name="email" id="modal-account-email" required>
        </label>
        <label>Contact
          <input type="text" name="contact" id="modal-account-contact" required>
        </label>
        <label>Role
          <select name="role" id="modal-account-role" required>
            <option value="Main Admin">Main Admin</option>
            <option value="Manager">Manager</option>
            <option value="Inventory Staff">Inventory Staff</option>
          </select>
        </label>
        <div class="modal-actions">
          <button type="submit" id="modal-save-btn">Save</button>
          <button type="button" id="modal-cancel-btn2" onclick="closeModal()">Cancel</button>
        </div>
      </form>
      <form id="modal-delete-form" style="display:none;">
        <h3>Delete Account</h3>
        <div style="margin-bottom:10px;">Are you sure you want to delete this account? This cannot be undone.</div>
        <input type="hidden" name="delete_account_id" id="modal-delete-id">
        <div class="modal-actions">
          <button type="submit" id="modal-delete-btn2">Delete</button>
          <button type="button" id="modal-cancel-btn3" onclick="closeModal()">Cancel</button>
        </div>
      </form>
    </div>
  </div>
<?php else: ?>
  <div class="admin-card" style="text-align:center;color:#d13a3a;font-weight:600;">
    You do not have access to admin account management.
  </div>
<?php endif; ?>
</div>

<script>
document.getElementById('show-accounts-btn')?.addEventListener('click',function(){
    const list = document.getElementById('accounts-list');
    list.style.display = (list.style.display === "none" || !list.style.display) ? 'block' : 'none';
});
// Age calculation
function generateAge() {
  const birthdate = document.getElementById('birthdate').value;
  const ageInput = document.getElementById('age');
  if (!birthdate) { ageInput.value = ""; return; }
  const today = new Date();
  const bdate = new Date(birthdate);
  let age = today.getFullYear() - bdate.getFullYear();
  const m = today.getMonth() - bdate.getMonth();
  if (m < 0 || (m === 0 && today.getDate() < bdate.getDate())) age--;
  ageInput.value = age >= 0 ? age : "";
}
const roleMatrixDesc = {
  "Main Admin": "Full access to all system features.",
  "Manager": "Manage products and aisles, view reports.",
  "Inventory Staff": "Add/edit products and aisles only."
};
function showRoleDesc() {
  const role = document.getElementById('role').value;
  document.getElementById('role-desc').textContent = roleMatrixDesc[role] || "";
}

// Modal logic
const modalBg = document.getElementById('edit-modal-bg');
const modalStepChoose = document.getElementById('modal-step-choose');
const modalEditForm = document.getElementById('modal-edit-form');
const modalDeleteForm = document.getElementById('modal-delete-form');
let currentAccount = null;

function closeModal() {
  modalBg.style.display = "none";
  modalStepChoose.style.display = "";
  modalEditForm.style.display = "none";
  modalDeleteForm.style.display = "none";
  currentAccount = null;
}

// Click on account row to open modal
document.querySelectorAll('.account-row').forEach(row => {
  row.addEventListener('click', function(){
    currentAccount = {
      id: row.getAttribute('data-id'),
      first_name: row.getAttribute('data-first_name'),
      last_name: row.getAttribute('data-last_name'),
      email: row.getAttribute('data-email'),
      contact: row.getAttribute('data-contact'),
      username: row.getAttribute('data-username'),
      role: row.getAttribute('data-role')
    };
    modalBg.style.display = "flex";
    modalStepChoose.style.display = "";
    modalEditForm.style.display = "none";
    modalDeleteForm.style.display = "none";
  });
});
// Edit button
document.getElementById('modal-edit-btn').onclick = function(){
  if (!currentAccount) return;
  modalStepChoose.style.display = "none";
  modalEditForm.style.display = "";
  modalDeleteForm.style.display = "none";
  // Populate form
  document.getElementById('modal-account-id').value = currentAccount.id;
  document.getElementById('modal-account-fn').value = currentAccount.first_name;
  document.getElementById('modal-account-ln').value = currentAccount.last_name;
  document.getElementById('modal-account-email').value = currentAccount.email;
  document.getElementById('modal-account-contact').value = currentAccount.contact;
  document.getElementById('modal-account-role').value = currentAccount.role;
};
// Delete button
document.getElementById('modal-delete-btn').onclick = function(){
  if (!currentAccount) return;
  modalStepChoose.style.display = "none";
  modalEditForm.style.display = "none";
  modalDeleteForm.style.display = "";
  document.getElementById('modal-delete-id').value = currentAccount.id;
};
// Modal close
window.closeModal = closeModal;

// Edit submit (AJAX or fallback)
document.getElementById('modal-edit-form').onsubmit = async function(e){
  e.preventDefault();
  const formData = new FormData(this);
  formData.append('action', 'edit_account_modal');
  const resp = await fetch('', { method: 'POST', body: formData });
  const data = await resp.json();
  if (data.success) {
    location.reload();
  } else {
    alert(data.message || "Error updating account.");
  }
};

// Delete submit (AJAX or fallback)
document.getElementById('modal-delete-form').onsubmit = async function(e){
  e.preventDefault();
  const formData = new FormData(this);
  formData.append('action', 'delete_account_modal');
  const resp = await fetch('', { method: 'POST', body: formData });
  const data = await resp.json();
  if (data.success) {
    location.reload();
  } else {
    alert(data.message || "Error deleting account.");
  }
};
</script>

</div>

            </div>




    






<script>

  
function loadActivityLogs() {
  fetch('dashboard.php', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: 'action=get_activity_logs'
  })
  .then(r => r.json())
  .then(logs => {
    let html = '';
    if (!logs.length) {
      html = "<li style='color:#888;'>No recent activity.</li>";
    } else {
      logs.forEach(log => {
        let activity = log.activity.replace(/\(ID:.*?\)/g, '').replace(/ID:.*?(,|\))/g, '').trim();
        const time = new Date(log.created_at);
        const timeStr = time.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        html += `<li><span style="color:#0ea5e9;font-weight:bold;">[${timeStr}]</span> ${activity}</li>`;
      });
    }
    document.getElementById('logList').innerHTML = html;
  });
}


  function loadActivityLogs() {
  fetch('dashboard.php', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: 'action=get_activity_logs'
  })
  .then(r => r.json())
  .then(logs => {
    let html = '';
    if (!logs.length) {
      html = "<li style='color:#888;'>No recent activity.</li>";
    } else {
      logs.forEach(log => {
        const time = new Date(log.created_at);
        const timeStr = time.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        html += `<li><span style="color:#0ea5e9;font-weight:bold;">[${timeStr}]</span> ${log.activity}</li>`;
      });
    }
    document.getElementById('logList').innerHTML = html;
  });
}
window.addEventListener('DOMContentLoaded', loadActivityLogs);
setInterval(loadActivityLogs, 15000); // refresh every 15s

// 1. Edit button shows on dropdown open
function toggleCategory(catId){
  const el = document.getElementById('cat_' + catId);
  el.style.display = el.style.display === 'none' ? 'block' : 'none';
  const btnWrap = el.querySelector('.editCatBtnWrapper');
  if (el.style.display === 'block' && btnWrap) btnWrap.style.display = "block";
  else if (btnWrap) btnWrap.style.display = "none";
  const saveBtn = el.querySelector('.saveBtn');
  if (saveBtn) saveBtn.style.display = "none";
  // Only one edit at a time: hide others
  document.querySelectorAll('.editCatBtnWrapper').forEach(w=>{if(w!==btnWrap)w.style.display='none';});
}
// Edit/save logic for one category at a time
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll(".editBtnCat").forEach(btn => {
    btn.onclick = function() {
      var cat = this.getAttribute("data-cat");
      var table = document.getElementById("cat_" + cat);
      if (!table) return;
      table.querySelectorAll('input, textarea').forEach(input => input.disabled = false);
      btn.style.display = "none";
      table.querySelector('.saveBtn').style.display = "inline-block";
    };
  });
  document.querySelectorAll(".saveBtn").forEach(btn => {
    btn.onclick = function() {};
  });
});

// 2. Not Available Modal: select all/deselect all
function hideNotAvailable() {
  document.getElementById('notAvailableModal').style.display = 'none';
}
document.getElementById('showNotAvailableBtn').addEventListener('click',function(){
  document.getElementById('notAvailableModal').style.display = 'flex';
  fetch('?action=fetch_not_available')
    .then(r=>r.json())
    .then(data=>{
      let out = '';
      let hasItems = false;
      for(const cat in data) {
        hasItems = true;
        out += `<h4 style="margin-top:18px;color:#c2410c;">${cat}</h4>
        <table class="not-available-table" style="width:100%;margin-bottom:18px;">
        <thead>
          <tr>
            <th><input type="checkbox" onclick="selectAllNotAvailableCat('${cat}', this.checked)"></th>
            <th>Image</th><th>Name</th><th>Price</th><th>Promo</th><th>Barcode</th><th>Description</th>
          </tr>
        </thead>
        <tbody>`;
        data[cat].forEach(item=>{
          let promo = "-";
          if(item.promo_status==="on") {
            promo = `<span style='color:#22c55e;font-weight:600;'>${item.promo_type} Promo</span><br>
                     <span style='font-size:.98em;'>${item.promo_details}</span><br>`;
            if(item.promo_price!==null && item.promo_price!=="")
              promo += `<span style='color:#f59e42;font-weight:600;'>₱${parseFloat(item.promo_price).toFixed(2)}</span>`;
          } else {
            promo = '<span style="color:#64748b;font-style:italic;">No promo</span>';
          }
          let imgTag = item.image && item.image.startsWith("data:image")
            ? `<img src="${item.image}" style='width:50px;height:50px;border-radius:6px;object-fit:cover;'/>`
            : "-";
          out += `<tr>
            <td style="text-align:center;">
              <input type="checkbox" class="notAvailCB notAvailCB_${cat}" name="not_available_ids[]" value="${item.id}">
            </td>
            <td style="text-align:center;">${imgTag}</td>
            <td>${item.name}</td>
            <td>₱${parseFloat(item.price).toFixed(2)}</td>
            <td>${promo}</td>
            <td>${item.barcode}</td>
            <td>${item.description}</td>
          </tr>`;
        });
        out += "</tbody></table>";
      }
      document.getElementById('notAvailableContent').innerHTML = out;
      document.getElementById('notAvailActions').style.display = hasItems ? "block" : "none";
    });
});
function selectAllNotAvailable(state) {
  document.querySelectorAll('.notAvailCB').forEach(cb=>cb.checked=state);
}
function selectAllNotAvailableCat(cat, state) {
  document.querySelectorAll('.notAvailCB_'+cat).forEach(cb=>cb.checked=state);
}

// 3. Promo Management: dynamic fields and auto-calc
document.addEventListener('DOMContentLoaded', function() {
  const promoTypeSel = document.getElementById('promoTypeSelect');
  const priceInput = document.querySelector('[name="price"]');
  const promoPriceInput = document.getElementById('promoPriceInput');
  const dynamicFieldDiv = document.getElementById('dynamicPromoFields');
  function removePromoFields() { dynamicFieldDiv.innerHTML = ''; }
  function addPromoFields(type) {
    removePromoFields();
    if(type==="discount_percent") {
      dynamicFieldDiv.innerHTML = `
        <label>Discount Percentage (%)</label>
        <input type="number" min="1" max="100" id="promoPercentInput" placeholder="e.g. 10" style="width:100%;">
      `;
      document.getElementById('promoPercentInput').addEventListener('input',function(){
        let percent = parseFloat(this.value);
        let price = parseFloat(priceInput.value);
        if(!isNaN(percent) && !isNaN(price)) {
          promoPriceInput.value = (price*(1-percent/100)).toFixed(2);
        }
      });
    } else if(type==="discount_fixed") {
      dynamicFieldDiv.innerHTML = `
        <label>Discount Amount (₱)</label>
        <input type="number" min="1" id="promoFixedInput" placeholder="e.g. 50" style="width:100%;">
      `;
      document.getElementById('promoFixedInput').addEventListener('input',function(){
        let fixed = parseFloat(this.value);
        let price = parseFloat(priceInput.value);
        if(!isNaN(fixed) && !isNaN(price)) {
          let promoP = price-fixed;
          promoPriceInput.value = promoP>0?promoP.toFixed(2):"0.00";
        }
      });
    } else if(type==="bogo") {
      dynamicFieldDiv.innerHTML = `
        <label>Minimum Quantity to Buy</label>
        <input type="number" min="1" id="promoBogoQty" placeholder="e.g. 1" style="width:100%;">
        <label>Free Product (ID or Name)</label>
        <input type="text" id="promoBogoFree" placeholder="Product name or id" style="width:100%;">
      `;
    } else if(type==="bundle") {
      dynamicFieldDiv.innerHTML = `
        <label>Bundle Product Names (comma separated)</label>
        <input type="text" id="promoBundleProducts" placeholder="Hotdog, Bun, Drink" style="width:100%;">
        <label>Bundle Price</label>
        <input type="number" min="1" id="promoBundlePrice" placeholder="e.g. 99" style="width:100%;">
      `;
      document.getElementById('promoBundlePrice').addEventListener('input',function(){
        promoPriceInput.value = this.value;
      });
    } else if(type==="category") {
      dynamicFieldDiv.innerHTML = `
        <label>Category Discount (%)</label>
        <input type="number" min="1" max="100" id="promoCatPercent" placeholder="e.g. 15" style="width:100%;">
      `;
      document.getElementById('promoCatPercent').addEventListener('input',function(){
        let percent = parseFloat(this.value);
        let price = parseFloat(priceInput.value);
        if(!isNaN(percent) && !isNaN(price)) {
          promoPriceInput.value = (price*(1-percent/100)).toFixed(2);
        }
      });
    }
  }
  if(promoTypeSel){
    promoTypeSel.addEventListener('change', function() {
      addPromoFields(this.value);
    });
  }
});
</script>




  <script>
  // Enable one edit button per category (all rows editable at once)
  document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll(".editBtnCat").forEach(btn => {
      btn.onclick = function() {
        var cat = this.getAttribute("data-cat");
        var table = document.getElementById("cat_" + cat);
        if (!table) return;
        table.querySelectorAll('input, textarea').forEach(input => input.disabled = false);
        btn.style.display = "none";
        document.querySelector('.saveBtn[data-cat="'+cat+'"]').style.display = "inline-block";
      };
    });
    document.querySelectorAll(".saveBtn").forEach(btn => {
      btn.onclick = function() {
        // let the form submit normally (no JS here)
      };
    });
    // Hide per-row edit buttons (if they exist)
    document.querySelectorAll('.editBtn:not(.editBtnCat)').forEach(btn=>btn.style.display='none');
  });

  // Not Available Modal with select all per category
  function hideNotAvailable() {
    document.getElementById('notAvailableModal').style.display = 'none';
  }
  document.getElementById('showNotAvailableBtn').addEventListener('click',function(){
    document.getElementById('notAvailableModal').style.display = 'flex';
    fetch('?action=fetch_not_available')
      .then(r=>r.json())
      .then(data=>{
        let out = '';
        if(Object.keys(data).length === 0) out = "<em>No not available products.</em>";
        else {
          for(const cat in data) {
            out += `<form method="post" style="margin-bottom:16px;border-bottom:1px solid #ececec;padding-bottom:12px;">
              <h4 style="margin-top:18px;color:#c2410c;">${cat}</h4>
              <button type="button" class="print-btn" onclick="selectAllNotAvailableCat('${cat}', true)">Select All</button>
              <button type="button" class="print-btn" onclick="selectAllNotAvailableCat('${cat}', false)">Deselect All</button>
              <button type="submit" class="print-btn" name="bulk_make_available" style="background:#10b981;">Make Selected Available</button>
              <table class="not-available-table" style="width:100%;margin-top:10px;">
                <thead>
                  <tr>
                    <th><input type="checkbox" onclick="selectAllNotAvailableCat('${cat}', this.checked)"></th>
                    <th>Image</th><th>Name</th><th>Price</th><th>Promo</th><th>Barcode</th><th>Description</th>
                  </tr>
                </thead>
                <tbody>`;
            data[cat].forEach(item=>{
              let promo = "-";
              if(item.promo_status==="on") {
                promo = `<span style='color:#22c55e;font-weight:600;'>${item.promo_type} Promo</span><br>
                         <span style='font-size:.98em;'>${item.promo_details}</span><br>`;
                if(item.promo_price!==null && item.promo_price!=="")
                  promo += `<span style='color:#f59e42;font-weight:600;'>₱${parseFloat(item.promo_price).toFixed(2)}</span>`;
              } else {
                promo = '<span style="color:#64748b;font-style:italic;">No promo</span>';
              }
              let imgTag = item.image && item.image.startsWith("data:image")
                ? `<img src="${item.image}" style='width:50px;height:50px;border-radius:6px;object-fit:cover;'/>`
                : "-";
              out += `<tr>
                <td style="text-align:center;">
                  <input type="checkbox" class="notAvailCB notAvailCB_${cat}" name="not_available_ids[]" value="${item.id}">
                </td>
                <td style="text-align:center;">${imgTag}</td>
                <td>${item.name}</td>
                <td>₱${parseFloat(item.price).toFixed(2)}</td>
                <td>${promo}</td>
                <td>${item.barcode}</td>
                <td>${item.description}</td>
              </tr>`;
            });
            out += "</tbody></table></form>";
          }
        }
        document.getElementById('notAvailableContent').innerHTML = out;
      });
  });
  // Per category select all/deselect all
  function selectAllNotAvailableCat(cat, state) {
    document.querySelectorAll('.notAvailCB_'+cat).forEach(cb=>cb.checked=state);
  }
  
  
  // Section Tab Switch with sessionStorage for restoring after reload
    function showSection(sectionId, element) {
      sessionStorage.setItem('activeSection', sectionId);
      const sections = document.querySelectorAll('.section');
      sections.forEach(section => section.classList.remove('active'));
      document.getElementById(sectionId).classList.add('active');
      const links = document.querySelectorAll('.sidebar a');
      links.forEach(link => link.classList.remove('active'));
      if(element) element.classList.add('active');
    }
    // On page load, restore last active section if any
    document.addEventListener('DOMContentLoaded', function() {
      const sec = sessionStorage.getItem('activeSection') || 'home';
      const link = Array.from(document.querySelectorAll('.sidebar a')).find(a => a.href.includes(`#${sec}`));
      if(link) showSection(sec, link);
    });

    // For product section, always stay after actions
    document.querySelectorAll('form').forEach(form=>{
      form.addEventListener('submit',function(e){
        if(form.id === "productForm" || form.id === "addProductForm"){
          sessionStorage.setItem('activeSection','product');
        }
      });
    });

// Show modal and fetch data
document.getElementById('showNotAvailableBtn').addEventListener('click',function(){
  document.getElementById('notAvailableModal').style.display = 'flex';
  fetch('?action=fetch_not_available')
    .then(r=>r.json())
    .then(data=>{
      let out = '';
      let hasItems = false;
      for(const cat in data) {
        hasItems = true;
        out += `<h4 style="margin-top:18px;color:#c2410c;">${cat}</h4>
        <table class="not-available-table" style="width:100%;margin-bottom:18px;">
        <thead>
          <tr>
            <th></th>
            <th>Image</th><th>Name</th><th>Price</th><th>Promo</th><th>Barcode</th><th>Description</th>
          </tr>
        </thead>
        <tbody>`;
        data[cat].forEach(item=>{
          let promo = "-";
          if(item.promo_status==="on") {
            promo = `<span style='color:#22c55e;font-weight:600;'>${item.promo_type} Promo</span><br>
                     <span style='font-size:.98em;'>${item.promo_details}</span><br>`;
            if(item.promo_price!==null && item.promo_price!=="")
              promo += `<span style='color:#f59e42;font-weight:600;'>₱${parseFloat(item.promo_price).toFixed(2)}</span>`;
          } else {
            promo = '<span style="color:#64748b;font-style:italic;">No promo</span>';
          }
          let imgTag = item.image && item.image.startsWith("data:image")
            ? `<img src="${item.image}" style='width:50px;height:50px;border-radius:6px;object-fit:cover;'/>`
            : "-";
          out += `<tr>
            <td style="text-align:center;">
              <input type="checkbox" class="notAvailCB notAvailCB_${cat}" name="not_available_ids[]" value="${item.id}">
            </td>
            <td style="text-align:center;">${imgTag}</td>
            <td>${item.name}</td>
            <td>₱${parseFloat(item.price).toFixed(2)}</td>
            <td>${promo}</td>
            <td>${item.barcode}</td>
            <td>${item.description}</td>
          </tr>`;
        });
        out += "</tbody></table>";
      }
      document.getElementById('notAvailableContent').innerHTML = out;
      document.getElementById('notAvailActions').style.display = hasItems ? "block" : "none";
    });
});
function selectAllNotAvailable(state) {
  document.querySelectorAll('.notAvailCB').forEach(cb=>cb.checked=state);
}
function selectAllNotAvailableCat(cat, state) {
  document.querySelectorAll('.notAvailCB_'+cat).forEach(cb=>cb.checked=state);
}
function hideNotAvailable() {
  document.getElementById('notAvailableModal').style.display = 'none';
}
    // Notification Toast
    function showNotification(message, type = "success", duration = 4000) {
      const container = document.getElementById("notificationContainer");
      const notif = document.createElement("div");
      notif.className = `toast ${type}`;
      notif.innerHTML = `
        <span class="icon">${
            type === "success" ? "✅" :
            type === "error"   ? "❌" :
            type === "warning" ? "⚠️" :
                                 "ℹ️"
        }</span>
        <span>${message}</span>
      `;
      container.appendChild(notif);
      setTimeout(() => notif.classList.add("show"), 50);
      notif.addEventListener("click", () => {
        notif.classList.remove("show");
        setTimeout(() => notif.remove(), 300);
      });
      setTimeout(() => {
        notif.classList.remove("show");
        setTimeout(() => notif.remove(), 300);
      }, duration);
    }


    // Message Admin + Log
    function messageAdmin() {
      const recipient = prompt("Enter the recipient admin's email:");
      if(recipient) {
        const message = prompt("Enter your message:");
        if(message) {
          alert(`Message sent to ${recipient}: ${message}`);
          addLog(`Message sent to ${recipient}`);
        }
      }
    }
    function logout() {
      if(confirm("Are you sure you want to logout?")){
        alert("You have been logged out.");
        // window.location.href = "login.html";
      }
    }
    function addLog(activity) {
      const logList = document.getElementById('logList');
      const li = document.createElement('li');
      li.textContent = activity;
      logList.prepend(li);
    }
    // Clock/Calendar
    function updateClock() {
      const clock = document.querySelector("#clock span");
      const now = new Date();
      clock.textContent = now.toLocaleTimeString("en-US", { hour12: true });
    }
    setInterval(updateClock, 1000); updateClock();
    function updateCalendar() {
      const calendar = document.querySelector("#calendar span");
      const now = new Date();
      const options = { weekday: "long", year: "numeric", month: "long", day: "numeric" };
      calendar.textContent = now.toLocaleDateString("en-US", options);
    }
    updateCalendar();

    // Toggle category block in product list
    function toggleCategory(catId){
      const el = document.getElementById('cat_' + catId);
      el.style.display = el.style.display === 'none' ? 'block' : 'none';
    }
    function enableEdit(catId){
      const table = document.getElementById('cat_' + catId);
      const inputs = table.querySelectorAll('input, textarea');
      inputs.forEach(input => input.disabled = false);
      table.querySelector('.editBtn').style.display = 'none';
      table.querySelector('.saveBtn').style.display = 'inline-block';
    }

    // Live preview for uploaded product image
    function showPreview(event) {
      const input = event.target;
      const previewDiv = document.getElementById('imagePreview');
      previewDiv.innerHTML = '';
      if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
          const img = document.createElement('img');
          img.src = e.target.result;
          img.style.maxWidth = '120px';
          img.style.maxHeight = '120px';
          img.style.borderRadius = '10px';
          img.style.display = 'block';
          previewDiv.appendChild(img);
        }
        reader.readAsDataURL(input.files[0]);
      }
    }

    // Enable/disable price when promo price set
    if (document.getElementById('promoInput') && document.getElementById('priceInput')) {
      document.getElementById('promoInput').addEventListener('input', ()=>{
        const promoInput = document.getElementById('promoInput');
        const priceInput = document.getElementById('priceInput');
        priceInput.disabled = promoInput.value ? true : false;
      });
    }

    

    // =======================
    // AISLE Section JS
    // =======================
    document.addEventListener('DOMContentLoaded', () => {
      // Global Edit Button
      if (document.getElementById('editBtn')) {
        document.getElementById('editBtn').addEventListener('click', () => {
          document.querySelectorAll('.aisle-input').forEach(i => i.disabled = false);
          document.querySelectorAll('.delete-btn').forEach(b => b.style.display = 'inline-block');
          document.getElementById('saveBtn').style.display = 'inline-block';
          document.getElementById('editBtn').style.display = 'none';
        });
      }
      // Highlight duplicates before saving
      if (document.getElementById('aisleForm')) {
        document.getElementById('aisleForm').addEventListener('submit', (e) => {
          const inputs = document.querySelectorAll('.aisle-input');
          const values = Array.from(inputs).map(i => i.value.trim());
          const duplicates = values.filter((v, i, a) => a.indexOf(v) !== i);
          inputs.forEach(i => i.style.borderColor = '#d1d5db');
          if (duplicates.length > 0) {
            e.preventDefault();
            inputs.forEach(i => {
              if (duplicates.includes(i.value.trim())) {
                i.style.borderColor = '#f87171';
              }
            });
            showNotification("Duplicate aisle numbers found!", "warning");
          }
        });
      }
      // Validate Add Aisle Form before submit
      if (document.getElementById('addAisleForm')) {
        document.getElementById('addAisleForm').addEventListener('submit', (e) => {
          const newAisle = document.getElementById('newAisle').value.trim();
          const inputs = document.querySelectorAll('.aisle-input');
          const duplicate = Array.from(inputs).some(i => i.value.trim() === newAisle);
          if (duplicate) {
            e.preventDefault();
            showNotification("⚠️ Aisle number already exists!", "warning");
          }
        });
      }
      // Delete aisle confirm popup
      document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function() {
          const aisleId = this.getAttribute("data-id");
          const oldBox = document.getElementById("confirmBox");
          if (oldBox) oldBox.remove();
          const confirmBox = document.createElement('div');
          confirmBox.id = "confirmBox";
          confirmBox.innerHTML = `
              <div style="background:white; padding:20px; border-radius:14px; box-shadow:0 8px 25px rgba(0,0,0,0.3); font-family:'Inter',sans-serif; max-width:320px; margin:auto; text-align:center;">
                  <p style="font-weight:600; font-size:1rem; color:#1f2937; margin-bottom:20px;">⚠️ Are you sure you want to delete this aisle?</p>
                  <div style="display:flex; justify-content:space-between; gap:10px;">
                      <button id="confirmYes" style="flex:1; background:#ef4444; color:white; border:none; padding:10px; border-radius:10px; font-weight:600; cursor:pointer;">Yes, Delete</button>
                      <button id="confirmNo" style="flex:1; background:#9ca3af; color:white; border:none; padding:10px; border-radius:10px; font-weight:600; cursor:pointer;">Cancel</button>
                  </div>
              </div>
          `;
          confirmBox.style.cssText = `
              position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
              z-index: 99999; background: rgba(0,0,0,0.4);
              width:100%; height:100%; display:flex; align-items:center; justify-content:center;`;
          document.body.appendChild(confirmBox);
          confirmBox.querySelector('#confirmYes').addEventListener('click', () => {
            confirmBox.remove();
            const form = document.createElement("form");
            form.method = "POST";
            form.innerHTML = `<input type="hidden" name="delete_aisle_id" value="${aisleId}">`;
            document.body.appendChild(form);
            form.submit();
          });
          confirmBox.querySelector('#confirmNo').addEventListener('click', () => {
            confirmBox.remove();
          });
        });
      });
    });


// Product Edit/Save logic
    function enableEdit(catId){
      const table = document.getElementById('cat_' + catId);
      const inputs = table.querySelectorAll('input, textarea');
      inputs.forEach(input => input.disabled = false);
      table.querySelector('.editBtn').style.display = 'none';
      table.querySelector('.saveBtn').style.display = 'inline-block';
    }
    function toggleCategory(catId){
      const el = document.getElementById('cat_' + catId);
      el.style.display = el.style.display === 'none' ? 'block' : 'none';
    }


    function printProductList(type) {
  if (type === 'available') {
    // Get all category blocks
    const categoryBlocks = document.querySelectorAll('.category-block');
    let out = '';
    categoryBlocks.forEach(block => {
      // Get the category title
      const catTitle = block.querySelector('.category-title')?.textContent || 'Category';
      out += `<h4 style="margin-top:18px;color:#34495e;">${catTitle}</h4>`;
      // Get the table for this category
      const table = block.querySelector('.category-table table');
      if (table) {
        out += table.outerHTML;
      }
    });

    let win = window.open('', '', 'width=900,height=600');
    win.document.write('<html><head><title>Product List - Available</title>');
    win.document.write('<style>table{border-collapse:collapse;width:100%;}th,td{padding:8px 7px;border:1px solid #e5e7eb;}th{background:#f1f5f9;} h4{margin-top:18px;color:#34495e;}</style>');
    win.document.write('</head><body>');
    win.document.write('<h2>ShopEase - Available Products (by Category)</h2>');
    win.document.write(out);
    win.document.write('</body></html>');
    win.document.close();
    win.print();
    return;
  }

  // Not available (unchanged)
  if (type === 'not_available') {
    fetch('?action=fetch_not_available')
      .then(r=>r.json())
      .then(data=>{
        let out = '';
        for(const cat in data) {
          out += `<h4 style="margin-top:18px;color:#c2410c;">${cat}</h4>
          <table style="width:100%;border-collapse:collapse;">
          <thead>
            <tr>
              <th>Image</th><th>Name</th><th>Price</th><th>Promo</th><th>Barcode</th><th>Description</th>
            </tr>
          </thead>
          <tbody>`;
          data[cat].forEach(item=>{
            let promo = "-";
            if(item.promo_status==="on") {
              promo = `<span style='color:#22c55e;font-weight:600;'>${item.promo_type} Promo</span><br>
                       <span style='font-size:.98em;'>${item.promo_details}</span><br>`;
              if(item.promo_price!==null && item.promo_price!=="")
                promo += `<span style='color:#f59e42;font-weight:600;'>₱${parseFloat(item.promo_price).toFixed(2)}</span>`;
            } else {
              promo = '<span style="color:#64748b;font-style:italic;">No promo</span>';
            }
            let imgTag = item.image && item.image.startsWith("data:image")
              ? `<img src="${item.image}" style='width:50px;height:50px;border-radius:6px;object-fit:cover;'/>`
              : "-";
            out += `<tr>
              <td style="text-align:center;">${imgTag}</td>
              <td>${item.name}</td>
              <td>₱${parseFloat(item.price).toFixed(2)}</td>
              <td>${promo}</td>
              <td>${item.barcode}</td>
              <td>${item.description}</td>
            </tr>`;
          });
          out += "</tbody></table>";
        }
        let win = window.open('', '', 'width=900,height=600');
        win.document.write('<html><head><title>Product List - Not Available</title>');
        win.document.write('<style>table{border-collapse:collapse;width:100%;}th,td{padding:8px 7px;border:1px solid #e5e7eb;}th{background:#f1f5f9;}</style>');
        win.document.write('</head><body>');
        win.document.write('<h2>ShopEase - Not Available Products</h2>');
        win.document.write(out);
        win.document.write('</body></html>');
        win.document.close();
        win.print();
      });
    return;
  }
}


document.addEventListener('DOMContentLoaded', function() {
  // Handle Enable Promo button
  document.querySelectorAll('.enablePromoBtn').forEach(btn => {
    btn.addEventListener('click', function() {
      const form = document.getElementById('addProductForm');
      if (!form) return;
      // Set fields
      form.querySelector('[name="name"]').value = this.dataset.name || '';
      form.querySelector('[name="price"]').value = this.dataset.price || '';
      form.querySelector('[name="barcode"]').value = this.dataset.barcode || '';
      form.querySelector('[name="description"]').value = this.dataset.description || '';
      // Set category
      let catSel = form.querySelector('[name="category"]');
      if (catSel) {
        Array.from(catSel.options).forEach(opt => {
          opt.selected = (opt.value === this.dataset.category);
        });
      }
      // Image preview
      const imgData = this.dataset.image;
      if (imgData) {
        let previewDiv = document.getElementById('imagePreview');
        previewDiv.innerHTML = '';
        let img = document.createElement('img');
        img.src = imgData;
        img.style.maxWidth = '120px';
        img.style.maxHeight = '120px';
        img.style.borderRadius = '10px';
        img.style.display = 'block';
        previewDiv.appendChild(img);
      }
      // Set promo fields
      form.querySelector('[name="promo_status"]').value = 'on';
      form.querySelector('[name="promo_type"]').value = '';
      form.querySelector('[name="promo_details"]').value = '';
      form.querySelector('[name="promo_price"]').value = '';
      form.querySelector('[name="promo_start"]').value = '';
      form.querySelector('[name="promo_end"]').value = '';
      // Set hidden edit_product_id (for update in backend)
      let hiddenId = form.querySelector('[name="edit_product_id"]');
      if (hiddenId) hiddenId.value = this.dataset.id;

      // Change Add button to Update button
      let addBtn = form.querySelector('[name="add_product"]');
      let updateBtn = form.querySelector('[name="update_product"]');
      if (!updateBtn) {
        updateBtn = document.createElement("button");
        updateBtn.type = "submit";
        updateBtn.name = "update_product";
        updateBtn.style.background = "#2ecc71";
        updateBtn.style.color = "#fff";
        updateBtn.style.padding = "12px 25px";
        updateBtn.style.border = "none";
        updateBtn.style.borderRadius = "8px";
        updateBtn.style.fontWeight = "600";
        updateBtn.style.cursor = "pointer";
        updateBtn.textContent = "Update Product Promo";
        addBtn.parentNode.insertBefore(updateBtn, addBtn);
      }
      addBtn.style.display = "none";
      updateBtn.style.display = "inline-block";

      // Scroll to form and highlight promo section
      form.scrollIntoView({behavior: "smooth"});
      let fieldset = form.querySelector('fieldset');
      if (fieldset) {
        fieldset.style.boxShadow = '0 0 6px 2px #f59e42';
        setTimeout(() => fieldset.style.boxShadow = '', 1200);
      }
    });
  });

  // If user cancels edit, you could show add_button again (not implemented here)
});


// Place this after your DOMContentLoaded block, or in the appropriate JS file

document.addEventListener('DOMContentLoaded', function() {
  // Attach to both new and existing Disable Promo buttons
  document.querySelectorAll('.disablePromoBtn').forEach(btn => {
    btn.addEventListener('click', function() {
      const prodId = this.dataset.id;
      const btnElem = this;
      btnElem.disabled = true;
      btnElem.textContent = "Disabling...";

      fetch('update_promo_status.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `id=${encodeURIComponent(prodId)}&promo_status=off`
      })
      .then(resp => resp.json())
      .then(data => {
        if(data.success) {
          // Replace promo cell with "No promo"
          const promoTd = btnElem.closest('td');
          promoTd.innerHTML = `<span style='color:#64748b;font-style:italic;'>No promo</span>`;
          showToast("Promo disabled successfully.", "success");
        } else {
          btnElem.textContent = "Disable Promo";
          btnElem.disabled = false;
          showToast("Failed to disable promo.", "error");
        }
      })
      .catch(() => {
        btnElem.textContent = "Disable Promo";
        btnElem.disabled = false;
        showToast("Failed to disable promo.", "error");
      });
    });
  });
});

// Toast helper (add this if not present)
function showToast(msg, type="success") {
  let container = document.getElementById('notificationContainer');
  if (!container) {
    container = document.createElement('div');
    container.id = 'notificationContainer';
    document.body.appendChild(container);
  }
  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  toast.textContent = msg;
  container.appendChild(toast);
  setTimeout(() => toast.classList.add("show"), 50);
  setTimeout(() => {
    toast.classList.remove("show");
    setTimeout(() => toast.remove(), 350);
  }, 1800);
}


document.addEventListener('DOMContentLoaded', function() {
  function bindDisablePromoBtns() {
    document.querySelectorAll('.disablePromoBtn').forEach(btn => {
      btn.onclick = function() {
        const prodId = this.dataset.id;
        const btnElem = this;
        btnElem.disabled = true;
        btnElem.textContent = "Disabling...";
        fetch('update_promo_status.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/x-www-form-urlencoded'},
          body: `id=${encodeURIComponent(prodId)}&promo_status=off`
        })
        .then(resp => resp.json())
        .then(data => {
          if(data.success) {
            // Replace promo cell with "No promo"
            const promoTd = btnElem.closest('td');
            promoTd.innerHTML = `<span style='color:#64748b;font-style:italic;'>No promo</span>`;
            showToast("Promo disabled successfully.", "success");
          } else {
            btnElem.textContent = "Disable Promo";
            btnElem.disabled = false;
            showToast("Failed to disable promo.", "error");
          }
        })
        .catch(() => {
          btnElem.textContent = "Disable Promo";
          btnElem.disabled = false;
          showToast("Failed to disable promo.", "error");
        });
      }
    });
  }
  bindDisablePromoBtns();
});
function showToast(msg, type="success") {
  let container = document.getElementById('notificationContainer');
  if (!container) {
    container = document.createElement('div');
    container.id = 'notificationContainer';
    document.body.appendChild(container);
  }
  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  toast.textContent = msg;
  container.appendChild(toast);
  setTimeout(() => toast.classList.add("show"), 50);
  setTimeout(() => {
    toast.classList.remove("show");
    setTimeout(() => toast.remove(), 350);
  }, 1800);
}

// Helper: disable all promo action buttons
function setPromoButtonsState(catId, enabled) {
  const table = document.getElementById('cat_' + catId);
  if (!table) return;
  table.querySelectorAll('.enablePromoBtn, .disablePromoBtn').forEach(btn => {
    btn.disabled = !enabled;
    btn.style.opacity = enabled ? "1" : "0.5";
    btn.style.cursor = enabled ? "pointer" : "not-allowed";
  });
}

document.addEventListener('DOMContentLoaded', function() {
  // Initially disable all promo action buttons
  document.querySelectorAll('.enablePromoBtn, .disablePromoBtn').forEach(btn => {
    btn.disabled = true;
    btn.style.opacity = "0.5";
    btn.style.cursor = "not-allowed";
  });

  // When edit button is clicked, enable promo action buttons in that category
  document.querySelectorAll('.editBtnCat').forEach(editBtn => {
    editBtn.addEventListener('click', function() {
      const catId = this.dataset.cat;
      setPromoButtonsState(catId, true);
      // Optionally: disable edit buttons for other categories, or visually indicate edit mode
    });
  });

  // If you have a Save/Cancel button, you can disable again:
  document.querySelectorAll('.saveBtn').forEach(saveBtn => {
    saveBtn.addEventListener('click', function() {
      const catId = this.dataset.cat;
      setPromoButtonsState(catId, false);
    });
  });
});
  </script>
</body>
</html>


