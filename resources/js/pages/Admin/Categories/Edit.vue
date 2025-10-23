<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Category, type VariantCategory, AppPageProps } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

interface PageProps
    extends AppPageProps<{
        category: Category & { hashid: string; parent_id?: number | null; variant_categories?: VariantCategory[] };
        categories: Category[];
        variantCategories: VariantCategory[];
    }> {}

const page = usePage<PageProps>();
const category = page.props.category;
const allCategories = page.props.categories || [];
const allVariantCategories = page.props.variantCategories || [];

// Determine pre-checked variants
let defaultVariantIds: number[] = [];
if (category.variant_categories && category.variant_categories.length > 0) {
    defaultVariantIds = category.variant_categories.map((v) => v.id);
} else {
    const defaultVariant = allVariantCategories.find((v) => v.default);
    defaultVariantIds = defaultVariant ? [defaultVariant.id] : [];
}

// Title & Breadcrumbs
const title = 'Edit Category';
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Categories', href: '/admin/categories' },
    { title, href: '' },
];

// Form
const form = useForm({
    name: category.name,
    slug: category.slug,
    active: category.active ?? true,
    parent_id: category.parent_id ?? ('' as number | ''),
    variant_categories: defaultVariantIds,
});

// Parent category search
const parentSearch = ref(category.parent_id ? allCategories.find((c) => c.id === category.parent_id)?.name || '' : '');
const showDropdown = ref(false);
const dropdownRef = ref<HTMLElement | null>(null);

const filteredParents = computed(() => {
    const candidates = allCategories.filter((c) => c.id !== category.id);
    if (!parentSearch.value) return candidates.slice(0, 5);
    return candidates.filter((c) => c.name.toLowerCase().includes(parentSearch.value.toLowerCase())).slice(0, 5);
});

function selectParent(c: Category) {
    form.parent_id = c.id;
    parentSearch.value = c.name;
    showDropdown.value = false;
}

function handleClickOutside(event: MouseEvent) {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
        showDropdown.value = false;
    }
}

onMounted(() => document.addEventListener('click', handleClickOutside));
onBeforeUnmount(() => document.removeEventListener('click', handleClickOutside));
</script>

<template>
    <Head :title="title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="flex flex-col space-y-4 rounded-xl bg-white p-4 text-[color:var(--card-foreground)]">
                <!-- Header -->
                <div class="flex items-center justify-between">
                    <h4 class="text-2xl font-bold">{{ title }}</h4>
                    <Link href="/admin/categories" class="text-sm text-[color:var(--primary)] hover:underline">← Back</Link>
                </div>

                <hr class="my-1 border-[color:var(--border)]" />

                <form @submit.prevent="form.put(`/admin/categories/${category.hashid}`)" class="mt-2 space-y-4 px-4">
                    <!-- Name -->
                    <div>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            placeholder="Enter category name"
                            class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                        />
                        <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</div>
                    </div>

                    <!-- Parent Category -->
                    <div class="relative" ref="dropdownRef">
                        <label for="parent" class="mb-1 block font-semibold">Parent Category (optional)</label>
                        <input
                            id="parent"
                            type="text"
                            v-model="parentSearch"
                            @focus="showDropdown = true"
                            placeholder="Search parent category"
                            class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                        />

                        <ul
                            v-if="showDropdown && filteredParents.length"
                            class="absolute z-10 mt-1 max-h-48 w-full overflow-auto rounded border border-gray-200 bg-white shadow-lg"
                        >
                            <li
                                v-for="parent in filteredParents"
                                :key="parent.id"
                                @mousedown.prevent="selectParent(parent)"
                                class="cursor-pointer px-3 py-2 hover:bg-gray-100"
                            >
                                {{ parent.name }}
                            </li>
                        </ul>
                    </div>

                    <!-- Variant Categories -->
                    <div>
                        <label class="mb-1 block font-semibold">Variants</label>
                        <div class="flex flex-wrap gap-2">
                            <label
                                v-for="variant in allVariantCategories"
                                :key="variant.id"
                                class="inline-flex cursor-pointer items-center space-x-2 px-2 py-1"
                            >
                                <input
                                    type="checkbox"
                                    :value="variant.id"
                                    v-model="form.variant_categories"
                                    :checked="variant.default && form.variant_categories.length === 0"
                                    class="h-4 w-4 rounded text-primary focus:ring-primary"
                                />
                                <span>{{ variant.name }}</span>
                            </label>
                        </div>
                        <div v-if="form.errors.variant_categories" class="mt-1 text-sm text-red-600">
                            {{ form.errors.variant_categories }}
                        </div>
                    </div>

                    <!-- Active -->
                    <div class="flex items-center space-x-2">
                        <input
                            id="active"
                            type="checkbox"
                            v-model="form.active"
                            class="h-4 w-4 rounded border border-gray-300 text-primary focus:ring-primary"
                        />
                        <label for="active" class="select-none">Active</label>
                    </div>

                    <!-- Submit -->
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded bg-[color:var(--primary)] px-4 py-2 text-white transition-colors duration-200 hover:bg-[color:var(--secondary)] hover:text-[color:var(--secondary-foreground)]"
                    >
                        {{ form.processing ? 'Updating...' : 'Update' }}
                    </button>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
