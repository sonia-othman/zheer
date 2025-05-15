
import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { Chart } from 'chart.js';
import zoomPlugin from 'chartjs-plugin-zoom';
import setupI18n from './i18n'


Chart.register(zoomPlugin);

Chart.defaults.font.family = 'Inter, sans-serif';
Chart.defaults.color = '#6b7280';
Chart.defaults.borderColor = 'rgba(209, 213, 219, 0.5)';

const appName = import.meta.env.VITE_APP_NAME || 'ژیر';
const i18n = setupI18n();
const app = createApp(App);
app.use(i18n);
app.mount('#app');

createInertiaApp({
  resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
  setup({ el, App, props, plugin }) {
    // Create i18n instance
    const i18n = createI18n({
      legacy: false,
      globalInjection: true,
      locale: props.initialPage.props.locale || 'en',
      fallbackLocale: window.Laravel.fallbackLocale || 'en',
      messages: {
        [props.initialPage.props.locale]: props.initialPage.props.translations || {}
      }
    });

    // Set RTL direction for Arabic and Kurdish
    if (['ar', 'ku'].includes(props.initialPage.props.locale)) {
      document.documentElement.dir = 'rtl';
    } else {
      document.documentElement.dir = 'ltr';
    }
    document.documentElement.lang = props.initialPage.props.locale;

    const app = createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(i18n);

    // Share the i18n instance with all components
    app.config.globalProperties.$i18n = i18n;
    app.config.globalProperties.$t = i18n.global.t;

    app.mount(el);
  },
});
