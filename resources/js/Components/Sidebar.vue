<template>
  <div class="fixed right-0 top-0 h-full w-64 bg-white p-5 border-l">
    <!-- Logo -->
    <div class="flex items-center justify-center mb-10">
      <img :src="'/images/logo1.png'" alt="Logo" class="w-auto max-w-[100px] h-auto">    
    </div>
    <nav class="space-y-5">
      <!-- Home Link -->
      <Link 
        :href="route('home')" 
        class="nav-link" 
        :class="{ 'active': $page.component === 'Home' }"
      >
        <HomeIcon class="w-6 h-6" />
        <span class="font-bold">سەرەکی</span>
      </Link>

      <!-- Notifications Link -->
      <Link 
        v-if="route().has('notifications')"
        :href="route('notifications')" 
        class="nav-link" 
        :class="{ 'active': $page.component === 'Notifications/Index' }"
      >
        <BellIcon class="w-6 h-6" />
        <span class="font-bold">ئاگاداری</span>
      </Link>

      <!-- Language Link -->
      <Link 
        v-if="route().has('language')"
        :href="route('language')" 
        class="nav-link" 
        :class="{ 'active': $page.component === 'Language/Index' }"
      >
        <GlobeAltIcon class="w-6 h-6" />
        <span class="font-semibold">زمان</span>
      </Link>

      <!-- Profile Link -->
      <Link 
        :href="route('profile.edit')" 
        class="nav-link" 
        :class="{ 'active': $page.component === 'Profile/Edit' }"
      >
        <UserIcon class="w-6 h-6" />
        <span class="font-bold">پڕۆفایل</span>
      </Link>
    </nav>
  </div>
</template>

<script setup>
import { HomeIcon, BellIcon, UserIcon } from "@heroicons/vue/24/solid";
import { GlobeAltIcon } from "@heroicons/vue/24/outline";
import { Link } from "@inertiajs/vue3";
</script>

<style scoped>
.nav-link {
  @apply flex flex-row-reverse items-center gap-2 px-4 py-2 rounded transition text-primary-light hover:bg-gray-100;
}

.nav-link.active {
  @apply bg-primary text-white font-bold;
}
</style>
