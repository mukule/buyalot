
<template>
    <AppLayout title="Welcome">
        <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
            <div class="container mx-auto px-4 py-16">
                <div class="max-w-4xl mx-auto">
                    <!-- Welcome Header -->
                    <div class="text-center mb-12">
                        <div class="mb-6">
                            <img
                                :src="avatarUrl"
                                :alt="customer.first_name"
                                class="w-24 h-24 rounded-full mx-auto object-cover border-4 border-white shadow-lg"
                            >
                        </div>

                        <h1 class="text-4xl font-bold text-gray-900 mb-4">
                            Welcome to {{ $page.props.appName || 'Our Platform' }}, {{ customer.first_name }}! 🎉
                        </h1>

                        <p class="text-xl text-gray-600 mb-8">
                            We're thrilled to have you join our community. Let's get you started on your journey.
                        </p>
                    </div>

                    <!-- Welcome Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
                        <!-- Get Started -->
                        <div class="bg-white rounded-lg shadow-md p-6 text-center hover:shadow-lg transition-shadow">
                            <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">Quick Start</h3>
                            <p class="text-gray-600 mb-4">Complete your profile and explore our features to get the most out of your experience.</p>
                            <Link
                                :href="route('customer.profile.show')"
                                class="inline-flex items-center text-blue-600 hover:text-blue-700 font-medium"
                            >
                                Complete Profile
                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </Link>
                        </div>

                        <!-- Explore Features -->
                        <div class="bg-white rounded-lg shadow-md p-6 text-center hover:shadow-lg transition-shadow">
                            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">Discover</h3>
                            <p class="text-gray-600 mb-4">Browse our features and services tailored just for you.</p>
                            <Link
                                :href="route('home')"
                                class="inline-flex items-center text-green-600 hover:text-green-700 font-medium"
                            >
                                Explore Now
                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </Link>
                        </div>

                        <!-- Get Help -->
                        <div class="bg-white rounded-lg shadow-md p-6 text-center hover:shadow-lg transition-shadow">
                            <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">Need Help?</h3>
                            <p class="text-gray-600 mb-4">Our support team is here to help you every step of the way.</p>
                            <a
                                href="#"
                                @click.prevent="openSupport"
                                class="inline-flex items-center text-purple-600 hover:text-purple-700 font-medium cursor-pointer"
                            >
                                Contact Support
                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        </div>
                    </div>

                    <!-- Account Details -->
                    <div class="bg-white rounded-lg shadow-md p-8 mb-8">
                        <h2 class="text-2xl font-semibold text-gray-900 mb-6">Your Account Details</h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 mb-1">Full Name</h3>
                                <p class="text-lg text-gray-900">{{ customer.first_name }} {{ customer.last_name }}</p>
                            </div>

                            <div>
                                <h3 class="text-sm font-medium text-gray-500 mb-1">Email Address</h3>
                                <p class="text-lg text-gray-900">{{ customer.email }}</p>
                            </div>

                            <div>
                                <h3 class="text-sm font-medium text-gray-500 mb-1">Account Type</h3>
                                <p class="text-lg text-gray-900">{{ customerType }}</p>
                            </div>

                            <div>
                                <h3 class="text-sm font-medium text-gray-500 mb-1">Member Since</h3>
                                <p class="text-lg text-gray-900">{{ customer.created_at }}</p>
                            </div>
                        </div>

                        <div v-if="customer.provider" class="mt-6 p-4 bg-blue-50 rounded-lg">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span class="text-blue-800">
                  Connected via {{ providerName }}
                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Next Steps -->
                    <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg shadow-lg p-8 text-white text-center">
                        <h2 class="text-2xl font-bold mb-4">Ready to Get Started?</h2>
                        <p class="text-blue-100 mb-6">
                            Take the next step and make the most of your {{ $page.props.appName || 'platform' }} experience.
                        </p>

                        <div class="flex flex-col sm:flex-row gap-4 justify-center">
                            <Link
                                :href="route('customer.profile.show')"
                                class="bg-white text-blue-600 hover:bg-blue-50 font-semibold py-3 px-6 rounded-lg transition-colors"
                            >
                                Complete Your Profile
                            </Link>

                            <Link
                                :href="route('home')"
                                class="bg-blue-500 hover:bg-blue-400 text-white font-semibold py-3 px-6 rounded-lg transition-colors"
                            >
                                Start Exploring
                            </Link>
                        </div>
                    </div>

                    <!-- Quick Tips -->
                    <div class="mt-12 grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Pro Tip</h3>
                                    <p class="text-gray-600">
                                        Complete your profile to unlock all features and get personalized recommendations.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow-md p-6">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Stay Safe</h3>
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
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/MainLayout.vue';

const props = defineProps({
    customer: Object
})

const avatarUrl = computed(() => {
    if (props.customer.avatar) return props.customer.avatar
    return `https://ui-avatars.com/api/?name=${encodeURIComponent(props.customer.first_name + ' ' + props.customer.last_name)}&size=128&background=3B82F6&color=ffffff`
})

const customerType = computed(() => {
    const type = props.customer.customer_type || 'individual'
    return type.charAt(0).toUpperCase() + type.slice(1)
})

const providerName = computed(() => {
    if (!props.customer.provider) return ''
    return props.customer.provider.charAt(0).toUpperCase() + props.customer.provider.slice(1)
})

const openSupport = () => {
    console.log('Opening support...')
}
</script>
