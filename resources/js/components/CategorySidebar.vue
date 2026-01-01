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
    <!-- SINGLE HOVER CONTAINER -->
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
                class="absolute inset-y-0 left-full z-50 ml-2 max-w-[900px] min-w-[600px] rounded-lg border bg-white shadow-xl"
                @mouseenter="onPanelEnter"
                @mouseleave="onPanelLeave"
            >
                <div class="p-4">
                    <!-- Header -->
                    <div class="mb-3 flex items-center justify-between border-b pb-2">
                        <h3 class="max-w-[70%] truncate text-sm font-semibold text-gray-800">
                            {{ activeCategory.name }}
                        </h3>
                        <a :href="`/${activeCategory.slug}`" class="text-xs text-gray-500 hover:text-primary"> View all </a>
                    </div>

                    <!-- CATEGORY GROUPS -->
                    <div class="grid grid-cols-3 gap-x-8 gap-y-4">
                        <div v-for="group in activeCategory.children" :key="group.id">
                            <h4 class="mb-1 truncate text-sm font-medium text-gray-700">
                                <a :href="`/${group.slug}`" class="hover:text-primary">
                                    {{ group.name }}
                                </a>
                            </h4>

                            <ul class="space-y-1 text-sm">
                                <li v-for="child in group.children ?? []" :key="child.id" class="truncate text-gray-600 hover:text-primary">
                                    <a :href="`/${child.slug}`">
                                        {{ child.name }}
                                    </a>
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
/* FADE + SLIDE */
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

/* MOBILE SAFETY */
@media (max-width: 1024px) {
    .absolute.left-full {
        left: calc(100% + 0.25rem);
    }
}

a {
    text-decoration: none;
}
</style>
