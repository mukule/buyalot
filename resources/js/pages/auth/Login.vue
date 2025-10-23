<script setup lang="ts">
console.log('login page started...');
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
console.log('Available routes:', route().routes);

defineProps<{
    status?: string;
    canResetPassword: boolean;
    errors?: Record<string, string>;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

const handleGoogleLogin = () => {
    if (route().has('google.redirect')) {
        window.location.href = route('google.redirect');
    } else {
        console.log('google auth url not found');
    }
};
</script>

<template>
    <AuthBase title="Log in to your account" description="Enter your email and password below to log in">
        <Head title="Log in" />

        <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <div v-if="errors?.google" class="mb-4 text-center text-sm font-medium text-red-600">
            {{ errors.google }}
        </div>

        <div class="flex flex-col gap-6">
            <!-- Regular Login Form -->
            <form @submit.prevent="submit" class="flex flex-col gap-6">
                <div class="grid gap-6">
                    <div class="grid gap-2">
                        <Label for="email">Email address</Label>
                        <Input
                            id="email"
                            type="email"
                            required
                            autofocus
                            :tabindex="1"
                            autocomplete="email"
                            v-model="form.email"
                            placeholder="email@example.com"
                        />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div class="grid gap-2">
                        <div class="flex items-center justify-between">
                            <Label for="password">Password</Label>
                            <!--                            <TextLink v-if="canResetPassword" :href="route('password.request')" class="text-sm" :tabindex="5">-->
                            <!--                                Forgot password?-->
                            <!--                            </TextLink>-->
                        </div>
                        <Input
                            id="password"
                            type="password"
                            required
                            :tabindex="2"
                            autocomplete="current-password"
                            v-model="form.password"
                            placeholder="Password"
                        />
                        <InputError :message="form.errors.password" />
                    </div>

                    <div class="flex items-center justify-between">
                        <Label for="remember" class="flex items-center space-x-3">
                            <Checkbox id="remember" v-model="form.remember" :tabindex="3" />
                            <span>Remember me</span>
                        </Label>
                    </div>

                    <Button type="submit" class="mt-4 w-full" :tabindex="4" :disabled="form.processing">
                        <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                        Log in
                    </Button>
                </div>
            </form>

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
            <Button type="button" variant="outline" class="flex w-full items-center justify-center gap-3 py-6" @click="handleGoogleLogin">
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

            <div class="text-center text-sm text-muted-foreground">
                Don't have an account?
                <!--                <TextLink :href="route('register')" :tabindex="6">Sign up</TextLink>-->
            </div>
        </div>
    </AuthBase>
</template>

<!--<script setup lang="ts">-->
<!--import InputError from '@/components/InputError.vue';-->
<!--import TextLink from '@/components/TextLink.vue';-->
<!--import { Button } from '@/components/ui/button';-->
<!--import { Checkbox } from '@/components/ui/checkbox';-->
<!--import { Input } from '@/components/ui/input';-->
<!--import { Label } from '@/components/ui/label';-->
<!--import AuthBase from '@/layouts/AuthLayout.vue';-->
<!--import { Head, useForm } from '@inertiajs/vue3';-->
<!--import { LoaderCircle } from 'lucide-vue-next';-->

<!--console.log("Available routes from Ziggy:", route().routes);-->
<!--console.log("Has login:", route().has('login'));-->
<!--console.log("Has register:", route().has('register'));-->
<!--console.log("Has password.request:", route().has('password.request'));-->

<!--defineProps<{-->
<!--    status?: string;-->
<!--    canResetPassword: boolean;-->
<!--}>();-->

<!--const form = useForm({-->
<!--    email: '',-->
<!--    password: '',-->
<!--    remember: false,-->
<!--});-->

<!--const submit = () => {-->
<!--    form.post(route('login'), {-->
<!--        onFinish: () => form.reset('password'),-->
<!--    });-->
<!--};-->

<!--</script>-->

<!--<template>-->
<!--    <AuthBase title="Log in to your account" description="Enter your email and password below to log in">-->
<!--        <Head title="Log in" />-->

<!--        <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-600">-->
<!--            {{ status }}-->
<!--        </div>-->

<!--        <form @submit.prevent="submit" class="flex flex-col gap-6">-->
<!--            <div class="grid gap-6">-->
<!--                <div class="grid gap-2">-->
<!--                    <Label for="email">Email address</Label>-->
<!--                    <Input-->
<!--                        id="email"-->
<!--                        type="email"-->
<!--                        required-->
<!--                        autofocus-->
<!--                        :tabindex="1"-->
<!--                        autocomplete="email"-->
<!--                        v-model="form.email"-->
<!--                        placeholder="email@example.com"-->
<!--                    />-->
<!--                    <InputError :message="form.errors.email" />-->
<!--                </div>-->

<!--                <div class="grid gap-2">-->
<!--                    <div class="flex items-center justify-between">-->
<!--                        <Label for="password">Password</Label>-->
<!--                        <TextLink v-if="canResetPassword" :href="route('password.request')" class="text-sm" :tabindex="5">-->
<!--                            Forgot password?-->
<!--                        </TextLink>-->
<!--                    </div>-->
<!--                    <Input-->
<!--                        id="password"-->
<!--                        type="password"-->
<!--                        required-->
<!--                        :tabindex="2"-->
<!--                        autocomplete="current-password"-->
<!--                        v-model="form.password"-->
<!--                        placeholder="Password"-->
<!--                    />-->
<!--                    <InputError :message="form.errors.password" />-->
<!--                </div>-->

<!--                <div class="flex items-center justify-between">-->
<!--                    <Label for="remember" class="flex items-center space-x-3">-->
<!--                        <Checkbox id="remember" v-model="form.remember" :tabindex="3" />-->
<!--                        <span>Remember me</span>-->
<!--                    </Label>-->
<!--                </div>-->

<!--                <Button type="submit" class="mt-4 w-full" :tabindex="4" :disabled="form.processing">-->
<!--                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />-->
<!--                    Log in-->
<!--                </Button>-->
<!--            </div>-->

<!--            <div class="text-center text-sm text-muted-foreground">-->
<!--                Don't have an account?-->
<!--                <TextLink :href="route('register')" :tabindex="5">Sign up</TextLink>-->
<!--            </div>-->
<!--        </form>-->
<!--    </AuthBase>-->
<!--</template>-->
