<?php
$destinations = $destinations ?? [];
$properties = $properties ?? [];
$title = 'Explore Boutique Hotels & Unique Stays - ZyroInn';
$metaDescription = 'Discover handpicked boutique hotels and unique stays worldwide. Book verified properties with real amenities on ZyroInn.';
$badge = 'Guest Portal';
include __DIR__ . '/../partials/guest-header.php';
?>

    <section class="hero">
      <div class="hero-bg">
        <img src="https://pahome.co.id/wp-content/uploads/2026/03/10.-Soft-Neutral-Color-Palettes-in-Boutique-Hotel-Room-Interiors.jpg" alt="Elegant boutique hotel lobby with warm ambient lighting">
      </div>
      <div class="hero-overlay"></div>
      <div class="hero-content">
        <h1 class="hero-title">Find Your Perfect Boutique Stay</h1>
        <p class="hero-subtitle">Discover handpicked boutique properties with verified amenities, curated for comfort and style.</p>
        <a href="#search-wrapper" class="hero-cta">Start Your Search</a>
      </div>
    </section>

    <div class="hero-stats-bar">
      <div class="hero-stats-grid">
        <div class="hero-stat">
          <div class="hero-stat-number">50+</div>
          <div class="hero-stat-label">Boutique Hotels</div>
        </div>
        <div class="hero-stat">
          <div class="hero-stat-number">200+</div>
          <div class="hero-stat-label">Curated Rooms</div>
        </div>
        <div class="hero-stat">
          <div class="hero-stat-number">1K+</div>
          <div class="hero-stat-label">Happy Guests</div>
        </div>
        <div class="hero-stat">
          <div class="hero-stat-number">4.8</div>
          <div class="hero-stat-label">Avg Rating</div>
        </div>
      </div>
    </div>

    <main class="main-content">

      <section id="search-wrapper" class="card reveal" style="scroll-margin-top:6rem;">
        <div class="p-6">
          <h2 class="section-title mb-1">Find Your Perfect Stay</h2>
          <p class="section-subtitle mb-5">Search by destination, dates, and guests</p>

          <form id="search-form" action="index.php" method="GET">
            <input type="hidden" name="route" value="search">

            <div class="grid-cols-1 gap-4" style="display:grid; grid-template-columns:1fr; gap:var(--space-4);">
              <div>
                <label for="location" class="input-label">Destination</label>
                <select name="location" id="location" class="input">
                  <option value="">Anywhere (Select Location)</option>
                  <?php foreach ($destinations as $dest):
                    $destStr = "{$dest['city']}, {$dest['state']}";
                  ?>
                    <option value="<?= htmlspecialchars($dest['city']) ?>"><?= htmlspecialchars($destStr) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="grid-2 gap-3">
                <div>
                  <label for="check_in" class="input-label">Check-In</label>
                  <input type="date" name="check_in" id="check_in" value="<?= date('Y-m-d') ?>" class="input">
                </div>
                <div>
                  <label for="check_out" class="input-label">Check-Out</label>
                  <input type="date" name="check_out" id="check_out" value="<?= date('Y-m-d', strtotime('+2 days')) ?>" class="input">
                </div>
              </div>

              <div>
                <label for="guests" class="input-label">Guests</label>
                <select name="guests" id="guests" class="input">
                  <option value="1">1 Guest</option>
                  <option value="2">2 Guests</option>
                  <option value="3">3 Guests</option>
                  <option value="4">4 Guests</option>
                </select>
              </div>

              <button type="submit" class="btn btn-primary btn-full">Search Properties</button>
            </div>
          </form>
        </div>
      </section>

      <section>
        <div class="card p-6 reveal" style="max-width:48rem; margin:0 auto;">
          <h2 class="feature-section-title">Why Choose ZyroInn?</h2>
          <p class="feature-section-text">We connect you with handpicked boutique properties that offer authentic local experiences, verified amenities, and personalized service — all in one seamless platform.</p>
          <div class="feature-list reveal-stagger">
            <div class="feature-list-item reveal-item">
              <span class="feature-list-icon">✓</span>
              <span>Verified properties with real guest reviews and ratings</span>
            </div>
            <div class="feature-list-item reveal-item">
              <span class="feature-list-icon">✓</span>
              <span>Curated boutique stays — from intimate lodges to luxury resorts</span>
            </div>
            <div class="feature-list-item reveal-item">
              <span class="feature-list-icon">✓</span>
              <span>Transparent pricing with no hidden fees</span>
            </div>
            <div class="feature-list-item reveal-item">
              <span class="feature-list-icon">✓</span>
              <span>Loyalty rewards that make every stay more rewarding</span>
            </div>
            <div class="feature-list-item reveal-item">
              <span class="feature-list-icon">✓</span>
              <span>24/7 guest support from booking to check-out</span>
            </div>
          </div>
        </div>
      </section>

      <section>
        <h2 class="section-title">Curated Destinations</h2>
        <p class="section-subtitle">Popular places to spend your holiday in style.</p>

        <div class="grid-2 reveal-stagger" style="margin-top:var(--space-7); gap:var(--space-8);">
          <?php foreach ($destinations as $dest):
            $destName = htmlspecialchars($dest['city']);
          ?>
            <a href="index.php?route=search&location=<?= urlencode($dest['city']) ?>"
               class="destination-card group reveal-item">
              <div class="destination-card-overlay"></div>
              <div class="destination-card-body">
                <span class="destination-card-name"><?= $destName ?></span>
                <span class="destination-card-state"><?= htmlspecialchars($dest['state']) ?></span>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      </section>

      <section>
        <h2 class="section-title">Curated Boutique Stays</h2>
        <p class="section-subtitle">Bespoke stays with verified, top-tier accommodations.</p>

        <?php if (empty($properties)): ?>
          <div class="empty-state reveal-scale">
            <div class="empty-state-icon"></div>
            <h3 class="empty-state-title">No properties yet</h3>
            <p class="empty-state-text">Check back soon for curated boutique stays.</p>
          </div>
        <?php else: ?>
          <div class="grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 reveal-stagger" style="display:grid;">
            <?php foreach ($properties as $property):
              include __DIR__ . '/../partials/card-property.php';
            endforeach; ?>
          </div>
        <?php endif; ?>
      </section>

      <section class="cta-section reveal">
        <h2>Ready to Experience Boutique Living?</h2>
        <p>Join thousands of travelers who trust ZyroInn for their curated stays. Start your journey today.</p>
        <a href="index.php?route=search" class="btn btn-primary">Browse All Stays</a>
      </section>

    </main>

    <?php include __DIR__ . '/../partials/site-footer.php'; ?>

    <?php
    $items = $navItems ?? [];
    include __DIR__ . '/../partials/bottom-tab-bar.php';
    ?>

    <?php
    $label = 'Find Boutique Stays';
    $onClick = "document.getElementById('search-form').submit()";
    include __DIR__ . '/../partials/sticky-action-bar.php';
    ?>

<script src="<?= BASE_URL ?>/assets/js/hero-3d.js" defer></script>
<?php include __DIR__ . '/../partials/guest-footer.php'; ?>