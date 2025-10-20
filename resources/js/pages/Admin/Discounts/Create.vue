<script setup lang="ts">
import { computed, reactive } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

const page = usePage();
const categories = (page.props as any).categories as Array<{id:number; name:string; parent_id:number|null}> || [];
const products = (page.props as any).products as Array<{id:number; name:string; category_id:number|null}> || [];
const variants = (page.props as any).variants as Array<{id:number; display_name?:string; sku?:string; product_id:number}> || [];
const customers = (page.props as any).customers as Array<{id:number; first_name?:string; last_name?:string; email:string; created_at:string}> || [];

const form = reactive({
  name: '',
  slug: '',
  description: '',
  code: '',
  type: 'percentage',
  value: 0,
  minimum_amount: 0,
  maximum_discount: 0,
  usage_limit: 0,
  usage_limit_per_customer: 0,
  is_active: true as any,
  starts_at: '',
  expires_at: '',
  applicable_to: 'all_variants',
  conditions: '' as any,
  metadata: '',
});

// UI state for building conditions
const state = reactive({
  scope: 'all_variants' as 'all_variants' | 'specific_products' | 'categories' | 'variants' | 'customers',
  selectedCategoryIds: [] as number[],
  selectedProductIds: [] as number[],
  selectedVariantIds: [] as number[],
  customerTarget: 'all_variants' as 'all_variants' | 'new' | 'by_order_count' | 'by_total_spend' | 'specific',
  newCustomerDays: 30 as number,
  minOrderCount: 1 as number,
  minTotalSpend: 0 as number,
  selectedCustomerIds: [] as number[],
  includeChildCategories: true,
  // Searches
  categorySearch: '' as string,
  productSearch: '' as string,
  variantSearch: '' as string,
  // Category drilldown
  categoryPath: [] as number[], // array of category IDs representing drill path
  selectedLeafCategoryId: null as number | null,
});

// Category helpers
const rootCategories = computed(() => categories.filter(c => c.parent_id == null));
const childrenOf = (parentId: number) => categories.filter(c => c.parent_id === parentId);
const pathBreadcrumb = computed(() => state.categoryPath.map(id => categories.find(c => c.id === id)).filter(Boolean) as Array<{id:number; name:string; parent_id:number|null}>);
const currentLevelCategories = computed(() => {
  const parentId = state.categoryPath.length ? state.categoryPath[state.categoryPath.length - 1] : null;
  const list = parentId === null ? rootCategories.value : childrenOf(parentId);
  const q = state.categorySearch.trim().toLowerCase();
  return q ? list.filter(c => c.name.toLowerCase().includes(q)) : list;
});
const leafProducts = computed(() => {
  const q = state.productSearch.trim().toLowerCase();
  const base = state.selectedLeafCategoryId ? products.filter(p => p.category_id === state.selectedLeafCategoryId) : [];
  return q ? base.filter(p => p.name.toLowerCase().includes(q)) : base;
});

const filteredProducts = computed(() => {
  let base = products;
  if (state.selectedCategoryIds.length) {
    const set = new Set(state.selectedCategoryIds);
    base = base.filter(p => p.category_id && set.has(p.category_id));
  }
  const q = state.productSearch.trim().toLowerCase();
  return q ? base.filter(p => p.name.toLowerCase().includes(q)) : base;
});
const filteredVariants = computed(() => {
  let base = variants;
  if (state.selectedProductIds.length) {
    const set = new Set(state.selectedProductIds);
    base = base.filter(v => set.has(v.product_id));
  }
  const q = state.variantSearch.trim().toLowerCase();
  const getName = (v: any) => (v.display_name || v.sku || '').toString().toLowerCase();
  return q ? base.filter(v => getName(v).includes(q)) : base;
});

