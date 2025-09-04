<?php
$conn = new mysqli('localhost', 'root', '', 'ecommerce_db');

$search = $_GET['search'] ?? '';

if ($search) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE barcode = ? OR name LIKE ?");
    $searchLike = "%" . $search . "%";
    $stmt->bind_param("ss", $search, $searchLike);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $usePromo = $row['promo_status'] === 'on' && !empty($row['promo_price']) && is_numeric($row['promo_price']);
            $promoPrice = $usePromo ? $row['promo_price'] : null;
            $displayPrice = $usePromo ? $promoPrice : $row['price'];
            $priceHtml = $usePromo
                ? "<span style='color:#ef4444;font-weight:700;'>₱" . number_format($promoPrice, 2) . "</span>
                   <span style='text-decoration:line-through;color:#888;font-size:0.95em;margin-left:8px;'>₱" . number_format($row['price'], 2) . "</span>"
                : "₱" . number_format($row['price'], 2);

            $aisle = htmlspecialchars($row['aisle_location'] ?? 'Aisle 1', ENT_QUOTES);

            $addToCartAttr = "addToCart('".htmlspecialchars($row['name'], ENT_QUOTES)."', {$displayPrice}, this" 
                . ($usePromo ? ", {$promoPrice}" : "") . ")";

            echo "<div class='product'>
                <span>{$row['name']} - {$priceHtml}</span>
                <div class='product-info'>
                    <button class='location-button' onclick=\"showProductLocation('{$aisle}')\">
                        View Location
                    </button>
                    <button class='cart-button' onclick=\"$addToCartAttr\">
                        <span class='add-to-cart'>Add to Cart</span>
                        <span class='added'>Added</span>
                        <i class='fas fa-shopping-cart'></i>
                        <i class='fas fa-box'></i>
                    </button>
                </div>
            </div>";
        }
    } else {
        echo "<div class='error-message'>Item not found</div>";
    }
}
?>

<style>
    .product {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        background-color: white;
        margin: 10px 0;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .product-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .aisle-info {
        padding: 5px 10px;
        background-color: #f1f1f1;
        border-radius: 5px;
        font-weight: bold;
        color: #333;
    }

    .location-button {
        background-color: #007bff;
        border: none;
        color: white;
        padding: 5px 10px;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.3s;
    }

    .location-button:hover {
        background-color: #0056b3;
    }
</style>