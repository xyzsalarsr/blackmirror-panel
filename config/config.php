<?php
// config/config.php
session_start();

define('BASE_URL', 'https://mahdiism.xyz/top');
define('PUBLIC_URL', BASE_URL . '/public');

function isLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . PUBLIC_URL . '/index.php?page=login');
        exit;
    }
}

function redirect($page) {
    header('Location: ' . PUBLIC_URL . '/index.php?page=' . $page);
    exit;
}

function generateHash() {
    return bin2hex(random_bytes(32));
}
