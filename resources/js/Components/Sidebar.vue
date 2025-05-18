<template>
   <div class="fixed right-0 top-0 h-full w-64 bg-white p-5 border-l z-50 overflow-visible">
    <!-- Logo -->
    <div class="flex items-center justify-center mb-10">
      <img :src="'/images/logo1.png'" alt="Logo" class="w-auto max-w-[100px] h-auto">
    </div>

    <nav class="space-y-3 relative">
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
      <SidebarLink
        :icon="'GlobeAltIcon'"
        match="language"
      >
        <LanguageSwitcher />
      </SidebarLink>
    </nav>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n';
import SidebarLink from './SidebarLink.vue';
import LanguageSwitcher from './LanguageSwitcher.vue';

const { locale, t } = useI18n();
const isRtl = ['ku', 'ar'].includes(locale.value);
</script>