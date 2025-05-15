import { createI18n } from 'vue-i18n'
import en from './locales/en.json'
import ar from './locales/ar.json'
import ku from './locales/ku.json'

const i18n = createI18n({
  legacy: false,
  globalInjection: true,
  locale: window.Laravel?.locale || 'en',
  fallbackLocale: 'en',
  messages: {
    en,
    ar,
    ku
  }
})

export default i18n
