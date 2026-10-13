<script setup lang="ts">
import PanelLayout from '@/layouts/panel/PanelLayout.vue';
import type { BreadcrumbItem } from '@/types';

const { breadcrumbs = [] } = defineProps<{
    breadcrumbs?: BreadcrumbItem[];
}>();
</script>

<template>
    <PanelLayout :migas="breadcrumbs">
        <slot />
    </PanelLayout>
</template>
