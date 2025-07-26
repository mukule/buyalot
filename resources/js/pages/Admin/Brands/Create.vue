<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    categories: { id: number; name: string }[];
    subcategories: { id: number; name: string; category_id: number }[];
}>();

const title = 'Create Brand';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Brands', href: '/admin/brands' },
    { title, href: '' },
];

const logoFile = ref<File | null>(null);
const logoPreview = ref<string | null>(null);

const form = ref({
    name: '',
    active: true,
    category_id: '' as string | number,
    subcategory_id: '' as string | number,
});

// Filter subcategories based on selected category
const filteredSubcategories = computed(() => {
    if (!form.value.category_id) return [];
    return props.subcategories.filter((sub) => sub.category_id === Number(form.value.category_id));
});

function onLogoChange(event: Event) {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (file) {
        logoFile.value = file;
        logoPreview.value = URL.createObjectURL(file);
    } else {
        logoFile.value = null;
        logoPreview.value = null;
    }
}

// Clear subcategory when category changes
watch(
    () => form.value.category_id,
    () => {
        form.value.subcategory_id = '';
    },
);

async function submit() {
    const formData = new FormData();
    formData.append('name', form.value.name);
    formData.append('active', form.value.active ? '1' : '0');
    if (form.value.category_id) formData.append('category_id', String(form.value.category_id));
    if (form.value.subcategory_id) formData.append('subcategory_id', String(form.value.subcategory_id));
    if (logoFile.value) {
        formData.append('logo_path', logoFile.value);
    }

    try {
        await axios.post('/admin/brands', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        window.location.href = '/admin/brands';
    } catch (error: unknown) {
        if (axios.isAxiosError(error)) {
            console.error('Submit error:', error.response?.data || error.message);
        } else {
            console.error('Unexpected error:', error);
        }
    }
}
</script>

<template>
    <Head :title="title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="flex h-full flex-1 flex-col space-y-4 rounded-xl bg-white p-4 text-[color:var(--card-foreground)]">
                <div class="flex items-center justify-between">
                    <h4 class="text-2xl font-bold">{{ title }}</h4>
                    <Link href="/admin/brands" class="text-sm text-[color:var(--primary)] hover:underline">← Back</Link>
                </div>

                <hr class="my-1 border-[color:var(--border)]" />

                <form @submit.prevent="submit" class="mt-2 space-y-4 px-4" enctype="multipart/form-data">
                    <!-- Brand Name -->
                    <div>
                        <input
                            v-model="form.name"
                            id="name"
                            type="text"
                            required
                            placeholder="Enter brand name"
                            class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                        />
                    </div>

                    <!-- Category Select -->
                    <div class="flex gap-4">
                        <!-- Category Select -->
                        <div class="flex-1">
                            <label for="category" class="mb-1 block font-semibold">Category*</label>
                            <select
                                id="category"
                                v-model="form.category_id"
                                required
                                class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                            >
                                <option value="" disabled>Select a category</option>
                                <option v-for="category in props.categories" :key="category.id" :value="category.id">
                                    {{ category.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Subcategory Select -->
                        <div class="flex-1">
                            <label for="subcategory" class="mb-1 block font-semibold">Subcategory*</label>
                            <select
                                id="subcategory"
                                v-model="form.subcategory_id"
                                required
                                :disabled="!form.category_id"
                                class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none disabled:cursor-not-allowed disabled:bg-gray-100"
                            >
                                <option value="" disabled>Select a subcategory</option>
                                <option v-for="subcategory in filteredSubcategories" :key="subcategory.id" :value="subcategory.id">
                                    {{ subcategory.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Logo Upload -->
                    <div>
                        <label for="logo" class="mb-1 block font-semibold">Brand Logo (optional)</label>
                        <input
                            id="logo"
                            name="logo_path"
                            type="file"
                            accept="image/*"
                            @change="onLogoChange"
                            class="block w-full rounded border border-[color:var(--border)] p-1 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                        />

                        <div v-if="logoPreview" class="mt-2">
                            <img :src="logoPreview" alt="Logo Preview" class="h-20 w-auto rounded border" />
                        </div>
                    </div>

                    <button
                        type="submit"
                        class="rounded bg-[color:var(--primary)] px-4 py-2 text-white transition-colors duration-200 hover:bg-[color:var(--secondary)] hover:text-[color:var(--secondary-foreground)]"
                    >
                        Submit
                    </button>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
