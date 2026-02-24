<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { api } from '../api/client';
import * as posApi from '../api/pos';
import { loadSettings, startPolling, stopPolling, clearSettingsCache, cachedSettings, type PosSettings } from '../api/settingsCache';
import { ChevronLeft, Lock, LogOut, Minus, Plus, Search, Trash2, UserPlus, Users as UsersIcon, History, Wallet, QrCode, X } from 'lucide-vue-next';
import { onMounted, onUnmounted, ref, watch, computed, reactive } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();
const session = ref<any>(null);
const settings = ref<any>({});
const loadingSession = ref(true);

const products = ref<any[]>([]);
const categories = ref<any[]>([]);
const brands = ref<any[]>([]);
const selectedCategoryId = ref<number | null>(null);
const selectedBrandId = ref<number | null>(null);
const search = ref('');
const loadingProducts = ref(false);

// --- Multi-tab state ---
interface TabState {
    id: number;
    label: string;
    cart: any[];
    selectedCustomer: any;
    unallocatedPayments: any[];
    selectedUnallocatedIds: number[];
}

const tabs = ref<TabState[]>([]);
const activeTabId = ref<number>(1);
let nextTabId = 2;

const maxTabs = computed(() => settings.value?.max_tabs || 1);

const activeTab = computed(() => tabs.value.find(t => t.id === activeTabId.value) || tabs.value[0]);

const cart = computed({
    get: () => activeTab.value?.cart ?? [],
    set: (val: any[]) => { if (activeTab.value) activeTab.value.cart = val; }
});

const selectedCustomer = computed({
    get: () => activeTab.value?.selectedCustomer ?? null,
    set: (val: any) => { if (activeTab.value) activeTab.value.selectedCustomer = val; }
});

const unallocatedPayments = computed({
    get: () => activeTab.value?.unallocatedPayments ?? [],
    set: (val: any[]) => { if (activeTab.value) activeTab.value.unallocatedPayments = val; }
});

const selectedUnallocatedIds = computed({
    get: () => activeTab.value?.selectedUnallocatedIds ?? [],
    set: (val: number[]) => { if (activeTab.value) activeTab.value.selectedUnallocatedIds = val; }
});

// --- Shared UI state ---
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
const loadingUnallocated = ref(false);
const showVariantModal = ref(false);
const selectedProduct = ref<any>(null);
const showRecordPaymentModal = ref(false);
const recordPaymentForm = ref({ amount: 0, payment_method: 'cash', reference: '', notes: '' });
const recordingPayment = ref(false);

const allowedPaymentMethods = computed(() => {
    const pm = settings.value?.payment_methods;
    if (!pm) return [{ id: 'cash', name: 'Cash', enabled: true }];
    return (Array.isArray(pm) ? pm : []).filter((m: any) => m.enabled);
});

const totalAllocated = computed(() => {
    return (activeTab.value?.unallocatedPayments ?? [])
        .filter((p: any) => (activeTab.value?.selectedUnallocatedIds ?? []).includes(p.id))
        .reduce((sum: number, p: any) => sum + Number((p.amount || 0) - (p.used_amount || 0)), 0);
});

const remainingToPay = computed(() => {
    return Math.max(0, cartTotal() - totalAllocated.value);
});

const selectedPaymentMethodDetails = computed(() => {
    return allowedPaymentMethods.value.find((m: any) => m.id === paymentMethod.value);
});

const qrCodeUrl = computed(() => {
    const d = selectedPaymentMethodDetails.value;
    if (!d?.show_qr || !d?.qr_value) return null;
    return `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(d.qr_value)}`;
});

const vatAmount = computed(() => {
    if (!settings.value?.vat_enabled) return 0;
    const t = cartTotal();
    return t - t / (1 + (settings.value.vat_percentage || 0) / 100);
});

// --- Tab management ---
function createTab(): TabState {
    const id = nextTabId++;
    return {
        id,
        label: `Tab ${tabs.value.length + 1}`,
        cart: [],
        selectedCustomer: null,
        unallocatedPayments: [],
        selectedUnallocatedIds: [],
    };
}

function addTab() {
    if (tabs.value.length >= maxTabs.value) return;
    const tab = createTab();
    tabs.value.push(tab);
    activeTabId.value = tab.id;
    saveAllTabsToStorage();
}

function closeTab(tabId: number) {
    if (tabs.value.length <= 1) return;
    const idx = tabs.value.findIndex(t => t.id === tabId);
    if (idx < 0) return;
    tabs.value.splice(idx, 1);
    if (activeTabId.value === tabId) {
        activeTabId.value = tabs.value[Math.max(0, idx - 1)].id;
    }
    saveAllTabsToStorage();
}

function switchTab(tabId: number) {
    activeTabId.value = tabId;
}

// --- Data fetching ---
const fetchSession = async () => {
    try {
        const data = await posApi.getSession();
        session.value = data.session;

        const cached = await loadSettings();
        settings.value = cached || data.settings || {};

        if (!session.value) {
            router.replace({ name: 'registers' });
            return;
        }

        startPolling();
    } catch (e) {
        router.replace({ name: 'login' });
    } finally {
        loadingSession.value = false;
    }
};

const fetchProducts = async (page = 1) => {
    if (!search.value && !selectedCategoryId.value && !selectedBrandId.value) {
        products.value = [];
        return;
    }
    loadingProducts.value = true;
    try {
        const data = await posApi.getProducts({
            search: search.value,
            category_id: selectedCategoryId.value ?? undefined,
            brand_id: selectedBrandId.value ?? undefined,
            page,
        });
        products.value = data?.data ?? data ?? [];
    } catch {
    } finally {
        loadingProducts.value = false;
    }
};

