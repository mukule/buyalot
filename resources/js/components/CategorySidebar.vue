<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

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

/**
 * COMPUTED
 */
const visibleCategories = computed(() => props.categories.slice(0, limit));
const hasMore = computed(() => props.categories.length > limit);

const activeCategory = computed(() => props.categories.find((c) => c.id === activeCategoryId.value) ?? null);

// Dynamic panel width based on banner area (10/12 of container)
const megaPanelWidth = computed(() => {
    if (!activeCategory.value?.children?.length) return '0px';
    const columnWidth = 220; // width of each column
    const gap = 16; // 1rem gap in px
    const numColumns = activeCategory.value.children.length;

    // Banner width fraction and container max-width
    const bannerFraction = 10 / 12;
    const containerMaxWidth = 1280; // Tailwind container xl breakpoint
    const maxPanelWidth = bannerFraction * containerMaxWidth;

    const totalWidth = Math.min(numColumns * columnWidth + (numColumns - 1) * gap, maxPanelWidth);
    return `${totalWidth}px`;
});

/**
 * EVENTS
 */
const onCategoryEnter = (id: number) => {
    if (!pinned.value) {
        activeCategoryId.value = id;
    }
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
    if (!pinned.value && !hoveringPanel.value) {
        activeCategoryId.value = null;
    }
};

const onPanelEnter = () => {
    hoveringPanel.value = true;
};

const onPanelLeave = () => {
    hoveringPanel.value = false;
    if (!pinned.value) {
        activeCategoryId.value = null;
    }
};

const goToCategoriesPage = () => router.visit('/categories');
</script>

<template>
    <aside class="relative w-full rounded-lg bg-white shadow-md" @mouseleave="onMenuLeave">
        <!-- LEFT: DEPARTMENTS -->
        <ul class="p-1">
            <li
                v-for="cat in visibleCategories"
                :key="cat.id"
                class="flex cursor-pointer items-center justify-between rounded-md px-3 py-2 text-sm text-gray-800 transition hover:bg-gray-100"
                @mouseenter="onCategoryEnter(cat.id)"
                @click.prevent="onCategoryClick(cat.id)"
            >
                <a :href="`/${cat.slug}`" class="flex-1 truncate hover:text-primary" @click.stop>
                    {{ cat.name }}
                </a>

                <span class="text-xs text-gray-400">›</span>
            </li>

            <li v-if="hasMore" class="px-3 py-2">
                <button class="w-full text-left text-sm text-gray-600 hover:text-primary" @click="goToCategoriesPage">Other Categories</button>
            </li>
        </ul>

        <!-- RIGHT: MEGA PANEL -->
        <transition name="fade">
            <div
                v-if="activeCategory && activeCategory.children?.length"
                class="mega-panel absolute inset-y-0 left-full z-50 ml-2 max-w-[calc(100vw-2rem)] overflow-hidden rounded-lg border bg-white shadow-xl"
                :style="{ width: megaPanelWidth }"
                @mouseenter="onPanelEnter"
                @mouseleave="onPanelLeave"
            >
                <div class="mega-columns p-6">
                    <div v-for="group in activeCategory.children" :key="group.id" class="mega-group">
                        <h4 class="mb-3 truncate text-sm font-medium text-gray-700">
                            <a :href="`/${group.slug}`" class="hover:text-primary">{{ group.name }}</a>
                        </h4>
                        <ul class="space-y-2 text-sm">
                            <li v-for="child in group.children ?? []" :key="child.id" class="truncate text-gray-600 hover:text-primary">
                                <a :href="`/${child.slug}`">{{ child.name }}</a>
                            </li>
                        </ul>
                    </div>

                    <!-- More Menus link if content exceeds banner width -->
                    <div v-if="activeCategory.children.length * 220 > (10 / 12) * 1280" class="mega-group flex items-center justify-center">
                        <a href="/categories" class="text-sm font-medium text-primary">More Menus ›</a>
                    </div>
                </div>
            </div>
        </transition>
    </aside>
</template>

<style scoped>
/* FADE + SLIDE ANIMATION */
.fade-enter-active,
.fade-leave-active {
    transition:
        opacity 0.15s ease,
        transform 0.15s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: translateX(-6px);
}

.fade-enter-to,
.fade-leave-from {
    opacity: 1;
    transform: translateX(0);
}

/* MEGA PANEL */
.mega-panel {
    height: 100%; /* Matches sidebar height */
}

/* NEWSPAPER-STYLE MULTI-COLUMNS */
.mega-columns {
    column-width: 220px; /* width of each column */
    column-gap: 1rem; /* space between columns */
    column-fill: auto; /* fill top -> bottom first */
    max-height: 100%;
    overflow: hidden;
}

.mega-group {
    display: inline-block; /* required for columns to work */
    width: 100%;
    break-inside: avoid; /* prevent a group from splitting across columns */
    margin-bottom: 1.5rem; /* space below each group */
}

/* Links */
a {
    text-decoration: none;
}
</style>
