<?php
// dashboard.php

session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/controllers/DashboardController.php';

requireLogin();

$ctrl    = new DashboardController();
$kpis    = $ctrl->getKPIs();
$monthly = $ctrl->getUsersPerMonth();
$ie      = $ctrl->getIncomeVsExpense();
$expCat  = $ctrl->getExpenseByCategory();
$maslow  = $ctrl->getMaslowDistribution();
$recent  = $ctrl->getRecentTransactions(8);

// Prepare JS data
$userLabels   = json_encode(array_column($monthly, 'month_label'));
$userData     = json_encode(array_map('intval', array_column($monthly, 'total')));
$catLabels    = json_encode(array_column($expCat, 'category_name'));
$catData      = json_encode(array_map('floatval', array_column($expCat, 'total')));
$maslowLabels = json_encode(array_column($maslow, 'name_en'));
$maslowData   = json_encode(array_map('floatval', array_column($maslow, 'total')));

$pageTitle  = 'Dashboard';
$breadcrumb = 'Home';
$currentPage = 'dashboard';

ob_start();
?>

<!-- KPI Cards -->
<div class="kpi-grid">
  <div class="kpi-card gold">
    <div class="kpi-header">
      <span class="kpi-label">Total Transactions</span>
      <div class="kpi-icon">
        <span class="material-symbols-rounded">receipt_long</span>
      </div>
    </div>
    <div class="kpi-value"><?= number_format($kpis['total_transactions']) ?></div>
    <div class="kpi-sub">All time records</div>
  </div>

  <div class="kpi-card green">
    <div class="kpi-header">
      <span class="kpi-label">Total Income</span>
      <div class="kpi-icon">
        <span class="material-symbols-rounded" style="font-size: 16px;">trending_up</span>
      </div>
    </div>
    <div class="kpi-value">฿<?= number_format($kpis['total_income'], 0) ?></div>
    <div class="kpi-sub">Cumulative income</div>
  </div>

  <div class="kpi-card red">
    <div class="kpi-header">
      <span class="kpi-label">Total Expense</span>
      <div class="kpi-icon">
        <span class="material-symbols-rounded" style="font-size: 16px;">trending_down</span>
      </div>
    </div>
    <div class="kpi-value">฿<?= number_format($kpis['total_expense'], 0) ?></div>
    <div class="kpi-sub">Cumulative expense</div>
  </div>

  <div class="kpi-card purple">
    <div class="kpi-header">
      <span class="kpi-label">Net Savings</span>
      <div class="kpi-icon">
        <span class="material-symbols-rounded">savings</span>
      </div>
    </div>
    <div class="kpi-value" style="color:<?= $kpis['net_savings'] >= 0 ? 'var(--green-400)' : 'var(--red-400)' ?>">
      <?= ($kpis['net_savings'] >= 0 ? '+' : '') ?>฿<?= number_format(abs($kpis['net_savings']), 0) ?>
    </div>
    <div class="kpi-sub">Income minus Expense</div>
  </div>
</div>


<div class="chart-grid cols-2" style="margin-bottom:18px;">
  <!-- <div class="chart-card">
    <div class="chart-card-header">
      <h3> User Growth (Last 12 Months)</h3>
      <span class="badge-pill">Monthly</span>
    </div>
    <div class="chart-container" style="height:240px;">
      <canvas id="usersLineChart"></canvas>
    </div>
  </div> -->

  <div class="chart-card" style="grid-column: span 2;">
    <div class="chart-card-header">
      <h3>Income vs Expense</h3>
      <span class="badge-pill">All Time</span>
    </div>
    <div class="chart-container" style="height:240px;">
      <canvas id="incomeExpensePie"></canvas>
    </div>
  </div>
</div>

<!-- Charts Row 2 -->
<div class="chart-grid cols-2" style="margin-bottom:18px;">
  <div class="chart-card" style="grid-column: span 2;">
    <div class="chart-card-header">
      <h3>Expense by Category</h3>
      <span class="badge-pill">Breakdown</span>
    </div>
    <div class="chart-container" style="height:260px;">
      <canvas id="expenseCategoryBar"></canvas>
    </div>
  </div>

  <div class="chart-card" style="grid-column: span 2;">
    <div class="chart-card-header">
      <h3>Maslow Financial Behavior</h3>
      <span class="badge-pill">Need Hierarchy</span>
    </div>
    <div class="chart-container" style="height:260px;">
      <canvas id="maslowDoughnut"></canvas>
    </div>
  </div>
</div>

<!-- Recent Transactions Table -->
<!-- <div class="table-card">
  <div class="table-card-header">
    <h3>🕐 Recent Transactions</h3>
    <a href="reports.php" class="btn btn-ghost btn-sm">View Reports →</a>
  </div>
  <?php if (empty($recent)): ?>
  <div class="empty-state">
    <div class="empty-icon">📭</div>
    <p>No transactions found yet</p>
  </div>
  <?php else: ?>
  <div style="overflow-x:auto;">
    <table class="data-table" id="recentTable">
      <thead>
        <tr>
          <th>Date</th>
          <th>User</th>
          <th>Description</th>
          <th>Category</th>
          <th>Type</th>
          <th style="text-align:right;">Amount</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($recent as $tx): ?>
        <tr>
          <td><?= date('d M Y', strtotime($tx['transaction_date'])) ?></td>
          <td class="td-main"><?= htmlspecialchars($tx['first_name'] . ' ' . $tx['last_name']) ?></td>
          <td><?= htmlspecialchars($tx['transaction_name'] ?? '—') ?></td>
          <td><?= htmlspecialchars($tx['category_name']) ?></td>
          <td>
            <span class="badge badge-<?= $tx['category_type'] ?>">
              <?= $tx['category_type'] === 'income' ? '↑ Income' : '↓ Expense' ?>
            </span>
          </td>
          <td style="text-align:right;" class="td-main">
            <span style="color:<?= $tx['category_type'] === 'income' ? 'var(--green-400)' : 'var(--red-400)' ?>">
              <?= $tx['category_type'] === 'income' ? '+' : '-' ?>฿<?= number_format($tx['amount'], 2) ?>
            </span>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div> -->

<?php
$content = ob_get_clean();

$inlineScript = <<<JS
document.addEventListener('DOMContentLoaded', () => {
  buildUsersLineChart('usersLineChart',   {$userLabels},   {$userData});
  buildIncomeExpensePie('incomeExpensePie', {$ie['income']}, {$ie['expense']});
  buildExpenseBarChart('expenseCategoryBar', {$catLabels}, {$catData});
  buildMaslowChart('maslowDoughnut', {$maslowLabels}, {$maslowData});
});
JS;

require __DIR__ . '/views/header.php';
echo $content;
require __DIR__ . '/views/footer.php';