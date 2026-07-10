<?php
$property = $property ?? [];
$property = array_merge([
  'id' => 0, 'name' => 'Boutique Property', 'code' => '',
  'address' => '', 'city' => '', 'state' => '', 'country' => '',
  'postal_code' => '', 'description' => 'Stay details will appear here once the property is available.',
  'enabled_amenities' => [], 'rooms' => []
], $property);
$reviewsResult = $reviewsResult ?? null;
$ratingSummary = $ratingSummary ?? null;
$ratingDistribution = $ratingDistribution ?? null;
$canReview = $canReview ?? false;
$hasReviewed = $hasReviewed ?? false;
?>
<?php
$title = htmlspecialchars($property['name'] ?? 'Boutique Property') . ' - ZyroInn';
$metaDescription = htmlspecialchars(substr($property['description'] ?? 'Book ' . ($property['name'] ?? 'this boutique property') . ' on ZyroInn. View rooms, amenities, and guest reviews.', 0, 160));
$badge = 'Property Details';
include __DIR__ . '/../partials/guest-header.php';
?>

    <main class="main-content">

      <section class="card" style="overflow:hidden; padding:0;">
        <div class="gallery-grid">
          <div class="gallery-main" style="background:linear-gradient(135deg,var(--brand-100),var(--neutral-50)); display:flex; align-items:center; justify-content:center;">
            <span style="font-size:4rem; opacity:0.15; font-weight:700; color:var(--brand-500);">H</span>
            <div style="position:absolute; bottom:1rem; left:1rem; display:flex; gap:0.5rem; z-index:2;">
              <span class="pill"><?= htmlspecialchars($property['city']) ?></span>
              <span class="pill"><?= htmlspecialchars(ucfirst($property['property_type'] ?? 'property')) ?></span>
            </div>
          </div>
        </div>
      </section>

      <section class="card">
        <div class="p-6">
          <div class="flex items-center gap-2 mb-3">
            <span class="pill pill-brand"><?= htmlspecialchars(ucfirst($property['property_type'] ?? 'property')) ?></span>
            <span class="text-tiny" style="color:var(--neutral-400);">Code: <?= htmlspecialchars($property['code']) ?></span>
          </div>

          <div class="flex items-start justify-between gap-4">
            <div>
              <h1 class="text-h2 mb-1"><?= htmlspecialchars($property['name']) ?></h1>
              <p class="text-small" style="color:var(--neutral-500);"><?= htmlspecialchars($property['address'] . ', ' . $property['city'] . ', ' . $property['state'] . ', ' . $property['country'] . ' ' . $property['postal_code']) ?></p>
            </div>
            <a href="index.php?route=property-summary&property_id=<?= (int)$property['id'] ?>"
               class="btn btn-ghost btn-sm" style="white-space:nowrap;">
              Summary
            </a>
          </div>

          <hr class="divider">

          <div>
            <h3 class="section-subtitle">About The Property</h3>
            <p class="text-small" style="color:var(--neutral-500);"><?= htmlspecialchars($property['description']) ?></p>
          </div>

          <hr class="divider">

          <div>
            <h3 class="section-subtitle">Property Amenities</h3>
            <?php
            $amenities = $property['enabled_amenities'];
            if (!empty($amenities)):
            ?>
              <div style="display:flex; flex-direction:column; gap:var(--space-4);">
                <?php foreach ($amenities as $category => $items): ?>
                  <div>
                    <h4 class="text-tiny" style="color:var(--brand-700); margin-bottom:var(--space-2);"><?= htmlspecialchars($category) ?></h4>
                    <?php include __DIR__ . '/../partials/amenity-badges.php'; ?>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <p class="text-small" style="color:var(--neutral-400); font-style:italic;">No property-level services registered.</p>
            <?php endif; ?>
          </div>
        </div>
      </section>

      <section id="rooms-list">
        <h2 class="section-title">Select Your Room</h2>
        <p class="section-subtitle">Choose from available layouts in this property.</p>

        <?php if (empty($property['rooms'])): ?>
          <div class="empty-state">
            <h3 class="empty-state-title">No available rooms</h3>
            <p class="empty-state-text">Check back later or try a different property.</p>
          </div>
        <?php else: ?>
          <div style="display:grid; grid-template-columns:repeat(1,1fr); gap:var(--space-6);">
            <?php foreach ($property['rooms'] as $room):
              include __DIR__ . '/../partials/card-room.php';
            endforeach; ?>
          </div>
        <?php endif; ?>

        <div class="mt-6 text-center">
          <a href="index.php?route=group-book&property_id=<?= (int)$property['id'] ?>" class="btn btn-ghost">
            Book Multiple Rooms →
          </a>
        </div>
      </section>

      <?php if ($property['id'] > 0 && $reviewsResult !== null):
        $reviewsData = $reviewsResult;
      ?>
      <section class="card" x-data="reviewSection(<?= (int)$property['id'] ?>, <?= (int)$canReview ?>, <?= (int)$hasReviewed ?>)">
        <div class="p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-h3 mb-0">Guest Reviews</h3>
            <div class="flex items-center gap-2">
              <span class="text-h4" style="color:var(--brand-600); margin:0;"><?= number_format($ratingSummary['average'], 1) ?></span>
              <span class="text-xs" style="color:#f59e0b;"><?= str_repeat('★', max(0, min(5, round($ratingSummary['average'])))) . str_repeat('☆', max(0, 5 - round($ratingSummary['average']))) ?></span>
              <span class="text-small" style="color:var(--neutral-400);">(<?= $ratingSummary['count'] ?> reviews)</span>
            </div>
          </div>

          <?php if ($ratingSummary['count'] > 0): ?>
          <div class="space-y-1 mb-4">
            <?php for ($star = 5; $star >= 1; $star--): ?>
              <div class="flex items-center gap-2 text-xs">
                <span style="width:1.5rem; text-align:right; color:var(--neutral-500);"><?= $star ?>★</span>
                <div style="flex:1; height:0.5rem; background:var(--neutral-100); border-radius:9999px; overflow:hidden;">
                  <?php $pct = $ratingSummary['count'] > 0 ? round(($ratingDistribution[$star] / $ratingSummary['count']) * 100) : 0; ?>
                  <div class="h-full" style="background:#f59e0b; border-radius:9999px; width:<?= $pct ?>%"></div>
                </div>
                <span style="width:1.5rem; color:var(--neutral-500);"><?= $ratingDistribution[$star] ?></span>
              </div>
            <?php endfor; ?>
          </div>
          <?php endif; ?>

          <div id="reviews-list" class="space-y-4 mb-4">
            <template x-for="review in reviews" :key="review.id">
              <div style="padding:var(--space-4); background:var(--neutral-50); border:1px solid var(--neutral-100); border-radius:var(--radius-sm);">
                <div class="flex items-center justify-between mb-1">
                  <span class="text-small" style="font-weight:700; color:var(--brand-900);" x-text="review.first_name + ' ' + review.last_name"></span>
                  <span class="text-xs" style="color:#f59e0b;" x-text="'★'.repeat(review.rating) + '☆'.repeat(5-review.rating) + ' ' + review.rating + '.0'"></span>
                </div>
                <p class="text-small" style="font-weight:600; color:var(--brand-900); margin-top:var(--space-1);" x-text="review.title" x-show="review.title"></p>
                <p class="text-small" style="color:var(--neutral-500); margin-top:var(--space-1);" x-text="review.body" x-show="review.body"></p>
                <p class="text-tiny" style="color:var(--neutral-400); margin-top:var(--space-2);" x-text="new Date(review.review_date).toLocaleDateString('en-US', {year: 'numeric', month: 'short', day: 'numeric'})"></p>
              </div>
            </template>
          </div>

          <div x-show="page < totalPages" class="text-center mb-4">
            <button type="button" @click="loadMore()" x-text="loading ? 'Loading...' : 'Load More Reviews'" class="btn btn-ghost btn-sm"></button>
          </div>

          <div x-show="canReview && !hasReviewed" style="border-top:1px solid var(--neutral-100); padding-top:var(--space-4);">
            <h4 class="section-subtitle mb-3">Write a Review</h4>
            <form @submit.prevent="submitReview()" class="space-y-3">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

              <div>
                <label class="input-label">Rating</label>
                <div class="flex gap-1">
                  <template x-for="star in 5" :key="star">
                    <button type="button" @click="newRating = star" class="text-xl focus:outline-none transition"
                      :class="star <= newRating ? 'text-yellow-500' : 'text-neutral-200'">★</button>
                  </template>
                </div>
              </div>
              <div><input type="text" x-model="newTitle" placeholder="Review title (optional)" class="input"></div>
              <div><textarea x-model="newBody" placeholder="Share your experience..." rows="3" class="input"></textarea></div>
              <button type="submit" x-text="submitting ? 'Submitting...' : 'Submit Review'" :disabled="submitting"
                class="btn btn-primary" :class="submitting ? 'opacity-50 cursor-not-allowed' : ''">
              </button>
              <p x-show="reviewError" x-text="reviewError" style="color:var(--error); font-size:0.8125rem; margin-top:var(--space-1);"></p>
              <p x-show="reviewSuccess" x-text="reviewSuccess" style="color:var(--success); font-size:0.8125rem; margin-top:var(--space-1);"></p>
            </form>
          </div>

          <div x-show="hasReviewed" style="border-top:1px solid var(--neutral-100); padding-top:var(--space-4); text-align:center;">
            <p class="text-small" style="color:var(--neutral-500);">You have already reviewed this property. Thank you for your feedback!</p>
          </div>
        </div>
      </section>
      <?php endif; ?>

    </main>

    <?php include __DIR__ . '/../partials/site-footer.php'; ?>
    <?php
    $items = $navItems ?? [];
    include __DIR__ . '/../partials/bottom-tab-bar.php';
    ?>
    <?php
    $label = 'Select a Room';
    $onClick = "document.getElementById('rooms-list').scrollIntoView({behavior: 'smooth'})";
    include __DIR__ . '/../partials/sticky-action-bar.php';
    ?>
<?php include __DIR__ . '/../partials/guest-footer.php'; ?>