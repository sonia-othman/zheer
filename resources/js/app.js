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

const appName = import.meta.env.VITE_APP_NAME || 'ژیر';

createInertiaApp({
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        // Set RTL direction and load CSS before creating app
        const locale = props.initialPage.props.locale || 'en';
        const isRtl = ['ar', 'ku'].includes(locale);
        
        document.documentElement.lang = locale;
        document.documentElement.dir = isRtl ? 'rtl' : 'ltr';

        // Dynamically import RTL CSS if needed
        if (isRtl) {
            import('../css/rtl.css');
        }

        // Create i18n instance
        const i18n = createI18n({
            legacy: false,
            globalInjection: true,
            locale: locale,
            fallbackLocale: 'en',
            messages: {
                en: { ...en, ...props.initialPage.props.translations },
                ar: { ...ar, ...props.initialPage.props.translations },
                ku: { ...ku, ...props.initialPage.props.translations }
            }
        });

        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(i18n)
            .mount(el);
    },
});