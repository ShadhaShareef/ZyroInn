<?php
$booking = $booking ?? [];
$addons = $addons ?? [];
$payments = $payments ?? [];
$booking = array_merge([
  'id' => 0, 'property_name' => '', 'address' => '',
  'city' => '', 'state' => '', 'country' => '',
  'room_number' => '', 'room_type' => '',
  'check_in_date' => '', 'check_out_date' => '',
  'base_rate' => 0, 'first_name' => '', 'last_name' => '',
], $booking);
$nights = max(1, (strtotime($booking['check_out_date']) - strtotime($booking['check_in_date'])) / 86400);
$roomTotal = $nights * (float)$booking['base_rate'];
$addonTotal = array_sum(array_map(fn($a) => (float)$a['price'] * (int)$a['quantity'], $addons));
$grandTotal = $roomTotal + $addonTotal;
$paidAmount = !empty($payments) ? (float)$payments[0]['amount'] : 0;
$txnRef = !empty($payments) ? $payments[0]['transaction_reference'] : '--';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>E-Bill - ZyroInn</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Poppins:wght@600;700&display=swap');
    *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
    body { font-family:'Inter',sans-serif; color:#1a1a2e; background:#fff; padding:2rem; line-height:1.5; }
    .bill-container { max-width:720px; margin:0 auto; }
    .bill-header { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:2rem; padding-bottom:1.5rem; border-bottom:2px solid #6C2BD9; }
    .bill-title { font-family:'Poppins',sans-serif; font-size:1.5rem; font-weight:700; color:#6C2BD9; }
    .bill-meta { font-size:0.75rem; color:#888; margin-top:0.25rem; }
    .bill-ref { font-size:0.875rem; font-weight:700; color:#1a1a2e; margin-top:0.5rem; }
    .section { margin-bottom:1.5rem; }
    .section-title { font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:#888; margin-bottom:0.75rem; }
    .grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
    .label { font-size:0.6875rem; color:#888; }
    .value { font-size:0.875rem; font-weight:600; color:#1a1a2e; }
    table { width:100%; border-collapse:collapse; font-size:0.8125rem; }
    th { text-align:left; font-size:0.6875rem; text-transform:uppercase; letter-spacing:0.06em; color:#888; padding:0.5rem 0; border-bottom:1px solid #eee; }
    td { padding:0.625rem 0; border-bottom:1px solid #f0f0f0; }
    .text-right { text-align:right; }
    .total-row td { font-weight:700; color:#1a1a2e; padding-top:0.75rem; border-top:2px solid #6C2BD9; }
    .paid-badge { display:inline-block; font-size:0.6875rem; font-weight:700; color:#16a34a; background:#f0fdf4; padding:0.125rem 0.625rem; border-radius:999px; }
    .print-btn { display:block; width:100%; padding:0.75rem; margin-top:2rem; background:#6C2BD9; color:#fff; border:none; border-radius:6px; font-family:'Inter',sans-serif; font-size:0.875rem; font-weight:600; cursor:pointer; text-align:center; text-decoration:none; }
    .print-btn:hover { background:#5a22b5; }
    @media print { .print-btn { display:none; } body { padding:0; } .bill-header { border-bottom-color:#000; } .total-row td { border-top-color:#000; } }
  </style>
</head>
<body>
  <div class="bill-container">
    <div class="bill-header">
      <div>
        <div class="bill-title">ZyroInn</div>
        <div class="bill-meta">E-Bill / Invoice</div>
        <div class="bill-ref">#BKG-<?= str_pad($booking['id'], 5, '0', STR_PAD_LEFT) ?></div>
      </div>
      <div class="text-right">
        <div class="value"><?= htmlspecialchars($booking['property_name']) ?></div>
        <div class="bill-meta"><?= date('F d, Y') ?></div>
      </div>
    </div>

    <div class="section">
      <div class="section-title">Guest &amp; Stay Details</div>
      <div class="grid-2">
        <div>
          <div class="label">Guest Name</div>
          <div class="value"><?= htmlspecialchars(($booking['first_name'] ?? '') . ' ' . ($booking['last_name'] ?? '')) ?></div>
        </div>
        <div>
          <div class="label">Room</div>
          <div class="value">Room <?= htmlspecialchars($booking['room_number']) ?> (<?= htmlspecialchars($booking['room_type']) ?>)</div>
        </div>
        <div>
          <div class="label">Check-In</div>
          <div class="value"><?= htmlspecialchars($booking['check_in_date']) ?></div>
        </div>
        <div>
          <div class="label">Check-Out</div>
          <div class="value"><?= htmlspecialchars($booking['check_out_date']) ?></div>
        </div>
        <div>
          <div class="label">Nights</div>
          <div class="value"><?= $nights ?></div>
        </div>
        <div>
          <div class="label">Address</div>
          <div class="value"><?= htmlspecialchars($booking['address'] . ', ' . $booking['city'] . ', ' . $booking['state']) ?></div>
        </div>
      </div>
    </div>

    <div class="section">
      <div class="section-title">Charges</div>
      <table>
        <thead>
          <tr><th>Item</th><th class="text-right">Qty</th><th class="text-right">Rate</th><th class="text-right">Amount</th></tr>
        </thead>
        <tbody>
          <tr>
            <td><?= htmlspecialchars($booking['room_type']) ?> - Room Charge</td>
            <td class="text-right"><?= $nights ?> night(s)</td>
            <td class="text-right">$<?= number_format($booking['base_rate'], 2) ?></td>
            <td class="text-right">$<?= number_format($roomTotal, 2) ?></td>
          </tr>
          <?php foreach ($addons as $addon): ?>
          <tr>
            <td><?= htmlspecialchars($addon['name']) ?></td>
            <td class="text-right">x<?= (int)$addon['quantity'] ?></td>
            <td class="text-right">$<?= number_format($addon['price'], 2) ?></td>
            <td class="text-right">$<?= number_format((float)$addon['price'] * (int)$addon['quantity'], 2) ?></td>
          </tr>
          <?php endforeach; ?>
          <tr class="total-row">
            <td colspan="3" class="text-right">Total</td>
            <td class="text-right">$<?= number_format($grandTotal, 2) ?></td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="section">
      <div class="section-title">Payment</div>
      <div style="display:flex; align-items:center; gap:1rem;">
        <div>
          <div class="label">Transaction Reference</div>
          <div class="value"><?= htmlspecialchars($txnRef) ?></div>
        </div>
        <span class="paid-badge">Paid</span>
      </div>
    </div>

    <button class="print-btn" onclick="window.print()">Print / Save as PDF</button>
  </div>
</body>
</html>
