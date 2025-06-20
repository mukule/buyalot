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
    let phone = form.phone.replace(/^0/, ''); // remove leading 0
    form.phone = `${form.country_code}${phone}`;

    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};

const showPassword = ref(false);
const showPasswordConfirm = ref(false);
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
            </div>

            <!-- Link to login -->
            <div class="text-center text-sm text-muted-foreground">
                Already have an account?
                <TextLink :href="route('login')" class="underline underline-offset-4" :tabindex="7">Log in</TextLink>
            </div>
        </form>
    </AuthBase>
</template>
