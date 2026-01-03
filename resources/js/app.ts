import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { QuillEditor } from '@vueup/vue-quill';
import '@vueup/vue-quill/dist/vue-quill.snow.css';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import PrimeVue from 'primevue/config';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { initializeTheme } from './composables/useAppearance';

const appName = import.meta.env.VITE_APP_NAME || 'Buyalot';
const pages = import.meta.glob<DefineComponent>('./pages/**/*.vue');

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),

    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, pages),

    setup({ el, App, props, plugin }) {
        const vueApp = createApp({ render: () => h(App, props) });

        vueApp.use(plugin);
        vueApp.use(ZiggyVue);
        vueApp.use(PrimeVue);

        vueApp.component('QuillEditor', QuillEditor);

        vueApp.mount(el);
    },

    progress: {
        color: '#f5762a',
    },
});

// Set theme on load
initializeTheme();
