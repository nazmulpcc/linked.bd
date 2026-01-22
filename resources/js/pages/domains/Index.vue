<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Domains" />

    <AppLayout
        title="Domains"
        description="Verify and manage the hostnames you want to use for short links."
    >
        <div class="rounded-2xl border border-dashed border-border/70 bg-card p-8">
            <p class="text-sm text-muted-foreground">
                Domain verification controls will appear here once the workflow
                is in place.
            </p>
        </div>
    </AppLayout>
</template>
