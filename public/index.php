<?php
// public/index.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$page   = $_GET['page']   ?? 'dashboard';
$action = $_GET['action'] ?? 'index';

$allowedPages = ['login', 'logout', 'dashboard', 'users', 'accounting', 'settings'];
if (!in_array($page, $allowedPages)) {
    $page = 'dashboard';
}

switch ($page) {
    case 'login':
        require_once __DIR__ . '/../controllers/AuthController.php';
        $controller = new AuthController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = trim($_POST['password'] ?? '');
            if ($controller->login($username, $password)) {
                redirect('dashboard');
            } else {
                $error = 'نام کاربری یا رمز عبور اشتباه است.';
                require_once __DIR__ . '/../views/auth/login.php';
            }
        } else {
            if (isLoggedIn()) redirect('dashboard');
            require_once __DIR__ . '/../views/auth/login.php';
        }
        break;

    case 'logout':
        require_once __DIR__ . '/../controllers/AuthController.php';
        $controller = new AuthController();
        $controller->logout();
        break;

    case 'dashboard':
        require_once __DIR__ . '/../controllers/DashboardController.php';
        $controller = new DashboardController();
        $controller->index();
        break;

    case 'users':
        require_once __DIR__ . '/../controllers/UserController.php';
        $controller = new UserController();
        if ($action === 'store')  $controller->store();
        elseif ($action === 'update') $controller->update();
        elseif ($action === 'delete') $controller->delete();
        elseif ($action === 'get')    $controller->get();
        else $controller->index();
        break;

    case 'accounting':
        require_once __DIR__ . '/../controllers/AccountingController.php';
        $controller = new AccountingController();
        if ($action === 'store')  $controller->store();
        elseif ($action === 'update') $controller->update();
        elseif ($action === 'delete') $controller->delete();
        else $controller->index();
        break;

    case 'settings':
        require_once __DIR__ . '/../controllers/SettingsController.php';
        $controller = new SettingsController();
        if ($action === 'update') $controller->update();
        else $controller->index();
        break;
}
