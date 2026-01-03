<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { ChevronLeft, Lock, LogOut, Minus, Plus, Search, Trash2, UserPlus, Users as UsersIcon, History, Wallet, QrCode } from 'lucide-vue-next';
import { onMounted, ref, watch, computed } from 'vue';
import { Select, SelectItem } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';

const props = defineProps<{
    session: any;
    settings: any;
}>();

const products = ref<any[]>([]);
const categories = ref<any[]>([]);
const brands = ref<any[]>([]);
const selectedCategoryId = ref<number | null>(null);
const selectedBrandId = ref<number | null>(null);
const search = ref('');
const loadingProducts = ref(false);
const cart = ref<any[]>([]);
const selectedCustomer = ref<any>(null);
const customers = ref<any[]>([]);
const customerSearch = ref('');
const showCustomerModal = ref(false);
const showPaymentModal = ref(false);
const paymentMethod = ref('cash');
const amountPaid = ref(0);
const customerPhone = ref('');
const mpesaRequest = ref<any>(null);
const pollingInterval = ref<any>(null);
const processingOrder = ref(false);
const lastOrder = ref<any>(null);
const showReceiptModal = ref(false);
const showVoidModal = ref(false);
const adminPin = ref('');
const voidError = ref('');
const isVerifyingAdmin = ref(false);

const showVoidedSalesModal = ref(false);
const voidedSales = ref<any[]>([]);
const loadingVoidedSales = ref(false);

const showUnallocatedModal = ref(false);
const unallocatedPayments = ref<any[]>([]);
const loadingUnallocated = ref(false);
const selectedUnallocatedIds = ref<number[]>([]);

const showVariantModal = ref(false);
const selectedProduct = ref<any>(null);

const recordPaymentForm = ref({
    amount: 0,
    payment_method: 'cash',
    reference: '',
    notes: '',
});
const recordingPayment = ref(false);

const allowedPaymentMethods = computed(() => {
    if (!props.settings.payment_methods) return [{ id: 'cash', name: 'Cash', enabled: true }];
    return props.settings.payment_methods.filter((m) => m.enabled);
});

const totalAllocated = computed(() => {
    return unallocatedPayments.value
        .filter((p) => selectedUnallocatedIds.value.includes(p.id))
        .reduce((sum, p) => sum + Number(p.amount - p.used_amount), 0);
});

const remainingToPay = computed(() => {
    return Math.max(0, cartTotal() - totalAllocated.value);
});

const selectedPaymentMethodDetails = computed(() => {
    return allowedPaymentMethods.value.find((m) => m.id === paymentMethod.value);
});

const qrCodeUrl = computed(() => {
    const details = selectedPaymentMethodDetails.value;
    if (!details || !details.show_qr || !details.qr_value) return null;

    const value = details.qr_value;
    // Use a public QR code API
    return `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(value)}`;
});

const fetchProducts = async (page = 1) => {
    // Don't fetch by default unless filtered
    if (!search.value && !selectedCategoryId.value && !selectedBrandId.value) {
        products.value = [];
        return;
    }

    loadingProducts.value = true;
    try {
        const response = await axios.get(route('admin.pos.products.index'), {
            params: {
                search: search.value,
                category_id: selectedCategoryId.value,
                brand_id: selectedBrandId.value,
                page,
            },
        });
        products.value = response.data.data;
    } catch (error) {
        console.error('Error fetching products:', error);
    } finally {
        loadingProducts.value = false;
    }
};

const fetchCategories = async () => {
    try {
        const response = await axios.get(route('admin.pos.categories.index'));
        categories.value = response.data.categories;
        brands.value = response.data.brands;
    } catch (error) {
        console.error('Error fetching categories:', error);
    }
};

const fetchCustomers = async () => {
    try {
        const response = await axios.get(route('admin.pos.customers.index'), {
            params: { search: customerSearch.value },
        });
        customers.value = response.data;
    } catch (error) {
        console.error('Error fetching customers:', error);
    }
};

const fetchUnallocatedPayments = async () => {
    if (!selectedCustomer.value) {
        unallocatedPayments.value = [];
        return;
    }
    loadingUnallocated.value = true;
    try {
        const response = await axios.get(route('admin.pos.unallocated-payments.index'), {
            params: { customer_id: selectedCustomer.value.id },
        });
        unallocatedPayments.value = response.data;
    } catch (error) {
        console.error('Error fetching unallocated payments:', error);
    } finally {
        loadingUnallocated.value = false;
    }
};

const saveCartToStorage = () => {
    // Check if session ID is available to avoid errors during mount/unmount
    if (!props.session?.id) return;

    localStorage.setItem(`pos_cart_${props.session.id}`, JSON.stringify(cart.value));
    if (selectedCustomer.value) {
        localStorage.setItem(`pos_customer_${props.session.id}`, JSON.stringify(selectedCustomer.value));
    } else {
        localStorage.removeItem(`pos_customer_${props.session.id}`);
    }
};

const loadCartFromStorage = () => {
    if (!props.session?.id) return;

    const savedCart = localStorage.getItem(`pos_cart_${props.session.id}`);
    if (savedCart) {
        cart.value = JSON.parse(savedCart);
    }
    const savedCustomer = localStorage.getItem(`pos_customer_${props.session.id}`);
    if (savedCustomer) {
        selectedCustomer.value = JSON.parse(savedCustomer);
        fetchUnallocatedPayments();
    }
};

const clearCartFromStorage = () => {
    if (!props.session?.id) return;

    localStorage.removeItem(`pos_cart_${props.session.id}`);
    localStorage.removeItem(`pos_customer_${props.session.id}`);
};

const recordUnallocatedPayment = async () => {
    if (!selectedCustomer.value) return;
    recordingPayment.value = true;
    try {
        await axios.post(route('admin.pos.unallocated-payments.store'), {
            customer_id: selectedCustomer.value.id,
            pos_session_id: props.session.id,
            ...recordPaymentForm.value,
        });
        showRecordPaymentModal.value = false;
        recordPaymentForm.value = { amount: 0, payment_method: 'cash', reference: '', notes: '' };
        fetchUnallocatedPayments();
    } catch (error) {
        console.error('Error recording payment:', error);
        alert('Failed to record payment');
    } finally {
        recordingPayment.value = false;
    }
};

