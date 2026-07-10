<?php
$conversations = $conversations ?? [];
$activeConversation = $activeConversation ?? null;
$messages = $messages ?? [];
$title = 'Guest Messages - ZyroInn';
$badge = 'Concierge';
include __DIR__ . '/../../partials/staff-header.php';
?>
  <div style="display:flex; flex-direction:column; gap:var(--space-4); min-height:60vh;">

  <?php if ($activeConversation === null): ?>

    <div style="display:flex; flex-direction:column; gap:var(--space-2);">
      <h2 class="text-h4" style="margin-bottom:var(--space-2);">Guest Messages</h2>
      <?php if (empty($conversations)): ?>
        <div class="empty-state">
          <h3 class="empty-state-title">No Conversations</h3>
          <p class="empty-state-text">Guest messages will appear here when guests reach out.</p>
        </div>
      <?php else: ?>
        <?php foreach ($conversations as $conv):
          $unread = (int)$conv['unread'];
        ?>
          <a href="index.php?route=messaging&conversation_id=<?= (int)$conv['id'] ?>" class="card" style="display:flex; align-items:center; gap:var(--space-3); padding:var(--space-4); text-decoration:none; color:inherit;">
            <div style="width:2.5rem; height:2.5rem; border-radius:9999px; background:var(--brand-100); color:var(--brand-700); font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
              <?= htmlspecialchars(substr($conv['first_name'] ?? 'G', 0, 1) . substr($conv['last_name'] ?? 'T', 0, 1)) ?>
            </div>
            <div style="flex:1; min-width:0;">
              <div style="display:flex; align-items:center; gap:var(--space-2);">
                <span style="font-weight:600; color:var(--brand-900);"><?= htmlspecialchars(($conv['first_name'] ?? '') . ' ' . ($conv['last_name'] ?? '')) ?></span>
                <?php if ($unread > 0): ?>
                  <span style="background:var(--brand-500); color:#fff; font-size:0.6875rem; font-weight:700; padding:0 0.375rem; border-radius:9999px; line-height:1.25rem;"><?= $unread ?></span>
                <?php endif; ?>
              </div>
              <span class="text-tiny" style="color:var(--neutral-400);">
                Room <?= htmlspecialchars($conv['room_number'] ?? '') ?> &middot; <?= htmlspecialchars($conv['check_in_date'] ?? '') ?> &ndash; <?= htmlspecialchars($conv['check_out_date'] ?? '') ?>
              </span>
            </div>
            <span style="color:var(--neutral-300);">&rarr;</span>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  <?php else: ?>

    <div style="display:flex; flex-direction:column; flex:1; min-height:0;">
      <div style="display:flex; align-items:center; gap:var(--space-3); padding-bottom:var(--space-3); border-bottom:1px solid var(--neutral-100);">
        <a href="index.php?route=messaging" style="color:var(--neutral-400); text-decoration:none; font-size:1.25rem;">&larr;</a>
        <div>
          <span style="font-weight:600; color:var(--brand-900);"><?= htmlspecialchars(($activeConversation['first_name'] ?? '') . ' ' . ($activeConversation['last_name'] ?? '')) ?></span>
          <span class="text-tiny" style="color:var(--neutral-400); display:block;">
            <?= htmlspecialchars($activeConversation['email'] ?? '') ?> &middot; Room <?= htmlspecialchars($activeConversation['room_number'] ?? '') ?>
          </span>
        </div>
      </div>

      <div style="flex:1; overflow-y:auto; padding:var(--space-3) 0; display:flex; flex-direction:column; gap:var(--space-3); max-height:50vh;">
        <?php if (empty($messages)): ?>
          <p class="text-small" style="color:var(--neutral-400); text-align:center; padding:var(--space-8);">No messages yet.</p>
        <?php endif; ?>
        <?php foreach ($messages as $msg): ?>
          <div style="display:flex; <?= $msg['sender_type'] === 'staff' ? 'justify-content:flex-end' : 'justify-content:flex-start' ?>">
            <div style="max-width:75%; padding:var(--space-3); border-radius:var(--radius-lg); <?= $msg['sender_type'] === 'staff' ? 'background:var(--brand-500); color:#fff; border-bottom-right-radius:0' : 'background:var(--neutral-50); color:var(--neutral-700); border-bottom-left-radius:0' ?>">
              <p style="font-size:0.875rem; margin:0; white-space:pre-wrap;"><?= htmlspecialchars($msg['message']) ?></p>
              <span style="font-size:0.625rem; opacity:0.7; display:block; margin-top:var(--space-1);"><?= date('M d, g:i A', strtotime($msg['created_at'])) ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <form method="POST" action="index.php?route=messaging" style="display:flex; gap:var(--space-2); padding-top:var(--space-3); border-top:1px solid var(--neutral-100);">
        <input type="hidden" name="conversation_id" value="<?= (int)$activeConversation['id'] ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
        <textarea name="message" rows="1" required placeholder="Reply as staff..." style="flex:1; resize:none; padding:var(--space-3); border:1px solid var(--neutral-200); border-radius:var(--radius-sm); font-family:inherit; font-size:0.875rem; outline:none; min-height:2.5rem;" oninput="this.style.height='';this.style.height=this.scrollHeight+'px'"></textarea>
        <button type="submit" class="btn btn-primary" style="white-space:nowrap;">Send</button>
      </form>
    </div>

  <?php endif; ?>

</div>
<?php include __DIR__ . '/../../partials/staff-footer.php'; ?>
