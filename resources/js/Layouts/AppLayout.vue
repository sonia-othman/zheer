<template>
  <div class="flex min-h-screen relative">
    <!-- Sidebar: fixed on the far right -->
    <Sidebar class="fixed top-0 right-0 w-64 h-screen z-30" />

    <!-- Navbar: fixed next to sidebar (on the right) -->
    <NavBar
      class="fixed top-0 z-20 bg-white h-16"
      style="right: 16rem; width: calc(100% - 16rem);"
    />

    <!-- Main content: has margin for sidebar + navbar height -->
    <div class="flex-1 mt-16 mr-64 p-6 bg-back">
      <slot />
    </div>
  </div>
</template>

<script setup>
import Sidebar from "../Components/Sidebar.vue";
import NavBar from "../Components/NavBar.vue";
</script>
