<template>
    <div class="relative">
        <button
            @click.stop="toggleDropdown"
            class="flex items-center text-sm font-medium text-gray-600 hover:text-gray-800 focus:outline-none"
        >
            <span class="mr-4">{{ currentLanguageLabel }}</span>
            <svg
                class="w-5 h-5 text-gray-400"
                :class="{ 'rotate-180': isOpen }"
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
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="transform scale-95 opacity-0"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0"
        >
            <div
                v-if="isOpen"
                class="z-50 w-48 bg-white divide-y divide-gray-100 rounded-md shadow-lg absulute right-4 top-20 ring-1 ring-black ring-opacity-5"
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
