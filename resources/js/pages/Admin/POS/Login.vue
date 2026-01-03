<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle, Delete } from 'lucide-vue-next';

const form = useForm({
    email: '',
    pin: '',
});

const onEmailInput = (e: Event) => {
    const target = e.target as HTMLInputElement | null;
    if (target) {
        form.email = target.value.toLowerCase();
    }
};

const appendPin = (digit: number) => {
    if (form.pin.length < 4) {
        form.pin += digit.toString();
    }
};

const clearPin = () => {
    form.pin = '';
};

const deleteLastPin = () => {
    form.pin = form.pin.slice(0, -1);
};

const submit = () => {
    form.post(route('pos.login.submit'), {
        onFinish: () => form.reset('pin'),
    });
};
</script>

<template>
    <AuthBase title="POS Attendant Login" description="Enter your email and 4-digit PIN to access the terminal">
        <Head title="POS Login" />

        <div class="flex flex-col gap-6">
            <form @submit.prevent="submit" class="flex flex-col gap-4">
                <div class="grid gap-2">
                    <Label for="email">Email address</Label>
                    <Input
                        id="email"
                        type="email"
                        required
                        autofocus
                        v-model="form.email"
                        @input="onEmailInput"
                        placeholder="email@example.com"
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="pin">4-Digit PIN</Label>
                    <div class="flex justify-between gap-2 mb-2">
                        <div v-for="i in 4" :key="i"
                            class="w-12 h-14 border-2 rounded-lg flex items-center justify-center text-2xl font-bold"
                            :class="form.pin.length >= i ? 'border-primary bg-primary/10' : 'border-gray-200'"
                        >
                            {{ form.pin[i-1] ? '●' : '' }}
                        </div>
                    </div>
                    <InputError :message="form.errors.pin" />
                </div>

                <!-- Numeric Keypad -->
                <div class="grid grid-cols-3 gap-3">
                    <Button v-for="i in 9" :key="i" type="button" variant="outline" class="h-14 text-xl font-bold" @click="appendPin(i)">
                        {{ i }}
                    </Button>
                    <Button type="button" variant="outline" class="h-14 text-xl font-bold" @click="clearPin">C</Button>
                    <Button type="button" variant="outline" class="h-14 text-xl font-bold" @click="appendPin(0)">0</Button>
                    <Button type="button" variant="outline" class="h-14 text-xl font-bold" @click="deleteLastPin">
                        <Delete class="h-6 w-6" />
                    </Button>
                </div>

                <Button type="submit" class="mt-4 w-full" :disabled="form.processing || form.pin.length !== 4">
                    <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                    Access POS
                </Button>
            </form>

            <div class="text-center">
                <a :href="route('login')" class="text-sm text-primary hover:underline">
                    Back to standard login
                </a>
            </div>
        </div>
    </AuthBase>
</template>
