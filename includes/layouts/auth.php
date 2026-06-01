<?php
/**
 * Auth page layout — top section (head + card open).
 * Closes in includes/layouts/auth_end.php.
 *
 * Required variables:
 *   string $pageTitle   Page title (plain text)
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Auth') ?> — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-gradient-to-br from-[#0b1120] via-[#111827] to-black flex items-center justify-center px-5">

    <div class="w-full max-w-md bg-gray-800/80 backdrop-blur-xl border border-gray-700 rounded-2xl shadow-2xl p-7 sm:p-8">
