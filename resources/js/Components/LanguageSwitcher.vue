<template>
  <div class="relative">
    <button
      @click.stop="toggleDropdown"
      class="flex items-center text-sm font-medium text-gray-700 hover:text-gray-800 focus:outline-none"
      :disabled="isLoading"
    >
      <span class="mr-1">{{ currentLanguageLabel }}</span>
      <svg
        class="h-5 w-5 text-gray-400 transition-transform duration-200"
        :class="{ 'transform rotate-180': isOpen }"
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
        class="absolute mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 z-50"
        :class="isRtl ? 'right-0' : 'left-0'"
        ref="dropdown"
        @click.stop
      >
        <div class="py-1" role="none">
          <a
            v-for="language in languages"
            :key="language.code"
            href="#"
            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900"
            :class="{ 
              'font-bold bg-gray-50': currentLocale === language.code,
              'cursor-not-allowed opacity-70': isLoading
            }"
            @click.prevent="!isLoading && switchLanguage(language.code)"
          >
            {{ language.name }}
            <span v-if="isLoading && currentSwitchingTo === language.code" class="ml-2">
              <svg class="animate-spin h-4 w-4 text-gray-500 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
            </span>
          </a>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

const isOpen = ref(false);
const dropdown = ref(null);
const isLoading = ref(false);
const currentSwitchingTo = ref(null);
const page = usePage();

const currentLocale = computed(() => String(page.props.locale || 'en'));
const isRtl = computed(() => ['ar', 'ku'].includes(currentLocale.value));

const languages = [
  { code: 'en', name: 'English' },
  { code: 'ku', name: 'کوردی' },
  { code: 'ar', name: 'العربية' }
];

const currentLanguageLabel = computed(() => {
  return languages.find(lang => lang.code === currentLocale.value)?.name || 'English';
});

const toggleDropdown = () => {
  if (!isLoading.value) {
    isOpen.value = !isOpen.value;
  }
};

const closeDropdown = () => {
  isOpen.value = false;
};

const switchLanguage = async (lang) => {
  if (currentLocale.value === lang || isLoading.value) return;
  
  isLoading.value = true;
  currentSwitchingTo.value = lang;
  closeDropdown();

  try {
    await router.get(route('language.switch', lang), {}, {
      preserveScroll: true,
      preserveState: true,
      onFinish: () => {
        isLoading.value = false;
        currentSwitchingTo.value = null;
      }
    });
  } catch (error) {
    isLoading.value = false;
    currentSwitchingTo.value = null;
    console.error('Language switch failed:', error);
  }
};
</script>