<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';

interface Props {
    banners: string[];
    link?: string; // optional link wrapper
}

const props = defineProps<Props>();

const currentIndex = ref(0);
const isPaused = ref(false);
const SLIDE_INTERVAL = 4000;
let intervalId: number | null = null;

const nextSlide = () => {
    currentIndex.value = (currentIndex.value + 1) % props.banners.length;
};

const startSlider = () => {
    intervalId = window.setInterval(() => {
        if (!isPaused.value) nextSlide();
    }, SLIDE_INTERVAL);
};

const stopSlider = () => {
    if (intervalId) {
        clearInterval(intervalId);
        intervalId = null;
    }
};

onMounted(startSlider);
onBeforeUnmount(stopSlider);
</script>

<template>
    <div class="relative h-[320px] w-full overflow-hidden rounded-lg shadow-md" @mouseenter="isPaused = true" @mouseleave="isPaused = false">
        <component :is="props.link ? 'a' : 'div'" :href="props.link ?? undefined" class="block h-full w-full">
            <div
                class="flex h-full w-full transition-transform duration-700 ease-in-out"
                :style="{ transform: `translateX(-${currentIndex * 100}%)` }"
            >
                <div v-for="(banner, index) in props.banners" :key="index" class="relative h-full w-full flex-shrink-0">
                    <div class="h-full w-full overflow-hidden">
                        <img :src="banner" class="h-full w-full object-cover object-center" loading="lazy" :alt="`Banner ${index + 1}`" />
                    </div>
                </div>
            </div>

            <div class="absolute bottom-4 left-1/2 z-10 flex -translate-x-1/2 transform space-x-2">
                <button
                    v-for="(_, index) in props.banners"
                    :key="index"
                    class="h-3 w-3 rounded-full transition focus:outline-none"
                    :class="{
                        'bg-secondary': currentIndex === index,
                        'bg-gray-300': currentIndex !== index,
                    }"
                    @click.stop="currentIndex = index"
                    :aria-label="`Go to slide ${index + 1}`"
                ></button>
            </div>
        </component>
    </div>
</template>
