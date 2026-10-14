<?php
require_once 'config/database.php';
if (is_logged_in()) redirect('/dashboard.php');

$mode = ($_GET['mode'] ?? '') === 'register' ? 'register' : 'login';
$errors = [];
$name = $email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    if ($mode === 'register') {
        if ($name === '') $errors[] = 'Name is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email.';
        if (strlen($pass) < 6) $errors[] = 'Password must be at least 6 characters.';
        if (!$errors) {
            $c = $pdo->prepare('SELECT id FROM expense_users WHERE email = ?');
            $c->execute([$email]);
            if ($c->fetch()) $errors[] = 'This email is already registered.';
        }
        if (!$errors) {
            $pdo->prepare('INSERT INTO expense_users (name, email, password) VALUES (?,?,?)')
                ->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT)]);
            flash('success', 'Account created. Please login.');
            redirect('/login.php');
        }
    } else {
        $s = $pdo->prepare('SELECT * FROM expense_users WHERE email = ?');
        $s->execute([$email]);
        $u = $s->fetch();
        if ($u && password_verify($pass, $u['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $u['id'];
            $_SESSION['name'] = $u['name'];
            redirect('/dashboard.php');
        }
        $errors[] = 'Wrong email or password.';
    }
}
render_header($mode === 'register' ? 'Register' : 'Login', 'auth');
?>
<div class="auth-wrap">
  <aside class="auth-art">
    <a class="brand" href="<?= BASE_URL ?>/index.php"><span class="mark">&#8377;</span>Expense Tracker</a>
    <h1>See where your money goes.</h1>
    <p>Log pocket money, hostel fees and book spends, then check your month at a glance.</p>
    <span class="float f1 cat-food"><i class="dot"></i>Food &#8377;180</span>
    <span class="float f2 cat-books"><i class="dot"></i>Books &#8377;450</span>
    <span class="float f3 cat-travel"><i class="dot"></i>Travel &#8377;60</span>
    <span class="float f4 cat-hostel"><i class="dot"></i>Hostel &#8377;3,500</span>
  </aside>
  <section class="auth-form">
    <div class="form-box">
      <h2><?= $mode === 'register' ? 'Create your account' : 'Welcome back' ?></h2>
      <?php foreach ($errors as $x): ?><div class="alert error"><?= e($x) ?></div><?php endforeach; ?>
      <form method="post" class="js-validate" novalidate>
        <?php if ($mode === 'register'): ?><label>Full name<input type="text" name="name" value="<?= e($name) ?>" required></label><?php endif; ?>
        <label>Email<input type="email" name="email" value="<?= e($email) ?>" required></label>
        <label>Password<input type="password" name="password" <?= $mode === 'register' ? 'minlength="6" ' : '' ?>required></label>
        <button class="btn" type="submit"><?= $mode === 'register' ? 'Create account' : 'Log in' ?></button>
      </form>
      <p class="muted"><?= $mode === 'register' ? '<a href="login.php">Back to login</a>' : 'New here? <a href="login.php?mode=register">Create an account</a>' ?></p>
    </div>
  </section>
</div>
<?php render_footer(); ?>
