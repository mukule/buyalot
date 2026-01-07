<script setup lang="ts">
import TextLink from '@/components/TextLink.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { LoaderCircle, Eye, EyeOff } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import VendorAuthLayout from '@/layouts/VendorAuthLayout.vue';

defineProps<{
    status?: string;
    canResetPassword: boolean;
    errors?: Record<string, string>;
}>();

// Get the App Name from the shared data (Inertia typically shares app.name from config)
const page = usePage();
const appName = computed(() => page.props.appName || 'Buyalot');

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

const onEmailInput = (e: Event) => {
    const target = e.target as HTMLInputElement | null;
    if (target) {
        form.email = target.value.toLowerCase();
    }
};

const submit = () => {
    form.post(route('vendor.login.store'), {
        onFinish: () => form.reset('password'),
    });
};

const handleGoogleLogin = () => {
    if (route().has('google.redirect')) {
        window.location.href = route('google.redirect');
    }
};
</script>

<template>
    <VendorAuthLayout
        :title="`Welcome back to ${appName}`"
        description="Access your merchant dashboard to manage inventory and sales."
    >
        <Head :title="`${appName} Merchant Login`" />

        <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <div class="flex flex-col gap-6">
            <form @submit.prevent="submit" class="flex flex-col gap-4">
                <div class="grid gap-4">
                    <div class="grid gap-2">
                        <Label for="email">Store Email Address</Label>
                        <Input
                            id="email"
                            type="email"
                            required
                            autofocus
                            v-model="form.email"
                            @input="onEmailInput"
                            placeholder="vendor@example.com"
                            class="h-11"
                        />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div class="grid gap-2">
                        <div class="flex items-center justify-between">
                            <Label for="password">Password</Label>
                        </div>
                        <div class="relative">
                            <Input
                                id="password"
                                :type="showPassword ? 'text' : 'password'"
                                required
                                v-model="form.password"
                                placeholder="••••••••"
                                class="h-11 pr-10"
                            />
                            <button
                                type="button"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-muted-foreground"
                                @click="showPassword = !showPassword"
                                tabindex="-1"
                            >
                                <Eye v-if="!showPassword" class="h-4 w-4" />
                                <EyeOff v-else class="h-4 w-4" />
                            </button>
                        </div>
                        <InputError :message="form.errors.password" />
                    </div>

                    <div class="flex items-center justify-between">
                        <Label class="flex items-center gap-2 cursor-pointer">
                            <Checkbox v-model:checked="form.remember" />
                            <span class="text-sm font-normal">Keep me logged in</span>
                        </Label>

                        <TextLink v-if="canResetPassword" :href="route('password.request')" class="text-sm">
                            Forgot password?
                        </TextLink>
                    </div>

                    <Button type="submit" class="w-full h-11" :disabled="form.processing">
                        <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                        Sign in to Dashboard
                    </Button>
                </div>
            </form>

            <div class="relative">
                <div class="absolute inset-0 flex items-center">
                    <span class="w-full border-t border-border" />
                </div>
                <div class="relative flex justify-center text-xs uppercase">
                    <span class="bg-background px-2 text-muted-foreground">Or login with</span>
                </div>
            </div>

            <Button variant="outline" class="w-full h-11 gap-2" @click="handleGoogleLogin">
                <svg class="h-4 w-4" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                    <path fill="#EB4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                </svg>
                Google Account
            </Button>

            <div class="mt-4 text-center text-sm">
                <span class="text-muted-foreground">New to {{ appName }} selling?</span>
                <TextLink :href="route('sell.index')" class="ml-1 font-semibold">
                    Create a merchant account
                </TextLink>
            </div>

            <div class="text-center">
                <TextLink :href="route('home')" class="text-xs text-muted-foreground hover:text-primary">
                    ← Back to Marketplace
                </TextLink>
            </div>
        </div>
    </VendorAuthLayout>
</template>
