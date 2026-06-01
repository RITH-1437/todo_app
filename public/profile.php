<?php
require '../includes/auth.php';
require '../config/database.php';

$userColumnsStmt = $conn->query("SHOW COLUMNS FROM users");
$userColumns = $userColumnsStmt->fetchAll(PDO::FETCH_COLUMN);

function userColumnExists(array $userColumns, string $column): bool
{
    return in_array($column, $userColumns, true);
}

function redirectWithToast(string $message, string $type = 'success'): never
{
    setToast($message, $type);
    redirect('profile.php');
}

// jsonResponse() provided by helpers/functions.php

function getUploadErrorMessage(int $error): string
{
    return match ($error) {
        UPLOAD_ERR_INI_SIZE,
        UPLOAD_ERR_FORM_SIZE => 'Avatar image is too large.',
        UPLOAD_ERR_PARTIAL => 'Avatar upload was interrupted. Please try again.',
        UPLOAD_ERR_NO_FILE => 'Please choose a valid image.',
        default => 'Avatar upload failed. Please try again.'
    };
}

function avatarDebugSuffix(array $avatarFile, ?string $extension = null, ?string $mimeType = null): string
{
    if (!AVATAR_UPLOAD_DEBUG) {
        return '';
    }

    $errorCode = isset($avatarFile['error']) ? (int) $avatarFile['error'] : -1;
    $size = isset($avatarFile['size']) ? (int) $avatarFile['size'] : 0;
    $safeExtension = $extension !== null && $extension !== '' ? $extension : 'n/a';
    $safeMime = $mimeType !== null && $mimeType !== '' ? $mimeType : 'n/a';

    return ' [debug: code=' . $errorCode . ', ext=' . $safeExtension . ', mime=' . $safeMime . ', size=' . $size . ']';
}

// getSafeAvatarPath() provided by helpers/functions.php

function deleteOldAvatar(?string $avatar): void
{
    if (!$avatar || $avatar === DEFAULT_AVATAR) {
        return;
    }

    $avatar = ltrim(str_replace('\\', '/', $avatar), '/');

    if (!str_starts_with($avatar, AVATAR_PUBLIC_DIR . '/')) {
        return;
    }

    $path = PUBLIC_DIR . '/' . $avatar;

    if (is_file($path)) {
        unlink($path);
    }
}


