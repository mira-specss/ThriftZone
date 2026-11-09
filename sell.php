<?php
/**
 * sell.php – Upload a new item for sale.
 * Requires login. Accepts JPG/PNG/WEBP, max 2 MB.
 */

session_start();
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$pageTitle = 'Jual Barang';
$userId    = (int)$_SESSION['user_id'];
$errors    = [];
$old       = [];
$success   = '';

// Sub-types per category (must match database.sql)
$subtypes = [
    1 => ['Clothes', 'Shoes', 'Bags', 'Accessories'],
    2 => ['Books', 'Vinyl & CDs', 'Games', 'Movies'],
    3 => ['Kitchenware', 'Home Decor', 'Electronics', 'Tools'],
    4 => ['Toys', 'Figurines', 'Trading Cards', 'Coins & Stamps'],
    5 => ['Anything else'],
];

// Load categories for select
try {
    $db   = getDB();
    $cats = $db->query('SELECT id, name FROM categories ORDER BY id')->fetchAll();
} catch (PDOException $e) {
    $cats = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ── Collect & sanitise fields ───────────────────────────
    $old['title']       = trim($_POST['title']       ?? '');
    $old['description'] = trim($_POST['description'] ?? '');
    $old['category_id'] = (int)($_POST['category_id'] ?? 0);
    $old['subtype']     = trim($_POST['subtype']     ?? '');
    $old['target']      = trim($_POST['target']      ?? '');
    $old['condition']   = trim($_POST['condition']   ?? '');
    $old['size']        = trim($_POST['size']        ?? '');
    $old['price']       = trim($_POST['price']       ?? '');

    // ── Validation ─────────────────────────────────────────
    if (mb_strlen($old['title']) < 3) {
        $errors['title'] = 'Judul minimal 3 karakter.';
    }
    if (!$old['category_id']) {
        $errors['category_id'] = 'Pilih kategori.';
    }
    if (!in_array($old['target'], ['Women','Men','Unisex','Toddler'])) {
        $errors['target'] = 'Pilih target pembeli.';
    }
    if (!in_array($old['condition'], ['Like new','Good','Fair'])) {
        $errors['condition'] = 'Pilih kondisi barang.';
    }
    $price = (int) preg_replace('/\D/', '', $old['price']);
    if ($price <= 0) {
        $errors['price'] = 'Harga harus lebih dari 0.';
    }

    // ── Photo upload ────────────────────────────────────────
    $photoPath = 'assets/img/sample/placeholder.svg'; // default
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $file     = $_FILES['photo'];
        $maxBytes = 2 * 1024 * 1024; // 2 MB

        if ($file['size'] > $maxBytes) {
            $errors['photo'] = 'Foto maksimal 2 MB.';
        } else {
            // Check MIME type (server-side)
            $finfo   = new finfo(FILEINFO_MIME_TYPE);
            $mime    = $finfo->file($file['tmp_name']);
            $allowed = ['image/jpeg', 'image/png', 'image/webp'];
            if (!in_array($mime, $allowed)) {
                $errors['photo'] = 'Format foto harus JPG, PNG, atau WEBP.';
            } else {
                $ext       = match($mime) {
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp',
                    default      => 'jpg',
                };
                $filename  = bin2hex(random_bytes(12)) . '.' . $ext;
                $destDir   = __DIR__ . '/uploads/';
                $destPath  = $destDir . $filename;

                if (!is_dir($destDir)) {
                    mkdir($destDir, 0755, true);
                }

                if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                    $errors['photo'] = 'Gagal mengunggah foto. Pastikan folder uploads/ dapat ditulis.';
                } else {
                    $photoPath = 'uploads/' . $filename;
                }
            }
        }
    } elseif (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $errors['photo'] = 'Terjadi kesalahan saat mengunggah foto.';
    }

    // ── Save to DB if no errors ─────────────────────────────
    if (empty($errors)) {
        try {
            $ins = $db->prepare(
                'INSERT INTO items
                   (user_id, category_id, title, description, subtype, target, `condition`, size, price, photo)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $ins->execute([
                $userId,
                $old['category_id'],
                $old['title'],
                $old['description'],
                $old['subtype'] ?: null,
                $old['target'],
                $old['condition'],
                $old['size'] ?: null,
                $price,
                $photoPath,
            ]);
            $newItemId = (int)$db->lastInsertId();
            header('Location: /item.php?id=' . $newItemId . '&sold=new');
            exit;
        } catch (PDOException $e) {
            $errors['general'] = 'Gagal menyimpan barang. Silakan coba lagi.';
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<main>
<div class="sell-wrap">
<div class="sell-card">
    <h2><i class="bi bi-plus-circle-fill" style="color:var(--color-teal)"></i> Jual Barangmu</h2>

    <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-error"><i class="bi bi-x-circle"></i> <?= htmlspecialchars($errors['general']) ?></div>
    <?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data" id="sellForm">

        <!-- Title -->
        <div class="form-group">
            <label class="form-label" for="title">Judul Barang <span style="color:var(--color-danger)">*</span></label>
            <input
                type="text" id="title" name="title"
                class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>"
                value="<?= htmlspecialchars($old['title'] ?? '') ?>"
                placeholder="Contoh: Kemeja Flannel H&M ukuran M"
                maxlength="200"
                required
            >
            <?php if (isset($errors['title'])): ?>
                <div class="invalid-feedback" style="display:block;"><?= htmlspecialchars($errors['title']) ?></div>
            <?php endif; ?>
        </div>

        <!-- Description -->
        <div class="form-group">
            <label class="form-label" for="description">Deskripsi</label>
            <textarea
                id="description" name="description"
                class="form-control"
                placeholder="Ceritakan kondisi barang, ukuran, merek, alasan dijual, dll."
                rows="4"
            ><?= htmlspecialchars($old['description'] ?? '') ?></textarea>
        </div>

        <!-- Category -->
        <div class="form-group">
            <label class="form-label" for="category_id">Kategori <span style="color:var(--color-danger)">*</span></label>
            <select
                id="category_id" name="category_id"
                class="form-control <?= isset($errors['category_id']) ? 'is-invalid' : '' ?>"
                required onchange="updateSubtypes(this.value)"
            >
                <option value="">-- Pilih Kategori --</option>
                <?php foreach ($cats as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= (isset($old['category_id']) && $old['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['category_id'])): ?>
                <div class="invalid-feedback" style="display:block;"><?= htmlspecialchars($errors['category_id']) ?></div>
            <?php endif; ?>
        </div>

        <!-- Sub-type -->
        <div class="form-group">
            <label class="form-label" for="subtype">Tipe</label>
            <select id="subtype" name="subtype" class="form-control">
                <option value="">-- Pilih tipe (opsional) --</option>
            </select>
        </div>

        <!-- Target & Condition in a row -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="form-group">
                <label class="form-label" for="target">Target <span style="color:var(--color-danger)">*</span></label>
                <select id="target" name="target" class="form-control <?= isset($errors['target']) ? 'is-invalid' : '' ?>" required>
                    <option value="">-- Pilih --</option>
                    <?php foreach (['Women','Men','Unisex','Toddler'] as $t): ?>
                    <option value="<?= $t ?>" <?= (isset($old['target']) && $old['target'] === $t) ? 'selected' : '' ?>><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['target'])): ?>
                    <div class="invalid-feedback" style="display:block;"><?= htmlspecialchars($errors['target']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="condition">Kondisi <span style="color:var(--color-danger)">*</span></label>
                <select id="condition" name="condition" class="form-control <?= isset($errors['condition']) ? 'is-invalid' : '' ?>" required>
                    <option value="">-- Pilih --</option>
                    <?php foreach (['Like new','Good','Fair'] as $c): ?>
                    <option value="<?= $c ?>" <?= (isset($old['condition']) && $old['condition'] === $c) ? 'selected' : '' ?>><?= $c ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['condition'])): ?>
                    <div class="invalid-feedback" style="display:block;"><?= htmlspecialchars($errors['condition']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Size & Price in a row -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="form-group">
                <label class="form-label" for="size">Ukuran</label>
                <input
                    type="text" id="size" name="size"
                    class="form-control"
                    value="<?= htmlspecialchars($old['size'] ?? '') ?>"
                    placeholder="S / M / L / 38 / dll."
                    maxlength="30"
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="price">Harga (Rp) <span style="color:var(--color-danger)">*</span></label>
                <input
                    type="number" id="price" name="price"
                    class="form-control <?= isset($errors['price']) ? 'is-invalid' : '' ?>"
                    value="<?= htmlspecialchars($old['price'] ?? '') ?>"
                    placeholder="50000"
                    min="1"
                    required
                >
                <?php if (isset($errors['price'])): ?>
                    <div class="invalid-feedback" style="display:block;"><?= htmlspecialchars($errors['price']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Photo -->
        <div class="form-group">
            <label class="form-label" for="photoInput">Foto Barang <span style="font-size:12px;color:var(--color-gray);">(JPG/PNG/WEBP, maks 2 MB)</span></label>
            <input
                type="file" id="photoInput" name="photo"
                class="form-control <?= isset($errors['photo']) ? 'is-invalid' : '' ?>"
                accept="image/jpeg,image/png,image/webp"
            >
            <?php if (isset($errors['photo'])): ?>
                <div class="invalid-feedback" style="display:block;"><?= htmlspecialchars($errors['photo']) ?></div>
            <?php endif; ?>
            <img id="photoPreview" src="" alt="Preview foto" class="photo-preview">
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%;margin-top:8px;">
            <i class="bi bi-cloud-upload-fill"></i> Upload &amp; Jual Sekarang
        </button>

    </form>
</div>
</div>
</main>

<script>
// Sub-type data (must match PHP $subtypes array)
const subtypeData = <?= json_encode($subtypes) ?>;

function updateSubtypes(catId) {
    const sel    = document.getElementById('subtype');
    const list   = subtypeData[catId] || [];
    sel.innerHTML = '<option value="">-- Pilih tipe (opsional) --</option>';
    list.forEach(st => {
        const opt = document.createElement('option');
        opt.value       = st;
        opt.textContent = st;
        sel.appendChild(opt);
    });
}

// Restore subtype on validation error (page reload)
document.addEventListener('DOMContentLoaded', function () {
    const catSel = document.getElementById('category_id');
    const oldSub = <?= json_encode($old['subtype'] ?? '') ?>;
    if (catSel && catSel.value) {
        updateSubtypes(catSel.value);
        const subSel = document.getElementById('subtype');
        if (subSel && oldSub) subSel.value = oldSub;
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
