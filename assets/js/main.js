/**
 * assets/js/main.js
 * ThriftZone – global JavaScript helpers.
 * No frameworks, plain ES6+.
 */

/* ── Utility: format price as Indonesian Rupiah ── */
function formatRupiah(num) {
    return 'Rp' + parseInt(num, 10)
        .toLocaleString('id-ID')       // e.g. 50.000
        .replace(/,/g, '.');           // ensure dots (id-ID already uses dots)
}

/* ── Wishlist / Cart add buttons: show toast feedback ── */
function showToast(message, type = 'success') {
    // Remove existing toasts
    document.querySelectorAll('.tz-toast').forEach(t => t.remove());

    const toast = document.createElement('div');
    toast.className = 'tz-toast tz-toast-' + type;
    toast.textContent = message;
    toast.style.cssText = `
        position: fixed;
        bottom: 24px;
        left: 50%;
        transform: translateX(-50%) translateY(0);
        background: ${type === 'success' ? '#0D9488' : '#EF4444'};
        color: #fff;
        padding: 12px 24px;
        border-radius: 50px;
        font-family: 'Poppins', sans-serif;
        font-size: 14px;
        font-weight: 600;
        box-shadow: 0 4px 16px rgba(0,0,0,.2);
        z-index: 9999;
        opacity: 0;
        transition: opacity .25s ease;
        pointer-events: none;
    `;
    document.body.appendChild(toast);

    // Fade in
    requestAnimationFrame(() => {
        toast.style.opacity = '1';
    });

    // Fade out and remove after 2.5 s
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 2500);
}

