<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps, Promotion } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { PlusIcon, XIcon } from 'lucide-vue-next';
import { computed, reactive, ref, watch } from 'vue';
import axios from 'axios';

// ─── Types ────────────────────────────────────────────────────────────────────
interface PaginationLink { url: string | null; label: string; active: boolean; }
interface PaginationMeta { current_page: number; from: number; last_page: number; path: string; per_page: number; to: number; total: number; }
interface PaginatedResponse<T> { data: T[]; links: PaginationLink[]; meta: PaginationMeta; }
interface LinkedItem { id: number; name: string; slug?: string; }

type LinkType = 'category' | 'product';
type SearchableType = 'categories' | 'products';

// Shaped promotion coming from controller's ->through()
interface ShapedPromotion {
  id: number;
  title: string;
  image_url: string | null;
  position: string;
  is_active: boolean;
  link_type: LinkType | null;
  link_id: number | null;
  linked_item: LinkedItem | null; // ✅ what the controller actually sends
  start_date: string | null;
  end_date: string | null;
  priority: number;
}

// ─── Page props ───────────────────────────────────────────────────────────────
const page = usePage<AppPageProps<{
  promotions: PaginatedResponse<ShapedPromotion>;
  filters: { position?: string; is_active?: string };
}>>();

const promotions = computed(() => page.props.promotions?.data ?? []);
const pagination  = computed(() => ({
  links: page.props.promotions?.links ?? [],
  meta:  page.props.promotions?.meta  ?? {} as PaginationMeta,
}));

// ─── Filters ──────────────────────────────────────────────────────────────────
const filters = reactive({
  position:  page.props.filters.position  ?? '',
  is_active: page.props.filters.is_active ?? '',
});
let filterTimer: ReturnType<typeof setTimeout> | null = null;
function applyFilters() {
  if (filterTimer) clearTimeout(filterTimer);
  filterTimer = setTimeout(() => {
    router.get(route('admin.promotions.index'), filters, { preserveState: true });
  }, 350);
}

// ─── Modal & form ─────────────────────────────────────────────────────────────
const showModal        = ref(false);
const isSaving         = ref(false);
const saveError        = ref<string | null>(null);
const editingPromotion = ref<ShapedPromotion | null>(null);
const imagePreview     = ref<string | null>(null);

const emptyForm = () => ({
  title:      '',
  image:      null as File | null,
  position:   '',
  link_type:  '' as LinkType | '',
  link_id:    null as number | null,
  start_date: '',
  end_date:   '',
  priority:   0,
  is_active:  true,
});
const form = reactive(emptyForm());

// Clear search state when link type changes
watch(() => form.link_type, () => {
  form.link_id        = null;
  selectedLabel.value = '';
  searchResults.categories = [];
  searchResults.products   = [];
  searchQuery.value   = '';
});

// ─── Search ───────────────────────────────────────────────────────────────────
const searchQuery   = ref('');
const isSearching   = ref(false);
const selectedLabel = ref('');
const searchResults = reactive<Record<SearchableType, LinkedItem[]>>({
  categories: [],
  products:   [],
});
const searchConfig: Record<LinkType, { key: SearchableType; routeName: string }> = {
  category: { key: 'categories', routeName: 'admin.promotions.searchCategories' },
  product:  { key: 'products',   routeName: 'admin.promotions.searchProducts'   },
};

let searchTimer: ReturnType<typeof setTimeout> | null = null;
function onSearchInput(query: string) {
  searchQuery.value = query;
  if (!form.link_type || !query.trim()) {
    if (form.link_type) searchResults[searchConfig[form.link_type as LinkType].key] = [];
    return;
  }
  if (searchTimer) clearTimeout(searchTimer);
  searchTimer = setTimeout(() => doSearch(form.link_type as LinkType, query), 300);
}

async function doSearch(type: LinkType, query: string) {
  isSearching.value = true;
  try {
    const { data } = await axios.get(route(searchConfig[type].routeName), { params: { query } });
    searchResults[searchConfig[type].key] = data;
  } catch {
    searchResults[searchConfig[type].key] = [];
  } finally {
    isSearching.value = false;
  }
}

