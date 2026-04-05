<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, computed } from 'vue';

interface Props {
    banners:    string[];
    links?:     (string | null)[];
    animation?: string;
    lazy?:      boolean;
}

const props = defineProps<Props>();

const currentIndex = ref(0);
const isPaused     = ref(false);
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
    if (intervalId) { clearInterval(intervalId); intervalId = null; }
};

onMounted(startSlider);
onBeforeUnmount(stopSlider);
</script>

<template>
    <div
        class="relative h-full w-full overflow-hidden rounded-lg shadow-md"
        @mouseenter="isPaused = true"
        @mouseleave="isPaused = false"
    >
        <!-- All slides stacked, only active one visible -->
        <div
            v-for="(banner, index) in props.banners"
            :key="index"
            class="slide absolute inset-0 h-full w-full"
            :class="{ 'slide-active': currentIndex === index }"
        >
            <component
                :is="props.links?.[index] ? 'a' : 'div'"
                :href="props.links?.[index] ?? undefined"
                class="block h-full w-full"
                :class="{ 'cursor-pointer': props.links?.[index] }"
            >
                <img
                    :src="banner"
                    class="zoom-img h-full w-full object-cover object-center"
                    :loading="props.lazy ? 'lazy' : 'eager'"
                    :alt="`Banner ${index + 1}`"
                />
            </component>
        </div>

        <!-- Dot indicators -->
        <div class="absolute bottom-4 left-1/2 z-10 flex -translate-x-1/2 gap-2">
            <button
                v-for="(_, index) in props.banners"
                :key="index"
                class="h-3 w-3 rounded-full transition focus:outline-none"
                :class="currentIndex === index ? 'bg-secondary' : 'bg-white/70'"
                @click.stop="currentIndex = index"
                :aria-label="`Go to slide ${index + 1}`"
            />
        </div>
    </div>
</template>

<style scoped>
/* Each slide sits stacked, invisible by default */
.slide {
    opacity: 0;
    transition: opacity 1s ease;
    z-index: 0;
}

/* Active slide fades in on top */
.slide-active {
    opacity: 1;
    z-index: 1;
}

/* The image slowly zooms in while the slide is visible */
.zoom-img {
    animation: none;
    transform: scale(1);
}

.slide-active .zoom-img {
    animation: kenburns 5s ease-in-out forwards;
}

@keyframes kenburns {
    0%   { transform: scale(1);    }
    100% { transform: scale(1.08); }
}
</style>