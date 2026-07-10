<?php
/**
 * staff-header.php - Shared Staff App Header Template
 */
if (session_status() === PHP_SESSION_NONE) {
    $env = [];
    $envPath = __DIR__ . '/../../config/env.php';
    if (file_exists($envPath)) {
        $env = require $envPath;
    }
    session_init($env);
}

$route = $_GET['route'] ?? 'room-status-board';
$role = $_SESSION['role'] ?? 'front_office';

// Define navigation tabs based on role
$staffNavItems = [];
if ($role === 'front_office' || $role === 'manager') {
    $staffNavItems = [
        [
            'label' => 'Today',
            'href' => 'index.php?route=room-status-board',
            'active' => ($route === 'room-status-board')
        ],
        [
            'label' => 'Reservations',
            'href' => 'index.php?route=reservations',
            'active' => ($route === 'reservations' || $route === 'check-in' || $route === 'check-out')
        ],
        [
            'label' => 'Guests',
            'href' => 'index.php?route=guests',
            'active' => ($route === 'guests')
        ],
        [
            'label' => 'Messages',
            'href' => 'index.php?route=messaging',
            'active' => ($route === 'messaging')
        ],
        [
            'label' => 'More',
            'href' => 'index.php?route=more',
            'active' => ($route === 'more')
        ]
    ];
} elseif ($role === 'housekeeping') {
    $staffNavItems = [
        [
            'label' => 'Status Board',
            'href' => 'index.php?route=room-status-board',
            'active' => ($route === 'room-status-board')
        ],
        [
            'label' => 'My Tasks',
            'href' => 'index.php?route=housekeeping-tasks',
            'active' => ($route === 'housekeeping-tasks')
        ],
        [
            'label' => 'Lost & Found',
            'href' => 'index.php?route=lost-and-found',
            'active' => ($route === 'lost-and-found')
        ],
        [
            'label' => 'Inventory',
            'href' => 'index.php?route=housekeeping-inventory',
            'active' => ($route === 'housekeeping-inventory')
        ]
    ];
} elseif ($role === 'maintenance') {
    $staffNavItems = [
        [
            'label' => 'Issue Queue',
            'href' => 'index.php?route=maintenance-queue',
            'active' => ($route === 'maintenance-queue' || $route === 'maintenance-detail')
        ],
        [
            'label' => 'PM Calendar',
            'href' => 'index.php?route=preventive-maintenance',
            'active' => ($route === 'preventive-maintenance')
        ],
        [
            'label' => 'Vendors',
            'href' => 'index.php?route=vendors',
            'active' => ($route === 'vendors')
        ]
    ];
} elseif ($role === 'fnb') {
    $staffNavItems = [
        [
            'label' => 'Order Queue',
            'href' => 'index.php?route=fnb-orders',
            'active' => ($route === 'fnb-orders')
        ],
        [
            'label' => 'Menu Editor',
            'href' => 'index.php?route=fnb-menu',
            'active' => ($route === 'fnb-menu')
        ],
        [
            'label' => 'Stock & Waste',
            'href' => 'index.php?route=fnb-stock-waste',
            'active' => ($route === 'fnb-stock-waste')
        ]
    ];
}

// Redirect helpers when switching to ensure we land on an allowed route for the role
$defaultRoutes = [
    'front_office' => 'room-status-board',
    'housekeeping' => 'room-status-board',
    'maintenance' => 'maintenance-queue',
    'fnb' => 'fnb-orders',
    'manager' => 'room-status-board'
];
$targetSwitchRoute = $defaultRoutes[$role] ?? 'room-status-board';
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-neutral-50">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title ?? 'Staff Console') ?> - ZyroInn</title>
  <!-- Google Fonts: Poppins & Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Alpine.js CDN -->
  <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
  
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            brand: {
              50: '#F5EEFF',
              100: '#E7D8FF',
              200: '#CEB0FF',
              300: '#B486FF',
              400: '#9759F0',
              500: '#6C2BD9',
              600: '#5A24B3',
              700: '#471C89',
              800: '#36145F',
              900: '#251031',
            },
            neutral: {
              50: '#F8F8FB',
              100: '#EFF0F5',
              200: '#D8DAE5',
              300: '#BFC3D6',
              400: '#9EA3B8',
              500: '#6E738A',
            }
          },
          fontFamily: {
            heading: ['Poppins', 'sans-serif'],
            body: ['Inter', 'sans-serif'],
          },
          borderRadius: {
            'pill': '9999px',
            '2xl': '1rem',
            '3xl': '1.5rem',
          }
        }
      }
    }
  </script>
  <style>
    body {
      font-family: 'Inter', sans-serif;
    }
    h1, h2, h3, h4 {
      font-family: 'Poppins', sans-serif;
    }
    [x-cloak] { display: none !important; }
  </style>
