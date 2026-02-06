<script setup lang="ts">
import MainLayout from '@/layouts/MainLayout.vue';
import { usePage } from '@inertiajs/vue3';

interface Policy {
    title: string;
    content: string;
    version: number;
    effective_from: string;
}

const page = usePage();
const policy = page.props.policy as Policy;
</script>

<template>
    <MainLayout>
        <section class="mx-auto my-4 w-full max-w-5xl px-4">
            <div class="rounded-lg bg-white p-8 shadow">
                <!-- Header -->
                <div class="mb-6 flex items-center justify-between">
                    <h1 class="text-3xl font-bold text-gray-900">
                        {{ policy.title }}
                    </h1>

                    <a :href="route('home')" class="text-sm text-primary underline hover:text-primary/80"> &larr; Back to Home </a>
                </div>

                <!-- Content -->
                <div class="prose prose-lg prose-headings:font-semibold prose-p:text-gray-700 max-w-none" v-html="policy.content"></div>
            </div>
        </section>
    </MainLayout>
</template>
