<script lang="ts">
// 1. Define and Export the interface so it's available everywhere
export interface Category {
    id: number;
    name: string;
    slug: string;
    children?: Category[];
}
</script>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, h, nextTick, onMounted, onUnmounted, ref, watchEffect } from 'vue';

// 2. Props Definition
interface Props {
    categories: Category[];
    limit?: number;
}

const props = defineProps<Props>();
const limit = props.limit ?? 20;

/**
 * RECURSIVE COMPONENT (Functional)
 * This handles the rendering of levels 3, 4, and 5
 */
const RecursiveList = (props: { items: Category[]; depth: number }) => {
    return props.items.map((item) => {
        const hasChildren = item.children && item.children.length > 0 && props.depth < 5;

        return h('li', { key: item.id, class: 'list-none' }, [
            h(
                'a',
                {
                    href: `/${item.slug}`,
                    class: [
                        'block transition-colors hover:text-primary',
                        props.depth === 3 ? 'text-gray-700 text-xs' : 'text-gray-500 text-xs mt-0.5',
                    ],
                },
                item.name,
            ),
            hasChildren
                ? h('ul', { class: 'ml-3 mt-1 border-l pl-2 space-y-1' }, [h(RecursiveList, { items: item.children!, depth: props.depth + 1 })])
                : null,
        ]);
    });
};

/**
 * STATE & REFS
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
 * RECURSIVE COUNTER
 */
const countNodes = (cat: Category, currentDepth: number, maxDepth: number): number => {
    if (currentDepth >= maxDepth || !cat.children?.length) return 1;
    return 1 + cat.children.reduce((acc, child) => acc + countNodes(child, currentDepth + 1, maxDepth), 0);
};

/**
 * POSITIONING LOGIC
 */
const updateMegaPanelPosition = () => {
    if (!sidebarRef.value || !activeCategoryId.value) return;
    const bannerSection = sidebarRef.value.closest('.flex-col.lg\\:flex-row');
    if (!bannerSection) return;
    const heroWrapper = bannerSection.querySelector('.lg\\:w-10\\/12');

    if (heroWrapper) {
        const heroRect = heroWrapper.getBoundingClientRect();
        const columnWidth = 220;
        const gap = 32;
        const padding = 48;
        const numCols = groupedColumns.value.length;

        const naturalWidth = numCols * columnWidth + (numCols - 1) * gap + padding;
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

    const MAX_ITEMS_PER_COLUMN = 22;
    const MIN_COLUMNS = 2;

    const groupHeights = children.map((group) => countNodes(group, 2, 5));
    const totalHeight = groupHeights.reduce((a, b) => a + b, 0);

    const numColumns = Math.max(Math.ceil(totalHeight / MAX_ITEMS_PER_COLUMN), MIN_COLUMNS);
    const targetHeight = Math.ceil(totalHeight / numColumns);

    const columns: Category[][] = [];
    let currentColumn: Category[] = [];
    let currentHeight = 0;

    for (let i = 0; i < children.length; i++) {
        const group = children[i];
        const height = groupHeights[i];

        if (currentHeight > 0 && currentHeight + height > targetHeight && columns.length < numColumns - 1) {
            columns.push([...currentColumn]);
            currentColumn = [];
            currentHeight = 0;
        }
        currentColumn.push(group);
        currentHeight += height;
    }

    if (currentColumn.length > 0) columns.push(currentColumn);
    while (columns.length < MIN_COLUMNS) columns.push([]);
    return columns;
});

/**
 * EVENTS
 */
let closeTimeout: any = null;
const onCategoryEnter = (id: number) => {
    if (closeTimeout) clearTimeout(closeTimeout);
    if (!pinned.value) activeCategoryId.value = id;
};
const onCategoryClick = (id: number) => {
    pinned.value = pinned.value && activeCategoryId.value === id ? false : true;
    activeCategoryId.value = pinned.value ? id : null;
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
                class="flex cursor-pointer items-center justify-between rounded-md px-3 py-0.5 text-xs text-gray-800 transition hover:bg-gray-100"
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
                class="mega-panel absolute z-30 overflow-y-auto rounded-lg border bg-white shadow-xl"
                :style="megaPanelStyle"
                @mouseenter="onPanelEnter"
                @mouseleave="onPanelLeave"
            >
                <div class="mega-columns h-full p-2.5">
                    <div v-for="(column, colIndex) in groupedColumns" :key="colIndex" class="mega-column">
                        <div v-for="group in column" :key="group.id" class="mega-group">
                            <h4 class="mb-2 truncate border-b pb-1 text-xs font-bold text-gray-900">
                                <a :href="`/${group.slug}`" class="hover:text-primary">{{ group.name }}</a>
                            </h4>

                            <ul class="space-y-2 text-xs">
                                <RecursiveList :items="group.children || []" :depth="3" />
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
.mega-panel::before {
    content: '';
    position: absolute;
    top: 0;
    left: -20px;
    width: 20px;
    height: 100%;
    background: transparent;
}
.mega-columns {
    display: flex;
    gap: 1.25rem;
    align-items: flex-start;
}
.mega-column {
    flex: 0 0 220px;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}
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
