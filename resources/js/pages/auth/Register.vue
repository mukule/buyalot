<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import allCountries from 'country-calling-code';
import { Eye, EyeOff, LoaderCircle } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const form = useForm({
    first_name: '',
    last_name: '',
    name: '',
    email: '',
    country_code: '+254',
    phone: '',
    password: '',
    password_confirmation: '',
    customer_type: 'individual',
});

// Always keep email lowercase on the client
watch(
    () => form.email,
    (val) => {
        if (typeof val === 'string') {
            const lower = val.toLowerCase();
            if (lower !== val) form.email = lower;
        }
    }
);

// Enforce Firstname/Lastname capitalization live on the form
const normalizeName = (v: string) => {
    const t = (v || '').trim();
    return t ? t.charAt(0).toUpperCase() + t.slice(1).toLowerCase() : '';
};

watch(
    () => form.first_name,
    (val) => {
        if (typeof val === 'string') {
            const normalized = normalizeName(val);
            if (normalized !== val) form.first_name = normalized;
        }
    }
);

watch(
    () => form.last_name,
    (val) => {
        if (typeof val === 'string') {
            const normalized = normalizeName(val);
            if (normalized !== val) form.last_name = normalized;
        }
    }
);

/* Single-stage form (address step removed) */

// Helpers for client-side validation
const clearClientErrors = (keys: string[]) => {
    keys.forEach((k) => form.clearErrors(k as any));
};

const setClientError = (key: string, message: string) => {
    form.setError(key as any, message);
};

const isEmail = (val: string) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val);

const validateStep1 = (): boolean => {
    const keys = ['first_name', 'last_name', 'email', 'phone', 'password', 'password_confirmation'];
    clearClientErrors(keys);

    let ok = true;

    if (!form.first_name?.trim()) {
        setClientError('first_name', 'First name is required.');
        ok = false;
    }
    if (!form.last_name?.trim()) {
        setClientError('last_name', 'Last name is required.');
        ok = false;
    }
    if (!form.email?.trim()) {
        setClientError('email', 'Email is required.');
        ok = false;
    } else if (!isEmail(form.email.trim())) {
        setClientError('email', 'Please enter a valid email address.');
        ok = false;
    }

    const rawPhone = (form.phone || '').toString();
    if (!rawPhone.trim()) {
        setClientError('phone', 'Phone is required.');
        ok = false;
    } else {
        const digits = rawPhone.replace(/[^0-9]/g, '').replace(/^0/, '');
        if (digits.length < 7 || digits.length > 15) {
            setClientError('phone', 'Enter a valid phone number.');
            ok = false;
        }
    }

    if (!form.password) {
        setClientError('password', 'Password is required.');
        ok = false;
    } else if (form.password.length < 8) {
        setClientError('password', 'Password must be at least 8 characters.');
        ok = false;
    }

    if (!form.password_confirmation) {
        setClientError('password_confirmation', 'Please confirm your password.');
        ok = false;
    } else if (form.password !== form.password_confirmation) {
        setClientError('password_confirmation', 'Passwords do not match.');
        ok = false;
    }

    return ok;
};



const toFlagEmoji = (iso: string): string => {
    if (!iso || iso.length !== 2) return '';
    const upper = iso.toUpperCase();
    const codePoints = Array.from(upper).map((ch) => 127397 + ch.charCodeAt(0));
    try {
        return String.fromCodePoint(...codePoints);
    } catch {
        return '';
    }
};

const countries = allCountries.map((c) => ({
    code: `+${Array.isArray(c.countryCodes) ? c.countryCodes[0] : ''}`,
    iso: c.isoCode2, // Add ISO shortform
    name: `${c.country}`,
    emoji: toFlagEmoji(c.isoCode2),
}));



const submit = () => {
    // Ensure email is lowercase before any validation/submission
    if (typeof form.email === 'string') {
        form.email = form.email.toLowerCase();
    }

    // Normalize first and last names: first char uppercase, rest lowercase
    if (typeof form.first_name === 'string') {
        const t = form.first_name.trim();
        form.first_name = t ? t.charAt(0).toUpperCase() + t.slice(1).toLowerCase() : '';
    }
    if (typeof form.last_name === 'string') {
        const t = form.last_name.trim();
        form.last_name = t ? t.charAt(0).toUpperCase() + t.slice(1).toLowerCase() : '';
    }

    // Validate basic info only
    const ok = validateStep1();
    if (!ok) return;

    // Compose display name
    form.name = `${form.first_name} ${form.last_name}`.trim();

    // Normalize phone number before submit
    const phone = form.phone.replace(/[^0-9]/g, '').replace(/^0/, ''); // digits only, remove leading 0
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
                <!-- Basic information -->
                <div class="grid gap-6">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Input id="first_name" type="text" required autofocus v-model="form.first_name" placeholder="First name" />
                            <InputError :message="form.errors.first_name" />
                        </div>
                        <div class="grid gap-2">
                            <Input id="last_name" type="text" required v-model="form.last_name" placeholder="Last name" />
                            <InputError :message="form.errors.last_name" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Input id="email" type="email" required autocomplete="email" v-model="form.email" placeholder="email@example.com" />
                        <InputError :message="form.errors.email" />
                    </div>

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
                                autocomplete="tel"
                                v-model="form.phone"
                                placeholder="712345678"
                                class="flex-1"
                            />
                        </div>
                        <InputError :message="form.errors.phone" />
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="relative grid gap-2">
                            <Input
                                :type="showPassword ? 'text' : 'password'"
                                id="password"
                                required
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
                </div>

                <!-- Actions -->
                <div class="mt-2 flex items-center justify-end">
                    <Button type="submit" :disabled="form.processing">
                        <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                        <span v-else>Sign up</span>
                    </Button>
                </div>

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
                <TextLink :href="route('login')" class="underline underline-offset-4">Log in</TextLink>
            </div>
        </form>
        <div class="text-center text-sm text-muted-foreground">
            <TextLink :href="route('home')" :tabindex="6">Back to Home Page</TextLink>
        </div>
    </AuthBase>
</template>
