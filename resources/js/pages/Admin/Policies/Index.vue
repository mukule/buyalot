<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { ArrowLeft, FileText, PlusIcon, SearchIcon } from 'lucide-vue-next';
import { computed, reactive, ref, watch } from 'vue';

// -------------------- Types --------------------
interface Policy {
    id: number;
    title: string;
    code: string;
    scope: string;
    status: 0 | 1;
    is_mandatory: boolean;
    latest_version?: number | null;
}

// -------------------- Page props --------------------
const page = usePage<
    AppPageProps<{
        policies: {
            data: Policy[];
            meta: {
                total: number;
                per_page: number;
                current_page: number;
                last_page: number;
            };
        };
    }>
>();

const reactivePolicies = reactive(page.props.policies?.data ?? []);

// -------------------- Search --------------------
const searchQuery = ref('');
const debouncedQuery = ref(searchQuery.value);

watch(
    searchQuery,
    debounce((val: string) => {
        debouncedQuery.value = val;
    }, 300),
);

const filteredPolicies = computed(() => {
    const query = debouncedQuery.value.trim().toLowerCase();
    return reactivePolicies.filter((p) => {
        if (!query) return true;
        return p.title.toLowerCase().includes(query) || p.scope.toLowerCase().includes(query);
    });
});

// -------------------- Breadcrumbs --------------------
const breadcrumbs = reactive([
    { title: 'Dashboard', href: route('admin.dashboard') },
    { title: 'Policies', href: route('admin.policies.index') },
]);

// -------------------- Actions --------------------
function createPolicy() {
    router.get(route('admin.policies.create'));
}

function editPolicy(policy: Policy) {
    router.get(route('admin.policies.edit', { policy: policy.id }));
}

function viewVersions(policy: Policy) {
    router.get(route('admin.policies.versions.index', { policy: policy.id }));
}

function goBack() {
    router.get(route('admin.dashboard'));
}

// -------------------- Toggle status --------------------
function togglePolicyStatus(policy: Policy) {
    const currentStatus = policy.status;
    const newStatus = currentStatus === 1 ? 0 : 1;

    policy.status = newStatus;

    router.patch(
        route('admin.policies.update', { policy: policy.id }),
        { status: newStatus },
        {
            onError: () => {
                policy.status = currentStatus;
            },
        },
    );
}

// -------------------- Pagination --------------------
const pagination = computed(
    () =>
        page.props.policies?.meta ?? {
            total: 0,
            per_page: 10,
            current_page: 1,
            last_page: 1,
        },
);

function goToPage(pageNumber: number) {
    router.get(route('admin.policies.index'), { page: pageNumber }, { preserveState: true });
}
</script>

<template>
    <Head title="Policies" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="card rounded-lg bg-white p-4 shadow-sm">
                <!-- Header -->
                <div class="relative mb-4 flex items-center justify-between">
                    <button @click="goBack" class="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
                        <ArrowLeft class="h-4 w-4" /> Back
                    </button>
                    <h1 class="absolute left-1/2 -translate-x-1/2 transform text-2xl font-semibold">Policies</h1>
                    <button @click="createPolicy" class="hover:bg-primary-dark ml-auto rounded-xl bg-primary px-4 py-2 text-white">
                        + New Policy
                    </button>
                </div>

                <!-- Search -->
                <div class="mb-4 flex items-center gap-4">
                    <div class="relative flex-1">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search policies..."
                            class="focus:ring-primary-500 focus:border-primary-500 block w-full rounded-md border py-2 pl-10 text-sm"
                        />
                        <SearchIcon class="absolute top-2.5 left-3 h-5 w-5 text-gray-400" />
                    </div>
                </div>

                <!-- Policies Table -->
                <div v-if="filteredPolicies.length" class="overflow-x-auto">
                    <table class="w-full table-auto border-collapse border border-gray-200">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="border px-4 py-2 text-left">Code</th>
                                <th class="border px-4 py-2 text-left">Title</th>
                                <th class="border px-4 py-2 text-left">Scope</th>
                                <th class="border px-4 py-2 text-left">Mandatory</th>
                                <th class="border px-4 py-2 text-left">Status</th>
                                <th class="border px-4 py-2 text-left">Latest Version</th>
                                <th class="border px-4 py-2 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="policy in filteredPolicies" :key="policy.id" class="hover:bg-gray-50">
                                <td class="border px-4 py-2">{{ policy.code }}</td>
                                <td class="flex items-center gap-2 border px-4 py-2">
                                    <FileText class="h-4 w-4 text-gray-500" />
                                    {{ policy.title }}
                                </td>
                                <td class="border px-4 py-2">{{ policy.scope }}</td>
                                <td class="border px-4 py-2">{{ policy.is_mandatory ? 'Yes' : 'No' }}</td>
                                <td class="border px-4 py-2">
                                    <button
                                        @click="togglePolicyStatus(policy)"
                                        :class="policy.status ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700'"
                                        class="rounded px-2 py-1 text-sm font-semibold"
                                    >
                                        {{ policy.status ? 'Active' : 'Inactive' }}
                                    </button>
                                </td>
                                <td class="border px-4 py-2">{{ policy.latest_version ?? '-' }}</td>
                                <td class="flex gap-2 border px-4 py-2">
                                    <button @click="viewVersions(policy)" class="text-sm text-blue-600 hover:underline">View Versions</button>
                                    <button @click="editPolicy(policy)" class="text-sm text-green-600 hover:underline">Edit</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div class="mt-4 flex justify-end gap-2">
                        <button
                            :disabled="pagination.current_page === 1"
                            @click="goToPage(pagination.current_page - 1)"
                            class="rounded border px-3 py-1 disabled:opacity-50"
                        >
                            Previous
                        </button>
                        <span class="px-2 py-1">Page {{ pagination.current_page }} of {{ pagination.last_page }}</span>
                        <button
                            :disabled="pagination.current_page === pagination.last_page"
                            @click="goToPage(pagination.current_page + 1)"
                            class="rounded border px-3 py-1 disabled:opacity-50"
                        >
                            Next
                        </button>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="p-8 text-center">
                    <PlusIcon class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No policies found</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ searchQuery ? 'Try adjusting your search' : 'Get started by creating a new policy.' }}
                    </p>
                    <button @click="createPolicy" class="hover:bg-primary-dark mt-4 rounded-xl bg-primary px-4 py-2 text-white">+ New Policy</button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
