<template>
  <div class="flex min-h-screen">
    <!-- Main Content + Navbar (left side) -->
    <div class="flex-1 flex flex-col">
      <!-- Navbar (full width but stops before the sidebar) -->
      <NavBar class="w-[calc(100%-16rem)] fixed top-0 left-0 z-40 bg-white " />

      <!-- Main Content (pushed below Navbar, avoids sidebar) -->
      <div class="flex-1 p-6 bg-back  mr-64">
        <slot />
      </div>
    </div>

    <!-- Sidebar (fixed right side) -->
    <Sidebar />
  </div>
</template>


<script setup>
import Sidebar from "../Components/Sidebar.vue";
import NavBar from "../Components/NavBar.vue";
</script>
