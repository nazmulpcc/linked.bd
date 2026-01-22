<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout
        title="Dashboard"
        description="Track your links and keep an eye on what is getting clicks."
    >
        <div class="grid gap-6 lg:grid-cols-[2fr_1fr]">
            <section class="rounded-2xl border border-border/70 bg-card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-muted-foreground">
                            Recent activity
                        </p>
                        <p class="text-2xl font-semibold">0 clicks today</p>
                    </div>
                    <div
                        class="rounded-full border border-border/70 bg-muted px-3 py-1 text-xs font-medium"
                    >
                        Live updates soon
                    </div>
                </div>
                <div class="mt-6 grid gap-3">
                    <div
                        class="rounded-xl border border-dashed border-border/70 px-4 py-6 text-sm text-muted-foreground"
                    >
                        Your latest links will show here. Create one to get
                        started.
                    </div>
                </div>
            </section>

            <aside class="rounded-2xl border border-border/70 bg-card p-6">
                <p class="text-sm font-medium text-muted-foreground">
                    Workspace snapshot
                </p>
                <div class="mt-4 grid gap-4 text-sm">
                    <div class="flex items-center justify-between">
                        <span>Active links</span>
                        <span class="font-semibold">0</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Custom domains</span>
                        <span class="font-semibold">0</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Protected links</span>
                        <span class="font-semibold">0</span>
                    </div>
                </div>
            </aside>
        </div>
    </AppLayout>
</template>
