<template>
  <div class="relative">
    <button
      @click="isOpen = !isOpen"
      class="flex items-center text-sm font-medium text-gray-700 hover:text-gray-800"
    >
      <span>{{ currentLanguageLabel }}</span>
      <svg
        class="ml-1 h-5 w-5 text-gray-400"
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 20 20"
        fill="currentColor"
      >
        <path
          fill-rule="evenodd"
          d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
          clip-rule="evenodd"
        />
      </svg>
    </button>

    <div
      v-if="isOpen"
      class="absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 divide-y divide-gray-100"
    >
      <div class="py-1" role="none">
        <form @submit.prevent="switchLanguage(language.code)" v-for="language in languages" :key="language.code">
          <button
            type="submit"
            class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900"
            :class="{ 'font-bold': currentLocale === language.code }"
          >
            {{ language.name }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';

const isOpen = ref(false);

const currentLocale = computed(() => usePage().props.locale || 'en');

const languages = [
  { code: 'en', name: 'English' },
  { code: 'ku', name: 'کوردی' },
  { code: 'ar', name: 'العربية' }
];

const currentLanguageLabel = computed(() => {
  const lang = languages.find(lang => lang.code === currentLocale.value);
  return lang ? lang.name : 'English';
});

const switchLanguage = async (locale) => {
  try {
    await axios.post(route('language.switch'), { locale });
    window.location.reload();
  } catch (error) {
    console.error('Error switching language:', error);
  }
  isOpen.value = false;
};
</script>