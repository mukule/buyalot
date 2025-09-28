<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import allCountries from 'country-calling-code';
import { Eye, EyeOff, LoaderCircle } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const form = useForm({
    name: '',
    email: '',
    country_code: '+254',
    phone: '',
    password: '',
    password_confirmation: '',
});

const countries = allCountries.map((c) => ({
    code: `+${Array.isArray(c.countryCodes) ? c.countryCodes[0] : ''}`,
    iso: c.isoCode2, // Add ISO shortform
    name: `${c.country}`,
    emoji: (c as any).emoji || '',
}));

const fullPhone = computed({
    get: () => form.phone,
    set: (val: string) => {
        form.phone = val.replace(/^\+?[0-9]+/, val); // keep only the number part
    },
});

const submit = () => {
    // Normalize phone number before submit
    const phone = form.phone.replace(/^0/, ''); // remove leading 0
    form.phone = `${form.country_code}${phone}`;

    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};

const showPassword = ref(false);
const showPasswordConfirm = ref(false);

const handleGoogleRegister = () => {
    window.location.href = route('google.register');
};
</script>

<template>
    <AuthBase title="Create an account" description="Enter your details below to create your account">
        <Head title="Register" />

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <!-- Name -->
                <div class="grid gap-2">
                    <Input id="name" type="text" required autofocus :tabindex="1" autocomplete="name" v-model="form.name" placeholder="Full name" />
                    <InputError :message="form.errors.name" />
                </div>

                <!-- Email -->
                <div class="grid gap-2">
                    <Input id="email" type="email" required :tabindex="2" autocomplete="email" v-model="form.email" placeholder="email@example.com" />
                    <InputError :message="form.errors.email" />
                </div>

                <!-- Country Code + Phone -->
                <div class="grid gap-2">
                    <div class="flex gap-2">
                        <select
                            v-model="form.country_code"
                            class="w-32 shrink-0 rounded-md border px-3 py-2 text-sm text-muted-foreground dark:border-muted dark:bg-background"
                        >
                            <option v-for="country in countries" :key="country.code + country.iso" :value="country.code">
                                {{ country.iso }} {{ country.emoji }} ({{ country.code }})
                            </option>
                        </select>

                        <Input
                            id="phone"
                            type="tel"
                            required
                            :tabindex="3"
                            autocomplete="tel"
                            v-model="form.phone"
                            placeholder="712345678"
                            class="flex-1"
                        />
                    </div>
                    <InputError :message="form.errors.phone" />
                </div>

                <!-- Password + Confirm Password -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="relative grid gap-2">
                        <Input
                            :type="showPassword ? 'text' : 'password'"
                            id="password"
                            required
                            :tabindex="4"
                            autocomplete="new-password"
                            v-model="form.password"
                            placeholder="Password"
                        />
                        <button type="button" class="absolute top-[38%] right-3" @click="showPassword = !showPassword">
                            <component :is="showPassword ? EyeOff : Eye" class="h-4 w-4 text-muted-foreground" />
                        </button>
                        <InputError :message="form.errors.password" />
                    </div>

                    <div class="relative grid gap-2">
                        <Input
                            :type="showPasswordConfirm ? 'text' : 'password'"
                            id="password_confirmation"
                            required
                            :tabindex="5"
                            autocomplete="new-password"
                            v-model="form.password_confirmation"
                            placeholder="Confirm"
                        />
                        <button type="button" class="absolute top-[38%] right-3" @click="showPasswordConfirm = !showPasswordConfirm">
                            <component :is="showPasswordConfirm ? EyeOff : Eye" class="h-4 w-4 text-muted-foreground" />
                        </button>
                        <InputError :message="form.errors.password_confirmation" />
                    </div>
                </div>

                <!-- Submit -->
                <Button type="submit" class="mt-2 w-full" tabindex="6" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    Create account
                </Button>

                <!-- Divider -->
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <span class="w-full border-t border-border" />
                    </div>
                    <div class="relative flex justify-center text-xs uppercase">
                        <span class="bg-background px-2 text-muted-foreground"> Or continue with Google </span>
                    </div>
                </div>

                <!-- Google Login Button -->
                <Button type="button" variant="outline" class="flex w-full items-center justify-center gap-3 py-6" @click="handleGoogleRegister">
                    <svg class="h-5 w-5" viewBox="0 0 24 24">
                        <path
                            fill="#4285F4"
                            d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                        />
                        <path
                            fill="#34A853"
                            d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                        />
                        <path
                            fill="#FBBC05"
                            d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
                        />
                        <path
                            fill="#EB4335"
                            d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                        />
                    </svg>
                    Continue with Google
                </Button>
            </div>

            <!-- Link to login -->
            <div class="text-center text-sm text-muted-foreground">
                Already have an account?
                <TextLink :href="route('login')" class="underline underline-offset-4" :tabindex="7">Log in</TextLink>
            </div>
        </form>
    </AuthBase>
</template>
