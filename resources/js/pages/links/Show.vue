<script setup lang="ts">
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import DynamicRuleBuilder from '@/pages/links/components/DynamicRuleBuilder.vue';
import { createCondition, createRule, type Rule, type RuleCondition } from '@/pages/links/components/dynamicTypes';
import links, { index as linksIndex } from '@/routes/links';
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type LinkSummary = {
    id: number;
    short_url: string;
    destination_url: string;
    link_type: 'static' | 'dynamic';
    fallback_destination_url: string | null;
    click_count: number;
    last_accessed_at: string | null;
    expires_at: string | null;
    domain: string | null;
};

type RuleConditionPayload = {
    id: number;
    condition_type: string | null;
    operator: string | null;
    value: string | string[] | Record<string, unknown> | null;
};

type RulePayload = {
    id: number;
    priority: number;
    destination_url: string;
    enabled: boolean;
    conditions: RuleConditionPayload[];
};

type ChartPoint = {
    label: string;
    value: number;
};

type RuleAnalytics = {
    id: number;
    priority: number;
    destination_url: string;
    enabled: boolean;
    clicks: number;
};

type Analytics = {
    total_visits: number;
    visits_by_day: ChartPoint[];
    top_referrers: ChartPoint[];
    device_breakdown: ChartPoint[];
    browser_breakdown: ChartPoint[];
    country_breakdown: ChartPoint[];
    rule_breakdown: RuleAnalytics[];
    fallback_clicks: number | null;
};

const props = defineProps<{
    link: LinkSummary;
    analytics: Analytics;
    dynamic: {
        rules: RulePayload[];
    } | null;
}>();

const maxVisits = computed(() =>
    Math.max(1, ...props.analytics.visits_by_day.map((point) => point.value)),
);

const formatDate = (value: string | null) =>
    value ? new Date(value).toLocaleString() : 'Never';

const isDynamic = computed(() => props.link.link_type === 'dynamic');
const isEditing = ref(false);
const fallbackDestination = ref(props.link.fallback_destination_url ?? '');
const rules = ref<Rule[]>([]);
const selectClass =
    'border-input text-foreground dark:bg-input/30 h-9 w-full rounded-md border bg-transparent px-3 py-1 text-base shadow-xs outline-none transition-[color,box-shadow] focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] md:text-sm';

const mapRuleConditions = (conditions: RuleConditionPayload[]): RuleCondition[] =>
    conditions.map((condition) => {
        const draft = createCondition();
        draft.condition_type = condition.condition_type ?? 'country';
        draft.operator = condition.operator ?? 'equals';

        if (draft.condition_type === 'time_window' && condition.value && typeof condition.value === 'object' && !Array.isArray(condition.value)) {
            const value = condition.value as Record<string, unknown>;
            draft.time = {
                timezone: typeof value.timezone === 'string' ? value.timezone : '',
                days: Array.isArray(value.days) ? (value.days as string[]) : [],
                hours: {
                    start: typeof value.hours === 'object' && value.hours !== null && typeof (value.hours as Record<string, unknown>).start === 'number'
                        ? ((value.hours as Record<string, unknown>).start as number)
                        : null,
                    end: typeof value.hours === 'object' && value.hours !== null && typeof (value.hours as Record<string, unknown>).end === 'number'
                        ? ((value.hours as Record<string, unknown>).end as number)
                        : null,
                },
            };
        } else if (Array.isArray(condition.value)) {
            draft.values = condition.value as string[];
        } else if (typeof condition.value === 'string') {
            draft.value = condition.value;
        }

        return draft;
    });

const resetEditor = () => {
    fallbackDestination.value = props.link.fallback_destination_url ?? '';
    rules.value = (props.dynamic?.rules ?? []).map((rule, index) => ({
        id: createRule(index + 1).id,
        priority: rule.priority,
        destination_url: rule.destination_url,
        enabled: rule.enabled,
        conditions: mapRuleConditions(rule.conditions),
    }));
};

resetEditor();

