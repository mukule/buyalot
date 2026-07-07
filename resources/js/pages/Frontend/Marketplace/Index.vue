<script setup lang="ts">
import { verticalIcon } from '@/components/marketplace/verticalIcons';
import MainLayout from '@/layouts/MainLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Check } from 'lucide-vue-next';

const props = defineProps<{
    verticals: { key: string; label: string; tagline: string; type: string; icon: string }[];
    active: string;
    title?: string;
}>();

const select = (key: string) => {
    router.post(route('marketplace.select'), { vertical: key }, { preserveScroll: true });
};
</script>

<template>
    <Head :title="props.title ?? 'Choose a marketplace'" />
    <MainLayout>
        <section class="mx-auto mt-6 mb-12 max-w-4xl">
            <div class="mb-8 text-center">
                <h1 class="text-2xl font-bold text-gray-900 sm:text-3xl">Choose your marketplace</h1>
                <p class="mt-2 text-sm text-gray-500 sm:text-base">
                    Pick what you're shopping for. We'll tailor the whole experience — you can switch anytime.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <button
                    v-for="v in verticals"
                    :key="v.key"
                    type="button"
                    :class="[
                        'group relative flex flex-col items-start gap-4 rounded-2xl border-2 bg-white p-6 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md',
                        v.key === active ? 'border-primary ring-2 ring-primary/20' : 'border-gray-200 hover:border-primary/60',
                    ]"
                    @click="select(v.key)"
                >
                    <span
                        v-if="v.key === active"
                        class="absolute top-3 right-3 flex h-6 w-6 items-center justify-center rounded-full bg-primary text-white"
                    >
                        <Check class="h-4 w-4" />
                    </span>

                    <span class="flex h-14 w-14 items-center justify-center rounded-xl bg-primary/10 text-primary transition group-hover:bg-primary group-hover:text-white">
                        <component :is="verticalIcon(v.icon)" class="h-7 w-7" />
                    </span>

                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ v.label }}</h2>
                        <p class="mt-1 text-sm text-gray-500">{{ v.tagline }}</p>
                    </div>

                    <span class="mt-auto pt-2 text-sm font-medium text-primary">
                        {{ v.key === active ? 'Currently selected' : 'Browse →' }}
                    </span>
                </button>
            </div>
        </section>
    </MainLayout>
</template>
