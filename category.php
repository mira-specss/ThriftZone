<?php
/**
 * category.php – Browse items by category, with a pop-out filter drawer.
 * Filtering is handled by JavaScript fetching api/items.php (returns JSON).
 */

session_start();
require_once __DIR__ . '/config/db.php';

// Validate category ID
$catId = isset($_GET['id']) ? (int) $_GET['id'] : 1;

try {
    $db = getDB();

    // Load this category
    $stmt    = $db->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([$catId]);
    $category = $stmt->fetch();
    if (!$category) {
        // Fallback to first category
        $category = $db->query('SELECT * FROM categories ORDER BY id LIMIT 1')->fetch();
        $catId    = $category ? (int)$category['id'] : 1;
    }
} catch (PDOException $e) {
    $category = ['id' => $catId, 'name' => 'Kategori'];
}

$pageTitle = $category['name'] ?? 'Kategori';

// Sub-types per category
$subtypes = [
    1 => ['Clothes', 'Shoes', 'Bags', 'Accessories'],
    2 => ['Books', 'Vinyl & CDs', 'Games', 'Movies'],
    3 => ['Kitchenware', 'Home Decor', 'Electronics', 'Tools'],
    4 => ['Toys', 'Figurines', 'Trading Cards', 'Coins & Stamps'],
    5 => ['Anything else'],
];

$catSubtypes = $subtypes[$catId] ?? ['Anything else'];

include __DIR__ . '/includes/header.php';
?>

<main>
<div class="container">

    <!-- ── Items Column (full width) ── -->
    <div class="category-layout">
    <div class="items-col">
        <div class="items-col-header">
            <div style="display:flex;gap:12px;align-items:baseline;">
                <h2><?= htmlspecialchars($category['name'] ?? 'Semua Barang') ?></h2>
                <span class="items-count" id="itemsCount"></span>
            </div>

            <!-- Opens the filter drawer -->
            <button type="button" class="btn btn-secondary btn-sm filter-open-btn" id="filterToggleBtn"
                    aria-controls="filterPanel" aria-expanded="false">
                <i class="bi bi-funnel"></i> Filter
                <span class="filter-count" id="filterCount" hidden>0</span>
            </button>
        </div>

        <!-- Loading state -->
        <div id="loadingSpinner" style="text-align:center;padding:40px;display:none;">
            <span class="spinner"></span>
            <p class="text-muted mt-1">Memuat barang…</p>
        </div>

        <!-- Items grid (populated by JS) -->
        <div class="items-grid" id="itemsGrid"></div>

        <!-- Empty state -->
        <div class="empty-state" id="emptyState" style="display:none;">
            <i class="bi bi-search"></i>
            <h3>Barang tidak ditemukan</h3>
            <p>Coba ubah filter atau cari kategori lain.</p>
        </div>
    </div>
    </div>

</div>
</main>

<!-- ── Filter drawer (slides in from the right) ── -->
<div class="filter-overlay" id="filterOverlay"></div>

<aside class="filter-drawer" id="filterPanel" role="dialog" aria-modal="true"
       aria-label="Filter barang" aria-hidden="true">

    <div class="filter-drawer-header">
        <h3><i class="bi bi-funnel-fill" style="color:var(--color-teal)"></i> Filter</h3>
        <button type="button" class="filter-close" id="filterClose" aria-label="Tutup filter">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <div class="filter-drawer-body">

        <!-- Target -->
        <div class="filter-section">
            <h4>Target</h4>
            <?php foreach (['Women','Men','Unisex','Toddler'] as $t): ?>
            <label>
                <input type="checkbox" name="target" value="<?= $t ?>" class="filter-input"> <?= $t ?>
            </label>
            <?php endforeach; ?>
        </div>

        <!-- Sub-type -->
        <div class="filter-section">
            <h4>Tipe</h4>
            <?php foreach ($catSubtypes as $st): ?>
            <label>
                <input type="checkbox" name="subtype" value="<?= htmlspecialchars($st) ?>" class="filter-input"> <?= htmlspecialchars($st) ?>
            </label>
            <?php endforeach; ?>
        </div>

        <!-- Condition -->
        <div class="filter-section">
            <h4>Kondisi</h4>
            <?php foreach (['Like new','Good','Fair'] as $c): ?>
            <label>
                <input type="checkbox" name="condition" value="<?= $c ?>" class="filter-input"> <?= $c ?>
            </label>
            <?php endforeach; ?>
        </div>

        <!-- Sort -->
        <div class="filter-section">
            <h4>Urutan</h4>
            <label>
                <input type="radio" name="sort" value="newest" class="filter-input" checked> Terbaru
            </label>
            <label>
                <input type="radio" name="sort" value="price_asc" class="filter-input"> Harga: Terendah
            </label>
            <label>
                <input type="radio" name="sort" value="price_desc" class="filter-input"> Harga: Tertinggi
            </label>
        </div>
    </div>

    <div class="filter-drawer-footer">
        <button type="button" class="btn btn-secondary" id="resetFilter">Reset</button>
        <button type="button" class="btn btn-primary" id="applyFilter" style="flex:1;">
            <i class="bi bi-check-lg"></i> Terapkan
        </button>
    </div>