const builtConditions = computed(() => {
  switch (state.scope) {
    case 'categories':
      return {
        applies_to: 'items',
        category_ids: state.selectedCategoryIds,
        include_children: !!state.includeChildCategories,
      };
    case 'specific_products':
      return {
        applies_to: 'items',
        product_ids: state.selectedProductIds,
      };
    case 'variants':
      return {
        applies_to: 'items',
        variant_ids: state.selectedVariantIds,
      };
    case 'customers':
      if (state.customerTarget === 'all_variants') return { applies_to: 'customers', scope: 'all' };
      if (state.customerTarget === 'new') return { applies_to: 'customers', scope: 'new', within_days: state.newCustomerDays };
      if (state.customerTarget === 'by_order_count') return { applies_to: 'customers', scope: 'by_order_count', min_orders: state.minOrderCount };
      if (state.customerTarget === 'by_total_spend') return { applies_to: 'customers', scope: 'by_total_spend', min_total_spend: state.minTotalSpend };
      return { applies_to: 'customers', scope: 'specific', customer_ids: state.selectedCustomerIds };
    default:
      return { applies_to: 'all' };
  }
});

// Handlers for category drilldown
const onSelectCategory = (catId: number) => {
  const children = categories.filter(c => c.parent_id === catId);
  if (children.length) {
    state.categoryPath.push(catId);
    state.selectedLeafCategoryId = null;
    state.selectedCategoryIds = [];
  } else {
    state.selectedLeafCategoryId = catId;
    state.selectedCategoryIds = [catId];
  }
};
const onBreadcrumbClick = (index: number) => {
  if (index < 0) {
    state.categoryPath = [];
  } else {
    state.categoryPath = state.categoryPath.slice(0, index + 1);
  }
  state.selectedLeafCategoryId = null;
  state.selectedCategoryIds = [];
};

const submit = () => {
  // Map scope to applicable_to for quick filtering in database if needed
  const scope = state.scope;
  form.applicable_to = scope === 'customers' ? 'specific_customers' : (scope === 'all_variants' ? 'all' : `specific_${scope}`);
  form.conditions = JSON.stringify(builtConditions.value);
  router.post(route('admin.discounts.store'), form);
};