const vatAmount = computed(() => {
    if (!props.settings.vat_enabled) return 0;
    const total = cartTotal();
    return total - total / (1 + props.settings.vat_percentage / 100);
});

onMounted(() => {
    loadCartFromStorage();
    fetchProducts();
    fetchCategories();
    fetchCustomers();
});

watch(
    [cart, selectedCustomer],
    () => {
        saveCartToStorage();
    },
    { deep: true },
);

watch([search, selectedCategoryId, selectedBrandId], () => {
    fetchProducts();
});

watch(customerSearch, () => {
    fetchCustomers();
});

const addToCart = (product: any) => {
    if (product.product_variants.length > 1) {
        selectedProduct.value = product;
        showVariantModal.value = true;
        return;
    }

    const variant = product.product_variants[0];
    if (!variant) return;

    addVariantToCart(product, variant);
};

const addVariantToCart = (product: any, variant: any) => {
    if (variant.stock <= 0) {
        alert('This variant is out of stock');
        return;
    }

    const existingItem = cart.value.find((item) => item.product_variant_id === variant.id);
    if (existingItem) {
        existingItem.quantity++;
    } else {
        cart.value.push({
            product_variant_id: variant.id,
            product_id: product.id,
            name: product.name + (product.product_variants.length > 1 ? ` (${variant.label})` : ''),
            sku: variant.sku,
            price: variant.final_price,
            quantity: 1,
            image: product.primary_image_url,
        });
    }
    showVariantModal.value = false;
    saveCartToStorage();
};

const removeFromCart = (index: number) => {
    cart.value.splice(index, 1);
    saveCartToStorage();
};

const updateQuantity = (index: number, delta: number) => {
    const item = cart.value[index];
    item.quantity += delta;
    if (item.quantity <= 0) {
        removeFromCart(index);
    } else {
        saveCartToStorage();
    }
};

const cartTotal = () => {
    return cart.value.reduce((total, item) => total + item.price * item.quantity, 0);
};

const selectCustomer = (customer: any) => {
    selectedCustomer.value = customer;
    customerPhone.value = customer.user?.phone || '';
    showCustomerModal.value = false;
    fetchUnallocatedPayments();
    saveCartToStorage();
};

const appendZeros = (zeros: string) => {
    const current = amountPaid.value.toString();
    if (current === '0') {
        if (zeros !== '0') {
            amountPaid.value = Number(zeros);
        }
    } else {
        amountPaid.value = Number(current + zeros);
    }
};

const initiateStkPush = async () => {
    if (!customerPhone.value) {
        alert('Please enter customer phone number');
        return;
    }

    processingOrder.value = true;
    try {
        const response = await axios.post(route('payments.initiate'), {
            payable_type: 'pos_session', // Or another appropriate type if needed
            payable_id: props.session.id,
            amount: remainingToPay.value,
            phone: customerPhone.value,
            provider: 'mpesa',
            method: 'stk_push',
        });

        if (response.data.payment) {
            mpesaRequest.value = response.data.payment;
            startPolling();
        }
    } catch (error: any) {
        console.error('Error initiating STK Push:', error);
        alert(error.response?.data?.message || 'Failed to initiate STK Push');
        processingOrder.value = false;
    }
};

const startPolling = () => {
    if (pollingInterval.value) clearInterval(pollingInterval.value);

    pollingInterval.value = setInterval(async () => {
        if (!mpesaRequest.value) return;

        try {
            const response = await axios.get(route('payments.requests.status', mpesaRequest.value.reference));
            if (response.data.verification?.success) {
                stopPolling();
                amountPaid.value = remainingToPay.value;
                submitOrder();
            } else if (response.data.verification?.status === 'failed' || response.data.verification?.status === 'cancelled') {
                stopPolling();
                alert('Payment failed or was cancelled');
                processingOrder.value = false;
            }
        } catch (error) {
            console.error('Error polling status:', error);
        }
    }, 3000);
};

const stopPolling = () => {
    if (pollingInterval.value) {
        clearInterval(pollingInterval.value);
        pollingInterval.value = null;
    }
};

const processCheckout = async () => {
    if (cart.value.length === 0) return;
    if (!selectedCustomer.value) {
        alert('Please select a customer');
        return;
    }
    showPaymentModal.value = true;
    amountPaid.value = remainingToPay.value;
};

const submitOrder = async () => {
    processingOrder.value = true;
    try {
        const response = await axios.post(route('admin.pos.orders.store'), {
            pos_session_id: props.session.id,
            customer_id: selectedCustomer.value.id,
            items: cart.value.map((item) => ({
                product_variant_id: item.product_variant_id,
                quantity: item.quantity,
            })),
            payment_method: paymentMethod.value,
            amount_paid: amountPaid.value,
            allocated_payment_ids: selectedUnallocatedIds.value,
        });

        lastOrder.value = response.data;
        cart.value = [];
        selectedCustomer.value = null;
        clearCartFromStorage();
        selectedUnallocatedIds.value = [];
        showPaymentModal.value = false;
        showReceiptModal.value = true;
        fetchProducts(); // Refresh stock
    } catch (error) {
        console.error('Error submitting order:', error);
        alert('Failed to submit order');
    } finally {
        processingOrder.value = false;
    }
};

const printReceipt = () => {
    const printContent = document.getElementById('receipt-content');
    if (!printContent) return;

    const printWindow = window.open('', '_blank', 'width=800,height=600');

    if (printWindow) {
        printWindow.document.write('<html lang=""><head><title>Print Receipt</title>');
        printWindow.document.write('<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">');
        printWindow.document.write('<style>');
        printWindow.document.write(
            'body { font-family: "Courier New", Courier, monospace; font-size: 12px; width: 80mm; padding: 10px; margin: 0; background: white; }',
        );
        printWindow.document.write('.text-center { text-align: center; }');
        printWindow.document.write('.text-right { text-align: right; }');
        printWindow.document.write('.font-bold { font-weight: bold; }');
        printWindow.document.write('.mb-2 { margin-bottom: 8px; }');
        printWindow.document.write('.border-b { border-bottom: 1px dashed #000; padding-bottom: 5px; margin-bottom: 5px; }');
        printWindow.document.write('table { width: 100%; border-collapse: collapse; }');
        printWindow.document.write('.flex { display: flex; }');
        printWindow.document.write('.justify-between { justify-content: space-between; }');
        printWindow.document.write('.italic { font-style: italic; }');
        printWindow.document.write('.whitespace-pre-line { white-space: pre-line; }');
        printWindow.document.write('</style></head><body>');
        printWindow.document.write(printContent.innerHTML);
        printWindow.document.write('</body></html>');
        printWindow.document.close();

        setTimeout(() => {
            printWindow.focus();
            printWindow.print();
            printWindow.close();
        }, 250);
    }
};

