<script setup lang="ts">
import logo from '@/assets/images/logo.png';
import { Link, router } from '@inertiajs/vue3';
import { Heart, Menu, Search, ShoppingCart, X } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { PageProps as InertiaPageProps } from '@inertiajs/core';



import type { AppPageProps } from '@/types';
const page = usePage<AppPageProps>();
const user = computed(() => page.props.auth?.user);

const userInitials = computed(() => {
  if (!user.value?.name) return '';
  return user.value.name
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase();
});

const browseDropdownOpen = ref(false);
const userDropdownOpen = ref(false);
const mobileMenuOpen = ref(false);
const showCategories = ref(false);

const browseCloseTimeout = ref<number | null>(null);
const userCloseTimeout = ref<number | null>(null);

const topLinks = [
    { name: 'Help Center', href: '/help' },
    { name: 'Sell on Buyalot', href: '/sell' },
];

const authLinks = computed(() => {
  if (user.value) {
    return [
     
      { name: 'My Account', href: '/account', isUser: true },
       { name: 'Orders', href: '/orders' },
      { name: 'Logout', href: '/logout', isLogout: true },
    ];
  } else {
    return [
         { name: 'Orders', href: '/orders' },
      { name: 'Login', href: '/login' },
      { name: 'Register', href: '/register' },
    ];
  }
});

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

function openDropdown(dropdown: 'browse' | 'user') {
    const timeoutRef = dropdown === 'browse' ? browseCloseTimeout : userCloseTimeout;
    clearTimeout(timeoutRef.value!);

    if (dropdown === 'browse') {
        browseDropdownOpen.value = true;
    } else {
        userDropdownOpen.value = true;
    }
}

function closeDropdownWithDelay(dropdown: 'browse' | 'user') {
    const timeout = window.setTimeout(() => {
        if (dropdown === 'browse') {
            browseDropdownOpen.value = false;
        } else {
            userDropdownOpen.value = false;
        }
    }, 300);

    if (dropdown === 'browse') {
        browseCloseTimeout.value = timeout;
    } else {
        userCloseTimeout.value = timeout;
    }
}

function toggleMobileMenu() {
    mobileMenuOpen.value = !mobileMenuOpen.value;
}

function logout() {
    router.post('/logout', {}, { preserveScroll: true });
}
</script>

<template>
    <!-- Top Navigation Bar -->
    <header class="fixed top-0 left-0 z-50 w-full bg-white shadow-md">
        <div class="container mx-auto flex items-center justify-between px-4 py-3 sm:px-6">
            <!-- Logo and Left Links -->
            <div class="flex items-center space-x-6">
                <Link href="/">
                    <img :src="logo" alt="App Logo" class="h-10 w-auto" />
                </Link>

                <!-- Desktop Top Links -->
                <nav class="hidden space-x-4 text-sm md:flex">
                    <template v-for="(link, index) in topLinks" :key="link.name">
                        <Link :href="link.href" class="text-gray-500 hover:underline">
                            {{ link.name }}
                        </Link>
                        <span v-if="index < topLinks.length - 1" class="text-gray-400">|</span>
                    </template>
                </nav>
            </div>

            <!-- Desktop Right Side Navigation -->
            <nav class="hidden items-center space-x-4 text-sm md:flex">
                <!-- Auth Links -->
                <template v-for="(link, index) in authLinks" :key="link.name">
                    <button
                      v-if="link.isLogout"
                      @click.prevent="logout"
                      class="text-gray-500 hover:underline cursor-pointer"
                    >
                      {{ link.name }}
                    </button>

                    <Link
                      v-else-if="!link.isUser"
                      :href="link.href"
                      class="text-gray-500 hover:underline"
                    >
                      {{ link.name }}
                    </Link>

                    <Link
                      v-else
                      :href="link.href"
                      class="flex items-center space-x-2 text-gray-700 hover:underline"
                    >
                      <div
                        class="flex h-7 w-7 items-center justify-center rounded-full bg-primary text-white text-xs font-semibold"
                        :title="user?.name"
                      >
                        {{ userInitials }}
                      </div>
                      <span>My Account</span>
                    </Link>
                    <span
                      v-if="index < authLinks.length - 1"
                      class="text-gray-400"
                    >|</span>
                </template>

                <!-- Icons -->
                <button class="relative flex items-center justify-center rounded-full bg-secondary p-2">
                    <Heart class="h-4 w-4 text-white" />
                </button>

                <button class="relative">
                    <ShoppingCart class="h-6 w-6 text-primary" />
                    <span class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] text-white">
                        0
                    </span>
                </button>
            </nav>

            <!-- Mobile Right Controls -->
            <div class="flex items-center space-x-4 md:hidden">
                <button class="relative" aria-label="Shopping cart">
                    <ShoppingCart class="h-6 w-6 text-primary" />
                    <span class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] text-white">
                        0
                    </span>
                </button>

                <button
                    @click="toggleMobileMenu"
                    class="inline-flex items-center justify-center rounded-md p-2 text-gray-700 hover:bg-gray-100 focus:ring-2 focus:ring-secondary focus:outline-none focus:ring-inset"
                    aria-label="Toggle mobile menu"
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
                            <template v-if="link.isLogout">
                              <button
                                @click.prevent="logout"
                                class="block w-full rounded-md px-3 py-2 text-gray-700 hover:bg-gray-100 hover:underline text-left"
                              >
                                {{ link.name }}
                              </button>
                            </template>

                            <template v-else-if="!link.isUser">
                              <Link
                                :href="link.href"
                                class="block rounded-md px-3 py-2 text-gray-700 hover:bg-gray-100 hover:underline"
                                @click="mobileMenuOpen = false"
                              >
                                {{ link.name }}
                              </Link>
                            </template>

                            <template v-else>
                              <Link
                                :href="link.href"
                                class="flex items-center space-x-2 rounded-md px-3 py-2 text-gray-700 hover:bg-gray-100 hover:underline"
                                @click="mobileMenuOpen = false"
                              >
                                <div
                                  class="flex h-7 w-7 items-center justify-center rounded-full bg-primary text-white text-xs font-semibold"
                                  :title="user?.name"
                                >
                                  {{ userInitials }}
                                </div>
                                <span>My Account</span>
                              </Link>
                            </template>
                        </template>
                    </div>

                    <!-- Search by Categories Toggle -->
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

                    <!-- Icons -->
                    <div class="flex space-x-6 px-3">
                        <button class="flex items-center justify-center rounded-full bg-secondary p-2" aria-label="Favorites">
                            <Heart class="h-5 w-5 text-white" />
                        </button>

                        <button class="relative" aria-label="Shopping cart">
                            <ShoppingCart class="h-6 w-6 text-primary" />
                            <span
                                class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] text-white"
                            >
                                0
                            </span>
                        </button>
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
