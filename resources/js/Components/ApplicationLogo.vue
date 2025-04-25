<template>
    <img :src="logoUrl" alt="Application Logo" class="h-[120px] w-auto" />
</template>

<script setup>
import { computed } from 'vue';

const logoUrl = computed(() => {
    return new URL('/images/zheer1.png', import.meta.url).href;
    
});
</script>