const closeSession = () => {
    if (confirm('Are you sure you want to close this session?')) {
        // We'll use a simple prompt for closing balance for now
        const balance = prompt('Enter closing balance:', '0');
        if (balance !== null) {
            clearCartFromStorage();
            router.post(route('admin.pos.sessions.close', props.session.id), {
                closing_balance: balance,
                notes: 'Closed from terminal',
            });
        }
    }
};

const lockTerminal = () => {
    router.post(route('admin.pos.lock'));
};

const handleVoidSale = () => {
    if (cart.value.length === 0 && !selectedCustomer.value) return;

    if (props.settings.require_admin_void) {
        showVoidModal.value = true;
        adminPin.value = '';
        voidError.value = '';
    } else {
        if (confirm('Are you sure you want to void this sale?')) {
            const reason = prompt('Enter reason for voiding (optional):');
            if (reason !== null) {
                voidSale(reason);
            }
        }
    }
};

const voidSale = async (reason = '') => {
    try {
        await axios.post(route('admin.pos.voided-sales.store'), {
            pos_session_id: props.session.id,
            customer_id: selectedCustomer.value?.id,
            cart_data: cart.value,
            total_amount: cartTotal(),
            reason: reason,
        });

        cart.value = [];
        selectedCustomer.value = null;
        clearCartFromStorage();
        showVoidModal.value = false;
    } catch (error) {
        console.error('Error saving voided sale:', error);
        alert('Failed to save voided sale record, but cart cleared.');
        cart.value = [];
        selectedCustomer.value = null;
        clearCartFromStorage();
        showVoidModal.value = false;
    }
};

const fetchVoidedSales = async () => {
    loadingVoidedSales.value = true;
    try {
        const response = await axios.get(route('admin.pos.voided-sales.index'), {
            params: { pos_session_id: props.session.id },
        });
        voidedSales.value = response.data;
    } catch (error) {
        console.error('Error fetching voided sales:', error);
    } finally {
        loadingVoidedSales.value = false;
    }
};

const openVoidedSales = () => {
    fetchVoidedSales();
    showVoidedSalesModal.value = true;
};

const recallSale = async (voidedSale: any) => {
    if (cart.value.length > 0) {
        if (!confirm('Current cart will be cleared. Continue?')) return;
    }

    try {
        await axios.post(route('admin.pos.voided-sales.recall', voidedSale.id));
        cart.value = voidedSale.cart_data;
        if (voidedSale.customer) {
            selectedCustomer.value = voidedSale.customer;
        }
        saveCartToStorage();
        showVoidedSalesModal.value = false;
    } catch (error) {
        console.error('Error recalling sale:', error);
        alert('Failed to recall sale.');
    }
};

const verifyAdminPin = async () => {
    if (adminPin.value.length !== 4) {
        voidError.value = 'PIN must be 4 digits.';
        return;
    }

    isVerifyingAdmin.value = true;
    voidError.value = '';

    try {
        const response = await axios.post(route('admin.pos.verify-admin-pin'), { pin: adminPin.value });
        if (response.data.success) {
            const reason = prompt('Enter reason for voiding (optional):');
            voidSale(reason || '');
        }
    } catch (error: any) {
        voidError.value = error.response?.data?.message || 'Invalid Admin PIN.';
        adminPin.value = '';
    } finally {
        isVerifyingAdmin.value = false;
    }
};

const appendAdminPin = (digit: number) => {
    if (adminPin.value.length < 4) {
        adminPin.value += digit.toString();
    }
    if (adminPin.value.length === 4) {
        verifyAdminPin();
    }
};
</script>

