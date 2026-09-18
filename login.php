<?php
require_once __DIR__ . '/config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$email = '';
$justRegistered = isset($_GET['registered']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = 'Invalid email or password.';
    } else {
        $stmt = $conn->prepare('SELECT id, name, password FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            // Regenerate session ID on login to prevent session fixation
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];

            header('Location: dashboard.php');
            exit;
        } else {
            $errors[] = 'Invalid email or password.';
        }
    }
}

$pageTitle = 'Login';
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrap">

    <div class="auth-card">

        <div class="login-logo">
            ✓
        </div>

        <h1>Welcome Back</h1>

        <p class="auth-sub">
            Sign in to your Task Manager account
        </p>

        <?php if ($justRegistered && empty($errors)): ?>
            <div class="alert alert-success">
                Account created. You can log in now.
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" novalidate>

            <label for="email">Email or Username</label>

            <div class="input-wrapper">
                

                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?php echo htmlspecialchars($email); ?>"
                    placeholder="Enter your email"
                    required
                >
            </div>


            <label for="password">Password</label>

            <div class="input-wrapper">
                

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    required
                >

                <button
                    type="button"
                    class="password-toggle"
                    onclick="togglePassword()"
                >
                    
                </button>
            </div>


            <button type="submit" class="btn btn-primary btn-block">
                Login
            </button>

        </form>

        <p class="auth-switch">
            New here?
            <a href="register.php">Create an account</a>
        </p>

    </div>

</div>

<script>
function togglePassword() {

    const password = document.getElementById("password");

    if (password.type === "password") {
        password.type = "text";
    } else {
        password.type = "password";
    }
}
</script>
         
<?php require_once __DIR__ . '/includes/footer.php'; ?>
