import { createI18n } from 'vue-i18n';

// Import your message files
import en from './locales/en.json';
import ku from './locales/ku.json';
import ar from './locales/ar.json';

// Create the i18n instance
const i18n = createI18n({
  legacy: false, // Use the Composition API
  globalInjection: true, // Make $t available in templates
  locale: document.documentElement.lang || 'en', // Default locale
  fallbackLocale: 'en', // Fallback locale
  messages: {
    en,
    ku,
    ar
  }
});

export default i18n;