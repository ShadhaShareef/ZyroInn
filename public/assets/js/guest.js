/* ZyroInn Guest App — Production UI Interactions v2.0 */

/* --- Page Loader --- */
(function() {
  const loader = document.getElementById('page-loader');
  if (loader) {
    window.addEventListener('load', function() {
      setTimeout(function() {
        loader.classList.add('hidden');
        setTimeout(function() { loader.style.display = 'none'; }, 500);
      }, 400);
    });
    if (document.readyState === 'complete') {
      loader.classList.add('hidden');
      loader.style.display = 'none';
    }
  }
})();

/* --- Scroll Reveal (IntersectionObserver) --- */
(function() {
  const revealEls = document.querySelectorAll('.reveal, .reveal-left, .reveal-right, .reveal-scale, .reveal-stagger');
  if (revealEls.length === 0) return;
  const observer = new IntersectionObserver(function(entries) {
    entries.forEach(function(entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });
  revealEls.forEach(function(el) { observer.observe(el); });
})();

/* --- Sticky Header Shadow on Scroll --- */
(function() {
  const header = document.querySelector('header');
  if (!header) return;
  let ticking = false;
  window.addEventListener('scroll', function() {
    if (!ticking) {
      window.requestAnimationFrame(function() {
        if (window.scrollY > 10) {
          header.style.boxShadow = '0 1px 8px rgba(0,0,0,0.06)';
        } else {
          header.style.boxShadow = 'none';
        }
        ticking = false;
      });
      ticking = true;
    }
  });
})();

/* --- Alpine.js Data Components --- */
document.addEventListener('alpine:init', () => {

  /* --- Bottom Sheet --- */
  Alpine.data('bottomSheet', () => ({
    open: false,
    focusableElements: null,
    firstFocusable: null,
    lastFocusable: null,
    openSheet() {
      this.open = true;
      this.$nextTick(() => this.setupFocusTrap());
      document.body.style.overflow = 'hidden';
    },
    close() {
      this.open = false;
      document.body.style.overflow = '';
    },
    onBackdropClick(event) {
      if (event.target === event.currentTarget) {
        this.close();
      }
    },
    setupFocusTrap() {
      const panel = this.$refs.panel;
      if (!panel) return;
      this.focusableElements = panel.querySelectorAll(
        'a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])'
      );
      this.firstFocusable = this.focusableElements[0];
      this.lastFocusable = this.focusableElements[this.focusableElements.length - 1];
      if (this.firstFocusable) this.firstFocusable.focus();
    },
    trapFocus(event) {
      if (!this.focusableElements || this.focusableElements.length === 0) return;
      if (event.shiftKey) {
        if (document.activeElement === this.firstFocusable) {
          event.preventDefault();
          this.lastFocusable.focus();
        }
      } else {
        if (document.activeElement === this.lastFocusable) {
          event.preventDefault();
          this.firstFocusable.focus();
        }
      }
    },
  }));

  /* --- Price Range Slider --- */
  Alpine.data('priceSlider', () => ({
    minVal: 0,
    maxVal: 500,
    minPercent: 0,
    maxPercent: 100,
    init() {
      const params = new URLSearchParams(window.location.search);
      this.minVal = parseInt(params.get('price_min')) || 0;
      this.maxVal = parseInt(params.get('price_max')) || 500;
      this.updateMin();
      this.updateMax();
    },
    updateMin(event) {
      if (event) this.minVal = Number(event.target.value);
      this.minVal = Math.min(this.minVal, this.maxVal - 10);
      this.minPercent = (this.minVal / 500) * 100;
    },
    updateMax(event) {
      if (event) this.maxVal = Number(event.target.value);
      this.maxVal = Math.max(this.maxVal, this.minVal + 10);
      this.maxPercent = (this.maxVal / 500) * 100;
    }
  }));

  /* --- Load More --- */
  Alpine.data('loadMore', () => ({
    loading: false,
    page: 1,
    totalPages: 1,
    init() {
      this.page = parseInt(document.querySelector('[data-page]')?.dataset.page || 1);
      this.totalPages = parseInt(document.querySelector('[data-total-pages]')?.dataset.totalPages || 1);
    },
    loadMore() {
      if (this.loading || this.page >= this.totalPages) return;
      this.loading = true;
      this.page++;
      const params = new URLSearchParams(window.location.search);
      params.set('page', this.page);
      params.set('ajax', '1');
      fetch('index.php?' + params.toString())
        .then(r => r.text())
        .then(html => {
          this.loading = false;
          if (html.trim()) {
            document.getElementById('properties-container')?.insertAdjacentHTML('beforeend', html);
          }
          if (this.page >= this.totalPages) {
            document.getElementById('load-more-container')?.remove();
          }
        })
        .catch(() => { this.loading = false; });
    }
  }));

  /* --- Review Section --- */
  Alpine.data('reviewSection', (propertyId, canReview, hasReviewed) => ({
    propertyId,
    canReview,
    hasReviewed,
    reviews: [],
    page: 1,
    totalPages: 1,
    loading: false,
    newRating: 5,
    newTitle: '',
    newBody: '',
    submitting: false,
    reviewError: '',
    reviewSuccess: '',
    init() {
      fetch('index.php?route=review&action=list&property_id=' + this.propertyId + '&page=1')
        .then(r => r.json())
        .then(data => {
          this.reviews = data.reviews;
          this.page = data.pagination.page;
          this.totalPages = data.pagination.totalPages;
        })
        .catch(() => {});
    },
    loadMore() {
      if (this.loading || this.page >= this.totalPages) return;
      this.loading = true;
      fetch('index.php?route=review&action=list&property_id=' + this.propertyId + '&page=' + (this.page + 1))
        .then(r => r.json())
        .then(data => {
          this.reviews = this.reviews.concat(data.reviews);
          this.page = data.pagination.page;
          this.totalPages = data.pagination.totalPages;
          this.loading = false;
        })
        .catch(() => { this.loading = false; });
    },
    submitReview() {
      if (this.newRating < 1 || this.newRating > 5) return;
      this.submitting = true;
      this.reviewError = '';
      this.reviewSuccess = '';
      const fd = new FormData();
      fd.append('property_id', this.propertyId);
      fd.append('rating', this.newRating);
      fd.append('title', this.newTitle);
      fd.append('body', this.newBody);
      fd.append('csrf_token', document.querySelector('[name="csrf_token"]')?.value || '');
      fetch('index.php?route=review&action=create', { method: 'POST', body: new URLSearchParams(fd) })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            this.reviewSuccess = 'Review submitted!';
            this.newRating = 5; this.newTitle = ''; this.newBody = '';
            this.hasReviewed = true;
            fetch('index.php?route=review&action=list&property_id=' + this.propertyId + '&page=1')
              .then(r => r.json())
              .then(data => { this.reviews = data.reviews; this.page = 1; this.totalPages = data.pagination.totalPages; });
          } else {
            this.reviewError = data.error || 'Failed to submit.';
          }
          this.submitting = false;
        })
        .catch(() => { this.reviewError = 'Network error.'; this.submitting = false; });
    }
  }));

  /* --- Loyalty Redemption --- */
  Alpine.data('loyaltyRedemption', (availablePoints, totalAmount) => ({
    redeemPoints: 0,
    dollarValue: 0,
    applied: false,
    redeemError: '',
    applyRedemption() {
      const pts = parseInt(this.redeemPoints);
      if (isNaN(pts) || pts <= 0) { this.redeemError = 'Enter valid points.'; return; }
      if (pts > availablePoints) { this.redeemError = 'Only ' + availablePoints.toLocaleString() + ' points available.'; return; }
      const dv = pts / 100;
      if (dv > totalAmount) { this.redeemError = 'Value exceeds total.'; return; }
      this.dollarValue = dv;
      this.applied = true;
      this.redeemError = '';
      const el = document.getElementById('total-amount');
      if (el) el.textContent = '$' + (totalAmount - dv).toFixed(2);
    }
  }));
});

