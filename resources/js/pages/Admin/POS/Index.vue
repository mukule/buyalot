<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ref } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';

const props = defineProps<{
    registers: any[];
    activeSession: any;
    canManageSettings?: boolean;
}>();

console.log('POS Registers:', props.registers);

const breadcrumbs = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'POS', href: '/admin/pos' },
];

const openSessionForm = useForm({
    pos_register_id: '',
    opening_balance: 0,
});

const showOpenModal = ref(false);
const selectedRegister = ref<any>(null);

const showPinModal = ref(false);
const pin = ref('');
const pinError = ref('');
const isVerifying = ref(false);

const showUpdatePinModal = ref(false);
const newPin = ref('');
const updatePinError = ref('');
const isUpdatingPin = ref(false);

function openUpdatePinModal() {
    showUpdatePinModal.value = true;
    newPin.value = '';
    updatePinError.value = '';
}

async function handleUpdatePin() {
    if (newPin.value.length !== 4 || !/^\d+$/.test(newPin.value)) {
        updatePinError.value = 'PIN must be 4 digits.';
        return;
    }

    isUpdatingPin.value = true;
    updatePinError.value = '';

    try {
        await router.post(route('admin.pos.update-pin'), { pin: newPin.value }, {
            onSuccess: () => {
                showUpdatePinModal.value = false;
                newPin.value = '';
            },
            onError: (errors) => {
                updatePinError.value = errors.pin || 'Failed to update PIN.';
            }
        });
    } catch (err) {
        console.error(err);
        updatePinError.value = 'An error occurred.';
    } finally {
        isUpdatingPin.value = false;
    }
}

function appendNewPin(digit: number) {
    if (newPin.value.length < 4) {
        newPin.value += digit.toString();
    }
}

function clearNewPin() {
    newPin.value = '';
}

function openOpenModal(register: any) {
    selectedRegister.value = register;
    openSessionForm.pos_register_id = register.id;
    showOpenModal.value = true;
}

function openPinModal(register: any) {
    selectedRegister.value = register;
    showPinModal.value = true;
    pin.value = '';
    pinError.value = '';
}

async function verifyPin() {
    if (pin.value.length !== 4) {
        pinError.value = 'PIN must be 4 digits.';
        return;
    }

    isVerifying.value = true;
    pinError.value = '';

    try {
        const response = await axios.post(route('admin.pos.verify-pin'), { pin: pin.value });
        if (response.data.success) {
            router.get(route('admin.pos.show'));
        }
    } catch (error: any) {
        pinError.value = error.response?.data?.message || 'Invalid PIN.';
        pin.value = '';
    } finally {
        isVerifying.value = false;
    }
}

function appendPin(digit: number) {
    if (pin.value.length < 4) {
        pin.value += digit.toString();
    }
    if (pin.value.length === 4) {
        verifyPin();
    }
}

function clearPin() {
    pin.value = '';
}

function submitOpenSession() {
    openSessionForm.post(route('admin.pos.sessions.open'), {
        onSuccess: () => {
            showOpenModal.value = false;
            openSessionForm.reset();
        },
    });
}
</script>

