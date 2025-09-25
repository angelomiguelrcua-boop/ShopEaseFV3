<?php
$conn = new mysqli('localhost', 'root', '', 'ecommerce_db');

$search = $_GET['search'] ?? '';

if ($search) {
    // Only get available products
    $stmt = $conn->prepare("SELECT * FROM products WHERE (barcode = ? OR name LIKE ?) AND available = 1");
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
                ? "<span class='text-[#ef4444] font-bold'>₱" . number_format($promoPrice, 2) . "</span>
                   <span class='line-through text-gray-400 font-semibold ml-2'>₱" . number_format($row['price'], 2) . "</span>"
                : "<span class='text-[#2563eb] font-bold'>₱" . number_format($row['price'], 2) . "</span>";

            // Handle missing aisle
            $aisle_number = (isset($row['aisle']) && trim($row['aisle']) !== '') ? $row['aisle'] : null;
            if ($aisle_number) {
                $aisle = "Aisle " . htmlspecialchars($aisle_number, ENT_QUOTES);
                // Avoid PHP parse error by using single quotes and addslashes for JS argument
                $viewLocationButton = '<button class="view-location-btn font-bold text-base px-4 py-2 rounded-full bg-[#10b981] text-white shadow-md transition-all duration-200 hover:bg-[#13cc91] focus:outline-none w-full sm:w-auto" onclick="showProductLocation(\'' . addslashes($aisle) . '\')">Locate Product</button>';
            } else {
                $aisle = "<span class='aisle-info' style='background:#fcc;'>No aisle set</span>";
                $viewLocationButton = "";
            }

            // Product image logic (default fallback if empty)
            $imgSrc = !empty($row['image']) ? htmlspecialchars($row['image'], ENT_QUOTES) : 'ASSETS/placeholder.png';

            echo "<div class='product-card flex flex-col sm:flex-row items-center justify-between mb-4 bg-white rounded-xl shadow-lg px-4 py-3 gap-4 sm:gap-0'>
                <div class='flex items-center gap-3 w-full sm:w-auto'>
                    <img src='" . $imgSrc . "' class='product-img' id='prodimg-" . $row['id'] . "' alt='" . htmlspecialchars($row['name'], ENT_QUOTES) . "' style='width:54px;height:54px;object-fit:cover;border-radius:10px;box-shadow:0 2px 8px #2563eb22;' loading='lazy'>
                    <div>
                        <div class='font-semibold text-lg text-gray-800'>" . htmlspecialchars($row['name'], ENT_QUOTES) . "</div>
                        <div class='text-sm text-gray-500'>" . $aisle . "</div>
                        <div class='font-bold mt-1' style='font-size:1.08em;'>" . $priceHtml . "</div>
                    </div>
                </div>
                <div class='flex flex-col gap-2 min-w-[140px] w-full sm:w-auto'>
                    $viewLocationButton
                    <button
                      type=\"button\"
                      class=\"add-to-cart-btn group relative flex items-center justify-center sm:justify-start gap-2 font-bold font-sans text-base px-6 py-2 rounded-full bg-[#2563eb] text-white shadow-lg
                             transition-all duration-200 
                             hover:bg-[#397cf7] hover:scale-105 hover:shadow-xl
                             active:scale-95 active:shadow
                             focus:outline-none
                             w-full sm:w-auto\"
                      onclick=\"addToCart('" . htmlspecialchars($row['name'], ENT_QUOTES) . "', $displayPrice, this, '#prodimg-" . $row['id'] . "')\"
                    >
                      <span class=\"icon-wrap flex items-center justify-center\">
                        <i class=\"fas fa-shopping-cart text-lg transition-all duration-200\"></i>
                        <i class=\"fas fa-check text-lg transition-all duration-200 absolute left-0 opacity-0\"></i>
                      </span>
                      <span class=\"ml-2\">Add to Cart</span>
                    </button>
                </div>
            </div>";
        }
    } else {
        echo "<div class='error-message text-center text-red-500 font-semibold mt-4'>Item not found</div>";
    }
}
?>

