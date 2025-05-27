<template>
    <div
        class="relative"
        ref="dropdownContainer"
    >
        <button
            @click.stop="toggleDropdown"
            @keydown.esc="closeDropdown"
            class="flex items-center text-sm font-medium text-gray-700 hover:text-gray-800 focus:outline-none"
            aria-haspopup="true"
            :aria-expanded="isOpen.toString()"
            tabindex="0"
        >
            <span class="mr-4">{{ currentLanguageLabel }}</span>
            <svg
                class="w-5 h-5 text-gray-400 transition-transform"
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
                class="absolute z-50 w-48 mt-2 transform -translate-x-1/2 bg-white divide-y divide-gray-100 rounded-md shadow-lg left-1/2 top-full ring-1 ring-black ring-opacity-5"
                role="menu"
                @click.stop
            >
                <div class="py-1">
                    <button
                        v-for="language in languages"
                        :key="language.code"
                        class="w-full px-4 py-2 text-sm text-left text-gray-700 hover:bg-gray-100 focus:outline-none"
                        :class="{ 'font-bold bg-gray-50': currentLocale === language.code }"
                        @click.prevent="switchLanguage(language.code)"
                        role="menuitem"
                    >
                        {{ language.name }}
                    </button>
                </div>
            </div>
        </transition>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { i18n } from '../app.js';

const isOpen = ref(false);
const dropdownContainer = ref(null);

const languages = [
    { code: 'en', name: 'English' },
    { code: 'ku', name: 'کوردی' },
    { code: 'ar', name: 'العربية' },
];

const currentLocale = computed(() => i18n.global.locale.value);
const currentLanguageLabel = computed(() => {
    return languages.find((lang) => lang.code === currentLocale.value)?.name || 'English';
});

const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
};

const closeDropdown = () => {
    isOpen.value = false;
};

const switchLanguage = (lang) => {
    if (currentLocale.value === lang) return;

    i18n.global.locale.value = lang;
    localStorage.setItem('locale', lang);
    document.documentElement.setAttribute('dir', ['ar', 'ku'].includes(lang) ? 'rtl' : 'ltr');
    closeDropdown();
};

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
    if (dropdownContainer.value && !dropdownContainer.value.contains(event.target)) {
        closeDropdown();
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>