const fetchCategories = async () => {
    try {
        const data = await posApi.getCategories();
        categories.value = data?.categories ?? [];
        brands.value = data?.brands ?? [];
    } catch {}
};

const fetchCustomers = async () => {
    try {
        const data = await posApi.getCustomers(customerSearch.value);
        customers.value = Array.isArray(data) ? data : [];
    } catch {}
};

const fetchUnallocatedPayments = async () => {
    if (!selectedCustomer.value) {
        unallocatedPayments.value = [];
        return;
    }
    loadingUnallocated.value = true;
    try {
        const data = await posApi.getUnallocatedPayments(selectedCustomer.value.id);
        unallocatedPayments.value = Array.isArray(data) ? data : [];
    } catch {
    } finally {
        loadingUnallocated.value = false;
    }
};

// --- Storage persistence (all tabs) ---
const saveAllTabsToStorage = () => {
    const sid = session.value?.id;
    if (!sid) return;
    const payload = tabs.value.map(t => ({
        id: t.id,
        label: t.label,
        cart: t.cart,
        selectedCustomer: t.selectedCustomer,
        selectedUnallocatedIds: t.selectedUnallocatedIds,
    }));
    localStorage.setItem(`pos_tabs_${sid}`, JSON.stringify(payload));
    localStorage.setItem(`pos_active_tab_${sid}`, String(activeTabId.value));
};

const loadTabsFromStorage = () => {
    const sid = session.value?.id;
    if (!sid) return;
    const raw = localStorage.getItem(`pos_tabs_${sid}`);
    if (raw) {
        try {
            const stored = JSON.parse(raw);
            if (Array.isArray(stored) && stored.length > 0) {
                tabs.value = stored.map((t: any) => ({
                    id: t.id,
                    label: t.label || `Tab ${t.id}`,
                    cart: t.cart || [],
                    selectedCustomer: t.selectedCustomer || null,
                    unallocatedPayments: [],
                    selectedUnallocatedIds: t.selectedUnallocatedIds || [],
                }));
                nextTabId = Math.max(...tabs.value.map(t => t.id)) + 1;

                const savedActive = Number(localStorage.getItem(`pos_active_tab_${sid}`));
                if (tabs.value.some(t => t.id === savedActive)) {
                    activeTabId.value = savedActive;
                } else {
                    activeTabId.value = tabs.value[0].id;
                }

                for (const tab of tabs.value) {
                    if (tab.selectedCustomer) {
                        fetchUnallocatedForTab(tab);
                    }
                }
                return;
            }
        } catch {}
    }

    tabs.value = [{ id: 1, label: 'Tab 1', cart: [], selectedCustomer: null, unallocatedPayments: [], selectedUnallocatedIds: [] }];
    activeTabId.value = 1;
    nextTabId = 2;
};

const fetchUnallocatedForTab = async (tab: TabState) => {
    if (!tab.selectedCustomer) return;
    try {
        const data = await posApi.getUnallocatedPayments(tab.selectedCustomer.id);
        tab.unallocatedPayments = Array.isArray(data) ? data : [];
    } catch {}
};

const clearAllTabsFromStorage = () => {
    const sid = session.value?.id;
    if (!sid) return;
    localStorage.removeItem(`pos_tabs_${sid}`);
    localStorage.removeItem(`pos_active_tab_${sid}`);
};

// --- Cart operations ---
const recordUnallocatedPayment = async () => {
    if (!selectedCustomer.value || !session.value) return;
    recordingPayment.value = true;
    try {
        await posApi.storeUnallocatedPayment({
            customer_id: selectedCustomer.value.id,
            pos_session_id: session.value.id,
            ...recordPaymentForm.value,
        });
        showRecordPaymentModal.value = false;
        recordPaymentForm.value = { amount: 0, payment_method: 'cash', reference: '', notes: '' };
        fetchUnallocatedPayments();
    } catch {
        alert('Failed to record payment');
    } finally {
        recordingPayment.value = false;
    }
};

const cartTotal = () => (activeTab.value?.cart ?? []).reduce((s: number, i: any) => s + Number(i.price || 0) * (i.quantity || 1), 0);

const addToCart = (product: any) => {
    const variants = product.product_variants ?? product.productVariants ?? [];
    if (variants.length > 1) {
        selectedProduct.value = product;
        showVariantModal.value = true;
        return;
    }
    const v = variants[0];
    if (!v) return;
    addVariantToCart(product, v);
};

const addVariantToCart = (product: any, variant: any) => {
    if (Number(variant.stock || 0) <= 0) {
        alert('Out of stock');
        return;
    }
    const tab = activeTab.value;
    if (!tab) return;
    const pvid = variant.id;
    const existing = tab.cart.find((i: any) => i.product_variant_id === pvid);
    if (existing) {
        existing.quantity++;
    } else {
        const vlist = product.product_variants ?? product.productVariants ?? [];
        tab.cart.push({
            product_variant_id: pvid,
            product_id: product.id,
            name: product.name + (vlist.length > 1 ? ` (${variant.label || variant.display_name || ''})` : ''),
            sku: variant.sku,
            price: variant.final_price ?? variant.price,
            quantity: 1,
            image: product.primary_image_url,
        });
    }
    showVariantModal.value = false;
    saveAllTabsToStorage();
};

const removeFromCart = (i: number) => {
    activeTab.value?.cart.splice(i, 1);
    saveAllTabsToStorage();
};

