<script setup lang="ts">
import MainLayout from '@/layouts/MainLayout.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { Filter, Heart, ShoppingCart, Star, XCircle } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

// ==========================
// Props
// ==========================
const props = defineProps<{
    category: {
        id: number;
        name: string;
        slug: string;
        getHierarchy?: any;
        parent_id?: number | null;
        breadcrumbs?: { id: number; name: string; slug: string }[];
    };
    breadcrumbs?: { id: number; name: string; slug: string }[];
    title?: string;
    products?: any;
    subcategories?: { id: number; name: string; slug: string }[];
    selectedSubcategory?: number | null;
    brands?: { id: number; name: string }[];
    selectedBrands?: number[];
}>();

// ==========================
// State & Computed
// ==========================
const breadcrumbTrail = computed(() => props.breadcrumbs ?? []);
const productsArray = computed(() => (Array.isArray(props.products) ? props.products : (props.products?.data ?? [])));

// Normalize selectedSubcategory type
const selectedSubcategory = ref<number | null>(props.selectedSubcategory != null ? Number(props.selectedSubcategory) : null);

// Mobile filter drawer
const showFilters = ref(false);

// ==========================
// Wishlist & Cart
// ==========================
const page = usePage();
const isAddingWishlist = ref(false);
const isAddingToCart = ref(false);

const isInWishlist = (variantId: number) => page.props.auth?.wishlistVariantIds?.includes(variantId) ?? false;

const isInCart = (variantId: number) => page.props.auth?.cartItems?.some((item: any) => item.product_variant_id === variantId) ?? false;

const toggleWishlist = (variantId: number) => {
    if (isAddingWishlist.value) return;
    isAddingWishlist.value = true;
    router.post(
        route('wishlist.store'),
        { product_variant_id: variantId },
        { preserveScroll: true, onFinish: () => (isAddingWishlist.value = false) },
    );
};

const addToCart = (variantId: number) => {
    if (isAddingToCart.value || isInCart(variantId)) return;
    isAddingToCart.value = true;
    router.post(
        route('cart.store'),
        { product_variant_id: variantId, quantity: 1 },
        { preserveScroll: true, onFinish: () => (isAddingToCart.value = false) },
    );
};

// ==========================
// Filters
// ==========================
const minPrice = ref<number | null>(Number(new URLSearchParams(window.location.search).get('min_price')) || null);
const maxPrice = ref<number | null>(Number(new URLSearchParams(window.location.search).get('max_price')) || null);

const selectedBrands = ref<number[]>((props.selectedBrands ?? []).map((b) => Number(b)));
const brandSearch = ref('');

const applyFilters = (extraParams: Record<string, any> = {}) => {
    const params: Record<string, any> = { ...extraParams };
    if (selectedSubcategory.value) params.subcategory = selectedSubcategory.value;
    if (minPrice.value) params.min_price = minPrice.value;
    if (maxPrice.value) params.max_price = maxPrice.value;
    if (selectedBrands.value.length) params.brands = selectedBrands.value.join(',');

    router.get(route('category.show', props.category.slug), params, { preserveScroll: true, preserveState: false });
};

// Subcategory
const filterBySubcategory = (subcategoryId: number | null) => {
    selectedSubcategory.value = subcategoryId;
    applyFilters({ subcategory: subcategoryId });
};

// Price
const applyPriceFilter = debounce(() => applyFilters(), 500);
watch([minPrice, maxPrice], applyPriceFilter);

const clearPriceFilters = () => {
    minPrice.value = null;
    maxPrice.value = null;
    applyFilters();
};

// Brand filter
const filteredBrands = computed(() => {
    if (!props.brands) return [];
    if (!brandSearch.value) return props.brands;
    return props.brands.filter((b) => b.name.toLowerCase().includes(brandSearch.value.toLowerCase()));
});

watch(selectedBrands, () => applyFilters());

