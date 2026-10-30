<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import { ExternalLink, Search, Trash2 } from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';
import type { AdminCampaign } from './Index.vue';

interface CampaignItem {
    id: number;
    name: string;
    slug: string;
    image_url: string | null;
    is_published: boolean;
    price: number;
    discount_percent: number | null;
    effective_percent: number | null;
    deal_price: number;
    sort_order: number;
}
interface SearchResult { id: number; name: string; image_url: string | null; price: number; }
interface PaginationLink { url: string | null; label: string; active: boolean; }

const props = defineProps<{
    campaign: AdminCampaign;
    products: { data: CampaignItem[]; links: PaginationLink[]; last_page: number; total: number };
    types: Record<string, string>;
    categories: { id: number; name: string; parent_id: number | null }[];
    filters: { search?: string };
}>();

const money = (n: number | null | undefined) => `KSh ${Number(n ?? 0).toLocaleString()}`;

// ─── Add products ────────────────────────────────────────────────────────────
const query = ref('');
const categoryId = ref<number | ''>('');
const results = ref<SearchResult[]>([]);
const isSearching = ref(false);
const picked = reactive(new Map<number, SearchResult>());
const addPercent = ref<number | string>('');
const isAdding = ref(false);

let searchTimer: ReturnType<typeof setTimeout> | null = null;
function search() {
    if (searchTimer) clearTimeout(searchTimer);
    if (query.value.trim().length < 2 && !categoryId.value) {
        results.value = [];
        return;
    }
    searchTimer = setTimeout(async () => {
        isSearching.value = true;
        try {
            const { data } = await axios.get(route('admin.campaigns.search-products', { campaign: props.campaign.id }), {
                params: { query: query.value, category_id: categoryId.value || undefined },
            });
            results.value = data;
        } catch {
            results.value = [];
        } finally {
            isSearching.value = false;
        }
    }, 300);
}

function togglePick(p: SearchResult) {
    if (picked.has(p.id)) picked.delete(p.id);
    else picked.set(p.id, p);
}
const allResultsPicked = computed(() => results.value.length > 0 && results.value.every((r) => picked.has(r.id)));
function togglePickAll() {
    if (allResultsPicked.value) results.value.forEach((r) => picked.delete(r.id));
    else results.value.forEach((r) => picked.set(r.id, r));
}

function submitAdd(payload: Record<string, unknown>) {
    isAdding.value = true;
    router.post(
        route('admin.campaigns.products.add', { campaign: props.campaign.id }),
        { ...payload, discount_percent: addPercent.value === '' ? null : addPercent.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                picked.clear();
                results.value = [];
                query.value = '';
            },
            onFinish: () => (isAdding.value = false),
        },
    );
}
const addPicked = () => submitAdd({ product_ids: [...picked.keys()] });
function addCategory() {
    const cat = props.categories.find((c) => c.id === categoryId.value);
    if (!cat || !confirm(`Add every published product in "${cat.name}" (and its subcategories)?`)) return;
    submitAdd({ category_id: cat.id });
}

// ─── Existing products ───────────────────────────────────────────────────────
const listSearch = ref(props.filters.search ?? '');
let listTimer: ReturnType<typeof setTimeout> | null = null;
function filterList() {
    if (listTimer) clearTimeout(listTimer);
    listTimer = setTimeout(() => {
        router.get(route('admin.campaigns.show', { campaign: props.campaign.id }), { search: listSearch.value }, { preserveState: true, replace: true });
    }, 350);
}

function updateItem(item: CampaignItem, changes: { discount_percent?: string | number | null; sort_order?: number }) {
    const data: Record<string, any> = { ...changes };
    if ('discount_percent' in data && (data.discount_percent === '' || data.discount_percent === undefined)) data.discount_percent = null;
    router.patch(route('admin.campaigns.products.update', { campaign: props.campaign.id, productId: item.id }), data, {
        preserveScroll: true,
        preserveState: true,
    });
}

