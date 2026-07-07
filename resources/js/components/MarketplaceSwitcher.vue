<script setup lang="ts">
import { verticalIcon } from '@/components/marketplace/verticalIcons';
import { router, usePage } from '@inertiajs/vue3';
import { Check, ChevronDown } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = withDefaults(defineProps<{ variant?: 'dropdown' | 'list' }>(), {
    variant: 'dropdown',
});

const emit = defineEmits<{ (e: 'selected'): void }>();

const page = usePage();

interface Vertical {
    key: string;
    label: string;
    tagline: string;
    type: string;
    icon: string;
}

const verticals = computed<Vertical[]>(() => ((page.props as any).marketplace?.verticals ?? []) as Vertical[]);
const activeKey = computed<string>(() => (page.props as any).marketplace?.active ?? 'ecommerce');
const activeVertical = computed<Vertical | undefined>(
    () => verticals.value.find((v) => v.key === activeKey.value) ?? verticals.value[0],
);

const open = ref(false);
const root = ref<HTMLElement | null>(null);

const select = (key: string) => {
    open.value = false;
    emit('selected');
    if (key === activeKey.value) return;
    router.post(route('marketplace.select'), { vertical: key }, { preserveScroll: true });
};

const onClickOutside = (e: MouseEvent) => {
    if (root.value && !root.value.contains(e.target as Node)) open.value = false;
};
onMounted(() => document.addEventListener('click', onClickOutside));
onBeforeUnmount(() => document.removeEventListener('click', onClickOutside));
</script>

<template>
    <!-- Inline list (mobile menu) -->
    <div v-if="variant === 'list'" class="space-y-1">
        <p class="px-3 pb-1 text-xs font-semibold tracking-wide text-gray-400 uppercase">Marketplace</p>
        <button
            v-for="v in verticals"
            :key="v.key"
            type="button"
            class="flex w-full items-center gap-3 rounded-md px-3 py-2 text-left text-sm hover:bg-gray-100"
            :class="v.key === activeKey ? 'font-semibold text-primary' : 'text-gray-700'"
            @click="select(v.key)"
        >
            <component :is="verticalIcon(v.icon)" class="h-5 w-5 flex-none" />
            <span class="flex-1">{{ v.label }}</span>
            <Check v-if="v.key === activeKey" class="h-4 w-4 text-primary" />
        </button>
    </div>

    <!-- Dropdown (desktop) -->
    <div v-else ref="root" class="relative">
        <button
            type="button"
            class="flex items-center gap-2 rounded-full border border-gray-200 bg-white px-3 py-1.5 text-sm text-gray-700 transition hover:border-primary/50 hover:text-primary"
            :aria-expanded="open"
            @click.stop="open = !open"
        >
            <component :is="verticalIcon(activeVertical?.icon)" class="h-4 w-4 text-primary" />
            <span class="max-w-[9rem] truncate font-medium">{{ activeVertical?.label }}</span>
            <ChevronDown class="h-4 w-4 text-gray-400 transition" :class="open ? 'rotate-180' : ''" />
        </button>

        <transition name="fade">
            <div
                v-if="open"
                class="absolute left-0 z-50 mt-2 w-72 overflow-hidden rounded-xl border border-gray-100 bg-white shadow-lg"
            >
                <p class="border-b border-gray-100 px-4 py-2 text-xs font-semibold tracking-wide text-gray-400 uppercase">
                    Choose marketplace
                </p>
                <button
                    v-for="v in verticals"
                    :key="v.key"
                    type="button"
                    class="flex w-full items-start gap-3 px-4 py-3 text-left transition hover:bg-gray-50"
                    @click="select(v.key)"
                >
                    <span
                        class="flex h-9 w-9 flex-none items-center justify-center rounded-lg"
                        :class="v.key === activeKey ? 'bg-primary text-white' : 'bg-primary/10 text-primary'"
                    >
                        <component :is="verticalIcon(v.icon)" class="h-5 w-5" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="flex items-center gap-2">
                            <span class="text-sm font-semibold text-gray-800">{{ v.label }}</span>
                            <Check v-if="v.key === activeKey" class="h-4 w-4 text-primary" />
                        </span>
                        <span class="block truncate text-xs text-gray-500">{{ v.tagline }}</span>
                    </span>
                </button>
            </div>
        </transition>
    </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.15s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