// ==========================
// Utilities
// ==========================
const formatPrice = (amount: number | string | null) => {
    if (amount == null) return 'KSh 0';
    const num = typeof amount === 'string' ? parseFloat(amount) : amount;
    return isNaN(num) ? 'KSh 0' : `KSh ${num.toLocaleString()}`;
};
</script>

<template>
    <MainLayout>
        <section class="mt-4 mb-4 flex flex-col gap-6">
            <!-- Banner -->
            <div class="relative w-full overflow-hidden rounded-sm">
                <img src="/buyalot1.png" alt="Category banner" class="h-auto w-full object-cover" loading="lazy" />
            </div>

            <!-- Breadcrumb -->
            <nav class="text-sm text-gray-600" aria-label="Breadcrumb">
                <ol class="flex flex-wrap items-center gap-1">
                    <li>
                        <a href="/" class="text-primary hover:underline">Home</a>
                        <span class="mx-1">/</span>
                    </li>
                    <li v-for="(crumb, index) in breadcrumbTrail" :key="crumb.id" class="flex items-center">
                        <a :href="`/${crumb.slug}`" class="text-primary hover:underline">{{ crumb.name }}</a>
                        <span v-if="index < breadcrumbTrail.length - 1" class="mx-1"> / </span>
                    </li>
                    <li class="truncate font-semibold text-gray-800">/ {{ props.category.name }}</li>
                </ol>
            </nav>

            <!-- Mobile Filter Button -->
            <div class="flex justify-end lg:hidden">
                <button @click="showFilters = true" class="flex items-center gap-1 rounded border px-3 py-1 text-sm text-gray-700 hover:bg-gray-100">
                    <Filter class="h-4 w-4" /> Filters
                </button>
            </div>

            <!-- Layout -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                <!-- Left Filters (Desktop) -->
                <aside class="col-span-3 hidden rounded-lg border bg-white p-4 shadow-sm lg:block">
                    <h2 class="mb-3 text-base font-semibold text-gray-800">Filters</h2>
                    <div v-if="props.subcategories?.length" class="mb-5">
                        <h3 class="mb-2 text-sm font-medium text-gray-700">Categories</h3>
                        <ul class="space-y-2">
                            <li>
                                <button
                                    @click="filterBySubcategory(null)"
                                    :class="[
                                        'w-full rounded px-2 py-1 text-left text-sm transition',
                                        !selectedSubcategory ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100',
                                    ]"
                                >
                                    All
                                </button>
                            </li>
                            <li v-for="sub in props.subcategories" :key="sub.id">
                                <button
                                    @click="filterBySubcategory(sub.id)"
                                    :class="[
                                        'w-full rounded px-2 py-1 text-left text-sm transition',
                                        selectedSubcategory === sub.id ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100',
                                    ]"
                                >
                                    {{ sub.name }}
                                </button>
                            </li>
                        </ul>
                    </div>

                    <!-- Price -->
                    <div class="mb-5">
                        <h3 class="mb-2 text-sm font-medium text-gray-700">Price Range (KSh)</h3>
                        <div class="mb-2 flex items-center gap-2">
                            <input
                                v-model.number="minPrice"
                                type="number"
                                placeholder="Min"
                                class="w-1/2 rounded border px-2 py-1 text-sm focus:border-primary focus:ring-0"
                            />
                            <span>-</span>
                            <input
                                v-model.number="maxPrice"
                                type="number"
                                placeholder="Max"
                                class="w-1/2 rounded border px-2 py-1 text-sm focus:border-primary focus:ring-0"
                            />
                        </div>
                        <button
                            v-if="minPrice || maxPrice"
                            @click="clearPriceFilters"
                            class="flex items-center gap-1 text-xs text-gray-500 transition hover:text-primary"
                        >
                            <XCircle class="h-4 w-4" /> Clear Price Filters
                        </button>
                    </div>

                    <!-- Brands -->
                    <div v-if="props.brands?.length" class="mb-5">
                        <h3 class="mb-2 text-sm font-medium text-gray-700">Brands</h3>
                        <input
                            v-model="brandSearch"
                            type="text"
                            placeholder="Search brand..."
                            class="mb-2 w-full rounded border px-2 py-1 text-sm focus:border-primary focus:ring-0"
                        />
                        <div class="max-h-32 overflow-y-auto pr-1">
                            <label
                                v-for="brand in filteredBrands.slice(0, 10)"
                                :key="brand.id"
                                class="mb-1 flex items-center gap-2 text-sm text-gray-700"
                            >
                                <input
                                    type="checkbox"
                                    :value="brand.id"
                                    v-model="selectedBrands"
                                    class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                                />
                                <span>{{ brand.name }}</span>
                            </label>
                        </div>
                        <button
                            v-if="selectedBrands.length"
                            @click="selectedBrands = []"
                            class="mt-2 flex items-center gap-1 text-xs text-gray-500 hover:text-primary"
                        >
                            <XCircle class="h-4 w-4" /> Clear Brand Filters
                        </button>
                    </div>
                </aside>

                <!-- Right Products -->
                <div class="col-span-12 lg:col-span-9">
                    <div v-if="productsArray.length" class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-4 xl:grid-cols-5">
                        <div
                            v-for="product in productsArray"
                            :key="product.id"
                            class="relative flex flex-col justify-between rounded-lg border bg-white p-3 shadow-sm transition-shadow duration-200 hover:shadow-md"
                        >
                            <span
                                v-if="product.discount_percent > 0"
                                class="absolute top-2 right-2 z-10 rounded bg-secondary/75 px-2 py-1 text-xs font-bold text-white"
                                >{{ Math.round(product.discount_percent) }} % OFF</span
                            >
                            <Link :href="`/products/${product.product_slug}?v=${product.id}`" class="block">
                                <div class="relative flex h-40 w-full justify-center overflow-hidden rounded-md bg-gray-50 sm:h-48 md:h-56">
                                    <img
                                        :src="product.primary_image_url ?? ''"
                                        :alt="product.name"
                                        loading="lazy"
                                        class="h-full w-full object-contain opacity-0 mix-blend-multiply transition-opacity duration-500"
                                        @load="($event.target as HTMLImageElement).classList.remove('opacity-0')"
                                    />
                                </div>
                            </Link>
                            <div class="mt-3 flex flex-col gap-1">
                                <h3 class="line-clamp-2 text-sm font-medium text-gray-700">{{ product.name }}</h3>

                                <p class="text-xs text-gray-400">{{ product.brand ?? '' }}</p>

                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-semibold text-primary">{{ formatPrice(product.final_price) }}</span>
                                    <span v-if="product.marked_price > product.final_price" class="text-xs text-gray-400 line-through">{{
                                        formatPrice(product.marked_price)
                                    }}</span>
                                </div>
                                <div class="mt-1 flex items-center justify-between text-yellow-400">
                                    <div class="flex gap-[2px]">
                                        <Star
                                            v-for="i in 5"
                                            :key="i"
                                            :class="i <= (product.rating ?? 0) ? 'fill-yellow-400' : 'fill-gray-200'"
                                            class="h-3 w-3"
                                        />
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Heart
                                            :class="[
                                                'h-6 w-6 cursor-pointer rounded-full p-1 transition-all hover:scale-110',
                                                isAddingWishlist ? 'cursor-wait opacity-50' : '',
                                                isInWishlist(product.id)
                                                    ? 'bg-secondary text-white'
                                                    : 'text-secondary hover:bg-primary hover:text-white',
                                            ]"
                                            @click.stop.prevent="toggleWishlist(product.id)"
                                        />
                                        <ShoppingCart
                                            :class="[
                                                'h-6 w-6 cursor-pointer rounded-full p-1 transition-all hover:scale-110',
                                                isAddingToCart ? 'cursor-wait opacity-50' : '',
                                                isInCart(product.id) ? 'bg-primary text-white' : 'text-primary hover:bg-secondary hover:text-white',
                                            ]"
                                            @click.stop.prevent="addToCart(product.id)"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="py-10 text-center text-gray-500">No products found in this category.</div>
                </div>
            </div>

            <!-- Mobile Filters Drawer -->
            <Transition name="fade">
                <div v-if="showFilters" class="fixed inset-0 z-50 flex items-end bg-black/40 lg:hidden" @click.self="showFilters = false">
                    <div class="relative max-h-[80vh] w-full overflow-y-auto rounded-t-2xl bg-white p-4">
                        <button @click="showFilters = false" class="absolute top-3 right-4 text-gray-500 hover:text-primary">
                            <XCircle class="h-5 w-5" />
                        </button>
                        <!-- Reuse filter section -->
                        <div class="flex flex-col gap-4">
                            <!-- Categories, Price, Brands go here (same as desktop filters) -->
                            <div v-if="props.subcategories?.length" class="mb-5">
                                <h3 class="mb-2 text-sm font-medium text-gray-700">Categories</h3>
                                <ul class="space-y-2">
                                    <li>
                                        <button
                                            @click="filterBySubcategory(null)"
                                            :class="[
                                                'w-full rounded px-2 py-1 text-left text-sm transition',
                                                !selectedSubcategory ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100',
                                            ]"
                                        >
                                            All
                                        </button>
                                    </li>
                                    <li v-for="sub in props.subcategories" :key="sub.id">
                                        <button
                                            @click="filterBySubcategory(sub.id)"
                                            :class="[
                                                'w-full rounded px-2 py-1 text-left text-sm transition',
                                                selectedSubcategory === sub.id ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100',
                                            ]"
                                        >
                                            {{ sub.name }}
                                        </button>
                                    </li>
                                </ul>
                            </div>

                            <!-- Price -->
                            <div class="mb-5">
                                <h3 class="mb-2 text-sm font-medium text-gray-700">Price Range (KSh)</h3>
                                <div class="mb-2 flex items-center gap-2">
                                    <input
                                        v-model.number="minPrice"
                                        type="number"
                                        placeholder="Min"
                                        class="w-1/2 rounded border px-2 py-1 text-sm focus:border-primary focus:ring-0"
                                    />
                                    <span>-</span>
                                    <input
                                        v-model.number="maxPrice"
                                        type="number"
                                        placeholder="Max"
                                        class="w-1/2 rounded border px-2 py-1 text-sm focus:border-primary focus:ring-0"
                                    />
                                </div>
                                <button
                                    v-if="minPrice || maxPrice"
                                    @click="clearPriceFilters"
                                    class="flex items-center gap-1 text-xs text-gray-500 transition hover:text-primary"
                                >
                                    <XCircle class="h-4 w-4" /> Clear Price Filters
                                </button>
                            </div>

                            <!-- Brands -->
                            <div v-if="props.brands?.length" class="mb-5">
                                <h3 class="mb-2 text-sm font-medium text-gray-700">Brands</h3>
                                <input
                                    v-model="brandSearch"
                                    type="text"
                                    placeholder="Search brand..."
                                    class="mb-2 w-full rounded border px-2 py-1 text-sm focus:border-primary focus:ring-0"
                                />
                                <div class="max-h-32 overflow-y-auto pr-1">
                                    <label
                                        v-for="brand in filteredBrands.slice(0, 10)"
                                        :key="brand.id"
                                        class="mb-1 flex items-center gap-2 text-sm text-gray-700"
                                    >
                                        <input
                                            type="checkbox"
                                            :value="brand.id"
                                            v-model="selectedBrands"
                                            class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                                        />
                                        <span>{{ brand.name }}</span>
                                    </label>
                                </div>
                                <button
                                    v-if="selectedBrands.length"
                                    @click="selectedBrands = []"
                                    class="mt-2 flex items-center gap-1 text-xs text-gray-500 hover:text-primary"
                                >
                                    <XCircle class="h-4 w-4" /> Clear Brand Filters
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </section>
    </MainLayout>
</template>
