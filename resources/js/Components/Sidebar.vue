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
        text="سەرەکی" 
        match="Home" 
      />

      <!-- Notifications -->
      <SidebarLink 
        v-if="route().has('notifications')" 
        href="notifications" 
        icon="BellIcon" 
        text="ئاگاداری" 
        match="Notifications" 
      />

      <!-- Language -->
      <SidebarLink 
        v-if="route().has('language')" 
        href="language" 
        icon="GlobeAltIcon" 
        text="زمان" 
        match="Language" 
      />
    </nav>
  </div>
</template>

<script setup>
import SidebarLink from './SidebarLink.vue'
</script>