const formatConditionValue = (value: RuleConditionPayload['value']): string | null => {
    if (value === null || value === undefined) {
        return null;
    }

    if (Array.isArray(value)) {
        return value.join(', ');
    }

    if (typeof value === 'object') {
        const timezone = typeof value.timezone === 'string' ? value.timezone : '';
        const days = Array.isArray(value.days) ? value.days.join(', ') : '';
        const hours = typeof value.hours === 'object' && value.hours !== null
            ? `${(value.hours as Record<string, unknown>).start ?? ''}-${(value.hours as Record<string, unknown>).end ?? ''}`
            : '';

        return [timezone && `tz: ${timezone}`, days && `days: ${days}`, hours && `hours: ${hours}`]
            .filter(Boolean)
            .join(' | ');
    }

    return String(value);
};
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
            <div class="flex flex-wrap items-center gap-2">
                <Form v-bind="links.clone.form(link.ulid)">
                    <Button variant="ghost" size="sm" type="submit">
                        Clone link
                    </Button>
                </Form>
                <Link :href="linksIndex()">
                    <Button variant="ghost" size="sm">Back to links</Button>
                </Link>
            </div>
        </div>

        <section
            v-if="isDynamic"
            class="mt-6 rounded-2xl border border-border/70 bg-card p-6"
        >
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold">Dynamic routing rules</p>
                    <p class="text-xs text-muted-foreground">
                        Edit the destinations and conditions for this link.
                    </p>
                </div>
                <Button
                    variant="ghost"
                    size="sm"
                    type="button"
                    @click="isEditing = !isEditing; if (!isEditing) resetEditor();"
                >
                    {{ isEditing ? 'Cancel' : 'Edit rules' }}
                </Button>
            </div>

            <div v-if="!isEditing" class="mt-5 grid gap-4">
                <div class="rounded-xl border border-border/70 bg-background p-4 text-sm">
                    <p class="text-xs font-semibold uppercase text-muted-foreground">Fallback destination</p>
                    <p class="mt-2 font-medium">
                        {{ link.fallback_destination_url || link.destination_url }}
                    </p>
                </div>

                <div class="grid gap-4">
                    <div
                        v-for="rule in dynamic?.rules || []"
                        :key="rule.id"
                        class="rounded-xl border border-border/70 bg-background p-4"
                    >
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex items-center gap-2 text-sm font-semibold">
                                <span>Rule</span>
                                <span class="rounded-md border border-border/70 px-2 py-0.5 text-xs">
                                    Priority {{ rule.priority }}
                                </span>
                            </div>
                            <span
                                class="text-xs"
                                :class="rule.enabled ? 'text-emerald-500' : 'text-muted-foreground'"
                            >
                                {{ rule.enabled ? 'Enabled' : 'Disabled' }}
                            </span>
                        </div>
                        <p class="mt-3 text-sm text-muted-foreground">Destination</p>
                        <p class="text-sm font-medium">{{ rule.destination_url }}</p>

                        <div class="mt-4 grid gap-2 text-xs text-muted-foreground">
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                Conditions
                            </p>
                            <ul class="grid gap-1">
                                <li
                                    v-for="condition in rule.conditions"
                                    :key="condition.id"
                                >
                                    {{ condition.condition_type }} ·
                                    {{ condition.operator }}
                                    <span v-if="formatConditionValue(condition.value)">
                                        — {{ formatConditionValue(condition.value) }}
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <Form
                v-else
                v-bind="links.dynamic.update.form(link.ulid)"
                v-slot="{ errors, processing }"
                class="mt-6 grid gap-6"
            >
                <DynamicRuleBuilder
                    v-model:fallbackDestination="fallbackDestination"
                    v-model:rules="rules"
                    :errors="errors"
                    :selectClass="selectClass"
                />

                <div class="flex flex-wrap gap-3">
                    <Button type="submit" :disabled="processing">
                        {{ processing ? 'Saving...' : 'Save changes' }}
                    </Button>
                </div>
            </Form>
        </section>

        <section
            v-if="isDynamic"
            class="mt-6 rounded-2xl border border-border/70 bg-card p-6"
        >
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold">Dynamic rule performance</p>
                    <p class="text-xs text-muted-foreground">
                        See which rules are winning and how often fallback is used.
                    </p>
                </div>
            </div>

            <div v-if="analytics.rule_breakdown.length" class="mt-5 grid gap-3">
                <div
                    v-for="rule in analytics.rule_breakdown"
                    :key="rule.id"
                    class="rounded-xl border border-border/70 bg-background p-4 text-sm"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <span class="rounded-md border border-border/70 px-2 py-0.5 text-xs">
                                Priority {{ rule.priority }}
                            </span>
                            <span
                                class="text-xs"
                                :class="rule.enabled ? 'text-emerald-500' : 'text-muted-foreground'"
                            >
                                {{ rule.enabled ? 'Enabled' : 'Disabled' }}
                            </span>
                        </div>
                        <span class="text-sm font-semibold">{{ rule.clicks }}</span>
                    </div>
                    <p class="mt-2 text-xs text-muted-foreground">Destination</p>
                    <p class="text-sm font-medium">{{ rule.destination_url }}</p>
                </div>
            </div>
            <p v-else class="mt-5 text-sm text-muted-foreground">
                No dynamic rule clicks yet.
            </p>

            <div class="mt-4 rounded-xl border border-border/70 bg-background p-4 text-sm">
                <div class="flex items-center justify-between">
                    <span class="text-muted-foreground">Fallback clicks</span>
                    <span class="font-semibold">{{ analytics.fallback_clicks ?? 0 }}</span>
                </div>
                <p class="mt-2 text-xs text-muted-foreground">
                    Used when no rules match or when a rule is disabled.
                </p>
            </div>
        </section>

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
