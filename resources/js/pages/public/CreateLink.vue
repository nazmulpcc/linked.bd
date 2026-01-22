<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Create a short link" />

    <AppLayout
        title="Create a short link"
        description="Paste a destination and ship a branded link in seconds."
    >
        <div class="rounded-2xl border border-dashed border-border/70 bg-card p-8">
            <p class="text-sm text-muted-foreground">
                The creation form lives here. We'll add domain selection,
                passwords, and expiry next.
            </p>
        </div>
    </AppLayout>
</template>
