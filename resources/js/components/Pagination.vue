<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

type PaginationLink = {
  url: string | null;
  label: string;
  active: boolean;
};

const props = defineProps<{
  links: PaginationLink[] | undefined | null;
  class?: string;
}>();

// Normalize labels coming from Laravel (they can include HTML entities)
const normalizedLinks = computed(() => {
  const links = props.links ?? [];
  return links.map((l) => ({
    ...l,
    // Remove surrounding HTML if any and keep as-is for v-html
    label: String(l.label),
  }));
});
</script>

<template>
  <nav v-if="normalizedLinks.length > 0" :class="['flex items-center justify-between', $attrs.class]">
    <div class="flex flex-1 items-center justify-between">
      <div class="relative z-0 inline-flex -space-x-px rounded-md shadow-sm">
        <component
          v-for="(link, i) in normalizedLinks"
          :is="link.url ? Link : 'span'"
          :key="i"
          :href="link.url || undefined"
          class="relative inline-flex select-none items-center border px-3 py-1 text-sm"
          :class="[
            link.active
              ? 'z-10 border-green-400 bg-indigo-50 text-green-600'
              : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50',
            !link.url && !link.active ? 'cursor-default opacity-50' : ''
          ]"
        >
          <span v-html="link.label" />
        </component>
      </div>
    </div>
  </nav>
</template>
