<?php
$conn = new mysqli('localhost', 'root', '', 'ecommerce_db');
if ($conn->connect_error) die("Connection failed");

$category = isset($_GET['category']) ? $conn->real_escape_string($_GET['category']) : '';
if (!$category) exit;

$res = $conn->query("SELECT p.*, a.aisle FROM products p LEFT JOIN aisles a ON p.category = a.category WHERE p.category='$category' ORDER BY p.name ASC");
if ($res && $res->num_rows > 0) {
    while ($prod = $res->fetch_assoc()) {
        $outOfStock = (isset($prod['stock']) && $prod['stock'] <= 0);
        $hasDiscount = (isset($prod['discount_price']) && $prod['discount_price'] > 0 && $prod['discount_price'] < $prod['price']);
        $aisle = !empty($prod['aisle']) ? htmlspecialchars($prod['aisle']) : '';
        ?>
<div class="shop-card bg-white rounded-[22px] shadow-md border border-[#f2f4f8] flex flex-col items-stretch min-h-[390px] max-w-xs mx-auto p-0 relative transition hover:shadow-lg animate-fadeIn" style="margin-bottom: 0.75rem;">
    <!-- Top Badge -->
    <?php if ($outOfStock): ?>
        <span class="absolute top-5 left-5 bg-red-500 text-white text-xs font-semibold rounded-full px-4 py-1 shadow z-20">Out of Stock</span>
    <?php elseif ($hasDiscount): ?>
        <span class="absolute top-5 left-5 bg-red-500 text-white text-xs font-semibold rounded-full px-4 py-1 shadow z-20">Sale!</span>
    <?php endif; ?>

    <!-- Product Image -->
    <div class="w-full flex items-center justify-center pt-5 px-5">
        <?php if (!empty($prod['image'])): ?>
            <img src="<?= htmlspecialchars($prod['image']) ?>" alt="<?= htmlspecialchars($prod['name']) ?>"
                 class="w-full aspect-[1.1/1] object-cover rounded-[18px] border border-[#f3f4f7] bg-white max-h-40" />
        <?php else: ?>
            <div class="w-full aspect-[1.1/1] flex items-center justify-center rounded-[18px] border border-[#f3f4f7] bg-[#f8fafc] text-gray-400 font-semibold text-base max-h-40">
                No Image
            </div>
        <?php endif; ?>
    </div>

    <!-- Details -->
    <div class="flex-1 flex flex-col px-6 pb-5 pt-3">
        <div class="font-bold text-base text-gray-900 mb-1"><?= htmlspecialchars($prod['name']) ?></div>
        <div class="flex items-center justify-between mb-2">
            <?php if ($hasDiscount): ?>
                <span class="text-green-600 font-bold text-lg mr-1">$<?= number_format($prod['discount_price'],2) ?></span>
                <span class="line-through text-gray-400 text-base font-semibold">$<?= number_format($prod['price'],2) ?></span>
            <?php else: ?>
                <span class="text-green-600 font-bold text-lg">$<?= number_format($prod['price'],2) ?></span>
            <?php endif; ?>
            <span class="flex items-center gap-1 text-[#6b7280] text-sm font-medium ml-2"><i class="fas fa-map-marker-alt text-base text-accent"></i> Aisle <?= $aisle ?></span>
        </div>

        <!-- Actions: Quantity + Add to Cart -->
        <div class="flex items-center gap-2 mb-2 w-full">
            <button class="qty-btn w-9 h-9 bg-[#f3f4f7] text-gray-700 rounded-full font-bold text-lg focus:outline-none border border-[#e5e7eb] hover:bg-[#e8ebf3] transition" onclick="changeQty(this, -1)" tabindex="-1">–</button>
            <span class="qty-num font-semibold text-lg select-none w-8 text-center inline-block">1</span>
            <button class="qty-btn w-9 h-9 bg-[#f3f4f7] text-gray-700 rounded-full font-bold text-lg focus:outline-none border border-[#e5e7eb] hover:bg-[#e8ebf3] transition" onclick="changeQty(this, +1)" tabindex="-1">+</button>
            <button class="addtocart-btn ml-auto px-6 py-2 bg-green-600 text-white rounded-[14px] font-semibold shadow hover:bg-green-700 active:scale-95 transition-all"
                onclick="addProductToCart(this, '<?= htmlspecialchars(addslashes($prod['name'])) ?>', <?= $hasDiscount ? $prod['discount_price'] : $prod['price'] ?>, <?= $outOfStock ? 0 : 1 ?>)"
                <?= $outOfStock ? 'disabled' : '' ?>
            >
                <span class="inline-flex items-center gap-2"><i class="fas fa-cart-plus"></i> Add to Cart</span>
            </button>
        </div>
        <!-- Locate Button -->
        <button class="locate-btn w-full px-0 py-2 border border-[#e5e7eb] text-[#374151] rounded-[13px] font-semibold flex items-center justify-center gap-2 bg-white hover:bg-[#f8fafc] transition"
            onclick="showProductLocation('Aisle <?= $aisle ?>')">
            <i class="fas fa-map-marker-alt"></i> Locate Product
        </button>
    </div>
</div>
<?php
    }
} else {
    echo '<div class="text-center text-gray-400 py-10">No products found in this category.</div>';
}
?>