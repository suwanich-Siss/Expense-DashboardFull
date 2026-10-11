<?php
// categories.php

session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/controllers/CategoryController.php';

requireLogin();

$ctrl = new CategoryController();

// Handle toggle status
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
  $ctrl->toggleStatus((int) $_GET['toggle']);
  header('Location: categories.php?msg=toggled');
  exit;
}

// Handle delete (Soft Delete — set is_active = 0)
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
  $result = $ctrl->delete((int) $_GET['delete']);
  $q = $result['success'] ? 'msg=deleted' : 'error=' . urlencode($result['message']);
  header("Location: categories.php?$q");
  exit;
}

// Handle Publish Update (bump category_version)
if (isset($_GET['publish'])) {
  $result = $ctrl->publishUpdate();
  header('Location: categories.php?msg=published&version=' . $result['version']);
  exit;
}

$categories = $ctrl->getAll(); // ดึงข้อมูลหมวดหมู่ทั้งหมด
$currentVersion = $ctrl->getCategoryVersion(); // ดึง category_version ปัจจุบัน
$pageTitle = 'Categories';
$breadcrumb = 'Home › Category Management';
$currentPage = 'categories';

$msg = $_GET['msg'] ?? ''; // ดึงข้อความแจ้งเตือนสำเร็จ (ถ้ามี)
$error = $_GET['error'] ?? ''; // ดึงข้อความแจ้งเตือนผิดพลาด (ถ้ามี)

ob_start();
?>

<!-- Flash messages -->
<?php if ($msg === 'created'): ?>
  <div class="alert alert-success" data-auto-dismiss="4000"> <span class="material-symbols-rounded"
      style="vertical-align: middle; font-size: 20px;">check_circle</span>
    Category created successfully</div>
<?php elseif ($msg === 'updated'): ?>
  <div class="alert alert-success" data-auto-dismiss="4000"> <span class="material-symbols-rounded"
      style="vertical-align: middle; font-size: 20px;">check_circle</span>
    Category updated successfully</div>
<?php elseif ($msg === 'deleted'): ?>
  <div class="alert alert-success" data-auto-dismiss="4000"> <span class="material-symbols-rounded"
      style="vertical-align: middle; font-size: 20px;">delete</span>
    Category deleted successfully</div>
<?php elseif ($msg === 'published'): ?>
  <div class="alert alert-success" data-auto-dismiss="5000"> <span class="material-symbols-rounded"
      style="vertical-align: middle; font-size: 20px;">publish</span>
    Published! Category version updated to <strong>v<?= (int)($_GET['version'] ?? 0) ?></strong> — Mobile apps will sync on next check</div>
<?php elseif ($msg === 'toggled'): ?>
  <div class="alert alert-info" data-auto-dismiss="3000"> <span class="material-symbols-rounded"
      style="vertical-align: middle; font-size: 20px;">sync</span>
    Category status updated</div>
<?php endif; ?>

