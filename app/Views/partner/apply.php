<?php
$title = 'Apply to List Your Property - ZyroInn Partners';
include __DIR__ . '/../partials/partner-header.php';

$propertyTypes = ['Boutique Hotel', 'Bed & Breakfast', 'Vacation Rental', 'Apartment', 'Guest House', 'Villa', 'Cabin', 'Hostel', 'Resort', 'Inn'];
$errorMsg = $errorMsg ?? '';
$formData = $formData ?? [];
$selectedAmenities = $formData['amenities'] ?? [];

// Build initial Alpine data as JSON
$alpineInit = json_encode([
    'property_name'  => $formData['property_name'] ?? '',
    'property_type'  => $formData['property_type'] ?? '',
    'room_count'     => $formData['room_count'] ?? '',
    'address'        => $formData['address'] ?? '',
    'city'           => $formData['city'] ?? '',
    'state'          => $formData['state'] ?? '',
    'country'        => $formData['country'] ?? 'US',
    'postal_code'    => $formData['postal_code'] ?? '',
    'contact_name'   => $formData['contact_name'] ?? '',
    'contact_email'  => $formData['contact_email'] ?? '',
    'contact_phone'  => $formData['contact_phone'] ?? '',
    'description'    => $formData['description'] ?? '',
    'amenities'      => $selectedAmenities,
]);
?>
<script>window.__partnerForm = <?= $alpineInit ?>;</script>
<div class="main-content-narrow" x-data="{
  step: 1,
  formData: window.__partnerForm || {},
  documentNames: [],
  errors: {},
  submitted: false,

  validateStep(s) {
    this.errors = {};
    if (s === 1) {
      if (!this.formData.property_name.trim()) this.errors.property_name = 'Property name is required';
      if (!this.formData.property_type) this.errors.property_type = 'Select a property type';
      if (!this.formData.room_count || parseInt(this.formData.room_count) < 1) this.errors.room_count = 'Enter a valid room count';
      if (!this.formData.city.trim()) this.errors.city = 'City is required';
      if (!this.formData.country) this.errors.country = 'Country is required';
    }
    if (s === 2) {
      if (!this.formData.contact_name.trim()) this.errors.contact_name = 'Contact name is required';
      if (!this.formData.contact_email.trim()) this.errors.contact_email = 'Email is required';
      else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.formData.contact_email)) this.errors.contact_email = 'Invalid email format';
    }
    if (s === 5 && this.submitted) {
      if (!this.formData.property_name.trim()) this.errors.property_name = 'Required';
      if (!this.formData.contact_name.trim()) this.errors.contact_name = 'Required';
      if (!this.formData.contact_email.trim()) this.errors.contact_email = 'Required';
    }
    return Object.keys(this.errors).length === 0;
  },

  nextStep() {
    if (this.validateStep(this.step)) {
      this.step = Math.min(this.step + 1, 5);
    }
  },

  prevStep() {
    this.step = Math.max(this.step - 1, 1);
  },

  toggleAmenity(key) {
    const idx = this.formData.amenities.indexOf(key);
    if (idx === -1) this.formData.amenities.push(key);
    else this.formData.amenities.splice(idx, 1);
  }
}">
  <div class="text-center reveal">
    <p class="section-subtitle">Partner Application</p>
    <h1 class="section-title text-h2">List Your Property</h1>
    <p class="text-small" style="margin-top:0.375rem; color:var(--neutral-500);">Complete the form below and our team will review your application within 2 business days.</p>
  </div>

  <?php if ($errorMsg): ?>
    <div class="alert alert-error"><?= htmlspecialchars($errorMsg) ?></div>
  <?php endif; ?>

  <!-- Progress Indicator -->
  <div class="booking-steps">
    <div class="booking-steps-track">
      <div class="booking-steps-progress" :style="'width: ' + ((step - 1) / 4 * 100) + '%'"></div>
      <?php $steps = ['Property', 'Contact', 'Features', 'Docs', 'Review'];
      foreach ($steps as $i => $label): ?>
        <div class="booking-step">
          <div class="booking-step-circle" :class="step > <?= $i + 1 ?> ? 'booking-step-completed' : (step === <?= $i + 1 ?> ? 'booking-step-active' : '')">
            <span x-show="step > <?= $i + 1 ?>">&#10003;</span>
            <span x-show="step <= <?= $i + 1 ?>"><?= $i + 1 ?></span>
          </div>
          <span class="booking-step-label" :class="step > <?= $i + 1 ?> ? 'booking-step-label-completed' : (step >= <?= $i + 1 ?> ? 'booking-step-label-active' : 'booking-step-label-pending')"><?= htmlspecialchars($label) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Form -->
  <form method="POST" action="index.php?route=apply" enctype="multipart/form-data" @submit.prevent="submitted = true; if (validateStep(5)) $el.submit()" novalidate>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

    <!-- Step 1: Property Details -->
    <div x-show="step === 1" class="card p-6 space-y-5">
      <div>
        <h2 class="text-h3" style="color:var(--brand-900);">Property Details</h2>
        <p class="text-small" style="color:var(--neutral-500); margin-top:0.25rem;">Tell us about your property.</p>
      </div>
      <label class="block">
        <span class="input-label">Property Name <span style="color:var(--error);">*</span></span>
        <input type="text" name="property_name" x-model="formData.property_name" class="input">
        <template x-if="errors.property_name"><p class="text-tiny" style="color:var(--error); margin-top:0.25rem;" x-text="errors.property_name"></p></template>
      </label>
      <div class="grid gap-4 sm:grid-cols-2">
        <label class="block">
          <span class="input-label">Property Type <span style="color:var(--error);">*</span></span>
          <input type="text" name="property_type" x-model="formData.property_type" list="property-types" class="input" placeholder="e.g. Boutique Hotel">
          <datalist id="property-types">
            <?php foreach ($propertyTypes as $pt): ?>
              <option value="<?= htmlspecialchars($pt) ?>">
            <?php endforeach; ?>
          </datalist>
          <template x-if="errors.property_type"><p class="text-tiny" style="color:var(--error); margin-top:0.25rem;" x-text="errors.property_type"></p></template>
        </label>
        <label class="block">
          <span class="input-label">Number of Rooms <span style="color:var(--error);">*</span></span>
          <input type="number" name="room_count" x-model="formData.room_count" min="1" class="input">
          <template x-if="errors.room_count"><p class="text-tiny" style="color:var(--error); margin-top:0.25rem;" x-text="errors.room_count"></p></template>
        </label>
      </div>
      <label class="block">
        <span class="input-label">Address</span>
        <input type="text" name="address" x-model="formData.address" class="input" placeholder="Street address">
      </label>
      <div class="grid gap-4 sm:grid-cols-2">
        <label class="block">
          <span class="input-label">City <span style="color:var(--error);">*</span></span>
          <input type="text" name="city" x-model="formData.city" class="input">
          <template x-if="errors.city"><p class="text-tiny" style="color:var(--error); margin-top:0.25rem;" x-text="errors.city"></p></template>
        </label>
        <label class="block">
          <span class="input-label">State</span>
          <input type="text" name="state" x-model="formData.state" class="input">
        </label>
      </div>
      <div class="grid gap-4 sm:grid-cols-2">
        <label class="block">
          <span class="input-label">Country <span style="color:var(--error);">*</span></span>
          <select name="country" x-model="formData.country" class="input">
            <option value="US">United States</option>
            <option value="CA">Canada</option>
            <option value="GB">United Kingdom</option>
            <option value="AU">Australia</option>
            <option value="IN">India</option>
            <option value="Other">Other</option>
          </select>
          <template x-if="errors.country"><p class="text-tiny" style="color:var(--error); margin-top:0.25rem;" x-text="errors.country"></p></template>
        </label>
        <label class="block">
          <span class="input-label">Postal Code</span>
          <input type="text" name="postal_code" x-model="formData.postal_code" class="input">
        </label>
      </div>
      <label class="block">
        <span class="input-label">Property Description</span>
        <textarea name="description" x-model="formData.description" rows="3" class="input" placeholder="Tell guests about your property &mdash; location highlights, unique features, etc."></textarea>
      </label>
      <div class="flex justify-end pt-2">
        <button type="button" @click="nextStep()" class="btn btn-primary">Continue</button>
      </div>
    </div>

    <!-- Step 2: Contact Info -->
    <div x-show="step === 2" class="card p-6 space-y-5">
      <div>
        <h2 class="text-h3" style="color:var(--brand-900);">Owner Contact Information</h2>
        <p class="text-small" style="color:var(--neutral-500); margin-top:0.25rem;">Who should we contact about this application?</p>
      </div>
      <label class="block">
        <span class="input-label">Full Name <span style="color:var(--error);">*</span></span>
        <input type="text" name="contact_name" x-model="formData.contact_name" class="input">
        <template x-if="errors.contact_name"><p class="text-tiny" style="color:var(--error); margin-top:0.25rem;" x-text="errors.contact_name"></p></template>
      </label>
      <div class="grid gap-4 sm:grid-cols-2">
        <label class="block">
          <span class="input-label">Email Address <span style="color:var(--error);">*</span></span>
          <input type="email" name="contact_email" x-model="formData.contact_email" class="input">
          <template x-if="errors.contact_email"><p class="text-tiny" style="color:var(--error); margin-top:0.25rem;" x-text="errors.contact_email"></p></template>
        </label>
        <label class="block">
          <span class="input-label">Phone Number</span>
          <input type="tel" name="contact_phone" x-model="formData.contact_phone" class="input" placeholder="+1 (555) 000-0000">
        </label>
      </div>
      <div class="flex justify-between pt-2">
        <button type="button" @click="prevStep()" class="btn btn-secondary">Back</button>
        <button type="button" @click="nextStep()" class="btn btn-primary">Continue</button>
      </div>
    </div>

    <!-- Step 3: Property Features -->
    <div x-show="step === 3" class="card p-6 space-y-5">
      <div>
        <h2 class="text-h3" style="color:var(--brand-900);">Property Features</h2>
        <p class="text-small" style="color:var(--neutral-500); margin-top:0.25rem;">Select the amenities and features your property offers. These will be shown to guests when they search for stays.</p>
      </div>
      <?php if (empty($allAmenities)): ?>
        <p class="text-small" style="color:var(--neutral-400);">No amenities configured yet. You can skip this step.</p>
      <?php else: ?>
        <div class="space-y-4" style="max-height:24rem; overflow-y:auto; padding-right:0.5rem;">
          <?php foreach ($allAmenities as $category => $amenities): ?>
            <div>
              <p class="text-tiny" style="color:var(--neutral-500); margin-bottom:0.5rem;"><?= htmlspecialchars(ucfirst($category)) ?></p>
              <div class="flex flex-wrap gap-2">
                <?php foreach ($amenities as $amenity):
                  $key = htmlspecialchars($amenity['key'], ENT_QUOTES, 'UTF-8');
                ?>
                  <label class="amenity-pill" style="cursor:pointer;" :class="formData.amenities.includes('<?= $key ?>') ? 'bg-brand-50 text-brand-700 border-brand-300' : ''">
                    <input type="checkbox" name="amenities[]" value="<?= $key ?>" @change="toggleAmenity('<?= $key ?>')" class="sr-only" <?= in_array($amenity['key'], $selectedAmenities) ? 'checked' : '' ?>>
                    <?php if (!empty($amenity['icon'])): ?><span><?= htmlspecialchars($amenity['icon']) ?></span><?php endif; ?>
                    <span><?= htmlspecialchars($amenity['label']) ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <div class="flex justify-between pt-2">
        <button type="button" @click="prevStep()" class="btn btn-secondary">Back</button>
        <button type="button" @click="nextStep()" class="btn btn-primary">Continue</button>
      </div>
    </div>

    <!-- Step 4: Document Upload -->
    <div x-show="step === 4" class="card p-6 space-y-5">
      <div>
        <h2 class="text-h3" style="color:var(--brand-900);">Document Upload</h2>
        <p class="text-small" style="color:var(--neutral-500); margin-top:0.25rem;">Provide identification and proof of property ownership.</p>
      </div>
      <div class="card p-6 text-center" style="border:2px dashed var(--neutral-200); border-radius:var(--radius-md); cursor:pointer; background:var(--neutral-50);" @click="$refs.docInput.click()" @dragover.prevent @drop.prevent="documentNames = Array.from($event.dataTransfer.files).map(f => f.name)">
        <svg style="margin:0 auto; width:2.5rem; height:2.5rem; color:var(--neutral-300);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
        <p class="text-small" style="font-weight:600; color:var(--neutral-600); margin-top:0.5rem;">Click to upload or drag and drop</p>
        <p class="text-tiny" style="color:var(--neutral-400); margin-top:0.25rem;">ID document, property deed, or tax record (PDF, PNG, JPG &mdash; max 10MB each)</p>
        <input type="file" name="documents[]" multiple accept=".pdf,.png,.jpg,.jpeg" class="hidden" x-ref="docInput" @change="documentNames = Array.from($event.target.files).map(f => f.name)">
      </div>
      <template x-if="documentNames.length > 0">
        <div class="space-y-2">
          <p class="text-tiny" style="font-weight:600; color:var(--neutral-500);">Selected files:</p>
          <template x-for="(name, idx) in documentNames" :key="idx">
            <div style="display:flex; align-items:center; gap:0.5rem; padding:0.5rem 0.75rem; background:var(--neutral-50); border-radius:var(--radius-sm); font-size:0.8125rem; color:var(--neutral-700);">
              <svg style="width:1rem; height:1rem; color:var(--neutral-400); flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
              <span class="flex-1" style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" x-text="name"></span>
            </div>
          </template>
        </div>
      </template>
      <div class="alert alert-warning" style="font-size:0.75rem;">
        <p class="font-semibold">Backend requirement:</p>
        <p>Secure file upload, virus scanning, encrypted storage, and compliance handling must be implemented by the backend team before this feature is active.</p>
      </div>
      <div class="flex justify-between pt-2">
        <button type="button" @click="prevStep()" class="btn btn-secondary">Back</button>
        <button type="button" @click="nextStep()" class="btn btn-primary">Review Application</button>
      </div>
    </div>

    <!-- Step 5: Review & Submit -->
    <div x-show="step === 5" class="card p-6 space-y-5">
      <div>
        <h2 class="text-h3" style="color:var(--brand-900);">Review &amp; Submit</h2>
        <p class="text-small" style="color:var(--neutral-500); margin-top:0.25rem;">Please review your application before submitting.</p>
      </div>
      <div class="summary-rows" style="background:var(--neutral-50); padding:var(--space-4); border-radius:var(--radius-md);">
        <div class="summary-row"><span>Property Name</span><span class="summary-row-value" x-text="formData.property_name || '&mdash;'"></span></div>
        <div class="summary-row"><span>Property Type</span><span class="summary-row-value" x-text="formData.property_type || '&mdash;'"></span></div>
        <div class="summary-row"><span>Rooms</span><span class="summary-row-value" x-text="formData.room_count || '&mdash;'"></span></div>
        <div class="summary-row"><span>Location</span><span class="summary-row-value"><span x-text="formData.city || '&mdash;'"></span><span x-text="formData.state ? ', ' + formData.state : ''"></span></span></div>
        <div class="summary-row"><span>Contact</span><span class="summary-row-value" x-text="formData.contact_name || '&mdash;'"></span></div>
        <div class="summary-row"><span>Email</span><span class="summary-row-value" x-text="formData.contact_email || '&mdash;'"></span></div>
        <div class="summary-row"><span>Phone</span><span class="summary-row-value" x-text="formData.contact_phone || '&mdash;'"></span></div>
        <div class="summary-row"><span>Description</span><span class="summary-row-value" x-text="formData.description || '&mdash;'"></span></div>
      </div>
      <div>
        <span class="input-label">Selected Amenities</span>
        <p class="text-small" style="color:var(--neutral-400);" x-show="formData.amenities.length === 0">None selected</p>
        <div class="flex flex-wrap gap-1 mt-1" x-show="formData.amenities.length > 0">
          <template x-for="key in formData.amenities" :key="key">
            <span class="amenity-pill" style="background:var(--brand-50); color:var(--brand-700); border-color:var(--brand-200);" x-text="key.replace(/_/g, ' ')"></span>
          </template>
        </div>
      </div>
      <div>
        <span class="input-label">Documents</span>
        <p class="text-small" style="color:var(--neutral-700);"><span x-text="documentNames.length || '0'"></span> file(s) selected <span class="text-tiny" style="color:var(--warning);">(flagged &mdash; backend processing required)</span></p>
      </div>
      <template x-if="Object.keys(errors).length > 0">
        <div class="alert alert-error" style="font-size:0.75rem;">
          <p class="font-semibold">Please fix the following before submitting:</p>
          <ul class="mt-1" style="list-style:disc; padding-left:1.25rem;">
            <template x-for="(msg, field) in errors" :key="field">
              <li x-text="msg"></li>
            </template>
          </ul>
        </div>
      </template>
      <div class="flex justify-between pt-2">
        <button type="button" @click="prevStep()" class="btn btn-secondary">Back</button>
        <button type="submit" class="btn btn-primary" style="background:var(--success);">Submit Application</button>
      </div>
    </div>
  </form>
</div>
<?php include __DIR__ . '/../partials/partner-footer.php'; ?>