const breadcrumbs = [
    { title: 'Dashboard', href: route('admin.dashboard') },
    { title: 'Discounts', href: route('admin.discounts.index') },
    { title: 'Create', href: route('admin.discounts.create') },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
      <div class="p-4">
          <div class="card flex flex-col gap-6 rounded-lg bg-white p-4 shadow-sm">
              <!-- Header -->
              <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-gray-800">Create Discount</h1>
        <a :href="route('admin.discounts.index')" class="text-primary underline">&larr; Back </a>
      </div>

      <div class="rounded-lg bg-white p-4 shadow">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Name</label>
            <input v-model="form.name" type="text" class="w-full rounded border px-3 py-2" />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Slug (optional)</label>
            <input v-model="form.slug" type="text" class="w-full rounded border px-3 py-2" />
          </div>
          <div class="md:col-span-2">
            <label class="mb-1 block text-sm font-medium text-gray-700">Description</label>
            <textarea v-model="form.description" rows="2" class="w-full rounded border px-3 py-2" />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Code (optional)</label>
            <input v-model="form.code" type="text" class="w-full rounded border px-3 py-2" />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Type</label>
            <select v-model="form.type" class="w-full rounded border px-3 py-2">
              <option value="percentage">Percentage (%)</option>
              <option value="fixed">Fixed Amount</option>
              <option value="free_shipping">Free Shipping</option>
            </select>
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Value</label>
            <input v-model.number="form.value" type="number" step="0.01" min="0" class="w-full rounded border px-3 py-2" />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Active</label>
            <select v-model="form.is_active" class="w-full rounded border px-3 py-2">
              <option :value="true">Yes</option>
              <option :value="false">No</option>
            </select>
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Starts At</label>
            <input v-model="form.starts_at" type="datetime-local" class="w-full rounded border px-3 py-2" />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Expires At</label>
            <input v-model="form.expires_at" type="datetime-local" class="w-full rounded border px-3 py-2" />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Minimum Order Amount</label>
            <input v-model.number="form.minimum_amount" type="number" step="0.01" min="0" class="w-full rounded border px-3 py-2" />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Maximum Discount</label>
            <input v-model.number="form.maximum_discount" type="number" step="0.01" min="0" class="w-full rounded border px-3 py-2" />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Usage Limit (Global)</label>
            <input v-model.number="form.usage_limit" type="number" step="1" min="0" class="w-full rounded border px-3 py-2" />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Usage Limit Per Customer</label>
            <input v-model.number="form.usage_limit_per_customer" type="number" step="1" min="0" class="w-full rounded border px-3 py-2" />
          </div>
          <div class="md:col-span-2">
            <label class="mb-1 block text-sm font-medium text-gray-700">Applies To</label>
            <select v-model="state.scope" class="w-full rounded border px-3 py-2">
              <option value="all_variants">All Variants</option>
              <option value="categories">Specific categories</option>
              <option value="specific_products">Specific products</option>
              <option value="variants">Specific product variants</option>
              <option value="customers">Customers</option>
            </select>
          </div>

          <!-- Categories selection (drilldown) -->
          <div v-if="state.scope==='categories'" class="md:col-span-2 grid grid-cols-1 gap-4">
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700">Search categories</label>
              <input v-model="state.categorySearch" type="text" placeholder="Search categories..." class="w-full rounded border px-3 py-2" />
            </div>

            <!-- Breadcrumb -->
            <div class="flex flex-wrap items-center gap-2 text-sm">
              <button type="button" class="text-primary underline" @click="onBreadcrumbClick(-1)">Root</button>
              <template v-for="(crumb, idx) in pathBreadcrumb" :key="crumb.id">
                <span>/</span>
                <button type="button" class="text-primary underline" @click="onBreadcrumbClick(idx)">{{ crumb.name }}</button>
              </template>
            </div>

            <!-- Current level categories -->
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700">Categories</label>
              <div class="grid grid-cols-1 gap-2 md:grid-cols-3">
                <button v-for="c in currentLevelCategories" :key="c.id" type="button" @click="onSelectCategory(c.id)" class="rounded border px-3 py-2 text-left hover:bg-gray-50">
                  {{ c.name }}
                </button>
              </div>
              <p v-if="!currentLevelCategories.length" class="text-sm text-gray-500">No categories here.</p>
            </div>

            <!-- Include children -->
            <div class="flex items-center gap-2">
              <input id="includeChildren" v-model="state.includeChildCategories" type="checkbox" class="h-4 w-4" />
              <label for="includeChildren" class="text-sm text-gray-700">Include sub-categories</label>
            </div>

            <!-- Leaf products preview -->
            <div v-if="state.selectedLeafCategoryId">
              <label class="mb-1 block text-sm font-medium text-gray-700">Products in selected category</label>
              <input v-model="state.productSearch" type="text" placeholder="Search products..." class="mb-2 w-full rounded border px-3 py-2" />
              <div class="max-h-48 overflow-auto rounded border">
                <div v-for="p in leafProducts" :key="p.id" class="border-b px-3 py-1 last:border-b-0">{{ p.name }}</div>
              </div>
              <p class="mt-1 text-xs text-gray-500">Note: This discount will target the selected category. Use the Products scope to target specific products.</p>
            </div>
          </div>

          <!-- Products selection -->
          <div v-if="state.scope==='specific_products'" class="md:col-span-2 grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700">Filter by Category (optional)</label>
              <select v-model="state.selectedCategoryIds" multiple class="w-full rounded border px-3 py-2 h-40">
                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700">Products</label>
              <input v-model="state.productSearch" type="text" placeholder="Search products..." class="mb-2 w-full rounded border px-3 py-2" />
              <select v-model="state.selectedProductIds" multiple class="w-full rounded border px-3 py-2 h-40">
                <option v-for="p in filteredProducts" :key="p.id" :value="p.id">{{ p.name }}</option>
              </select>
            </div>
          </div>

          <!-- Variants selection -->
          <div v-if="state.scope==='variants'" class="md:col-span-2 grid grid-cols-1 gap-4 md:grid-cols-3">
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700">Filter by Category (optional)</label>
              <select v-model="state.selectedCategoryIds" multiple class="w-full rounded border px-3 py-2 h-40">
                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700">Filter by Product (optional)</label>
              <input v-model="state.productSearch" type="text" placeholder="Search products..." class="mb-2 w-full rounded border px-3 py-2" />
              <select v-model="state.selectedProductIds" multiple class="w-full rounded border px-3 py-2 h-40">
                <option v-for="p in filteredProducts" :key="p.id" :value="p.id">{{ p.name }}</option>
              </select>
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700">Variants</label>
              <input v-model="state.variantSearch" type="text" placeholder="Search variants..." class="mb-2 w-full rounded border px-3 py-2" />
              <select v-model="state.selectedVariantIds" multiple class="w-full rounded border px-3 py-2 h-40">
                <option v-for="v in filteredVariants" :key="v.id" :value="v.id">{{ v.display_name || v.sku || ('Variant #' + v.id) }}</option>
              </select>
            </div>
          </div>

          <!-- Customers selection -->
          <div v-if="state.scope==='customers'" class="md:col-span-2 grid grid-cols-1 gap-4">
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700">Customer Target</label>
              <select v-model="state.customerTarget" class="w-full rounded border px-3 py-2">
                <option value="all">All customers</option>
                <option value="new">New customers (joined within N days)</option>
                <option value="by_order_count">Customers with at least N orders</option>
                <option value="by_total_spend">Customers with total spend >= amount</option>
                <option value="specific">Specific customers</option>
              </select>
            </div>
            <div v-if="state.customerTarget==='new'" class="grid grid-cols-1 gap-4 md:grid-cols-2">
              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Within days</label>
                <input v-model.number="state.newCustomerDays" type="number" min="1" class="w-full rounded border px-3 py-2" />
              </div>
            </div>
            <div v-if="state.customerTarget==='by_order_count'" class="grid grid-cols-1 gap-4 md:grid-cols-2">
              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Minimum Orders</label>
                <input v-model.number="state.minOrderCount" type="number" min="1" class="w-full rounded border px-3 py-2" />
              </div>
            </div>
            <div v-if="state.customerTarget==='by_total_spend'" class="grid grid-cols-1 gap-4 md:grid-cols-2">
              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Minimum Total Spend</label>
                <input v-model.number="state.minTotalSpend" type="number" min="0" step="0.01" class="w-full rounded border px-3 py-2" />
              </div>
            </div>
            <div v-if="state.customerTarget==='specific'">
              <label class="mb-1 block text-sm font-medium text-gray-700">Select Customers</label>
              <select v-model="state.selectedCustomerIds" multiple class="w-full rounded border px-3 py-2 h-48">
                <option v-for="c in customers" :key="c.id" :value="c.id">{{ ([(c.first_name||''),(c.last_name||'')].join(' ').trim()) || c.email }} ({{ c.email }})</option>
              </select>
            </div>
          </div>
          <div class="md:col-span-2">
            <label class="mb-1 block text-sm font-medium text-gray-700">Metadata (JSON)</label>
            <textarea v-model="form.metadata" rows="3" class="w-full rounded border px-3 py-2 font-mono text-sm" />
          </div>
        </div>

        <div class="mt-4">
          <button @click="submit" class="rounded bg-primary px-4 py-2 text-white hover:bg-primary/90">Save</button>
        </div>
      </div>
          </div>
      </div>
  </AppLayout>
</template>
