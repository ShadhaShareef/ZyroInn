<?php
if (session_status() === PHP_SESSION_NONE) {
    $env = [];
    $envPath = __DIR__ . '/../../config/env.php';
    if (file_exists($envPath)) {
        $env = require $envPath;
    }
    session_init($env);
}

$route = $_GET['route'] ?? 'dashboard';
$propertyId = $propertyId ?? 0;
$title = $title ?? 'Admin Panel';

require_once __DIR__ . '/icons.php';

$adminNavItems = [
    ['label' => 'Dashboard',     'href' => 'index.php?route=dashboard',     'icon' => svg_icon('dashboard', 'w-5 h-5'), 'active' => $route === 'dashboard'],
    ['label' => 'Onboarding',    'href' => 'index.php?route=onboarding',   'icon' => svg_icon('clipboard', 'w-5 h-5'), 'active' => $route === 'onboarding'],
    ['label' => 'Reservations',  'href' => 'index.php?route=reservations', 'icon' => svg_icon('calendar', 'w-5 h-5'), 'active' => $route === 'reservations'],
    ['label' => 'Guest CRM',     'href' => 'index.php?route=guests',       'icon' => svg_icon('building', 'w-5 h-5'), 'active' => $route === 'guests'],
    ['label' => 'Billing',       'href' => 'index.php?route=billing',      'icon' => svg_icon('dollar', 'w-5 h-5'), 'active' => $route === 'billing'],
    ['label' => 'Commissions',   'href' => 'index.php?route=commissions',  'icon' => svg_icon('trending', 'w-5 h-5'), 'active' => $route === 'commissions'],
    ['label' => 'Analytics',     'href' => 'index.php?route=analytics',    'icon' => svg_icon('reports', 'w-5 h-5'), 'active' => $route === 'analytics'],
    ['label' => 'Fraud',         'href' => 'index.php?route=fraud',        'icon' => svg_icon('shield', 'w-5 h-5'), 'active' => $route === 'fraud'],
    ['label' => 'Moderation',    'href' => 'index.php?route=moderation',   'icon' => svg_icon('check', 'w-5 h-5'), 'active' => $route === 'moderation'],
    ['label' => 'Support',       'href' => 'index.php?route=support',      'icon' => svg_icon('concierge', 'w-5 h-5'), 'active' => in_array($route, ['support', 'support-detail'])],
    ['label' => 'Disputes',      'href' => 'index.php?route=disputes',     'icon' => svg_icon('alerts', 'w-5 h-5'), 'active' => $route === 'disputes'],
    ['label' => 'Audit Log',     'href' => 'index.php?route=audit-log',    'icon' => svg_icon('clock', 'w-5 h-5'), 'active' => $route === 'audit-log'],
];
$userInitials = strtoupper(substr($_SESSION['first_name'] ?? 'A', 0, 1) . substr($_SESSION['last_name'] ?? 'U', 0, 1));
$userName = htmlspecialchars(($_SESSION['first_name'] ?? 'Admin') . ' ' . ($_SESSION['last_name'] ?? 'User'));
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-neutral-50">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title) ?> - ZyroInn Admin</title>
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
            brand: { 50: '#F5EEFF', 100: '#E7D8FF', 200: '#CEB0FF', 300: '#B486FF', 400: '#9759F0', 500: '#6C2BD9', 600: '#5A24B3', 700: '#471C89', 800: '#36145F', 900: '#251031' },
            neutral: { 50: '#F8F8FB', 100: '#EFF0F5', 200: '#D8DAE5', 300: '#BFC3D6', 400: '#9EA3B8', 500: '#6E738A' }
          },
          fontFamily: { heading: ['Poppins', 'sans-serif'], body: ['Inter', 'sans-serif'] },
          borderRadius: { 'pill': '9999px', '2xl': '1rem', '3xl': '1.5rem' }
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
<body class="h-full bg-neutral-50 text-neutral-800 antialiased" x-data="{ sidebarOpen: true, mobileNavOpen: false }">
  <div class="min-h-full flex">
    <!-- Desktop Sidebar -->
    <aside class="hidden md:flex md:flex-col bg-white border-r border-neutral-200 shadow-sm transition-all duration-300" :class="sidebarOpen ? 'w-64' : 'w-16'">
      <div class="flex h-16 items-center justify-between px-4 border-b border-neutral-200">
        <div x-show="sidebarOpen" class="flex items-center gap-2">
          <span class="text-xl font-bold bg-gradient-to-r from-brand-600 to-brand-400 bg-clip-text text-transparent">ZyroInn</span>
          <span class="rounded-pill bg-brand-50 px-1.5 py-0.5 text-[9px] font-bold text-brand-700 uppercase tracking-wider border border-brand-100">Admin</span>
        </div>
        <button type="button" @click="sidebarOpen = !sidebarOpen" class="rounded-full p-1.5 text-neutral-500 hover:bg-neutral-100 transition">
          <svg x-show="sidebarOpen" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          <svg x-show="!sidebarOpen" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
      </div>
      <nav class="flex-1 overflow-y-auto py-4 px-2 space-y-1">
        <?php foreach ($adminNavItems as $item):
          $active = $item['active'];
          $baseClass = 'flex items-center gap-3 rounded-2xl px-3 py-2.5 text-sm font-semibold transition';
          $activeClass = $active ? 'bg-brand-50 text-brand-700 border border-brand-100' : 'text-neutral-600 hover:bg-neutral-100 hover:text-neutral-800 border border-transparent';
        ?>
          <a href="<?= htmlspecialchars($item['href']) ?>" class="<?= $baseClass ?> <?= $activeClass ?>" title="<?= htmlspecialchars($item['label']) ?>">
            <span class="text-lg flex-shrink-0"><?= $item['icon'] ?></span>
            <span x-show="sidebarOpen" class="truncate"><?= htmlspecialchars($item['label']) ?></span>
          </a>
        <?php endforeach; ?>
      </nav>
      <div class="border-t border-neutral-200 p-4" x-show="sidebarOpen">
        <div class="flex items-center gap-3">
          <div class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-100 text-brand-700 font-bold text-sm">
            <?= $userInitials ?>
          </div>
          <div class="text-xs">
            <p class="font-semibold text-neutral-700"><?= $userName ?></p>
            <p class="text-neutral-500">Platform Admin</p>
          </div>
        </div>
        <a href="index.php?route=logout" class="mt-3 flex items-center gap-2 rounded-2xl px-3 py-2 text-xs font-semibold text-neutral-500 hover:bg-rose-50 hover:text-rose-600 transition">
          Log Out
        </a>
      </div>
    </aside>

    <!-- Mobile Nav Overlay -->
    <div x-show="mobileNavOpen" class="fixed inset-0 z-50 md:hidden" x-cloak>
      <div class="absolute inset-0 bg-black/30" @click="mobileNavOpen = false"></div>
      <div class="absolute left-0 top-0 bottom-0 w-72 bg-white shadow-xl" @click.away="mobileNavOpen = false">
        <div class="flex h-16 items-center justify-between px-4 border-b border-neutral-200">
          <div class="flex items-center gap-2">
            <span class="text-xl font-bold bg-gradient-to-r from-brand-600 to-brand-400 bg-clip-text text-transparent">ZyroInn</span>
            <span class="rounded-pill bg-brand-50 px-1.5 py-0.5 text-[9px] font-bold text-brand-700 uppercase tracking-wider">Admin</span>
          </div>
          <button type="button" @click="mobileNavOpen = false" class="rounded-full p-1.5 text-neutral-500 hover:bg-neutral-100 transition">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
        <nav class="py-4 px-2 space-y-1">
          <?php foreach ($adminNavItems as $item):
            $active = $item['active'];
            $baseClass = 'flex items-center gap-3 rounded-2xl px-3 py-2.5 text-sm font-semibold transition';
            $activeClass = $active ? 'bg-brand-50 text-brand-700 border border-brand-100' : 'text-neutral-600 hover:bg-neutral-100 hover:text-neutral-800 border border-transparent';
          ?>
            <a href="<?= htmlspecialchars($item['href']) ?>" class="<?= $baseClass ?> <?= $activeClass ?>">
              <span class="text-lg flex-shrink-0"><?= $item['icon'] ?></span>
              <span class="truncate"><?= htmlspecialchars($item['label']) ?></span>
            </a>
          <?php endforeach; ?>
        </nav>
      </div>
    </div>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-h-screen">
      <!-- Top bar -->
      <header class="bg-white border-b border-neutral-200 sticky top-0 z-30 shadow-sm">
        <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
          <div class="md:hidden flex items-center gap-3">
            <button type="button" @click="mobileNavOpen = true" class="rounded-full p-1.5 text-neutral-500 hover:bg-neutral-100 transition -ml-1.5">
              <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <span class="text-xl font-bold bg-gradient-to-r from-brand-600 to-brand-400 bg-clip-text text-transparent">ZyroInn</span>
            <span class="rounded-pill bg-brand-50 px-1.5 py-0.5 text-[9px] font-bold text-brand-700 uppercase tracking-wider">Admin</span>
          </div>
          <div class="hidden md:flex items-center">
            <h2 class="text-lg font-semibold text-brand-900"><?= htmlspecialchars($title ?? 'Platform Admin') ?></h2>
          </div>
          <div class="flex items-center gap-4">
            <a href="index.php?route=logout" class="text-xs font-semibold text-neutral-500 hover:text-rose-600 transition">Log Out</a>
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-100 text-brand-700 font-bold text-sm">
              <?= $userInitials ?>
            </div>
          </div>
        </div>
      </header>

      <main class="flex-1 mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8 pb-20 md:pb-8">

        <!-- Notifications -->
        <?php if (isset($_GET['success'])): ?>
          <div class="mb-6 rounded-2xl bg-emerald-50 border border-emerald-200 p-4 text-sm font-semibold text-emerald-800 flex items-center gap-2">
            <span class="w-5 h-5 text-emerald-500"><?= svg_icon('check') ?></span>
            <span><?= htmlspecialchars($_GET['success']) ?></span>
          </div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
          <div class="mb-6 rounded-2xl bg-rose-50 border border-rose-200 p-4 text-sm font-semibold text-rose-800 flex items-center gap-2">
            <span class="w-5 h-5 text-rose-500"><?= svg_icon('xmark') ?></span>
            <span><?= htmlspecialchars($_GET['error']) ?></span>
          </div>
        <?php endif; ?>
