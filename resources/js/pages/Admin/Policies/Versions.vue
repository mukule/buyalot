<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ArrowLeft, PlusIcon, Trash2, X } from 'lucide-vue-next';
import { reactive, ref } from 'vue';

// -------------------- Types --------------------
interface PolicyVersion {
    id: number;
    version_number: number;
    status: 0 | 1;
    effective_from?: string | null;
    created_at?: string | null;
    updated_at?: string | null;
    content: string;
}

// -------------------- Props --------------------
const page = usePage<
    AppPageProps & {
        policy: { id: number; title: string };
        versions: PolicyVersion[];
    }
>();

const policy = page.props.policy;
const reactiveVersions = reactive(page.props.versions ?? []);

// -------------------- Modal --------------------
const showModal = ref(false);
const modalContent = ref('');

// -------------------- Breadcrumbs --------------------
const breadcrumbs = reactive([
    { title: 'Dashboard', href: route('admin.dashboard') },
    { title: 'Policies', href: route('admin.policies.index') },
    { title: policy.title, href: '' },
]);

// -------------------- Actions --------------------
function openVersion(version: PolicyVersion) {
    modalContent.value = version.content;
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
}

function createVersion() {
    router.get(route('admin.policies.versions.create', { policy: policy.id }));
}

function editVersion(version: PolicyVersion) {
    router.get(route('admin.policies.versions.edit', { policy: policy.id, version: version.id }));
}

function deleteVersion(version: PolicyVersion) {
    if (!confirm(`Are you sure you want to delete version #${version.version_number}?`)) return;

    router.delete(route('admin.policies.versions.destroy', { policy: policy.id, version: version.id }), {
        onSuccess: () => {
            const index = reactiveVersions.findIndex((v) => v.id === version.id);
            if (index !== -1) reactiveVersions.splice(index, 1);
        },
    });
}

function goBack() {
    router.get(route('admin.policies.index'));
}

// -------------------- Helpers --------------------
function formatDate(date?: string | null) {
    return date ? date.slice(0, 10) : '-';
}
</script>

<template>
    <Head :title="`Policy Versions - ${policy.title}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="card rounded-lg bg-white p-4 shadow-sm">
                <!-- Header -->
                <div class="relative mb-4 flex items-center justify-between">
                    <button @click="goBack" class="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
                        <ArrowLeft class="h-4 w-4" /> Back
                    </button>
                    <h1 class="absolute left-1/2 -translate-x-1/2 transform text-2xl font-semibold">Versions - {{ policy.title }}</h1>
                    <button
                        @click="createVersion"
                        class="hover:bg-primary-dark ml-auto flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-white"
                    >
                        <PlusIcon class="h-4 w-4" /> New Version
                    </button>
                </div>

                <!-- Versions Table -->
                <div v-if="reactiveVersions.length" class="overflow-x-auto">
                    <table class="w-full table-auto border-collapse border border-gray-200">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="border px-4 py-2 text-left">Version #</th>
                                <th class="border px-4 py-2 text-left">Status</th>
                                <th class="border px-4 py-2 text-left">Last Updated At</th>
                                <th class="border px-4 py-2 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="version in reactiveVersions" :key="version.id" class="hover:bg-gray-50">
                                <td class="border px-4 py-2 font-semibold">{{ version.version_number }}</td>
                                <td class="border px-4 py-2">{{ version.status ? 'Active' : 'Inactive' }}</td>
                                <td class="border px-4 py-2">{{ formatDate(version.updated_at) }}</td>
                                <td class="flex items-center gap-2 border px-4 py-2">
                                    <button @click="openVersion(version)" class="text-sm text-blue-600 hover:underline">View Content</button>
                                    <button @click="editVersion(version)" class="text-sm text-green-600 hover:underline">Edit</button>
                                    <button @click="deleteVersion(version)" class="flex items-center gap-1 text-sm text-red-600 hover:underline">
                                        <Trash2 class="h-4 w-4" /> Delete
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-else class="p-8 text-center">
                    <PlusIcon class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No versions found</h3>
                    <p class="mt-1 text-sm text-gray-500">Create a version from the policy page to get started.</p>
                    <button
                        @click="createVersion"
                        class="hover:bg-primary-dark mx-auto mt-4 flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-white"
                    >
                        <PlusIcon class="h-4 w-4" /> New Version
                    </button>
                </div>
            </div>

            <!-- Modal -->
            <transition name="fade">
                <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                    <div class="relative w-full max-w-2xl rounded-xl bg-white p-6 shadow-lg">
                        <button @click="closeModal" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700">
                            <X class="h-5 w-5" />
                        </button>
                        <h2 class="mb-4 text-lg font-semibold">Version Content</h2>
                        <div class="max-h-[400px] overflow-y-auto text-gray-700" v-html="modalContent"></div>
                    </div>
                </div>
            </transition>
        </div>
    </AppLayout>
</template>

<style>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