<template>
    <Head title="POS Terminal" />
    <div class="flex h-screen flex-col overflow-hidden bg-gray-100">
        <!-- Header -->
        <header class="flex items-center justify-between border-b bg-white px-4 py-3 shadow-sm">
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="icon" as="a" :href="route('admin.pos.index')">
                    <ChevronLeft class="h-5 w-5" />
                </Button>
                <h1 class="text-lg font-bold">POS: {{ session.register.name }}</h1>
                <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800"> Open ({{ session.user.name }}) </span>
            </div>
            <div class="flex items-center gap-4">
                <div class="relative w-64">
                    <Search class="absolute top-2.5 left-2.5 h-4 w-4 text-muted-foreground" />
                    <Input v-model="search" placeholder="Search products..." class="pl-9" />
                </div>
                <Button variant="destructive" size="sm" @click="handleVoidSale" :disabled="cart.length === 0 && !selectedCustomer">
                    <Trash2 class="mr-2 h-4 w-4" />
                    Void Sale
                </Button>
                <Button variant="outline" size="sm" @click="openVoidedSales" class="border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100">
                    <History class="mr-2 h-4 w-4" />
                    Voided Sales
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    @click="showUnallocatedModal = true"
                    class="border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100"
                >
                    <Wallet class="mr-2 h-4 w-4" />
                    Unallocated
                </Button>
                <Button variant="outline" size="sm" @click="lockTerminal" class="mr-2">
                    <Lock class="mr-2 h-4 w-4" />
                    Lock
                </Button>
                <Button variant="outline" size="sm" @click="closeSession">
                    <LogOut class="mr-2 h-4 w-4" />
                    Close Session
                </Button>
            </div>
        </header>

        <div class="flex flex-grow overflow-hidden">
            <!-- Left Panel: Categories & Products -->
            <div class="flex w-2/3 flex-col border-r bg-white">
                <!-- Categories & Brands Bar -->
                <div class="space-y-2 border-b p-2">
                    <div class="scrollbar-hide flex gap-2 overflow-x-auto pb-1 whitespace-nowrap">
                        <Button
                            :variant="selectedCategoryId === null ? 'default' : 'outline'"
                            size="sm"
                            @click="selectedCategoryId = null"
                            class="flex-shrink-0"
                        >
                            All Categories
                        </Button>
                        <Button
                            v-for="cat in categories"
                            :key="cat.id"
                            :variant="selectedCategoryId === cat.id ? 'default' : 'outline'"
                            size="sm"
                            @click="selectedCategoryId = cat.id"
                            class="flex-shrink-0"
                        >
                            {{ cat.name }}
                        </Button>
                    </div>
                    <div v-if="brands.length > 0" class="scrollbar-hide flex gap-2 overflow-x-auto pb-1 whitespace-nowrap">
                        <Button
                            :variant="selectedBrandId === null ? 'default' : 'outline'"
                            size="sm"
                            @click="selectedBrandId = null"
                            class="h-7 flex-shrink-0 text-xs"
                        >
                            All Brands
                        </Button>
                        <Button
                            v-for="brand in brands"
                            :key="brand.id"
                            :variant="selectedBrandId === brand.id ? 'default' : 'outline'"
                            size="sm"
                            @click="selectedBrandId = brand.id"
                            class="h-7 flex-shrink-0 text-xs"
                        >
                            {{ brand.name }}
                        </Button>
                    </div>
                </div>

                <!-- Products Grid -->
                <div
                    class="flex-grow overflow-y-auto p-4"
                    :class="[
                        settings.product_display_design === 'small_grid'
                            ? 'grid grid-cols-3 gap-2 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6'
                            : 'grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-4',
                    ]"
                >
                    <Card
                        v-for="product in products"
                        :key="product.id"
                        class="flex h-fit cursor-pointer flex-col overflow-hidden transition-all hover:ring-2 hover:ring-primary"
                        :class="[settings.product_display_design === 'small_grid' ? 'gap-1 p-1' : '']"
                        @click="addToCart(product)"
                    >
                        <div v-if="settings.show_product_images" class="relative aspect-square shrink-0 bg-gray-50">
                            <img v-if="product.primary_image_url" :src="product.primary_image_url" class="h-full w-full object-cover" alt="" />
                            <div v-else class="flex h-full w-full items-center justify-center text-gray-300">
                                <Search class="h-8 w-8" />
                            </div>
                            <div
                                v-if="product.product_variants[0]?.stock <= 0"
                                class="absolute inset-0 flex items-center justify-center bg-black/40 text-[10px] font-bold text-white uppercase"
                            >
                                Out of Stock
                            </div>
                        </div>
                        <CardContent :class="[settings.product_display_design === 'small_grid' ? 'p-1' : 'p-2']">
                            <p class="truncate font-medium" :class="[settings.product_display_design === 'small_grid' ? 'text-[11px]' : 'text-sm']">
                                {{ product.name }}
                            </p>
                            <div class="mt-0.5 flex items-center justify-between">
                                <p
                                    class="truncate text-muted-foreground"
                                    :class="[settings.product_display_design === 'small_grid' ? 'text-[9px]' : 'text-xs']"
                                >
                                    {{ product.category?.name }}
                                </p>
                                <span
                                    class="flex-shrink-0 rounded font-bold"
                                    :class="[
                                        settings.product_display_design === 'small_grid' ? 'px-0.5 text-[8px]' : 'px-1 text-[10px]',
                                        product.product_variants[0]?.stock > 5 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700',
                                    ]"
                                >
                                    Qty: {{ product.product_variants[0]?.stock || 0 }}
                                </span>
                            </div>
                            <p class="mt-0.5 font-bold text-primary" :class="[settings.product_display_design === 'small_grid' ? 'text-[11px]' : '']">
                                {{ settings.currency_symbol || 'KES' }} {{ Number(product.product_variants[0]?.final_price).toLocaleString() }}
                            </p>
                        </CardContent>
                    </Card>

                    <div v-if="loadingProducts" class="col-span-full py-20 text-center">
                        <p class="text-muted-foreground">Loading products...</p>
                    </div>

                    <div v-if="!loadingProducts && products.length === 0" class="col-span-full py-20 text-center">
                        <div v-if="!search && !selectedCategoryId && !selectedBrandId" class="space-y-3">
                            <Search class="mx-auto h-12 w-12 text-gray-300" />
                            <p class="text-muted-foreground">Search or select a category/brand to display products</p>
                        </div>
                        <p v-else class="text-muted-foreground">No products found matching your criteria</p>
                    </div>
                </div>
            </div>

            <!-- Right Panel: Cart & Customer -->
            <div class="flex w-1/3 flex-col bg-white">
                <!-- Customer Selection -->
                <div class="border-b p-4">
                    <div v-if="selectedCustomer" class="flex items-center justify-between rounded-lg border border-blue-100 bg-blue-50 p-3">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-200 font-bold text-blue-700">
                                {{ selectedCustomer.user.name.charAt(0) }}
                            </div>
                            <div>
                                <p class="text-sm font-bold">{{ selectedCustomer.user.name }}</p>
                                <p class="text-xs text-blue-600">{{ selectedCustomer.user.phone || 'No phone' }}</p>
                            </div>
                        </div>
                        <Button variant="ghost" size="icon" @click="selectedCustomer = null">
                            <Trash2 class="h-4 w-4 text-red-500" />
                        </Button>
                    </div>
                    <Button v-else variant="outline" class="w-full justify-start gap-2" @click="showCustomerModal = true">
                        <UserPlus class="h-4 w-4" />
                        Select Customer
                    </Button>
                </div>

                <!-- Cart Items -->
                <div class="flex-grow overflow-y-auto">
                    <div v-if="cart.length === 0" class="flex h-full flex-col items-center justify-center p-10 text-center text-muted-foreground">
                        <Search class="mb-2 h-12 w-12 opacity-20" />
                        <p>Your cart is empty</p>
                    </div>
                    <div v-else class="divide-y">
                        <div v-for="(item, index) in cart" :key="index" class="flex items-center gap-3 p-4">
                            <img :src="item.image" class="h-12 w-12 rounded bg-gray-50 object-cover" v-if="item.image" alt="" />
                            <div class="grow overflow-hidden">
                                <p class="truncate text-sm font-medium">{{ item.name }}</p>
                                <p class="text-xs text-muted-foreground">KES {{ Number(item.price).toLocaleString() }}</p>
                            </div>
                            <div class="flex items-center gap-2 rounded-md border p-1">
                                <Button variant="ghost" size="icon" class="h-6 w-6" @click="updateQuantity(index, -1)">
                                    <Minus class="h-3 w-3" />
                                </Button>
                                <span class="w-6 text-center text-xs font-bold">{{ item.quantity }}</span>
                                <Button variant="ghost" size="icon" class="h-6 w-6" @click="updateQuantity(index, 1)">
                                    <Plus class="h-3 w-3" />
                                </Button>
                            </div>
                            <div class="min-w-[80px] text-right">
                                <p class="text-sm font-bold">KES {{ (item.price * item.quantity).toLocaleString() }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary & Checkout -->
                <div class="space-y-4 border-t bg-gray-50 p-4">
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-muted-foreground">Subtotal</span>
                            <span>KES {{ (cartTotal() - vatAmount).toLocaleString() }}</span>
                        </div>
                        <div class="flex justify-between text-sm" v-if="settings.vat_enabled">
                            <span class="text-muted-foreground">Tax ({{ settings.vat_percentage }}%)</span>
                            <span>KES {{ vatAmount.toLocaleString() }}</span>
                        </div>
                        <Separator />
                        <div class="flex justify-between text-xl font-bold">
                            <span>Total</span>
                            <span class="text-primary">KES {{ cartTotal().toLocaleString() }}</span>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <Button variant="outline" class="h-14 flex-1" @click="handleVoidSale" :disabled="cart.length === 0 && !selectedCustomer">
                            Void
                        </Button>
                        <Button class="h-14 flex-[3] text-lg font-bold" @click="processCheckout" :disabled="cart.length === 0">
                            Pay KES {{ cartTotal().toLocaleString() }}
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Void Sale Authorization Modal -->
        <div v-if="showVoidModal" class="fixed inset-0 z-[70] flex items-center justify-center bg-black/50 p-4">
            <Card class="w-full max-w-sm overflow-hidden bg-white">
                <CardHeader class="text-center">
                    <CardTitle>Void Sale Authorization</CardTitle>
                    <CardDescription>An administrator must enter their PIN to void this sale.</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="flex flex-col items-center gap-6">
                        <!-- PIN Display -->
                        <div class="flex gap-4">
                            <div
                                v-for="i in 4"
                                :key="i"
                                class="h-4 w-4 rounded-full border-2 border-red-500"
                                :class="{ 'bg-red-500': adminPin.length >= i }"
                            ></div>
                        </div>

                        <p v-if="voidError" class="text-sm font-medium text-red-600">{{ voidError }}</p>

                        <!-- Number Pad -->
                        <div class="grid w-full grid-cols-3 gap-4">
                            <Button v-for="n in 9" :key="n" variant="outline" class="h-16 text-xl font-bold" @click="appendAdminPin(n)">
                                {{ n }}
                            </Button>
                            <Button variant="ghost" class="h-16 text-red-600" @click="adminPin = ''">Clear</Button>
                            <Button variant="outline" class="h-16 text-xl font-bold" @click="appendAdminPin(0)">0</Button>
                            <Button variant="ghost" class="h-16" @click="showVoidModal = false">Cancel</Button>
                        </div>

                        <div v-if="isVerifyingAdmin" class="animate-pulse text-sm text-muted-foreground">Verifying Admin PIN...</div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Receipt Preview Modal -->
        <div v-if="showReceiptModal && lastOrder" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 p-4">
            <Card class="flex w-full max-w-sm flex-col overflow-hidden bg-white">
                <CardHeader class="flex flex-row items-center justify-between border-b py-3">
                    <CardTitle class="text-base">Sale Completed</CardTitle>
                    <Button variant="ghost" size="icon" @click="showReceiptModal = false">
                        <Plus class="h-4 w-4 rotate-45" />
                    </Button>
                </CardHeader>
                <CardContent class="grow overflow-y-auto p-0">
                    <!-- Receipt Content for Printing -->
                    <div
                        id="receipt-content"
                        class="mx-auto bg-white p-6 text-black"
                        style="width: 80mm; font-family: 'Courier New', Courier, monospace; font-size: 12px"
                    >
                        <div class="mb-4 text-center">
                            <h2 class="text-lg leading-tight font-bold">{{ lastOrder.settings.business_name }}</h2>
                            <p v-if="lastOrder.settings.business_address">{{ lastOrder.settings.business_address }}</p>
                            <p v-if="lastOrder.settings.business_phone">Tel: {{ lastOrder.settings.business_phone }}</p>
                            <p v-if="lastOrder.settings.tax_number">VAT PIN: {{ lastOrder.settings.tax_number }}</p>
                        </div>

                        <div class="mb-2 border-b">
                            <p>Receipt: {{ lastOrder.order.order_code }}</p>
                            <p>Date: {{ new Date(lastOrder.order.created_at).toLocaleString() }}</p>
                            <p>Cashier: {{ session.user.name }}</p>
                            <p>Customer: {{ lastOrder.order.customer?.first_name }} {{ lastOrder.order.customer?.last_name }}</p>
                        </div>

                        <div v-if="lastOrder.settings.receipt_header" class="mb-2 text-center whitespace-pre-line italic">
                            {{ lastOrder.settings.receipt_header }}
                        </div>

                        <table class="mb-2 w-full">
                            <thead>
                                <tr class="border-b">
                                    <th class="py-1 text-left">Item</th>
                                    <th class="py-1 text-right">Qty</th>
                                    <th class="py-1 text-right">Price</th>
                                    <th class="py-1 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in lastOrder.order.order_items" :key="item.id">
                                    <td class="py-1">{{ item.product_snapshot.name }}</td>
                                    <td class="py-1 text-right">{{ item.quantity }}</td>
                                    <td class="py-1 text-right">{{ Number(item.unit_price).toLocaleString() }}</td>
                                    <td class="py-1 text-right font-bold">{{ Number(item.total_price).toLocaleString() }}</td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="mb-2 border-b"></div>

                        <div class="mb-4 space-y-1">
                            <div class="flex justify-between">
                                <span>Subtotal:</span>
                                <span>{{ lastOrder.order.currency }} {{ Number(lastOrder.order.subtotal).toLocaleString() }}</span>
                            </div>
                            <div class="flex justify-between" v-if="Number(lastOrder.order.tax_amount) > 0">
                                <span>VAT ({{ lastOrder.settings.vat_percentage }}%):</span>
                                <span>{{ lastOrder.order.currency }} {{ Number(lastOrder.order.tax_amount).toLocaleString() }}</span>
                            </div>
                            <div class="flex justify-between text-sm font-bold">
                                <span>TOTAL:</span>
                                <span>{{ lastOrder.order.currency }} {{ Number(lastOrder.order.total_amount).toLocaleString() }}</span>
                            </div>
                        </div>

                        <div class="mb-2 border-b"></div>

                        <div class="mb-2 flex justify-between">
                            <span>Paid Via:</span>
                            <span class="capitalize">{{ lastOrder.order.payments[0]?.method }}</span>
                        </div>

                        <div v-if="lastOrder.settings.receipt_footer" class="mt-4 text-center whitespace-pre-line italic">
                            {{ lastOrder.settings.receipt_footer }}
                        </div>

                        <div class="mt-6 text-center text-[10px]">Powered by Buyalot POS</div>
                    </div>
                </CardContent>
                <CardFooter class="grid grid-cols-2 gap-3 border-t p-4">
                    <Button variant="outline" @click="showReceiptModal = false">Close</Button>
                    <Button @click="printReceipt">Print Receipt</Button>
                </CardFooter>
            </Card>
        </div>

        <!-- Customer Modal -->
        <div v-if="showCustomerModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <Card class="flex max-h-[80vh] w-full max-w-2xl flex-col bg-white">
                <header class="flex items-center justify-between border-b p-4">
                    <h2 class="text-lg font-bold">Select Customer</h2>
                    <Button variant="ghost" size="icon" @click="showCustomerModal = false">
                        <Plus class="h-5 w-5 rotate-45" />
                    </Button>
                </header>
                <div class="p-4">
                    <div class="relative mb-4">
                        <Search class="absolute top-2.5 left-2.5 h-4 w-4 text-muted-foreground" />
                        <Input v-model="customerSearch" placeholder="Search by name, phone or email..." class="pl-9" />
                    </div>
                    <div class="max-h-[50vh] divide-y overflow-y-auto rounded-md border">
                        <div
                            v-for="customer in customers"
                            :key="customer.id"
                            class="flex cursor-pointer items-center justify-between p-3 hover:bg-gray-50"
                            @click="selectCustomer(customer)"
                        >
                            <div class="flex items-center gap-3">
                                <UsersIcon class="h-5 w-5 text-gray-400" />
                                <div>
                                    <p class="font-medium">{{ customer.user.name }}</p>
                                    <p class="text-xs text-muted-foreground">{{ customer.user.phone }} | {{ customer.user.email }}</p>
                                </div>
                            </div>
                            <Button variant="outline" size="sm">Select</Button>
                        </div>
                        <div v-if="customers.length === 0" class="p-10 text-center text-muted-foreground">
                            No customers found matching "{{ customerSearch }}"
                        </div>
                    </div>
                </div>
            </Card>
        </div>

        <!-- Variant Selection Modal -->
        <div v-if="showVariantModal" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 p-4">
            <Card class="w-full max-w-lg bg-white">
                <header class="flex items-center justify-between border-b p-4">
                    <h2 class="text-lg font-bold">Select Variant: {{ selectedProduct?.name }}</h2>
                    <Button variant="ghost" size="icon" @click="showVariantModal = false">
                        <Plus class="h-5 w-5 rotate-45" />
                    </Button>
                </header>
                <div class="grid max-h-[60vh] grid-cols-1 gap-3 overflow-y-auto p-4">
                    <div
                        v-for="variant in selectedProduct?.product_variants"
                        :key="variant.id"
                        class="flex cursor-pointer items-center justify-between rounded-xl border p-4 hover:border-primary"
                        @click="addVariantToCart(selectedProduct, variant)"
                        :class="{ 'pointer-events-none opacity-50 grayscale': variant.stock <= 0 }"
                    >
                        <div>
                            <p class="font-bold">{{ variant.label || 'Standard Variant' }}</p>
                            <p class="text-xs text-muted-foreground">SKU: {{ variant.sku }} | Stock: {{ variant.stock }}</p>
                        </div>
                        <p class="font-bold text-primary">KES {{ Number(variant.final_price).toLocaleString() }}</p>
                    </div>
                </div>
            </Card>
        </div>

        <!-- Payment Modal -->
        <div v-if="showPaymentModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <Card class="w-full max-w-md bg-white">
                <header class="flex items-center justify-between border-b p-4">
                    <h2 class="text-lg font-bold">Checkout</h2>
                    <Button variant="ghost" size="icon" @click="showPaymentModal = false">
                        <Plus class="h-5 w-5 rotate-45" />
                    </Button>
                </header>
                <div class="space-y-6 p-6">
                    <div class="text-center">
                        <p class="text-sm font-semibold text-muted-foreground uppercase">Total Amount Due</p>
                        <h3 class="text-4xl font-black text-primary">KES {{ cartTotal().toLocaleString() }}</h3>
                    </div>

                    <div
                        v-if="unallocatedPayments.length > 0 && selectedUnallocatedIds.length === 0"
                        class="flex items-center justify-between rounded-lg border border-amber-100 bg-amber-50 p-3 text-sm text-amber-800"
                    >
                        <span>This customer has unallocated payments.</span>
                        <Button
                            type="button"
                            variant="link"
                            size="sm"
                            @click="showUnallocatedModal = true"
                            class="h-auto p-0 font-bold text-amber-800"
                            >Allocate</Button
                        >
                    </div>

                    <div class="space-y-3">
                        <Label>Select Payment Method</Label>
                        <div class="grid grid-cols-2 gap-3">
                            <div
                                v-for="method in allowedPaymentMethods"
                                :key="method.id"
                                class="flex cursor-pointer flex-col items-center gap-2 rounded-xl border-2 p-4 transition-all"
                                :class="paymentMethod === method.id ? 'border-primary bg-primary/5' : 'border-gray-100 hover:border-gray-200'"
                                @click="paymentMethod = method.id"
                            >
                                <QrCode v-if="method.show_qr || method.id.includes('qr')" class="h-5 w-5" />
                                <span class="font-bold uppercase">{{ method.name }}</span>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="selectedPaymentMethodDetails?.show_qr"
                        class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed bg-white p-6 shadow-sm"
                    >
                        <div v-if="qrCodeUrl" class="mb-3 rounded-lg border bg-white p-2">
                            <img :src="qrCodeUrl" alt="Payment QR Code" class="h-48 w-48" />
                        </div>
                        <QrCode v-else class="mb-2 h-24 w-24 text-gray-300" />

                        <p class="text-sm font-bold text-primary">{{ selectedPaymentMethodDetails.name }}</p>
                        <p class="mt-1 text-center text-xs whitespace-pre-line text-muted-foreground">{{ selectedPaymentMethodDetails.qr_value }}</p>

                        <div class="mt-4 flex w-full gap-2">
                            <Button type="button" variant="outline" size="sm" class="flex-1" @click="amountPaid = remainingToPay">
                                Confirm Payment Received
                            </Button>
                        </div>
                    </div>

                    <div
                        v-else-if="paymentMethod.includes('qr')"
                        class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed bg-gray-50 p-6"
                    >
                        <QrCode class="mb-2 h-24 w-24 text-gray-300" />
                        <p class="text-sm font-medium">Scan to Pay</p>
                        <p class="mt-1 text-center text-xs text-muted-foreground">Simulated QR Code Payment Flow</p>
                        <Button type="button" variant="outline" size="sm" class="mt-4" @click="amountPaid = remainingToPay">Confirm Scan</Button>
                    </div>

                    <div v-if="totalAllocated > 0" class="rounded-lg border border-blue-100 bg-blue-50 p-3 text-sm text-blue-800">
                        <div class="flex justify-between font-bold">
                            <span>Total Allocated:</span>
                            <span>KES {{ totalAllocated.toLocaleString() }}</span>
                        </div>
                        <div class="mt-1 flex justify-between">
                            <span>Remaining to Pay:</span>
                            <span>KES {{ remainingToPay.toLocaleString() }}</span>
                        </div>
                    </div>

                    <div class="space-y-2" v-if="remainingToPay > 0">
                        <Label for="amount_paid">Amount Paid ({{ paymentMethod }})</Label>
                        <div class="space-y-2">
                            <Input id="amount_paid" v-model="amountPaid" type="number" class="h-14 text-2xl font-bold" />
                            <div class="grid grid-cols-4 gap-2">
                                <Button type="button" variant="outline" size="sm" @click="appendZeros('0')">0</Button>
                                <Button type="button" variant="outline" size="sm" @click="appendZeros('00')">00</Button>
                                <Button type="button" variant="outline" size="sm" @click="appendZeros('000')">000</Button>
                                <Button type="button" variant="outline" size="sm" @click="appendZeros('0000')">0000</Button>
                            </div>
                        </div>
                    </div>

                    <div v-if="paymentMethod === 'mpesa'" class="space-y-2 rounded-lg border border-green-100 bg-green-50 p-4">
                        <Label for="customer_phone">M-Pesa Phone Number</Label>
                        <Input id="customer_phone" v-model="customerPhone" placeholder="e.g. 254712345678" class="font-bold" />
                        <Button
                            type="button"
                            class="w-full bg-green-600 text-white hover:bg-green-700"
                            @click="initiateStkPush"
                            :disabled="processingOrder"
                        >
                            {{ processingOrder ? 'Waiting for Payment...' : 'Send STK Push' }}
                        </Button>
                        <p class="text-center text-[10px] text-green-700">A prompt will be sent to the customer's phone.</p>
                    </div>

                    <div
                        v-if="amountPaid > remainingToPay && remainingToPay > 0"
                        class="flex items-center justify-between rounded-lg border border-green-100 bg-green-50 p-3"
                    >
                        <span class="font-medium text-green-700">Change Due:</span>
                        <span class="text-lg font-black text-green-800">KES {{ (amountPaid - remainingToPay).toLocaleString() }}</span>
                    </div>
                </div>
                <footer class="flex gap-3 border-t p-4">
                    <Button variant="outline" class="h-14 flex-1" @click="showPaymentModal = false">Cancel</Button>
                    <Button
                        class="h-14 flex-[2] text-xl font-bold"
                        @click="submitOrder"
                        :disabled="processingOrder || (amountPaid < remainingToPay && remainingToPay > 0)"
                    >
                        {{ processingOrder ? 'Processing...' : 'Complete Sale' }}
                    </Button>
                </footer>
            </Card>
        </div>

        <!-- Unallocated Payments Modal -->
        <div v-if="showUnallocatedModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <Card class="flex max-h-[90vh] w-full max-w-2xl flex-col bg-white">
                <CardHeader class="flex flex-row items-center justify-between">
                    <div>
                        <CardTitle>Unallocated Payments</CardTitle>
                        <CardDescription v-if="selectedCustomer">Customer: {{ selectedCustomer.user.name }}</CardDescription>
                        <CardDescription v-else>Please select a customer first</CardDescription>
                    </div>
                    <Button v-if="selectedCustomer" size="sm" @click="showRecordPaymentModal = true" class="gap-2">
                        <Plus class="h-4 w-4" />
                        Record Payment
                    </Button>
                </CardHeader>
                <CardContent class="grow overflow-y-auto">
                    <div v-if="!selectedCustomer" class="py-10 text-center text-muted-foreground">
                        Select a customer to view their unallocated payments.
                    </div>
                    <div v-else-if="loadingUnallocated" class="py-10 text-center text-muted-foreground">Loading payments...</div>
                    <div v-else-if="unallocatedPayments.length === 0" class="py-10 text-center text-muted-foreground">
                        No active unallocated payments for this customer.
                    </div>
                    <div v-else class="space-y-2">
                        <div
                            v-for="payment in unallocatedPayments"
                            :key="payment.id"
                            class="flex cursor-pointer items-center justify-between rounded-lg border p-3 hover:bg-gray-50"
                            @click="
                                () => {
                                    if (selectedUnallocatedIds.includes(payment.id)) {
                                        selectedUnallocatedIds = selectedUnallocatedIds.filter((id) => id !== payment.id);
                                    } else {
                                        selectedUnallocatedIds.push(payment.id);
                                    }
                                }
                            "
                        >
                            <div class="flex items-center gap-3">
                                <input
                                    type="checkbox"
                                    :checked="selectedUnallocatedIds.includes(payment.id)"
                                    class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                                    @click.stop
                                />
                                <div>
                                    <p class="font-medium">KES {{ Number(payment.amount - payment.used_amount).toLocaleString() }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ payment.payment_method }} • {{ new Date(payment.created_at).toLocaleDateString() }}
                                        <span v-if="payment.reference"> • {{ payment.reference }}</span>
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">Available</p>
                            </div>
                        </div>
                    </div>
                </CardContent>
                <CardFooter class="justify-between border-t p-4">
                    <Button variant="outline" @click="showUnallocatedModal = false">Close</Button>
                    <div class="flex items-center gap-4">
                        <p class="text-sm font-medium">
                            Allocated: <span class="font-bold text-primary">KES {{ totalAllocated.toLocaleString() }}</span>
                        </p>
                        <Button @click="showUnallocatedModal = false">Apply to Cart</Button>
                    </div>
                </CardFooter>
            </Card>
        </div>

        <!-- Record Payment Modal -->
        <div v-if="showRecordPaymentModal" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 p-4">
            <Card class="w-full max-w-md bg-white">
                <CardHeader>
                    <CardTitle>Record Unallocated Payment</CardTitle>
                    <CardDescription>Payment will be stored on customer's account for future use.</CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid gap-2">
                        <Label for="rec_amount">Amount</Label>
                        <Input id="rec_amount" type="number" v-model="recordPaymentForm.amount" required />
                    </div>
                    <div class="grid gap-2">
                        <Label>Payment Method</Label>
                        <Select v-model="recordPaymentForm.payment_method">
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="method in allowedPaymentMethods" :key="method.id" :value="method.id">
                                    {{ method.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="grid gap-2">
                        <Label for="rec_notes">Notes (Optional)</Label>
                        <Textarea id="rec_notes" v-model="recordPaymentForm.notes" placeholder="Reason for advanced payment..." />
                    </div>
                    <div class="grid gap-2">
                        <Label for="rec_ref">Reference (Optional)</Label>
                        <Input id="rec_ref" v-model="recordPaymentForm.reference" placeholder="e.g. M-Pesa Code" />
                    </div>
                </CardContent>
                <CardFooter class="justify-end gap-3 border-t p-4">
                    <Button variant="outline" @click="showRecordPaymentModal = false">Cancel</Button>
                    <Button :disabled="recordingPayment || recordPaymentForm.amount <= 0" @click="recordUnallocatedPayment">
                        {{ recordingPayment ? 'Saving...' : 'Record Payment' }}
                    </Button>
                </CardFooter>
            </Card>
        </div>

        <!-- Voided Sales Modal -->
        <div v-if="showVoidedSalesModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <Card class="flex max-h-[90vh] w-full max-w-4xl flex-col bg-white">
                <CardHeader class="flex flex-row items-center justify-between">
                    <div>
                        <CardTitle>Voided Sales</CardTitle>
                        <CardDescription>Recently voided transactions that can be recalled</CardDescription>
                    </div>
                    <Button variant="ghost" size="icon" @click="showVoidedSalesModal = false">
                        <Plus class="h-4 w-4 rotate-45" />
                    </Button>
                </CardHeader>
                <CardContent class="grow overflow-y-auto">
                    <div v-if="loadingVoidedSales" class="py-20 text-center">
                        <p class="animate-pulse text-muted-foreground">Loading voided sales...</p>
                    </div>
                    <div v-else-if="voidedSales.length === 0" class="py-20 text-center">
                        <p class="text-muted-foreground">No voided sales found for this session.</p>
                    </div>
                    <div v-else class="rounded-md border">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b bg-muted/50 text-left">
                                    <th class="p-3 font-medium">Time</th>
                                    <th class="p-3 font-medium">Customer</th>
                                    <th class="p-3 font-medium">Items</th>
                                    <th class="p-3 text-right font-medium">Total</th>
                                    <th class="p-3 font-medium">Reason</th>
                                    <th class="p-3 text-right font-medium">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="sale in voidedSales" :key="sale.id" class="border-b last:border-0 hover:bg-gray-50">
                                    <td class="p-3 text-muted-foreground">{{ new Date(sale.voided_at).toLocaleTimeString() }}</td>
                                    <td class="p-3 font-medium">{{ sale.customer?.user?.name || 'Walk-in Customer' }}</td>
                                    <td class="p-3">
                                        <div class="text-xs">
                                            {{ sale.cart_data.length }} items
                                            <span class="text-muted-foreground"
                                                >({{
                                                    sale.cart_data
                                                        .map((i) => i.name)
                                                        .slice(0, 2)
                                                        .join(', ')
                                                }}{{ sale.cart_data.length > 2 ? '...' : '' }})</span
                                            >
                                        </div>
                                    </td>
                                    <td class="p-3 text-right font-bold">KES {{ Number(sale.total_amount).toLocaleString() }}</td>
                                    <td class="p-3 text-xs text-muted-foreground italic">{{ sale.reason || 'No reason provided' }}</td>
                                    <td class="p-3 text-right">
                                        <Button size="sm" variant="outline" @click="recallSale(sale)" class="gap-1">
                                            <History class="h-3 w-3" />
                                            Recall
                                        </Button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
                <div class="border-t bg-gray-50 p-4 text-right">
                    <Button variant="outline" @click="showVoidedSalesModal = false">Close</Button>
                </div>
            </Card>
        </div>
    </div>
</template>

<style scoped>
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
