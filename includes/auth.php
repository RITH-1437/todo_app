<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/helpers/functions.php';

requireLogin('/auth/login.php');