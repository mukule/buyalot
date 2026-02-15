<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { route } from 'ziggy-js';
import { Button } from '@/components/ui/button';
import { Banknote, CheckCircle } from 'lucide-vue-next';

interface Reconciliation {
    id: number;
    order_id: number;
    order?: { ulid: string; order_code: string; status: string; total_amount: number; currency: string };
    delivery_user?: { id: number; name: string; email: string };
    warehouse?: { id: number; name: string; address?: string };
    confirmed_by_user?: { id: number; name: string } | null;
    amount: number;
    currency: string;
    reconciled_at: string | null;
    confirmed_at: string | null;
}

const props = defineProps<{
    reconciliations: {
        data: Reconciliation[];
        links: { url: string | null; label: string; active: boolean }[];
    };
}>();

const breadcrumbs = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'COD Reconciliations', href: '#' },
];

const confirmingId = ref<number | null>(null);

function money(amount: number, currency: string) {
    try {
        return new Intl.NumberFormat(undefined, { style: 'currency', currency }).format(amount);
    } catch {
        return `${currency} ${amount.toFixed(2)}`;
    }
}

function confirmReceipt(r: Reconciliation) {
    if (confirmingId.value) return;
    confirmingId.value = r.id;
    router.post(route('admin.cod-reconciliations.confirm', r.id), {}, {
        preserveState: true,
        onFinish: () => { confirmingId.value = null; },
        onSuccess: () => router.reload(),
    });
}
</script>

<template>
    <Head title="COD Cash Reconciliations" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="mb-6">
                <h1 class="text-2xl font-semibold">COD Cash Reconciliations</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Delivery persons hand over COD cash to the warehouse. Confirm receipt here when you have received the full amount.
                </p>
            </div>

            <div class="overflow-x-auto rounded-lg border bg-card shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-muted/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Order</th>
                            <th class="px-4 py-3 text-left font-medium">Amount</th>
                            <th class="px-4 py-3 text-left font-medium">Warehouse</th>
                            <th class="px-4 py-3 text-left font-medium">Delivery person</th>
                            <th class="px-4 py-3 text-left font-medium">Reconciled at</th>
                            <th class="px-4 py-3 text-left font-medium">Status</th>
                            <th class="px-4 py-3 text-right font-medium">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="r in props.reconciliations.data" :key="r.id">
                            <td class="px-4 py-3">
                                <Link
                                    v-if="r.order?.ulid"
                                    :href="route('admin.orders.show', r.order.ulid)"
                                    class="font-medium text-primary hover:underline"
                                >
                                    #{{ r.order?.order_code || '—' }}
                                </Link>
                                <span v-else>#{{ r.order?.order_code || '—' }}</span>
                            </td>
                            <td class="px-4 py-3 font-medium">{{ money(r.amount, r.currency) }}</td>
                            <td class="px-4 py-3">{{ r.warehouse?.name || '—' }}</td>
                            <td class="px-4 py-3">{{ r.delivery_user?.name || '—' }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ r.reconciled_at || '—' }}</td>
                            <td class="px-4 py-3">
                                <span
                                    v-if="r.confirmed_at"
                                    class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/40 dark:text-green-200"
                                >
                                    <CheckCircle class="h-3.5 w-3.5" />
                                    Confirmed
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900/40 dark:text-amber-200"
                                >
                                    <Banknote class="h-3.5 w-3.5" />
                                    Pending confirmation
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Button
                                    v-if="!r.confirmed_at"
                                    size="sm"
                                    @click="confirmReceipt(r)"
                                    :disabled="confirmingId === r.id"
                                >
                                    {{ confirmingId === r.id ? 'Confirming…' : 'Confirm receipt' }}
                                </Button>
                                <span v-else class="text-xs text-muted-foreground">
                                    Confirmed by {{ r.confirmed_by_user?.name || '—' }}
                                </span>
                            </td>
                        </tr>
                        <tr v-if="!props.reconciliations.data?.length">
                            <td colspan="7" class="px-4 py-8 text-center text-muted-foreground">
                                No COD reconciliations yet. Delivery persons reconcile cash after delivering COD orders.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="props.reconciliations.links?.length > 1" class="mt-4 flex justify-center gap-1">
                <template v-for="(link, idx) in props.reconciliations.links" :key="idx">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="rounded border px-3 py-1 text-sm"
                        :class="link.active ? 'border-primary bg-primary text-white' : 'border-input hover:bg-muted'"
                    >
                        {{ link.label }}
                    </Link>
                    <span v-else class="rounded border border-input px-3 py-1 text-sm text-muted-foreground">
                        {{ link.label }}
                    </span>
                </template>
            </div>
        </div>
    </AppLayout>
</template>
