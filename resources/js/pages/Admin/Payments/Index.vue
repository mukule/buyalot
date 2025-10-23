<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { Eye, SearchIcon } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Payment {
    id: number;
    ulid: string;
    amount: string;
    currency: string;
    provider: string;
    method: string;
    status: string;
    reference: string | null;
    created_at: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}
interface PaginationMeta {
    current_page: number;
    from: number;
    last_page: number;
    path: string;
    per_page: number;
    to: number;
    total: number;
}
interface PaginatedResponse<T> {
    data: T[];
    links: PaginationLink[];
    meta: PaginationMeta;
}

const page = usePage<{ payments: PaginatedResponse<Payment>; filters: { search?: string } }>();
const searchQuery = ref(page.props.filters.search || '');
const debouncedQuery = ref(searchQuery.value);

watch(
    searchQuery,
    debounce((val: string) => {
        router.get(route('admin.payments.index'), { search: val }, { preserveState: true, replace: true });
    }, 300),
);

const payments = computed(() => page.props.payments.data);
const pagination = computed(() => ({ links: page.props.payments.links, meta: page.props.payments.meta }));

function viewPayment(hashid: number) {
    router.get(route('admin.payments.show', { payment: hashid }));
}
</script>

<template>
    <Head title="Payments" />

    <AppLayout
        :breadcrumbs="[
            { title: 'Dashboard', href: route('admin.dashboard') },
            { title: 'Payments', href: route('admin.payments.index') },
        ]"
    >
        <div class="p-4">
            <div class="card rounded-lg bg-white p-4 shadow-sm">
                <h1 class="mb-4 text-2xl font-semibold text-gray-800">Payments</h1>

                <div class="mb-4 flex">
                    <div class="relative w-full">
                        <SearchIcon class="absolute top-2.5 left-3 h-5 w-5 text-gray-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search payments..."
                            class="focus:border-primary-500 focus:ring-primary-500 block w-full rounded-md border border-gray-300 py-2 pl-10 text-sm"
                        />
                    </div>
                </div>

                <div v-if="payments.length" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Provider</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Method</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="payment in payments" :key="payment.id" class="hover:bg-gray-50">
                                <td class="px-4 py-4 text-sm text-gray-700">{{ payment.reference || payment.ulid }}</td>
                                <td class="px-4 py-4 text-sm text-gray-700">{{ payment.provider }}</td>
                                <td class="px-4 py-4 text-sm text-gray-700">{{ payment.method }}</td>
                                <td class="px-4 py-4 text-sm font-semibold text-gray-800">{{ payment.amount }} {{ payment.currency }}</td>
                                <td
                                    class="px-4 py-4 text-sm capitalize"
                                    :class="{
                                        'text-yellow-600': payment.status === 'pending',
                                        'text-green-600': payment.status === 'completed',
                                        'text-red-600': payment.status === 'failed',
                                    }"
                                >
                                    {{ payment.status }}
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-500">{{ new Date(payment.created_at).toLocaleString() }}</td>
                                <td class="px-4 py-4 text-right">
                                    <button @click="viewPayment(payment.hashid)" class="text-blue-600 hover:text-blue-800">
                                        <Eye class="h-5 w-5" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else class="p-8 text-center text-gray-500">No payments found.</div>
            </div>
        </div>
    </AppLayout>
</template>
