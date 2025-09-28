<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { AlertCircle, CheckCircle, Info, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface FlashMessages {
    success?: string;
    error?: string;
    info?: string;
}

const page = usePage();
const flash = computed<FlashMessages>(() => page.props.flash || {});

const visible = ref(false);
const type = ref<'success' | 'error' | 'info' | null>(null);
const message = ref('');

watch(
    () => flash.value,
    (val) => {
        if (val.success) {
            type.value = 'success';
            message.value = val.success;
        } else if (val.error) {
            type.value = 'error';
            message.value = val.error;
        } else if (val.info) {
            type.value = 'info';
            message.value = val.info;
        } else {
            type.value = null;
            message.value = '';
        }

        if (message.value) {
            visible.value = true;
            setTimeout(() => {
                visible.value = false;
            }, 4000);
        }
    },
    { immediate: true, deep: true },
);
</script>
<template>
    <transition name="fade">
        <div
            v-if="visible && message"
            class="fixed top-4 right-4 z-50 w-[90%] max-w-sm rounded-lg border-l-4 bg-white p-5 shadow-lg ring-1 ring-black/5"
            :class="{
                'border-green-500': type === 'success',
                'border-red-500': type === 'error',
                'border-blue-500': type === 'info',
            }"
        >
            <div class="flex items-start space-x-3">
                <!-- Icon -->
                <div class="pt-0.5">
                    <CheckCircle v-if="type === 'success'" class="h-6 w-6 text-green-500" />
                    <AlertCircle v-if="type === 'error'" class="h-6 w-6 text-red-500" />
                    <Info v-if="type === 'info'" class="h-6 w-6 text-blue-500" />
                </div>

                <!-- Message -->
                <div class="flex-1 text-sm text-gray-800">
                    {{ message }}
                </div>

                <!-- Close Button -->
                <button @click="visible = false" class="text-gray-400 transition hover:text-gray-600">
                    <X class="h-4 w-4" />
                </button>
            </div>
        </div>
    </transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
