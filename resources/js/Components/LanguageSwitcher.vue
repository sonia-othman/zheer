<template>
  <div class="relative">
    <button
      @click.stop="toggleDropdown"
      class="flex items-center text-sm font-medium text-gray-700 hover:text-gray-800 focus:outline-none"
    >
      <span class="mr-4">{{ currentLanguageLabel }}</span>
      <svg class="h-5 w-5 text-gray-400" :class="{ 'rotate-180': isOpen }" viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
      </svg>
    </button>

    <transition
      enter-active-class="transition ease-out duration-100"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
     <div
  v-if="isOpen"
  class="absolute left-1/2 transform -translate-x-1/2 top-full mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 z-50"
  @click.stop
>

        <div class="py-1">
          <a
            v-for="language in languages"
            :key="language.code"
            href="#"
            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
            :class="{ 'font-bold bg-gray-50': currentLocale === language.code }"
            @click.prevent="switchLanguage(language.code)"
          >
            {{ language.name }}
          </a>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { i18n } from '../app.js';

const isOpen = ref(false);

const languages = [
  { code: 'en', name: 'English' },
  { code: 'ku', name: 'کوردی' },
  { code: 'ar', name: 'العربية' }
];

const currentLocale = computed(() => i18n.global.locale.value);
const isRtl = computed(() => ['ar', 'ku'].includes(currentLocale.value));
const currentLanguageLabel = computed(() =>
  languages.find(lang => lang.code === currentLocale.value)?.name || 'English'
);

const toggleDropdown = () => {
  isOpen.value = !isOpen.value;
};

const switchLanguage = (lang) => {
  if (i18n.global.locale.value === lang) return;

  i18n.global.locale.value = lang;
  localStorage.setItem('locale', lang);
  document.documentElement.setAttribute('dir', ['ar', 'ku'].includes(lang) ? 'rtl' : 'ltr');
  isOpen.value = false;
};
</script>
