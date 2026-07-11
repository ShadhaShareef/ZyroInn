<?php
$route = $route ?? 'expenses';
$propertyName = $property['name'] ?? 'Property Console';
$propertyOptions = $propertyOptions ?? [];
$propertyId = $propertyId ?? 0;
$expenses = $expenses ?? [];
$expenseSummary = $expenseSummary ?? ['monthly_spend' => 0, 'pending_count' => 0, 'pending_amount' => 0];
$csrfToken = $csrfToken ?? '';
$title = 'Expenses';

include __DIR__ . '/../partials/owner-header.php';
?>
<div class="space-y-6" x-data="expenseManager()">
  <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
    <div>
      <p class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-600">Expenses</p>
      <h1 class="mt-1 text-2xl font-semibold text-brand-900">Track spending and approvals</h1>
      <p class="mt-2 text-sm text-neutral-500">Review recurring costs, upcoming invoices, and budget health.</p>
    </div>
    <button @click="openForm()" class="inline-flex items-center gap-2 rounded-2xl bg-brand-600 px-5 py-3 text-sm font-semibold text-white hover:bg-brand-700 transition shadow-sm">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
      Create expense
    </button>
  </div>

  <!-- Summary Cards -->
  <div class="grid gap-4 md:grid-cols-3">
    <div class="rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm">
      <p class="text-xs font-semibold uppercase tracking-[0.2em] text-neutral-400">Monthly spend</p>
      <p class="mt-2 text-2xl font-semibold text-brand-900">$<?= number_format((float)($expenseSummary['monthly_spend'] ?? 0), 2) ?></p>
    </div>
    <div class="rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm">
      <p class="text-xs font-semibold uppercase tracking-[0.2em] text-neutral-400">Pending approvals</p>
      <p class="mt-2 text-2xl font-semibold text-amber-600"><?= (int)($expenseSummary['pending_count'] ?? 0) ?></p>
    </div>
    <div class="rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm">
      <p class="text-xs font-semibold uppercase tracking-[0.2em] text-neutral-400">Pending amount</p>
      <p class="mt-2 text-2xl font-semibold text-brand-900">$<?= number_format((float)($expenseSummary['pending_amount'] ?? 0), 2) ?></p>
    </div>
  </div>

  <!-- Expense List -->
  <section class="rounded-3xl border border-neutral-200 bg-white p-6 shadow-sm">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-brand-900">Recent expenses</h2>
        <p class="mt-1 text-sm text-neutral-500">Latest purchases and approvals.</p>
      </div>
    </div>
    <div class="mt-6 space-y-3">
      <template x-if="expenses.length === 0">
        <div class="rounded-2xl border border-neutral-200 bg-neutral-50 p-8 text-center">
          <p class="text-sm font-semibold text-neutral-500">No expenses recorded yet.</p>
          <p class="mt-1 text-sm text-neutral-400">Create your first expense to start tracking.</p>
        </div>
      </template>
      <template x-for="e in expenses" :key="e.id">
        <article class="flex flex-col gap-3 rounded-2xl border border-neutral-200 bg-neutral-50 p-4 md:flex-row md:items-center md:justify-between">
          <div class="flex-1">
            <div class="flex items-center gap-2">
              <p class="text-sm font-semibold text-brand-900" x-text="e.title"></p>
              <span class="rounded-pill px-2 py-0.5 text-[10px] font-semibold"
                    :class="e.status === 'approved' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : e.status === 'rejected' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-amber-50 text-amber-700 border border-amber-200'"
                    x-text="e.status.charAt(0).toUpperCase() + e.status.slice(1)"></span>
            </div>
            <p class="mt-1 text-sm text-neutral-500" x-text="e.vendor ? e.vendor + ' · ' + e.date : e.date"></p>
            <p class="text-xs text-neutral-400" x-show="e.notes" x-text="e.notes"></p>
          </div>
          <div class="flex items-center gap-3">
            <div class="text-right">
              <p class="text-sm font-bold text-brand-700" x-text="'$' + parseFloat(e.amount).toFixed(2)"></p>
              <p class="text-[10px] text-neutral-400" x-text="e.category || 'Uncategorized'"></p>
            </div>
            <div class="flex gap-1">
              <button @click="editExpense(e)" class="rounded-2xl border border-neutral-200 bg-white px-3 py-1.5 text-xs font-semibold text-neutral-600 hover:bg-neutral-50 transition">Edit</button>
              <button @click="deleteExpense(e.id)" class="rounded-2xl border border-rose-200 bg-white px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50 transition">Delete</button>
            </div>
          </div>
        </article>
      </template>
    </div>
  </section>

  <!-- Add/Edit Modal -->
  <div x-show="showForm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/30" @click.self="showForm = false">
    <div class="w-full max-w-lg rounded-3xl bg-white p-6 shadow-xl mx-4">
      <h3 class="text-lg font-bold text-brand-900" x-text="editId ? 'Edit Expense' : 'Create Expense'"></h3>
      <form @submit.prevent="saveExpense()" class="mt-4 space-y-3">
        <label class="block text-sm font-semibold text-neutral-700">
          <span>Title *</span>
          <input type="text" x-model="form.title" required class="mt-1 w-full rounded-2xl border border-neutral-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
        </label>
        <div class="grid grid-cols-2 gap-3">
          <label class="block text-sm font-semibold text-neutral-700">
            <span>Amount *</span>
            <input type="number" step="0.01" min="0" x-model="form.amount" required class="mt-1 w-full rounded-2xl border border-neutral-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
          </label>
          <label class="block text-sm font-semibold text-neutral-700">
            <span>Date</span>
            <input type="date" x-model="form.date" class="mt-1 w-full rounded-2xl border border-neutral-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
          </label>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <label class="block text-sm font-semibold text-neutral-700">
            <span>Vendor</span>
            <input type="text" x-model="form.vendor" class="mt-1 w-full rounded-2xl border border-neutral-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
          </label>
          <label class="block text-sm font-semibold text-neutral-700">
            <span>Category</span>
            <select x-model="form.category" class="mt-1 w-full rounded-2xl border border-neutral-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
              <option value="">Select...</option>
              <option value="supplies">Supplies</option>
              <option value="maintenance">Maintenance</option>
              <option value="marketing">Marketing</option>
              <option value="utilities">Utilities</option>
              <option value="payroll">Payroll</option>
              <option value="food">Food & Beverage</option>
              <option value="other">Other</option>
            </select>
          </label>
        </div>
        <label class="block text-sm font-semibold text-neutral-700">
          <span>Status</span>
          <select x-model="form.status" class="mt-1 w-full rounded-2xl border border-neutral-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
          </select>
        </label>
        <label class="block text-sm font-semibold text-neutral-700">
          <span>Notes (optional)</span>
          <textarea x-model="form.notes" rows="2" class="mt-1 w-full rounded-2xl border border-neutral-200 px-3 py-2 text-sm outline-none focus:border-brand-500"></textarea>
        </label>
        <div class="flex gap-2">
          <button type="submit" class="rounded-2xl bg-brand-500 px-4 py-2 text-sm font-bold text-white hover:bg-brand-600 transition">Save</button>
          <button type="button" @click="showForm = false" class="rounded-2xl border border-neutral-200 px-4 py-2 text-sm font-bold text-neutral-600 hover:bg-neutral-50 transition">Cancel</button>
        </div>
        <p x-show="formError" x-text="formError" class="text-sm font-semibold text-rose-600"></p>
      </form>
    </div>
  </div>
