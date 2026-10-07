<?php
// controllers/UserController.php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Transaction.php';

class UserController {
    private $userModel;
    private $transactionModel;

    public function __construct() {
        $this->userModel = new User();
        $this->transactionModel = new Transaction();
    }

    public function index() {
        requireLogin();

        $page    = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
        $perPage = 20;
        $offset  = ($page - 1) * $perPage;
        $search  = trim($_GET['search'] ?? '');

        $total      = $this->userModel->countAll($search);
        $users      = $this->userModel->getAll($search, $perPage, $offset);
        $totalPages = (int)ceil($total / $perPage);

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            header('Content-Type: application/json');
            echo json_encode([
                'success'    => true,
                'html'       => $this->renderTableRows($users),
                'pagination' => $this->renderPagination($page, $totalPages, $search),
                'total'      => $total,
            ]);
            exit;
        }

        extract(compact('users', 'page', 'totalPages', 'search', 'total'));
        require_once __DIR__ . '/../views/users/index.php';
    }

    public function store() {
        requireLogin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name'              => htmlspecialchars(trim($_POST['name'])),
                'amount'            => floatval($_POST['amount']),
                'subscription_code' => trim($_POST['subscription_code']),
                'order_number'      => htmlspecialchars(trim($_POST['order_number'])),
                'fetch_url_sub'     => trim($_POST['fetch_url_sub'] ?? ''),
                'use_fetch'         => isset($_POST['use_fetch']) ? 1 : 0,
            ];

            $newUserId = $this->userModel->create($data);

            if ($newUserId) {
                $this->transactionModel->create([
                    'user_id'     => $newUserId,
                    'amount'      => $data['amount'],
                    'description' => 'User registered: ' . $data['name'],]);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'user_create_failed']);
            }
        }
        exit;
    }

    public function update() {
        requireLogin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id   = intval($_POST['id']);
            $data = [
                'name'              => htmlspecialchars(trim($_POST['name'])),
                'amount'            => floatval($_POST['amount']),
                'subscription_code' => trim($_POST['subscription_code']),
                'order_number'      => htmlspecialchars(trim($_POST['order_number'])),
                'fetch_url_sub'     => trim($_POST['fetch_url_sub'] ?? ''),
                'use_fetch'         => isset($_POST['use_fetch']) ? 1 : 0,
            ];
            echo json_encode(['success' => $this->userModel->update($id, $data)]);
        }
        exit;
    }


    public function delete() {
        requireLogin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = intval($_POST['id']);
            echo json_encode(['success' => $this->userModel->delete($id)]);
        }
        exit;
    }

    public function get() {
        requireLogin();
        $id   = intval($_GET['id']);
        $user = $this->userModel->getById($id);
        if ($user) {
            $user['subscription_code'] = base64_decode($user['subscription_code']);
            echo json_encode(['success' => true, 'user' => $user]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit;
    }

    // ─── Private Helpers ────────────────────────────────────────────────────────

    private function renderTableRows(array $users): string {
        if (empty($users)) {
            return '<tr><td colspan="6" class="empty-state">کاربری یافت نشد</td></tr>';
        }

        ob_start();
        foreach ($users as $i => $user):
            $sub_decoded = !empty($user['subscription_code'])
                ? base64_decode($user['subscription_code'])
                : '';
        ?>
        <tr class="animate-row">
            <td><?= $i + 1 ?></td>
            <td>
                <div class="user-cell">
                    <div class="user-avatar-sm"><?= mb_substr($user['name'], 0, 1) ?></div>
                    <?= htmlspecialchars($user['name']) ?>
                </div>
            </td>
            <td><?= number_format($user['amount']) ?> تومان</td>
            <td><?= date('Y/m/d', strtotime($user['created_at'])) ?></td>
            <td>
                <div class="action-btns">
                    <button class="action-btn btn-edit" title="ویرایش"
                        onclick="editUser(<?= htmlspecialchars(json_encode([
                            'id'           => $user['id'],
                            'name'         => $user['name'],
                            'order_number' => $user['order_number'],
                            'amount'       => (float)$user['amount'],
                        ]), ENT_QUOTES) ?>)">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </button>
                    <button class="action-btn btn-link" title="لینک اشتراک"
                        onclick="copySubLink('<?= htmlspecialchars(BASE_URL) ?>/sub/index.php?hash=<?= $user['hash'] ?>')">
                        <i class="fa-solid fa-link"></i>
                    </button>
                    <button class="action-btn btn-delete" title="حذف"
                        onclick="deleteUser(<?= $user['id'] ?>)">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
        <?php
        endforeach;
        return ob_get_clean();
    }

    private function renderPagination(int $current, int $total, string $search): string {
        if ($total <= 1) return '';

        ob_start();
        for ($i = 1; $i <= $total; $i++) {
            $active = $i === $current ? 'active' : '';
            echo "<button class='page-btn {$active}' onclick=\"loadUsers({$i})\">{$i}</button>";
        }
        return ob_get_clean();
    }
}
