<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { create } from '@/routes/links';
import { dashboard, login } from '@/routes';
import { Button } from '@/components/ui/button';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps<{
    canRegister: boolean;
}>();

const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <Head title="Short links that feel yours" />

    <AppLayout>
        <template #header>
            <div class="grid gap-8 lg:grid-cols-[1.1fr_0.9fr] lg:items-end">
                <div class="space-y-6">
                    <div class="space-y-3">
                        <p
                            class="inline-flex items-center gap-2 rounded-full border border-border/70 bg-muted px-3 py-1 text-xs font-medium text-muted-foreground"
                        >
                            Built for launches, campaigns, and teams of one.
                        </p>
                        <h1
                            class="text-4xl font-semibold tracking-tight text-foreground sm:text-5xl"
                        >
                            Short links with custom domains, passwords, and QR
                            codes on day one.
                        </h1>
                        <p class="max-w-xl text-base text-muted-foreground">
                            Create branded links in seconds, protect them with
                            passwords, and track simple click stats without the
                            analytics clutter.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <Link :href="create()">
                            <Button size="lg">Create a short link</Button>
                        </Link>
                        <Link v-if="user" :href="dashboard()">
                            <Button variant="secondary" size="lg">
                                Go to dashboard
                            </Button>
                        </Link>
                        <Link v-else :href="login()">
                            <Button variant="ghost" size="lg">Sign in</Button>
                        </Link>
                    </div>
                </div>
                <div
                    class="rounded-2xl border border-border/70 bg-card p-6 shadow-sm"
                >
                    <div class="space-y-4">
                        <div class="space-y-1">
                            <p class="text-xs font-semibold uppercase text-muted-foreground">
                                Example
                            </p>
                            <p class="text-lg font-semibold">
                                go.yourbrand.com/launch
                            </p>
                        </div>
                        <div class="grid gap-3 text-sm text-muted-foreground">
                            <div class="flex items-center justify-between">
                                <span>Destination</span>
                                <span class="text-foreground">
                                    product.yourbrand.com
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>Password</span>
                                <span class="text-foreground">Enabled</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>QR code</span>
                                <span class="text-foreground">Ready</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>Clicks</span>
                                <span class="text-foreground">142</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <section class="grid gap-6 lg:grid-cols-3">
            <div class="rounded-2xl border border-border/70 bg-card p-5">
                <p class="text-sm font-medium">Custom domains</p>
                <p class="mt-2 text-sm text-muted-foreground">
                    Bring your own hostnames and keep aliases unique per domain.
                </p>
            </div>
            <div class="rounded-2xl border border-border/70 bg-card p-5">
                <p class="text-sm font-medium">Password protection</p>
                <p class="mt-2 text-sm text-muted-foreground">
                    Lock down a link with a password before redirecting.
                </p>
            </div>
            <div class="rounded-2xl border border-border/70 bg-card p-5">
                <p class="text-sm font-medium">Instant QR codes</p>
                <p class="mt-2 text-sm text-muted-foreground">
                    Download ready-to-share QR codes for every short link.
                </p>
            </div>
        </section>
    </AppLayout>
</template>
