<?php
/**
 * Main application layout — HTML head + nav bar.
 *
 * Required variables (set by the including page before this include):
 *   string       $pageTitle         Page <title> (plain text)
 *   string       $themePreference   'dark' | 'light'
 *   array        $currentUser       ['name', 'email', ...]
 *   string       $userAvatarUrl     Relative URL to the user avatar
 *   string       $defaultAvatarUrl  Fallback avatar URL
 *   string[]     $headScripts       (optional) extra <script> src tags in <head>
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? APP_NAME) ?> — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">

    <!-- Inject server-side theme before Tailwind renders to prevent flash -->
    <script>
        window.todoInitialTheme = <?= json_encode($themePreference) ?>;
        try {
            localStorage.setItem('theme', window.todoInitialTheme);
            document.documentElement.dataset.theme = window.todoInitialTheme;
        } catch (e) {
            document.documentElement.dataset.theme = window.todoInitialTheme;
        }
    </script>

    <script src="https://cdn.tailwindcss.com"></script>
    <?php if (!empty($headScripts)): foreach ($headScripts as $src): ?>
        <script src="<?= htmlspecialchars($src) ?>"></script>
    <?php endforeach; endif; ?>
</head>

<body id="body"
      data-theme="<?= htmlspecialchars($themePreference) ?>"
      class="<?= $themePreference === 'dark' ? 'bg-slate-950' : 'bg-gray-100' ?> min-h-screen transition duration-300">

    <div class="max-w-7xl mx-auto py-10 px-5 lg:px-8">

        <!-- Top bar: branding + profile menu -->
        <div class="flex justify-between items-center mb-8">

            <div>
                <h1 id="app-title"
                    class="text-4xl font-bold <?= $themePreference === 'dark' ? 'text-white' : 'text-gray-800' ?> transition duration-300">
                    <?= APP_NAME ?>
                </h1>
                <p id="welcome-text"
                   class="<?= $themePreference === 'dark' ? 'text-slate-400' : 'text-gray-500' ?> mt-2 transition duration-300">
                    Welcome, <?= htmlspecialchars($currentUser['name']) ?>
                </p>
            </div>

            <!-- Profile menu -->
            <div class="relative">
                <button id="profile-menu-button" type="button"
                        class="group flex items-center gap-3 rounded-2xl border border-white/10 bg-white/[0.07] px-3 py-2 text-white shadow-[0_18px_45px_rgba(15,23,42,0.20)] backdrop-blur-xl transition-all duration-300 hover:-translate-y-0.5 hover:shadow-blue-500/20">
                    <img src="<?= htmlspecialchars($userAvatarUrl) ?>"
                         onerror="this.onerror=null;this.src='<?= htmlspecialchars($defaultAvatarUrl) ?>';"
                         alt="Profile avatar"
                         class="h-11 w-11 rounded-full object-cover ring-2 ring-blue-400/30 transition duration-300 group-hover:ring-blue-400/70">
                    <span class="hidden text-left sm:block">
                        <span class="block text-sm font-semibold profile-menu-name"><?= htmlspecialchars($currentUser['name']) ?></span>
                        <span class="block text-xs text-slate-400 profile-menu-email"><?= htmlspecialchars($currentUser['email']) ?></span>
                    </span>
                </button>

                <div id="profile-dropdown"
                     class="pointer-events-none absolute right-0 top-16 z-40 w-64 translate-y-2 scale-95 rounded-xl border border-white/10 bg-slate-900/95 p-2 text-white opacity-0 shadow-2xl shadow-slate-950/40 backdrop-blur-xl transition-all duration-200">
                    <div class="border-b border-white/10 px-3 py-3">
                        <p class="text-sm font-semibold"><?= htmlspecialchars($currentUser['name']) ?></p>
                        <p class="mt-1 truncate text-xs text-slate-400"><?= htmlspecialchars($currentUser['email']) ?></p>
                    </div>

                    <a href="profile.php"
                       class="mt-2 block rounded-lg px-3 py-2 text-sm transition hover:bg-white/10">
                        Profile Settings
                    </a>

                    <button id="theme-toggle" type="button"
                            class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm transition hover:bg-white/10">
                        <span>Theme Toggle</span>
                        <span id="theme-toggle-icon"><?= $themePreference === 'dark' ? '☀️' : '🌙' ?></span>
                    </button>

                    <a href="auth/logout.php"
                       class="mt-1 block rounded-lg px-3 py-2 text-sm text-red-300 transition hover:bg-red-500/10 hover:text-red-200">
                        Logout
                    </a>
                </div>
            </div>

        </div><!-- /top bar -->
