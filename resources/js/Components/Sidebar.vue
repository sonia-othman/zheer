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
          @change="switchLanguage($event)" 
          class="w-full text-right bg-transparent border-none focus:ring-0"
        >
          <option value="ku" {{ app()->getLocale() === 'ku' ? 'selected' : '' }}>کوردی</option>
          <option value="ar" {{ app()->getLocale() === 'ar' ? 'selected' : '' }}>عربی</option>
          <option value="en" {{ app()->getLocale() === 'en' ? 'selected' : '' }}>English</option>
        </select>
      </div>
   </nav>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n';
import SidebarLink from './SidebarLink.vue';
const { t } = useI18n();
</script>