<style>

    @keyframes cartBtnBounce {
  0%   { transform: scale(1);}
  20%  { transform: scale(1.14);}
  40%  { transform: scale(0.97);}
  60%  { transform: scale(1.08);}
  80%  { transform: scale(0.98);}
  100% { transform: scale(1);}
}
.add-to-cart-btn.animated {
  animation: cartBtnBounce 0.55s cubic-bezier(.28,.84,.42,1.1);
}
.add-to-cart-btn .fa-check {
  transition: opacity 0.28s, transform 0.28s;
  transform: scale(0.7);
}
.add-to-cart-btn .fa-check.opacity-100 {
  opacity: 1 !important;
  transform: scale(1);
}
.add-to-cart-btn .fa-check.opacity-0 {
  opacity: 0 !important;
  transform: scale(0.7);
}
.add-to-cart-btn .fa-shopping-cart.opacity-0 {
  opacity: 0 !important;
  transform: scale(1.25) rotate(-15deg);
  transition: opacity 0.18s, transform 0.28s;
}
.add-to-cart-btn .fa-shopping-cart {
  transition: opacity 0.18s, transform 0.28s;
}


.product-card {
    transition: box-shadow 0.2s, transform 0.2s;
}
.product-card:hover {
    box-shadow: 0 8px 28px #2563eb1f;
    transform: scale(1.012);
}
.aisle-info {
    background: #f1f5f9;
    color: #2563eb;
    padding: 2px 10px;
    font-size: 0.98em;
    border-radius: 6px;
    margin-left: 8px;
}
.add-to-cart-btn { font-family: 'Inter', sans-serif; }
/* Add to Cart button animation (for JS) */
.add-to-cart-btn .icon-wrap { position: relative; width: 1.35em; }
.add-to-cart-btn .fa-check { left: 0; }
.add-to-cart-btn.ring-pulse {
    box-shadow: 0 0 0 0.35rem #2563eb33;
    transition: box-shadow 0.3s;
}
@media (max-width: 640px) {
    .product-card { flex-direction: column; align-items: flex-start; gap: 10px; }
    .add-to-cart-btn, .view-location-btn { width: 100% !important; }
}
/* Add To Cart animated bounce + icon transitions */
@keyframes cartBtnBounce {
  0%   { transform: scale(1);}
  20%  { transform: scale(1.14);}
  40%  { transform: scale(0.97);}
  60%  { transform: scale(1.08);}
  80%  { transform: scale(0.98);}
  100% { transform: scale(1);}
}
.add-to-cart-btn.animated {
  animation: cartBtnBounce 0.55s cubic-bezier(.28,.84,.42,1.1);
}
.add-to-cart-btn .fa-check {
  transition: opacity 0.28s, transform 0.28s;
  transform: scale(0.7);
}
.add-to-cart-btn .fa-check.opacity-100 {
  opacity: 1 !important;
  transform: scale(1);
}
.add-to-cart-btn .fa-check.opacity-0 {
  opacity: 0 !important;
  transform: scale(0.7);
}
.add-to-cart-btn .fa-shopping-cart.opacity-0 {
  opacity: 0 !important;
  transform: scale(1.25) rotate(-15deg);
  transition: opacity 0.18s, transform 0.28s;
}
.add-to-cart-btn .fa-shopping-cart {
  transition: opacity 0.18s, transform 0.28s;
}
</style>
<script>
    function addToCart(name, price, btn, imgSelector) {
    // -- Your actual cart logic here (e.g. AJAX, local state update) --
    if (!btn) return;
    const iconWrap = btn.querySelector('.icon-wrap');
    const cartIcon = iconWrap.children[0];
    const checkIcon = iconWrap.children[1];

    // Animate cart icon to checkmark
    cartIcon.classList.add('opacity-0');
    checkIcon.classList.remove('opacity-0');
    checkIcon.classList.add('opacity-100');

    // Add ring pulse and bounce
    btn.classList.remove('animated'); // for rapid clicks, restart animation
    void btn.offsetWidth; // force reflow
    btn.classList.add('ring-4', 'ring-[#2563eb]/30', 'animated');

    // Restore after a short delay
    setTimeout(() => {
        cartIcon.classList.remove('opacity-0');
        checkIcon.classList.add('opacity-0');
        checkIcon.classList.remove('opacity-100');
        btn.classList.remove('ring-4', 'ring-[#2563eb]/30', 'animated');
    }, 850);

    // Optionally: trigger fly-to-cart animation here if desired
}

</script>