const updateQuantity = (i: number, d: number) => {
    const item = activeTab.value?.cart[i];
    if (!item) return;
    item.quantity += d;
    if (item.quantity <= 0) removeFromCart(i);
    else saveAllTabsToStorage();
};

const selectCustomer = (c: any) => {
    if (activeTab.value) {
        activeTab.value.selectedCustomer = c;
        customerPhone.value = c.user?.phone || '';
        showCustomerModal.value = false;
        fetchUnallocatedPayments();
        saveAllTabsToStorage();
    }
};

const appendZeros = (z: string) => {
    const cur = amountPaid.value.toString();
    amountPaid.value = cur === '0' ? Number(z) : Number(cur + z);
};

const processCheckout = () => {
    if (cart.value.length === 0) return;
    if (!selectedCustomer.value) {
        alert('Please select a customer');
        return;
    }
    showPaymentModal.value = true;
    amountPaid.value = remainingToPay.value;
};

const submitOrder = async () => {
    if (!session.value || !selectedCustomer.value) return;
    processingOrder.value = true;
    try {
        const res = await posApi.createSale({
            pos_session_id: session.value.id,
            customer_id: selectedCustomer.value.id,
            items: cart.value.map((i: any) => ({ product_variant_id: i.product_variant_id, quantity: i.quantity })),
            payment_method: paymentMethod.value,
            amount_paid: amountPaid.value,
            allocated_payment_ids: selectedUnallocatedIds.value.length ? selectedUnallocatedIds.value : undefined,
        });
        lastOrder.value = res;

        if (activeTab.value) {
            activeTab.value.cart = [];
            activeTab.value.selectedCustomer = null;
            activeTab.value.selectedUnallocatedIds = [];
            activeTab.value.unallocatedPayments = [];
        }
        saveAllTabsToStorage();

        showPaymentModal.value = false;
        showReceiptModal.value = true;
        fetchProducts();
    } catch {
        alert('Failed to submit order');
    } finally {
        processingOrder.value = false;
    }
};

const initiateStkPush = async () => {
    if (!customerPhone.value) {
        alert('Please enter customer phone');
        return;
    }
    processingOrder.value = true;
    try {
        const { data } = await api.post(posApi.getPaymentsInitiateUrl(), {
            payable_type: 'pos_session',
            payable_id: session.value.id,
            amount: remainingToPay.value,
            phone: customerPhone.value,
            provider: 'mpesa',
            method: 'stk_push',
        });
        if (data?.payment) {
            mpesaRequest.value = data.payment;
            startPaymentPolling();
        }
    } catch (e) {
        alert((e as any)?.response?.data?.message || 'Failed');
        processingOrder.value = false;
    }
};

const startPaymentPolling = () => {
    if (pollingInterval.value) clearInterval(pollingInterval.value);
    pollingInterval.value = setInterval(async () => {
        if (!mpesaRequest.value) return;
        try {
            const { data } = await api.get(posApi.getPaymentStatusUrl(mpesaRequest.value.reference));
            if (data?.verification?.success) {
                stopPaymentPolling();
                amountPaid.value = remainingToPay.value;
                submitOrder();
            } else if (data?.verification?.status === 'failed' || data?.verification?.status === 'cancelled') {
                stopPaymentPolling();
                alert('Payment failed');
                processingOrder.value = false;
            }
        } catch {}
    }, 3000);
};

const stopPaymentPolling = () => {
    if (pollingInterval.value) {
        clearInterval(pollingInterval.value);
        pollingInterval.value = null;
    }
};

const printReceipt = () => {
    const el = document.getElementById('receipt-content');
    if (!el) return;
    const w = window.open('', '_blank', 'width=800,height=600');
    if (w) {
        w.document.write('<html><head><title>Receipt</title>');
        w.document.write('<style>body{font:12px "Courier New";width:80mm;padding:10px;margin:0}</style></head><body>');
        w.document.write(el.innerHTML);
        w.document.write('</body></html>');
        w.document.close();
        setTimeout(() => { w.focus(); w.print(); w.close(); }, 250);
    }
};

const closeSession = async () => {
    if (!confirm('Close this session?')) return;
    const bal = prompt('Enter closing balance:', '0');
    if (bal === null) return;
    try {
        await posApi.closeSession(session.value.id, Number(bal), 'Closed from terminal');
        clearAllTabsFromStorage();
        clearSettingsCache();
        await posApi.logout();
        router.replace({ name: 'login' });
    } catch {
        alert('Failed to close session');
    }
};

const lockTerminal = async () => {
    stopPolling();
    await posApi.logout();
    router.replace({ name: 'login' });
};

const handleVoidSale = () => {
    if (cart.value.length === 0 && !selectedCustomer.value) return;
    if (settings.value?.require_admin_void) {
        showVoidModal.value = true;
        adminPin.value = '';
        voidError.value = '';
    } else {
        const reason = prompt('Reason for void (optional):');
        if (reason !== null) voidSale(reason);
    }
};

const voidSale = async (reason = '') => {
    try {
        await posApi.storeVoidedSale({
            pos_session_id: session.value.id,
            customer_id: selectedCustomer.value?.id,
            cart_data: cart.value,
            total_amount: cartTotal(),
            reason,
        });
        if (activeTab.value) {
            activeTab.value.cart = [];
            activeTab.value.selectedCustomer = null;
        }
        saveAllTabsToStorage();
        showVoidModal.value = false;
    } catch {
        alert('Failed to save void');
        if (activeTab.value) {
            activeTab.value.cart = [];
            activeTab.value.selectedCustomer = null;
        }
        saveAllTabsToStorage();
        showVoidModal.value = false;
    }
};

