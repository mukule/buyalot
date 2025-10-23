<template>
    <AppLayout title="Welcome">
        <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
            <div class="container mx-auto px-4 py-16">
                <div class="mx-auto max-w-4xl">
                    <!-- Welcome Header -->
                    <div class="mb-12 text-center">
                        <div class="mb-6">
                            <img
                                :src="avatarUrl"
                                :alt="customer.first_name"
                                class="mx-auto h-24 w-24 rounded-full border-4 border-white object-cover shadow-lg"
                            />
                        </div>

                        <h1 class="mb-4 text-4xl font-bold text-gray-900">
                            Welcome to {{ $page.props.appName || 'Our Platform' }}, {{ customer.first_name }}! 🎉
                        </h1>

                        <p class="mb-8 text-xl text-gray-600">
                            We're thrilled to have you join our community. Let's get you started on your journey.
                        </p>
                    </div>

                    <!-- Welcome Cards -->
                    <div class="mb-12 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                        <!-- Get Started -->
                        <div class="rounded-lg bg-white p-6 text-center shadow-md transition-shadow hover:shadow-lg">
                            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-blue-100">
                                <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                            <h3 class="mb-2 text-xl font-semibold text-gray-900">Quick Start</h3>
                            <p class="mb-4 text-gray-600">Complete your profile and explore our features to get the most out of your experience.</p>
                            <Link
                                :href="route('customer.profile.show')"
                                class="inline-flex items-center font-medium text-blue-600 hover:text-blue-700"
                            >
                                Complete Profile
                                <svg class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </Link>
                        </div>

                        <!-- Explore Features -->
                        <div class="rounded-lg bg-white p-6 text-center shadow-md transition-shadow hover:shadow-lg">
                            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-green-100">
                                <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"
                                    ></path>
                                </svg>
                            </div>
                            <h3 class="mb-2 text-xl font-semibold text-gray-900">Discover</h3>
                            <p class="mb-4 text-gray-600">Browse our features and services tailored just for you.</p>
                            <Link :href="route('home')" class="inline-flex items-center font-medium text-green-600 hover:text-green-700">
                                Explore Now
                                <svg class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </Link>
                        </div>

                        <!-- Get Help -->
                        <div class="rounded-lg bg-white p-6 text-center shadow-md transition-shadow hover:shadow-lg">
                            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-purple-100">
                                <svg class="h-8 w-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                    ></path>
                                </svg>
                            </div>
                            <h3 class="mb-2 text-xl font-semibold text-gray-900">Need Help?</h3>
                            <p class="mb-4 text-gray-600">Our support team is here to help you every step of the way.</p>
                            <a
                                href="#"
                                @click.prevent="openSupport"
                                class="inline-flex cursor-pointer items-center font-medium text-purple-600 hover:text-purple-700"
                            >
                                Contact Support
                                <svg class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        </div>
                    </div>

                    <!-- Account Details -->
                    <div class="mb-8 rounded-lg bg-white p-8 shadow-md">
                        <h2 class="mb-6 text-2xl font-semibold text-gray-900">Your Account Details</h2>

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <h3 class="mb-1 text-sm font-medium text-gray-500">Full Name</h3>
                                <p class="text-lg text-gray-900">{{ customer.first_name }} {{ customer.last_name }}</p>
                            </div>

                            <div>
                                <h3 class="mb-1 text-sm font-medium text-gray-500">Email Address</h3>
                                <p class="text-lg text-gray-900">{{ customer.email }}</p>
                            </div>

                            <div>
                                <h3 class="mb-1 text-sm font-medium text-gray-500">Account Type</h3>
                                <p class="text-lg text-gray-900">{{ customerType }}</p>
                            </div>

                            <div>
                                <h3 class="mb-1 text-sm font-medium text-gray-500">Member Since</h3>
                                <p class="text-lg text-gray-900">{{ customer.created_at }}</p>
                            </div>
                        </div>

                        <div v-if="customer.provider" class="mt-6 rounded-lg bg-blue-50 p-4">
                            <div class="flex items-center">
                                <svg class="mr-2 h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                    ></path>
                                </svg>
                                <span class="text-blue-800"> Connected via {{ providerName }} </span>
                            </div>
                        </div>
                    </div>

                    <!-- Next Steps -->
                    <div class="rounded-lg bg-gradient-to-r from-blue-600 to-purple-600 p-8 text-center text-white shadow-lg">
                        <h2 class="mb-4 text-2xl font-bold">Ready to Get Started?</h2>
                        <p class="mb-6 text-blue-100">
                            Take the next step and make the most of your {{ $page.props.appName || 'platform' }} experience.
                        </p>

                        <div class="flex flex-col justify-center gap-4 sm:flex-row">
                            <Link
                                :href="route('customer.profile.show')"
                                class="rounded-lg bg-white px-6 py-3 font-semibold text-blue-600 transition-colors hover:bg-blue-50"
                            >
                                Complete Your Profile
                            </Link>

                            <Link
                                :href="route('home')"
                                class="rounded-lg bg-blue-500 px-6 py-3 font-semibold text-white transition-colors hover:bg-blue-400"
                            >
                                Start Exploring
                            </Link>
                        </div>
                    </div>

                    <!-- Quick Tips -->
                    <div class="mt-12 grid grid-cols-1 gap-8 md:grid-cols-2">
                        <div class="rounded-lg bg-white p-6 shadow-md">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-yellow-100">
                                        <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"
                                            ></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <h3 class="mb-2 text-lg font-semibold text-gray-900">Pro Tip</h3>
                                    <p class="text-gray-600">Complete your profile to unlock all features and get personalized recommendations.</p>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-lg bg-white p-6 shadow-md">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-green-100">
                                        <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                                            ></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <h3 class="mb-2 text-lg font-semibold text-gray-900">Stay Safe</h3>
                                    <p class="text-gray-600">
                                        Your account is secure. Remember to keep your login credentials private and update your password regularly.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/MainLayout.vue';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    customer: Object,
});

const avatarUrl = computed(() => {
    if (props.customer.avatar) return props.customer.avatar;
    return `https://ui-avatars.com/api/?name=${encodeURIComponent(props.customer.first_name + ' ' + props.customer.last_name)}&size=128&background=3B82F6&color=ffffff`;
});

const customerType = computed(() => {
    const type = props.customer.customer_type || 'individual';
    return type.charAt(0).toUpperCase() + type.slice(1);
});

const providerName = computed(() => {
    if (!props.customer.provider) return '';
    return props.customer.provider.charAt(0).toUpperCase() + props.customer.provider.slice(1);
});

const openSupport = () => {
    console.log('Opening support...');
};
</script>
