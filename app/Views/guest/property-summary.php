<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Property Summary — <?= htmlspecialchars($property['name'] ?? 'ZyroInn') ?></title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  html { font-size: 14px; }
  body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    color: #1e293b; background: #f8fafc; line-height: 1.6; padding: 2rem 1rem;
  }
  .container { max-width: 800px; margin: 0 auto; background: #fff; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; }
  .header { background: #1e293b; color: #fff; padding: 2rem 2.5rem; }
  .header h1 { font-size: 1.75rem; font-weight: 800; letter-spacing: -0.02em; }
  .header .sub { margin-top: 0.25rem; font-size: 0.85rem; color: #94a3b8; }
  .header .badge { display: inline-block; margin-top: 0.75rem; background: #334155; color: #e2e8f0; font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; padding: 0.3rem 0.75rem; border-radius: 999px; }
  .section { padding: 1.5rem 2.5rem; border-bottom: 1px solid #e2e8f0; }
  .section:last-of-type { border-bottom: none; }
  .section h2 { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #64748b; margin-bottom: 1rem; }
  .desc { font-size: 0.9rem; color: #475569; line-height: 1.7; }
  .amenity-category { margin-bottom: 1rem; }
  .amenity-category:last-child { margin-bottom: 0; }
  .amenity-category h3 { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #1e293b; margin-bottom: 0.5rem; }
  .amenity-list { display: flex; flex-wrap: wrap; gap: 0.4rem; }
  .amenity-pill { display: inline-flex; align-items: center; gap: 0.35rem; background: #f1f5f9; color: #334155; font-size: 0.75rem; font-weight: 600; padding: 0.3rem 0.75rem; border-radius: 999px; border: 1px solid #e2e8f0; }
  .amenity-pill .icon { font-size: 0.8rem; }
  .room-card { border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.25rem; margin-bottom: 1rem; }
  .room-card:last-child { margin-bottom: 0; }
  .room-card h3 { font-size: 1.05rem; font-weight: 700; color: #0f172a; }
  .room-card .meta { display: flex; flex-wrap: wrap; gap: 1rem; margin-top: 0.35rem; font-size: 0.8rem; color: #64748b; }
  .room-card .meta span { display: inline-flex; align-items: center; gap: 0.25rem; }
  .room-card .rate { margin-top: 0.5rem; font-size: 1.1rem; font-weight: 800; color: #0f172a; }
  .room-card .rate small { font-weight: 400; font-size: 0.75rem; color: #64748b; }
  .room-card .room-amenities { margin-top: 0.75rem; }
  .room-card .room-amenities .amenity-list .amenity-pill { background: #fff; border-color: #cbd5e1; font-size: 0.7rem; }
  .footer { padding: 1.25rem 2.5rem; text-align: center; font-size: 0.7rem; color: #94a3b8; border-top: 1px solid #e2e8f0; background: #f8fafc; }
  .footer a { color: #475569; text-decoration: underline; }
  .no-data { font-size: 0.8rem; color: #94a3b8; font-style: italic; }

  @media print {
    body { background: #fff; padding: 0; font-size: 12px; }
    .container { box-shadow: none; border-radius: 0; max-width: 100%; }
    .header { background: #1e293b !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .amenity-pill { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .room-card { break-inside: avoid; }
    .print-hide { display: none !important; }
  }
  @media (max-width: 600px) {
    body { padding: 0; }
    .container { border-radius: 0; }
    .header { padding: 1.25rem 1.25rem; }
    .section { padding: 1rem 1.25rem; }
  }
</style>
</head>
<body>
<div class="container">

  <!-- Header -->
  <div class="header">
    <h1><?= htmlspecialchars($property['name'] ?? '') ?></h1>
    <p class="sub"><?= htmlspecialchars(trim(($property['address'] ?? '') . ', ' . ($property['city'] ?? '') . ', ' . ($property['state'] ?? '') . ' ' . ($property['postal_code'] ?? ''))) ?></p>
    <span class="badge"><?= htmlspecialchars(ucfirst($property['property_type'] ?? 'property')) ?> &middot; Property Summary</span>
  </div>

  <!-- Description -->
  <div class="section">
    <h2>About This Property</h2>
    <p class="desc"><?= htmlspecialchars($property['description'] ?? 'No description available.') ?></p>
  </div>

  <!-- Property Amenities -->
  <div class="section">
    <h2>Amenities &amp; Services</h2>
    <?php
    $enabledAmenities = $property['enabled_amenities'] ?? [];
    if (!empty($enabledAmenities)):
    ?>
      <?php foreach ($enabledAmenities as $category => $items): ?>
        <?php
        $enabledItems = array_filter($items, fn($i) => !isset($i['enabled']) || $i['enabled']);
        if (empty($enabledItems)) continue;
        ?>
        <div class="amenity-category">
          <h3><?= htmlspecialchars($category) ?></h3>
          <div class="amenity-list">
            <?php foreach ($enabledItems as $item): ?>
              <span class="amenity-pill">
                <?php if (!empty($item['icon'])): ?><span class="icon"><?= htmlspecialchars($item['icon']) ?></span><?php endif; ?>
                <?= htmlspecialchars($item['label'] ?? '') ?>
              </span>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p class="no-data">No amenities registered for this property.</p>
    <?php endif; ?>
  </div>

  <!-- Room Types -->
  <div class="section">
    <h2>Rooms &amp; Suites</h2>
    <?php $rooms = $property['rooms'] ?? []; ?>
    <?php if (!empty($rooms)): ?>
      <?php foreach ($rooms as $room): ?>
        <div class="room-card">
          <h3>Room <?= htmlspecialchars($room['room_number'] ?? '') ?> — <?= htmlspecialchars($room['room_type'] ?? '') ?></h3>
          <div class="meta">
            <span><span style="display:inline-flex;align-items:center;justify-content:center;width:1.25rem;height:1.25rem;border-radius:9999px;background:#f1f5f9;color:#334155;font-weight:700;font-size:0.65rem;margin-right:0.25rem;">G</span> Up to <?= (int)($room['occupancy'] ?? 1) ?> guests</span>
            <span><span style="display:inline-flex;align-items:center;justify-content:center;width:1.25rem;height:1.25rem;border-radius:9999px;background:#f1f5f9;color:#334155;font-weight:700;font-size:0.65rem;margin-right:0.25rem;">B</span> <?= (int)($room['bed_count'] ?? 1) ?> bed<?= (int)($room['bed_count'] ?? 1) > 1 ? 's' : '' ?></span>
          </div>
          <div class="rate">$<?= number_format((float)($room['base_rate'] ?? 0), 2) ?> <small>/ night</small></div>
          <?php $roomAmenities = $room['enabled_amenities'] ?? []; ?>
          <?php if (!empty($roomAmenities)): ?>
            <div class="room-amenities">
              <div class="amenity-list">
                <?php foreach ($roomAmenities as $item): ?>
                  <?php if (isset($item['enabled']) && !$item['enabled']) continue; ?>
                  <span class="amenity-pill">
                    <?php if (!empty($item['icon'])): ?><span class="icon"><?= htmlspecialchars($item['icon']) ?></span><?php endif; ?>
                    <?= htmlspecialchars($item['label'] ?? '') ?>
                  </span>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p class="no-data">No rooms currently available at this property.</p>
    <?php endif; ?>
  </div>

  <!-- Footer -->
  <div class="footer">
    Generated by ZyroInn &mdash; <?= htmlspecialchars($property['name'] ?? '') ?> &mdash; <?= date('F d, Y') ?>
    <br class="print-hide">
    <span class="print-hide"><a href="javascript:window.print()">Print this page</a> or <a href="?route=property&property_id=<?= (int)$property['id'] ?>">view full property details</a>.</span>
  </div>

</div>
</body>
</html>
