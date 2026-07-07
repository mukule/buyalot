<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps, BreadcrumbItem } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Save } from 'lucide-vue-next';
import { defineAsyncComponent } from 'vue';

// Lazy-loaded so the CKEditor bundle stays out of the main chunk.
const RichTextEditor = defineAsyncComponent(() => import('@/components/RichTextEditor.vue'));

// -------------------- Types --------------------
interface PolicyVersion {
    id?: number;
    version_number?: number;
    content: string;
    effective_from?: string | null;
    status: number; // 0 or 1
}

// -------------------- Page Props --------------------
const page = usePage<
    AppPageProps<{
        policy: { id: number; title: string };
        version?: PolicyVersion;
    }>
>();

const isEdit = !!page.props.version;
const policy = page.props.policy;

// -------------------- Breadcrumbs --------------------
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: route('admin.dashboard') },
    { title: 'Policies', href: route('admin.policies.index') },
    { title: policy.title, href: route('admin.policies.versions.index', { policy: policy.id }) },
    {
        title: isEdit ? `Edit Version #${page.props.version?.version_number}` : 'New Version',
        href: '',
    },
];

// -------------------- Form --------------------
// Ensure status is numeric 0|1
const form = useForm<PolicyVersion>({
    content: page.props.version?.content ?? '',
    effective_from: page.props.version?.effective_from ?? '',
    status: Number(page.props.version?.status ?? 0),
});

// -------------------- Actions --------------------
function submit() {
    // No manual coercion needed; form.status is already 0 or 1
    if (isEdit && page.props.version?.id) {
        form.put(
            route('admin.policies.versions.update', {
                policy: policy.id,
                version: page.props.version.id,
            }),
        );
    } else {
        form.post(route('admin.policies.versions.store', { policy: policy.id }));
    }
}

function goBack() {
    router.get(route('admin.policies.versions.index', { policy: policy.id }));
}
</script>

<template>
    <Head :title="isEdit ? `Edit Version #${page.props.version?.version_number}` : 'New Version'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="flex flex-1 flex-col space-y-4 rounded-xl bg-white p-4">
                <!-- Header -->
                <div class="flex items-center justify-between">
                    <h4 class="text-2xl font-bold">
                        {{ isEdit ? `Edit Version #${page.props.version?.version_number}` : 'Create New Version' }}
                    </h4>

                    <button
                        @click="goBack"
                        class="inline-flex items-center rounded border border-[color:var(--primary)] bg-transparent px-3 py-1.5 text-sm font-medium text-[color:var(--primary)] transition hover:bg-[color:var(--primary)] hover:text-white"
                    >
                        <ArrowLeft class="mr-1 h-4 w-4" />
                        Back
                    </button>
                </div>

                <hr class="my-1 border-[color:var(--border)]" />

                <!-- Form -->
                <form @submit.prevent="submit" class="mt-2 space-y-5 px-2">
                    <!-- Content -->
                    <div>
                        <label class="mb-1 block font-semibold">Content</label>
                        <RichTextEditor
                            v-model="form.content"
                            placeholder="Enter version content..."
                            class="min-h-[200px] rounded-md border border-[color:var(--border)] bg-white"
                        />
                        <div v-if="form.errors.content" class="mt-1 text-sm text-red-600">
                            {{ form.errors.content }}
                        </div>
                    </div>

                    <!-- Effective Date -->
                    <!-- <div>
                        <label class="mb-1 block font-semibold">Effective From (optional)</label>
                        <input
                            v-model="form.effective_from"
                            type="date"
                            class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                        />
                        <div v-if="form.errors.effective_from" class="mt-1 text-sm text-red-600">
                            {{ form.errors.effective_from }}
                        </div>
                    </div> -->

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

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" class="rounded border px-4 py-2" @click="goBack">Cancel</button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex items-center gap-2 rounded bg-[color:var(--primary)] px-4 py-2 text-white transition hover:bg-[color:var(--secondary)] disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <Save class="h-4 w-4" />
                            {{ isEdit ? 'Update Version' : 'Create Version' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
