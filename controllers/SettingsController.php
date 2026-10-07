<?php
// controllers/SettingsController.php
require_once __DIR__ . '/../models/Setting.php';

class SettingsController {
    private $settingModel;

    public function __construct() {
        $this->settingModel = new Setting();
    }

    public function index() {
        requireLogin();
        $settings = $this->settingModel->getAll();
        require_once __DIR__ . '/../views/settings/index.php';
    }

    public function update()
    {
        requireLogin();

        $defaultSubscription = $_POST['default_subscription'] ?? '';

        $this->settingModel->set('default_subscription', $defaultSubscription);

        redirect('settings');
    }
}
