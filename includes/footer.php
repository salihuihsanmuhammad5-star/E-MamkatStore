<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="footer-col-1">
                <h3>Download Our App</h3>
                <p>Available on Android and iOS</p>
                <div class="app-logo">
                    <img src="<?= BASE_URL ?>/images/play-store.png" alt="Play Store">
                    <img src="<?= BASE_URL ?>/images/app-store.png" alt="App Store">
                </div>
            </div>
            <div class="footer-col-2">
                <img src="<?= BASE_URL ?>/images/logo1-white.png" alt="MamkatStore">
                <p>Bringing African fashion to the world.</p>
            </div>
            <div class="footer-col-3">
                <h3>Links</h3>
                <ul>
                    <li><a href="<?= BASE_URL ?>/products.php">All Products</a></li>
                    <li><a href="<?= BASE_URL ?>/account.php">My Account</a></li>
                </ul>
            </div>
            <div class="footer-col-3">
                <h3>Follow Us</h3>
                <ul>
                    <li><a href="https://facebook.com/YourUsername" target="_blank" rel="noopener noreferrer">Facebook</a></li>
                    <li><a href="https://instagram.com/YourUsername" target="_blank" rel="noopener noreferrer">Instagram</a></li>
                    <li><a href="https://wa.me/+2348115003393" target="_blank" rel="noopener noreferrer">Whatsapp</a></li>
                </ul>
            </div>
        </div>
        <hr>
        <p class="copyright">&copy; <?= date('Y') ?> MamkatStore. All rights reserved.</p>
    </div>
</footer>

<script>
// Mobile menu toggle (from old code)
var MenuItems = document.getElementById("MenuItems");
MenuItems.style.maxHeight = "0px";
function menutoggle() {
    if (MenuItems.style.maxHeight == "0px") {
        MenuItems.style.maxHeight = "200px";
    } else {
        MenuItems.style.maxHeight = "0px";
    }
}
</script>
