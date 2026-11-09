<?php
/**
 * login.php – User login.
 * Uses password_verify() against the bcrypt hash in the database.
 */

session_start();
require_once __DIR__ . '/config/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: /');
    exit;
}

$pageTitle = 'Masuk';
$error     = '';
$oldEmail  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';
    $oldEmail = $email;

    if (!$email || !$password) {
        $error = 'Email dan password wajib diisi.';
    } else {
        try {
            $db   = getDB();
            $stmt = $db->prepare('SELECT id, name, password FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Login success
                session_regenerate_id(true);
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['flash']     = ['type' => 'success', 'message' => 'Selamat datang kembali, ' . $user['name'] . '!'];
                header('Location: /');
                exit;
            } else {
                $error = 'Email atau password salah.';
            }
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan. Silakan coba lagi.';
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<main>
<div class="auth-wrap">
    <div class="auth-card">
        <h2><i class="bi bi-person-check" style="color:var(--color-teal)"></i> Masuk ke ThriftZone</h2>

        <?php if ($error): ?>
            <div class="alert alert-error"><i class="bi bi-x-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Demo credentials hint -->
        <div class="alert alert-info" style="font-size:12px;">
            <i class="bi bi-info-circle"></i>
            Demo: <strong>budi@example.com</strong> / <strong>password123</strong>
        </div>

        <form method="POST" action="">

            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input
                    type="email"
                    id="email" name="email"
                    class="form-control"
                    value="<?= htmlspecialchars($oldEmail) ?>"
                    placeholder="email@example.com"
                    required autofocus
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input
                    type="password"
                    id="password" name="password"
                    class="form-control"
                    placeholder="Password kamu"
                    required
                >
            </div>

            <button type="submit" class="btn btn-primary">Masuk</button>
        </form>

        <p class="auth-switch">
            Belum punya akun? <a href="/register.php">Daftar gratis</a>
        </p>
    </div>
</div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