const fetchVoidedSales = async () => {
    loadingVoidedSales.value = true;
    try {
        const data = await posApi.getVoidedSales(session.value.id);
        voidedSales.value = Array.isArray(data) ? data : [];
    } catch {}
    finally { loadingVoidedSales.value = false; }
};

const openVoidedSales = () => {
    fetchVoidedSales();
    showVoidedSalesModal.value = true;
};

const recallSale = async (sale: any) => {
    if (cart.value.length > 0 && !confirm('Clear cart and recall?')) return;
    try {
        await posApi.recallVoidedSale(sale.id);
        if (activeTab.value) {
            activeTab.value.cart = sale.cart_data ?? [];
            if (sale.customer) activeTab.value.selectedCustomer = sale.customer;
        }
        saveAllTabsToStorage();
        showVoidedSalesModal.value = false;
    } catch {
        alert('Failed to recall');
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
        const res = await posApi.verifyAdminPin(adminPin.value);
        if (res.success) {
            const reason = prompt('Reason for void (optional):');
            voidSale(reason || '');
        }
    } catch (e) {
        voidError.value = (e as any)?.response?.data?.message || 'Invalid Admin PIN.';
        adminPin.value = '';
    } finally {
        isVerifyingAdmin.value = false;
    }
};

const appendAdminPin = (d: number) => {
    if (adminPin.value.length < 4) adminPin.value += String(d);
    if (adminPin.value.length === 4) verifyAdminPin();
};

const goBack = () => router.push({ name: 'registers' });

// Watch for settings cache updates from polling
watch(cachedSettings, (newSettings) => {
    if (newSettings) {
        settings.value = newSettings;
    }
});

watch([search, selectedCategoryId, selectedBrandId], () => fetchProducts());
watch(customerSearch, () => fetchCustomers());
watch(activeTabId, () => saveAllTabsToStorage());
watch(tabs, () => saveAllTabsToStorage(), { deep: true });

onMounted(async () => {
    await fetchSession();
    if (session.value) {
        loadTabsFromStorage();
        fetchProducts();
        fetchCategories();
        fetchCustomers();
    }
});

onUnmounted(() => {
    stopPaymentPolling();
    stopPolling();
});
</script>

