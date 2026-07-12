<?php
$title = 'Check Application Status - ZyroInn Partners';
include __DIR__ . '/../partials/partner-header.php';

$ref = trim($_GET['ref'] ?? '');
$email = trim($_GET['email'] ?? '');
$lookupResult = null;
$lookupError = '';

if ($ref !== '' && $email !== '') {
    try {
        $db = \App\Services\Database::getConnection();
        $stmt = $db->prepare("SELECT property_code, contact_name, contact_email, property_name, status, created_at, review_notes FROM onboarding_requests WHERE property_code = ? AND contact_email = ? LIMIT 1");
        $stmt->execute([$ref, $email]);
        $row = $stmt->fetch();
        if ($row) {
            $lookupResult = $row;
        } else {
            $lookupError = 'No application found with that reference number and email combination.';
        }
    } catch (\Exception $e) {
        $lookupError = 'Something went wrong while looking up your application. Please try again later or contact support.';
    }
}

$statusConfig = [
    'pending'     => ['label' => 'Submitted',     'class' => 'status-badge waitlisted'],
    'verified'    => ['label' => 'Under Review',  'class' => 'status-badge checked_in'],
    'approved'    => ['label' => 'Approved',      'class' => 'status-badge confirmed'],
    'rejected'    => ['label' => 'Not Approved',  'class' => 'status-badge cancelled'],
    'onboarding'  => ['label' => 'Onboarding',    'class' => 'status-badge pending'],
];
?>
<div class="main-content-narrow">
  <div class="text-center reveal">
    <p class="section-subtitle">Application Status</p>
    <h1 class="section-title text-h2">Check Application Status</h1>
    <p class="text-small" style="margin-top:0.375rem; color:var(--neutral-500);">Enter your reference number and email address to check the status of your property application.</p>
  </div>

  <!-- Lookup Form -->
  <div class="card p-6 reveal">
    <form method="GET" action="index.php" class="space-y-4">
      <input type="hidden" name="route" value="status">
      <div class="grid gap-4 sm:grid-cols-2">
        <label class="block">
          <span class="input-label">Reference Number <span style="color:var(--error);">*</span></span>
          <input type="text" name="ref" value="<?= htmlspecialchars($ref) ?>" placeholder="e.g. ZI-A1B2C3" class="input" required>
        </label>
        <label class="block">
          <span class="input-label">Email Address <span style="color:var(--error);">*</span></span>
          <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" placeholder="you@example.com" class="input" required>
        </label>
      </div>
      <button type="submit" class="btn btn-primary">Check Status</button>
    </form>
  </div>

  <?php if ($lookupError): ?>
    <!-- Error State -->
    <div class="card p-6 text-center" style="margin-top:var(--space-6); border-color:var(--error);">
      <div style="width:3rem; height:3rem; margin:0 auto; border-radius:50%; background:var(--error-bg); display:flex; align-items:center; justify-content:center;">
        <svg style="width:1.5rem; height:1.5rem; color:var(--error);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      </div>
      <p class="text-h4" style="color:var(--error); margin-top:1rem;">Application Not Found</p>
      <p class="text-small" style="color:var(--neutral-500); margin-top:0.25rem;"><?= htmlspecialchars($lookupError) ?></p>
      <p class="text-tiny" style="color:var(--neutral-400); margin-top:1rem;">If you believe this is an error, please contact our support team at <strong>partners@zyroinn.com</strong> with your reference number.</p>
    </div>
  <?php elseif ($lookupResult): ?>
    <!-- Status Result -->
    <div class="card p-6" style="margin-top:var(--space-6);">
      <div style="display:flex; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; gap:1rem;">
        <div>
          <p class="text-tiny" style="color:var(--neutral-500);">Application</p>
          <p class="text-h3" style="color:var(--brand-900); margin-top:0.125rem;"><?= htmlspecialchars($lookupResult['property_name']) ?></p>
          <p class="text-small" style="color:var(--neutral-500); margin-top:0.25rem;">Reference: <span style="font-family:monospace; font-weight:700; color:var(--neutral-700);"><?= htmlspecialchars($lookupResult['property_code']) ?></span></p>
        </div>
        <?php $sc = $statusConfig[$lookupResult['status']] ?? ['label' => 'Unknown', 'class' => 'status-badge pending']; ?>
        <span class="<?= $sc['class'] ?>"><?= htmlspecialchars($sc['label']) ?></span>
      </div>

      <hr class="divider">

      <div class="space-y-2">
        <div class="summary-row"><span style="font-size:0.8125rem;">Submitted on</span><span class="summary-row-value"><?= date('F j, Y', strtotime($lookupResult['created_at'])) ?></span></div>
        <div class="summary-row"><span style="font-size:0.8125rem;">Contact</span><span class="summary-row-value"><?= htmlspecialchars($lookupResult['contact_name']) ?> (<?= htmlspecialchars($lookupResult['contact_email']) ?>)</span></div>
      </div>

      <?php if ($lookupResult['review_notes']): ?>
        <div style="margin-top:1rem; padding:var(--space-4); background:var(--neutral-50); border:1px solid var(--neutral-200); border-radius:var(--radius-sm);">
          <p class="text-tiny" style="color:var(--neutral-500); margin-bottom:0.25rem;">Review Notes</p>
          <p class="text-small" style="color:var(--neutral-700);"><?= nl2br(htmlspecialchars($lookupResult['review_notes'])) ?></p>
        </div>
      <?php endif; ?>

      <?php if ($lookupResult['status'] === 'rejected'): ?>
        <div class="alert alert-warning" style="margin-top:1rem; font-size:0.8125rem; display:flex; gap:0.5rem; align-items:flex-start;">
          <svg style="width:1rem; height:1rem; color:var(--warning); margin-top:0.125rem; flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
          <div>
            <p class="font-semibold">What happens next?</p>
            <p style="font-weight:400; margin-top:0.25rem;">You may reapply after addressing the concerns noted above. Contact our partner support team for more details.</p>
          </div>
        </div>
      <?php endif; ?>

      <div style="margin-top:1rem; padding:var(--space-4); background:var(--brand-50); border:1px solid var(--brand-100); border-radius:var(--radius-sm);" class="text-small">
        <p class="font-semibold" style="color:var(--brand-700);">Working assumption &mdash; confirm with backend:</p>
        <p style="color:var(--brand-600); margin-top:0.125rem; font-weight:400;">This status lookup uses the reference number and email combination to query the onboarding_requests table. No user account or login is required at this stage.</p>
      </div>
    </div>
  <?php elseif ($ref !== '' || $email !== ''): ?>
    <!-- Incomplete state -->
    <div class="card p-6 text-center" style="margin-top:var(--space-6);">
      <div style="width:3rem; height:3rem; margin:0 auto; border-radius:50%; background:var(--neutral-100); display:flex; align-items:center; justify-content:center;">
        <svg style="width:1.5rem; height:1.5rem; color:var(--neutral-400);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
      </div>
      <p class="text-h4" style="color:var(--neutral-700); margin-top:1rem;">Enter your details above</p>
      <p class="text-small" style="color:var(--neutral-500); margin-top:0.25rem;">Provide both your reference number and email address to check your application status.</p>
    </div>
  <?php else: ?>
    <!-- Initial state (no search yet) -->
    <div class="card p-6 text-center" style="margin-top:var(--space-6);">
      <div style="width:3rem; height:3rem; margin:0 auto; border-radius:50%; background:var(--neutral-100); display:flex; align-items:center; justify-content:center;">
        <svg style="width:1.5rem; height:1.5rem; color:var(--neutral-400);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
      </div>
      <p class="text-h4" style="color:var(--neutral-700); margin-top:1rem;">Enter your details above</p>
      <p class="text-small" style="color:var(--neutral-500); margin-top:0.25rem;">Provide both your reference number and email address to check your application status.</p>
    </div>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/../partials/partner-footer.php'; ?>
