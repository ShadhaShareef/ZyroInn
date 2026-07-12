<?php
$title = 'About Us - ZyroInn';
$metaDescription = 'Learn about ZyroInn — a curated platform connecting travelers with handpicked boutique hotels and unique stays worldwide.';
$badge = 'Guest Portal';
include __DIR__ . '/../partials/guest-header.php';
?>
    <section class="hero">
      <div class="hero-bg">
        <img src="https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1200" alt="Boutique hotel lobby with warm lighting">
      </div>
      <div class="hero-overlay"></div>
      <div class="hero-content">
        <h1 class="hero-title">About ZyroInn</h1>
        <p class="hero-subtitle">We connect travelers with authentic, handpicked boutique stays — and help property owners grow their business.</p>
      </div>
    </section>

    <main class="main-content">
      <section class="card p-8 reveal">
        <h2 class="section-title mb-1">Our Story</h2>
        <p class="section-subtitle mb-5">Built for travelers who value authenticity over sameness.</p>
        <div class="space-y-4 text-sm text-neutral-600 leading-relaxed max-w-3xl">
          <p>ZyroInn was founded on a simple belief: the best travel experiences happen in places with character. Not cookie-cutter chain hotels, but boutique properties run by people who care deeply about hospitality.</p>
          <p>We built ZyroInn to bridge the gap between travelers seeking authentic stays and independent property owners who want to showcase their unique spaces. Every property on our platform is reviewed for quality, verified for accuracy, and listed with real amenities — so what you see is what you get.</p>
          <p>Today, ZyroInn partners with boutique hotels, bed & breakfasts, vacation rentals, and unique stays across multiple locations. Our platform handles everything from discovery and booking to check-in and support, making the experience seamless for both guests and property owners.</p>
        </div>
      </section>

      <section class="card p-8 reveal">
        <h2 class="section-title mb-1">What We Stand For</h2>
        <div class="mt-6 grid gap-6 sm:grid-cols-2">
          <div class="rounded-2xl border border-brand-100 bg-brand-50 p-5">
            <h3 class="text-base font-bold text-brand-800">&#10003; Verified Quality</h3>
            <p class="mt-1 text-sm text-neutral-600">Every property is reviewed before listing. Amenities are verified, photos are checked, and quality standards are maintained.</p>
          </div>
          <div class="rounded-2xl border border-brand-100 bg-brand-50 p-5">
            <h3 class="text-base font-bold text-brand-800">&#10003; Real Transparency</h3>
            <p class="mt-1 text-sm text-neutral-600">No hidden fees, no misleading photos, no bait-and-switch. What you book is exactly what you get — guaranteed.</p>
          </div>
          <div class="rounded-2xl border border-brand-100 bg-brand-50 p-5">
            <h3 class="text-base font-bold text-brand-800">&#10003; Local Character</h3>
            <p class="mt-1 text-sm text-neutral-600">We prioritize independent properties that reflect local culture and architecture. Every stay tells a story.</p>
          </div>
          <div class="rounded-2xl border border-brand-100 bg-brand-50 p-5">
            <h3 class="text-base font-bold text-brand-800">&#10003; Fair Partnership</h3>
            <p class="mt-1 text-sm text-neutral-600">Property owners keep control of their rates, availability, and policies. We provide the tools — they run their business.</p>
          </div>
        </div>
      </section>

      <!-- Partner CTA Card -->
      <section class="card p-8 reveal text-center">
        <div class="rounded-3xl bg-gradient-to-br from-brand-900 to-brand-700 p-8 sm:p-12">
          <h2 class="text-2xl font-bold text-white">Own a Property? List It With Us</h2>
          <p class="mt-3 text-brand-200 max-w-lg mx-auto">Join 50+ property owners who trust ZyroInn. Zero upfront costs, powerful management tools, and dedicated support — get started in minutes.</p>
          <div class="mt-6 flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="<?= BASE_URL ?>/partner/index.php" class="rounded-2xl bg-white px-8 py-3 text-base font-bold text-brand-700 hover:bg-brand-50 transition shadow-lg">List Your Property</a>
            <a href="<?= BASE_URL ?>/partner/index.php?route=status" class="rounded-2xl border border-white/30 px-8 py-3 text-base font-bold text-white hover:bg-white/10 transition">Check Application Status</a>
          </div>
          <p class="mt-4 text-xs text-brand-300">No credit card required. Set up takes less than 10 minutes.</p>
        </div>
      </section>
    </main>
<?php
include __DIR__ . '/../partials/site-footer.php';
include __DIR__ . '/../partials/bottom-tab-bar.php';
include __DIR__ . '/../partials/guest-footer.php';
?>
