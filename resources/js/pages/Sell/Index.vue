<script setup lang="ts">
import MainLayout from '@/layouts/MainLayout.vue';
import { Link } from '@inertiajs/vue3';

import sell_on_buyalot from '@/assets/images/banners/sell-on-buyalot.png';
import start_selling_bg from '@/assets/images/bg/start-selling.jpg';
import application from '@/assets/images/icons/application.svg';
import approval from '@/assets/images/icons/approval.svg';
import growth from '@/assets/images/icons/growth.svg';
import onboarding from '@/assets/images/icons/onboarding.svg';
import registration from '@/assets/images/icons/registration.svg';
import sales from '@/assets/images/icons/sales.svg';

const cards = [
    {
        title: 'Optimise Your Growth',
        description: 'Easily boost your sales by leveraging our active customer base of over 3 million happy online shoppers.',
        image: 'https://www.takealot.com/36c5e68ec7d3ea31fd9999e79398490f69e476ec/static/media/src/images/sell-on-takealot/cards/optimise-your-growth.jpg-758bca1bd6b74237d09e.jpg',
    },
    {
        title: 'End–to–End Solutions',
        description:
            "We'll provide the tools you need to set up and sell – manage your stock, pricing, product selection and more from the Takealot Seller Portal.",
        image: 'https://www.takealot.com/36c5e68ec7d3ea31fd9999e79398490f69e476ec/static/media/src/images/sell-on-takealot/cards/end-to-end-solutions.jpg-73b9874edfdf9aeaa3b5.jpg',
    },
    {
        title: 'Hassle–Free Logistics',
        description: "From handling warehousing to delivery and returns, we've made online retail easier than ever.",
        image: 'https://www.takealot.com/36c5e68ec7d3ea31fd9999e79398490f69e476ec/static/media/src/images/sell-on-takealot/cards/hassle-free-logistics.jpg-dfeefa2f54d08b8ca3ce.jpg',
    },
    {
        title: 'Safe & Secure Payments',
        description: 'Payments are made directly to you four times per month.',
        image: 'https://www.takealot.com/36c5e68ec7d3ea31fd9999e79398490f69e476ec/static/media/src/images/sell-on-takealot/cards/safe-and-secure-payments.jpg-ed8aa4f3e9d11eefe1dc.jpg',
    },
];

const steps = [
    {
        title: 'Application',
        description: 'Apply now and tell us about your business and products.',
        icon: application,
    },
    {
        title: 'Approval',
        description: "We'll review your application and get in touch within 10 business days.",
        icon: approval,
    },
    {
        title: 'Registration',
        description: 'Complete your Takealot Seller account by supplying all the required information and paperwork.',
        icon: registration,
    },
    {
        title: 'Onboarding',
        description: 'Learn all about our processes and choose your stock model.',
        icon: onboarding,
    },
    {
        title: 'Sales',
        description: 'Get your products live and start selling.',
        icon: sales,
    },
    {
        title: 'Growth',
        description: 'Boost your online sales via promotions, analyse your performance using reports and so much more.',
        icon: growth,
    },
];
</script>

