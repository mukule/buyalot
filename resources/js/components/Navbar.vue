<script setup lang="ts">
import logo from '@/assets/images/logo.png';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Heart, Menu, Search, ShoppingCart, X } from 'lucide-vue-next';
import { computed, inject, ref } from 'vue';

import type { AppPageProps } from '@/types';

// Page props
const page = usePage<AppPageProps>();

// User object
const user = computed(() => page.props.auth?.user);

// Counts
const wishlistCount = computed<number>(() => page.props.auth?.counts?.wishlist ?? 0);
const cartCount = computed<number>(() => page.props.auth?.counts?.cart ?? 0);

// User initials
const userInitials = computed(() => {
    if (!user.value?.name) return '';
    return user.value.name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase();
});

// Menu state
const mobileMenuOpen = ref(false);
const showCategories = ref(false);

// Top links
const topLinks = [
    { name: 'Help Center', href: '/help' },
    { name: 'Sell on Buyalot', href: '/sell' },
];

// Auth links
const authLinks = computed(() => {
    if (user.value) {
        return [
            { name: 'My Account', href: '/account', isUser: true },
            { name: 'Orders', href: '/orders/my-orders' },
            { name: 'Logout', href: '/logout', isLogout: true },
        ];
    } else {
        return [
            { name: 'Orders', href: '/orders/my-orders' },
            { name: 'Login', href: '/login' },
            { name: 'Register', href: '/register' },
        ];
    }
});

// Categories
const categoryLinks = [
    'Electronics',
    'Apparel',
    'Home & Garden',
    'Beauty',
    'Sports',
    'Toys',
    'Books',
    'Automotive',
    'Health & Wellness',
    'Office Supplies',
    'Grocery',
    'Mobile Phones',
    'Computers',
    'Games',
    'Music',
];

// Ziggy route helper (if available)
const route = inject<((name: string, params?: any) => string) | undefined>('route');

// Safe URLs with fallbacks
const wishlistUrl = computed<string>(() => (route ? route('wishlist.index') : '/wishlist'));
const cartUrl = computed<string>(() => (route ? route('cart.index') : '/cart'));

console.log(wishlistUrl.value);
console.log(cartUrl.value);
// Methods
function toggleMobileMenu() {
    mobileMenuOpen.value = !mobileMenuOpen.value;
}

function logout() {
    router.post('/logout', {}, { preserveScroll: true });
}
</script>

