<?php
// controllers/DashboardController.php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Transaction.php';

class DashboardController {
    private $userModel;
    private $transactionModel;

    public function __construct() {
        $this->userModel = new User();
        $this->transactionModel = new Transaction();
    }

    public function index() {
        requireLogin();
        $data = [
            'daily_revenue'   => $this->transactionModel->getDailyRevenue(),
            'monthly_revenue' => $this->transactionModel->getMonthlyRevenue(),
            'daily_signups'   => $this->userModel->getDailyCount(),
            'monthly_signups' => $this->userModel->getMonthlyCount(),
            'chart_data'      => $this->transactionModel->getDailyChart(7),
        ];
        extract($data);
        require_once __DIR__ . '/../views/dashboard/index.php';
    }
}
