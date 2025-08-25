<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ShieldX, ArrowLeft, Home, Mail } from 'lucide-vue-next';

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
            description: 'Return to the main dashboard'
        },
        {
            title: 'Go Back',
            href: 'javascript:history.back()',
            description: 'Return to the previous page'
        }
    ]
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
        <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-md w-full space-y-8 text-center">
                <!-- Icon -->
                <div class="mx-auto flex items-center justify-center h-24 w-24 rounded-full bg-red-100">
                    <ShieldX class="h-12 w-12 text-red-600" />
                </div>

                <!-- Header -->
                <div>
                    <h1 class="mt-6 text-3xl font-bold text-gray-900">
                        Access Denied
                    </h1>
                    <p class="mt-2 text-sm text-gray-600">
                        {{ message }}
                    </p>
                    <p v-if="requiredPermission" class="mt-2 text-xs text-gray-500">
                        Required permission: <code class="bg-gray-100 px-2 py-1 rounded text-red-600">{{ requiredPermission }}</code>
                    </p>
                </div>

                <!-- Error Details Card -->
                <div class="bg-white shadow-lg rounded-lg p-6 text-left">
                    <h3 class="text-lg font-medium text-gray-900 mb-3">What can you do?</h3>
                    <ul class="space-y-3">
                        <li class="flex items-start space-x-3">
                            <div class="flex-shrink-0 w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center">
                                <span class="text-blue-600 text-sm font-medium">1</span>
                            </div>
                            <p class="text-sm text-gray-600">
                                Contact your administrator to request access to this resource
                            </p>
                        </li>
                        <li class="flex items-start space-x-3">
                            <div class="flex-shrink-0 w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center">
                                <span class="text-blue-600 text-sm font-medium">2</span>
                            </div>
                            <p class="text-sm text-gray-600">
                                Verify you are logged in with the correct account
                            </p>
                        </li>
                        <li class="flex items-start space-x-3">
                            <div class="flex-shrink-0 w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center">
                                <span class="text-blue-600 text-sm font-medium">3</span>
                            </div>
                            <p class="text-sm text-gray-600">
                                Navigate to a different section you have access to
                            </p>
                        </li>
                    </ul>
                </div>

                <!-- Action Buttons -->
                <div class="space-y-4">
                    <!-- Primary Actions -->
                    <div class="space-y-2">
                        <button
                            @click="goBack"
                            class="group relative w-full flex justify-center items-center py-3 px-4 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200"
                        >
                            <ArrowLeft class="h-4 w-4 mr-2" />
                            Go Back
                        </button>

                        <Link
                            href="/admin/dashboard"
                            class="group relative w-full flex justify-center items-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200"
                        >
                            <Home class="h-4 w-4 mr-2" />
                            Go to Dashboard
                        </Link>
                    </div>

                    <!-- Secondary Actions -->
                    <div class="pt-4 border-t border-gray-200">
                        <button
                            @click="contactSupport"
                            class="group relative w-full flex justify-center items-center py-2 px-4 text-sm font-medium text-blue-600 hover:text-blue-500 transition-colors duration-200"
                        >
                            <Mail class="h-4 w-4 mr-2" />
                            Request Access
                        </button>
                    </div>

                    <!-- Suggested Actions (if provided) -->
                    <div v-if="suggestedActions && suggestedActions.length > 0" class="pt-4 border-t border-gray-200">
                        <h4 class="text-sm font-medium text-gray-900 mb-3">Suggested Actions:</h4>
                        <div class="space-y-2">
                            <Link
                                v-for="action in suggestedActions"
                                :key="action.title"
                                :href="action.href"
                                class="block w-full text-left py-2 px-3 text-sm text-blue-600 hover:text-blue-500 hover:bg-blue-50 rounded-md transition-colors duration-200"
                            >
                                <div class="font-medium">{{ action.title }}</div>
                                <div v-if="action.description" class="text-xs text-gray-500 mt-1">
                                    {{ action.description }}
                                </div>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Footer Info -->
                <div class="text-xs text-gray-400 pt-6">
                    <p>If you believe this is an error, please contact support.</p>
                    <p class="mt-1">Error Code: 403 - Forbidden</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