<template>
    <MainLayout>
        <template #full>
            <!-- Hero Section -->
            <section class="relative h-[400px] w-full bg-cover bg-center" :style="{ backgroundImage: `url(${sell_on_buyalot})` }">
                <div class="absolute inset-0 bg-black/10"></div>

                <div class="relative container mx-auto h-full px-4 sm:px-6 lg:px-8">
                    <div class="flex h-full items-center justify-center md:justify-start">
                        <div class="w-full rounded-lg bg-primary/70 p-6 text-center backdrop-blur-sm md:max-w-2xl md:p-8 md:text-left lg:p-10">
                            <div class="space-y-4 text-white md:space-y-6">
                                <h1 class="text-3xl leading-tight font-bold tracking-tight sm:text-4xl md:text-5xl">
                                    Join KENYA's Best Online <br class="hidden sm:block" />
                                    Marketplace Platform
                                </h1>
                                <p class="text-lg leading-relaxed md:text-xl">Sell to over 3 million happy shoppers</p>
                                <div class="flex flex-wrap justify-center gap-4 md:justify-start">
                                    <Link
                                        href="/sell/apply"
                                        class="transform rounded-md bg-white px-6 py-3 text-sm font-semibold text-primary shadow-md transition hover:scale-105 hover:bg-secondary hover:text-white md:px-8 md:py-3 md:text-base"
                                    >
                                        Apply
                                    </Link>

                                    <button
                                        class="transform rounded-md border-2 border-white px-6 py-3 text-sm font-semibold text-white transition hover:scale-105 hover:bg-white hover:text-primary md:px-8 md:py-3 md:text-base"
                                    >
                                        See Pricing
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Feature Cards Section -->
            <section class="container mx-auto my-12 px-0 px-4">
                <div class="mx-auto mb-12 max-w-5xl space-y-6 text-center">
                    <h4 class="text-2xl leading-snug font-bold tracking-tight text-gray-900 md:text-3xl lg:text-3xl">
                        Get the tools you need to increase sales and grow your business online
                    </h4>
                    <p class="mx-auto max-w-3xl text-lg leading-relaxed text-gray-600 md:text-xl">
                        Selling your products online has never been easier. Simply apply to sell as a Takealot seller today and easily reach online
                        shoppers across South Africa.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
                    <div
                        v-for="card in cards"
                        :key="card.title"
                        class="h-full overflow-hidden rounded-xl bg-white text-center shadow-md transition-all duration-300 hover:shadow-lg"
                    >
                        <img :src="card.image" :alt="card.title" class="h-48 w-full object-cover" loading="lazy" />
                        <div class="space-y-3 p-6">
                            <h3 class="text-xl font-semibold text-primary">{{ card.title }}</h3>
                            <p class="text-base leading-relaxed text-gray-600">
                                {{ card.description }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex justify-center">
                    <Link href="/sell/apply" class="rounded-md bg-primary px-8 py-3 font-semibold text-white shadow-md transition hover:bg-secondary">
                        Apply to Sell
                    </Link>
                </div>
            </section>

            <!-- Steps Section -->
            <section class="relative w-full bg-cover bg-center py-14 text-white" :style="{ backgroundImage: `url(${start_selling_bg})` }">
                <div class="absolute inset-0 bg-primary/90"></div>

                <div class="relative container mx-auto px-4 sm:px-6 lg:px-8">
                    <h2 class="mb-12 text-center text-2xl font-bold sm:text-3xl md:text-4xl">Start selling online in just a few easy steps</h2>

                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                        <div v-for="step in steps" :key="step.title" class="text-center">
                            <img :src="step.icon" :alt="step.title" class="mx-auto mb-4 h-16 w-16" />
                            <h3 class="mb-2 text-lg font-semibold">{{ step.title }}</h3>
                            <p class="text-sm leading-relaxed">{{ step.description }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Pricing Section -->
            <section class="container mx-auto my-20 px-4 sm:px-6 lg:px-8">
                <div class="mb-12 space-y-4 text-center">
                    <h2 class="text-2xl font-bold text-gray-900 sm:text-3xl md:text-4xl">Pricing</h2>
                    <p class="text-lg text-gray-600">
                        We charge a monthly subscription fee of <strong>***</strong> per seller account/month. You can choose to cancel your account
                        at any time.
                    </p>
                </div>

                <div>
                    <details class="group overflow-hidden rounded-md border border-gray-200">
                        <summary class="cursor-pointer bg-gray-50 px-6 py-4 font-semibold text-gray-800 hover:bg-gray-100">Success Fees</summary>
                        <div class="px-6 py-4 text-gray-600">To be updated.</div>
                    </details>

                    <details class="group mt-4 overflow-hidden rounded-md border border-gray-200">
                        <summary class="cursor-pointer bg-gray-50 px-6 py-4 font-semibold text-gray-800 hover:bg-gray-100">Fulfilment Fees</summary>
                        <div class="px-6 py-4 text-gray-600">To be updated.</div>
                    </details>

                    <details class="group mt-4 overflow-hidden rounded-md border border-gray-200">
                        <summary class="cursor-pointer bg-gray-50 px-6 py-4 font-semibold text-gray-800 hover:bg-gray-100">Storage Fees</summary>
                        <div class="px-6 py-4 text-gray-600">To be updated.</div>
                    </details>
                </div>
            </section>
        </template>
    </MainLayout>
</template>
