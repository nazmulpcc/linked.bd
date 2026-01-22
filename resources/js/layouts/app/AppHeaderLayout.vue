<script setup lang="ts">
import AppHeader from '@/components/AppHeader.vue';
import AppShell from '@/components/AppShell.vue';
</script>

<template>
    <AppShell variant="header" class="flex-col">
        <AppHeader />
        <slot />
    </AppShell>
</template>
