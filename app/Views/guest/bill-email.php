<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family:Helvetica,Arial,sans-serif; color:#1a1a2e; padding:24px;">
  <div style="max-width:600px; margin:0 auto;">
    <div style="border-bottom:2px solid #6C2BD9; padding-bottom:16px; margin-bottom:24px;">
      <h1 style="font-size:22px; color:#6C2BD9; margin:0;">ZyroInn</h1>
      <p style="font-size:12px; color:#888; margin:4px 0 0;">E-Bill / Invoice</p>
      <p style="font-size:14px; font-weight:700; margin:8px 0 0;">#BKG-<?= str_pad($booking['id'], 5, '0', STR_PAD_LEFT) ?></p>
    </div>
    <table style="width:100%; font-size:13px; border-collapse:collapse;">
      <tr><td style="color:#888; width:120px; padding:4px 0;">Property</td><td style="font-weight:600;"><?= htmlspecialchars($booking['property_name']) ?></td></tr>
      <tr><td style="color:#888; padding:4px 0;">Room</td><td style="font-weight:600;">Room <?= htmlspecialchars($booking['room_number']) ?> (<?= htmlspecialchars($booking['room_type']) ?>)</td></tr>
      <tr><td style="color:#888; padding:4px 0;">Check-In</td><td style="font-weight:600;"><?= htmlspecialchars($booking['check_in_date']) ?></td></tr>
      <tr><td style="color:#888; padding:4px 0;">Check-Out</td><td style="font-weight:600;"><?= htmlspecialchars($booking['check_out_date']) ?></td></tr>
      <tr><td style="color:#888; padding:4px 0;">Nights</td><td style="font-weight:600;"><?= $nights ?></td></tr>
    </table>
    <?php $fnbItems = $fnbItems ?? []; ?>
    <table style="width:100%; font-size:13px; border-collapse:collapse; margin-top:24px;">
      <thead>
        <tr style="border-bottom:1px solid #eee;">
          <th style="text-align:left; font-size:11px; color:#888; text-transform:uppercase; padding:8px 0;">Item</th>
          <th style="text-align:right; font-size:11px; color:#888; text-transform:uppercase; padding:8px 0;">Amount</th>
        </tr>
      </thead>
      <tbody>
        <tr><td style="padding:8px 0; border-bottom:1px solid #f0f0f0;"><?= htmlspecialchars($booking['room_type']) ?> (<?= $nights ?> nights)</td><td style="text-align:right; padding:8px 0; border-bottom:1px solid #f0f0f0;">$<?= number_format($roomTotal, 2) ?></td></tr>
        <?php foreach ($addons as $addon): ?>
        <tr><td style="padding:8px 0; border-bottom:1px solid #f0f0f0;"><?= htmlspecialchars($addon['name']) ?> (x<?= (int)$addon['quantity'] ?>)</td><td style="text-align:right; padding:8px 0; border-bottom:1px solid #f0f0f0;">$<?= number_format((float)$addon['price'] * (int)$addon['quantity'], 2) ?></td></tr>
        <?php endforeach; ?>
        <?php if (!empty($fnbItems)): ?>
        <tr><td colspan="2" style="font-size:10px; color:#888; text-transform:uppercase; letter-spacing:0.06em; padding-top:12px;">Services &amp; F&B</td></tr>
        <?php foreach ($fnbItems as $fi): ?>
        <tr><td style="padding:8px 0; border-bottom:1px solid #f0f0f0;"><?= htmlspecialchars($fi['item_name']) ?> (x<?= (int)$fi['quantity'] ?>)</td><td style="text-align:right; padding:8px 0; border-bottom:1px solid #f0f0f0;">$<?= number_format((float)$fi['item_price'] * (int)$fi['quantity'], 2) ?></td></tr>
        <?php endforeach; ?>
        <?php endif; ?>
        <tr style="font-weight:700; color:#1a1a2e;">
          <td style="padding:10px 0; border-top:2px solid #6C2BD9;">Total</td>
          <td style="text-align:right; padding:10px 0; border-top:2px solid #6C2BD9;">$<?= number_format($grandTotal, 2) ?></td>
        </tr>
      </tbody>
    </table>
    <div style="margin-top:24px; padding-top:16px; border-top:1px solid #eee; font-size:12px; color:#888;">
      <p style="margin:0 0 4px;">Transaction: <?= htmlspecialchars($txnRef) ?></p>
      <p style="margin:0; color:#16a34a; font-weight:600;">Paid</p>
    </div>
  </div>
</body>
</html>
