<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ref } from 'vue';
import { Plus, Settings, Monitor, Trash2, Edit, Store } from 'lucide-vue-next';

const props = defineProps<{
    settings: any;
    registers: any[];
    warehouses: any[];
    sellers?: any[];
    isSellerContext?: boolean;
    accountSettings?: any[];
}>();

const breadcrumbs = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'POS', href: '/admin/pos' },
    { title: 'Settings', href: '/admin/pos/settings' },
];

const globalSettingsForm = useForm({
    business_name: props.settings.business_name || '',
    business_address: props.settings.business_address || '',
    business_phone: props.settings.business_phone || '',
    business_email: props.settings.business_email || '',
    tax_number: props.settings.tax_number || '',
    vat_percentage: props.settings.vat_percentage || 0,
    vat_enabled: !!props.settings.vat_enabled,
    require_admin_void: !!props.settings.require_admin_void,
    show_product_images: props.settings.show_product_images !== undefined ? !!props.settings.show_product_images : true,
    max_tabs: props.settings.max_tabs || 1,
    product_display_design: props.settings.product_display_design || 'grid',
    currency_symbol: props.settings.currency_symbol || 'KES',
    receipt_header: props.settings.receipt_header || '',
    receipt_footer: props.settings.receipt_footer || '',
    invoice_prefix: props.settings.invoice_prefix || 'INV-',
    receipt_prefix: props.settings.receipt_prefix || 'RCPT-',
    payment_methods: props.settings.payment_methods || [
        { id: 'cash', name: 'Cash', enabled: true },
        { id: 'mpesa', name: 'M-Pesa', enabled: true }
    ],
});

const registerForm = useForm({
    id: null as number | null,
    name: '',
    seller_id: '' as string,
    warehouse_id: '',
    status: 'active',
    receipt_type: 'thermal',
    invoice_type: 'standard',
    auto_print_receipt: false,
});

const accountForm = useForm({
    id: null as number | null,
    seller_id: '' as string,
    max_tabs: 1,
    show_product_images: true,
    product_display_design: 'grid',
    require_admin_void: false,
});

const showRegisterModal = ref(false);
const isEditingRegister = ref(false);
const showAccountModal = ref(false);
const isEditingAccount = ref(false);

function openAddRegister() {
    isEditingRegister.value = false;
    registerForm.reset();
    registerForm.id = null;
    registerForm.seller_id = '';
    showRegisterModal.value = true;
}

function openEditRegister(register: any) {
    isEditingRegister.value = true;
    registerForm.id = register.id;
    registerForm.name = register.name;
    registerForm.seller_id = register.seller_id ? register.seller_id.toString() : '';
    registerForm.warehouse_id = register.warehouse_id ? register.warehouse_id.toString() : '';
    registerForm.status = register.status;
    registerForm.receipt_type = register.receipt_type;
    registerForm.invoice_type = register.invoice_type;
    registerForm.auto_print_receipt = !!register.auto_print_receipt;
    showRegisterModal.value = true;
}

function openAddAccount() {
    isEditingAccount.value = false;
    accountForm.reset();
    accountForm.id = null;
    accountForm.seller_id = '';
    accountForm.max_tabs = props.settings.max_tabs || 1;
    accountForm.show_product_images = !!props.settings.show_product_images;
    accountForm.product_display_design = props.settings.product_display_design || 'grid';
    accountForm.require_admin_void = !!props.settings.require_admin_void;
    showAccountModal.value = true;
}

function openEditAccount(acct: any) {
    isEditingAccount.value = true;
    accountForm.id = acct.id;
    accountForm.seller_id = acct.seller_id ? acct.seller_id.toString() : '';
    accountForm.max_tabs = acct.max_tabs || 1;
    accountForm.show_product_images = !!acct.show_product_images;
    accountForm.product_display_design = acct.product_display_design || 'grid';
    accountForm.require_admin_void = !!acct.require_admin_void;
    showAccountModal.value = true;
}

function saveGlobalSettings() {
    globalSettingsForm.post(route('admin.pos.settings.global.update'), {
        preserveScroll: true,
    });
}

function submitAccount() {
    if (isEditingAccount.value && accountForm.id) {
        accountForm.put(route('admin.pos.settings.account.update', accountForm.id), {
            onSuccess: () => (showAccountModal.value = false),
        });
    } else {
        accountForm.post(route('admin.pos.settings.account.store'), {
            onSuccess: () => (showAccountModal.value = false),
        });
    }
}

