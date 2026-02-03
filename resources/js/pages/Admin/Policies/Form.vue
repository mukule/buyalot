<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps, BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { Save } from 'lucide-vue-next';

/**
 * Policy interface (metadata only)
 */
interface Policy {
    id: number;
    title: string;
    scope: string;
    status: 0 | 1;
    is_mandatory: boolean;
}

const page = usePage<
    AppPageProps<{
        policy: Policy | null;
        scopes: string[];
    }>
>();

const isEdit = !!page.props.policy;

/**
 * Breadcrumbs
 */
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Policies', href: '/admin/policies' },
    { title: isEdit ? 'Edit Policy' : 'Create Policy', href: '' },
];

/**
 * Form state
 */
const form = useForm({
    title: page.props.policy?.title ?? '',
    scope: page.props.policy?.scope ?? page.props.scopes?.[0] ?? '',
    status: page.props.policy?.status ?? 1,
    is_mandatory: page.props.policy?.is_mandatory ? 1 : 0,
});

function submit() {
    if (isEdit && page.props.policy) {
        form.put(route('admin.policies.update', { policy: page.props.policy.id }));
    } else {
        form.post(route('admin.policies.store'));
    }
}

function goBack() {
    router.get(route('admin.policies.index'));
}
</script>

<template>
    <Head :title="isEdit ? 'Edit Policy' : 'Create Policy'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="flex h-full flex-1 flex-col space-y-4 rounded-xl bg-white p-4">
                <!-- Header -->
                <div class="flex items-center justify-between">
                    <h4 class="text-2xl font-bold">
                        {{ isEdit ? 'Edit Policy' : 'Create Policy' }}
                    </h4>

                    <Link
                        href="/admin/policies"
                        class="inline-flex items-center rounded border border-[color:var(--primary)] bg-transparent px-3 py-1.5 text-sm font-medium text-[color:var(--primary)] transition hover:bg-[color:var(--primary)] hover:text-white"
                    >
                        ← Back
                    </Link>
                </div>

                <hr class="my-1 border-[color:var(--border)]" />

                <!-- Form -->
                <form @submit.prevent="submit" class="mt-2 space-y-5 px-2">
                    <!-- Title + Scope -->
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <!-- Title -->
                        <div>
                            <label class="mb-1 block font-semibold">Title</label>
                            <input
                                v-model="form.title"
                                type="text"
                                class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                            />
                            <div v-if="form.errors.title" class="mt-1 text-sm text-red-600">
                                {{ form.errors.title }}
                            </div>
                        </div>

                        <!-- Scope -->
                        <div>
                            <label class="mb-1 block font-semibold">Scope</label>
                            <select
                                v-model="form.scope"
                                class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                            >
                                <option v-for="s in page.props.scopes" :key="s" :value="s">
                                    {{ s }}
                                </option>
                            </select>
                            <div v-if="form.errors.scope" class="mt-1 text-sm text-red-600">
                                {{ form.errors.scope }}
                            </div>
                        </div>
                    </div>

                    <!-- Mandatory + Status Row -->
                    <div class="flex items-center gap-6">
                        <!-- Mandatory -->
                        <div class="flex items-center gap-2">
                            <input
                                id="mandatory"
                                type="checkbox"
                                v-model="form.is_mandatory"
                                true-value="1"
                                false-value="0"
                                class="h-4 w-4 rounded border border-[color:var(--border)]"
                            />
                            <label for="mandatory" class="font-semibold">Mandatory policy</label>
                        </div>

                        <!-- Status -->
                        <div class="flex items-center gap-2">
                            <input
                                id="status"
                                type="checkbox"
                                v-model="form.status"
                                true-value="1"
                                false-value="0"
                                class="h-4 w-4 rounded border border-[color:var(--border)]"
                            />
                            <label for="status" class="font-semibold">Active</label>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" class="rounded border px-4 py-2" @click="goBack">Cancel</button>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex items-center gap-2 rounded bg-[color:var(--primary)] px-4 py-2 text-white transition hover:bg-[color:var(--secondary)] disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <Save class="h-4 w-4" />
                            {{ isEdit ? 'Update Policy' : 'Create Policy' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
