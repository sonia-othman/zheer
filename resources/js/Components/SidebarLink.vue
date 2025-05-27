<template>
    <component
        :is="href ? Link : 'div'"
        :href="href ? route(href) : undefined"
        class="flex items-center w-full gap-2 px-4 py-2 transition rounded cursor-pointer"
        :class="[
            rtl ? 'flex-row-reverse' : 'flex-row',
            isActive ? 'bg-primary text-white font-bold' : 'text-primary-light hover:bg-gray-100'
        ]"
    >
        <component
            v-if="iconComponent"
            :is="iconComponent"
            class="w-6 h-6"
        />
        <span>
            <slot>{{ text }}</slot>
        </span>
    </component>
</template>

<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { HomeIcon, BellIcon } from "@heroicons/vue/24/solid";
import { GlobeAltIcon } from "@heroicons/vue/24/outline";
import { useI18n } from 'vue-i18n';

const { locale } = useI18n();

const props = defineProps({
    href: String,
    icon: String,
    text: String,
    match: String,
    rtl: Boolean
});

const iconComponent = computed(() => ({
    HomeIcon,
    BellIcon,
    GlobeAltIcon
}[props.icon]));

const page = usePage();
const isActive = computed(() =>
    props.match ? page.component.includes(props.match) : false
);
</script>
