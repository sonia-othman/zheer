<template>
  <div class="flex min-h-screen">
    <!-- Main Content + Navbar (left side) -->
    <div class="flex-1 flex flex-col">
      <!-- Navbar remains LTR -->
      <NavBar class="w-[calc(100%-16rem)] fixed top-0 left-0 z-40 bg-white" />

      <!-- Main Content with conditional RTL -->
      <div 
        class="flex-1 p-6 bg-back mr-64"
        :class="{ 'content-rtl': $page.props.isRtl }"
      >
        <slot />
      </div>
    </div>

    <!-- Sidebar remains LTR -->
    <Sidebar />
  </div>
</template>

<script setup>
import Sidebar from "../Components/Sidebar.vue";
import NavBar from "../Components/NavBar.vue";
</script>