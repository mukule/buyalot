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
            <div class="mt-4 text-center text-sm">
                <span class="text-muted-foreground">New to {{ appName }} selling?</span>
                <TextLink :href="route('sell.index')" class="ml-1 font-semibold">
                    Create a Vendor account
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