/* ── Add to Cart (item detail page) ── */
document.addEventListener('DOMContentLoaded', function () {
    /* Mobile hamburger menu */
    const hamburgerBtn = document.getElementById('hamburgerBtn');
    const mobileNav = document.getElementById('mobileNav');
    if (hamburgerBtn && mobileNav) {
        hamburgerBtn.addEventListener('click', function () {
            const opening = mobileNav.hasAttribute('hidden');
            mobileNav.toggleAttribute('hidden', !opening);
            hamburgerBtn.setAttribute('aria-expanded', String(opening));
        });
        // Close the menu if the window grows to desktop size
        window.addEventListener('resize', function () {
            if (window.innerWidth > 768) {
                mobileNav.setAttribute('hidden', '');
                hamburgerBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }
    /* Add to Cart button */
    const addCartBtn = document.getElementById('addCartBtn');
    if (addCartBtn) {
        addCartBtn.addEventListener('click', async function () {
            const itemId = this.dataset.itemId;
            addCartBtn.disabled = true;
            try {
                const res = await fetch('/thriftzone/api/cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'add', item_id: itemId })
                });
                const data = await res.json();
                if (data.success) {
                    showToast('✓ Ditambahkan ke keranjang!');
                    // Update badge in header
                    const badge = document.querySelector('.header-nav .nav-icon-link[href*="cart"] .badge');
                    if (badge) badge.textContent = data.cart_count;
                } else {
                    showToast(data.message || 'Gagal menambahkan.', 'error');
                    addCartBtn.disabled = false;
                }
            } catch {
                showToast('Koneksi error.', 'error');
                addCartBtn.disabled = false;
            }
        });
    }

    /* Add to Wishlist button */
    const addWishBtn = document.getElementById('addWishBtn');
    if (addWishBtn) {
        addWishBtn.addEventListener('click', async function () {
            const itemId = this.dataset.itemId;
            const isWished = this.dataset.wished === '1';
            try {
                const res = await fetch('/thriftzone/api/wishlist.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: isWished ? 'remove' : 'add',
                        item_id: itemId
                    })
                });
                const data = await res.json();
                if (data.success) {
                    if (data.wished) {
                        this.dataset.wished = '1';
                        this.innerHTML = '<i class="bi bi-heart-fill"></i> Di Wishlist';
                        this.classList.add('btn-primary');
                        this.classList.remove('btn-outline');
                    } else {
                        this.dataset.wished = '0';
                        this.innerHTML = '<i class="bi bi-heart"></i> Tambah Wishlist';
                        this.classList.remove('btn-primary');
                        this.classList.add('btn-outline');
                    }
                    showToast(data.message);
                } else {
                    showToast(data.message || 'Gagal.', 'error');
                }
            } catch {
                showToast('Koneksi error.', 'error');
            }
        });
    }

    /* Remove from cart (cart.php inline buttons) */
    document.querySelectorAll('.remove-cart-btn').forEach(btn => {
        btn.addEventListener('click', async function () {
            const itemId = this.dataset.itemId;
            const row = this.closest('tr');
            try {
                const res = await fetch('/thriftzone/api/cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'remove', item_id: itemId })
                });
                const data = await res.json();
                if (data.success) {
                    row?.remove();
                    // Update subtotal
                    const subEl = document.getElementById('cartSubtotal');
                    if (subEl && data.subtotal !== undefined) {
                        subEl.textContent = formatRupiah(data.subtotal);
                    }
                    if (data.cart_count === 0) {
                        location.reload();
                    }
                    showToast('Item dihapus dari keranjang.');
                } else {
                    showToast(data.message || 'Gagal menghapus.', 'error');
                }
            } catch {
                showToast('Koneksi error.', 'error');
            }
        });
    });

    /* Remove from wishlist (wishlist.php inline buttons) */
    document.querySelectorAll('.remove-wish-btn').forEach(btn => {
        btn.addEventListener('click', async function () {
            const itemId = this.dataset.itemId;
            const card = this.closest('.item-card-wrap');
            try {
                const res = await fetch('/thriftzone/api/wishlist.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'remove', item_id: itemId })
                });
                const data = await res.json();
                if (data.success) {
                    card?.remove();
                    showToast('Dihapus dari wishlist.');
                    const grid = document.getElementById('wishlistGrid');
                    if (grid && grid.children.length === 0) location.reload();
                } else {
                    showToast(data.message || 'Gagal.', 'error');
                }
            } catch {
                showToast('Koneksi error.', 'error');
            }
        });
    });

    /* Photo preview on sell page */
    const photoInput = document.getElementById('photoInput');
    const photoPreview = document.getElementById('photoPreview');
    if (photoInput && photoPreview) {
        photoInput.addEventListener('change', function () {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = e => {
                    photoPreview.src = e.target.result;
                    photoPreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    }

    /* Checkout form client-side validation */
    const checkoutForm = document.getElementById('checkoutForm');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function (e) {
            let valid = true;
            const fields = ['fullname', 'address', 'phone', 'postal'];
            fields.forEach(id => {
                const el = document.getElementById(id);
                if (!el) return;
                if (!el.value.trim()) {
                    el.classList.add('is-invalid');
                    valid = false;
                } else {
                    el.classList.remove('is-invalid');
                }
            });
            // Phone: digits only, 8-15 chars
            const phone = document.getElementById('phone');
            if (phone && phone.value && !/^\d{8,15}$/.test(phone.value.replace(/\s/g, ''))) {
                phone.classList.add('is-invalid');
                valid = false;
            }
            // Postal: 5 digits
            const postal = document.getElementById('postal');
            if (postal && postal.value && !/^\d{5}$/.test(postal.value.trim())) {
                postal.classList.add('is-invalid');
                valid = false;
            }
            if (!valid) {
                e.preventDefault();
                showToast('Mohon lengkapi semua kolom dengan benar.', 'error');
            }
        });
    }

    /* Sell form client-side validation */
    const sellForm = document.getElementById('sellForm');
    if (sellForm) {
        sellForm.addEventListener('submit', function (e) {
            let valid = true;
            ['title', 'price', 'category_id'].forEach(id => {
                const el = document.getElementById(id);
                if (el && !el.value.trim()) {
                    el.classList.add('is-invalid');
                    valid = false;
                } else if (el) {
                    el.classList.remove('is-invalid');
                }
            });
            if (!valid) {
                e.preventDefault();
                showToast('Mohon isi semua kolom wajib.', 'error');
            }
        });
    }

    /* Payment: "I have paid" button */
    const paidBtn = document.getElementById('paidBtn');
    if (paidBtn) {
        paidBtn.addEventListener('click', async function () {
            const orderId = this.dataset.orderId;
            paidBtn.disabled = true;
            paidBtn.textContent = 'Memproses…';
            try {
                const res = await fetch('/thriftzone/api/payment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order_id: orderId })
                });
                const data = await res.json();
                if (data.success) {
                    window.location.href = '/thriftzone/thankyou.php?order_id=' + orderId;
                } else {
                    showToast(data.message || 'Gagal.', 'error');
                    paidBtn.disabled = false;
                    paidBtn.textContent = 'Saya Sudah Bayar';
                }
            } catch {
                showToast('Koneksi error.', 'error');
                paidBtn.disabled = false;
                paidBtn.textContent = 'Saya Sudah Bayar';
            }
        });
    }

});

/* Cancel order (payment page + My Orders) */
document.addEventListener('click', async function (e) {
    const btn = e.target.closest('.cancel-order-btn');
    if (!btn) return;
    if (!confirm('Yakin ingin membatalkan pesanan ini? Barang akan tersedia lagi untuk pembeli lain.')) return;

    btn.disabled = true;
    try {
        const res = await fetch('/thriftzone/api/cancel_order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ order_id: btn.dataset.orderId })
        });
        const data = await res.json();
        if (data.success) {
            showToast('Pesanan dibatalkan.');
            setTimeout(() => { window.location.href = '/thriftzone/orders.php'; }, 700);
        } else {
            showToast(data.message || 'Gagal membatalkan.', 'error');
            btn.disabled = false;
        }
    } catch {
        showToast('Koneksi error.', 'error');
        btn.disabled = false;
    }
});