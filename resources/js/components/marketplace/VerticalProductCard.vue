<script setup lang="ts">
import QuoteRequestModal from '@/components/marketplace/QuoteRequestModal.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    BadgeCheck,
    Car,
    Cog,
    FileText,
    Fuel,
    Gauge,
    HardHat,
    Heart,
    Lock,
    LockOpen,
    MapPin,
    Palette,
    Phone,
    Ruler,
    ShieldCheck,
    ShoppingCart,
    SlidersHorizontal,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = withDefaults(
    defineProps<{
        product: any;
        type: 'automotive' | 'construction' | string;
        layout?: 'grid' | 'list';
    }>(),
    { layout: 'grid' },
);

const page = usePage();

const attrs = computed(() => props.product.attributes ?? {});
const isReserved = computed(() => !!props.product.reserved);
const isAutomotive = computed(() => props.type === 'automotive');
const isConstruction = computed(() => props.type === 'construction');
const isAuthed = computed(() => !!(page.props as any).auth?.user);

// Quote request (construction)
const showQuote = ref(false);
const quoteTarget = computed(() => ({
    id: props.product.product_id,
    name: props.product.product_name ?? props.product.name,
    vertical: props.type === 'construction' ? 'construction' : props.type === 'automotive' ? 'cars' : undefined,
    unit: attrs.value.unit ?? null,
}));

const title = computed(() => {
    if (isAutomotive.value) {
        const parts = [attrs.value.year, attrs.value.make, attrs.value.model].filter(Boolean);
        if (parts.length) return parts.join(' ');
    }
    return props.product.product_name ?? props.product.name;
});

const chips = computed(() => {
    const a = attrs.value;
    if (isAutomotive.value) {
        return [
            a.mileage != null ? { icon: Gauge, text: `${Number(a.mileage).toLocaleString()} km` } : null,
            a.transmission ? { icon: Cog, text: a.transmission } : null,
            a.fuel ? { icon: Fuel, text: a.fuel } : null,
            a.engine_cc ? { icon: SlidersHorizontal, text: `${Number(a.engine_cc).toLocaleString()} cc` } : null,
            a.drive_type ? { icon: Car, text: a.drive_type } : null,
            a.steering ? { icon: Cog, text: `${a.steering} hand` } : null,
            a.color ? { icon: Palette, text: a.color } : null,
        ].filter(Boolean);
    }
    return [
        a.material ? { icon: HardHat, text: a.material } : null,
        a.type ? { icon: Ruler, text: a.type } : null,
        a.unit ? { icon: Ruler, text: `per ${a.unit}` } : null,
    ].filter(Boolean);
});

const placeholderIcon = computed(() => (isAutomotive.value ? Car : HardHat));

const imgError = ref(false);
const hasImage = computed(
    () => !imgError.value && props.product.primary_image_url && !String(props.product.primary_image_url).includes('fallback'),
);

const formatPrice = (amount: number | string | null) => {
    if (amount == null) return 'KSh 0';
    const num = typeof amount === 'string' ? parseFloat(amount) : amount;
    return isNaN(num) ? 'KSh 0' : `KSh ${num.toLocaleString()}`;
};

// Wishlist / cart
const isAddingWishlist = ref(false);
const isAddingToCart = ref(false);
const variantId = computed(() => props.product.id);

const isInWishlist = computed(() => (page.props as any).auth?.wishlistVariantIds?.includes(variantId.value) ?? false);
const isInCart = computed(
    () => (page.props as any).auth?.cartItems?.some((i: any) => i.product_variant_id === variantId.value) ?? false,
);

const toggleWishlist = () => {
    if (isAddingWishlist.value) return;
    isAddingWishlist.value = true;
    router.post(
        route('wishlist.store'),
        { product_variant_id: variantId.value },
        { preserveScroll: true, onFinish: () => (isAddingWishlist.value = false) },
    );
};

const addToCart = () => {
    if (isAddingToCart.value || isInCart.value || isReserved.value) return;
    isAddingToCart.value = true;
    router.post(
        route('cart.store'),
        { product_variant_id: variantId.value, quantity: 1 },
        { preserveScroll: true, onFinish: () => (isAddingToCart.value = false) },
    );
};

// Contact (cars): a car isn't bought through the cart — buyers call the seller.
const sellerPhone = computed<string | null>(() => {
    const p = attrs.value.phone ?? props.product.contact_phone ?? null;
    return p ? String(p).trim() : null;
});
const telHref = computed(() => (sellerPhone.value ? `tel:${sellerPhone.value.replace(/[^\d+]/g, '')}` : '#'));
const showPhone = ref(false);
const revealPhone = () => {
    showPhone.value = true;
};

