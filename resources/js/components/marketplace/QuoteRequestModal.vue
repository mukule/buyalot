<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { Loader2, X } from 'lucide-vue-next';
import { watch } from 'vue';

const props = defineProps<{
    open: boolean;
    product: { id?: number; name?: string; vertical?: string; unit?: string | null };
}>();

const emit = defineEmits<{ (e: 'close'): void }>();

const page = usePage();

const form = useForm({
    product_id: props.product.id ?? null,
    vertical: props.product.vertical ?? null,
    name: (page.props as any).auth?.user?.name ?? '',
    email: (page.props as any).auth?.user?.email ?? '',
    phone: '',
    quantity: null as number | null,
    unit: props.product.unit ?? null,
    message: '',
});

// Keep hidden fields in sync if the target product changes.
watch(
    () => props.product,
    (p) => {
        form.product_id = p.id ?? null;
        form.vertical = p.vertical ?? null;
        form.unit = p.unit ?? null;
    },
);

const submit = () => {
    form.post(route('marketplace.quote'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('quantity', 'message', 'phone');
            emit('close');
        },
    });
};
</script>

<template>
    <Transition name="fade">
        <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="emit('close')">
            <div class="w-full max-w-md rounded-xl bg-white shadow-xl">
                <div class="flex items-center justify-between border-b px-4 py-3">
                    <h3 class="font-semibold text-gray-800">Request a bulk quote</h3>
                    <button class="text-gray-500 hover:text-primary" @click="emit('close')"><X class="h-5 w-5" /></button>
                </div>

                <form class="space-y-3 p-4" @submit.prevent="submit">
                    <p v-if="product.name" class="text-sm text-gray-500">For: <span class="font-medium text-gray-700">{{ product.name }}</span></p>

                    <div>
                        <label class="block text-xs font-medium text-gray-600">Your name*</label>
                        <input v-model="form.name" type="text" required class="w-full rounded-md border px-3 py-2 text-sm" />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-red-500">{{ form.errors.name }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600">Email</label>
                            <input v-model="form.email" type="email" class="w-full rounded-md border px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600">Phone</label>
                            <input v-model="form.phone" type="text" class="w-full rounded-md border px-3 py-2 text-sm" />
                        </div>
                    </div>
                    <p v-if="form.errors.email" class="text-xs text-red-500">{{ form.errors.email }}</p>

                    <div>
                        <label class="block text-xs font-medium text-gray-600">
                            Quantity <span v-if="product.unit" class="text-gray-400">({{ product.unit }})</span>
                        </label>
                        <input v-model.number="form.quantity" type="number" min="1" class="w-full rounded-md border px-3 py-2 text-sm" />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600">Message</label>
                        <textarea v-model="form.message" rows="3" placeholder="Delivery location, timeline, specs…" class="w-full rounded-md border px-3 py-2 text-sm"></textarea>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="flex w-full items-center justify-center gap-2 rounded-md bg-primary py-2.5 text-sm font-semibold text-white transition hover:bg-primary/90 disabled:opacity-60"
                    >
                        <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                        Send request
                    </button>
                </form>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