</aside>

<script>
(function () {
    const catId      = <?= (int)$catId ?>;
    const grid       = document.getElementById('itemsGrid');
    const spinner    = document.getElementById('loadingSpinner');
    const emptyEl    = document.getElementById('emptyState');
    const countEl    = document.getElementById('itemsCount');

    // Drawer elements
    const drawer     = document.getElementById('filterPanel');
    const overlay    = document.getElementById('filterOverlay');
    const openBtn    = document.getElementById('filterToggleBtn');
    const closeBtn   = document.getElementById('filterClose');
    const countBadge = document.getElementById('filterCount');

    /* ── Drawer open / close ── */
    function openDrawer() {
        drawer.classList.add('open');
        overlay.classList.add('open');
        drawer.setAttribute('aria-hidden', 'false');
        openBtn.setAttribute('aria-expanded', 'true');
        document.body.classList.add('no-scroll');
        closeBtn.focus();
    }

    function closeDrawer() {
        drawer.classList.remove('open');
        overlay.classList.remove('open');
        drawer.setAttribute('aria-hidden', 'true');
        openBtn.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('no-scroll');
        openBtn.focus();
    }

    openBtn.addEventListener('click', openDrawer);
    closeBtn.addEventListener('click', closeDrawer);          // X button
    overlay.addEventListener('click', closeDrawer);           // click outside
    document.addEventListener('keydown', function (e) {       // Escape key
        if (e.key === 'Escape' && drawer.classList.contains('open')) closeDrawer();
    });

    /* ── Badge: number of active filters ── */
    function updateFilterCount() {
        let n = document.querySelectorAll('.filter-input[type="checkbox"]:checked').length;
        const sortEl = document.querySelector('input[name="sort"]:checked');
        if (sortEl && sortEl.value !== 'newest') n++;
        countBadge.textContent = n;
        countBadge.hidden = (n === 0);
    }

    /* ── Render a single item card as HTML ── */
    function cardHTML(item) {
        const sold   = item.is_sold == 1;
        const price  = 'Rp' + parseInt(item.price).toLocaleString('id-ID');
        const img    = '/' + item.photo;
        const badge  = sold ? '<span class="badge-sold">Terjual</span>' : '<span class="badge-condition">' + item.condition + '</span>';
        return `
        <a href="/item.php?id=${item.id}" class="item-card${sold ? ' opacity-70' : ''}">
            <img src="${img}" alt="${item.title}" class="item-card-img"
                 onerror="this.src='/assets/img/sample/placeholder.svg'">
            <div class="item-card-body">
                <div class="item-card-title">${item.title}</div>
                <div class="item-card-price">${price}</div>
                <div class="item-card-meta">
                    ${badge}
                    <span>${item.target || ''}</span>
                </div>
            </div>
        </a>`;
    }

    /* ── Collect filter values and fetch from API ── */
    function fetchItems() {
        updateFilterCount();
        spinner.style.display = 'block';
        grid.innerHTML        = '';
        emptyEl.style.display = 'none';

        const targets    = [...document.querySelectorAll('input[name="target"]:checked')].map(i => i.value);
        const subtypes   = [...document.querySelectorAll('input[name="subtype"]:checked')].map(i => i.value);
        const conditions = [...document.querySelectorAll('input[name="condition"]:checked')].map(i => i.value);
        const sortEl     = document.querySelector('input[name="sort"]:checked');
        const sort       = sortEl ? sortEl.value : 'newest';

        const params = new URLSearchParams();
        params.set('category_id', catId);
        if (targets.length)    params.set('target',    targets.join(','));
        if (subtypes.length)   params.set('subtype',   subtypes.join(','));
        if (conditions.length) params.set('condition', conditions.join(','));
        params.set('sort', sort);

        fetch('/api/items.php?' + params.toString())
            .then(r => r.json())
            .then(data => {
                spinner.style.display = 'none';
                if (data.items && data.items.length > 0) {
                    grid.innerHTML = data.items.map(cardHTML).join('');
                    countEl.textContent = data.items.length + ' barang';
                    emptyEl.style.display = 'none';
                } else {
                    emptyEl.style.display = 'flex';
                    countEl.textContent = '0 barang';
                }
            })
            .catch(() => {
                spinner.style.display = 'none';
                emptyEl.style.display = 'flex';
            });
    }

    // Initial load
    fetchItems();

    // Terapkan: apply filters, then close the drawer
    document.getElementById('applyFilter').addEventListener('click', function () {
        fetchItems();
        closeDrawer();
    });

    // Reset: clear everything (drawer stays open so you can pick again)
    document.getElementById('resetFilter').addEventListener('click', function () {
        document.querySelectorAll('.filter-input[type="checkbox"]').forEach(el => el.checked = false);
        const newestRadio = document.querySelector('input[name="sort"][value="newest"]');
        if (newestRadio) newestRadio.checked = true;
        fetchItems();
    });
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>