<template>
    <header class="fixed top-0 left-0 z-50 w-full bg-white shadow-md">
        <div class="container mx-auto flex items-center justify-between px-4 py-3 sm:px-6">
            <!-- Logo & Top Links -->
            <div class="flex items-center space-x-6">
                <Link href="/">
                    <img :src="logo" alt="App Logo" class="h-10 w-auto" />
                </Link>
                <nav class="hidden space-x-4 text-sm md:flex">
                    <template v-for="(link, index) in topLinks" :key="link.name">
                        <Link :href="link.href" class="text-gray-500 hover:underline">{{ link.name }}</Link>
                        <span v-if="index < topLinks.length - 1" class="text-gray-400">|</span>
                    </template>
                </nav>
            </div>

            <!-- Desktop Right Nav -->
            <nav class="hidden items-center space-x-4 text-sm md:flex">
                <template v-for="(link, index) in authLinks" :key="link.name">
                    <button v-if="link.isLogout" @click.prevent="logout" class="cursor-pointer text-gray-500 hover:underline">
                        {{ link.name }}
                    </button>
                    <Link v-else-if="!link.isUser" :href="link.href" class="text-gray-500 hover:underline">{{ link.name }}</Link>
                    <Link v-else :href="link.href" class="flex items-center space-x-2 text-gray-700 hover:underline">
                        <div
                            class="flex h-7 w-7 items-center justify-center rounded-full bg-primary text-xs font-semibold text-white"
                            :title="user?.name"
                        >
                            {{ userInitials }}
                        </div>
                        <span>My Account</span>
                    </Link>
                    <span v-if="index < authLinks.length - 1" class="text-gray-400">|</span>
                </template>
                <Link href="/login">Login2</Link>

                <!-- Wishlist -->
                <Link :href="wishlistUrl" class="relative flex items-center justify-center rounded-full bg-secondary p-2">
                    <Heart class="h-4 w-4 text-white" />
                    <span
                        v-if="wishlistCount > 0"
                        class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] text-white"
                    >
                        {{ wishlistCount }}
                    </span>
                </Link>

                <!-- Cart -->
                <Link :href="cartUrl" class="relative flex items-center justify-center">
                    <ShoppingCart class="h-6 w-6 text-primary" />
                    <span
                        v-if="cartCount > 0"
                        class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] text-white"
                    >
                        {{ cartCount }}
                    </span>
                </Link>
            </nav>

            <!-- Mobile Right Controls -->
            <div class="flex items-center space-x-4 md:hidden">
                <Link :href="wishlistUrl" class="relative flex items-center justify-center rounded-full bg-secondary p-2">
                    <Heart class="h-5 w-5 text-white" />
                    <span
                        v-if="wishlistCount > 0"
                        class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] text-white"
                    >
                        {{ wishlistCount }}
                    </span>
                </Link>
                <Link :href="cartUrl" class="relative flex items-center justify-center">
                    <ShoppingCart class="h-6 w-6 text-primary" />
                    <span
                        v-if="cartCount > 0"
                        class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] text-white"
                    >
                        {{ cartCount }}
                    </span>
                </Link>
                <button
                    @click="toggleMobileMenu"
                    class="inline-flex items-center justify-center rounded-md p-2 text-gray-700 hover:bg-gray-100 focus:ring-2 focus:ring-secondary focus:outline-none focus:ring-inset"
                >
                    <Menu v-if="!mobileMenuOpen" class="h-6 w-6" />
                    <X v-else class="h-6 w-6" />
                </button>
            </div>
        </div>

        <!-- Mobile Menu -->
        <transition name="fade">
            <nav v-show="mobileMenuOpen" class="max-h-[80vh] overflow-y-auto border-t border-gray-200 bg-white shadow-md md:hidden">
                <div class="space-y-3 px-4 py-4">
                    <!-- Top Links -->
                    <div class="flex flex-col space-y-2 border-b border-gray-200 pb-3">
                        <template v-for="link in topLinks" :key="'mobile-top-' + link.name">
                            <Link
                                :href="link.href"
                                class="block rounded-md px-3 py-2 text-gray-700 hover:bg-gray-100 hover:underline"
                                @click="mobileMenuOpen = false"
                            >
                                {{ link.name }}
                            </Link>
                        </template>
                    </div>

                    <!-- Auth Links -->
                    <div class="flex flex-col space-y-2 border-b border-gray-200 pb-3">
                        <template v-for="link in authLinks" :key="'mobile-auth-' + link.name">
                            <button
                                v-if="link.isLogout"
                                @click.prevent="logout"
                                class="block w-full rounded-md px-3 py-2 text-left text-gray-700 hover:bg-gray-100 hover:underline"
                            >
                                {{ link.name }}
                            </button>
                            <Link
                                v-else
                                :href="link.href"
                                class="block rounded-md px-3 py-2 text-gray-700 hover:bg-gray-100 hover:underline"
                                @click="mobileMenuOpen = false"
                            >
                                {{ link.name }}
                            </Link>
                        </template>
                    </div>

                    <!-- Categories -->
                    <div class="border-b border-gray-200 pb-3">
                        <button
                            class="w-full rounded-md px-3 py-2 text-left text-gray-700 hover:bg-gray-100 hover:underline"
                            @click="showCategories = !showCategories"
                        >
                            Search by Categories
                        </button>
                        <div v-if="showCategories" class="mt-2 max-h-60 space-y-1 overflow-y-auto pr-2 pl-3">
                            <button
                                v-for="(category, index) in categoryLinks"
                                :key="'mobile-cat-' + index"
                                class="block w-full rounded-md px-2 py-1 text-left text-sm text-gray-700 hover:bg-gray-100"
                            >
                                {{ category }}
                            </button>
                        </div>
                    </div>
                </div>
            </nav>
        </transition>
    </header>

    <!-- Search Bar -->
    <div class="sticky top-[56px] z-40 w-full bg-primary py-3">
        <div class="container mx-auto flex items-center px-4 sm:px-6">
            <div class="relative mx-auto mt-2 w-full max-w-3xl">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <Search class="h-5 w-5 text-gray-400" />
                </div>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                    <Search class="h-5 w-5 text-gray-400" />
                </div>
                <input
                    type="text"
                    placeholder="Search products, brands..."
                    class="w-full rounded-md bg-white py-2 pr-10 pl-10 text-sm text-gray-700 placeholder-gray-500 shadow-sm focus:ring-2 focus:ring-secondary focus:outline-none"
                />
            </div>
        </div>
    </div>
</template>
