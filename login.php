<?php
require_once __DIR__ . '/includes/auth.php';

$error = '';
$login = '';
$next = safe_next($_GET['next'] ?? $_POST['next'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    // Simple brute force protection: 5 tries, then wait 5 minutes
    $_SESSION['login_fails'] = $_SESSION['login_fails'] ?? 0;
    $_SESSION['login_lock'] = $_SESSION['login_lock'] ?? 0;
    if ($_SESSION['login_lock'] > time()) {
        $error = 'Too many tries. Wait a few minutes and try again.';
    } else {
        $stmt = db()->prepare('SELECT id, password_hash FROM users WHERE username = ? OR email = ?');
        $stmt->execute([$login, $login]);
        $row = $stmt->fetch();
        if ($row && password_verify($password, $row['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$row['id'];
            $_SESSION['login_fails'] = 0;
            flash('Welcome back! ⏱️');
            header('Location: ' . $next);
            exit;
        }
        $_SESSION['login_fails']++;
        if ($_SESSION['login_fails'] >= 5) {
            $_SESSION['login_lock'] = time() + 300;
            $_SESSION['login_fails'] = 0;
        }
        $error = 'Wrong username/email or password.';
    }
}

$pageTitle = 'Log in · Birthday Time Machine';
require __DIR__ . '/includes/header.php';
?>
<section class="panel narrow auth">
  <h1 class="section-title">⏱️ Log in</h1>
  <?php if ($error): ?><p class="form-error"><?= e($error) ?></p><?php endif; ?>
  <form method="post" class="stack-form">
    <?= csrf_field() ?>
    <input type="hidden" name="next" value="<?= e($next) ?>">
    <label>Username or email <input name="login" required value="<?= e($login) ?>"></label>
    <label>Password <input type="password" name="password" required></label>
    <button type="submit" class="big-btn">Log in</button>
  </form>
  <p>New here? <a href="register.php">Create an account</a></p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