<template>
    <div v-if="loadingSession" class="flex h-screen items-center justify-center">Loading terminal...</div>

    <div v-else-if="session" class="flex h-screen flex-col overflow-hidden bg-gray-100">
        <header class="flex items-center justify-between border-b bg-white px-4 py-3 shadow-sm">
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="icon" @click="goBack">
                    <ChevronLeft class="h-5 w-5" />
                </Button>
                <h1 class="text-lg font-bold">POS: {{ session.register?.name }}</h1>
                <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">
                    Open ({{ session.user?.name }})
                </span>
            </div>
            <div class="flex items-center gap-4">
                <div class="relative w-64">
                    <Search class="absolute top-2.5 left-2.5 h-4 w-4 text-muted-foreground" />
                    <Input v-model="search" placeholder="Search products..." class="pl-9" />
                </div>
                <Button variant="destructive" size="sm" @click="handleVoidSale" :disabled="cart.length === 0 && !selectedCustomer">
                    <Trash2 class="mr-2 h-4 w-4" /> Void Sale
                </Button>
                <Button variant="outline" size="sm" @click="openVoidedSales" class="border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100">
                    <History class="mr-2 h-4 w-4" /> Voided Sales
                </Button>
                <Button variant="outline" size="sm" @click="showUnallocatedModal = true" class="border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100">
                    <Wallet class="mr-2 h-4 w-4" /> Unallocated
                </Button>
                <Button variant="outline" size="sm" @click="lockTerminal" class="mr-2">
                    <Lock class="mr-2 h-4 w-4" /> Lock
                </Button>
                <Button variant="outline" size="sm" @click="closeSession">
                    <LogOut class="mr-2 h-4 w-4" /> Close Session
                </Button>
            </div>
        </header>

        <div class="flex flex-grow overflow-hidden">
            <!-- Products panel -->
            <div class="flex w-2/3 flex-col border-r bg-white">
                <div class="space-y-2 border-b p-2">
                    <div class="scrollbar-hide flex gap-2 overflow-x-auto whitespace-nowrap pb-1">
                        <Button :variant="selectedCategoryId === null ? 'default' : 'outline'" size="sm" @click="selectedCategoryId = null">All</Button>
                        <Button v-for="c in categories" :key="c.id" :variant="selectedCategoryId === c.id ? 'default' : 'outline'" size="sm" @click="selectedCategoryId = c.id">
                            {{ c.name }}
                        </Button>
                    </div>
                    <div v-if="brands.length" class="scrollbar-hide flex gap-2 overflow-x-auto whitespace-nowrap pb-1">
                        <Button :variant="selectedBrandId === null ? 'default' : 'outline'" size="sm" class="text-xs h-7" @click="selectedBrandId = null">All Brands</Button>
                        <Button v-for="b in brands" :key="b.id" :variant="selectedBrandId === b.id ? 'default' : 'outline'" size="sm" class="text-xs h-7" @click="selectedBrandId = b.id">
                            {{ b.name }}
                        </Button>
                    </div>
                </div>
                <div class="flex-grow overflow-y-auto p-4 grid grid-cols-3 gap-2 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                    <Card
                        v-for="p in products"
                        :key="p.id"
                        class="flex h-fit cursor-pointer flex-col overflow-hidden transition-all hover:ring-2 hover:ring-primary gap-1 p-1"
                        @click="addToCart(p)"
                    >
                        <div v-if="settings.show_product_images" class="relative aspect-square shrink-0 bg-gray-50">
                            <img v-if="p.primary_image_url" :src="p.primary_image_url" class="h-full w-full object-cover" alt="" />
                            <div v-else class="flex h-full w-full items-center justify-center text-gray-300"><Search class="h-8 w-8" /></div>
                            <div v-if="(p.product_variants?.[0] ?? p.productVariants?.[0])?.stock <= 0" class="absolute inset-0 flex items-center justify-center bg-black/40 text-[10px] font-bold text-white uppercase">Out of Stock</div>
                        </div>
                        <CardContent class="p-1">
                            <p class="truncate text-[11px] font-medium">{{ p.name }}</p>
                            <div class="mt-0.5 flex items-center justify-between">
                                <p class="truncate text-muted-foreground text-[9px]">{{ p.category?.name }}</p>
                                <span class="rounded px-0.5 text-[8px] font-bold flex-shrink-0" :class="((p.product_variants?.[0] ?? p.productVariants?.[0])?.stock > 5) ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">
                                    Qty: {{ (p.product_variants?.[0] ?? p.productVariants?.[0])?.stock ?? 0 }}
                                </span>
                            </div>
                            <p class="mt-0.5 font-bold text-primary text-[11px]">
                                {{ settings.currency_symbol || 'KES' }} {{ Number((p.product_variants?.[0] ?? p.productVariants?.[0])?.final_price ?? (p.product_variants?.[0] ?? p.productVariants?.[0])?.price ?? 0).toLocaleString() }}
                            </p>
                        </CardContent>
                    </Card>
                    <div v-if="loadingProducts" class="col-span-full py-20 text-center text-muted-foreground">Loading...</div>
                    <div v-if="!loadingProducts && products.length === 0" class="col-span-full py-20 text-center text-muted-foreground">
                        {{ !search && !selectedCategoryId && !selectedBrandId ? 'Search or select category/brand' : 'No products found' }}
                    </div>
                </div>
            </div>

            <!-- Cart panel (with tabs) -->
            <div class="flex w-1/3 flex-col bg-white">
                <!-- Tab bar -->
                <div v-if="maxTabs > 1" class="flex items-center border-b bg-gray-50 px-2">
                    <div class="scrollbar-hide flex flex-1 gap-1 overflow-x-auto py-1.5">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            class="group relative flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-medium transition-colors whitespace-nowrap"
                            :class="activeTabId === tab.id
                                ? 'bg-white text-primary shadow-sm border'
                                : 'text-muted-foreground hover:bg-gray-100 hover:text-foreground'"
                            @click="switchTab(tab.id)"
                        >
                            <span>{{ tab.label }}</span>
                            <span v-if="tab.cart.length > 0" class="inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-primary/10 px-1 text-[10px] font-bold text-primary">
                                {{ tab.cart.length }}
                            </span>
                            <button
                                v-if="tabs.length > 1"
                                class="ml-1 rounded p-0.5 opacity-0 hover:bg-red-100 hover:text-red-600 group-hover:opacity-100"
                                @click.stop="closeTab(tab.id)"
                            >
                                <X class="h-3 w-3" />
                            </button>
                        </button>
                    </div>
                    <Button
                        v-if="tabs.length < maxTabs"
                        variant="ghost"
                        size="icon"
                        class="ml-1 h-7 w-7 shrink-0"
                        @click="addTab"
                    >
                        <Plus class="h-3.5 w-3.5" />
                    </Button>
                </div>

                <!-- Customer -->
                <div class="border-b p-4">
                    <div v-if="selectedCustomer" class="flex items-center justify-between rounded-lg border border-blue-100 bg-blue-50 p-3">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-200 font-bold text-blue-700">{{ selectedCustomer.user?.name?.charAt(0) }}</div>
                            <div>
                                <p class="text-sm font-bold">{{ selectedCustomer.user?.name }}</p>
                                <p class="text-xs text-blue-600">{{ selectedCustomer.user?.phone || 'No phone' }}</p>
                            </div>
                        </div>
                        <Button variant="ghost" size="icon" @click="selectedCustomer = null"><Trash2 class="h-4 w-4 text-red-500" /></Button>
                    </div>
                    <Button v-else variant="outline" class="w-full justify-start gap-2" @click="showCustomerModal = true">
                        <UserPlus class="h-4 w-4" /> Select Customer
                    </Button>
                </div>

                <!-- Cart items -->
                <div class="flex-grow overflow-y-auto">
                    <div v-if="cart.length === 0" class="flex h-full flex-col items-center justify-center p-10 text-center text-muted-foreground">
                        <Search class="mb-2 h-12 w-12 opacity-20" /><p>Your cart is empty</p>
                    </div>
                    <div v-else class="divide-y">
                        <div v-for="(item, i) in cart" :key="i" class="flex items-center gap-3 p-4">
                            <img v-if="item.image && settings.show_product_images" :src="item.image" class="h-12 w-12 rounded bg-gray-50 object-cover" alt="" />
                            <div class="grow overflow-hidden">
                                <p class="truncate text-sm font-medium">{{ item.name }}</p>
                                <p class="text-xs text-muted-foreground">{{ settings.currency_symbol || 'KES' }} {{ Number(item.price).toLocaleString() }}</p>
                            </div>
                            <div class="flex items-center gap-2 rounded-md border p-1">
                                <Button variant="ghost" size="icon" class="h-6 w-6" @click="updateQuantity(i, -1)"><Minus class="h-3 w-3" /></Button>
                                <span class="w-6 text-center text-xs font-bold">{{ item.quantity }}</span>
                                <Button variant="ghost" size="icon" class="h-6 w-6" @click="updateQuantity(i, 1)"><Plus class="h-3 w-3" /></Button>
                            </div>
                            <div class="min-w-[80px] text-right"><p class="text-sm font-bold">{{ settings.currency_symbol || 'KES' }} {{ (item.price * item.quantity).toLocaleString() }}</p></div>
                        </div>
                    </div>
                </div>

                <!-- Totals -->
                <div class="space-y-4 border-t bg-gray-50 p-4">
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm"><span class="text-muted-foreground">Subtotal</span><span>{{ (cartTotal() - vatAmount).toLocaleString() }}</span></div>
                        <div v-if="settings.vat_enabled" class="flex justify-between text-sm"><span class="text-muted-foreground">Tax ({{ settings.vat_percentage }}%)</span><span>{{ vatAmount.toLocaleString() }}</span></div>
                        <Separator />
                        <div class="flex justify-between text-xl font-bold"><span>Total</span><span class="text-primary">{{ settings.currency_symbol || 'KES' }} {{ cartTotal().toLocaleString() }}</span></div>
                    </div>
                    <div class="flex gap-2">
                        <Button variant="outline" class="h-14 flex-1" @click="handleVoidSale" :disabled="cart.length === 0 && !selectedCustomer">Void</Button>
                        <Button class="h-14 flex-[3] text-lg font-bold" @click="processCheckout" :disabled="cart.length === 0">Pay {{ settings.currency_symbol || 'KES' }} {{ cartTotal().toLocaleString() }}</Button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Void Modal -->
        <div v-if="showVoidModal" class="fixed inset-0 z-[70] flex items-center justify-center bg-black/50 p-4">
            <Card class="w-full max-w-sm bg-white">
                <CardHeader class="text-center"><CardTitle>Void Authorization</CardTitle><CardDescription>Enter admin PIN</CardDescription></CardHeader>
                <CardContent>
                    <div class="flex flex-col items-center gap-6">
                        <div class="flex gap-4">
                            <div v-for="j in 4" :key="j" class="h-4 w-4 rounded-full border-2 border-red-500" :class="{ 'bg-red-500': adminPin.length >= j }"></div>
                        </div>
                        <p v-if="voidError" class="text-sm font-medium text-red-600">{{ voidError }}</p>
                        <div class="grid w-full grid-cols-3 gap-4">
                            <Button v-for="n in 9" :key="n" variant="outline" class="h-16 text-xl font-bold" @click="appendAdminPin(n)">{{ n }}</Button>
                            <Button variant="ghost" class="h-16 text-red-600" @click="adminPin = ''">Clear</Button>
                            <Button variant="outline" class="h-16 text-xl font-bold" @click="appendAdminPin(0)">0</Button>
                            <Button variant="ghost" class="h-16" @click="showVoidModal = false">Cancel</Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Receipt Modal -->
        <div v-if="showReceiptModal && lastOrder" class="fixed inset-0 z-60 flex items-center justify-center bg-black/50 p-4">
            <Card class="flex w-full max-w-sm flex-col bg-white">
                <CardHeader class="flex flex-row items-center justify-between border-b py-3"><CardTitle>Sale Completed</CardTitle><Button variant="ghost" size="icon" @click="showReceiptModal = false">×</Button></CardHeader>
                <CardContent class="p-0">
                    <div id="receipt-content" class="mx-auto bg-white p-6 text-black" style="width:80mm;font:12px 'Courier New'">
                        <div class="mb-4 text-center">
                            <h2 class="text-lg font-bold">{{ lastOrder.settings?.business_name }}</h2>
                            <p v-if="lastOrder.settings?.business_address">{{ lastOrder.settings.business_address }}</p>
                            <p v-if="lastOrder.settings?.business_phone">Tel: {{ lastOrder.settings.business_phone }}</p>
                        </div>
                        <div class="mb-2 border-b">
                            <p>Receipt: {{ lastOrder.order?.order_code }}</p>
                            <p>Date: {{ new Date(lastOrder.order?.created_at).toLocaleString() }}</p>
                            <p>Cashier: {{ session.user?.name }}</p>
                            <p>Customer: {{ lastOrder.order?.customer?.first_name }} {{ lastOrder.order?.customer?.last_name }}</p>
                        </div>
                        <table class="mb-2 w-full">
                            <thead><tr class="border-b"><th class="py-1 text-left">Item</th><th class="py-1 text-right">Qty</th><th class="py-1 text-right">Price</th><th class="py-1 text-right">Total</th></tr></thead>
                            <tbody>
                                <tr v-for="item in lastOrder.order?.order_items" :key="item.id">
                                    <td class="py-1">{{ item.product_snapshot?.name }}</td>
                                    <td class="py-1 text-right">{{ item.quantity }}</td>
                                    <td class="py-1 text-right">{{ Number(item.unit_price).toLocaleString() }}</td>
                                    <td class="py-1 text-right font-bold">{{ Number(item.total_price).toLocaleString() }}</td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="mb-4 space-y-1">
                            <div class="flex justify-between"><span>Subtotal:</span><span>{{ lastOrder.order?.currency }} {{ Number(lastOrder.order?.subtotal).toLocaleString() }}</span></div>
                            <div v-if="Number(lastOrder.order?.tax_amount) > 0" class="flex justify-between"><span>VAT:</span><span>{{ lastOrder.order?.currency }} {{ Number(lastOrder.order?.tax_amount).toLocaleString() }}</span></div>
                            <div class="flex justify-between font-bold"><span>TOTAL:</span><span>{{ lastOrder.order?.currency }} {{ Number(lastOrder.order?.total_amount).toLocaleString() }}</span></div>
                        </div>
                    </div>
                </CardContent>
                <CardFooter class="grid grid-cols-2 gap-3 border-t p-4">
                    <Button variant="outline" @click="showReceiptModal = false">Close</Button>
                    <Button @click="printReceipt">Print</Button>
                </CardFooter>
            </Card>
        </div>

        <!-- Customer Modal -->
        <div v-if="showCustomerModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <Card class="flex max-h-[80vh] w-full max-w-2xl flex-col bg-white">
                <header class="flex items-center justify-between border-b p-4"><h2 class="text-lg font-bold">Select Customer</h2><Button variant="ghost" size="icon" @click="showCustomerModal = false">×</Button></header>
                <div class="p-4">
                    <div class="relative mb-4"><Search class="absolute top-2.5 left-2.5 h-4 w-4 text-muted-foreground" /><Input v-model="customerSearch" placeholder="Search by name, phone or email..." class="pl-9" /></div>
                    <div class="max-h-[50vh] divide-y overflow-y-auto rounded-md border">
                        <div v-for="c in customers" :key="c.id" class="flex cursor-pointer items-center justify-between p-3 hover:bg-gray-50" @click="selectCustomer(c)">
                            <div class="flex items-center gap-3"><UsersIcon class="h-5 w-5 text-gray-400" /><div><p class="font-medium">{{ c.user?.name }}</p><p class="text-xs text-muted-foreground">{{ c.user?.phone }} | {{ c.user?.email }}</p></div></div>
                            <Button variant="outline" size="sm">Select</Button>
                        </div>
                        <div v-if="customers.length === 0" class="p-10 text-center text-muted-foreground">No customers found</div>
                    </div>
                </div>
            </Card>
        </div>

        <!-- Variant Modal -->
        <div v-if="showVariantModal" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 p-4">
            <Card class="w-full max-w-lg bg-white">
                <header class="flex items-center justify-between border-b p-4"><h2 class="text-lg font-bold">Select Variant: {{ selectedProduct?.name }}</h2><Button variant="ghost" size="icon" @click="showVariantModal = false">×</Button></header>
                <div class="grid max-h-[60vh] grid-cols-1 gap-3 overflow-y-auto p-4">
                    <div v-for="v in (selectedProduct?.product_variants ?? selectedProduct?.productVariants ?? [])" :key="v.id" class="flex cursor-pointer items-center justify-between rounded-xl border p-4 hover:border-primary" @click="addVariantToCart(selectedProduct, v)" :class="{ 'pointer-events-none opacity-50 grayscale': v.stock <= 0 }">
                        <div><p class="font-bold">{{ v.label || v.display_name || 'Standard' }}</p><p class="text-xs text-muted-foreground">SKU: {{ v.sku }} | Stock: {{ v.stock }}</p></div>
                        <p class="font-bold text-primary">{{ settings.currency_symbol || 'KES' }} {{ Number(v.final_price ?? v.price).toLocaleString() }}</p>
                    </div>
                </div>
            </Card>
        </div>

        <!-- Payment Modal -->
        <div v-if="showPaymentModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <Card class="w-full max-w-md bg-white">
                <header class="flex items-center justify-between border-b p-4"><h2 class="text-lg font-bold">Checkout</h2><Button variant="ghost" size="icon" @click="showPaymentModal = false">×</Button></header>
                <div class="space-y-6 p-6">
                    <div class="text-center"><p class="text-sm font-semibold text-muted-foreground uppercase">Total Due</p><h3 class="text-4xl font-black text-primary">{{ settings.currency_symbol || 'KES' }} {{ cartTotal().toLocaleString() }}</h3></div>
                    <div class="space-y-3"><Label>Payment Method</Label>
                        <div class="grid grid-cols-2 gap-3">
                            <div v-for="m in allowedPaymentMethods" :key="m.id" class="flex cursor-pointer flex-col items-center gap-2 rounded-xl border-2 p-4" :class="paymentMethod === m.id ? 'border-primary bg-primary/5' : 'border-gray-100'" @click="paymentMethod = m.id">
                                <QrCode v-if="m.show_qr" class="h-5 w-5" /><span class="font-bold uppercase">{{ m.name }}</span>
                            </div>
                        </div>
                    </div>
                    <div v-if="totalAllocated > 0" class="rounded-lg border border-blue-100 bg-blue-50 p-3 text-sm text-blue-800">
                        <div class="flex justify-between font-bold"><span>Allocated:</span><span>{{ totalAllocated.toLocaleString() }}</span></div>
                        <div class="flex justify-between mt-1"><span>Remaining:</span><span>{{ remainingToPay.toLocaleString() }}</span></div>
                    </div>
                    <div v-if="remainingToPay > 0" class="space-y-2"><Label>Amount Paid</Label><Input v-model="amountPaid" type="number" class="h-14 text-2xl font-bold" /></div>
                    <div v-if="paymentMethod === 'mpesa'" class="rounded-lg border border-green-100 bg-green-50 p-4 space-y-2">
                        <Label>M-Pesa Phone</Label><Input v-model="customerPhone" placeholder="254712345678" />
                        <Button class="w-full bg-green-600 text-white hover:bg-green-700" @click="initiateStkPush" :disabled="processingOrder">{{ processingOrder ? 'Waiting...' : 'Send STK Push' }}</Button>
                    </div>
                </div>
                <footer class="flex gap-3 border-t p-4">
                    <Button variant="outline" class="h-14 flex-1" @click="showPaymentModal = false">Cancel</Button>
                    <Button class="h-14 flex-[2] text-xl font-bold" @click="submitOrder" :disabled="processingOrder || (amountPaid < remainingToPay && remainingToPay > 0)">{{ processingOrder ? 'Processing...' : 'Complete Sale' }}</Button>
                </footer>
            </Card>
        </div>

        <!-- Unallocated Modal -->
        <div v-if="showUnallocatedModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <Card class="flex max-h-[90vh] w-full max-w-2xl flex-col bg-white">
                <CardHeader class="flex flex-row items-center justify-between"><div><CardTitle>Unallocated Payments</CardTitle><CardDescription v-if="selectedCustomer">Customer: {{ selectedCustomer.user?.name }}</CardDescription><CardDescription v-else>Select customer first</CardDescription></div><Button v-if="selectedCustomer" size="sm" @click="showRecordPaymentModal = true">Record Payment</Button></CardHeader>
                <CardContent class="grow overflow-y-auto">
                    <div v-if="!selectedCustomer" class="py-10 text-center text-muted-foreground">Select a customer first.</div>
                    <div v-else-if="loadingUnallocated" class="py-10 text-center">Loading...</div>
                    <div v-else-if="unallocatedPayments.length === 0" class="py-10 text-center text-muted-foreground">No unallocated payments.</div>
                    <div v-else class="space-y-2">
                        <div v-for="p in unallocatedPayments" :key="p.id" class="flex cursor-pointer items-center justify-between rounded-lg border p-3 hover:bg-gray-50" @click="selectedUnallocatedIds = selectedUnallocatedIds.includes(p.id) ? selectedUnallocatedIds.filter((x: number) => x !== p.id) : [...selectedUnallocatedIds, p.id]">
                            <div class="flex items-center gap-3">
                                <input type="checkbox" :checked="selectedUnallocatedIds.includes(p.id)" @click.stop />
                                <div><p class="font-medium">{{ Number((p.amount || 0) - (p.used_amount || 0)).toLocaleString() }}</p><p class="text-xs text-muted-foreground">{{ p.payment_method }} {{ p.created_at }}</p></div>
                            </div>
                        </div>
                    </div>
                </CardContent>
                <CardFooter class="justify-between border-t p-4"><Button variant="outline" @click="showUnallocatedModal = false">Close</Button><div class="flex items-center gap-4"><p class="text-sm font-medium">Allocated: <span class="font-bold text-primary">{{ totalAllocated.toLocaleString() }}</span></p><Button @click="showUnallocatedModal = false">Apply</Button></div></CardFooter>
            </Card>
        </div>

        <!-- Record Payment Modal -->
        <div v-if="showRecordPaymentModal" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 p-4">
            <Card class="w-full max-w-md bg-white">
                <CardHeader><CardTitle>Record Payment</CardTitle><CardDescription>Store for future use</CardDescription></CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid gap-2"><Label>Amount</Label><Input v-model="recordPaymentForm.amount" type="number" required /></div>
                    <div class="grid gap-2"><Label>Method</Label><Select v-model="recordPaymentForm.payment_method"><SelectTrigger><SelectValue /></SelectTrigger><SelectContent><SelectItem v-for="m in allowedPaymentMethods" :key="m.id" :value="m.id">{{ m.name }}</SelectItem></SelectContent></Select></div>
                    <div class="grid gap-2"><Label>Notes</Label><Textarea v-model="recordPaymentForm.notes" /></div>
                </CardContent>
                <CardFooter class="justify-end gap-3 border-t p-4"><Button variant="outline" @click="showRecordPaymentModal = false">Cancel</Button><Button :disabled="recordingPayment || recordPaymentForm.amount <= 0" @click="recordUnallocatedPayment">{{ recordingPayment ? 'Saving...' : 'Record' }}</Button></CardFooter>
            </Card>
        </div>

        <!-- Voided Sales Modal -->
        <div v-if="showVoidedSalesModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <Card class="flex max-h-[90vh] w-full max-w-4xl flex-col bg-white">
                <CardHeader class="flex flex-row items-center justify-between"><div><CardTitle>Voided Sales</CardTitle><CardDescription>Recall a voided sale</CardDescription></div><Button variant="ghost" size="icon" @click="showVoidedSalesModal = false">×</Button></CardHeader>
                <CardContent class="grow overflow-y-auto">
                    <div v-if="loadingVoidedSales" class="py-20 text-center">Loading...</div>
                    <div v-else-if="voidedSales.length === 0" class="py-20 text-center text-muted-foreground">No voided sales.</div>
                    <div v-else class="space-y-2">
                        <div v-for="s in voidedSales" :key="s.id" class="flex items-center justify-between rounded-lg border p-3">
                            <div><p class="font-medium">{{ s.customer?.user?.name || 'Walk-in' }}</p><p class="text-xs text-muted-foreground">{{ (s.cart_data ?? []).length }} items · {{ Number(s.total_amount).toLocaleString() }}</p></div>
                            <Button size="sm" variant="outline" @click="recallSale(s)">Recall</Button>
                        </div>
                    </div>
                </CardContent>
                <div class="border-t bg-gray-50 p-4 text-right"><Button variant="outline" @click="showVoidedSalesModal = false">Close</Button></div>
            </Card>
        </div>
    </div>
</template>

<style scoped>
.scrollbar-hide::-webkit-scrollbar { display: none; }
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
</style>