$user = fetchCurrentUser($conn, $userColumns);
if (!$user) { redirect('auth/login.php'); }
$toast = getToast();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_theme') {
        if (!userColumnExists($userColumns, 'theme_preference')) {
            jsonResponse([
                'success' => false,
                'message' => 'Theme preference column is missing. Please run the latest migration.'
            ], 500);
        }

        $theme = $_POST['theme_preference'] ?? 'dark';

        if (!in_array($theme, ['light', 'dark'], true)) {
            jsonResponse([
                'success' => false,
                'message' => 'Invalid theme preference.'
            ], 422);
        }

        $stmt = $conn->prepare("
            UPDATE users
            SET theme_preference = :theme
            WHERE id = :id
        ");

        $stmt->execute([
            ':theme' => $theme,
            ':id' => $_SESSION['user_id']
        ]);

        $_SESSION['theme_preference'] = $theme;

        jsonResponse([
            'success' => true,
            'message' => 'Theme changed successfully.',
            'theme' => $theme
        ]);
    }

    if ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');

        if ($name === '') {
            redirectWithToast('Username cannot be empty.', 'error');
        }

        $stmt = $conn->prepare("
            UPDATE users
            SET name = :name
            WHERE id = :id
        ");

        $stmt->execute([
            ':name' => $name,
            ':id' => $_SESSION['user_id']
        ]);

        $_SESSION['user_name'] = $name;

        redirectWithToast('Profile updated successfully.');
    }

    if ($action === 'update_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (!password_verify($currentPassword, $user['password'])) {
            redirectWithToast('Current password is incorrect.', 'error');
        }

        if (strlen($newPassword) < 6) {
            redirectWithToast('New password must be at least 6 characters.', 'error');
        }

        if ($newPassword !== $confirmPassword) {
            redirectWithToast('Password confirmation does not match.', 'error');
        }

        $stmt = $conn->prepare("
            UPDATE users
            SET password = :password
            WHERE id = :id
        ");

        $stmt->execute([
            ':password' => password_hash($newPassword, PASSWORD_DEFAULT),
            ':id' => $_SESSION['user_id']
        ]);

        redirectWithToast('Password changed successfully.');
    }

    if ($action === 'upload_avatar') {
        if (!userColumnExists($userColumns, 'avatar')) {
            redirectWithToast('Avatar column is missing. Please run the latest migration.', 'error');
        }

        if (!isset($_FILES['avatar']) || !is_array($_FILES['avatar'])) {
            redirectWithToast('Please choose a valid image.', 'error');
        }

        $avatarFile = $_FILES['avatar'];

        if (($avatarFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            redirectWithToast(
                getUploadErrorMessage((int) ($avatarFile['error'] ?? UPLOAD_ERR_NO_FILE)) . avatarDebugSuffix($avatarFile),
                'error'
            );
        }

        $avatarSize = (int) ($avatarFile['size'] ?? 0);

        if ($avatarSize <= 0 || $avatarSize > MAX_AVATAR_SIZE) {
            redirectWithToast('Avatar must be ' . MAX_AVATAR_SIZE_LABEL . ' or smaller.' . avatarDebugSuffix($avatarFile), 'error');
        }

        $tmpPath = (string) ($avatarFile['tmp_name'] ?? '');

        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            redirectWithToast('Invalid avatar upload.' . avatarDebugSuffix($avatarFile), 'error');
        }

        $allowedMimeMap = [
            'image/jpeg' => ['jpg', 'jpeg'],
            'image/pjpeg' => ['jpg', 'jpeg'],
            'image/jpg' => ['jpg', 'jpeg'],
            'image/png' => ['png'],
            'image/x-png' => ['png'],
            'image/webp' => ['webp']
        ];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

        $originalName = (string) ($avatarFile['name'] ?? '');
        $originalExtension = strtolower(trim(ltrim((string) pathinfo($originalName, PATHINFO_EXTENSION), '.')));

        if (!in_array($originalExtension, $allowedExtensions, true)) {
            redirectWithToast(
                'Avatar must be a JPG, PNG, JPEG, or WEBP image.' . avatarDebugSuffix($avatarFile, $originalExtension),
                'error'
            );
        }

        $mimeType = '';

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = $finfo ? (string) finfo_file($finfo, $tmpPath) : '';

            if ($finfo) {
                finfo_close($finfo);
            }
        }

        if (!$mimeType && function_exists('mime_content_type')) {
            $mimeType = (string) mime_content_type($tmpPath);
        }

        if (!$mimeType) {
            $imageInfo = @getimagesize($tmpPath);

            if ($imageInfo && isset($imageInfo['mime'])) {
                $mimeType = (string) $imageInfo['mime'];
            }
        }

        $mimeType = strtolower(trim(explode(';', $mimeType)[0] ?? ''));

        if (!isset($allowedMimeMap[$mimeType])) {
            redirectWithToast(
                'Avatar must be a JPG, PNG, JPEG, or WEBP image.' . avatarDebugSuffix($avatarFile, $originalExtension, $mimeType),
                'error'
            );
        }

        if (!in_array($originalExtension, $allowedMimeMap[$mimeType], true)) {
            redirectWithToast(
                'Avatar extension does not match detected image type.' . avatarDebugSuffix($avatarFile, $originalExtension, $mimeType),
                'error'
            );
        }

        if (!@getimagesize($tmpPath)) {
            redirectWithToast(
                'Avatar file is not a valid image.' . avatarDebugSuffix($avatarFile, $originalExtension, $mimeType),
                'error'
            );
        }

        if (!is_dir(AVATAR_UPLOAD_DIR) && !mkdir(AVATAR_UPLOAD_DIR, 0755, true)) {
            redirectWithToast('Avatar upload folder could not be created.', 'error');
        }

        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $baseName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $baseName);
        $baseName = trim(substr($baseName, 0, 40), '_') ?: 'avatar';
        $extension = $allowedMimeMap[$mimeType][0];
        $filename = 'user_' . (int) $_SESSION['user_id'] . '_' . bin2hex(random_bytes(8)) . '_' . $baseName . '.' . $extension;
        $destination = AVATAR_UPLOAD_DIR . '/' . $filename;
        $storedPath = AVATAR_PUBLIC_DIR . '/' . $filename;

        if (!move_uploaded_file($tmpPath, $destination)) {
            redirectWithToast('Avatar upload failed.' . avatarDebugSuffix($avatarFile, $originalExtension, $mimeType), 'error');
        }

        $stmt = $conn->prepare("
            UPDATE users
            SET avatar = :avatar
            WHERE id = :id
        ");

        $stmt->execute([
            ':avatar' => $storedPath,
            ':id' => $_SESSION['user_id']
        ]);

        deleteOldAvatar($user['avatar'] ?? null);

        redirectWithToast('Avatar uploaded successfully.');
    }
}

