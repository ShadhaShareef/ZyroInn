<?php
$title = 'Messages - ZyroInn';
$badge = 'Inbox';
$conversations = $conversations ?? [];
$activeConversation = $activeConversation ?? null;
$messages = $messages ?? [];
$guestBookings = $guestBookings ?? [];
include __DIR__ . '/../partials/guest-header.php';
?>

    <main class="main-content">

      <div style="display:flex; flex-direction:column; gap:var(--space-4); height:calc(100vh - 12rem);">

        <?php if (empty($conversations)): ?>

          <div class="empty-state" style="flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center;">
            <div style="width:5rem; height:5rem; background:var(--brand-50); border-radius:9999px; display:flex; align-items:center; justify-content:center; margin-bottom:var(--space-4); color:var(--brand-400); font-size:1.5rem; font-weight:700;">M</div>
            <h3 class="empty-state-title">No Messages Yet</h3>
            <p class="empty-state-text">Start a conversation with the front office about your booking.</p>
            <?php if (!empty($guestBookings)): ?>
              <button class="btn btn-primary" style="margin-top:var(--space-4);" onclick="document.getElementById('new-msg-modal').classList.remove('hidden')">Send a Message</button>
            <?php endif; ?>
          </div>

        <?php elseif ($activeConversation === null): ?>
          <?php /* Conversation list */ ?>
          <div style="display:flex; flex-direction:column; gap:var(--space-2);">
            <div style="display:flex; align-items:center; justify-content:space-between;">
              <h2 class="section-title mb-1">Conversations</h2>
              <button class="btn btn-primary btn-sm" onclick="document.getElementById('new-msg-modal').classList.remove('hidden')">New Message</button>
            </div>
            <?php foreach ($conversations as $conv):
              $convId = (int)$conv['id'];
              $unread = (int)$conv['unread'];
            ?>
              <a href="index.php?route=messages&conversation_id=<?= $convId ?>" class="card" style="display:flex; align-items:center; gap:var(--space-3); padding:var(--space-4); text-decoration:none; color:inherit;">
                <div style="width:2.5rem; height:2.5rem; border-radius:9999px; background:var(--brand-100); color:var(--brand-700); font-weight:700; display:flex; align-items:center; justify-content:center; font-size:0.875rem; flex-shrink:0;"><?= htmlspecialchars(substr($conv['property_name'] ?? 'H', 0, 1)) ?></div>
                <div style="flex:1; min-width:0;">
                  <div style="display:flex; align-items:center; gap:var(--space-2);">
                    <span style="font-weight:600; color:var(--brand-900);"><?= htmlspecialchars($conv['property_name'] ?? '') ?></span>
                    <?php if ($unread > 0): ?>
                      <span style="background:var(--brand-500); color:#fff; font-size:0.6875rem; font-weight:700; padding:0 0.375rem; border-radius:9999px; line-height:1.25rem;"><?= $unread ?></span>
                    <?php endif; ?>
                  </div>
                  <span class="text-tiny" style="color:var(--neutral-400);">
                    Room <?= htmlspecialchars($conv['room_number'] ?? '') ?> &middot; <?= htmlspecialchars($conv['room_type'] ?? '') ?>
                    &middot; <?= htmlspecialchars($conv['check_in_date'] ?? '') ?> to <?= htmlspecialchars($conv['check_out_date'] ?? '') ?>
                  </span>
                </div>
                <span style="color:var(--neutral-300); font-size:1.25rem;">&rarr;</span>
              </a>
            <?php endforeach; ?>
          </div>

        <?php else: ?>
          <?php /* Active conversation - chat view */ ?>
          <div style="display:flex; flex-direction:column; flex:1; min-height:0;">
            <div style="display:flex; align-items:center; gap:var(--space-3); padding-bottom:var(--space-3); border-bottom:1px solid var(--neutral-100);">
              <a href="index.php?route=messages" style="color:var(--neutral-400); text-decoration:none; font-size:1.25rem;">&larr;</a>
              <div>
                <span style="font-weight:600; color:var(--brand-900);"><?= htmlspecialchars($activeConversation['property_name'] ?? 'Front Office') ?></span>
                <span class="text-tiny" style="color:var(--neutral-400); display:block;">
                  Room <?= htmlspecialchars($activeConversation['room_number'] ?? '') ?> &middot; <?= htmlspecialchars($activeConversation['room_type'] ?? '') ?>
                </span>
              </div>
            </div>

            <div id="message-list" style="flex:1; overflow-y:auto; padding:var(--space-3) 0; display:flex; flex-direction:column; gap:var(--space-3);">
              <?php if (empty($messages)): ?>
                <p class="text-small" style="color:var(--neutral-400); text-align:center; padding:var(--space-8);">No messages yet. Send a message to the front office below.</p>
              <?php endif; ?>
              <?php foreach ($messages as $msg): ?>
                <div style="display:flex; <?= $msg['sender_type'] === 'guest' ? 'justify-content:flex-end' : 'justify-content:flex-start' ?>">
                  <div style="max-width:75%; padding:var(--space-3); border-radius:var(--radius-lg); <?= $msg['sender_type'] === 'guest' ? 'background:var(--brand-500); color:#fff; border-bottom-right-radius:0' : 'background:var(--neutral-50); color:var(--neutral-700); border-bottom-left-radius:0' ?>">
                    <p style="font-size:0.875rem; margin:0; white-space:pre-wrap;"><?= htmlspecialchars($msg['message']) ?></p>
                    <span style="font-size:0.625rem; opacity:0.7; display:block; margin-top:var(--space-1);"><?= date('M d, g:i A', strtotime($msg['created_at'])) ?></span>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>

            <form id="message-form" method="POST" action="index.php?route=messages" style="display:flex; gap:var(--space-2); padding-top:var(--space-3); border-top:1px solid var(--neutral-100);">
              <input type="hidden" name="conversation_id" value="<?= (int)$activeConversation['id'] ?>">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
              <textarea name="message" rows="1" required placeholder="Type your message..." style="flex:1; resize:none; padding:var(--space-3); border:1px solid var(--neutral-200); border-radius:var(--radius-sm); font-family:inherit; font-size:0.875rem; outline:none; min-height:2.5rem;" oninput="this.style.height='';this.style.height=this.scrollHeight+'px'"></textarea>
              <button type="submit" class="btn btn-primary" style="white-space:nowrap;">Send</button>
            </form>
          </div>
        <?php endif; ?>

      </div>

    </main>

    <?php if (!empty($guestBookings)): ?>
    <div id="new-msg-modal" class="hidden" style="position:fixed; inset:0; z-index:9999; display:flex; align-items:center; justify-content:center;">
      <div style="position:absolute; inset:0; background:rgba(0,0,0,0.4);" onclick="document.getElementById('new-msg-modal').classList.add('hidden')"></div>
      <div style="position:relative; background:#fff; border-radius:var(--radius-lg); padding:var(--space-6); width:90%; max-width:480px; box-shadow:0 8px 32px rgba(0,0,0,0.15);">
        <button type="button" style="position:absolute; top:var(--space-3); right:var(--space-3); background:none; border:none; font-size:1.25rem; cursor:pointer; color:var(--neutral-400); padding:var(--space-1); line-height:1;" onclick="document.getElementById('new-msg-modal').classList.add('hidden')">&times;</button>
        <form method="POST" action="index.php?route=messages">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
          <div style="display:flex; flex-direction:column; gap:var(--space-4);">
            <h3 class="text-h3">New Message</h3>
            <div>
              <label class="input-label" for="new_booking_id">Booking</label>
              <select name="new_booking_id" id="new_booking_id" required class="input">
                <option value="">-- Select a booking --</option>
                <?php foreach ($guestBookings as $bk): ?>
                  <option value="<?= (int)$bk['id'] ?>">
                    <?= htmlspecialchars($bk['property_name']) ?> - Room <?= htmlspecialchars($bk['room_number']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="input-label" for="new_msg_text">Message</label>
              <textarea name="message" id="new_msg_text" rows="4" required placeholder="How can we help you?" class="input" style="resize:none;"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Send Message</button>
          </div>
        </form>
      </div>
    </div>
    <?php endif; ?>

    <script>
    document.getElementById('message-list')?.lastElementChild?.scrollIntoView();
    </script>

    <?php include __DIR__ . '/../partials/site-footer.php'; ?>
    <?php $items = $navItems ?? []; include __DIR__ . '/../partials/bottom-tab-bar.php'; ?>
    <?php
    $label = 'Front Office';
    $href = '';
    $onClick = "document.getElementById('message-form')?.querySelector('textarea')?.focus()";
    include __DIR__ . '/../partials/sticky-action-bar.php';
    ?>
<?php include __DIR__ . '/../partials/guest-footer.php'; ?>
