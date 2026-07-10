document.addEventListener('alpine:init', () => {
  Alpine.data('bottomSheet', () => ({
    open: false,
    focusableElements: null,
    firstFocusable: null,
    lastFocusable: null,

    openSheet() {
      this.open = true;
      this.$nextTick(() => this.setupFocusTrap());
    },

    close() {
      this.open = false;
    },

    onBackdropClick(event) {
      if (event.target === event.currentTarget) {
        this.close();
      }
    },

    setupFocusTrap() {
      const panel = this.$refs.panel;
      if (!panel) return;
      this.focusableElements = panel.querySelectorAll('a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])');
      this.firstFocusable = this.focusableElements[0];
      this.lastFocusable = this.focusableElements[this.focusableElements.length - 1];
      if (this.firstFocusable) {
        this.firstFocusable.focus();
      }
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
});
