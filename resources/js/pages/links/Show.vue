<script setup lang="ts">
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as linksIndex } from '@/routes/links';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

type LinkSummary = {
    id: number;
    short_url: string;
    destination_url: string;
    click_count: number;
    last_accessed_at: string | null;
    expires_at: string | null;
    domain: string | null;
};

type ChartPoint = {
    label: string;
    value: number;
};

type Analytics = {
    total_visits: number;
    visits_by_day: ChartPoint[];
    top_referrers: ChartPoint[];
    device_breakdown: ChartPoint[];
    browser_breakdown: ChartPoint[];
    country_breakdown: ChartPoint[];
};

const props = defineProps<{
    link: LinkSummary;
    analytics: Analytics;
}>();

const maxVisits = computed(() =>
    Math.max(1, ...props.analytics.visits_by_day.map((point) => point.value)),
);

const formatDate = (value: string | null) =>
    value ? new Date(value).toLocaleString() : 'Never';
</script>

<template>
    <Head title="Link analytics" />

    <AppLayout
        title="Link analytics"
        description="See how your short link is performing over time."
    >
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm text-muted-foreground">Short link</p>
                <p class="text-lg font-semibold">{{ link.short_url }}</p>
                <p class="text-sm text-muted-foreground">
                    {{ link.destination_url }}
                </p>
            </div>
            <Link :href="linksIndex()">
                <Button variant="ghost" size="sm">Back to links</Button>
            </Link>
        </div>

        <div class="mt-6 grid gap-4 lg:grid-cols-3">
            <div class="rounded-2xl border border-border/70 bg-card p-6">
                <p class="text-xs font-semibold uppercase text-muted-foreground">Total visits</p>
                <p class="mt-2 text-3xl font-semibold">
                    {{ analytics.total_visits }}
                </p>
            </div>
            <div class="rounded-2xl border border-border/70 bg-card p-6">
                <p class="text-xs font-semibold uppercase text-muted-foreground">Last accessed</p>
                <p class="mt-2 text-base font-semibold">
                    {{ formatDate(link.last_accessed_at) }}
                </p>
            </div>
            <div class="rounded-2xl border border-border/70 bg-card p-6">
                <p class="text-xs font-semibold uppercase text-muted-foreground">Expires</p>
                <p class="mt-2 text-base font-semibold">
                    {{ formatDate(link.expires_at) }}
                </p>
            </div>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-[2fr_1fr]">
            <section class="rounded-2xl border border-border/70 bg-card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold">Visits over time</p>
                        <p class="text-xs text-muted-foreground">
                            Last {{ analytics.visits_by_day.length }} days
                        </p>
                    </div>
                </div>

                <div v-if="analytics.visits_by_day.length" class="mt-6 grid gap-3">
                    <div
                        v-for="point in analytics.visits_by_day"
                        :key="point.label"
                        class="grid gap-2"
                    >
                        <div class="flex items-center justify-between text-xs text-muted-foreground">
                            <span>{{ point.label }}</span>
                            <span>{{ point.value }}</span>
                        </div>
                        <div class="h-2 rounded-full bg-muted">
                            <div
                                class="h-2 rounded-full bg-primary"
                                :style="{ width: `${(point.value / maxVisits) * 100}%` }"
                            ></div>
                        </div>
                    </div>
                </div>
                <p v-else class="mt-6 text-sm text-muted-foreground">
                    No visits yet.
                </p>
            </section>

            <aside class="grid gap-4">
                <div class="rounded-2xl border border-border/70 bg-card p-6">
                    <p class="text-sm font-semibold">Top referrers</p>
                    <div v-if="analytics.top_referrers.length" class="mt-4 grid gap-3 text-sm">
                        <div
                            v-for="item in analytics.top_referrers"
                            :key="item.label"
                            class="flex items-center justify-between"
                        >
                            <span class="text-muted-foreground">{{ item.label }}</span>
                            <span class="font-medium">{{ item.value }}</span>
                        </div>
                    </div>
                    <p v-else class="mt-4 text-sm text-muted-foreground">No referrers yet.</p>
                </div>

                <div class="rounded-2xl border border-border/70 bg-card p-6">
                    <p class="text-sm font-semibold">Devices</p>
                    <div v-if="analytics.device_breakdown.length" class="mt-4 grid gap-3 text-sm">
                        <div
                            v-for="item in analytics.device_breakdown"
                            :key="item.label"
                            class="flex items-center justify-between"
                        >
                            <span class="text-muted-foreground">{{ item.label }}</span>
                            <span class="font-medium">{{ item.value }}</span>
                        </div>
                    </div>
                    <p v-else class="mt-4 text-sm text-muted-foreground">No device data yet.</p>
                </div>
            </aside>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <section class="rounded-2xl border border-border/70 bg-card p-6">
                <p class="text-sm font-semibold">Browsers</p>
                <div v-if="analytics.browser_breakdown.length" class="mt-4 grid gap-3 text-sm">
                    <div
                        v-for="item in analytics.browser_breakdown"
                        :key="item.label"
                        class="flex items-center justify-between"
                    >
                        <span class="text-muted-foreground">{{ item.label }}</span>
                        <span class="font-medium">{{ item.value }}</span>
                    </div>
                </div>
                <p v-else class="mt-4 text-sm text-muted-foreground">No browser data yet.</p>
            </section>

            <section class="rounded-2xl border border-border/70 bg-card p-6">
                <p class="text-sm font-semibold">Countries</p>
                <div v-if="analytics.country_breakdown.length" class="mt-4 grid gap-3 text-sm">
                    <div
                        v-for="item in analytics.country_breakdown"
                        :key="item.label"
                        class="flex items-center justify-between"
                    >
                        <span class="text-muted-foreground">{{ item.label }}</span>
                        <span class="font-medium">{{ item.value }}</span>
                    </div>
                </div>
                <p v-else class="mt-4 text-sm text-muted-foreground">No country data yet.</p>
            </section>
        </div>
    </AppLayout>
</template>
