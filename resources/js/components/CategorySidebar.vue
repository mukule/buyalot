<script setup lang="ts">
interface Props {
    categories: string[];
}

const props = defineProps<Props>();
</script>

<template>
    <aside class="w-full rounded-lg bg-white px-6 py-6 shadow-md lg:w-[20.83%]">
        <ul class="space-y-1">
            <li v-for="(category, index) in props.categories" :key="index">
                <a href="#" class="text-sm text-gray-700 transition hover:text-blue-600">
                    {{ category }}
                </a>
            </li>
        </ul>
    </aside>
</template>
