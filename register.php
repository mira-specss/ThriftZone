<?php
/**
 * register.php – User registration.
 * Uses password_hash() for secure password storage.
 */

session_start();
require_once __DIR__ . '/config/db.php';

// Already logged in? Go home.
if (isset($_SESSION['user_id'])) {
    header('Location: /');
    exit;
}

$pageTitle = 'Daftar';
$errors    = [];
$old       = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['name']  = trim($_POST['name']  ?? '');
    $old['email'] = trim($_POST['email'] ?? '');
    $password     = $_POST['password']   ?? '';
    $confirm      = $_POST['confirm']    ?? '';

    // ── Server-side validation ──────────────────────────────
    if (mb_strlen($old['name']) < 2) {
        $errors['name'] = 'Nama minimal 2 karakter.';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Email tidak valid.';
    }
    if (mb_strlen($password) < 6) {
        $errors['password'] = 'Password minimal 6 karakter.';
    }
    if ($password !== $confirm) {
        $errors['confirm'] = 'Konfirmasi password tidak cocok.';
    }

    if (empty($errors)) {
        try {
            $db = getDB();

            // Check duplicate email
            $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$old['email']]);
            if ($stmt->fetch()) {
                $errors['email'] = 'Email sudah digunakan.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $ins  = $db->prepare(
                    'INSERT INTO users (name, email, password) VALUES (?, ?, ?)'
                );
                $ins->execute([$old['name'], $old['email'], $hash]);
                $userId = (int)$db->lastInsertId();

                // Log them in immediately
                $_SESSION['user_id']   = $userId;
                $_SESSION['user_name'] = $old['name'];
                $_SESSION['flash']     = ['type' => 'success', 'message' => 'Selamat datang di ThriftZone, ' . $old['name'] . '!'];
                header('Location: /');
                exit;
            }
        } catch (PDOException $e) {
            $errors['general'] = 'Terjadi kesalahan. Silakan coba lagi.';
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<main>
<div class="auth-wrap">
    <div class="auth-card">
        <h2><i class="bi bi-person-plus" style="color:var(--color-teal)"></i> Daftar Akun</h2>

        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-error"><i class="bi bi-x-circle"></i> <?= htmlspecialchars($errors['general']) ?></div>
        <?php endif; ?>

        <form method="POST" action="">

            <!-- Name -->
            <div class="form-group">
                <label class="form-label" for="name">Nama Lengkap</label>
                <input
                    type="text"
                    id="name" name="name"
                    class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                    value="<?= htmlspecialchars($old['name'] ?? '') ?>"
                    placeholder="Nama kamu"
                    required
                >
                <?php if (isset($errors['name'])): ?>
                    <div class="invalid-feedback" style="display:block;"><?= htmlspecialchars($errors['name']) ?></div>
                <?php endif; ?>
            </div>

            <!-- Email -->
            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input
                    type="email"
                    id="email" name="email"
                    class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                    value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                    placeholder="email@example.com"
                    required
                >
                <?php if (isset($errors['email'])): ?>
                    <div class="invalid-feedback" style="display:block;"><?= htmlspecialchars($errors['email']) ?></div>
                <?php endif; ?>
            </div>

            <!-- Password -->
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input
                    type="password"
                    id="password" name="password"
                    class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                    placeholder="Minimal 6 karakter"
                    required
                >
                <?php if (isset($errors['password'])): ?>
                    <div class="invalid-feedback" style="display:block;"><?= htmlspecialchars($errors['password']) ?></div>
                <?php endif; ?>
            </div>

            <!-- Confirm Password -->
            <div class="form-group">
                <label class="form-label" for="confirm">Konfirmasi Password</label>
                <input
                    type="password"
                    id="confirm" name="confirm"
                    class="form-control <?= isset($errors['confirm']) ? 'is-invalid' : '' ?>"
                    placeholder="Ulangi password"
                    required
                >
                <?php if (isset($errors['confirm'])): ?>
                    <div class="invalid-feedback" style="display:block;"><?= htmlspecialchars($errors['confirm']) ?></div>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary">Buat Akun</button>
        </form>

        <p class="auth-switch">
            Sudah punya akun? <a href="/login.php">Masuk di sini</a>
        </p>
    </div>
</div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

