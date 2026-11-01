<script setup lang="ts">
/**
 * Contact the seller of a vehicle (vehicles have no cart):
 *   - message:  send a message ("Inquire")
 *   - callback: ask the seller to call back
 *   - offer:    make a price offer
 * Messages are screened server-side for abusive language; a buyer who sends one
 * is blocked from sending more.
 */
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { AlertTriangle, HandCoins, Loader2, MessageSquare, PhoneCall, X } from 'lucide-vue-next';
import { computed, watch } from 'vue';

type Mode = 'message' | 'callback' | 'offer';

const props = withDefaults(
    defineProps<{
        open: boolean;
        product: { id?: number; name?: string; price?: number | null };
        mode?: Mode;
    }>(),
    { mode: 'message' },
);

const emit = defineEmits<{ (e: 'close'): void }>();

const page = usePage();
const user = computed<any>(() => (page.props as any).auth?.user ?? null);
const isBlocked = computed(() => !!user.value?.inquiry_blocked);

const defaultMessage = (mode: Mode, name?: string) =>
    mode === 'message' && name ? `Hi, is the ${name} still available?` : '';

const form = useForm({
    type: props.mode as Mode,
    product_id: props.product.id ?? null,
    name: user.value?.name ?? '',
    email: user.value?.email ?? '',
    phone: user.value?.phone ?? '',
    message: defaultMessage(props.mode, props.product.name),
    offer_amount: null as number | null,
});

// Re-target the form when it's reopened in another mode / for another listing.
watch(
    () => [props.open, props.mode, props.product.id] as const,
    ([open]) => {
        if (!open) return;
        form.clearErrors();
        form.type = props.mode;
        form.product_id = props.product.id ?? null;
        form.message = defaultMessage(props.mode, props.product.name);
        form.offer_amount = null;
    },
);

const title = computed(() => ({ message: 'Message the seller', callback: 'Request a call back', offer: 'Make an offer' })[props.mode]);
const submitLabel = computed(() => ({ message: 'Send message', callback: 'Request call back', offer: 'Send offer' })[props.mode]);
const icon = computed(() => ({ message: MessageSquare, callback: PhoneCall, offer: HandCoins })[props.mode]);

// Quick offer suggestions: 5% / 10% below the asking price.
const offerSuggestions = computed(() => {
    const price = Number(props.product.price ?? 0);
    if (!price) return [];
    return [0.95, 0.9].map((f) => Math.round((price * f) / 1000) * 1000);
});
const formatPrice = (n: number) => `KSh ${n.toLocaleString()}`;

const submit = () => {
    form.post(route('marketplace.inquire'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('message', 'offer_amount');
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
                    <h3 class="flex items-center gap-2 font-semibold text-gray-800">
                        <component :is="icon" class="h-4 w-4 text-primary" /> {{ title }}
                    </h3>
                    <button class="text-gray-500 hover:text-primary" aria-label="Close" @click="emit('close')"><X class="h-5 w-5" /></button>
                </div>

                <!-- Guests must sign in to contact sellers -->
                <div v-if="!user" class="space-y-3 p-4 text-sm text-gray-600">
                    <p>Please sign in to contact the seller.</p>
                    <Link href="/login" class="block w-full rounded-md bg-primary py-2.5 text-center font-semibold text-white hover:bg-primary/90">
                        Sign in
                    </Link>
                </div>

                <!-- Blocked for abusive language -->
                <div v-else-if="isBlocked" class="flex gap-3 p-4 text-sm text-red-700">
                    <AlertTriangle class="h-5 w-5 flex-none" />
                    <div class="space-y-1">
                        <p class="font-semibold">Please do not use abusive words.</p>
                        <p>Your message contained abusive language, so it was not sent and you have been blocked from sending messages to sellers.</p>
                    </div>
                </div>

                <form v-else class="space-y-3 p-4" @submit.prevent="submit">
                    <p v-if="product.name" class="text-sm text-gray-500">
                        About: <span class="font-medium text-gray-700">{{ product.name }}</span>
                        <span v-if="product.price" class="text-gray-400"> · asking {{ formatPrice(Number(product.price)) }}</span>
                    </p>

                    <!-- Offer amount -->
                    <div v-if="mode === 'offer'">
                        <label class="block text-xs font-medium text-gray-600">Your offer (KSh)*</label>
                        <input v-model.number="form.offer_amount" type="number" min="1" required class="w-full rounded-md border px-3 py-2 text-sm" />
                        <div v-if="offerSuggestions.length" class="mt-2 flex flex-wrap gap-2">
                            <button
                                v-for="amount in offerSuggestions"
                                :key="amount"
                                type="button"
                                class="rounded-full border border-primary/40 px-3 py-1 text-xs font-medium text-primary hover:bg-primary/10"
                                @click="form.offer_amount = amount"
                            >
                                {{ formatPrice(amount) }}
                            </button>
                        </div>
                        <p v-if="form.errors.offer_amount" class="mt-1 text-xs text-red-500">{{ form.errors.offer_amount }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600">Your name</label>
                        <input v-model="form.name" type="text" class="w-full rounded-md border px-3 py-2 text-sm" />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-red-500">{{ form.errors.name }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600">Phone<span v-if="mode === 'callback'">*</span></label>
                            <input v-model="form.phone" type="tel" :required="mode === 'callback'" class="w-full rounded-md border px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600">Email</label>
                            <input v-model="form.email" type="email" class="w-full rounded-md border px-3 py-2 text-sm" />
                        </div>
                    </div>
                    <p v-if="form.errors.phone || form.errors.email" class="text-xs text-red-500">{{ form.errors.phone || form.errors.email }}</p>

                    <div>
                        <label class="block text-xs font-medium text-gray-600">
                            {{ mode === 'message' ? 'Message*' : 'Note (optional)' }}
                        </label>
                        <textarea
                            v-model="form.message"
                            :rows="mode === 'message' ? 4 : 2"
                            :required="mode === 'message'"
                            maxlength="2000"
                            :placeholder="mode === 'callback' ? 'Best time to call…' : mode === 'offer' ? 'e.g. cash buyer, can view this weekend' : ''"
                            class="w-full rounded-md border px-3 py-2 text-sm"
                        ></textarea>
                        <p v-if="form.errors.message" class="mt-1 flex gap-1.5 rounded-md bg-red-50 p-2 text-xs text-red-600">
                            <AlertTriangle class="h-4 w-4 flex-none" /> {{ form.errors.message }}
                        </p>
                        <p v-else class="mt-1 text-[11px] text-gray-400">Be respectful — messages with abusive language are blocked.</p>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="flex w-full items-center justify-center gap-2 rounded-md bg-primary py-2.5 text-sm font-semibold text-white transition hover:bg-primary/90 disabled:opacity-60"
                    >
                        <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                        {{ submitLabel }}
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
