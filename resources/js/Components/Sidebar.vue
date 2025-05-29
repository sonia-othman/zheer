<template>
    <div>
        <!-- Mobile Toggle Button -->
        <button
            @click="toggleSidebar"
            class="fixed z-50 p-2 transition-colors bg-white rounded-md shadow-md md:hidden right-4 top-4 hover:bg-gray-100"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="w-6 h-6 text-gray-700"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M4 6h16M4 12h16M4 18h16"
                />
            </svg>
        </button>

        <!-- Sidebar Overlay (Mobile Only) -->
        <div
            v-show="isOpen"
            @click="toggleSidebar"
            class="fixed inset-0 z-40 transition-opacity bg-black bg-opacity-50 md:hidden"
            :class="{ 'opacity-0': !isOpen, 'opacity-100': isOpen }"
        ></div>

        <!-- Sidebar -->
        <div
            class="fixed top-0 right-0 z-40 w-64 h-full p-5 transition-transform duration-300 ease-in-out bg-white border-l shadow-lg"
            :class="{
                'translate-x-0': isOpen,
                'translate-x-full': !isOpen,
                'md:translate-x-0': true // Always visible on desktop
            }"
        >
            <!-- Close Button (Mobile Only) -->
            <button
                @click="toggleSidebar"
                class="absolute p-1 rounded-full md:hidden left-4 top-4 hover:bg-gray-100"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="w-6 h-6 text-gray-500"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M6 18L18 6M6 6l12 12"
                    />
                </svg>
            </button>

            <!-- Logo -->
            <div class="flex items-center justify-center mb-10">
                <img
                    :src="'/images/zheer.png'"
                    alt="Logo"
                    class="w-auto max-w-[170px] h-auto"
                >
            </div>

            <nav class="relative space-y-3">
                <!-- Home Link -->
                <SidebarLink
                    href="home"
                    icon="HomeIcon"
                    :text="t('common.home')"
                    match="Home"
                    @click="handleLinkClick"
                />

                <!-- Notifications -->
                <SidebarLink
                    v-if="route().has('notifications')"
                    href="notifications"
                    icon="BellIcon"
                    :text="t('common.notifications')"
                    match="Notifications"
                    @click="handleLinkClick"
                />

                <div class="flex items-center px-4 py-2 space-x-2 text-gray-700">
                    <LanguageSwitcher />
                </div>
            </nav>
        </div>
    </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n';
import { ref, onMounted, onUnmounted } from 'vue';
import SidebarLink from './SidebarLink.vue';
import LanguageSwitcher from './LanguageSwitcher.vue';

const { locale, t } = useI18n();
const isRtl = ['ku', 'ar'].includes(locale.value);
const isOpen = ref(false);

// Set initial state based on screen size
const checkScreenSize = () => {
    isOpen.value = window.innerWidth >= 768; // md breakpoint
};

// Toggle sidebar visibility
const toggleSidebar = () => {
    isOpen.value = !isOpen.value;
};

// Close sidebar when a link is clicked (mobile only)
const handleLinkClick = () => {
    if (window.innerWidth < 768) {
        isOpen.value = false;
    }
};

// Handle window resize
const handleResize = () => {
    checkScreenSize();
};

// Set up event listeners
onMounted(() => {
    checkScreenSize();
    window.addEventListener('resize', handleResize);
});

// Clean up event listeners
onUnmounted(() => {
    window.removeEventListener('resize', handleResize);
});
</script>
