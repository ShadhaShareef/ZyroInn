<?php
if (session_status() === PHP_SESSION_NONE) {
    $env = [];
    $envPath = __DIR__ . '/../../config/env.php';
    if (file_exists($envPath)) $env = require $envPath;
    session_init($env);
}
$route = $_GET['route'] ?? 'landing';
$title = $title ?? 'List Your Property - ZyroInn Partners';
$metaDescription = $metaDescription ?? 'Join ZyroInn and list your property on our platform. Reach travelers worldwide with zero upfront costs.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title) ?></title>
  <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
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
          borderRadius: { 'pill': '9999px', '2xl': '1rem', '3xl': '1.5rem' }
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
      <a href="index.php?route=landing" class="flex items-center gap-2">
        <span style="font-size:1.5rem; font-weight:700; background:linear-gradient(135deg,var(--brand-600),var(--brand-400)); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;">ZyroInn</span>
      </a>
      <span class="pill pill-brand">Partners</span>
    </div>
    <nav class="flex items-center gap-1" style="overflow:visible; flex-shrink:0;">
      <a href="index.php?route=landing" class="glass-nav-link <?= $route === 'landing' ? 'active' : '' ?>">Home</a>
      <a href="index.php?route=landing#how-it-works" class="glass-nav-link">How It Works</a>
      <a href="index.php?route=landing#benefits" class="glass-nav-link">Why Partner</a>
      <a href="index.php?route=status" class="glass-nav-link <?= $route === 'status' ? 'active' : '' ?>">Status</a>
      <a href="index.php?route=apply" class="btn btn-primary btn-sm" style="margin-left:0.5rem;">Apply Now</a>
    </nav>
  </div>
</header>

<?php if ($route === 'apply' || $route === 'confirm' || $route === 'status'): ?>
<div style="max-width:72rem; margin:0 auto; padding:var(--space-2) var(--space-4) 0;">
  <div class="breadcrumb">
    <a href="index.php?route=landing">Home</a>
    <span class="breadcrumb-sep">/</span>
    <span class="breadcrumb-current"><?= $route === 'apply' ? 'Apply' : ($route === 'confirm' ? 'Confirmation' : 'Status') ?></span>
  </div>
</div>
<?php endif; ?>

<main>
