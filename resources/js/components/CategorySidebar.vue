<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, onUnmounted, ref, watchEffect } from 'vue';

interface Category {
    id: number;
    name: string;
    slug: string;
    children?: Category[];
}

interface Props {
    categories: Category[];
    limit?: number;
}

const props = defineProps<Props>();
const limit = props.limit ?? 11;

/**
 * STATE
 */
const activeCategoryId = ref<number | null>(null);
const pinned = ref(false);
const hoveringPanel = ref(false);
const sidebarRef = ref<HTMLElement>();
const megaPanelRef = ref<HTMLElement>();

const megaPanelStyle = ref({
    width: 'auto',
    height: 'auto',
    left: 'calc(100% + 16px)',
    top: '0',
});

/**
 * POSITIONING LOGIC
 */
const updateMegaPanelPosition = () => {
    if (!sidebarRef.value || !activeCategoryId.value) return;

    const bannerSection = sidebarRef.value.closest('.flex-col.lg\\:flex-row');
    if (!bannerSection) return;

    // The hero banner container (lg:w-10/12)
    const heroWrapper = bannerSection.querySelector('.lg\\:w-10\\/12');

    if (heroWrapper) {
        const heroRect = heroWrapper.getBoundingClientRect();

        // 1. Calculate content-based width
        const columnWidth = 220;
        const gap = 32; // 2rem gap
        const padding = 48; // p-6 on both sides (24px * 2)
        const numCols = groupedColumns.value.length;

        const naturalWidth = numCols * columnWidth + (numCols - 1) * gap + padding;

        // 2. Cap width at hero banner width
        const finalWidth = Math.min(naturalWidth, heroRect.width);

        megaPanelStyle.value = {
            width: `${finalWidth}px`,
            height: `${heroRect.height}px`,
            left: 'calc(100% + 16px)',
            top: '0',
        };
    }
};

const handleResize = () => {
    if (activeCategoryId.value) updateMegaPanelPosition();
};

onMounted(() => window.addEventListener('resize', handleResize));
onUnmounted(() => window.removeEventListener('resize', handleResize));

watchEffect(() => {
    if (activeCategoryId.value) {
        nextTick(() => updateMegaPanelPosition());
    }
});

/**
 * COMPUTED
 */
const visibleCategories = computed(() => props.categories.slice(0, limit));
const hasMore = computed(() => props.categories.length > limit);
const activeCategory = computed(() => props.categories.find((c) => c.id === activeCategoryId.value) ?? null);

const groupedColumns = computed(() => {
    if (!activeCategory.value?.children) return [];
    const children = activeCategory.value.children;

    const MAX_ITEMS_PER_COLUMN = 15;
    const MIN_COLUMNS = 2;

    let totalItems = 0;
    const groupItemCounts: number[] = [];
    for (const group of children) {
        const count = 1 + (group.children?.length || 0);
        groupItemCounts.push(count);
        totalItems += count;
    }

    const numColumns = Math.max(Math.ceil(totalItems / MAX_ITEMS_PER_COLUMN), MIN_COLUMNS);
    const columns: Category[][] = [];
    let currentColumn: Category[] = [];
    let currentColumnItemCount = 0;
    const targetItemsPerColumn = Math.ceil(totalItems / numColumns);

    for (let i = 0; i < children.length; i++) {
        const group = children[i];
        const groupItemCount = groupItemCounts[i];

        if (currentColumnItemCount > 0 && currentColumnItemCount + groupItemCount > targetItemsPerColumn && columns.length < numColumns - 1) {
            columns.push([...currentColumn]);
            currentColumn = [];
            currentColumnItemCount = 0;
        }
        currentColumn.push(group);
        currentColumnItemCount += groupItemCount;
    }

    if (currentColumn.length > 0) columns.push(currentColumn);
    while (columns.length < MIN_COLUMNS) columns.push([]);
    return columns;
});

/**
 * EVENTS
 */
let closeTimeout: ReturnType<typeof setTimeout> | null = null;

