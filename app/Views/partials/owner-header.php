<?php
if (session_status() === PHP_SESSION_NONE) {
    $env = [];
    $envPath = __DIR__ . '/../../config/env.php';
    if (file_exists($envPath)) {
        $env = require $envPath;
    }
    session_init($env);
}
$route = $route ?? ($_GET['route'] ?? 'dashboard');
$propertyId = $propertyId ?? 0;
$propertyOptions = $propertyOptions ?? [];

$ownerNavItems = [
    ['label' => 'Dashboard',       'href' => BASE_URL . '/owner/index.php?route=dashboard',        'active' => $route === 'dashboard'],
    ['label' => 'Rate & Inventory','href' => BASE_URL . '/owner/index.php?route=inventory',        'active' => $route === 'inventory'],
    ['label' => 'Property Features','href' => BASE_URL . '/owner/index.php?route=property-features','active' => $route === 'property-features'],
    ['label' => 'Alerts',          'href' => BASE_URL . '/owner/index.php?route=alerts',            'active' => $route === 'alerts'],
    ['label' => 'Staff Scheduling','href' => BASE_URL . '/owner/index.php?route=staff-scheduling',  'active' => $route === 'staff-scheduling'],
    ['label' => 'Expenses',        'href' => BASE_URL . '/owner/index.php?route=expenses',          'active' => $route === 'expenses'],
    ['label' => 'Reports',         'href' => BASE_URL . '/owner/index.php?route=reports',           'active' => $route === 'reports'],
];

$ownerMobileItems = [
    ['label' => 'Dashboard', 'icon' => '📊', 'href' => BASE_URL . '/owner/index.php?route=dashboard',        'active' => $route === 'dashboard'],
    ['label' => 'Inventory', 'icon' => '🛏️', 'href' => BASE_URL . '/owner/index.php?route=inventory',        'active' => $route === 'inventory'],
    ['label' => 'Features',  'icon' => '✨', 'href' => BASE_URL . '/owner/index.php?route=property-features','active' => $route === 'property-features'],
    ['label' => 'Alerts',    'icon' => '🔔', 'href' => BASE_URL . '/owner/index.php?route=alerts',            'active' => $route === 'alerts'],
    ['label' => 'More',      'icon' => '⚙️', 'href' => BASE_URL . '/owner/index.php?route=staff-scheduling',  'active' => in_array($route, ['staff-scheduling','expenses','reports'])],
];
$userInitials = strtoupper(substr($_SESSION['first_name'] ?? 'O', 0, 1) . substr($_SESSION['last_name'] ?? 'U', 0, 1));
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-neutral-50">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title ?? 'Owner Console') ?> - ZyroInn</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            brand: {
              50: '#F5EEFF', 100: '#E7D8FF', 200: '#CEB0FF', 300: '#B486FF',
              400: '#9759F0', 500: '#6C2BD9', 600: '#5A24B3', 700: '#471C89',
              800: '#36145F', 900: '#251031',
            },
            neutral: {
              50: '#F8F8FB', 100: '#EFF0F5', 200: '#D8DAE5', 300: '#BFC3D6',
              400: '#9EA3B8', 500: '#6E738A',
            }
          },
          fontFamily: { heading: ['Poppins', 'sans-serif'], body: ['Inter', 'sans-serif'] },
          borderRadius: { pill: '9999px', '2xl': '1rem', '3xl': '1.5rem' }
        }
      }
    }
  </script>
  <style>
    body { font-family: 'Inter', sans-serif; }
    h1, h2, h3, h4 { font-family: 'Poppins', sans-serif; }
    [x-cloak] { display: none !important; }
  </style>
</head>
<body class="h-full bg-neutral-50 text-neutral-800 antialiased" <?= $bodyData ?? '' ?>>
  <div class="min-h-full flex flex-col justify-between">
    <!-- Header -->
    <header class="border-b border-neutral-200 bg-white sticky top-0 z-40 shadow-sm">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">
          <div class="flex items-center gap-3">
            <a href="<?= BASE_URL ?>/owner/index.php?route=dashboard" class="flex items-center gap-2">
              <span class="text-2xl font-bold bg-gradient-to-r from-brand-600 to-brand-400 bg-clip-text text-transparent">ZyroInn</span>
            </a>
            <span class="rounded-pill border border-brand-100 bg-brand-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-[0.25em] text-brand-700">Owner Portal</span>
          </div>

          <!-- Desktop Nav -->
          <nav class="hidden md:flex items-center gap-1">
            <?php foreach ($ownerNavItems as $item): ?>
              <a href="<?= htmlspecialchars($item['href']) ?>"
                 class="whitespace-nowrap rounded-2xl px-3 py-2 text-sm font-semibold transition <?= $item['active'] ? 'bg-brand-50 text-brand-700 border border-brand-100' : 'text-neutral-500 hover:text-brand-500 hover:bg-neutral-50' ?>">
                <?= htmlspecialchars($item['label']) ?>
              </a>
            <?php endforeach; ?>
          </nav>

          <div class="flex items-center gap-3">
            <?php if (!empty($propertyOptions)): ?>
            <div class="items-center gap-2 rounded-pill bg-neutral-100 px-3 py-1 text-xs hidden md:flex">
              <span class="font-semibold text-neutral-500">Property</span>
               <select onchange="window.location.href='<?= BASE_URL ?>/owner/index.php?route=<?= htmlspecialchars($route) ?>&property_id=' + this.value" class="bg-transparent font-semibold text-brand-600 outline-none">
                <?php foreach ($propertyOptions as $option): ?>
                  <option value="<?= (int)($option['id'] ?? 0) ?>" <?= ((int)($option['id'] ?? 0) === (int)$propertyId) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($option['name'] ?? 'Property') ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>/owner/index.php?route=logout" class="hidden md:inline text-xs font-semibold text-neutral-500 hover:text-rose-600 transition">Log Out</a>
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-100 text-brand-700 font-bold text-sm border border-brand-200">
              <?= $userInitials ?>
            </div>
          </div>
        </div>
      </div>
    </header>