function selectLink(id: number, name: string) {
  form.link_id        = id;
  selectedLabel.value = name;
  if (form.link_type) searchResults[searchConfig[form.link_type as LinkType].key] = [];
  searchQuery.value   = '';
}
function clearLink() { form.link_id = null; selectedLabel.value = ''; }

const activeResults = computed(() =>
  form.link_type ? searchResults[searchConfig[form.link_type as LinkType].key] : []
);

// ─── Modal open / close ───────────────────────────────────────────────────────
function openModal(promotion: ShapedPromotion | null = null) {
  saveError.value   = null;
  searchQuery.value = '';

  if (promotion) {
    editingPromotion.value = promotion;
    Object.assign(form, {
      title:      promotion.title      ?? '',
      image:      null,
      position:   promotion.position   ?? '',
      link_type:  promotion.link_type  ?? '',
      link_id:    promotion.link_id    ?? null,
      start_date: promotion.start_date ?? '',
      end_date:   promotion.end_date   ?? '',
      priority:   promotion.priority   ?? 0,
      is_active:  promotion.is_active  ?? true,
    });
    // ✅ Use linked_item — what the controller actually returns
    selectedLabel.value = promotion.linked_item?.name ?? '';
    imagePreview.value  = promotion.image_url ?? null;
  } else {
    editingPromotion.value = null;
    Object.assign(form, emptyForm());
    selectedLabel.value = '';
    imagePreview.value  = null;
  }

  showModal.value = true;
}
function closeModal() { showModal.value = false; }

// ─── Image ────────────────────────────────────────────────────────────────────
function onImageSelected(event: Event) {
  const file = (event.target as HTMLInputElement).files?.[0] ?? null;
  if (file) {
    form.image         = file;
    imagePreview.value = URL.createObjectURL(file);
  }
}

// ─── Save ─────────────────────────────────────────────────────────────────────
async function savePromotion() {
  isSaving.value  = true;
  saveError.value = null;

  const payload = new FormData();
  if (editingPromotion.value) payload.append('id', String(editingPromotion.value.id));
  payload.append('title',      form.title);
  payload.append('position',   form.position);
  payload.append('link_type',  form.link_type ?? '');
  payload.append('start_date', form.start_date);
  payload.append('end_date',   form.end_date);
  payload.append('priority',   String(form.priority));
  // ✅ Send as actual 1/0 integer string that passes Laravel's boolean validation
  payload.append('is_active',  form.is_active ? '1' : '0');
  if (form.link_id != null) payload.append('link_id', String(form.link_id));
  if (form.image)            payload.append('image',  form.image);

  // ✅ forceFormData ensures Inertia handles File objects correctly
  router.post(route('admin.promotions.save'), payload, {
    forceFormData:  true,
    preserveState:  true,
    onSuccess: ()       => closeModal(),
    onError:   (errors) => { saveError.value = Object.values(errors)[0] as string ?? 'Something went wrong.'; },
    onFinish:  ()       => { isSaving.value = false; },
  });
}

// ─── Delete ───────────────────────────────────────────────────────────────────
function deletePromotion(id: number) {
  if (!confirm('This will permanently delete this promotion. Continue?')) return;
  router.delete(route('admin.promotions.destroy', { promotion: id }));
}

// ─── Pagination ───────────────────────────────────────────────────────────────
function goToPage(url: string | null) { if (url) router.get(url); }
function rowIndex(index: number) {
  const meta = pagination.value.meta;
  return index + 1 + ((meta.current_page ?? 1) - 1) * (meta.per_page ?? promotions.value.length);
}

// ─── Breadcrumbs ──────────────────────────────────────────────────────────────
const breadcrumbs = [
  { title: 'Dashboard',  href: route('admin.dashboard') },
  { title: 'Promotions', href: route('admin.promotions.index') },
];
</script>

