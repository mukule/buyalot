<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
const appName = usePage().props.appName || 'Buyalot';
</script>

<template>
    <footer class="border-t border-white/20 bg-[color:var(--primary)] px-4 pt-12 pb-6 text-[color:var(--primary-foreground)] sm:px-6 md:px-8">
        <div class="mx-auto grid max-w-7xl grid-cols-2 gap-8 sm:grid-cols-3 md:grid-cols-5">
            
            <div>
                <h4 class="mb-3 font-semibold text-white">Shop</h4>
                <ul class="space-y-1 text-sm">
                    <li><a href="#" class="text-white hover:underline">Daily Deals</a></li>
                    <li><a href="#" class="text-white hover:underline">App Only Deals</a></li>
                    <li><a href="#" class="text-white hover:underline">Clearance Sale</a></li>
                    <li><a href="#" class="text-white hover:underline">Gift Vouchers</a></li>
                </ul>
            </div>

            
            <div>
                <h4 class="mb-3 font-semibold text-white">Account</h4>
                <ul class="space-y-1 text-sm">
                    <li><a href="#" class="text-white hover:underline">My Account</a></li>
                    <li><a href="#" class="text-white hover:underline">Track Order</a></li>
                    <li><a href="#" class="text-white hover:underline">Returns</a></li>
                    <li><a href="#" class="text-white hover:underline">Personal Details</a></li>
                    <li><a href="#" class="text-white hover:underline">Invoices</a></li>
                    <li><a href="#" class="text-white hover:underline">BuyalotMORE</a></li>
                </ul>
            </div>

           
            <div>
                <h4 class="mb-3 font-semibold text-white">Help</h4>
                <ul class="space-y-1 text-sm">
                    <li><a href="#" class="text-white hover:underline">Help Centre</a></li>
                    <li><a href="#" class="text-white hover:underline">Contact Us</a></li>
                    <li><a href="#" class="text-white hover:underline">Submit an Idea</a></li>
                    <li><a href="#" class="text-white hover:underline">Suggest a Product</a></li>
                    <li><a href="#" class="text-white hover:underline">Shipping & Delivery</a></li>
                    <li><a href="#" class="text-white hover:underline">Takealot Pickup Points</a></li>
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
                    <li><a href="#" class="text-white hover:underline">Sell on Takealot</a></li>
                    <li><a href="#" class="text-white hover:underline">Deliver for Takealot</a></li>
                    <li><a href="#" class="text-white hover:underline">Press & News</a></li>
                    <li><a href="#" class="text-white hover:underline">Competitions</a></li>
                    <li><a href="#" class="text-white hover:underline">B2B</a></li>
                    <li><a href="#" class="text-white hover:underline">Mr D</a></li>
                </ul>
            </div>

            <!-- Policy -->
            <div>
                <h4 class="mb-3 font-semibold text-white">Policy</h4>
                <ul class="space-y-1 text-sm">
                    <li><a href="#" class="text-white hover:underline">Returns Policy</a></li>
                    <li><a href="#" class="text-white hover:underline">Terms & Conditions</a></li>
                    <li><a href="#" class="text-white hover:underline">Privacy Policy</a></li>
                    <li><a href="#" class="text-white hover:underline">Human Rights Statement</a></li>
                    <li><a href="#" class="text-white hover:underline">Code of Advertising</a></li>
                    <li><a href="#" class="text-white hover:underline">Speak Up Process</a></li>
                </ul>
            </div>
        </div>

        <!-- Footer bottom -->
        <div class="mt-10 border-t border-white/20 pt-6 text-center text-sm text-white">
            &copy; {{ new Date().getFullYear() }} {{ appName }}. All rights reserved.
        </div>
    </footer>
</template>