/* --- Search page utilities --- */
window.applySort = function(val) {
  const p = new URLSearchParams(window.location.search);
  p.set('sort', val);
  window.location.search = p.toString();
};

window.applyFilters = function() {
  const form = document.getElementById('filter-form');
  if (!form) return;
  const fd = new FormData(form);
  const p = new URLSearchParams();
  p.set('route', 'search');
  for (let [k, v] of fd.entries()) { if (v) p.append(k, v); }
  const loc = document.getElementById('location-select');
  if (loc?.value) p.set('location', loc.value);
  else { const u = new URLSearchParams(window.location.search); if (u.get('location')) p.set('location', u.get('location')); }
  const u = new URLSearchParams(window.location.search);
  if (u.get('sort')) p.set('sort', u.get('sort'));
  window.location.search = p.toString();
};

window.clearFilters = function() {
  window.location.href = 'index.php?route=search';
};

window.openFilterSheet = function() {
  document.querySelector('[x-data="bottomSheet()"] button')?.click();
};

window.toggleMapView = function() {
  const c = document.getElementById('map-container');
  const pc = document.getElementById('properties-container');
  const lm = document.getElementById('load-more-container');
  const lbl = document.getElementById('map-view-label');
  if (!c) return;
  const hidden = c.classList.contains('hidden');
  c.classList.toggle('hidden');
  pc?.classList.toggle('hidden');
  lm?.classList.toggle('hidden');
  lbl.textContent = hidden ? 'List View' : 'Map View';
  if (hidden) window.loadGoogleMaps?.();
};