// Reservation (cars only)
const isReserving = ref(false);
const toggleReservation = () => {
    if (isReserving.value) return;
    isReserving.value = true;
    const name = isReserved.value ? 'marketplace.unreserve' : 'marketplace.reserve';
    router.post(
        route(name, props.product.product_id),
        {},
        { preserveScroll: true, preserveState: false, onFinish: () => (isReserving.value = false) },
    );
};
</script>

<template>
    <div
        class="relative flex overflow-hidden rounded-lg border bg-white shadow-sm transition-shadow duration-200 hover:shadow-md"
        :class="[layout === 'list' ? 'flex-col sm:flex-row' : 'flex-col', isReserved ? 'opacity-95' : '']"
    >
        <!-- Reserved ribbon -->
        <div
            v-if="isReserved"
            class="absolute top-0 left-0 z-20 flex items-center gap-1 rounded-br-lg bg-secondary px-2 py-1 text-[11px] font-bold text-white shadow"
        >
            <Lock class="h-3 w-3" /> Reserved
        </div>

        <span
            v-if="!isReserved && product.discount_percent > 0"
            class="absolute top-2 left-2 z-10 rounded bg-secondary/90 px-2 py-1 text-xs font-bold text-white"
        >
            {{ Math.round(product.discount_percent) }}% OFF
        </span>
        <span
            v-if="attrs.condition"
            class="absolute top-2 right-2 z-10 rounded bg-white/90 px-2 py-1 text-[10px] font-semibold text-gray-700 shadow-sm"
        >
            {{ attrs.condition }}
        </span>

        <Link
            :href="`/products/${product.product_slug}?v=${product.id}`"
            class="block"
            :class="layout === 'list' ? 'sm:w-56 sm:flex-none' : ''"
        >
            <div
                class="relative flex w-full items-center justify-center overflow-hidden bg-gray-50"
                :class="layout === 'list' ? 'h-44 sm:h-full' : 'h-40 sm:h-48'"
            >
                <img
                    v-if="hasImage"
                    :src="product.primary_image_url"
                    :alt="title"
                    loading="lazy"
                    class="h-full w-full object-cover"
                    :class="isReserved ? 'grayscale-[35%]' : ''"
                    @error="imgError = true"
                />
                <div
                    v-else
                    class="flex h-full w-full flex-col items-center justify-center gap-2 bg-gradient-to-br from-primary/10 to-secondary/10 text-primary/40"
                >
                    <component :is="placeholderIcon" class="h-12 w-12" />
                    <span class="text-xs">No image</span>
                </div>
            </div>
        </Link>

        <div class="flex flex-1 flex-col gap-2 p-3">
            <div class="flex items-start justify-between gap-2">
                <Link :href="`/products/${product.product_slug}?v=${product.id}`" class="block">
                    <h3 class="line-clamp-2 text-sm font-semibold text-gray-800">{{ title }}</h3>
                </Link>
            </div>

            <p v-if="product.stock_id" class="text-[11px] font-medium tracking-wide text-gray-400">
                {{ isConstruction ? 'SKU' : 'Stock ID' }}: {{ product.stock_id }}
            </p>

            <div v-if="isConstruction && (attrs.certification || attrs.min_order)" class="flex flex-wrap items-center gap-2">
                <span
                    v-if="attrs.certification"
                    class="inline-flex items-center gap-1 rounded bg-primary/10 px-1.5 py-0.5 text-[11px] font-medium text-primary"
                >
                    <ShieldCheck class="h-3 w-3" /> {{ attrs.certification }}
                </span>
                <span v-if="attrs.min_order" class="text-[11px] text-gray-500">Min order: {{ attrs.min_order }} {{ attrs.unit }}</span>
            </div>

            <!-- Spec chips -->
            <div v-if="chips.length" class="flex flex-wrap gap-1.5">
                <span
                    v-for="(chip, i) in chips"
                    :key="i"
                    class="inline-flex items-center gap-1 rounded bg-gray-100 px-1.5 py-0.5 text-[11px] text-gray-600"
                >
                    <component :is="chip.icon" class="h-3 w-3" />
                    {{ chip.text }}
                </span>
            </div>

            <p v-if="product.location" class="flex items-center gap-1 text-xs text-gray-500">
                <MapPin class="h-3 w-3" /> {{ product.location }}
            </p>

            <div class="mt-auto flex items-end justify-between pt-1">
                <div class="flex flex-col">
                    <span class="text-base font-bold text-primary">
                        {{ formatPrice(product.final_price) }}<span v-if="isConstruction && attrs.unit" class="text-xs font-normal text-gray-500"> / {{ attrs.unit }}</span>
                    </span>
                    <span v-if="product.marked_price > product.final_price" class="text-xs text-gray-400 line-through">
                        {{ formatPrice(product.marked_price) }}
                    </span>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        :class="[
                            'flex h-8 w-8 items-center justify-center rounded-full transition hover:scale-110',
                            isAddingWishlist ? 'cursor-wait opacity-50' : '',
                            isInWishlist ? 'bg-secondary text-white' : 'bg-gray-100 text-secondary hover:bg-secondary hover:text-white',
                        ]"
                        :aria-label="isInWishlist ? 'In wishlist' : 'Add to wishlist'"
                        @click.stop.prevent="toggleWishlist"
                    >
                        <Heart class="h-4 w-4" />
                    </button>
                    <!-- Cars: call the seller instead of add-to-cart -->
                    <a
                        v-if="isAutomotive && sellerPhone && showPhone"
                        :href="telHref"
                        class="flex h-8 items-center justify-center gap-1.5 rounded-full bg-primary px-3 text-xs font-semibold text-white transition hover:bg-primary/90"
                        :aria-label="`Call ${sellerPhone}`"
                        @click.stop
                    >
                        <Phone class="h-3.5 w-3.5" /> {{ sellerPhone }}
                    </a>
                    <button
                        v-else-if="isAutomotive"
                        type="button"
                        :disabled="!sellerPhone"
                        :class="[
                            'flex h-8 w-8 items-center justify-center rounded-full transition hover:scale-110',
                            sellerPhone ? 'bg-gray-100 text-primary hover:bg-primary hover:text-white' : 'cursor-not-allowed bg-gray-100 text-gray-300',
                        ]"
                        :aria-label="sellerPhone ? 'Show seller phone' : 'No phone provided'"
                        :title="sellerPhone ? 'Call seller' : 'No phone provided'"
                        @click.stop.prevent="revealPhone"
                    >
                        <Phone class="h-4 w-4" />
                    </button>
                    <button
                        v-else
                        type="button"
                        :disabled="isReserved"
                        :class="[
                            'flex h-8 w-8 items-center justify-center rounded-full transition hover:scale-110',
                            isAddingToCart ? 'cursor-wait opacity-50' : '',
                            isReserved ? 'cursor-not-allowed bg-gray-100 text-gray-300' : '',
                            !isReserved && isInCart ? 'bg-primary text-white' : '',
                            !isReserved && !isInCart ? 'bg-gray-100 text-primary hover:bg-primary hover:text-white' : '',
                        ]"
                        :aria-label="isReserved ? 'Reserved' : isInCart ? 'In cart' : 'Add to cart'"
                        @click.stop.prevent="addToCart"
                    >
                        <ShoppingCart class="h-4 w-4" />
                    </button>
                </div>
            </div>

            <!-- Bulk quote (construction) -->
            <button
                v-if="isConstruction"
                type="button"
                class="mt-1 flex w-full items-center justify-center gap-1.5 rounded-md border border-primary/40 px-2 py-1.5 text-xs font-medium text-primary transition hover:bg-primary/10"
                @click.stop.prevent="showQuote = true"
            >
                <FileText class="h-3.5 w-3.5" /> Request quote
            </button>

            <!-- Reservation controls (cars, authenticated users) -->
            <button
                v-if="isAutomotive && isAuthed"
                type="button"
                :disabled="isReserving"
                :class="[
                    'mt-1 flex w-full items-center justify-center gap-1.5 rounded-md border px-2 py-1.5 text-xs font-medium transition',
                    isReserving ? 'cursor-wait opacity-60' : '',
                    isReserved
                        ? 'border-secondary/40 text-secondary hover:bg-secondary/10'
                        : 'border-primary/40 text-primary hover:bg-primary/10',
                ]"
                @click.stop.prevent="toggleReservation"
            >
                <component :is="isReserved ? LockOpen : BadgeCheck" class="h-3.5 w-3.5" />
                {{ isReserved ? 'Remove reservation' : 'Reserve' }}
            </button>
        </div>

        <QuoteRequestModal v-if="isConstruction" :open="showQuote" :product="quoteTarget" @close="showQuote = false" />
    </div>
</template>
