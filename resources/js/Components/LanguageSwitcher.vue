<template>
  <div class="relative">
    <button
      @click="toggleDropdown"
      class="flex items-center text-sm font-medium text-gray-700 hover:text-gray-800 focus:outline-none"
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
        v-show="isOpen"
        class="absolute mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 z-50"
        :class="isRtl ? 'right-0' : 'left-0'"
        ref="dropdown"
      >
        <div class="py-1" role="none">
          <a
            v-for="language in languages"
            :key="language.code"
            href="#"
            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900"
            :class="{ 'font-bold bg-gray-50': currentLocale === language.code }"
            @click="switchLanguage(language.code)"
          >
            {{ language.name }}
          </a>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

const isOpen = ref(false);
const dropdown = ref(null);
const page = usePage();

const currentLocale = computed(() => page.props.locale || 'en');
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
  isOpen.value = !isOpen.value;
};

const closeDropdown = () => {
  isOpen.value = false;
};

const handleClickOutside = (event) => {
  if (dropdown.value && !dropdown.value.contains(event.target)) {
    closeDropdown();
  }
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});

const switchLanguage = async (lang) => {
  closeDropdown();
  try {
    await router.get(route('language.switch', lang), {}, {
      preserveScroll: true,
      onSuccess: () => {
        if (isRtl.value !== ['ar', 'ku'].includes(lang)) {
          window.location.reload();
        }
      }
    });
  } catch (error) {
    console.error('Language switch failed:', error);
  }
};
</script>