</div>

<script>
  document.addEventListener('alpine:init', () => {
    Alpine.data('expenseManager', () => ({
      expenses: <?= json_encode(array_map(function($e) {
        return [
          'id' => (int)$e['id'],
          'title' => $e['title'],
          'vendor' => $e['vendor'] ?? '',
          'amount' => (float)$e['amount'],
          'category' => $e['category'] ?? '',
          'date' => $e['date'],
          'status' => $e['status'] ?? 'pending',
          'notes' => $e['notes'] ?? '',
        ];
      }, $expenses)) ?>,
      showForm: false,
      editId: null,
      form: { title: '', amount: '', vendor: '', category: '', date: '<?= date('Y-m-d') ?>', status: 'pending', notes: '' },
      formError: '',

      openForm() {
        this.editId = null;
        this.form = { title: '', amount: '', vendor: '', category: '', date: '<?= date('Y-m-d') ?>', status: 'pending', notes: '' };
        this.formError = '';
        this.showForm = true;
      },

      editExpense(e) {
        this.editId = e.id;
        this.form = {
          title: e.title,
          amount: e.amount,
          vendor: e.vendor || '',
          category: e.category || '',
          date: e.date,
          status: e.status,
          notes: e.notes || '',
        };
        this.formError = '';
        this.showForm = true;
      },

      saveExpense() {
        this.formError = '';
        if (!this.form.title || !this.form.amount) { this.formError = 'Title and amount are required.'; return; }
        const csrf = '<?= $csrfToken ?>';
        const endpoint = this.editId ? 'expense-update' : 'expense-create';
        const body = { csrf_token: csrf, ...this.form };
        if (this.editId) body.id = this.editId;

        fetch('<?= BASE_URL ?>/owner/index.php?route=' + endpoint, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(body),
        })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            this.showForm = false;
            this.loadExpenses();
          } else {
            this.formError = data.error || 'Save failed';
          }
        })
        .catch(e => { this.formError = e.message; });
      },

      deleteExpense(id) {
        if (!confirm('Delete this expense?')) return;
        const csrf = '<?= $csrfToken ?>';
        fetch('<?= BASE_URL ?>/owner/index.php?route=expense-delete', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ csrf_token: csrf, id: id }),
        })
        .then(r => r.json())
        .then(data => { if (data.success) this.loadExpenses(); });
      },

      loadExpenses() {
        fetch('<?= BASE_URL ?>/owner/index.php?route=api-expenses')
          .then(r => r.json())
          .then(data => {
            if (data.success) {
              this.expenses = data.expenses.map(e => ({
                id: e.id,
                title: e.title,
                vendor: e.vendor || '',
                amount: parseFloat(e.amount),
                category: e.category || '',
                date: e.date,
                status: e.status,
                notes: e.notes || '',
              }));
            }
          });
      },
    }));
  });
</script>
<?php include __DIR__ . '/../partials/owner-footer.php'; ?>
