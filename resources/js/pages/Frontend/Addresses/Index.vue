<script setup lang="ts">
import MainLayout from '@/layouts/MainLayout.vue';
import { router, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Edit, PlusCircle } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';

// --- Types ---
interface Address {
    id: number;
    first_name: string;
    last_name: string;
    address_line_1: string;
    address_line_2?: string;
    city?: string;
    state_province?: string;
    postal_code?: string;
    country_name?: string;
    phone?: string;
    region?: string;
    pickup_point?: string;
    is_default: boolean;
}

const page = usePage();
const addresses = (page.props as any).addresses as Address[];
const cartSubtotal = (page.props as any).cart_subtotal as number;

// Selected address id (radio)
const selectedAddressId = ref<number | null>(addresses.find((a) => a.is_default)?.id || addresses[0]?.id || null);

const addressLinkLabel = computed(() => (addresses.length > 0 ? 'Add New Address' : 'Add Address'));
const addressLinkIcon = computed(() => PlusCircle);
const addressLinkUrl = computed(() => route('checkout.addresses.create'));

const formatPrice = (amount?: number | null) => `KSh ${(amount ?? 0).toLocaleString(undefined, { maximumFractionDigits: 2 })}`;

const proceedToPayment = () => {
    if (!selectedAddressId.value) return;

    router.visit(route('checkout.payment'), {
        method: 'get',
        data: { address_id: selectedAddressId.value },
        preserveScroll: true,
        preserveState: true,
    });
};

const editAddress = (addressId: number) => {
    router.visit(route('checkout.addresses.edit', { id: addressId }));
};

const goBack = () => {
    router.visit(route('checkout.summary'));
};

/** 🔥 NEW: Automatically update default address in backend */
const makeDefault = (addressId: number) => {
    router.post(
        route('checkout.addresses.make-default', { address: addressId }),
        {},
        {
            preserveScroll: true,
            preserveState: true,
        },
    );
};

/** 🔥 NEW: Trigger when user selects a new address */
const onSelectAddress = (addressId: number) => {
    selectedAddressId.value = addressId;
    makeDefault(addressId);
};
</script>

<template>
    <MainLayout>
        <section class="mx-auto mt-4 mb-4 flex max-w-7xl flex-col overflow-x-hidden">
            <div class="flex flex-col gap-4 lg:flex-row">
                <!-- LEFT: Customer Addresses -->
                <div class="w-full lg:w-8/12">
                    <div class="rounded-lg bg-white p-4 shadow">
                        <div class="mb-2 flex items-center justify-between">
                            <h1 class="text-lg font-semibold text-gray-800">Customer Addresses</h1>

                            <div class="flex gap-2">
                                <button @click="goBack" class="flex cursor-pointer items-center gap-1 text-sm text-gray-700 hover:text-gray-900">
                                    <ArrowLeft class="h-4 w-4" /> Back
                                </button>

                                <a :href="addressLinkUrl" class="flex items-center gap-1 text-sm text-primary hover:text-primary/80">
                                    <component :is="addressLinkIcon" class="h-4 w-4" />
                                    {{ addressLinkLabel }}
                                </a>
                            </div>
                        </div>

                        <hr class="mb-4 border-gray-200" />

                        <div v-if="addresses.length > 0" class="flex flex-col gap-2">
                            <div v-for="address in addresses" :key="address.id" class="flex items-center justify-between rounded border p-3">
                                <div class="flex items-start gap-3">
                                    <!-- 🔥 UPDATED RADIO: Calls backend when selected -->
                                    <input
                                        type="radio"
                                        name="selected_address"
                                        :checked="selectedAddressId === address.id"
                                        @change="onSelectAddress(address.id)"
                                        class="mt-1 h-4 w-4 text-primary"
                                    />

                                    <div class="flex flex-col">
                                        <span class="font-medium text-gray-700">{{ address.first_name }} {{ address.last_name }}</span>

                                        <span class="text-sm text-gray-600">
                                            {{ address.address_line_1 }}
                                            <template v-if="address.address_line_2"> | {{ address.address_line_2 }}</template>
                                            <template v-if="address.region"> | Region: {{ address.region }}</template>
                                            <template v-if="address.pickup_point"> | Pickup: {{ address.pickup_point }}</template>
                                            <template v-if="address.phone"> | Phone: {{ address.phone }}</template>
                                            <template v-if="address.is_default"> | <strong>Default</strong></template>
                                        </span>
                                    </div>
                                </div>

                                <!-- Edit icon -->
                                <button
                                    @click="editAddress(address.id)"
                                    class="cursor-pointer text-primary hover:text-primary/80"
                                    title="Edit Address"
                                >
                                    <Edit class="h-4 w-4" />
                                </button>
                            </div>
                        </div>

                        <p v-else class="text-sm text-gray-500">No addresses found. Please add one.</p>
                    </div>
                </div>

                <!-- RIGHT: Cart Subtotal Only -->
                <div class="w-full lg:w-4/12">
                    <div class="rounded-lg bg-white p-4 shadow">
                        <h2 class="text-lg font-semibold text-gray-800">Checkout Summary</h2>

                        <div class="mt-2 flex justify-between text-sm text-gray-700">
                            <span>Subtotal</span>
                            <span class="font-semibold text-gray-800">{{ formatPrice(cartSubtotal) }}</span>
                        </div>

                        <button
                            @click="proceedToPayment"
                            class="mt-4 flex w-full items-center justify-center gap-2 rounded bg-primary px-4 py-2 text-white hover:bg-primary/90"
                        >
                            Proceed to Payment
                        </button>

                        <p class="mt-2 text-center text-xs text-gray-600">
                            By proceeding, you accept the
                            <a :href="route('terms')" class="text-primary underline hover:text-primary/80"> Terms &amp; Conditions </a>
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </MainLayout>
</template>
