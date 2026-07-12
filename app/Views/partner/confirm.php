<?php
$title = 'Application Submitted - ZyroInn Partners';
include __DIR__ . '/../partials/partner-header.php';
?>
<div class="main-content-narrow">
  <div class="card p-8 sm:p-12 text-center reveal">
    <div style="width:4rem; height:4rem; margin:0 auto; border-radius:50%; background:var(--confirmation-bg); border:1px solid var(--confirmation-color); display:flex; align-items:center; justify-content:center;">
      <svg style="width:2rem; height:2rem; color:var(--confirmation-color);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </div>
    <h1 class="text-h2" style="color:var(--brand-900); margin-top:1.5rem;">Application Submitted!</h1>
    <p class="text-body" style="color:var(--neutral-500); max-width:24rem; margin:0.5rem auto 0;">Thank you for applying to list your property with ZyroInn. Our team will review your application and get back to you within 2 business days.</p>

    <div style="margin-top:2rem; display:inline-block; padding:var(--space-5) var(--space-8); background:var(--brand-50); border:1px solid var(--brand-100); border-radius:var(--radius-md);">
      <p class="text-tiny" style="color:var(--brand-500);">Your Reference Number</p>
      <p style="font-family:var(--font-heading); font-size:1.75rem; font-weight:800; color:var(--brand-700); letter-spacing:0.05em; margin-top:0.25rem;"><?= htmlspecialchars($reference ?: 'ZI-??????') ?></p>
    </div>

    <div class="alert alert-warning" style="margin-top:1.5rem; text-align:left; font-size:0.8125rem;">
      <p class="font-semibold">Important note &mdash; no login access yet:</p>
      <p style="margin-top:0.25rem; font-weight:400;">You do not need to create an account at this stage. Your application is tracked by the reference number above. If your property is approved, you will receive an email with instructions to set up your account and access the owner portal.</p>
    </div>

    <div style="margin-top:2rem; display:flex; flex-direction:column; align-items:center; gap:1rem;">
      <a href="index.php?route=status&ref=<?= urlencode($reference) ?>" class="btn btn-primary">Check Application Status</a>
      <a href="index.php?route=landing" class="btn btn-ghost">Back to Home</a>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../partials/partner-footer.php'; ?>
