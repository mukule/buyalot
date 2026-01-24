<script setup lang="ts">
import logo from '@/assets/images/logo.png';
import { Link, router, usePage } from '@inertiajs/vue3';
import { ChevronDown, ChevronUp, Heart, Menu, Search, ShoppingCart, X } from 'lucide-vue-next';
import { computed, inject, ref } from 'vue';

import type { AppPageProps } from '@/types';

// --- Types
interface Category {
    id: number;
    name: string;
    slug?: string;
    children?: Category[];
}

// --- Page & auth
const page = usePage<AppPageProps>();
const user = computed(() => page.props.auth?.user);
const customerId = computed(() => page.props.auth?.customer_id);

// Wishlist & Cart counts
const wishlistCount = computed(() => page.props.auth?.wishlistVariantIds?.length ?? 0);
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

// Menu states
const mobileMenuOpen = ref(false);
const showCategories = ref(false);
const expandedCategories = ref<Record<number, boolean>>({});

// --- Categories
const categories = computed<Category[]>(() => {
    const raw = (page.props as any).categories;
    return Array.isArray(raw) ? (raw as Category[]) : [];
});
const mobileCategories = computed(() => categories.value.slice(0, 10));

// Top links
const topLinks = [
    { name: 'Help Center', href: '/help' },
    { name: 'Sell on Buyalot', href: '/sell' },
    { name: 'Vendor Login', href: '/vendor/login' },
];

// Routes and auth
const route = inject<((name: string, params?: any) => string) | undefined>('route');
const authLinks = computed(() => {
    if (user.value) {
        const dashboardUrl =
            customerId.value && route
                ? route('customers.dashboard', { customer: customerId.value })
                : route
                  ? route('admin.dashboard')
                  : '/admin/dashboard';
        return [
            { name: 'My Account', href: dashboardUrl, isUser: true },
            { name: 'Orders', href: route ? route('orders.index') : '/orders/my-orders' },
            { name: 'Logout', href: '/logout', isLogout: true },
        ];
    } else {
        return [
            { name: 'Orders', href: route ? route('orders.index') : '/orders/my-orders' },
            { name: 'Login', href: '/login' },
            { name: 'Register', href: '/register' },
        ];
    }
});

const wishlistUrl = computed(() => (route ? route('wishlist.index') : '/wishlist'));
const cartUrl = computed(() => (route ? route('cart.index') : '/cart'));

// --- Search
const searchQuery = ref('');
const suggestions = ref<any[]>([]);
const showSuggestions = ref(false);
const activeIndex = ref(-1);
let suggestTimer: any = null;

async function fetchSuggestions(q: string) {
    try {
        const url = `/search?ajax=1&q=${encodeURIComponent(q)}&per_page=5`;
        const res = await fetch(url, { headers: { Accept: 'application/json' } });
        const json = await res.json();
        suggestions.value = json.results?.data ?? [];
        showSuggestions.value = suggestions.value.length > 0;
        activeIndex.value = -1;
    } catch (e) {
        suggestions.value = [];
        showSuggestions.value = false;
    }
}

function onSearchInput() {
    const q = searchQuery.value.trim();
    clearTimeout(suggestTimer);
    if (q.length < 2) {
        suggestions.value = [];
        showSuggestions.value = false;
        return;
    }
    suggestTimer = setTimeout(() => fetchSuggestions(q), 250);
}

function submitSearch(selected: any = null) {
    const q = selected?.name || searchQuery.value.trim();
    if (!q) return;
    showSuggestions.value = false;
    router.get('/search', { q }, { preserveScroll: true });
}

function onKeyDown(e: KeyboardEvent) {
    if (!showSuggestions.value) return;
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        activeIndex.value = (activeIndex.value + 1) % suggestions.value.length;
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        activeIndex.value = (activeIndex.value - 1 + suggestions.value.length) % suggestions.value.length;
    } else if (e.key === 'Enter' && activeIndex.value >= 0) {
        e.preventDefault();
        submitSearch(suggestions.value[activeIndex.value]);
    } else if (e.key === 'Escape') {
        showSuggestions.value = false;
    }
}

// Highlight search match
function highlightMatch(text: string, query: string) {
    if (!query) return text;
    const regex = new RegExp(`(${query})`, 'gi');
    return text.replace(regex, '<mark class="bg-yellow-200">$1</mark>');
}

// --- Menu toggles
function toggleMobileMenu() {
    mobileMenuOpen.value = !mobileMenuOpen.value;
}
function logout() {
    router.post('/logout', {}, { preserveScroll: true });
}
function toggleCategory(catId: number) {
    expandedCategories.value[catId] = !expandedCategories.value[catId];
}
</script>

