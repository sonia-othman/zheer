<template>
  <div class="fixed right-0 top-0 h-full w-64 bg-white p-5 border-l">
    <!-- Logo -->
    <div class="flex items-center justify-center mb-10">
      <img :src="'/images/logo1.png'" alt="Logo" class="w-auto max-w-[100px] h-auto">
    </div>

    <nav class="space-y-3">
      <!-- Home Link -->
      <SidebarLink
        href="home"
        icon="HomeIcon"
        :text="t('common.dashboard')"
        match="Home"
      />

      <!-- Notifications -->
      <SidebarLink
        v-if="route().has('notifications')"
        href="notifications"
        icon="BellIcon"
        :text="t('common.notifications')"
        match="Notifications"
      />

      <!-- Language Switcher -->
      <div class="flex flex-row-reverse items-center gap-2 px-4 py-2">
        <GlobeAltIcon class="w-6 h-6 text-primary-light" />
        <select 
          v-model="currentLocale"
          @change="switchLanguage" 
          class="w-full text-right bg-transparent border-none focus:ring-0"
        >
          <option value="ku">کوردی</option>
          <option value="ar">العربية</option>
          <option value="en">English</option>
        </select>
      </div>
    </nav>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { router } from '@inertiajs/vue3';
import SidebarLink from './SidebarLink.vue';
import { GlobeAltIcon } from '@heroicons/vue/24/outline'; // Make sure to import this

const { t, locale } = useI18n();

// Initialize with the current locale
const currentLocale = ref(locale.value);

// Switch language function
const switchLanguage = () => {
  // Update locale in Vue I18n
  locale.value = currentLocale.value;
  
  // Change HTML direction based on the selected language
  const isRtl = ['ar', 'ku'].includes(currentLocale.value);
  document.documentElement.dir = isRtl ? 'rtl' : 'ltr';
  document.documentElement.lang = currentLocale.value;
  
  // Send request to server to update the locale in session
  router.get(route('language.switch', currentLocale.value), {}, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      // You could add a notification here if you want
    }
  });
};

// Ensure the current locale is set correctly on mount
onMounted(() => {
  // Get locale from document or default
  currentLocale.value = document.documentElement.lang || 'en';
  
  // Set RTL direction if needed
  if (['ar', 'ku'].includes(currentLocale.value)) {
    document.documentElement.dir = 'rtl';
  }
});
</script>