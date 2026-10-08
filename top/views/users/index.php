<?php
// views/users/index.php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="main-content">
    <div class="topbar">
        <button class="hamburger" id="hamburger">
            <i class="fa-solid fa-bars"></i>
        </button>
        <div class="page-title">
            <h2>مدیریت کاربران</h2>
            <span>
                مجموع:
                <strong id="usersCount"><?= $total ?? 0 ?></strong>کاربر
            </span>
        </div>
        <div class="topbar-actions">
            <button class="btn btn-primary" onclick="openModal('addUserModal')">
                <i class="fa-solid fa-plus"></i>
                افزودن کاربر
            </button></div>
    </div>

    <div class="content-area">
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text"
                   id="searchInput"
                   placeholder="جستجو نام یا شماره order..."
                   value="<?= htmlspecialchars($search ?? '') ?>">
        </div><div class="table-card glass">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>نام</th>
                            <th>مبلغ</th>
                            <th>تاریخ ثبت</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody">
                        <?php if (empty($users)): ?>
                            <tr><td colspan="6" class="empty-state">کاربری یافت نشد</td></tr>
                        <?php else: ?>
                            <?php foreach ($users as $i => $user): ?>
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
                                        <button class="action-btn btn-edit" title="ویرایش"onclick="editUser(<?= htmlspecialchars(json_encode([
                                                'id' => $user['id'],
                                                'name' => $user['name'],
                                                'order_number' => $user['order_number'],
                                                'amount' => (float)$user['amount'],
                                                'fetch_url_sub' => $user['fetch_url_sub'] ?? '',
                                                'use_fetch' => $user['use_fetch'] ?? 0
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
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if (($totalPages ?? 1) > 1): ?>
            <div class="pagination" id="paginationContainer">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <button class="page-btn <?= $i === ($page ?? 1) ? 'active' : '' ?>"
                            onclick="loadUsers(<?= $i ?>)">
                        <?= $i ?>
                    </button>
                <?php endfor; ?>
            </div>
            <?php else: ?>
            <div class="pagination" id="paginationContainer"></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal-overlay" id="addUserModal">
    <div class="modal glass">
        <div class="modal-header">
            <h3><i class="fa-solid fa-user-plus"></i> افزودن کاربر</h3>
            <button class="modal-close" onclick="closeModal('addUserModal')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="addUserForm">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>نام کاربر</label>
                        <input type="text" name="name" required placeholder="نام کامل">
                    </div>
                    <div class="form-group">
                        <label>شماره Order</label>
                        <input type="text" name="order_number" placeholder="ORD-001" value="0">
                    </div>
                </div>
                <div class="form-group">
                    <label>مبلغ (تومان)</label>
                    <input type="number" name="amount" required placeholder="0" value="800000">
                </div>
                <div class="form-group">
                    <label>کد اشتراک <small>(اختیاری)</small></label>
                    <textarea name="subscription_code" rows="5" placeholder="در صورت خالی بودن از دیفالت استفاده می‌شود"></textarea>
                </div>
                <div class="form-group">
                    <label>URL اشتراک برای Fetch <small>(اختیاری)</small></label>
                    <input type="text" name="fetch_url_sub" placeholder="https://...">
                </div>
                <label class="custom-toggle">
                    <input type="checkbox" name="use_fetch" value="1" checked>
                    <span class="toggle-slider"></span>
                    <span class="toggle-label-text">استفاده از Fetch URL به جای کد دستی</span>
                </label>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('addUserModal')">انصراف</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> ذخیره
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal-overlay" id="editUserModal">
    <div class="modal glass">
        <div class="modal-header">
            <h3><i class="fa-solid fa-pen-to-square"></i> ویرایش کاربر</h3>
            <button class="modal-close" onclick="closeModal('editUserModal')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="editUserForm">
            <input type="hidden" name="id" id="editUserId">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>نام کاربر</label>
                        <input type="text" name="name" id="editUserName" required>
                    </div>
                    <div class="form-group">
                        <label>شماره Order</label>
                        <input type="text" name="order_number" id="editUserOrder">
                    </div>
                </div>
                <div class="form-group">
                    <label>مبلغ (تومان)</label>
                    <input type="number" name="amount" id="editUserAmount" required>
                </div>
                <div class="form-group">
                    <label>کد اشتراک</label>
                    <textarea name="subscription_code" id="editUserSub" rows="5"></textarea>
                </div>
                <div class="form-group">
                    <label>URL اشتراک برای Fetch</label>
                    <input type="text" name="fetch_url_sub" id="editUserFetchUrl">
                </div>
                <div class="form-group">
                    <label class="custom-toggle">
                        <input type="checkbox" name="use_fetch" id="editUserUseFetch" value="1">
                        <span class="toggle-slider"></span>
                        <span class="toggle-label-text">استفاده از Fetch URL</span>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('editUserModal')">انصراف</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-check"></i> بروزرسانی
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