<template>
    <Head title="POS Management" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">POS Registers</h1>
                <div class="flex gap-2">
                    <Button v-if="canManageSettings" @click="router.visit(route('admin.pos.settings.index'))" variant="outline">
                        POS Settings
                    </Button>
                    <Button @click="openUpdatePinModal" variant="outline">
                        Update POS PIN
                    </Button>
                    <div v-if="activeSession">
                        <Button as="a" :href="route('admin.pos.show')" variant="default">
                            Go to Terminal
                        </Button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <Card v-for="register in registers" :key="register.id" class="flex flex-col">
                    <CardHeader>
                        <CardTitle>{{ register.name }}</CardTitle>
                        <CardDescription>
                            {{ register.warehouse?.name || 'No Warehouse Assigned' }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="flex-grow">
                        <div v-if="register.active_session" class="space-y-2">
                            <p class="text-sm text-green-600 font-medium">Session Open</p>
                            <p class="text-xs text-muted-foreground">Opened by: {{ register.active_session.user?.name }}</p>
                        </div>
                        <div v-else>
                            <p class="text-sm text-muted-foreground">Status: Inactive</p>
                        </div>
                    </CardContent>
                    <CardFooter>
                        <Button
                            v-if="!register.active_session"
                            @click="openOpenModal(register)"
                            class="w-full"
                        >
                            Open Register
                        </Button>
                        <Button
                            v-else-if="activeSession && activeSession.pos_register_id === register.id"
                            @click="openPinModal(register)"
                            variant="secondary"
                            class="w-full"
                        >
                            Open Terminal
                        </Button>
                        <Button
                            v-else
                            disabled
                            variant="outline"
                            class="w-full"
                        >
                            In Use
                        </Button>
                    </CardFooter>
                </Card>
            </div>

            <!-- PIN Login Modal -->
            <div v-if="showPinModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
                <Card class="w-full max-w-sm bg-white overflow-hidden">
                    <CardHeader class="text-center">
                        <CardTitle>Terminal Login</CardTitle>
                        <CardDescription>Enter your 4-digit PIN to access {{ selectedRegister?.name }}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex flex-col items-center gap-6">
                            <!-- PIN Display -->
                            <div class="flex gap-4">
                                <div
                                    v-for="i in 4"
                                    :key="i"
                                    class="w-4 h-4 rounded-full border-2 border-primary"
                                    :class="{ 'bg-primary': pin.length >= i }"
                                ></div>
                            </div>

                            <p v-if="pinError" class="text-sm text-red-600 font-medium">{{ pinError }}</p>

                            <!-- Number Pad -->
                            <div class="grid grid-cols-3 gap-4 w-full">
                                <Button
                                    v-for="n in 9"
                                    :key="n"
                                    variant="outline"
                                    class="h-16 text-xl font-bold"
                                    @click="appendPin(n)"
                                >
                                    {{ n }}
                                </Button>
                                <Button variant="ghost" class="h-16 text-red-600" @click="clearPin">Clear</Button>
                                <Button variant="outline" class="h-16 text-xl font-bold" @click="appendPin(0)">0</Button>
                                <Button variant="ghost" class="h-16" @click="showPinModal = false">Cancel</Button>
                            </div>

                            <div v-if="isVerifying" class="text-sm text-muted-foreground animate-pulse">
                                Verifying PIN...
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Update PIN Modal -->
            <div v-if="showUpdatePinModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
                <Card class="w-full max-w-sm bg-white overflow-hidden">
                    <CardHeader class="text-center">
                        <CardTitle>Update POS PIN</CardTitle>
                        <CardDescription>Set a new 4-digit PIN for terminal access.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex flex-col items-center gap-6">
                            <div class="flex gap-4">
                                <div
                                    v-for="i in 4"
                                    :key="i"
                                    class="w-4 h-4 rounded-full border-2 border-primary"
                                    :class="{ 'bg-primary': newPin.length >= i }"
                                ></div>
                            </div>

                            <p v-if="updatePinError" class="text-sm text-red-600 font-medium">{{ updatePinError }}</p>

                            <div class="grid grid-cols-3 gap-4 w-full">
                                <Button
                                    v-for="n in 9"
                                    :key="n"
                                    variant="outline"
                                    class="h-16 text-xl font-bold"
                                    @click="appendNewPin(n)"
                                >
                                    {{ n }}
                                </Button>
                                <Button variant="ghost" class="h-16 text-red-600" @click="clearNewPin">Clear</Button>
                                <Button variant="outline" class="h-16 text-xl font-bold" @click="appendNewPin(0)">0</Button>
                                <Button variant="ghost" class="h-16" @click="showUpdatePinModal = false">Cancel</Button>
                            </div>

                            <Button
                                class="w-full h-12 text-lg font-bold"
                                :disabled="newPin.length !== 4 || isUpdatingPin"
                                @click="handleUpdatePin"
                            >
                                {{ isUpdatingPin ? 'Updating...' : 'Set New PIN' }}
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Open Session Modal (Simplified as a div for now since I don't have a Dialog component ready) -->
            <div v-if="showOpenModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
                <Card class="w-full max-w-md bg-white">
                    <CardHeader>
                        <CardTitle>Open Register: {{ selectedRegister?.name }}</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="space-y-2">
                            <Label for="opening_balance">Opening Balance</Label>
                            <Input
                                id="opening_balance"
                                v-model="openSessionForm.opening_balance"
                                type="number"
                                step="0.01"
                            />
                        </div>
                    </CardContent>
                    <CardFooter class="flex justify-end gap-2">
                        <Button variant="ghost" @click="showOpenModal = false">Cancel</Button>
                        <Button @click="submitOpenSession" :disabled="openSessionForm.processing">
                            {{ openSessionForm.processing ? 'Opening...' : 'Open Session' }}
                        </Button>
                    </CardFooter>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
