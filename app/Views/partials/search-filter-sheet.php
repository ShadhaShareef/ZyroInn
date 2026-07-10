<?php
require_once __DIR__ . '/../../bootstrap.php';
use App\Services\AmenityService;

$amenityService = new AmenityService();
$filterable = $amenityService->getFilterableAmenities();

try {
    $db = \App\Services\Database::getConnection();
    $stmt = $db->prepare("SELECT DISTINCT city, state FROM properties WHERE status = 'active' ORDER BY city");
    $stmt->execute();
    $locations = $stmt->fetchAll();
} catch (Exception $e) {
    $locations = [];
}

ob_start();
?>
<form id="filter-form" style="display:flex; flex-direction:column; gap:var(--space-5);">
  <div>
    <h4 class="section-subtitle" style="margin-bottom: var(--space-2);">Room Type</h4>
    <div style="display:flex; flex-wrap:wrap; gap:var(--space-2);">
      <?php if (!empty($filterable['Room Type'])):
        foreach ($filterable['Room Type'] as $item): ?>
          <label style="cursor:pointer;">
            <input type="checkbox" name="room_type[]" value="<?= htmlspecialchars($item['key']) ?>" class="sr-only peer">
            <div class="pill-filter"><?= htmlspecialchars($item['label']) ?></div>
          </label>
        <?php endforeach;
      endif; ?>
    </div>
  </div>

  <div>
    <h4 class="section-subtitle" style="margin-bottom: var(--space-2);">Amenities</h4>
    <div style="display:flex; flex-wrap:wrap; gap:var(--space-2);">
      <?php if (!empty($filterable['Amenities'])):
        foreach ($filterable['Amenities'] as $item): ?>
          <label style="cursor:pointer;">
            <input type="checkbox" name="amenities[]" value="<?= htmlspecialchars($item['key']) ?>" class="sr-only peer">
            <div class="pill-filter"><?= htmlspecialchars($item['label']) ?></div>
          </label>
        <?php endforeach;
      endif; ?>
    </div>
  </div>

  <div x-data="priceSlider()" x-init="init()">
    <h4 class="section-subtitle" style="margin-bottom: var(--space-2);">Price (Per Night)</h4>
    <div style="position: relative; height: 0.5rem; background: var(--neutral-200); border-radius: 9999px; margin-bottom: 1.5rem; margin-top: 1rem;">
      <div style="background: var(--brand-500); border-radius: 9999px; position: absolute; height: 100%;" :style="{ left: minPercent + '%', right: (100 - maxPercent) + '%' }"></div>
      <input type="range" min="0" max="500" step="10" @input="updateMin($event)" style="position: absolute; appearance: none; -webkit-appearance: none; z-index: 20; height: 0.5rem; width: 100%; opacity: 0; cursor: pointer;" aria-label="Minimum price">
      <input type="range" min="0" max="500" step="10" @input="updateMax($event)" style="position: absolute; appearance: none; -webkit-appearance: none; z-index: 20; height: 0.5rem; width: 100%; opacity: 0; cursor: pointer;" aria-label="Maximum price">
      <div style="position: absolute; width: 1.25rem; height: 1.25rem; background: #fff; border: 2px solid var(--brand-500); border-radius: 9999px; top: -0.375rem; pointer-events: none; box-shadow: 0 1px 3px rgba(0,0,0,0.15);" :style="{ left: 'calc(' + minPercent + '% - 10px)' }"></div>
      <div style="position: absolute; width: 1.25rem; height: 1.25rem; background: #fff; border: 2px solid var(--brand-500); border-radius: 9999px; top: -0.375rem; pointer-events: none; box-shadow: 0 1px 3px rgba(0,0,0,0.15);" :style="{ left: 'calc(' + maxPercent + '% - 10px)' }"></div>
    </div>
    <input type="hidden" name="price_min" :value="minVal">
    <input type="hidden" name="price_max" :value="maxVal">
    <div style="display: flex; justify-content: space-between; font-size: 0.75rem; font-weight: 600; color: var(--neutral-500);">
      <div>Min: $<span x-text="minVal"></span></div>
      <div>Max: $<span x-text="maxVal"></span></div>
    </div>
  </div>

  <div>
    <h4 class="section-subtitle" style="margin-bottom: var(--space-2);">Property Type</h4>
    <div style="display:flex; flex-wrap:wrap; gap:var(--space-2);">
      <?php
      $propertyTypes = ['resort' => 'Resort', 'homestay' => 'Homestay', 'lodge' => 'Lodge', 'budget' => 'Budget', 'luxury' => 'Luxury'];
      foreach ($propertyTypes as $val => $label):
      ?>
        <label style="cursor:pointer;">
          <input type="checkbox" name="property_type[]" value="<?= htmlspecialchars($val) ?>" class="sr-only peer">
          <div class="pill-filter"><?= htmlspecialchars($label) ?></div>
        </label>
      <?php endforeach; ?>
    </div>
  </div>

  <div>
    <h4 class="section-subtitle" style="margin-bottom: var(--space-2);">Location</h4>
    <select name="location" id="location-select" class="input">
      <option value="">All Locations</option>
      <?php foreach ($locations as $loc):
        $locStr = "{$loc['city']}, {$loc['state']}";
      ?>
        <option value="<?= htmlspecialchars($loc['city']) ?>"><?= htmlspecialchars($locStr) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
</form>
<?php
$contentHtml = ob_get_clean();

$sheetId = 'guest-search-filter-sheet';
$triggerLabel = 'Filters';
$title = 'Filter Search Results';
$description = 'Select room type, amenities, price, property type, and location to refine your search.';
$footerActions = [
    ['label' => 'Apply Filters', 'onClick' => 'window.applyFilters()'],
    ['label' => 'Clear All Filters', 'onClick' => 'window.clearFilters()']
];

include __DIR__ . '/bottom-sheet.php';
?>