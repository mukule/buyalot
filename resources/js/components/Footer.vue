<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { ChevronDown, ChevronUp } from 'lucide-vue-next';
import { ref } from 'vue';

// Type for customer policies
interface CustomerPolicy {
    title: string;
    slug: string;
}

const page = usePage();

// Explicitly tell TS that customerPolicies is CustomerPolicy[]
const customerPolicies = (page.props.customerPolicies as CustomerPolicy[]) ?? [];

const appName = page.props.appName || 'Bianlina';

// On mobile the footer links are collapsed by default and can be toggled.
// On md+ screens the footer is always fully visible (button hidden).
const showFooter = ref(false);
</script>

<template>
    <footer class="border-t border-white/20 bg-[color:var(--primary)] px-4 pt-6 pb-6 text-[color:var(--primary-foreground)] sm:px-6 md:px-8 md:pt-12">
        <!-- Mobile toggle: show/hide the footer links -->
        <button
            type="button"
            class="mx-auto mb-2 flex w-full max-w-7xl items-center justify-between text-white md:hidden"
            :aria-expanded="showFooter"
            aria-controls="footer-links"
            @click="showFooter = !showFooter"
        >
            <span class="font-semibold">Footer &amp; info</span>
            <ChevronUp v-if="showFooter" class="h-5 w-5" />
            <ChevronDown v-else class="h-5 w-5" />
        </button>

        <div
            id="footer-links"
            class="mx-auto max-w-7xl grid-cols-2 gap-8 sm:grid-cols-3 md:grid md:grid-cols-5"
            :class="showFooter ? 'grid' : 'hidden'"
        >
            <!-- Shop -->
            <div>
                <h4 class="mb-3 font-semibold text-white">Shop</h4>
                <ul class="space-y-1 text-sm">
                    <li><a href="#" class="text-white hover:underline">Daily Deals</a></li>
                    <li><a href="#" class="text-white hover:underline">App Only Deals</a></li>
                    <li><a href="#" class="text-white hover:underline">Clearance Sale</a></li>
                    <li><a href="#" class="text-white hover:underline">Gift Vouchers</a></li>
                </ul>
            </div>

            <!-- Account -->
            <div>
                <h4 class="mb-3 font-semibold text-white">Account</h4>
                <ul class="space-y-1 text-sm">
                    <li><a href="#" class="text-white hover:underline">My Account</a></li>
                    <li><a href="#" class="text-white hover:underline">Track Order</a></li>
                    <li><a href="#" class="text-white hover:underline">Returns</a></li>
                    <li><a href="#" class="text-white hover:underline">Personal Details</a></li>
                    <li><a href="#" class="text-white hover:underline">Invoices</a></li>
                    <li><a href="#" class="text-white hover:underline">Bianlina MORE</a></li>
                </ul>
            </div>

            <!-- Help -->
            <div>
                <h4 class="mb-3 font-semibold text-white">Help</h4>
                <ul class="space-y-1 text-sm">
                    <li><a href="#" class="text-white hover:underline">Help Centre</a></li>
                    <li><a href="#" class="text-white hover:underline">Contact Us</a></li>
                    <li><a href="#" class="text-white hover:underline">Submit an Idea</a></li>
                    <li><a href="#" class="text-white hover:underline">Suggest a Product</a></li>
                    <li><a href="#" class="text-white hover:underline">Shipping & Delivery</a></li>
                    <li><a href="#" class="text-white hover:underline">Returns</a></li>
                    <li><a href="#" class="text-white hover:underline">Log IP Complaint</a></li>
                </ul>
            </div>

            <!-- Company -->
            <div>
                <h4 class="mb-3 font-semibold text-white">Company</h4>
                <ul class="space-y-1 text-sm">
                    <li><a href="#" class="text-white hover:underline">About Us</a></li>
                    <li><a href="#" class="text-white hover:underline">Careers</a></li>
                    <li><a href="/sell" class="text-white hover:underline">Sell on Bianlina</a></li>
                    <li><a href="/delivery/register" class="text-white hover:underline">Deliver for Bianlina</a></li>
                    <li><a href="#" class="text-white hover:underline">Press & News</a></li>
                    <li><a href="#" class="text-white hover:underline">Competitions</a></li>
                    <li><a href="#" class="text-white hover:underline">B2B</a></li>
                </ul>
            </div>

            <!-- Policies -->
            <div>
                <h4 class="mb-3 font-semibold text-white">Policy</h4>
                <ul class="space-y-1 text-sm text-white">
                    <li v-for="policy in customerPolicies" :key="policy.slug" class="truncate">
                        <a :href="`/policies/${policy.slug}`" class="text-white hover:underline"> {{ policy.title }} </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Footer bottom -->
        <div class="mt-4 border-t border-white/20 pt-6 text-center text-sm text-white md:mt-10">
            &copy; {{ new Date().getFullYear() }} {{ appName }}. All rights reserved.
        </div>
    </footer>
</template>

<style scoped>
.truncate {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
</style>
