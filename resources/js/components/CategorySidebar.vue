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
const megaPanelHeight = ref('auto');
const megaPanelStyle = ref({
    width: '0px',
    height: 'auto',
    left: '100%',
    top: '0',
});

/**
 * REACTIVE VIEWPORT WIDTH
 */
const viewportWidth = ref(window.innerWidth);
const viewportHeight = ref(window.innerHeight);

const updateViewport = () => {
    viewportWidth.value = window.innerWidth;
    viewportHeight.value = window.innerHeight;
};

onMounted(() => {
    window.addEventListener('resize', updateViewport);
    updateViewport();
});

onUnmounted(() => {
    window.removeEventListener('resize', updateViewport);
});

/**
 * Calculate mega panel position and size
 */
const updateMegaPanelPosition = () => {
    if (!sidebarRef.value || !megaPanelRef.value || !activeCategoryId.value) return;

    // Get sidebar position and dimensions
    const sidebarRect = sidebarRef.value.getBoundingClientRect();

    // Get the banner section container
    const bannerSection = sidebarRef.value.closest('.flex.flex-col.lg\\:flex-row');
    let bannerHeight = sidebarRect.height;

    if (bannerSection) {
        bannerHeight = bannerSection.clientHeight;
    } else {
        // Fallback to parent container
        const parentContainer = sidebarRef.value.parentElement;
        if (parentContainer) {
            bannerHeight = parentContainer.clientHeight;
        }
    }

    // Calculate panel width based on content
    const columnWidth = 220;
    const gap = 16;
    const activeCategory = props.categories.find((c) => c.id === activeCategoryId.value);
    const numColumns = activeCategory?.children?.length || 0;

    let panelWidth = numColumns * columnWidth + (numColumns - 1) * gap;

    // Constrain width to available space
    const availableSpace = viewportWidth.value - sidebarRect.right - 32; // 32px for margins
    panelWidth = Math.min(panelWidth, availableSpace, 1200); // Max 1200px

    // Check if panel would overflow the right edge
    const panelRightEdge = sidebarRect.right + panelWidth + 8; // +8 for margin-left

    // If panel would overflow, position it to the left instead
    let leftPosition = '100%';
    if (panelRightEdge > viewportWidth.value - 16) {
        // 16px for safety margin
        // Position to the left of sidebar
        leftPosition = `-${panelWidth + 8}px`;
    }

    // Update style
    megaPanelStyle.value = {
        width: `${panelWidth}px`,
        height: `${bannerHeight}px`,
        left: leftPosition,
        top: '0',
    };
};

/**
 * Watch for active category changes
 */
watchEffect(() => {
    if (activeCategoryId.value) {
        nextTick(() => {
            updateMegaPanelPosition();
        });
    }
});

/**
 * Watch for viewport changes
 */
watchEffect(() => {
    if (activeCategoryId.value) {
        updateMegaPanelPosition();
    }
});

/**
 * COMPUTED
 */
const visibleCategories = computed(() => props.categories.slice(0, limit));
const hasMore = computed(() => props.categories.length > limit);
const activeCategory = computed(() => props.categories.find((c) => c.id === activeCategoryId.value) ?? null);

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
    if (!pinned.value && !hoveringPanel.value) activeCategoryId.value = null;
};

const onPanelEnter = () => (hoveringPanel.value = true);

const onPanelLeave = () => {
    hoveringPanel.value = false;
    if (!pinned.value) activeCategoryId.value = null;
};

const goToCategoriesPage = () => router.visit('/categories');
</script>

<template>
    <aside ref="sidebarRef" class="relative h-full w-full rounded-lg bg-white shadow-md" @mouseleave="onMenuLeave">
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
                ref="megaPanelRef"
                class="mega-panel absolute z-50 overflow-y-auto rounded-lg border bg-white shadow-xl"
                :style="megaPanelStyle"
                @mouseenter="onPanelEnter"
                @mouseleave="onPanelLeave"
            >
                <div class="mega-columns p-4">
                    <div v-for="group in activeCategory.children" :key="group.id" class="mega-group">
                        <h4 class="mb-2 truncate text-sm font-medium text-gray-700">
                            <a :href="`/${group.slug}`" class="hover:text-primary">{{ group.name }}</a>
                        </h4>
                        <ul class="space-y-1 text-sm">
                            <li v-for="child in group.children ?? []" :key="child.id" class="truncate text-gray-600 hover:text-primary">
                                <a :href="`/${child.slug}`">{{ child.name }}</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </transition>
    </aside>
</template>

<style scoped>
/* Ensure sidebar takes full height */
aside {
    height: 100%;
}

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
    box-sizing: border-box;
    margin-left: 8px;
}

/* FLEXBOX MULTI-COLUMNS (responsive) */
.mega-columns {
    display: flex;
    flex-wrap: wrap;
    gap: 1.5rem;
}

.mega-group {
    flex: 0 0 220px;
    min-width: 0; /* Important for truncation */
}

/* Links */
a {
    text-decoration: none;
}

/* Optional: Add scrollbar styling for the mega panel */
.mega-panel::-webkit-scrollbar {
    width: 6px;
}

.mega-panel::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.mega-panel::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
}

.mega-panel::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* Responsive adjustments */
@media (max-width: 1536px) {
    .mega-group {
        flex: 0 0 200px;
    }
}

@media (max-width: 1280px) {
    .mega-group {
        flex: 0 0 180px;
    }
}
</style>