<template>
  <Head title="Promotions" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-4">
      <div class="card flex flex-col gap-6 rounded-lg bg-white p-4 shadow-sm">

        <!-- Header -->
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
          <h1 class="text-2xl font-semibold text-gray-800">Promotions</h1>
          <button @click="openModal()" class="rounded-xl bg-primary px-4 py-2 text-white hover:bg-primary-dark">
            + New Promotion
          </button>
        </div>

        <!-- Filters -->
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:gap-4">
          <input
            type="text" v-model="filters.position" @input="applyFilters"
            placeholder="Search by position" class="w-full rounded border px-3 py-2 md:w-64"
          />
          <select v-model="filters.is_active" @change="applyFilters" class="rounded border px-3 py-2">
            <option value="">All</option>
            <option value="1">Active</option>
            <option value="0">Inactive</option>
          </select>
        </div>

        <!-- Table -->
        <div v-if="promotions.length" class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">#</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Banner</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Position</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Type</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Linked Item</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Status</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
              <tr v-for="(promotion, index) in promotions" :key="promotion.id" class="hover:bg-gray-50">
                <td class="px-4 py-4 text-sm text-gray-500">{{ rowIndex(index) }}</td>
                <td class="px-4 py-4">
                  <img
                    v-if="promotion.image_url" :src="promotion.image_url"
                    class="h-10 w-20 rounded border object-cover"
                  />
                  <span v-else class="text-xs text-gray-400">—</span>
                </td>
                <td class="px-4 py-4 text-sm text-gray-800">{{ promotion.position }}</td>
                <td class="px-4 py-4 text-sm capitalize text-gray-800">
                  {{ promotion.link_type?.replace('_', ' ') ?? '—' }}
                </td>
                <!-- ✅ Read from linked_item, not promotion.category / promotion.product -->
                <td class="px-4 py-4 text-sm text-gray-800">
                  {{ promotion.linked_item?.name ?? '—' }}
                </td>
                <td class="px-4 py-4 text-sm">
                  <span
                    class="rounded-full px-2 py-0.5 text-xs font-medium"
                    :class="promotion.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                  >
                    {{ promotion.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </td>
                <td class="px-4 py-4 text-right text-sm">
                  <button @click="openModal(promotion)" class="mr-3 text-blue-600 hover:underline">Edit</button>
                  <button @click="deletePromotion(promotion.id)" class="text-red-600 hover:underline">Delete</button>
                </td>
              </tr>
            </tbody>
          </table>

          <!-- ✅ Pagination restored -->
          <div class="mt-4 flex items-center justify-between">
            <div class="text-sm text-gray-600">
              Page {{ pagination.meta.current_page }} of {{ pagination.meta.last_page }}
            </div>
            <div class="flex flex-wrap gap-1">
              <button
                v-for="link in pagination.links" :key="link.label"
                :disabled="!link.url" @click="goToPage(link.url)"
                class="rounded border border-gray-300 px-3 py-1 text-sm hover:bg-gray-100 disabled:opacity-50"
                :class="{ 'bg-gray-200 font-semibold': link.active }"
              >
                {{ link.label.replace(/&laquo;/g, '«').replace(/&raquo;/g, '»') }}
              </button>
            </div>
          </div>
        </div>

        <!-- Empty state -->
        <div v-else class="p-8 text-center">
          <PlusIcon class="mx-auto h-12 w-12 text-gray-400" />
          <h3 class="mt-2 text-sm font-medium text-gray-900">No promotions</h3>
          <p class="mt-1 text-sm text-gray-500">Get started by creating a new promotion.</p>
        </div>

      </div>
    </div>

    <!-- ── Modal ──────────────────────────────────────────────────────────── -->
    <transition name="fade">
      <div
        v-if="showModal"
        class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/30 py-10"
        @click.self="closeModal"
      >
        <!-- ✅ overflow-y-auto on modal body so it scrolls on small screens -->
        <div class="w-full max-w-xl rounded-lg bg-white p-6 shadow-lg">

          <div class="mb-4 flex items-center justify-between">
            <h2 class="text-xl font-semibold">{{ editingPromotion ? 'Edit Promotion' : 'New Promotion' }}</h2>
            <button @click="closeModal"><XIcon class="h-5 w-5" /></button>
          </div>

          <div v-if="saveError" class="mb-3 rounded bg-red-50 px-3 py-2 text-sm text-red-600">
            {{ saveError }}
          </div>

          <form @submit.prevent="savePromotion" class="grid grid-cols-1 gap-4 md:grid-cols-2">

            <!-- Title -->
            <div class="col-span-1 md:col-span-2">
              <label class="mb-1 block text-xs text-gray-500">Title</label>
              <input type="text" v-model="form.title" placeholder="Title" class="w-full rounded border px-3 py-2" />
            </div>

            <!-- Banner Image -->
            <div class="col-span-1 md:col-span-2">
              <label class="mb-1 block text-xs text-gray-500">Banner Image (PNG, JPG)</label>
              <input type="file" accept="image/png,image/jpeg" @change="onImageSelected" class="w-full rounded border px-3 py-2" />
              <img v-if="imagePreview" :src="imagePreview" alt="Banner Preview" class="mt-2 max-h-40 w-auto rounded border object-cover" />
            </div>

            <!-- Position -->
            <div>
              <label class="mb-1 block text-xs text-gray-500">Position</label>
              <input type="text" v-model="form.position" placeholder="e.g. homepage_top" class="w-full rounded border px-3 py-2" />
            </div>

            <!-- Link Type -->
            <div>
              <label class="mb-1 block text-xs text-gray-500">Link Type</label>
              <select v-model="form.link_type" class="w-full rounded border px-3 py-2">
                <option value="">Select Link Type</option>
                <option value="category">Category</option>
                <option value="product">Product</option>
              </select>
            </div>

            <!-- Linked Item Search -->
            <div class="col-span-1 md:col-span-2">
              <label class="mb-1 block text-xs text-gray-500">Linked Item</label>
              <div v-if="form.link_id" class="flex items-center gap-2 rounded bg-blue-50 px-3 py-1.5 text-sm text-blue-700">
                <span>{{ selectedLabel }}</span>
                <button type="button" @click="clearLink" class="ml-auto text-blue-400 hover:text-blue-600">
                  <XIcon class="h-4 w-4" />
                </button>
              </div>
              <div v-else-if="form.link_type">
                <input
                  type="text" :value="searchQuery"
                  @input="onSearchInput(($event.target as HTMLInputElement).value)"
                  placeholder="Search..." class="w-full rounded border px-3 py-2"
                />
                <div v-if="isSearching" class="mt-1 text-xs text-gray-400">Searching…</div>
                <ul v-if="activeResults.length" class="mt-1 max-h-40 overflow-y-auto rounded border">
                  <li
                    v-for="item in activeResults" :key="item.id"
                    @click="selectLink(item.id, item.name)"
                    class="cursor-pointer px-3 py-1.5 text-sm hover:bg-gray-100"
                  >
                    {{ item.name }}
                  </li>
                </ul>
              </div>
              <p v-else class="text-xs text-gray-400">Select a link type first.</p>
            </div>

            <!-- Start / End Date -->
            <div>
              <label class="mb-1 block text-xs text-gray-500">Start Date</label>
              <input type="date" v-model="form.start_date" class="w-full rounded border px-3 py-2" />
            </div>
            <div>
              <label class="mb-1 block text-xs text-gray-500">End Date</label>
              <input type="date" v-model="form.end_date" class="w-full rounded border px-3 py-2" />
            </div>

            <!-- Priority -->
            <div>
              <label class="mb-1 block text-xs text-gray-500">Priority</label>
              <input type="number" v-model="form.priority" min="0" class="w-full rounded border px-3 py-2" />
            </div>

            <!-- Active -->
            <div class="flex items-center mt-6">
              <input type="checkbox" v-model="form.is_active" id="is_active" class="mr-2" />
              <label for="is_active" class="text-sm">Active</label>
            </div>

            <!-- Submit -->
            <div class="col-span-1 md:col-span-2">
              <button type="submit" :disabled="isSaving" class="w-full rounded bg-primary px-4 py-2 text-white disabled:opacity-60">
                {{ isSaving ? 'Saving…' : editingPromotion ? 'Update' : 'Create' }}
              </button>
            </div>

          </form>
        </div>
      </div>
    </transition>

  </AppLayout>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.15s ease; }
.fade-enter-from, .fade-leave-to       { opacity: 0; }
</style>