<script setup lang="ts">
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { show as showLink } from '@/routes/links';
import { Head, Link } from '@inertiajs/vue3';

type RecentLink = {
    id: number;
    ulid: string | null;
    short_url: string;
    destination_url: string;
    click_count: number;
    last_accessed_at: string | null;
};

type Stats = {
    clicks_today: number;
    active_links: number;
    custom_domains: number;
    protected_links: number;
};

defineProps<{
    stats: Stats;
    recent_links: RecentLink[];
}>();
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
                        <p class="text-2xl font-semibold">
                            {{ stats.clicks_today }} clicks today
                        </p>
                    </div>
                    <div
                        class="rounded-full border border-border/70 bg-muted px-3 py-1 text-xs font-medium"
                    >
                        Live updates soon
                    </div>
                </div>
                <div class="mt-6 grid gap-3">
                    <div
                        v-if="!recent_links.length"
                        class="rounded-xl border border-dashed border-border/70 px-4 py-6 text-sm text-muted-foreground"
                    >
                        Your latest links will show here. Create one to get
                        started.
                    </div>
                    <div
                        v-for="link in recent_links"
                        v-else
                        :key="link.id"
                        class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-border/70 bg-muted/30 px-4 py-4 text-sm"
                    >
                        <div class="space-y-1">
                            <p class="font-medium">{{ link.short_url }}</p>
                            <p class="text-xs text-muted-foreground">
                                {{ link.destination_url }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                <span v-if="link.last_accessed_at">
                                    Last accessed
                                    {{ new Date(link.last_accessed_at).toLocaleString() }}
                                </span>
                                <span v-else>No clicks yet</span>
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-xs text-muted-foreground">
                                {{ link.click_count }} clicks
                            </span>
                            <Button
                                v-if="link.ulid"
                                size="sm"
                                variant="ghost"
                                as-child
                            >
                                <Link :href="showLink(link.ulid)">
                                    View analytics
                                </Link>
                            </Button>
                        </div>
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
                        <span class="font-semibold">{{ stats.active_links }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Custom domains</span>
                        <span class="font-semibold">{{ stats.custom_domains }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Protected links</span>
                        <span class="font-semibold">{{ stats.protected_links }}</span>
                    </div>
                </div>
            </aside>
        </div>
    </AppLayout>
</template>