<template>
    <header class="fixed top-0 left-0 z-50 w-full bg-white shadow-md">
        <div class="container mx-auto flex items-center justify-between px-4 py-3 sm:px-6">
            <!-- Logo -->
            <div class="flex items-center space-x-6">
                <Link href="/">
                    <img :src="logo" alt="Logo" class="h-10 w-auto" />
                </Link>
                <nav class="hidden space-x-4 text-sm md:flex">
                    <template v-for="(link, index) in topLinks" :key="link.name">
                        <Link :href="link.href" class="text-gray-500 hover:underline">{{ link.name }}</Link>
                        <span v-if="index < topLinks.length - 1" class="text-gray-400">|</span>
                    </template>
                </nav>
            </div>

            <!-- Desktop Nav -->
            <nav class="hidden items-center space-x-4 text-sm md:flex">
                <template v-for="(link, index) in authLinks" :key="link.name">
                    <button v-if="link.isLogout" @click.prevent="logout" class="cursor-pointer text-gray-500 hover:underline">
                        {{ link.name }}
                    </button>
                    <Link v-else-if="!link.isUser" :href="link.href" class="text-gray-500 hover:underline">
                        {{ link.name }}
                    </Link>
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

                <Link :href="wishlistUrl" class="relative flex items-center justify-center rounded-full bg-secondary p-2">
                    <Heart class="h-4 w-4 text-white" />
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
            </nav>

            <!-- Mobile Controls -->
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
                    class="inline-flex items-center justify-center rounded-md p-2 text-gray-700 hover:bg-gray-100 focus:ring-2 focus:ring-secondary"
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

                    <!-- Dynamic Categories -->
                    <div class="border-b border-gray-200 pb-3">
                        <button
                            class="w-full rounded-md px-3 py-2 text-left text-gray-700 hover:bg-gray-100 hover:underline"
                            @click="showCategories = !showCategories"
                        >
                            Browse Categories
                        </button>

                        <div v-if="showCategories" class="mt-2 max-h-80 overflow-y-auto pr-2 pl-1">
                            <template v-for="cat in mobileCategories" :key="'mobile-cat-' + cat.id">
                                <div>
                                    <button
                                        class="flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100"
                                        @click="toggleCategory(cat.id)"
                                    >
                                        <span>{{ cat.name }}</span>
                                        <component :is="expandedCategories[cat.id] ? ChevronUp : ChevronDown" class="h-4 w-4 text-gray-500" />
                                    </button>

                                    <transition name="slide-fade">
                                        <div
                                            v-if="expandedCategories[cat.id] && cat.children && cat.children.length"
                                            class="ml-4 border-l border-gray-200 pl-3"
                                        >
                                            <Link
                                                v-for="child in cat.children"
                                                :key="'mobile-subcat-' + child.id"
                                                :href="`/category/${child.id}`"
                                                class="block rounded-md px-2 py-1 text-sm text-gray-600 hover:bg-gray-50"
                                                @click="mobileMenuOpen = false"
                                            >
                                                - {{ child.name }}
                                            </Link>
                                        </div>
                                    </transition>
                                </div>
                            </template>

                            <button
                                v-if="categories.length > 10"
                                class="block w-full px-3 py-2 text-sm text-primary hover:underline"
                                @click="router.visit('/categories')"
                            >
                                View all categories
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
                <input
                    v-model="searchQuery"
                    @input="onSearchInput"
                    @focus="onSearchInput"
                    @keydown="onKeyDown"
                    @keyup.enter.prevent="submitSearch"
                    type="text"
                    placeholder="Search products, brands..."
                    class="w-full rounded-md bg-white py-2 pr-10 pl-10 text-sm text-gray-700 placeholder-gray-500 shadow-sm focus:ring-2 focus:ring-secondary focus:outline-none"
                />

                <!-- Suggestions Dropdown -->
                <div v-if="showSuggestions" class="absolute z-50 mt-2 max-h-80 w-full overflow-auto rounded-md border bg-white shadow">
                    <div
                        v-for="(s, i) in suggestions"
                        :key="s.id"
                        @mousedown.prevent="submitSearch(s)"
                        :class="['flex cursor-pointer items-center gap-3 p-2 hover:bg-gray-50', { 'bg-gray-100': i === activeIndex }]"
                    >
                        <img :src="s.primary_image_url || '/fallback-image.png'" alt="" class="h-10 w-10 flex-none rounded object-contain" />
                        <div class="min-w-0 text-sm">
                            <div v-html="highlightMatch(s.name, searchQuery)" class="truncate text-gray-800"></div>
                            <div v-if="s.brand" class="truncate text-xs text-gray-500">{{ s.brand }}</div>
                        </div>
                    </div>
                    <div class="border-t p-2 text-center">
                        <button class="text-sm text-primary hover:underline" @mousedown.prevent="submitSearch">See all results</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.slide-fade-enter-active,
.slide-fade-leave-active {
    transition: all 0.25s ease;
}
.slide-fade-enter-from,
.slide-fade-leave-to {
    opacity: 0;
    max-height: 0;
    transform: translateY(-4px);
}
</style>
