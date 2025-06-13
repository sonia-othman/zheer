<template>
  <div class="flex min-h-screen relative">
    <Sidebar />
    
    <!-- Navbar: responsive positioning -->
    <NavBar class="fixed top-0 right-0 z-20 bg-white h-16 w-full md:w-[calc(100%-16rem)] md:right-64" />
    
    <!-- Main content: responsive margins -->
    <div class="flex-1 mt-16 p-6 bg-back md:mr-64">
      <slot />
    </div>
  </div>
</template>

<script setup>
import Sidebar from "../Components/Sidebar.vue";
import NavBar from "../Components/NavBar.vue";
</script>
