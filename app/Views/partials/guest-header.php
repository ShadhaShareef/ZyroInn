<?php
if (session_status() === PHP_SESSION_NONE) {
    $env = [];
    $envPath = __DIR__ . '/../../config/env.php';
    if (file_exists($envPath)) $env = require $envPath;
    session_init($env);
}
$route = $_GET['route'] ?? 'home';
$badge = $badge ?? 'Guest Portal';
$showNav = $showNav ?? true;
$metaDescription = $metaDescription ?? 'Discover handpicked boutique hotels and unique stays worldwide on ZyroInn.';
$ogImage = $ogImage ?? '';
$canonical = $canonical ?? '';
$navItems = $navItems ?? [];
$c = $_SESSION['first_name'] ?? '';
$d = $_SESSION['last_name'] ?? '';
$userInitials = strtoupper(($c ? $c[0] : 'G') . ($d ? $d[0] : 'T'));
$isLoggedIn = !empty($_SESSION['guest_id']);
$guestName = trim($c . ' ' . $d);

/* Route labels for breadcrumb */
$routeLabels = [
  'home' => 'Home', 'search' => 'Search', 'property' => 'Property Details',
  'room' => 'Room Details', 'book' => 'Booking', 'group-book' => 'Group Booking',
  'confirm' => 'Confirmation', 'waitlist-confirm' => 'Waitlist',
  'bookings' => 'My Bookings', 'profile' => 'My Profile',
  'messages' => 'Messages', 'services' => 'Services',
  'pre-arrival' => 'Pre-Arrival Check-In', 'loyalty-history' => 'Loyalty History',
];
$currentLabel = $routeLabels[$route] ?? 'ZyroInn';

/* Default nav items shown to all visitors (logged-in pages override post-header) */
if (empty($navItems)) {
  $navItems = [
    ['href' => 'index.php?route=home', 'label' => 'Home', 'active' => $route === 'home'],
    ['href' => 'index.php?route=search', 'label' => 'Browse Stays', 'active' => $route === 'search'],
    ['href' => 'index.php?route=book', 'label' => 'Book Now', 'active' => $route === 'book'],
  ];
  if ($isLoggedIn) {
    $navItems = [
      ['href' => 'index.php?route=home', 'label' => 'Home', 'active' => $route === 'home'],
      ['href' => 'index.php?route=search', 'label' => 'Browse Stays', 'active' => $route === 'search'],
      ['href' => 'index.php?route=bookings', 'label' => 'My Bookings', 'active' => $route === 'bookings'],
      ['href' => 'index.php?route=messages', 'label' => 'Messages', 'active' => $route === 'messages'],
      ['href' => 'index.php?route=services', 'label' => 'Services', 'active' => $route === 'services'],
      ['href' => 'index.php?route=profile', 'label' => 'Profile', 'active' => $route === 'profile'],
    ];
  }
}
$showNav = $showNav && count($navItems) > 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title ?? 'ZyroInn') ?></title>
  <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
  <link rel="canonical" href="<?= htmlspecialchars($canonical ?: ($scheme ?? 'https') . '://' . ($host ?? $_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/')) ?>">
  <meta property="og:type" content="website">
  <meta property="og:title" content="<?= htmlspecialchars($title ?? 'ZyroInn') ?>">
  <meta property="og:description" content="<?= htmlspecialchars($metaDescription) ?>">
  <?php if ($ogImage): ?><meta property="og:image" content="<?= htmlspecialchars($ogImage) ?>"><?php endif; ?>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="<?= BASE_URL ?>/assets/js/guest.js" defer></script>
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
          borderRadius: { 'pill': '9999px' }
        }
      }
    }
  </script>
  <style>
    [x-cloak] { display: none !important; }
  </style>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/guest.css">
</head>
<body>

<div id="page-loader" class="page-loader">
  <div class="typing-indicator">
    <div class="typing-circle"></div>
    <div class="typing-circle"></div>
    <div class="typing-circle"></div>
    <div class="typing-shadow"></div>
    <div class="typing-shadow"></div>
    <div class="typing-shadow"></div>
  </div>
</div>

<header class="glass" style="border-bottom:1px solid rgba(216,218,229,0.5); position:sticky; top:0; z-index:40;">
  <div class="flex items-center justify-between" style="height:4rem; max-width:72rem; margin:0 auto; padding:0 var(--space-4);">
    <div class="flex items-center gap-3">
      <a href="index.php?route=home" class="flex items-center gap-2">
        <span style="font-size:1.5rem; font-weight:700; background:linear-gradient(135deg,var(--brand-600),var(--brand-400)); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;">ZyroInn</span>
      </a>
      <span class="pill pill-brand"><?= htmlspecialchars($badge) ?></span>
    </div>

    <?php if ($showNav && !empty($navItems)): ?>
    <nav class="flex items-center gap-1" style="overflow:visible; flex-shrink:0;">
      <?php foreach ($navItems as $item): ?>
        <a href="<?= htmlspecialchars($item['href']) ?>"
           class="glass-nav-link <?= !empty($item['active']) ? 'active' : '' ?>"><?= htmlspecialchars($item['label']) ?></a>
      <?php endforeach; ?>
    </nav>
    <?php endif; ?>

    <div class="flex items-center gap-3">
      <?php if ($isLoggedIn): ?>
        <a href="index.php?route=logout" class="btn btn-ghost btn-sm" style="color:var(--neutral-400);">Log out</a>
        <span class="text-small" style="color:var(--neutral-400);"><?= htmlspecialchars($guestName) ?></span>
        <div style="width:2.25rem; height:2.25rem; border-radius:50%; background:var(--brand-100); color:var(--brand-700); font-weight:700; font-size:0.8125rem; display:flex; align-items:center; justify-content:center; border:1px solid var(--brand-200);"><?= $userInitials ?></div>
      <?php else: ?>
        <a href="index.php?route=login" class="btn btn-primary btn-sm">Sign In</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<?php if ($route !== 'home' && $route !== 'login' && $route !== 'register'): ?>
<div style="max-width:72rem; margin:0 auto; padding:var(--space-2) var(--space-4) 0;">
  <div class="breadcrumb">
    <a href="index.php?route=home">Home</a>
    <span class="breadcrumb-sep">/</span>
    <span class="breadcrumb-current"><?= htmlspecialchars($currentLabel) ?></span>
  </div>
</div>
<?php endif; ?>

<main>