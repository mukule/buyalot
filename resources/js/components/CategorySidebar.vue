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

const activeCategoryId = ref<number | null>(null);
const pinned = ref(false);

const visibleCategories = computed(() => props.categories.slice(0, limit));
const hasMore = computed(() => props.categories.length > limit);

const onCategoryEnter = (id: number) => {
    if (pinned.value) return;
    activeCategoryId.value = id;
};

const onCategoryLeave = () => {
    if (pinned.value) return;
    setTimeout(() => {
        if (!pinned.value) activeCategoryId.value = null;
    }, 60);
};

const onPanelEnter = () => {};

const onPanelLeave = () => {
    if (!pinned.value) activeCategoryId.value = null;
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

const goToCategoriesPage = () => {
    router.visit('/categories');
};

const activeCategory = computed(() => {
    return props.categories.find((c) => c.id === activeCategoryId.value) ?? null;
});
</script>

<template>
    <aside class="relative w-full rounded-lg bg-white shadow-md lg:w-[20.83%]">
        <ul class="p-1">
            <li
                v-for="cat in visibleCategories"
                :key="cat.id"
                class="relative flex items-center justify-between rounded-md px-3 py-2 transition hover:bg-gray-50"
                @mouseenter="onCategoryEnter(cat.id)"
                @mouseleave="onCategoryLeave"
                @click.prevent="onCategoryClick(cat.id)"
                role="button"
            >
                <a :href="`/category/${cat.slug}`" class="flex-1 text-sm text-gray-800 hover:text-primary" @click.stop>
                    {{ cat.name }}
                </a>
            </li>

            <!-- 'Other Categories' -->
            <li v-if="hasMore" class="px-3 py-2">
                <button class="w-full text-left text-sm text-gray-600 hover:text-primary" @click="goToCategoriesPage">Other Categories</button>
            </li>
        </ul>

        <!-- Right-side mega panel -->
        <transition name="fade">
            <div
                v-if="activeCategory && activeCategory.children?.length"
                class="absolute top-0 left-full z-40 ml-2 w-[520px] rounded-lg border bg-white shadow-lg"
                @mouseenter="onPanelEnter"
                @mouseleave="onPanelLeave"
            >
                <div class="p-4">
                    <div class="mb-3 flex items-center justify-between border-b pb-2">
                        <h3 class="text-sm font-semibold text-gray-800">
                            {{ activeCategory.name }}
                        </h3>
                        <a :href="`/category/${activeCategory.id}`" class="text-xs text-gray-500 hover:text-primary"> View all </a>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div v-for="child in activeCategory.children" :key="child.id" class="min-h-[40px]">
                            <h4 class="mb-2 text-sm font-medium text-gray-700">
                                <a :href="`/category/${child.id}`" class="hover:text-primary">
                                    {{ child.name }}
                                </a>
                            </h4>

                            <ul class="space-y-1 text-sm">
                                <li v-for="grand in child.children ?? []" :key="grand.id" class="text-gray-600 hover:text-primary">
                                    <a :href="`/category/${grand.id}`">
                                        {{ grand.name }}
                                    </a>
                                </li>

                                <li v-if="!(child.children && child.children.length)" class="text-gray-600">
                                    <a :href="`/category/${child.id}`" class="block hover:text-primary"> Browse {{ child.name }} </a>
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

@media (max-width: 1024px) {
    .absolute.left-full {
        left: calc(100% + 0.25rem) !important;
    }
}

a {
    text-decoration: none;
}
</style>