const selected = ref<number[]>([]);
const allSelected = computed(() => props.products.data.length > 0 && props.products.data.every((p) => selected.value.includes(p.id)));
function toggleSelectAll() {
    selected.value = allSelected.value ? [] : props.products.data.map((p) => p.id);
}
function remove(ids: number[]) {
    if (!ids.length || !confirm(`Remove ${ids.length} product(s) from this campaign?`)) return;
    router.delete(route('admin.campaigns.products.remove', { campaign: props.campaign.id }), {
        data: { product_ids: ids },
        preserveScroll: true,
        onSuccess: () => (selected.value = []),
    });
}

const storefrontUrl = computed(() => (props.campaign.type === 'daily_deal' ? '/deals/today' : `/deals/${props.campaign.slug}`));

const indexHref = route('admin.campaigns.index');

const breadcrumbs = [
    { title: 'Dashboard', href: route('admin.dashboard') },
    { title: 'Deals & Offers', href: route('admin.campaigns.index') },
    { title: props.campaign.title, href: route('admin.campaigns.show', { campaign: props.campaign.id }) },
];
</script>

<template>
    <Head :title="`${campaign.title} — Products`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-4 p-4">
            <!-- Campaign summary -->
            <div class="flex flex-col gap-3 rounded-lg bg-white p-4 shadow-sm md:flex-row md:items-center md:justify-between">
                <div>
                    <div class="text-xs font-semibold text-gray-500 uppercase">{{ types[campaign.type] }} · {{ campaign.status }}</div>
                    <h1 class="text-2xl font-semibold text-gray-800">{{ campaign.title }}</h1>
                    <p class="text-sm text-gray-500">
                        Default discount:
                        <strong>{{ campaign.discount_percent ? `${campaign.discount_percent}%` : 'none (featured only)' }}</strong>
                        · {{ products.total }} product(s). A per-product % overrides the default. If a product is in several live campaigns, the
                        biggest discount wins.
                    </p>
                </div>
                <div class="flex gap-2">
                    <a :href="storefrontUrl" target="_blank" class="inline-flex items-center gap-1 rounded border px-3 py-2 text-sm hover:bg-gray-50">
                        View on store <ExternalLink class="h-4 w-4" />
                    </a>
                    <Link :href="indexHref" class="rounded border px-3 py-2 text-sm hover:bg-gray-50">Back</Link>
                </div>
            </div>

            <!-- Add products -->
            <div class="rounded-lg bg-white p-4 shadow-sm">
                <h2 class="mb-3 text-lg font-semibold text-gray-800">Add products</h2>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-12">
                    <div class="relative md:col-span-5">
                        <Search class="absolute top-2.5 left-3 h-4 w-4 text-gray-400" />
                        <input
                            v-model="query"
                            @input="search"
                            type="text"
                            placeholder="Search by product name or code"
                            class="w-full rounded border py-2 pr-3 pl-9"
                        />
                    </div>
                    <select v-model="categoryId" @change="search" class="rounded border px-3 py-2 md:col-span-3">
                        <option value="">Any category</option>
                        <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.parent_id ? '— ' : '' }}{{ c.name }}</option>
                    </select>
                    <input
                        v-model="addPercent"
                        type="number"
                        min="0"
                        max="100"
                        step="0.01"
                        placeholder="% off (blank = campaign default)"
                        class="rounded border px-3 py-2 md:col-span-4"
                    />
                </div>

                <div v-if="isSearching" class="mt-3 text-sm text-gray-500">Searching…</div>
                <div v-else-if="results.length" class="mt-3 max-h-80 overflow-y-auto rounded border">
                    <label class="flex items-center gap-2 border-b bg-gray-50 px-3 py-2 text-xs font-medium text-gray-600">
                        <input type="checkbox" :checked="allResultsPicked" @change="togglePickAll" /> Select all {{ results.length }} results
                    </label>
                    <label v-for="r in results" :key="r.id" class="flex cursor-pointer items-center gap-3 border-b px-3 py-2 last:border-0 hover:bg-gray-50">
                        <input type="checkbox" :checked="picked.has(r.id)" @change="togglePick(r)" />
                        <img :src="r.image_url || '/fallback-image.png'" class="h-10 w-10 rounded border object-contain" />
                        <span class="flex-1 text-sm text-gray-800">{{ r.name }}</span>
                        <span class="text-sm text-gray-600">{{ money(r.price) }}</span>
                    </label>
                </div>
                <p v-else-if="query.length >= 2 || categoryId" class="mt-3 text-sm text-gray-500">No matching products that aren't already in this campaign.</p>

                <div class="mt-3 flex flex-wrap gap-2">
                    <button
                        @click="addPicked"
                        :disabled="!picked.size || isAdding"
                        class="rounded bg-primary px-4 py-2 text-sm text-white disabled:opacity-50"
                    >
                        Add {{ picked.size || '' }} selected
                    </button>
                    <button
                        v-if="categoryId"
                        @click="addCategory"
                        :disabled="isAdding"
                        class="rounded border border-primary px-4 py-2 text-sm text-primary disabled:opacity-50"
                    >
                        Add entire category
                    </button>
                </div>
            </div>

            <!-- Products in campaign -->
            <div class="rounded-lg bg-white p-4 shadow-sm">
                <div class="mb-3 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <h2 class="text-lg font-semibold text-gray-800">Products in this campaign</h2>
                    <div class="flex gap-2">
                        <input v-model="listSearch" @input="filterList" type="text" placeholder="Filter" class="rounded border px-3 py-2 text-sm" />
                        <button
                            v-if="selected.length"
                            @click="remove(selected)"
                            class="rounded border border-red-300 px-3 py-2 text-sm text-red-600 hover:bg-red-50"
                        >
                            Remove {{ selected.length }}
                        </button>
                    </div>
                </div>

                <div v-if="products.data.length" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                            <tr>
                                <th class="px-3 py-2 text-left"><input type="checkbox" :checked="allSelected" @change="toggleSelectAll" /></th>
                                <th class="px-3 py-2 text-left">Product</th>
                                <th class="px-3 py-2 text-left">Price</th>
                                <th class="px-3 py-2 text-left">% off override</th>
                                <th class="px-3 py-2 text-left">Deal price</th>
                                <th class="px-3 py-2 text-left">Order</th>
                                <th class="px-3 py-2 text-right"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="item in products.data" :key="item.id" class="hover:bg-gray-50">
                                <td class="px-3 py-2"><input type="checkbox" v-model="selected" :value="item.id" /></td>
                                <td class="px-3 py-2">
                                    <div class="flex items-center gap-3">
                                        <img :src="item.image_url || '/fallback-image.png'" class="h-10 w-10 rounded border object-contain" />
                                        <div>
                                            <div class="font-medium text-gray-800">{{ item.name }}</div>
                                            <div v-if="!item.is_published" class="text-xs text-amber-600">Not published — hidden on store</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-2 text-gray-700">{{ money(item.price) }}</td>
                                <td class="px-3 py-2">
                                    <input
                                        type="number"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                        :value="item.discount_percent ?? ''"
                                        :placeholder="campaign.discount_percent ? `${campaign.discount_percent} (default)` : 'none'"
                                        @change="updateItem(item, { discount_percent: ($event.target as HTMLInputElement).value })"
                                        class="w-32 rounded border px-2 py-1"
                                    />
                                </td>
                                <td class="px-3 py-2">
                                    <span class="font-semibold text-primary">{{ money(item.deal_price) }}</span>
                                    <span v-if="item.effective_percent" class="ml-1 text-xs text-green-700">-{{ item.effective_percent }}%</span>
                                </td>
                                <td class="px-3 py-2">
                                    <input
                                        type="number"
                                        min="0"
                                        :value="item.sort_order"
                                        @change="updateItem(item, { sort_order: Number(($event.target as HTMLInputElement).value) })"
                                        class="w-20 rounded border px-2 py-1"
                                    />
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <button @click="remove([item.id])" class="text-red-600 hover:text-red-800" title="Remove">
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div v-if="products.last_page > 1" class="mt-4 flex flex-wrap justify-end gap-1">
                        <Link
                            v-for="link in products.links"
                            :key="link.label"
                            :href="link.url ?? ''"
                            preserve-scroll
                            class="rounded border border-gray-300 px-3 py-1 text-sm hover:bg-gray-100"
                            :class="{ 'bg-gray-200 font-semibold': link.active, 'pointer-events-none opacity-50': !link.url }"
                        >
                            <span v-html="link.label" />
                        </Link>
                    </div>
                </div>
                <p v-else class="py-6 text-center text-sm text-gray-500">No products yet — search above to add some.</p>
            </div>
        </div>
    </AppLayout>
</template>
