<?php
// views/accounting/index.php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="main-content">
    <div class="topbar">
        <button class="hamburger" id="hamburger">
            <i class="fa-solid fa-bars"></i>
        </button>
        <div class="page-title">
            <h2>حسابداری</h2>
             <span>تراکنش ها: <span id="transactionsCount"><?= $total ?></span></span>
        </div>
        <button class="btn btn-primary" onclick="openModal('addTransactionModal')">
            <i class="fa-solid fa-plus"></i>
            ثبت تراکنش
        </button>
    </div>

    <div class="content-area">
        <div class="revenue-cards">
            <div class="revenue-card glass animate-in" style="--delay:0.1s">
                <div class="revenue-icon icon-purple">
                    <i class="fa-solid fa-calendar-days"></i>
                </div>
                <div class="revenue-info">
                    <span class="revenue-label">درآمد ماه جاری</span>
                    <span class="revenue-value"><?= number_format($monthly_revenue) ?> <small>تومان</small></span>
                </div>
                <div class="stat-glow glow-purple"></div>
            </div>

            <div class="revenue-card glass animate-in" style="--delay:0.2s">
                <div class="revenue-icon icon-green">
                    <i class="fa-solid fa-chart-pie"></i>
                </div>
                <div class="revenue-info">
                    <span class="revenue-label">درآمد کل</span>
                    <span class="revenue-value"><?= number_format($total_revenue) ?> <small>تومان</small></span>
                </div>
                <div class="stat-glow glow-green"></div>
            </div>
        </div>

        <div class="table-card glass">
            <div class="table-header">
                <h3>لیست تراکنش‌ها</h3>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>کاربر</th>
                            <th>مبلغ</th>
                            <th>توضیحات</th>
                            <th>تاریخ</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody id="transactionsTableBody">
                    <?php if (empty($transactions)): ?>
                        <tr><td colspan="6" class="empty-state">تراکنشی یافت نشد</td></tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $i => $trans): ?>
                        <tr class="animate-row">
                            <td><?= $i + 1 ?></td>
                            <td>
                                <?php if ($trans['user_name']): ?>
                                    <div class="user-cell">
                                        <div class="user-avatar-sm"><?= mb_substr($trans['user_name'], 0, 1) ?></div>
                                        <?= htmlspecialchars($trans['user_name']) ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">تراکنش مستقل</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="amount-badge <?= $trans['amount'] >= 0 ? 'positive' : 'negative' ?>">
                                    <?= number_format($trans['amount']) ?> تومان
                                </span>
                            </td>
                            <td><?= htmlspecialchars($trans['description'] ?? '-') ?></td>
                            <td><?= date('Y/m/d H:i', strtotime($trans['created_at'])) ?></td>
                            <td>
                                <div class="action-btns">
                                <button class="action-btn btn-edit" title="ویرایش مبلغ"
                                onclick="editTransaction({
                                    id: <?= $trans['id'] ?>,
                                    amount: <?= (float)$trans['amount'] ?>
                                })">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>

                                    <button class="action-btn btn-delete" title="حذف"
                                        onclick="deleteTransaction(<?= $trans['id'] ?>)">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div  class="pagination" id="transactionsPagination">
                <?php
                $totalPages = (int) ceil($total / 15); 
                if ($totalPages > 1):
                    for ($i = 1; $i <= $totalPages; $i++):
                        $active = $i === $page ? 'active' : '';
                ?>
                    <button class="page-btn <?= $active ?>" onclick="loadTransactions(<?= $i ?>)"><?= $i ?></button>
                <?php
                    endfor;
                endif;
                ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Transaction Modal -->
<div class="modal-overlay" id="addTransactionModal">
    <div class="modal glass">
        <div class="modal-header">
            <h3><i class="fa-solid fa-plus"></i> ثبت تراکنش</h3>
            <button class="modal-close" onclick="closeModal('addTransactionModal')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="addTransactionForm">
            <div class="modal-body">
                <div class="form-group">
                    <label>کاربر <small>(اختیاری)</small></label>
                    <select name="user_id">
                        <option value="">تراکنش مستقل</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>مبلغ (تومان)</label>
                    <input type="number" name="amount" required placeholder="0">
                </div>
                <div class="form-group">
                    <label>توضیحات</label>
                    <textarea name="description" rows="3" placeholder="توضیحات تراکنش..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('addTransactionModal')">انصراف</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-check"></i> ذخیره
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Transaction Modal -->
<div class="modal-overlay" id="editTransactionModal">
    <div class="modal glass modal-sm">
        <div class="modal-header">
            <h3><i class="fa-solid fa-pen-to-square"></i> ویرایش مبلغ</h3>
            <button class="modal-close" onclick="closeModal('editTransactionModal')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="editTransactionForm">
            <input type="hidden" name="id" id="editTransactionId">
            <div class="modal-body">
                <div class="form-group">
                    <label>مبلغ جدید (تومان)</label>
                    <input type="number" name="amount" id="editTransactionAmount" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('editTransactionModal')">انصراف</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-check"></i> بروزرسانی
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
