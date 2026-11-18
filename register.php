<?php
require_once __DIR__ . '/includes/auth.php';

$errors = [];
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $username)) {
        $errors[] = 'Username must be 3 to 30 letters, numbers or underscores.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors) {
        $check = db()->prepare('SELECT 1 FROM users WHERE username = ? OR email = ?');
        $check->execute([$username, $email]);
        if ($check->fetchColumn()) {
            $errors[] = 'That username or email is already taken.';
        }
    }

    if (!$errors) {
        $stmt = db()->prepare('INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)');
        $stmt->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT)]);
        $newId = (int)db()->lastInsertId();   // grab the ID before the session code runs more queries
        session_regenerate_id(true);
        $_SESSION['user_id'] = $newId;
        flash('Welcome aboard, ' . $username . '! 🚀');
        header('Location: index.php');
        exit;
    }
}

$pageTitle = 'Sign up · Birthday Time Machine';
require __DIR__ . '/includes/header.php';
?>
<section class="panel narrow auth">
  <h1 class="section-title">🚀 Create an account</h1>
  <p>Save your trips and get share links.</p>
  <?php foreach ($errors as $err): ?><p class="form-error"><?= e($err) ?></p><?php endforeach; ?>
  <form method="post" class="stack-form">
    <?= csrf_field() ?>
    <label>Username <input name="username" required maxlength="30" value="<?= e($username) ?>"></label>
    <label>Email <input type="email" name="email" required value="<?= e($email) ?>"></label>
    <label>Password <input type="password" name="password" required minlength="8"></label>
    <label>Confirm password <input type="password" name="confirm" required minlength="8"></label>
    <button type="submit" class="big-btn">Sign up</button>
  </form>
  <p>Already have an account? <a href="login.php">Log in</a></p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
