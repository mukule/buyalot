<script setup lang="ts">
import Pagination from '@/components/Pagination.vue';
import VerticalFilters from '@/components/marketplace/VerticalFilters.vue';
import VerticalProductCard from '@/components/marketplace/VerticalProductCard.vue';
import { router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { ArrowLeftRight, Filter, LayoutGrid, List, SlidersHorizontal, X } from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';

const props = withDefaults(
    defineProps<{
        vertical: { key: string; label: string; tagline: string; type: string; icon: string };
        filterSchema: any[];
        sorts: { key: string; label: string }[];
        filterOptions: Record<string, (string | number)[]>;
        activeFilters: Record<string, any>;
        products: any;
        quickTags?: { label: string; params: Record<string, any> }[];
    }>(),
    { quickTags: () => [] },
);

const showFilters = ref(false);
const view = ref<'grid' | 'list'>('grid');

// Build the reactive form from the schema + echoed active filters.
const initialForm = () => {
    const form: Record<string, any> = {};
    for (const f of props.filterSchema) {
        if (f.type === 'select') {
            form[f.key] = props.activeFilters[f.key] ?? null;
        } else if (f.type === 'range') {
            form[f.min] = props.activeFilters[f.min] ?? null;
            form[f.max] = props.activeFilters[f.max] ?? null;
        }
    }
    form.sort = props.activeFilters.sort ?? props.sorts[0]?.key ?? 'newest';
    return form;
};

const form = reactive(initialForm());

const productList = computed(() => props.products?.data ?? []);
const paginationLinks = computed(() => props.products?.links ?? []);
const total = computed(() => props.products?.total ?? productList.value.length);

const apply = (opts: { preserveState?: boolean } = {}) => {
    const params: Record<string, any> = {};
    for (const [key, value] of Object.entries(form)) {
        if (value !== null && value !== '' && value !== undefined) {
            params[key] = value;
        }
    }
    showFilters.value = false;
    router.get(route('marketplace.show', props.vertical.key), params, {
        preserveScroll: true,
        // Keep the component (and thus focus + typed values) mounted while typing
        // in the debounced range inputs; discrete actions (selects, tags) re-seed.
        preserveState: opts.preserveState ?? false,
        replace: true,
    });
};

// While typing a range, preserve state so the input keeps focus and its value.
const applyDebounced = debounce(() => apply({ preserveState: true }), 500);

// Quick tags: one-tap filter presets. A tag is active when all its params are
// currently applied; tapping toggles it on/off.
const isTagActive = (tag: { params: Record<string, any> }) =>
    Object.entries(tag.params).every(([k, v]) => String(form[k] ?? '') === String(v));

const toggleTag = (tag: { params: Record<string, any> }) => {
    const active = isTagActive(tag);
    for (const [k, v] of Object.entries(tag.params)) {
        form[k] = active ? null : v;
    }
    apply();
};
</script>

<template>
    <section class="mt-4 mb-10">
        <!-- Header -->
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900 sm:text-2xl">{{ vertical.label }}</h1>
                <p class="text-sm text-gray-500">{{ vertical.tagline }}</p>
            </div>
            <a
                href="/marketplace"
                class="inline-flex w-fit items-center gap-1.5 rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-700 transition hover:bg-gray-50"
            >
                <ArrowLeftRight class="h-4 w-4" /> Change marketplace
            </a>
        </div>

        <!-- Quick tags (horizontally scrollable on mobile) -->
        <div v-if="quickTags.length" class="no-scrollbar mb-4 -mx-1 flex gap-2 overflow-x-auto px-1 pb-1 sm:flex-wrap sm:overflow-visible">
            <button
                v-for="tag in quickTags"
                :key="tag.label"
                type="button"
                :class="[
                    'shrink-0 rounded-full border px-3 py-1 text-xs font-medium whitespace-nowrap transition',
                    isTagActive(tag)
                        ? 'border-primary bg-primary text-white'
                        : 'border-gray-300 bg-white text-gray-600 hover:border-primary/60 hover:text-primary',
                ]"
                @click="toggleTag(tag)"
            >
                {{ tag.label }}
            </button>
        </div>

        <!-- Controls row -->
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <p class="text-sm text-gray-500">{{ total }} result{{ total === 1 ? '' : 's' }}</p>
            <div class="flex items-center gap-2">
                <!-- View toggle -->
                <div class="hidden items-center rounded-md border border-gray-300 sm:flex">
                    <button
                        type="button"
                        class="flex h-8 w-8 items-center justify-center rounded-l-md transition"
                        :class="view === 'grid' ? 'bg-primary text-white' : 'text-gray-500 hover:bg-gray-50'"
                        aria-label="Grid view"
                        @click="view = 'grid'"
                    >
                        <LayoutGrid class="h-4 w-4" />
                    </button>
                    <button
                        type="button"
                        class="flex h-8 w-8 items-center justify-center rounded-r-md transition"
                        :class="view === 'list' ? 'bg-primary text-white' : 'text-gray-500 hover:bg-gray-50'"
                        aria-label="List view"
                        @click="view = 'list'"
                    >
                        <List class="h-4 w-4" />
                    </button>
                </div>
                <div class="flex items-center gap-1.5">
                    <SlidersHorizontal class="hidden h-4 w-4 text-gray-400 sm:block" />
                    <select
                        v-model="form.sort"
                        class="rounded-md border border-gray-300 px-2 py-1.5 text-sm text-gray-700 focus:border-primary focus:ring-0"
                        @change="apply"
                    >
                        <option v-for="s in sorts" :key="s.key" :value="s.key">{{ s.label }}</option>
                    </select>
                </div>
                <button
                    type="button"
                    class="flex items-center gap-1.5 rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-700 transition hover:bg-gray-50 md:hidden"
                    @click="showFilters = true"
                >
                    <Filter class="h-4 w-4" /> Filters
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-12">
            <!-- Desktop filters -->
            <aside class="sticky top-36 col-span-4 hidden h-fit self-start rounded-lg border bg-white p-4 shadow-sm md:block lg:col-span-3">
                <VerticalFilters
                    :schema="filterSchema"
                    :options="filterOptions"
                    :form="form"
                    @apply="apply"
                    @apply-debounced="applyDebounced"
                />
            </aside>

            <!-- Products -->
            <div class="col-span-12 md:col-span-8 lg:col-span-9">
                <div
                    v-if="productList.length"
                    class="grid gap-3"
                    :class="view === 'list' ? 'grid-cols-1' : 'grid-cols-2 md:grid-cols-3 xl:grid-cols-4'"
                >
                    <VerticalProductCard
                        v-for="product in productList"
                        :key="product.id"
                        :product="product"
                        :type="vertical.type"
                        :layout="view"
                    />
                </div>

                <div v-else class="flex flex-col items-center gap-2 rounded-lg border border-dashed py-16 text-center text-gray-500">
                    <Filter class="h-8 w-8 text-gray-300" />
                    <p>No listings match your filters.</p>
                </div>

                <div v-if="paginationLinks.length > 3" class="mt-8 flex justify-center lg:justify-end">
                    <Pagination :links="paginationLinks" />
                </div>
            </div>
        </div>

        <!-- Mobile filter drawer -->
        <Transition name="fade">
            <div v-if="showFilters" class="fixed inset-0 z-50 flex items-end bg-black/40 md:hidden" @click.self="showFilters = false">
                <div class="relative max-h-[85vh] w-full overflow-y-auto rounded-t-2xl bg-white p-4 pb-8">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-base font-semibold text-gray-800">Filters</h2>
                        <button class="text-gray-500 hover:text-primary" @click="showFilters = false">
                            <X class="h-5 w-5" />
                        </button>
                    </div>
                    <VerticalFilters
                        :schema="filterSchema"
                        :options="filterOptions"
                        :form="form"
                        :columns="2"
                        @apply="apply"
                        @apply-debounced="applyDebounced"
                    />
                    <button
                        type="button"
                        class="sticky bottom-0 mt-6 w-full rounded-md bg-primary py-3 text-sm font-semibold text-white shadow-lg"
                        @click="apply"
                    >
                        Show {{ total }} result{{ total === 1 ? '' : 's' }}
                    </button>
                </div>
            </div>
        </Transition>
    </section>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
