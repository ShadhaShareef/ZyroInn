<?php
$error = $error ?? '';
$success = $success ?? '';
$resetLink = $resetLink ?? '';
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-neutral-50">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password - ZyroInn</title>
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
          borderRadius: { pill: '9999px', '2xl': '1rem', '3xl': '1.5rem' }
        }
      }
    }
  </script>
  <style>
    body { font-family: 'Inter', sans-serif; }
    h1, h2, h3, h4 { font-family: 'Poppins', sans-serif; }
  </style>
</head>
<body class="h-full flex items-center justify-center p-4">
  <div class="w-full max-w-sm">
    <div class="text-center mb-8">
      <h1 class="text-3xl font-bold bg-gradient-to-r from-brand-600 to-brand-400 bg-clip-text text-transparent">ZyroInn</h1>
      <p class="text-sm text-neutral-500 mt-2">Reset your password</p>
    </div>

    <div class="bg-white rounded-3xl border border-neutral-200 shadow-sm p-6">
      <?php if ($error): ?>
        <div class="mb-4 rounded-2xl bg-red-50 border border-red-200 px-4 py-3 text-sm font-semibold text-red-700"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="mb-4 rounded-2xl bg-green-50 border border-green-200 px-4 py-3 text-sm font-semibold text-green-700"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>

      <?php if ($resetLink): ?>
        <div class="mb-4 rounded-2xl bg-brand-50 border border-brand-200 px-4 py-3 text-sm text-brand-800">
          <p class="font-semibold mb-1">Dev Mode — Reset Link:</p>
          <a href="<?= htmlspecialchars($resetLink) ?>" class="text-brand-600 hover:underline break-all"><?= htmlspecialchars($resetLink) ?></a>
          <p class="text-[10px] text-brand-500 mt-2">In production, this link would be emailed.</p>
        </div>
      <?php endif; ?>

      <?php if (!$success): ?>
      <form method="POST" action="forgot-password.php" class="space-y-4">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

        <div>
          <label for="email" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Email Address</label>
          <input type="email" id="email" name="email" required autocomplete="email"
                 class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500" placeholder="you@example.com">
        </div>

        <button type="submit" class="w-full rounded-2xl bg-brand-500 py-3 text-sm font-bold text-white hover:bg-brand-600 transition shadow-sm">
          Send Reset Link
        </button>
      </form>
      <?php endif; ?>

      <div class="mt-4 text-center">
        <a href="login.php" class="text-xs font-semibold text-brand-500 hover:text-brand-600 hover:underline">Back to Sign In</a>
      </div>
    </div>
  </div>
</body>
</html>
