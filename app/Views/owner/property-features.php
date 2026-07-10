<?php
$title = 'Property Features - Owner Console';
$bodyData = 'x-data="toastManager()"';
include __DIR__ . '/../partials/owner-header.php';
?>

  <!-- Toast Notification System -->
  <div class="fixed bottom-5 right-5 z-50 flex flex-col gap-2">
    <template x-for="toast in toasts" :key="toast.id">
      <div 
        x-show="toast.show"
        x-transition:enter="transition ease-out duration-300 transform"
        x-transition:enter-start="translate-y-2 opacity-0"
        x-transition:enter-end="translate-y-0 opacity-100"
        x-transition:leave="transition ease-in duration-200 transform"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        :class="toast.type === 'success' ? 'bg-emerald-500 text-white border-emerald-600' : 'bg-rose-500 text-white border-rose-600'"
        class="flex items-center gap-3 rounded-2xl px-5 py-3 shadow-lg border text-sm font-semibold transition"
      >
        <span x-text="toast.type === 'success' ? '✅' : '❌'"></span>
        <span x-text="toast.message"></span>
      </div>
    </template>
  </div>

    <!-- Main Container -->
    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <div class="grid grid-cols-1 gap-8 md:grid-cols-4">
        
        <!-- Sidebar Navigation (using Collapsible Drawer partial) -->
        <div class="md:col-span-1">
          <?php
          $title = $property['name'] ?? 'Property Console';
          $menuItems = [
              ['label' => 'Property Features', 'href' => '#', 'active' => true],
              ['label' => 'Rooms (Mock)', 'href' => '#'],
              ['label' => 'Bookings (Mock)', 'href' => '#'],
          ];
          $actions = [];
          $initialOpen = true;
          include __DIR__ . '/../partials/collapsible-drawer.php';
          ?>
        </div>

        <!-- Content Area -->
        <div class="md:col-span-3">
          <div class="rounded-3xl bg-white p-6 shadow-sm border border-neutral-200">
            <!-- Header -->
            <div class="mb-8">
              <h1 class="text-2xl font-bold text-brand-900">Property Features & Amenities</h1>
              <p class="mt-2 text-sm text-neutral-500">
                Manage the amenities available at <span class="font-semibold text-brand-700"><?= htmlspecialchars($property['name']) ?></span>. 
                Click any chip to toggle the feature instantly. Updates are automatically synced in the background.
              </p>
            </div>

            <!-- Amenities Categories -->
            <div class="space-y-8">
              <?php foreach ($groupedAmenities as $categoryName => $amenities): ?>
                <div class="border-b border-neutral-100 pb-6 last:border-0 last:pb-0">
                  <h3 class="text-sm font-bold uppercase tracking-[0.15em] text-neutral-500 mb-4"><?= htmlspecialchars($categoryName) ?></h3>
                  
                  <div class="flex flex-wrap gap-3">
                    <?php foreach ($amenities as $item): ?>
                      <div class="amenity-toggle-wrapper"
                           data-key="<?= htmlspecialchars($item['key']) ?>"
                           data-enabled="<?= $item['enabled'] ? 'true' : 'false' ?>">
                        <?php
                        $amenity = $item['key'];
                        $enabled = $item['enabled'];
                        $icon = $item['icon'] ?? '';
                        $label = $item['label'];
                        include __DIR__ . '/../partials/amenity-chip.php';
                        ?>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>

          </div>
        </div>

      </div>
    </main>
  </div>

  <script>
    // Toast state management via Alpine.js
    function toastManager() {
      return {
        toasts: [],
        showToast(message, type = 'success') {
          const id = Date.now();
          this.toasts.push({ id, message, type, show: true });
          setTimeout(() => {
            const index = this.toasts.findIndex(t => t.id === id);
            if (index !== -1) {
              this.toasts[index].show = false;
            }
          }, 3000);
        }
      }
    }

    // CSRF token for AJAX requests
    const csrfToken = '<?= AuthService::generateCsrfToken() ?>';

    // AJAX toggle client-side implementation with Optimistic Updates & Rollback
    document.addEventListener('DOMContentLoaded', () => {
      const propertyId = <?= $propertyId ?>;
      
      document.querySelectorAll('.amenity-toggle-wrapper').forEach(wrapper => {
        const button = wrapper.querySelector('button');
        const key = wrapper.getAttribute('data-key');
        
        button.addEventListener('click', () => {
          const isCurrentlyEnabled = wrapper.getAttribute('data-enabled') === 'true';
          const nextState = !isCurrentlyEnabled;
          
          // 1. Optimistic Update (UI updates immediately)
          wrapper.setAttribute('data-enabled', nextState ? 'true' : 'false');
          
          // Re-render button classes optimistically matching amenity-chip.php
          if (nextState) {
            button.className = 'inline-flex items-center gap-2 rounded-pill px-3 py-2 text-sm font-semibold transition bg-brand-50 text-brand-700 border border-brand-100 hover:bg-brand-100 hover:text-brand-700';
          } else {
            button.className = 'inline-flex items-center gap-2 rounded-pill px-3 py-2 text-sm font-semibold transition bg-neutral-100 text-neutral-500 border border-neutral-200 hover:bg-brand-100 hover:text-brand-700';
          }

          // Access Alpine.js app context to trigger toast notifications
          const app = document.body.__x.$data;
          
          // 2. Perform AJAX request
          fetch('<?= BASE_URL ?>/owner/index.php?route=toggle-feature', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({
              property_id: propertyId,
              amenity_key: key,
              enabled: nextState,
              csrf_token: csrfToken
            })
          })
          .then(async response => {
            const data = await response.json();
            if (!response.ok || !data.success) {
              throw new Error(data.error || 'Server error occurred');
            }
            // Success notification
            const label = button.querySelector('span:last-child').textContent;
            app.showToast(`"${label}" updated successfully.`, 'success');
          })
          .catch(err => {
            console.error('Toggle feature failed:', err);
            
            // 3. Rollback State on Failure
            wrapper.setAttribute('data-enabled', isCurrentlyEnabled ? 'true' : 'false');
            
            if (isCurrentlyEnabled) {
              button.className = 'inline-flex items-center gap-2 rounded-pill px-3 py-2 text-sm font-semibold transition bg-brand-50 text-brand-700 border border-brand-100 hover:bg-brand-100 hover:text-brand-700';
            } else {
              button.className = 'inline-flex items-center gap-2 rounded-pill px-3 py-2 text-sm font-semibold transition bg-neutral-100 text-neutral-500 border border-neutral-200 hover:bg-brand-100 hover:text-brand-700';
            }
            
            app.showToast(`Failed to update feature: ${err.message}`, 'error');
          });
        });
      });
    });
  </script>
<?php include __DIR__ . '/../partials/owner-footer.php'; ?>
