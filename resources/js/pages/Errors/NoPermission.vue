<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Home, Mail, ShieldX } from 'lucide-vue-next';

interface Props {
    message?: string;
    requiredPermission?: string;
    suggestedActions?: Array<{
        title: string;
        href: string;
        description?: string;
    }>;
}

const props = withDefaults(defineProps<Props>(), {
    message: "You don't have permission to access this resource.",
    requiredPermission: '',
    suggestedActions: () => [
        {
            title: 'Go to Dashboard',
            href: '/admin/dashboard',
            description: 'Return to the main dashboard',
        },
        {
            title: 'Go Back',
            href: 'javascript:history.back()',
            description: 'Return to the previous page',
        },
    ],
});

function goBack() {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        router.visit('/admin/dashboard');
    }
}

function contactSupport() {
    // You can customize this to open a support modal or redirect to contact page
    window.location.href = 'mailto:support@yourapp.com?subject=Access Request&body=I need access to: ' + window.location.href;
}
</script>

<template>
    <Head title="Access Denied" />

    <AppLayout>
        <div class="flex min-h-screen items-center justify-center bg-gray-50 px-4 py-12 sm:px-6 lg:px-8">
            <div class="w-full max-w-md space-y-8 text-center">
                <!-- Icon -->
                <div class="mx-auto flex h-24 w-24 items-center justify-center rounded-full bg-red-100">
                    <ShieldX class="h-12 w-12 text-red-600" />
                </div>

                <!-- Header -->
                <div>
                    <h1 class="mt-6 text-3xl font-bold text-gray-900">Access Denied</h1>
                    <p class="mt-2 text-sm text-gray-600">
                        {{ message }}
                    </p>
                    <p v-if="requiredPermission" class="mt-2 text-xs text-gray-500">
                        Required permission: <code class="rounded bg-gray-100 px-2 py-1 text-red-600">{{ requiredPermission }}</code>
                    </p>
                </div>

                <!-- Error Details Card -->
                <div class="rounded-lg bg-white p-6 text-left shadow-lg">
                    <h3 class="mb-3 text-lg font-medium text-gray-900">What can you do?</h3>
                    <ul class="space-y-3">
                        <li class="flex items-start space-x-3">
                            <div class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-blue-100">
                                <span class="text-sm font-medium text-blue-600">1</span>
                            </div>
                            <p class="text-sm text-gray-600">Contact your administrator to request access to this resource</p>
                        </li>
                        <li class="flex items-start space-x-3">
                            <div class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-blue-100">
                                <span class="text-sm font-medium text-blue-600">2</span>
                            </div>
                            <p class="text-sm text-gray-600">Verify you are logged in with the correct account</p>
                        </li>
                        <li class="flex items-start space-x-3">
                            <div class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-blue-100">
                                <span class="text-sm font-medium text-blue-600">3</span>
                            </div>
                            <p class="text-sm text-gray-600">Navigate to a different section you have access to</p>
                        </li>
                    </ul>
                </div>

                <!-- Action Buttons -->
                <div class="space-y-4">
                    <!-- Primary Actions -->
                    <div class="space-y-2">
                        <button
                            @click="goBack"
                            class="group relative flex w-full items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 transition-colors duration-200 hover:bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none"
                        >
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Go Back
                        </button>

                        <Link
                            href="/admin/dashboard"
                            class="group relative flex w-full items-center justify-center rounded-md border border-transparent bg-blue-600 px-4 py-3 text-sm font-medium text-white transition-colors duration-200 hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none"
                        >
                            <Home class="mr-2 h-4 w-4" />
                            Go to Dashboard
                        </Link>
                    </div>

                    <!-- Secondary Actions -->
                    <div class="border-t border-gray-200 pt-4">
                        <button
                            @click="contactSupport"
                            class="group relative flex w-full items-center justify-center px-4 py-2 text-sm font-medium text-blue-600 transition-colors duration-200 hover:text-blue-500"
                        >
                            <Mail class="mr-2 h-4 w-4" />
                            Request Access
                        </button>
                    </div>

                    <!-- Suggested Actions (if provided) -->
                    <div v-if="suggestedActions && suggestedActions.length > 0" class="border-t border-gray-200 pt-4">
                        <h4 class="mb-3 text-sm font-medium text-gray-900">Suggested Actions:</h4>
                        <div class="space-y-2">
                            <Link
                                v-for="action in suggestedActions"
                                :key="action.title"
                                :href="action.href"
                                class="block w-full rounded-md px-3 py-2 text-left text-sm text-blue-600 transition-colors duration-200 hover:bg-blue-50 hover:text-blue-500"
                            >
                                <div class="font-medium">{{ action.title }}</div>
                                <div v-if="action.description" class="mt-1 text-xs text-gray-500">
                                    {{ action.description }}
                                </div>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Footer Info -->
                <div class="pt-6 text-xs text-gray-400">
                    <p>If you believe this is an error, please contact support.</p>
                    <p class="mt-1">Error Code: 403 - Forbidden</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