function deleteAccountSetting(id: number) {
    if (confirm('Remove account-specific settings? Global defaults will apply for this account.')) {
        router.delete(route('admin.pos.settings.account.destroy', id), {
            preserveScroll: true,
        });
    }
}

function submitRegister() {
    if (isEditingRegister.value) {
        registerForm.put(route('admin.pos.registers.update', registerForm.id), {
            onSuccess: () => (showRegisterModal.value = false),
        });
    } else {
        registerForm.post(route('admin.pos.registers.store'), {
            onSuccess: () => (showRegisterModal.value = false),
        });
    }
}

function deleteRegister(id: number) {
    if (confirm('Are you sure you want to delete this register?')) {
        registerForm.delete(route('admin.pos.registers.destroy', id));
    }
}

function addPaymentMethod() {
    globalSettingsForm.payment_methods.push({
        id: 'pm_' + Date.now(),
        name: '',
        enabled: true,
        show_qr: false,
        qr_value: ''
    });
}

function removePaymentMethod(index: number) {
    globalSettingsForm.payment_methods.splice(index, 1);
}

function sellerName(sellerId: number): string {
    const s = props.sellers?.find((s: any) => s.id === sellerId);
    return s?.company_legal_name || `Seller #${sellerId}`;
}
</script>

<template>
    <Head title="POS Settings" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 max-w-6xl mx-auto">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold">POS Configuration</h1>
                    <p class="text-muted-foreground">Manage global POS settings, per-account overrides, and terminal devices</p>
                </div>
            </div>

            <Tabs default-value="global" class="space-y-6">
                <TabsList>
                    <TabsTrigger value="global" class="gap-2">
                        <Settings class="h-4 w-4" />
                        Global Settings
                    </TabsTrigger>
                    <TabsTrigger value="accounts" class="gap-2">
                        <Store class="h-4 w-4" />
                        Account Settings
                    </TabsTrigger>
                    <TabsTrigger value="terminals" class="gap-2">
                        <Monitor class="h-4 w-4" />
                        Terminals (Registers)
                    </TabsTrigger>
                </TabsList>

                <!-- Global Settings -->
                <TabsContent value="global">
                    <form @submit.prevent="saveGlobalSettings">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Business Information</CardTitle>
                                    <CardDescription>Details that will appear on receipts and invoices</CardDescription>
                                </CardHeader>
                                <CardContent class="space-y-4">
                                    <div class="grid gap-2">
                                        <Label for="business_name">Business Name</Label>
                                        <Input id="business_name" v-model="globalSettingsForm.business_name" />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="business_address">Address</Label>
                                        <Textarea id="business_address" v-model="globalSettingsForm.business_address" rows="2" />
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="grid gap-2">
                                            <Label for="business_phone">Phone</Label>
                                            <Input id="business_phone" v-model="globalSettingsForm.business_phone" />
                                        </div>
                                        <div class="grid gap-2">
                                            <Label for="business_email">Email</Label>
                                            <Input id="business_email" type="email" v-model="globalSettingsForm.business_email" />
                                        </div>
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="tax_number">Tax Number (PIN/VAT)</Label>
                                        <Input id="tax_number" v-model="globalSettingsForm.tax_number" placeholder="e.g. P051234567Z" />
                                    </div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle>Tax, Currency & Terminal Defaults</CardTitle>
                                    <CardDescription>Configure VAT, payment formatting, and default terminal behavior</CardDescription>
                                </CardHeader>
                                <CardContent class="space-y-4">
                                    <div class="flex items-center justify-between p-3 border rounded-lg">
                                        <div class="space-y-0.5">
                                            <Label>Enable VAT</Label>
                                            <p class="text-xs text-muted-foreground">Calculate tax on all POS sales</p>
                                        </div>
                                        <Switch
                                            :checked="!!globalSettingsForm.vat_enabled"
                                            @update:checked="(val) => globalSettingsForm.vat_enabled = !!val"
                                        />
                                    </div>
                                    <div class="flex items-center justify-between p-3 border rounded-lg">
                                        <div class="space-y-0.5">
                                            <Label>Require Admin to Void</Label>
                                            <p class="text-xs text-muted-foreground">Admin PIN required to clear cart/void sale</p>
                                        </div>
                                        <Switch
                                            :checked="!!globalSettingsForm.require_admin_void"
                                            @update:checked="(val) => globalSettingsForm.require_admin_void = !!val"
                                        />
                                    </div>
                                    <div class="flex items-center justify-between p-3 border rounded-lg">
                                        <div class="space-y-0.5">
                                            <Label>Show Product Images</Label>
                                            <p class="text-xs text-muted-foreground">Display images in POS terminal product grid</p>
                                        </div>
                                        <Switch
                                            :checked="!!globalSettingsForm.show_product_images"
                                            @update:checked="(val) => globalSettingsForm.show_product_images = !!val"
                                        />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label>Max Selling Tabs</Label>
                                        <div class="flex items-center gap-3">
                                            <Input type="number" v-model="globalSettingsForm.max_tabs" min="1" max="20" class="w-24" />
                                            <span class="text-xs text-muted-foreground">Concurrent carts per terminal (default 1)</span>
                                        </div>
                                    </div>
                                    <div class="grid gap-2">
                                        <Label>Product Display Design</Label>
                                        <Select v-model="globalSettingsForm.product_display_design">
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select design" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="grid">Standard Grid</SelectItem>
                                                <SelectItem value="small_grid">Small Grid</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div class="grid gap-2" v-if="globalSettingsForm.vat_enabled">
                                        <Label for="vat_percentage">VAT Percentage (%)</Label>
                                        <Input id="vat_percentage" type="number" step="0.01" v-model="globalSettingsForm.vat_percentage" />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="currency_symbol">Currency Symbol</Label>
                                        <Input id="currency_symbol" v-model="globalSettingsForm.currency_symbol" />
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="grid gap-2">
                                            <Label for="invoice_prefix">Invoice Prefix</Label>
                                            <Input id="invoice_prefix" v-model="globalSettingsForm.invoice_prefix" />
                                        </div>
                                        <div class="grid gap-2">
                                            <Label for="receipt_prefix">Receipt Prefix</Label>
                                            <Input id="receipt_prefix" v-model="globalSettingsForm.receipt_prefix" />
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <Card class="md:col-span-2">
                                <CardHeader>
                                    <CardTitle>Receipt Templates</CardTitle>
                                    <CardDescription>Add headers and footers to your printed receipts</CardDescription>
                                </CardHeader>
                                <CardContent class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="grid gap-2">
                                        <Label for="receipt_header">Header Text</Label>
                                        <Textarea id="receipt_header" v-model="globalSettingsForm.receipt_header" rows="4" placeholder="Welcome message, slogans..." />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="receipt_footer">Footer Text</Label>
                                        <Textarea id="receipt_footer" v-model="globalSettingsForm.receipt_footer" rows="4" placeholder="Return policy, thank you message..." />
                                    </div>
                                </CardContent>
                                <CardFooter class="border-t px-6 py-4 justify-end">
                                    <Button :disabled="globalSettingsForm.processing">
                                        {{ globalSettingsForm.processing ? 'Saving...' : 'Save All Settings' }}
                                    </Button>
                                </CardFooter>
                            </Card>

                            <Card class="md:col-span-2">
                                <CardHeader class="flex flex-row items-center justify-between space-y-0">
                                    <div>
                                        <CardTitle>Allowed Payment Methods</CardTitle>
                                        <CardDescription>Configure payment options available at the POS terminal</CardDescription>
                                    </div>
                                    <Button type="button" variant="outline" size="sm" @click="addPaymentMethod" class="gap-2">
                                        <Plus class="h-4 w-4" />
                                        Add Method
                                    </Button>
                                </CardHeader>
                                <CardContent>
                                    <div class="space-y-4">
                                        <div v-for="(method, index) in globalSettingsForm.payment_methods" :key="method.id" class="flex flex-col gap-4 p-3 border rounded-lg">
                                            <div class="flex items-center gap-4">
                                                <div class="flex-grow grid grid-cols-2 gap-4">
                                                    <div class="grid gap-2">
                                                        <Label :for="'pm_name_' + index">Method Name</Label>
                                                        <Input :id="'pm_name_' + index" v-model="method.name" placeholder="e.g. Card, M-Pesa, Cash" />
                                                    </div>
                                                    <div class="flex items-center gap-4">
                                                        <div class="flex items-center gap-2">
                                                            <Switch
                                                                :checked="!!method.enabled"
                                                                @update:checked="(val) => method.enabled = !!val"
                                                            />
                                                            <Label>Enabled</Label>
                                                        </div>
                                                        <div class="flex items-center gap-2">
                                                            <Switch
                                                                :checked="!!method.show_qr"
                                                                @update:checked="(val) => method.show_qr = !!val"
                                                            />
                                                            <Label>Show QR</Label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <Button type="button" variant="ghost" size="icon" class="text-red-600" @click="removePaymentMethod(index)">
                                                    <Trash2 class="h-4 w-4" />
                                                </Button>
                                            </div>
                                            <div v-if="method.show_qr" class="grid grid-cols-1 gap-4 pt-2 border-t">
                                                <div class="grid gap-2">
                                                    <Label :for="'pm_qr_value_' + index">QR Code Value / Paybill / Instructions</Label>
                                                    <Input :id="'pm_qr_value_' + index" v-model="method.qr_value" placeholder="e.g. Paybill: 123456, Acc: 789 or URL" />
                                                </div>
                                            </div>
                                        </div>
                                        <div v-if="globalSettingsForm.payment_methods.length === 0" class="text-center py-4 text-muted-foreground">
                                            No payment methods configured. Standard Cash will be used.
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </form>
                </TabsContent>

                <!-- Account Settings -->
                <TabsContent value="accounts">
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0">
                            <div>
                                <CardTitle>Per-Account POS Settings</CardTitle>
                                <CardDescription>Override global defaults for specific seller accounts. Accounts without overrides inherit global settings.</CardDescription>
                            </div>
                            <Button @click="openAddAccount" size="sm" class="gap-2">
                                <Plus class="h-4 w-4" />
                                Add Account Override
                            </Button>
                        </CardHeader>
                        <CardContent>
                            <div class="border rounded-md">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b bg-muted/50">
                                            <th class="p-3 text-left font-medium">Account</th>
                                            <th class="p-3 text-center font-medium">Max Tabs</th>
                                            <th class="p-3 text-center font-medium">Product Images</th>
                                            <th class="p-3 text-center font-medium">Display</th>
                                            <th class="p-3 text-center font-medium">Admin Void</th>
                                            <th class="p-3 text-right font-medium">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="border-b bg-blue-50/50">
                                            <td class="p-3 font-medium text-blue-700">Global Default</td>
                                            <td class="p-3 text-center">{{ settings.max_tabs || 1 }}</td>
                                            <td class="p-3 text-center">
                                                <span :class="settings.show_product_images ? 'text-green-600' : 'text-gray-400'">
                                                    {{ settings.show_product_images ? 'Yes' : 'No' }}
                                                </span>
                                            </td>
                                            <td class="p-3 text-center capitalize">{{ settings.product_display_design || 'grid' }}</td>
                                            <td class="p-3 text-center">
                                                <span :class="settings.require_admin_void ? 'text-orange-600' : 'text-gray-400'">
                                                    {{ settings.require_admin_void ? 'Yes' : 'No' }}
                                                </span>
                                            </td>
                                            <td class="p-3 text-right text-xs text-muted-foreground">Edit in Global tab</td>
                                        </tr>
                                        <tr v-for="acct in accountSettings" :key="acct.id" class="border-b last:border-0">
                                            <td class="p-3 font-medium">{{ acct.seller?.company_legal_name || `Seller #${acct.seller_id}` }}</td>
                                            <td class="p-3 text-center font-bold">{{ acct.max_tabs }}</td>
                                            <td class="p-3 text-center">
                                                <span :class="acct.show_product_images ? 'text-green-600' : 'text-gray-400'">
                                                    {{ acct.show_product_images ? 'Yes' : 'No' }}
                                                </span>
                                            </td>
                                            <td class="p-3 text-center capitalize">{{ acct.product_display_design }}</td>
                                            <td class="p-3 text-center">
                                                <span :class="acct.require_admin_void ? 'text-orange-600' : 'text-gray-400'">
                                                    {{ acct.require_admin_void ? 'Yes' : 'No' }}
                                                </span>
                                            </td>
                                            <td class="p-3 text-right">
                                                <div class="flex justify-end gap-2">
                                                    <Button variant="ghost" size="icon" @click="openEditAccount(acct)">
                                                        <Edit class="h-4 w-4" />
                                                    </Button>
                                                    <Button variant="ghost" size="icon" class="text-red-600" @click="deleteAccountSetting(acct.id)">
                                                        <Trash2 class="h-4 w-4" />
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr v-if="!accountSettings?.length">
                                            <td colspan="6" class="p-8 text-center text-muted-foreground">
                                                No per-account overrides. All accounts use global defaults.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Terminals Management -->
                <TabsContent value="terminals">
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0">
                            <div>
                                <CardTitle>POS Terminals</CardTitle>
                                <CardDescription>List of all registers available for sales</CardDescription>
                            </div>
                            <Button @click="openAddRegister" size="sm" class="gap-2">
                                <Plus class="h-4 w-4" />
                                Add Terminal
                            </Button>
                        </CardHeader>
                        <CardContent>
                            <div class="border rounded-md">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b bg-muted/50">
                                            <th class="p-3 text-left font-medium">Name</th>
                                            <th v-if="!isSellerContext" class="p-3 text-left font-medium">Branch / Seller</th>
                                            <th class="p-3 text-left font-medium">Warehouse</th>
                                            <th class="p-3 text-left font-medium">Print Type</th>
                                            <th class="p-3 text-left font-medium">Status</th>
                                            <th class="p-3 text-right font-medium">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="reg in registers" :key="reg.id" class="border-b last:border-0">
                                            <td class="p-3 font-medium">{{ reg.name }}</td>
                                            <td v-if="!isSellerContext" class="p-3 text-muted-foreground">{{ reg.seller?.company_legal_name || '—' }}</td>
                                            <td class="p-3 text-muted-foreground">{{ reg.warehouse?.name }}</td>
                                            <td class="p-3">
                                                <span class="capitalize">{{ reg.receipt_type }}</span>
                                                <span v-if="reg.auto_print_receipt" class="ml-2 text-[10px] bg-blue-100 text-blue-700 px-1 rounded uppercase">Auto</span>
                                            </td>
                                            <td class="p-3">
                                                <span :class="['px-2 py-0.5 rounded-full text-xs font-medium', reg.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700']">
                                                    {{ reg.status }}
                                                </span>
                                            </td>
                                            <td class="p-3 text-right">
                                                <div class="flex justify-end gap-2">
                                                    <Button variant="ghost" size="icon" @click="openEditRegister(reg)">
                                                        <Edit class="h-4 w-4" />
                                                    </Button>
                                                    <Button variant="ghost" size="icon" class="text-red-600" @click="deleteRegister(reg.id)">
                                                        <Trash2 class="h-4 w-4" />
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr v-if="registers.length === 0">
                                            <td :colspan="isSellerContext ? 5 : 6" class="p-8 text-center text-muted-foreground">
                                                No registers found. Click "Add Terminal" to create one.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>
        </div>

        <!-- Add/Edit Register Modal -->
        <div v-if="showRegisterModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <Card class="w-full max-w-md bg-white">
                <CardHeader>
                    <CardTitle>{{ isEditingRegister ? 'Edit Terminal' : 'Add New Terminal' }}</CardTitle>
                    <CardDescription>Configure terminal settings and warehouse allocation</CardDescription>
                </CardHeader>
                <form @submit.prevent="submitRegister">
                    <CardContent class="space-y-4">
                        <div class="grid gap-2">
                            <Label for="reg_name">Terminal Name</Label>
                            <Input id="reg_name" v-model="registerForm.name" required />
                        </div>
                        <div v-if="!isSellerContext && (sellers?.length ?? 0) > 0" class="grid gap-2">
                            <Label for="reg_seller">Branch / Seller Account</Label>
                            <Select v-model="registerForm.seller_id" required>
                                <SelectTrigger>
                                    <SelectValue placeholder="Select branch/seller" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="s in sellers" :key="s.id" :value="s.id.toString()">
                                        {{ s.company_legal_name || `Seller #${s.id}` }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="grid gap-2">
                            <Label for="reg_warehouse">Warehouse Allocation</Label>
                            <Select v-model="registerForm.warehouse_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select a warehouse" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="wh in warehouses" :key="wh.id" :value="wh.id.toString()">
                                        {{ wh.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="grid gap-2">
                                <Label>Receipt Type</Label>
                                <Select v-model="registerForm.receipt_type">
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="thermal">Thermal (80mm)</SelectItem>
                                        <SelectItem value="standard">Standard (A4/A5)</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div class="grid gap-2">
                                <Label>Invoice Type</Label>
                                <Select v-model="registerForm.invoice_type">
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="standard">Standard</SelectItem>
                                        <SelectItem value="simplified">Simplified</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                        <div class="flex items-center justify-between p-3 border rounded-lg">
                            <div class="space-y-0.5">
                                <Label>Auto-print Receipt</Label>
                                <p class="text-xs text-muted-foreground">Print immediately after payment</p>
                            </div>
                            <Switch
                                :checked="!!registerForm.auto_print_receipt"
                                @update:checked="(val) => registerForm.auto_print_receipt = !!val"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label>Status</Label>
                            <Select v-model="registerForm.status">
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="active">Active</SelectItem>
                                    <SelectItem value="inactive">Inactive</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </CardContent>
                    <CardFooter class="justify-end gap-3 border-t px-6 py-4">
                        <Button type="button" variant="outline" @click="showRegisterModal = false">Cancel</Button>
                        <Button :disabled="registerForm.processing">
                            {{ registerForm.processing ? 'Saving...' : 'Save Terminal' }}
                        </Button>
                    </CardFooter>
                </form>
            </Card>
        </div>

        <!-- Add/Edit Account Settings Modal -->
        <div v-if="showAccountModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <Card class="w-full max-w-md bg-white">
                <CardHeader>
                    <CardTitle>{{ isEditingAccount ? 'Edit Account Settings' : 'Add Account Override' }}</CardTitle>
                    <CardDescription>Override global POS settings for a specific seller account</CardDescription>
                </CardHeader>
                <form @submit.prevent="submitAccount">
                    <CardContent class="space-y-4">
                        <div v-if="!isEditingAccount" class="grid gap-2">
                            <Label>Seller Account</Label>
                            <Select v-model="accountForm.seller_id" required>
                                <SelectTrigger>
                                    <SelectValue placeholder="Select seller account" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="s in sellers" :key="s.id" :value="s.id.toString()">
                                        {{ s.company_legal_name || `Seller #${s.id}` }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div v-else class="p-3 bg-muted rounded-lg">
                            <p class="text-sm font-medium">{{ sellerName(Number(accountForm.seller_id)) }}</p>
                        </div>

                        <div class="grid gap-2">
                            <Label>Max Selling Tabs</Label>
                            <div class="flex items-center gap-3">
                                <Input type="number" v-model="accountForm.max_tabs" min="1" max="20" class="w-24" />
                                <span class="text-xs text-muted-foreground">Concurrent carts per terminal</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between p-3 border rounded-lg">
                            <div class="space-y-0.5">
                                <Label>Show Product Images</Label>
                                <p class="text-xs text-muted-foreground">Display images in POS terminal product grid</p>
                            </div>
                            <Switch
                                :checked="!!accountForm.show_product_images"
                                @update:checked="(val) => accountForm.show_product_images = !!val"
                            />
                        </div>

                        <div class="grid gap-2">
                            <Label>Product Display Design</Label>
                            <Select v-model="accountForm.product_display_design">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select design" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="grid">Standard Grid</SelectItem>
                                    <SelectItem value="small_grid">Small Grid</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="flex items-center justify-between p-3 border rounded-lg">
                            <div class="space-y-0.5">
                                <Label>Require Admin to Void</Label>
                                <p class="text-xs text-muted-foreground">Admin PIN required to clear cart/void sale</p>
                            </div>
                            <Switch
                                :checked="!!accountForm.require_admin_void"
                                @update:checked="(val) => accountForm.require_admin_void = !!val"
                            />
                        </div>
                    </CardContent>
                    <CardFooter class="justify-end gap-3 border-t px-6 py-4">
                        <Button type="button" variant="outline" @click="showAccountModal = false">Cancel</Button>
                        <Button :disabled="accountForm.processing">
                            {{ accountForm.processing ? 'Saving...' : 'Save Account Settings' }}
                        </Button>
                    </CardFooter>
                </form>
            </Card>
        </div>
    </AppLayout>
</template>
