<?php
// controllers/AccountingController.php
require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../models/User.php';

class AccountingController {
    private $transactionModel;
    private $userModel;
    private const PER_PAGE = 15;

    public function __construct() {
        $this->transactionModel = new Transaction();
        $this->userModel        = new User();
    }

    public function index(): void {
        requireLogin();

        $page    = max(1, intval($_GET['p'] ?? 1));
        $perPage = self::PER_PAGE;
        $offset  = ($page - 1) * $perPage;
        $total   = $this->transactionModel->countAll();

        $transactions    = $this->transactionModel->getAll($perPage, $offset);
        $monthly_revenue = $this->transactionModel->getMonthlyRevenue();
        $total_revenue   = $this->transactionModel->getTotalRevenue();
        $users           = $this->userModel->getAll();
        $totalPages      = (int) ceil($total / $perPage);

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            $html       = $this->renderTableRows($transactions);
            $pagination = $this->renderPagination($page, $totalPages, $total);
            echo json_encode([
                'success'    => true,
                'html'       => $html,
                'pagination' => $pagination,
                'total'      => $total,
            ]);
            exit;
        }

        require_once __DIR__ . '/../views/accounting/index.php';
    }

    private function renderTableRows(array $transactions): string {
        if (empty($transactions)) {
            return '<tr><td colspan="6" class="empty-state">تراکنشی یافت نشد</td></tr>';
        }

        $page   = max(1, intval($_GET['p'] ?? 1));
        $offset = ($page - 1) * self::PER_PAGE;

        $html = '';
        foreach ($transactions as $i => $trans) {
            $rowNum    = $offset + $i + 1;
            $userName  = $trans['user_name']
                ? '<div class="user-cell"><div class="user-avatar-sm">'
                  . mb_substr(htmlspecialchars($trans['user_name']), 0, 1)
                  . '</div>' . htmlspecialchars($trans['user_name']) . '</div>'
                : '<span class="text-muted">تراکنش مستقل</span>';

            $amountClass = $trans['amount'] >= 0 ? 'positive' : 'negative';
            $amount      = number_format($trans['amount']);
            $desc        = htmlspecialchars($trans['description'] ?? '-');
            $date        = date('Y/m/d H:i', strtotime($trans['created_at']));
            $id          = (int) $trans['id'];
            $floatAmount = (float) $trans['amount'];

            $html .= <<<HTML
            <tr class="animate-row">
                <td>{$rowNum}</td>
                <td>{$userName}</td>
                <td><span class="amount-badge {$amountClass}">{$amount} تومان</span></td>
                <td>{$desc}</td>
                <td>{$date}</td>
                <td>
                    <div class="action-btns">
                        <button class="action-btn btn-edit" title="ویرایش مبلغ"
                            onclick="editTransaction({id:{$id},amount:{$floatAmount}})">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </button>
                        <button class="action-btn btn-delete" title="حذف"
                            onclick="deleteTransaction({$id})">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
            HTML;
        }

        return $html;
    }

    private function renderPagination(int $current, int $total, int $totalRecords): string {
        if ($total <= 1) return '';

        $html = '<div class="pagination">';

        for ($i = 1; $i <= $total; $i++) {
            $active = $i === $current ? 'active' : '';
            $html  .= "<button class=\"page-btn {$active}\" onclick=\"loadTransactions({$i})\">{$i}</button>";
        }

        $html .= '</div>';
        return $html;
    }

    public function store(): void {
        requireLogin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'user_id'     => !empty($_POST['user_id']) ? intval($_POST['user_id']) : null,
                'amount'      => floatval($_POST['amount']),
                'description' => htmlspecialchars(trim($_POST['description'] ?? '')),
            ];
            echo json_encode(['success' => $this->transactionModel->create($data)]);
        }
        exit;
    }

    public function update(): void {
        requireLogin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id     = intval($_POST['id']);
            $amount = floatval($_POST['amount']);
            echo json_encode(['success' => $this->transactionModel->update($id, $amount)]);
        }
        exit;
    }

    public function delete(): void {
        requireLogin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = intval($_POST['id']);
            echo json_encode(['success' => $this->transactionModel->delete($id)]);
        }
        exit;
    }
}
