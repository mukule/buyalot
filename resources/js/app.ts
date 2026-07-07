import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { initializeTheme } from './composables/useAppearance';

const appName = import.meta.env.VITE_APP_NAME || 'Bianlina';
const pages = import.meta.glob<DefineComponent>('./pages/**/*.vue');

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),

    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, pages),

    setup({ el, App, props, plugin }) {
        const vueApp = createApp({ render: () => h(App, props) });

        vueApp.use(plugin);
        vueApp.use(ZiggyVue);

        vueApp.mount(el);
    },

    progress: {
        color: '#FF4500',
    },
});

// Set theme on load
initializeTheme();
