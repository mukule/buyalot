<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps, DocumentType } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage<
    AppPageProps<{
        documentTypes: {
            data: (DocumentType & { hashid: string })[];
            current_page: number;
            last_page: number;
            total: number;
            prev_page_url: string | null;
            next_page_url: string | null;
        };
    }>
>();

const documentTypes = computed(() => page.props.documentTypes.data);

const breadcrumbs = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Verification Documents', href: '/admin/document-types' },
];

function goToCreatePage() {
    router.get(route('admin.document-types.create'));
}

function editDocumentType(hashid: string) {
    router.get(route('admin.document-types.edit', hashid));
}

function deleteDocumentType(hashid: string) {
    if (confirm('Are you sure you want to delete this document type?')) {
        router.delete(route('admin.document-types.destroy', hashid));
    }
}
</script>

<template>
    <Head title="Verification Documents" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="card flex flex-col gap-6 rounded-lg bg-white p-4 shadow-sm">
                <!-- Header -->
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-semibold">Verification Documents</h1>
                    <button @click="goToCreatePage" class="hover:bg-primary-dark rounded-xl bg-primary px-4 py-2 text-white">+ New</button>
                </div>

                <hr />

                <!-- Document Types List -->
                <ul class="list-none divide-y divide-gray-200">
                    <li v-for="(doc, index) in documentTypes" :key="doc.hashid" class="flex items-center justify-between px-4 py-3">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:space-x-3">
                            <span class="w-6 text-center font-semibold text-gray-600">{{ index + 1 }}.</span>
                            <span class="font-medium text-gray-800">{{ doc.name }}</span>
                            <span v-if="doc.description" class="text-sm text-gray-500">- {{ doc.description }}</span>
                        </div>

                        <div class="space-x-4">
                            <button @click="editDocumentType(doc.hashid)" class="text-sm text-primary hover:underline">Edit</button>
                            <button @click="deleteDocumentType(doc.hashid)" class="text-sm text-red-600 hover:underline">Delete</button>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </AppLayout>
</template>