<?php if ($error): ?>
  <div class="alert alert-danger" data-auto-dismiss="6000"> <span class="material-symbols-rounded"
      style="vertical-align: middle; font-size: 20px;">error</span> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="table-card">
  <div class="table-card-header">
    <h3>🏷️ All Categories (<?= count($categories) ?>)</h3>
    <div style="display:flex;gap:10px;align-items:center;">
      <div class="table-search">
        <input id="catSearch" type="text" placeholder="Search categories..." />
      </div>
      <!-- Version badge + Publish button -->
      <span style="font-size:.8rem;color:var(--gray-400);white-space:nowrap;">
        <span class="material-symbols-rounded" style="font-size:14px;vertical-align:middle;">label</span>
        v<?= $currentVersion ?>
      </span>
      <a href="categories.php?publish=1"
         class="btn btn-sm"
         style="background:var(--teal-400);color:#fff;display:inline-flex;align-items:center;gap:6px;white-space:nowrap;"
         onclick="return confirm('Publish category update? This will bump the version to v<?= $currentVersion + 1 ?> and trigger mobile sync.')">
        <span class="material-symbols-rounded" style="font-size:16px;">publish</span>
        Publish Update
      </a>
      <a href="add_category.php" class="btn btn-primary btn-sm">
        + Add Category
      </a>
    </div>
  </div>

  <?php if (empty($categories)): ?>
    <div class="empty-state">
      <div class="empty-icon">🏷️</div>
      <p>No categories found. <a href="add_category.php" style="color:var(--teal-400)">Add the first one</a>
      </p>
    </div>
  <?php else: ?>
    <div style="overflow-x:auto;">
      <table class="data-table" id="catTable">
        <thead>
          <tr>
            <th style="width:60px;">ID</th>
            <th>Category Name</th>
            <th>Type</th>
            <th>Created Date</th>
            <th style="text-align:center;">Status</th>
            <th style="text-align:center; width:140px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($categories as $cat): ?>
            <tr>
              <td style="color:var(--gray-500);font-size:.8rem;">#<?= $cat['category_id'] ?></td>
              <td class="td-main"><?= htmlspecialchars($cat['category_name']) ?></td>
              <td>
                <span class="badge badge-<?= $cat['category_type'] ?>"
                  style="display: inline-flex; align-items: center; gap: 4px;">
                  <?php if ($cat['category_type'] === 'income'): ?>
                    <span class="material-symbols-rounded" style="font-size: 16px;">trending_up</span> Income
                  <?php else: ?>
                    <span class="material-symbols-rounded" style="font-size: 16px;">trending_down</span> Expense
                  <?php endif; ?>
                </span>
              </td>
              <td><?= date('d M Y', strtotime($cat['created_at'])) ?></td>
              <td style="text-align:center;">
                <span class="badge <?= $cat['is_active'] ? 'badge-active' : 'badge-inactive' ?>"
                  style="display: inline-flex; align-items: center; gap: 4px;">
                  <span class="material-symbols-rounded" style="font-size: 14px;">
                    <?= $cat['is_active'] ? 'check_circle' : 'radio_button_unchecked' ?>
                  </span>
                  <?= $cat['is_active'] ? 'Active' : 'Inactive' ?>
                </span>
              </td>
              <td style="text-align:center;">
                <div class="action-btns" style="justify-content:center; display: flex; gap: 5px;">

                  <a href="edit_category.php?id=<?= $cat['category_id'] ?>" class="btn-icon edit" data-tooltip="Edit"
                    style="display: flex; align-items: center; justify-content: center;">
                    <span class="material-symbols-rounded" style="font-size: 20px;">edit</span>
                  </a>

                  <button class="btn-icon toggle" data-tooltip="<?= $cat['is_active'] ? 'Deactivate' : 'Activate' ?>"
                    onclick="window.location='categories.php?toggle=<?= $cat['category_id'] ?>'"
                    style="display: flex; align-items: center; justify-content: center;">
                    <span class="material-symbols-rounded" style="font-size: 20px;">
                      <?= $cat['is_active'] ? 'lock' : 'lock_open' ?>
                    </span>
                  </button>

                  <button class="btn-icon del" data-tooltip="Delete"
                    onclick="if(confirm('Delete category &quot; <?= htmlspecialchars($cat['category_name'], ENT_QUOTES) ?> &quot;? This cannot be undone.')) window.location.href='categories.php?delete=<?= $cat['category_id'] ?>'"
                    style="display: flex; align-items: center; justify-content: center;">
                    <span class="material-symbols-rounded" style="font-size: 20px;">delete</span>
                  </button>

                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
$inlineScript = "document.addEventListener('DOMContentLoaded',()=>initTableSearch('catSearch','catTable'));";

require __DIR__ . '/views/header.php';
echo $content;
require __DIR__ . '/views/footer.php';
