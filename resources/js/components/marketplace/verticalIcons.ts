import { Car, HardHat, ShoppingBag, Store } from 'lucide-vue-next';
import type { Component } from 'vue';

/** Maps a vertical's config `icon` string to a lucide component. */
export const VERTICAL_ICONS: Record<string, Component> = {
    ShoppingBag,
    Car,
    HardHat,
    Store,
};

export const verticalIcon = (name?: string): Component => VERTICAL_ICONS[name ?? ''] ?? Store;
