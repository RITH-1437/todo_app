<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once '../../config/app.php';
require_once '../../helpers/functions.php';
require '../../config/database.php';

// Redirect already-authenticated users
if (isLoggedIn()) {
    redirect('../index.php');
}

$toast  = null;
$errors = [];

if (isset($_POST['register'])) {
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($name === '')                                    { $errors[] = 'Name is required.'; }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'A valid email is required.'; }
    if (strlen($password) < 8)                          { $errors[] = 'Password must be at least 8 characters.'; }

    if (empty($errors)) {
        // Check email uniqueness
        $check = $conn->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $check->execute([':email' => $email]);
        if ($check->fetch()) {
            $errors[] = 'An account with that email already exists.';
        }
    }

    if (empty($errors)) {
        $stmt = $conn->prepare(
            'INSERT INTO users (name, email, password) VALUES (:name, :email, :password)'
        );
        $stmt->execute([
            ':name'     => $name,
            ':email'    => $email,
            ':password' => password_hash($password, PASSWORD_DEFAULT),
        ]);
        setToast('Registration successful! Please log in.', 'success');
        redirect('login.php');
    } else {
        $toast = ['message' => implode(' ', $errors), 'type' => 'error'];
    }
}

$pageTitle   = 'Register';
$pageScripts = ['../../assets/js/register.js'];
require '../../includes/layouts/auth.php';
?>

        <h1 class="text-3xl font-bold text-white mb-6">Register</h1>

        <form method="POST" class="space-y-4">
            <input type="text" name="name"
                   placeholder="Enter name"
                   value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                   required
                   class="w-full bg-gray-900 text-white border border-gray-700 placeholder-gray-400 rounded-xl px-4 py-3 focus:outline-none focus:ring-4 focus:ring-blue-500/30">

            <input type="email" name="email"
                   placeholder="Enter email"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                   required
                   class="w-full bg-gray-900 text-white border border-gray-700 placeholder-gray-400 rounded-xl px-4 py-3 focus:outline-none focus:ring-4 focus:ring-blue-500/30">

            <input type="password" name="password"
                   placeholder="Enter password (min 8 characters)"
                   required
                   class="w-full bg-gray-900 text-white border border-gray-700 placeholder-gray-400 rounded-xl px-4 py-3 focus:outline-none focus:ring-4 focus:ring-blue-500/30">

            <button type="submit" name="register"
                    class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold rounded-xl py-3 transition duration-200 shadow-lg shadow-green-500/20">
                Register
            </button>
        </form>

        <p class="text-center text-gray-300 mt-5">
            Already have an account?
            <a href="login.php" class="text-blue-500 hover:underline">Login</a>
        </p>

<?php require '../../includes/layouts/auth_end.php'; ?>