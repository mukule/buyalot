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
import AuthLayout from '@/layouts/AuthLayout.vue';

defineProps<{
    status?: string;
    canResetPassword: boolean;
    errors?: Record<string, string>;
}>();

const page = usePage();
const appName = computed(() => page.props.appName || 'Bianlina');

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

const onEmailInput = (e: Event) => {
    const target = e.target as HTMLInputElement | null;
    if (target) form.email = target.value.toLowerCase();
};

const submit = () => {
    form.post(route('delivery.login.store'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <AuthLayout title="Delivery login" :description="`Sign in to view your assigned deliveries`">
        <Head :title="`${appName} – Delivery Login`" />

        <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <div class="flex flex-col gap-6">
            <form @submit.prevent="submit" class="flex flex-col gap-4">
                <div class="grid gap-4">
                    <div class="grid gap-2">
                        <Label for="email">Email</Label>
                        <Input
                            id="email"
                            type="email"
                            required
                            autofocus
                            v-model="form.email"
                            @input="onEmailInput"
                            placeholder="delivery@example.com"
                            class="h-11"
                        />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password">Password</Label>
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
                        <Label class="flex cursor-pointer items-center gap-2">
                            <Checkbox v-model:checked="form.remember" />
                            <span class="text-sm font-normal">Remember me</span>
                        </Label>
                        <TextLink v-if="canResetPassword" :href="route('password.request')" class="text-sm">Forgot password?</TextLink>
                    </div>

                    <p class="text-sm text-muted-foreground">
                        Not yet a delivery partner?
                        <TextLink :href="route('delivery.register')">Register here</TextLink>
                    </p>

                    <Button type="submit" class="h-11 w-full text-white" :disabled="form.processing">
                        <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                        Sign in
                    </Button>
                </div>
            </form>
        </div>
    </AuthLayout>
</template>
