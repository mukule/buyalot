<script setup lang="ts">
import Modal from '@/components/Modal.vue';
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

interface Variant {
    id: number;
    display_name: string;
    current_stock: number;
    quantity: number;
}

const props = defineProps<{
    show: boolean;
    product: {
        name: string;
        hashid: string;
        product_variants?: {
            id: number;
            display_name: string;
            stock: number;
        }[];
    } | null;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'success'): void;
}>();

const restockForm = useForm<{
    variants: Variant[];
    note: string;
}>({
    variants: [],
    note: '',
});

watch(
    () => props.product,
    (product) => {
        if (!product) return;

        restockForm.clearErrors();
        restockForm.variants = (product.product_variants ?? []).map((v) => ({
            id: v.id,
            display_name: v.display_name,
            current_stock: v.stock,
            quantity: 0,
        }));
    },
    { immediate: true },
);

const hasQuantityToRestock = () => {
    return restockForm.variants.some((v) => v.quantity > 0);
};

const submitRestock = () => {
    if (!props.product) return;

    if (!hasQuantityToRestock()) {
        restockForm.setError('variants', 'Please enter at least one quantity to restock.');
        return;
    }

    const payload = {
        variants: restockForm.variants
            .filter((v) => v.quantity > 0)
            .map((v) => ({ id: v.id, quantity: v.quantity })),
        note: restockForm.note,
    };

    restockForm.transform(() => payload).post(route('admin.products.restock.variants', props.product.hashid), {
        preserveScroll: true,
        onSuccess: () => {
            restockForm.reset();
            emit('success');
            emit('close');
        },
    });
};
</script>

<template>
    <Modal :show="show" @close="emit('close')">
        <h2 class="mb-4 text-lg font-bold">Restock {{ product?.name }}</h2>

        <p v-if="restockForm.errors.variants" class="mb-2 text-sm text-red-600">{{ restockForm.errors.variants }}</p>

        <table v-if="restockForm.variants.length" class="w-full text-sm">
            <thead>
                <tr class="border-b">
                    <th class="text-left">Variant</th>
                    <th>Current</th>
                    <th>Restock Qty</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="variant in restockForm.variants" :key="variant.id">
                    <td>{{ variant.display_name }}</td>
                    <td class="text-center">{{ variant.current_stock }}</td>
                    <td class="text-center">
                        <input type="number" min="0" v-model.number="variant.quantity" class="w-20 rounded border p-1 text-center" />
                    </td>
                </tr>
            </tbody>
        </table>

        <p v-else class="py-4 text-sm text-gray-500">No variants to restock for this product.</p>

        <textarea v-model="restockForm.note" class="mt-4 w-full rounded border p-2" placeholder="Optional note" />

        <div class="mt-4 flex justify-end gap-2">
            <button type="button" @click="emit('close')" class="btn-secondary">Cancel</button>
            <button
                type="button"
                @click="submitRestock"
                class="btn-primary"
                :disabled="restockForm.processing || !restockForm.variants.length"
            >
                {{ restockForm.processing ? 'Restocking...' : 'Restock' }}
            </button>
        </div>
    </Modal>
</template>
