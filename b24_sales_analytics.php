<?php
require_once __DIR__ . '/app/includes/header.php';
require_once __DIR__ . '/app/includes/audit.php';
require_once __DIR__ . '/app/includes/organic_gate.php';
require_once __DIR__ . '/app/includes/auth.php';
$u = auth_current_user();
$operator = is_array($u) ? ($u['display_name'] ?? 'system') : 'system';
$totalRevenue = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM sales")->fetchColumn();
$orderCount = (int)$db->query("SELECT COUNT(*) FROM sales")->fetchColumn();
$avgOrder = $orderCount > 0 ? $totalRevenue / $orderCount : 0;
$monthRevenue = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM sales WHERE substr(sale_date,1,7)='" . date('Y-m') . "'")->fetchColumn();
$income = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM financial_transactions WHERE transaction_type='INCOME'")->fetchColumn();
$expense = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM financial_transactions WHERE transaction_type='EXPENSE'")->fetchColumn();
$result = $income - $expense;
$topBatches = $db->query("SELECT pb.batch_code, pb.crop_type, COUNT(s.id) AS orders, COALESCE(SUM(s.amount),0) AS revenue FROM sales s JOIN production_batches pb ON pb.id=s.batch_id GROUP BY s.batch_id ORDER BY revenue DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
$reports = [
 ['file' => 'report_batch_yield.php', 'label' => __('b24_rep_yield')],
 ['file' => 'report_monthly_revenue.php', 'label' => __('b24_rep_month')],
 ['file' => 'report_top_customers.php', 'label' => __('b24_rep_topcust')],
 ['file' => 'report_top_products.php', 'label' => __('b24_rep_topprod')],
 ['file' => 'report_product_profit.php', 'label' => __('b24_rep_profit')],
 ['file' => 'report_inventory_value.php', 'label' => __('b24_rep_inv')],
];
?>
<div style="padding:24px">
    <div style="background:linear-gradient(135deg,#0f766e,#16a34a);color:#fff;border-radius:18px;padding:24px;margin-bottom:20px">
        <h1 style="margin:0"><?php echo htmlspecialchars(__('b24_title')); ?></h1>
        <p style="margin:6px 0 0;opacity:.9"><?php echo htmlspecialchars(__('b24_subtitle')); ?></p>
    </div>
    <div style="display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap">
        <div style="flex:1;min-width:140px;background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:16px"><div style="font-size:26px;font-weight:800;color:#16a34a">€ <?php echo number_format($totalRevenue,2); ?></div><div><?php echo htmlspecialchars(__('b24_revenue')); ?></div></div>
        <div style="flex:1;min-width:140px;background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:16px"><div style="font-size:26px;font-weight:800"><?php echo $orderCount; ?></div><div><?php echo htmlspecialchars(__('b24_orders')); ?></div></div>
        <div style="flex:1;min-width:140px;background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:16px"><div style="font-size:26px;font-weight:800">€ <?php echo number_format($avgOrder,2); ?></div><div><?php echo htmlspecialchars(__('b24_avg')); ?></div></div>
        <div style="flex:1;min-width:140px;background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:16px"><div style="font-size:26px;font-weight:800;color:#0f766e">€ <?php echo number_format($monthRevenue,2); ?></div><div><?php echo htmlspecialchars(__('b24_this_month')); ?></div></div>
    </div>
    <div style="display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap">
        <div style="flex:1;min-width:140px;background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:16px"><div style="font-size:22px;font-weight:800;color:#16a34a">€ <?php echo number_format($income,2); ?></div><div><?php echo htmlspecialchars(__('b24_income')); ?></div></div>
        <div style="flex:1;min-width:140px;background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:16px"><div style="font-size:22px;font-weight:800;color:#dc2626">€ <?php echo number_format($expense,2); ?></div><div><?php echo htmlspecialchars(__('b24_expense')); ?></div></div>
        <div style="flex:1;min-width:140px;background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:16px"><div style="font-size:22px;font-weight:800;color:<?php echo $result>=0?'#0f766e':'#dc2626'; ?>">€ <?php echo number_format($result,2); ?></div><div><?php echo htmlspecialchars(__('b24_result')); ?></div></div>
    </div>
    <h3 style="color:#374151"><?php echo htmlspecialchars(__('b24_top_batches')); ?></h3>
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:8px 16px;margin-bottom:20px">
        <table style="width:100%;border-collapse:collapse">
            <thead><tr style="text-align:left;color:#6b7280"><th style="padding:10px"><?php echo htmlspecialchars(__('b24_batch')); ?></th><th style="padding:10px"><?php echo htmlspecialchars(__('b24_crop')); ?></th><th style="padding:10px"><?php echo htmlspecialchars(__('b24_orders')); ?></th><th style="padding:10px"><?php echo htmlspecialchars(__('b24_revenue')); ?></th></tr></thead>
            <tbody>
            <?php if (empty($topBatches)): ?>
                <tr><td colspan="4" style="padding:16px;color:#6b7280"><?php echo htmlspecialchars(__('b24_no_data')); ?></td></tr>
            <?php else: foreach ($topBatches as $b): ?>
                <tr style="border-top:1px solid #e5e7eb">
                    <td style="padding:10px"><strong><?php echo htmlspecialchars($b['batch_code']); ?></strong></td>
                    <td style="padding:10px;color:#6b7280"><?php echo htmlspecialchars($b['crop_type']); ?></td>
                    <td style="padding:10px"><?php echo (int)$b['orders']; ?></td>
                    <td style="padding:10px;font-weight:700;color:#16a34a">€ <?php echo number_format((float)$b['revenue'],2); ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <h3 style="color:#374151"><?php echo htmlspecialchars(__('b24_reports')); ?></h3>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
        <?php foreach ($reports as $r): ?>
            <a href="<?php echo htmlspecialchars($r['file']); ?>" style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:14px 18px;text-decoration:none;color:#0f766e;font-weight:800"><?php echo htmlspecialchars($r['label']); ?></a>
        <?php endforeach; ?>
    </div>
</div>
<?php require_once __DIR__ . '/app/includes/footer.php'; ?>
