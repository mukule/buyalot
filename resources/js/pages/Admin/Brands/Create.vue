<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const title = 'Create Brand';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Brands', href: '/admin/brands' },
    { title, href: '' },
];

// Form state
const form = ref({
    name: '',
    active: true,
});

const logoFile = ref<File | null>(null);
const logoPreview = ref<string | null>(null);
const submitting = ref(false);

// Handle logo selection + preview
function onLogoChange(event: Event) {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    if (!file) {
        logoFile.value = null;
        logoPreview.value = null;
        return;
    }

    logoFile.value = file;
    logoPreview.value = URL.createObjectURL(file);
}

// Submit form
function submit() {
    if (submitting.value) return;
    submitting.value = true;

    const formData = new FormData();
    formData.append('name', form.value.name);
    formData.append('active', form.value.active ? '1' : '0');

    if (logoFile.value) {
        formData.append('logo_path', logoFile.value);
    }

    router.post('/admin/brands', formData, {
        forceFormData: true,

        onSuccess: () => {
            router.visit('/admin/brands');
        },

        onFinish: () => {
            submitting.value = false;
        },
    });
}
</script>

<template>
    <Head :title="title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="flex h-full flex-1 flex-col space-y-4 rounded-xl bg-white p-4 text-[color:var(--card-foreground)]">
                <div class="flex items-center justify-between">
                    <h4 class="text-2xl font-bold">{{ title }}</h4>
                    <Link href="/admin/brands" class="text-sm text-[color:var(--primary)] hover:underline"> ← Back </Link>
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

                    <!-- Logo Upload -->
                    <div>
                        <label for="logo" class="mb-1 block font-semibold"> Brand Logo (optional) </label>

                        <input
                            id="logo"
                            type="file"
                            accept="image/*"
                            @change="onLogoChange"
                            class="block w-full rounded border border-[color:var(--border)] p-1 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                        />

                        <div v-if="logoPreview" class="mt-2">
                            <img :src="logoPreview" alt="Logo Preview" class="h-20 w-auto rounded border object-contain" />
                        </div>
                    </div>

                    <!-- Active -->
                    <div class="flex items-center space-x-2">
                        <input
                            type="checkbox"
                            id="active"
                            v-model="form.active"
                            class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                        />
                        <label for="active" class="font-semibold">Active</label>
                    </div>

                    <!-- Submit -->
                    <button
                        type="submit"
                        :disabled="submitting"
                        class="rounded bg-[color:var(--primary)] px-4 py-2 text-white transition hover:bg-[color:var(--secondary)] hover:text-[color:var(--secondary-foreground)] disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {{ submitting ? 'Submitting...' : 'Submit' }}
                    </button>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
