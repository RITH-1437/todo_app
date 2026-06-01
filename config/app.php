<?php

/**
 * Application-wide constants.
 * Included early by every page before any other require.
 */

// ── Load .env variables first ─────────────────────────────────────────────────
require_once __DIR__ . '/env.php';
loadEnv(__DIR__ . '/../.env');

// ── Identity ──────────────────────────────────────────────────────────────────
defined('APP_NAME')  || define('APP_NAME',  env('APP_NAME', 'Todo App'));

// ── Paths (computed once relative to project root) ───────────────────────────
defined('APP_ROOT')  || define('APP_ROOT',  dirname(__DIR__));
defined('PUBLIC_DIR')|| define('PUBLIC_DIR', APP_ROOT . '/public');

// ── Avatar ────────────────────────────────────────────────────────────────────
defined('DEFAULT_AVATAR')        || define('DEFAULT_AVATAR',        'uploads/avatars/default-avatar.svg');
defined('AVATAR_PUBLIC_DIR')     || define('AVATAR_PUBLIC_DIR',     'uploads/avatars');
defined('AVATAR_UPLOAD_DIR')     || define('AVATAR_UPLOAD_DIR',     PUBLIC_DIR . '/uploads/avatars');
defined('MAX_AVATAR_SIZE')       || define('MAX_AVATAR_SIZE',       52428800); // 50 MB
defined('MAX_AVATAR_SIZE_LABEL') || define('MAX_AVATAR_SIZE_LABEL', '50MB');
defined('AVATAR_UPLOAD_DEBUG')   || define('AVATAR_UPLOAD_DEBUG',   false);
