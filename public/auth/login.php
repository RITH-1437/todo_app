<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once '../../config/app.php';
require_once '../../helpers/functions.php';
require '../../config/database.php';

// Redirect already-authenticated users
if (isLoggedIn()) {
    redirect('../index.php');
}

$toast = getToast();

if (isset($_POST['login'])) {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $toast = ['message' => 'Please fill in all fields.', 'type' => 'error'];
    } else {
        $stmt = $conn->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            setToast('Login successful!', 'success');
            redirect('../index.php');
        } else {
            $toast = ['message' => 'Invalid email or password.', 'type' => 'error'];
        }
    }
}

$pageTitle   = 'Login';
$pageScripts = ['../../assets/js/login.js'];
require '../../includes/layouts/auth.php';
?>

        <h1 class="text-3xl font-bold text-white mb-6">Login</h1>

        <form method="POST" class="space-y-4">
            <input type="email" name="email"
                   placeholder="Enter email"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                   required
                   class="w-full bg-gray-900 text-white border border-gray-700 placeholder-gray-400 rounded-xl px-4 py-3 focus:outline-none focus:ring-4 focus:ring-blue-500/30">

            <input type="password" name="password"
                   placeholder="Enter password"
                   required
                   class="w-full bg-gray-900 text-white border border-gray-700 placeholder-gray-400 rounded-xl px-4 py-3 focus:outline-none focus:ring-4 focus:ring-blue-500/30">

            <button type="submit" name="login"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl py-3 transition duration-200 shadow-lg shadow-blue-500/20">
                Login
            </button>
        </form>

        <p class="text-center text-gray-300 mt-5">
            Don't have an account?
            <a href="register.php" class="text-blue-500 hover:underline">Register</a>
        </p>

<?php require '../../includes/layouts/auth_end.php'; ?>