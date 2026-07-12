<?php
// index.php - Partner Surface Front Controller (public, unauthenticated)
require_once __DIR__ . '/../../app/bootstrap.php';

use App\Services\AmenityService;
use App\Services\AuthService;
use App\Services\Database;

$env = [];
$envPath = __DIR__ . '/../../config/env.php';
if (file_exists($envPath)) {
    $env = require $envPath;
}

session_init($env);
requireEncryptionKey($env);
enforceHttps($env);

// CSRF token for the public form (session exists even without login)
AuthService::generateCsrfToken();

$route = $_GET['route'] ?? 'landing';

// POST: Handle form submissions (must come before GET routes)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Apply submission
    if ($route === 'apply') {
        if (!AuthService::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            die("CSRF validation failed. Please go back and try again.");
        }

        $propertyName = trim($_POST['property_name'] ?? '');
        $propertyType = trim($_POST['property_type'] ?? '');
        $roomCount = (int)($_POST['room_count'] ?? 0);
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $state = trim($_POST['state'] ?? '');
        $country = trim($_POST['country'] ?? 'US');
        $postalCode = trim($_POST['postal_code'] ?? '');
        $contactName = trim($_POST['contact_name'] ?? '');
        $contactEmail = trim($_POST['contact_email'] ?? '');
        $contactPhone = trim($_POST['contact_phone'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $amenities = $_POST['amenities'] ?? [];

        // Basic validation
        $errors = [];
        if (empty($propertyName)) $errors[] = 'Property name is required.';
        if (empty($contactName)) $errors[] = 'Contact name is required.';
        if (empty($contactEmail)) $errors[] = 'Email is required.';
        if ($roomCount < 1) $errors[] = 'Room count must be at least 1.';
        if (empty($city)) $errors[] = 'City is required.';

        if (!empty($errors)) {
            $amenityService = new AmenityService();
            $allAmenities = $amenityService->getFilterableAmenities();
            $csrfToken = AuthService::generateCsrfToken();
            $errorMsg = implode(' ', $errors);
            $formData = $_POST;
            include __DIR__ . '/../../app/Views/partner/apply.php';
            exit;
        }

        // Build a structured description that includes property type, room count, and amenities
        $extraInfo = [
            'property_type' => $propertyType,
            'room_count' => $roomCount,
            'amenities' => $amenities,
            'description' => $description,
        ];

        // Generate reference number
        $reference = 'ZI-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                INSERT INTO onboarding_requests (property_name, property_code, contact_name, contact_email, contact_phone, address, city, state, country, postal_code, description, verification_docs, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([
                $propertyName,
                $reference,
                $contactName,
                $contactEmail,
                $contactPhone ?: null,
                $address ?: null,
                $city,
                $state ?: null,
                $country,
                $postalCode ?: null,
                json_encode($extraInfo),
                json_encode(['submitted_amenities' => $amenities]),
            ]);

            header("Location: index.php?route=confirm&ref=" . urlencode($reference));
            exit;
        } catch (\Exception $e) {
            $amenityService = new AmenityService();
            $allAmenities = $amenityService->getFilterableAmenities();
            $csrfToken = AuthService::generateCsrfToken();
            $errorMsg = 'Something went wrong while submitting your application. Please try again or contact support.';
            $formData = $_POST;
            include __DIR__ . '/../../app/Views/partner/apply.php';
            exit;
        }
    }

    http_response_code(400);
    die("Invalid request.");
}

// --------------- ROUTE: LANDING ---------------
if ($route === 'landing') {
    include __DIR__ . '/../../app/Views/partner/landing.php';
    exit;
}

// --------------- ROUTE: APPLY ---------------
if ($route === 'apply') {
    $amenityService = new AmenityService();
    $allAmenities = $amenityService->getFilterableAmenities();
    $csrfToken = AuthService::generateCsrfToken();
    include __DIR__ . '/../../app/Views/partner/apply.php';
    exit;
}

// --------------- ROUTE: CONFIRM ---------------
if ($route === 'confirm') {
    $reference = $_GET['ref'] ?? '';
    include __DIR__ . '/../../app/Views/partner/confirm.php';
    exit;
}

// --------------- ROUTE: STATUS ---------------
if ($route === 'status') {
    include __DIR__ . '/../../app/Views/partner/status.php';
    exit;
}

http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Page Not Found - ZyroInn Partners</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
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
</head>
<body class="bg-neutral-50 text-neutral-800 antialiased">
  <div class="flex min-h-screen items-center justify-center px-4">
    <div class="text-center max-w-md">
      <p class="text-6xl font-bold text-brand-300">404</p>
      <h1 class="mt-4 text-2xl font-bold text-brand-900">Page Not Found</h1>
      <p class="mt-2 text-sm text-neutral-500">The page you&rsquo;re looking for doesn&rsquo;t exist or has been moved.</p>
      <a href="index.php" class="mt-6 inline-block rounded-2xl bg-brand-500 px-6 py-2.5 text-sm font-bold text-white hover:bg-brand-600 transition">Back to Home</a>
    </div>
  </div>
</body>
</html>
