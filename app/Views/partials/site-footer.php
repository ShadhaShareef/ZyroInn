<footer class="site-footer">
  <div class="site-footer-inner">
    <div>
      <div class="site-footer-brand">ZyroInn</div>
      <p class="site-footer-about">Curated boutique hotels and unique stays worldwide. Book verified properties with real amenities and personalized service.</p>
      <div class="site-footer-socials">
        <a href="#" class="site-footer-social" aria-label="Facebook">FB</a>
        <a href="#" class="site-footer-social" aria-label="Instagram">IG</a>
        <a href="#" class="site-footer-social" aria-label="Twitter">X</a>
      </div>
    </div>

    <div>
      <h4 class="site-footer-heading">Quick Links</h4>
      <div class="site-footer-links">
        <a href="index.php?route=home" class="site-footer-link">Home</a>
        <a href="index.php?route=search" class="site-footer-link">Browse Stays</a>
        <a href="index.php?route=bookings" class="site-footer-link">My Bookings</a>
        <a href="index.php?route=profile" class="site-footer-link">My Profile</a>
        <a href="index.php?route=messages" class="site-footer-link">Messages</a>
        <a href="<?= BASE_URL ?>/partner/index.php" class="site-footer-link">List Your Property</a>
      </div>
    </div>

    <div>
      <h4 class="site-footer-heading">Support</h4>
      <div class="site-footer-links">
        <a href="#" class="site-footer-link">Help Center</a>
        <a href="#" class="site-footer-link">Cancellation Policy</a>
        <a href="#" class="site-footer-link">Privacy Policy</a>
        <a href="#" class="site-footer-link">Terms of Service</a>
        <a href="#" class="site-footer-link">FAQ</a>
      </div>
    </div>

    <div>
      <h4 class="site-footer-heading">Stay Updated</h4>
      <p style="font-size:0.75rem; color:var(--brand-300); margin:0 0 var(--space-3); line-height:1.5;">Get exclusive deals and travel inspiration.</p>
      <form onsubmit="return false" style="display:flex; gap:var(--space-2);">
        <input type="email" placeholder="Your email" style="flex:1; padding:0.5rem 0.75rem; border-radius:var(--radius-sm); border:1px solid rgba(255,255,255,0.15); background:rgba(255,255,255,0.08); color:#fff; font-size:0.75rem; outline:none;">
        <button type="submit" style="padding:0.5rem 0.75rem; border-radius:var(--radius-sm); border:none; background:var(--brand-500); color:#fff; font-size:0.75rem; font-weight:600; cursor:pointer;">Subscribe</button>
      </form>
      <div style="margin-top:var(--space-4); display:flex; flex-direction:column; gap:var(--space-2);">
        <div style="display:flex; align-items:center; gap:var(--space-2); font-size:0.75rem; color:var(--brand-300);">
          <span style="opacity:0.6;">support@zyroinn.com</span>
        </div>
        <div style="display:flex; align-items:center; gap:var(--space-2); font-size:0.75rem; color:var(--brand-300);">
          <span style="opacity:0.6;">+1 (555) 123-4567</span>
        </div>
        <div style="display:flex; align-items:center; gap:var(--space-2); font-size:0.75rem; color:var(--brand-300);">
          <span style="opacity:0.6;">Available 24/7</span>
        </div>
      </div>
    </div>
  </div>
  <div class="site-footer-copyright">
    &copy; <?= date('Y') ?> ZyroInn. All rights reserved.
  </div>
</footer>