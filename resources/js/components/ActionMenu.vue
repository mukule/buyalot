<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, nextTick, watch } from 'vue';

type MenuItem = {
  label: string;
  icon?: any; // Vue component
  onClick?: () => void;
  danger?: boolean;
  disabled?: boolean;
};

const props = defineProps<{
  items: MenuItem[];
  align?: 'left' | 'right';
  zIndex?: number;
  buttonClass?: string;
  menuClass?: string;
}>();

const isOpen = ref(false);
const triggerRef = ref<HTMLElement | null>(null);
const menuRef = ref<HTMLElement | null>(null);
const position = ref<{ top: number; left: number }>({ top: 0, left: 0 });

function open() {
  isOpen.value = true;
  nextTick(updatePosition);
}
function close() {
  isOpen.value = false;
}
function toggle() {
  if (isOpen.value) {
    close();
  } else {
    open();
  }
}

function updatePosition() {
  const btn = triggerRef.value;
  if (!btn) return;
  const rect = btn.getBoundingClientRect();
  const margin = 8;
  const top = rect.bottom + window.scrollY + margin;
  let left = rect.left + window.scrollX;
  const menuWidth = 176; // w-44 ≈ 176px
  if ((props.align ?? 'right') === 'right') {
    left = rect.right + window.scrollX - menuWidth;
  }
  // Flip if overflowing viewport
  const viewportWidth = document.documentElement.clientWidth;
  if (left + menuWidth > viewportWidth - margin) {
    left = viewportWidth - margin - menuWidth;
  }
  if (left < margin) left = margin;
  position.value = { top, left };
}

function onClickOutside(e: MouseEvent) {
  const path = e.composedPath() as EventTarget[];
  if (triggerRef.value && path.includes(triggerRef.value)) return;
  if (menuRef.value && path.includes(menuRef.value)) return;
  close();
}

function onKeydown(e: KeyboardEvent) {
  if (!isOpen.value) return;
  if (e.key === 'Escape') {
    e.stopPropagation();
    close();
    triggerRef.value?.focus();
  } else if (e.key === 'ArrowDown') {
    e.preventDefault();
    const first = menuRef.value?.querySelector<HTMLElement>('button:not([disabled])');
    first?.focus();
  }
}

onMounted(() => {
  document.addEventListener('click', onClickOutside);
  window.addEventListener('resize', updatePosition);
  window.addEventListener('scroll', updatePosition, true);
  document.addEventListener('keydown', onKeydown);
});
onBeforeUnmount(() => {
  document.removeEventListener('click', onClickOutside);
  window.removeEventListener('resize', updatePosition);
  window.removeEventListener('scroll', updatePosition, true);
  document.removeEventListener('keydown', onKeydown);
});

watch(isOpen, (val) => {
  if (val) nextTick(updatePosition);
});

function handleItemClick(item: MenuItem) {
  if (item.disabled) return;
  close();
  item.onClick?.();
}
</script>

<template>
  <div class="inline-block text-left">
    <button
      ref="triggerRef"
      type="button"
      :class="buttonClass || 'inline-flex items-center gap-1 rounded-md border border-gray-300 px-2 py-1 text-sm text-gray-700 hover:bg-gray-50'"
      @click.stop="toggle"
      aria-haspopup="menu"
      :aria-expanded="isOpen"
    >
      <slot name="button">Actions</slot>
    </button>

    <teleport to="body">
      <div
        v-if="isOpen"
        :style="{ top: position.top + 'px', left: position.left + 'px', zIndex: (zIndex ?? 50).toString() }"
        ref="menuRef"
        role="menu"
        :class="['absolute w-44 origin-top-right rounded-md bg-white shadow-xl ring-1 ring-black/5', menuClass]"
      >
        <div class="py-1 text-sm text-gray-700">
          <button
            v-for="(item, idx) in items"
            :key="idx"
            type="button"
            :disabled="item.disabled"
            @click.stop="handleItemClick(item)"
            class="flex w-full items-center gap-2 px-3 py-2 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            :class="item.danger ? 'text-red-600 hover:bg-red-50' : ''"
          >
            <component v-if="item.icon" :is="item.icon" class="h-4 w-4 text-gray-500" />
            {{ item.label }}
          </button>
        </div>
      </div>
    </teleport>
  </div>

</template>

<style scoped>
</style>