</head>
<body class="h-full text-neutral-800 antialiased" <?= $bodyData ?? '' ?>>

  <!-- Shell Layout -->
  <div class="min-h-full flex flex-col justify-between">
    <!-- Staff Navbar Header -->
    <header class="bg-white border-b border-neutral-200 sticky top-0 z-40 shadow-sm">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">
          <div class="flex items-center gap-3">
            <span class="text-2xl font-bold bg-gradient-to-r from-brand-600 to-brand-400 bg-clip-text text-transparent">ZyroInn</span>
            <span class="rounded-pill bg-brand-50 px-2 py-0.5 text-[10px] font-bold text-brand-700 uppercase tracking-widest border border-brand-100">Staff Portal</span>
          </div>
          
          <!-- Desktop navigation links -->
          <nav class="hidden md:flex items-center gap-6">
            <?php foreach ($staffNavItems as $item): ?>
              <a href="<?= $item['href'] ?>" class="text-sm font-semibold <?= $item['active'] ? 'text-brand-600 border-b-2 border-brand-500 pb-1' : 'text-neutral-500 hover:text-brand-500 transition pb-1' ?>">
                <?= htmlspecialchars($item['label']) ?>
              </a>
            <?php endforeach; ?>
          </nav>

          <div class="flex items-center gap-4">
            <!-- Developer Role Quick-Switcher -->
            <div class="flex items-center gap-2 bg-neutral-100 rounded-pill px-3 py-1 text-xs">
              <span class="text-neutral-500 font-medium whitespace-nowrap">Logged in:</span>
              <span class="font-bold text-brand-700 uppercase whitespace-nowrap"><?= str_replace('_', ' ', htmlspecialchars($role)) ?></span>
              <div class="h-3 w-px bg-neutral-300"></div>
              <select onchange="let routeMap={'front_office':'room-status-board','housekeeping':'room-status-board','maintenance':'maintenance-queue','fnb':'fnb-orders','manager':'room-status-board'}; window.location.href='index.php?switch_to=' + this.value + '&route=' + (routeMap[this.value] || 'room-status-board')" class="bg-transparent border-0 font-semibold text-brand-600 cursor-pointer focus:ring-0">
                <option value="front_office" <?= $role === 'front_office' ? 'selected' : '' ?>>Priya (Front Office)</option>
                <option value="housekeeping" <?= $role === 'housekeeping' ? 'selected' : '' ?>>Leo (Housekeeper)</option>
                <option value="maintenance" <?= $role === 'maintenance' ? 'selected' : '' ?>>Asha (Maintenance)</option>
                <option value="fnb" <?= $role === 'fnb' ? 'selected' : '' ?>>Vikram (F&B Staff)</option>
                <option value="manager" <?= $role === 'manager' ? 'selected' : '' ?>>Rohit (Manager)</option>
              </select>
            </div>
            
            <a href="index.php?route=logout" class="hidden md:inline text-xs font-semibold text-neutral-500 hover:text-rose-600 transition">Log Out</a>
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-100 text-brand-700 font-bold">
              <?= strtoupper(substr($_SESSION['first_name'] ?? 'U', 0, 1) . substr($_SESSION['last_name'] ?? 'P', 0, 1)) ?>
            </div>
          </div>
        </div>
      </div>
    </header>

    <?php
    $staffIconMap = [
      'Today' => '📋', 'Reservations' => '📅', 'Guests' => '👥', 'More' => '⚙️',
      'Status Board' => '📋', 'My Tasks' => '✅', 'Lost & Found' => '🔍', 'Inventory' => '📦',
      'Issue Queue' => '🔧', 'PM Calendar' => '📅', 'Vendors' => '🏢',
      'Order Queue' => '🍽️', 'Menu Editor' => '📝', 'Stock & Waste' => '📊',
    ];
    $mobileItems = array_slice($staffNavItems, 0, 5);
    $items = [];
    foreach ($mobileItems as $item) {
        $items[] = [
            'label' => $item['label'],
            'icon' => $staffIconMap[$item['label']] ?? '📌',
            'href' => $item['href'],
            'active' => $item['active'],
        ];
    }
    include __DIR__ . '/bottom-tab-bar.php';
    ?>

    <!-- Main Content -->
    <main class="flex-grow mx-auto w-full max-w-7xl px-4 py-8 pb-24 sm:px-6 lg:px-8 lg:pb-8">
      
      <!-- Success / Error notifications -->
      <?php if (isset($_GET['success'])): ?>
        <div class="mb-6 rounded-2xl bg-emerald-50 border border-emerald-200 p-4 text-sm font-semibold text-emerald-800 flex items-center gap-2">
          <span>✅</span>
          <span><?= htmlspecialchars($_GET['success']) ?></span>
        </div>
      <?php endif; ?>
      <?php if (isset($_GET['error'])): ?>
        <div class="mb-6 rounded-2xl bg-rose-50 border border-rose-200 p-4 text-sm font-semibold text-rose-800 flex items-center gap-2">
          <span>❌</span>
          <span><?= htmlspecialchars($_GET['error']) ?></span>
        </div>
      <?php endif; ?>