$user = fetchCurrentUser($conn, $userColumns);
if (!$user) { redirect('auth/login.php'); }
$avatarPath   = getSafeAvatarPath($user['avatar'] ?? null);
$avatarUrl    = $avatarPath;
$defaultAvatarUrl = DEFAULT_AVATAR;
$themePreference = $user['theme_preference'] === 'light' ? 'light' : 'dark';
$isDark = $themePreference === 'dark';

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script>
        window.todoInitialTheme = <?= json_encode($themePreference) ?>;
        try {
            localStorage.setItem('theme', window.todoInitialTheme);
            document.documentElement.dataset.theme = window.todoInitialTheme;
        } catch (error) {
            document.documentElement.dataset.theme = window.todoInitialTheme;
        }
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body id="body" data-theme="<?= htmlspecialchars($themePreference) ?>" class="<?= $isDark ? 'bg-slate-950 text-white' : 'bg-gray-100 text-slate-900' ?> min-h-screen transition duration-300">
    <main class="mx-auto max-w-6xl px-5 py-10 lg:px-8">
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a id="profile-back-link" href="index.php" class="<?= $isDark ? 'text-slate-400 hover:text-white' : 'text-slate-500 hover:text-slate-900' ?> text-sm font-medium transition">
                    Back to dashboard
                </a>
                <h1 class="mt-3 text-4xl font-bold tracking-tight">Profile Settings</h1>
                <p class="profile-muted <?= $isDark ? 'text-slate-400' : 'text-slate-500' ?> mt-2">
                    Manage your account, avatar, password, and dashboard preferences.
                </p>
            </div>
        </div>

        <section class="grid gap-6 lg:grid-cols-[360px_minmax(0,1fr)]">
            <aside class="profile-surface <?= $isDark ? 'bg-white/[0.07] border-white/10 shadow-slate-950/30' : 'bg-white border-slate-200 shadow-slate-200/80' ?> rounded-2xl border p-6 shadow-2xl backdrop-blur-xl transition duration-300 sm:p-8">
                <div class="flex flex-col items-center text-center">
                    <div class="relative">
                        <div class="absolute inset-0 rounded-full bg-blue-500/20 blur-2xl"></div>
                        <img id="avatar-preview" src="<?= htmlspecialchars($avatarUrl) ?>" onerror="this.onerror=null;this.src='<?= htmlspecialchars($defaultAvatarUrl) ?>';" alt="Profile avatar" class="relative h-28 w-28 rounded-full object-cover ring-4 ring-blue-500/30 shadow-2xl shadow-blue-500/20 transition duration-300 hover:scale-[1.03] hover:ring-blue-400/70 sm:h-32 sm:w-32">
                    </div>
                    <h2 class="mt-5 text-2xl font-bold"><?= htmlspecialchars($user['name']) ?></h2>
                    <p class="profile-muted <?= $isDark ? 'text-slate-400' : 'text-slate-500' ?> mt-1 break-all text-sm"><?= htmlspecialchars($user['email']) ?></p>
                </div>

                <form id="avatar-upload-form" method="POST" enctype="multipart/form-data" class="mt-8 space-y-4">
                    <input type="hidden" name="action" value="upload_avatar">
                    <label class="profile-muted <?= $isDark ? 'text-slate-300' : 'text-slate-600' ?> block text-sm font-medium" for="avatar-input">
                        Upload profile image
                    </label>
                    <input id="avatar-input" type="file" name="avatar" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="profile-input w-full rounded-2xl border <?= $isDark ? 'border-white/10 bg-slate-900/80 text-white file:bg-slate-800 file:text-white' : 'border-slate-200 bg-white text-slate-900 file:bg-slate-100 file:text-slate-700' ?> px-4 py-3 text-sm file:mr-4 file:rounded-xl file:border-0 file:px-4 file:py-2 file:font-semibold transition">
                    <p class="profile-muted <?= $isDark ? 'text-slate-400' : 'text-slate-500' ?> text-xs">
                        JPG, PNG, or WEBP. Maximum size: <?= htmlspecialchars(MAX_AVATAR_SIZE_LABEL) ?>.
                    </p>
                    <button type="submit" class="w-full rounded-2xl bg-blue-600 px-5 py-3 font-semibold text-white shadow-lg shadow-blue-500/20 transition hover:-translate-y-0.5 hover:bg-blue-700 hover:shadow-blue-500/30">
                        Upload Avatar
                    </button>
                </form>
            </aside>

            <div class="space-y-6">
                <section class="profile-surface <?= $isDark ? 'bg-white/[0.07] border-white/10 shadow-slate-950/30' : 'bg-white border-slate-200 shadow-slate-200/80' ?> rounded-2xl border p-6 shadow-2xl backdrop-blur-xl transition duration-300 sm:p-8">
                    <h2 class="text-xl font-bold">Username</h2>
                    <p class="profile-muted <?= $isDark ? 'text-slate-400' : 'text-slate-500' ?> mt-1 text-sm">Update the display name shown across your dashboard.</p>
                    <form method="POST" class="mt-6 grid gap-4 sm:grid-cols-[1fr_auto]">
                        <input type="hidden" name="action" value="update_profile">
                        <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required class="profile-input rounded-2xl border px-5 py-4 transition focus:outline-none focus:ring-4 focus:ring-blue-500/20 <?= $isDark ? 'border-white/10 bg-slate-900/80 text-white placeholder-slate-400' : 'border-slate-200 bg-white text-slate-900 placeholder-slate-400' ?>">
                        <button type="submit" class="rounded-2xl bg-blue-600 px-6 py-4 font-semibold text-white shadow-lg shadow-blue-500/20 transition hover:-translate-y-0.5 hover:bg-blue-700">
                            Save
                        </button>
                    </form>
                </section>

                <section class="profile-surface <?= $isDark ? 'bg-white/[0.07] border-white/10 shadow-slate-950/30' : 'bg-white border-slate-200 shadow-slate-200/80' ?> rounded-2xl border p-6 shadow-2xl backdrop-blur-xl transition duration-300 sm:p-8">
                    <h2 class="text-xl font-bold">Password</h2>
                    <p class="profile-muted <?= $isDark ? 'text-slate-400' : 'text-slate-500' ?> mt-1 text-sm">Use a secure password with at least 6 characters.</p>
                    <form method="POST" class="mt-6 grid gap-4">
                        <input type="hidden" name="action" value="update_password">
                        <input type="password" name="current_password" placeholder="Current password" required class="profile-input rounded-2xl border px-5 py-4 transition focus:outline-none focus:ring-4 focus:ring-blue-500/20 <?= $isDark ? 'border-white/10 bg-slate-900/80 text-white placeholder-slate-400' : 'border-slate-200 bg-white text-slate-900 placeholder-slate-400' ?>">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <input type="password" name="new_password" placeholder="New password" required class="profile-input rounded-2xl border px-5 py-4 transition focus:outline-none focus:ring-4 focus:ring-blue-500/20 <?= $isDark ? 'border-white/10 bg-slate-900/80 text-white placeholder-slate-400' : 'border-slate-200 bg-white text-slate-900 placeholder-slate-400' ?>">
                            <input type="password" name="confirm_password" placeholder="Confirm password" required class="profile-input rounded-2xl border px-5 py-4 transition focus:outline-none focus:ring-4 focus:ring-blue-500/20 <?= $isDark ? 'border-white/10 bg-slate-900/80 text-white placeholder-slate-400' : 'border-slate-200 bg-white text-slate-900 placeholder-slate-400' ?>">
                        </div>
                        <button type="submit" class="w-full rounded-2xl bg-slate-900 px-6 py-4 font-semibold text-white shadow-lg shadow-slate-950/20 transition hover:-translate-y-0.5 hover:bg-black sm:w-auto sm:justify-self-start">
                            Change Password
                        </button>
                    </form>
                </section>

                <section class="profile-surface <?= $isDark ? 'bg-white/[0.07] border-white/10 shadow-slate-950/30' : 'bg-white border-slate-200 shadow-slate-200/80' ?> rounded-2xl border p-6 shadow-2xl backdrop-blur-xl transition duration-300 sm:p-8">
                    <h2 class="text-xl font-bold">Theme Preference</h2>
                    <p class="profile-muted <?= $isDark ? 'text-slate-400' : 'text-slate-500' ?> mt-1 text-sm">This preference is saved to your account and loaded after login.</p>
                    <form id="theme-preference-form" method="POST" class="mt-6 flex flex-col gap-4 sm:flex-row">
                        <input type="hidden" name="action" value="update_theme">
                        <select name="theme_preference" class="profile-input rounded-2xl border px-5 py-4 transition focus:outline-none focus:ring-4 focus:ring-blue-500/20 <?= $isDark ? 'border-white/10 bg-slate-900/80 text-white' : 'border-slate-200 bg-white text-slate-900' ?>">
                            <option value="dark" <?= $themePreference === 'dark' ? 'selected' : '' ?>>Dark mode</option>
                            <option value="light" <?= $themePreference === 'light' ? 'selected' : '' ?>>Light mode</option>
                        </select>
                        <button type="submit" class="rounded-2xl bg-blue-600 px-6 py-4 font-semibold text-white shadow-lg shadow-blue-500/20 transition hover:-translate-y-0.5 hover:bg-blue-700">
                            Save Theme
                        </button>
                    </form>
                </section>
            </div>
        </section>
    </main>

    <div id="toast-container" class="fixed top-5 right-5 z-50 space-y-3"></div>

    <script src="../assets/js/toast.js"></script>
    <script src="../assets/js/profile.js"></script>
    <?php if ($toast): ?>
        <script>
            showToast(
                <?= json_encode($toast['message']) ?>,
                <?= json_encode($toast['type']) ?>
            );
        </script>
    <?php endif; ?>
</body>

</html>
