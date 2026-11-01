<script setup lang="ts">
/** "Report abuse" on a marketplace listing — sent to admins for review. */
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { Flag, Loader2, X } from 'lucide-vue-next';
import { computed, watch } from 'vue';

const props = defineProps<{
    open: boolean;
    productId: number;
}>();

const emit = defineEmits<{ (e: 'close'): void }>();

const page = usePage();
const user = computed<any>(() => (page.props as any).auth?.user ?? null);

// Keys must match ProductReport::REASONS on the server.
const reasons = [
    { value: 'scam', label: 'Scam / fraud' },
    { value: 'wrong_info', label: 'Wrong or misleading information' },
    { value: 'wrong_price', label: 'Wrong price' },
    { value: 'already_sold', label: 'Already sold / unavailable' },
    { value: 'seller_unreachable', label: 'Seller unreachable' },
    { value: 'offensive', label: 'Offensive content' },
    { value: 'other', label: 'Other' },
];

const form = useForm({ product_id: props.productId, reason: '', comment: '' });

watch(
    () => props.productId,
    (id) => (form.product_id = id),
);

const submit = () => {
    form.post(route('marketplace.report'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('reason', 'comment');
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
                    <h3 class="flex items-center gap-2 font-semibold text-gray-800"><Flag class="h-4 w-4 text-secondary" /> Report this listing</h3>
                    <button class="text-gray-500 hover:text-primary" aria-label="Close" @click="emit('close')"><X class="h-5 w-5" /></button>
                </div>

                <div v-if="!user" class="space-y-3 p-4 text-sm text-gray-600">
                    <p>Please sign in to report a listing.</p>
                    <Link href="/login" class="block w-full rounded-md bg-primary py-2.5 text-center font-semibold text-white hover:bg-primary/90">
                        Sign in
                    </Link>
                </div>

                <form v-else class="space-y-3 p-4" @submit.prevent="submit">
                    <fieldset class="space-y-1.5">
                        <legend class="mb-1 text-xs font-medium text-gray-600">What's wrong?*</legend>
                        <label v-for="r in reasons" :key="r.value" class="flex cursor-pointer items-center gap-2 text-sm text-gray-700">
                            <input v-model="form.reason" type="radio" name="reason" :value="r.value" required class="accent-[color:var(--primary)]" />
                            {{ r.label }}
                        </label>
                        <p v-if="form.errors.reason" class="text-xs text-red-500">{{ form.errors.reason }}</p>
                    </fieldset>

                    <div>
                        <label class="block text-xs font-medium text-gray-600">Details (optional)</label>
                        <textarea v-model="form.comment" rows="3" maxlength="1000" class="w-full rounded-md border px-3 py-2 text-sm"></textarea>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing || !form.reason"
                        class="flex w-full items-center justify-center gap-2 rounded-md bg-secondary py-2.5 text-sm font-semibold text-white transition hover:bg-secondary/90 disabled:opacity-60"
                    >
                        <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                        Send report
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
