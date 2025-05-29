import '../css/app.css';
import './bootstrap';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { createI18n } from 'vue-i18n';
import { Chart } from 'chart.js';
import zoomPlugin from 'chartjs-plugin-zoom';

// Import language files
import en from '../lang/locales/en.json';
import ar from '../lang/locales/ar.json';
import ku from '../lang/locales/ku.json';

Chart.register(zoomPlugin);
Chart.defaults.font.family = 'Inter, sans-serif';
Chart.defaults.color = '#6b7280';
Chart.defaults.borderColor = 'rgba(209, 213, 219, 0.5)';

// Get saved or default locale
const savedLocale = localStorage.getItem('locale') || 'en';
const isRtl = ['ar', 'ku'].includes(savedLocale);

// Set document direction
document.documentElement.setAttribute('dir', isRtl ? 'rtl' : 'ltr');

// Create i18n instance
export const i18n = createI18n({
    legacy: false,
    globalInjection: true,
    locale: savedLocale,
    fallbackLocale: 'en',
    messages: { en, ar, ku },
    missing: (locale, key) => {
        console.warn(`Missing translation: ${key} in ${locale}`);
    }
});

const appName = import.meta.env.VITE_APP_NAME || 'ژیر';

createInertiaApp({
    resolve: name => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        // Check if element already has Vue instance
        if (!el._vueApp) {
            const app = createApp({ render: () => h(App, props) })
                .use(plugin)
                .use(ZiggyVue)
                .use(i18n);

            app.mount(el);
            el._vueApp = app;
        }
    }
});
