<?php
/**
 * includes/footer.php
 * Shared footer. Include at the bottom of every page before </body>.
 */
?>
<footer class="site-footer">
    <div class="container footer-inner">
        <div class="footer-brand">
            <img src="/thriftzone/assets/img/logo.png" alt="ThriftZone" class="footer-logo">
            <p class="footer-tagline">Belanja preloved, hemat & berkelanjutan.</p>
        </div>

        <div class="footer-links">
            <h4>Kategori</h4>
            <ul>
                <li><a href="/thriftzone/category.php?id=1">Fashion</a></li>
                <li><a href="/thriftzone/category.php?id=2">Entertainment</a></li>
                <li><a href="/thriftzone/category.php?id=3">Utilities</a></li>
                <li><a href="/thriftzone/category.php?id=4">Collectibles</a></li>
                <li><a href="/thriftzone/category.php?id=5">Other</a></li>
            </ul>
        </div>

        <div class="footer-links">
            <h4>Akun</h4>
            <ul>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="/thriftzone/orders.php">Pesanan Saya</a></li>
                    <li><a href="/thriftzone/wishlist.php">Wishlist</a></li>
                    <li><a href="/thriftzone/sell.php">Jual Barang</a></li>
                    <li><a href="/thriftzone/logout.php">Keluar</a></li>
                <?php else: ?>
                    <li><a href="/thriftzone/login.php">Masuk</a></li>
                    <li><a href="/thriftzone/register.php">Daftar</a></li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="footer-links">
            <h4>ThriftZone</h4>
            <ul>
                <li><a href="/thriftzone/">Beranda</a></li>
                <li><a href="/thriftzone/cart.php">Keranjang</a></li>
            </ul>
        </div>
    </div>

    <div class="footer-bottom">
        <p>&copy; <?= date('Y') ?> ThriftZone. Dibuat dengan ♻️ untuk bumi yang lebih baik.</p>
    </div>
</footer>

<!-- Mobile nav toggle script -->
<script>
(function() {
    const btn = document.getElementById('hamburgerBtn');
    const nav = document.getElementById('mobileNav');
    if (btn && nav) {
        btn.addEventListener('click', function() {
            const isOpen = !nav.hidden;
            nav.hidden = isOpen;
            btn.setAttribute('aria-expanded', String(!isOpen));
        });
    }
})();
</script>

<!-- Main JS -->
<script src="/thriftzone/assets/js/main.js"></script>
</body>
</html>