const onCategoryEnter = (id: number) => {
    if (closeTimeout) clearTimeout(closeTimeout);
    if (!pinned.value) activeCategoryId.value = id;
};

const onCategoryClick = (id: number) => {
    if (pinned.value && activeCategoryId.value === id) {
        pinned.value = false;
        activeCategoryId.value = null;
    } else {
        pinned.value = true;
        activeCategoryId.value = id;
    }
};

const onMenuLeave = () => {
    closeTimeout = setTimeout(() => {
        if (!pinned.value && !hoveringPanel.value) activeCategoryId.value = null;
    }, 100);
};

const onPanelEnter = () => {
    if (closeTimeout) clearTimeout(closeTimeout);
    hoveringPanel.value = true;
};

const onPanelLeave = () => {
    hoveringPanel.value = false;
    if (!pinned.value) activeCategoryId.value = null;
};

const goToCategoriesPage = () => router.visit('/categories');
</script>

<template>
    <aside ref="sidebarRef" class="relative h-full w-full rounded-lg bg-white shadow-md" @mouseleave="onMenuLeave">
        <ul class="p-1">
            <li
                v-for="cat in visibleCategories"
                :key="cat.id"
                class="flex cursor-pointer items-center justify-between rounded-md px-3 py-2 text-sm text-gray-800 transition hover:bg-gray-100"
                :class="{ 'bg-gray-50 font-medium text-primary': activeCategoryId === cat.id }"
                @mouseenter="onCategoryEnter(cat.id)"
                @click.prevent="onCategoryClick(cat.id)"
            >
                <a :href="`/${cat.slug}`" class="flex-1 truncate" @click.stop>
                    {{ cat.name }}
                </a>
                <span class="text-xs text-gray-400">›</span>
            </li>

            <li v-if="hasMore" class="mt-1 border-t px-3 py-2">
                <button class="w-full text-left text-sm font-medium text-gray-600 hover:text-primary" @click="goToCategoriesPage">
                    All Categories
                </button>
            </li>
        </ul>

        <transition name="fade">
            <div
                v-if="activeCategory && activeCategory.children?.length"
                ref="megaPanelRef"
                class="mega-panel absolute z-50 overflow-y-auto rounded-lg border bg-white shadow-xl"
                :style="megaPanelStyle"
                @mouseenter="onPanelEnter"
                @mouseleave="onPanelLeave"
            >
                <div class="mega-columns h-full p-6">
                    <div v-for="(column, colIndex) in groupedColumns" :key="colIndex" class="mega-column">
                        <div v-for="group in column" :key="group.id" class="mega-group">
                            <h4 class="mb-2 truncate border-b pb-1 text-sm font-bold text-gray-900">
                                <a :href="`/${group.slug}`" class="hover:text-primary">{{ group.name }}</a>
                            </h4>
                            <ul class="space-y-1.5 text-sm">
                                <li v-for="child in group.children ?? []" :key="child.id" class="truncate text-gray-600 hover:text-primary">
                                    <a :href="`/${child.slug}`">{{ child.name }}</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </aside>
</template>

<style scoped>
aside {
    height: 100%;
}

/* ANIMATION */
.fade-enter-active,
.fade-leave-active {
    transition:
        opacity 0.15s ease,
        transform 0.15s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: translateX(-8px);
}

/* THE BRIDGE: Prevents closing when moving mouse across the 16px gap */
.mega-panel::before {
    content: '';
    position: absolute;
    top: 0;
    left: -20px;
    width: 20px;
    height: 100%;
    background: transparent;
}

/* FLEX COLUMNS */
.mega-columns {
    display: flex;
    gap: 2rem;
    align-items: flex-start;
    justify-content: flex-start;
}

.mega-column {
    flex: 0 0 220px; /* Fixed width for items */
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

/* Custom Scrollbar */
.mega-panel::-webkit-scrollbar {
    width: 4px;
}
.mega-panel::-webkit-scrollbar-track {
    background: #f1f1f1;
}
.mega-panel::-webkit-scrollbar-thumb {
    background: #ccc;
    border-radius: 10px;
}
</style>
