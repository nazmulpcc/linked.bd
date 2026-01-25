<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { computed, onMounted, ref } from 'vue';
import {
    createCondition,
    createRule,
    type Rule,
    type RuleCondition,
} from './dynamicTypes';

const props = defineProps<{
    errors: Record<string, string>;
    selectClass: string;
}>();

const rules = defineModel<Rule[]>('rules', {
    required: true,
});
const fallbackDestination = defineModel<string>('fallbackDestination', {
    required: true,
});

const conditionTypeOptions = [
    { value: 'country', label: 'Country' },
    { value: 'device_type', label: 'Device type' },
    { value: 'operating_system', label: 'OS' },
    { value: 'browser', label: 'Browser' },
    { value: 'referrer_domain', label: 'Referrer domain' },
    { value: 'referrer_path', label: 'Referrer path' },
    { value: 'utm_source', label: 'UTM source' },
    { value: 'utm_medium', label: 'UTM medium' },
    { value: 'utm_campaign', label: 'UTM campaign' },
    { value: 'language', label: 'Language' },
    { value: 'time_window', label: 'Time window' },
];

const operatorsByType: Record<string, { value: string; label: string }[]> = {
    country: [
        { value: 'equals', label: 'Equals' },
        { value: 'not_equals', label: 'Not equals' },
        { value: 'in', label: 'In list' },
        { value: 'not_in', label: 'Not in list' },
    ],
    device_type: [
        { value: 'equals', label: 'Equals' },
        { value: 'not_equals', label: 'Not equals' },
        { value: 'in', label: 'In list' },
        { value: 'not_in', label: 'Not in list' },
    ],
    operating_system: [
        { value: 'equals', label: 'Equals' },
        { value: 'not_equals', label: 'Not equals' },
        { value: 'in', label: 'In list' },
        { value: 'not_in', label: 'Not in list' },
    ],
    browser: [
        { value: 'equals', label: 'Equals' },
        { value: 'not_equals', label: 'Not equals' },
        { value: 'in', label: 'In list' },
        { value: 'not_in', label: 'Not in list' },
    ],
    referrer_domain: [
        { value: 'equals', label: 'Equals' },
        { value: 'not_equals', label: 'Not equals' },
        { value: 'contains', label: 'Contains' },
        { value: 'not_contains', label: 'Not contains' },
        { value: 'starts_with', label: 'Starts with' },
        { value: 'ends_with', label: 'Ends with' },
        { value: 'exists', label: 'Exists' },
        { value: 'not_exists', label: 'Not exists' },
    ],
    referrer_path: [
        { value: 'equals', label: 'Equals' },
        { value: 'not_equals', label: 'Not equals' },
        { value: 'contains', label: 'Contains' },
        { value: 'not_contains', label: 'Not contains' },
        { value: 'starts_with', label: 'Starts with' },
        { value: 'ends_with', label: 'Ends with' },
        { value: 'exists', label: 'Exists' },
        { value: 'not_exists', label: 'Not exists' },
    ],
    utm_source: [
        { value: 'equals', label: 'Equals' },
        { value: 'not_equals', label: 'Not equals' },
        { value: 'in', label: 'In list' },
        { value: 'not_in', label: 'Not in list' },
        { value: 'contains', label: 'Contains' },
        { value: 'not_contains', label: 'Not contains' },
        { value: 'exists', label: 'Exists' },
        { value: 'not_exists', label: 'Not exists' },
    ],
    utm_medium: [
        { value: 'equals', label: 'Equals' },
        { value: 'not_equals', label: 'Not equals' },
        { value: 'in', label: 'In list' },
        { value: 'not_in', label: 'Not in list' },
        { value: 'contains', label: 'Contains' },
        { value: 'not_contains', label: 'Not contains' },
        { value: 'exists', label: 'Exists' },
        { value: 'not_exists', label: 'Not exists' },
    ],
    utm_campaign: [
        { value: 'equals', label: 'Equals' },
        { value: 'not_equals', label: 'Not equals' },
        { value: 'in', label: 'In list' },
        { value: 'not_in', label: 'Not in list' },
        { value: 'contains', label: 'Contains' },
        { value: 'not_contains', label: 'Not contains' },
        { value: 'exists', label: 'Exists' },
        { value: 'not_exists', label: 'Not exists' },
    ],
    language: [
        { value: 'equals', label: 'Equals' },
        { value: 'not_equals', label: 'Not equals' },
        { value: 'starts_with', label: 'Starts with' },
        { value: 'in', label: 'In list' },
        { value: 'not_in', label: 'Not in list' },
    ],
    time_window: [{ value: 'equals', label: 'Matches window' }],
};

const dayOptions = [
    'monday',
    'tuesday',
    'wednesday',
    'thursday',
    'friday',
    'saturday',
    'sunday',
];

const templateOptions = [
    {
        id: 'mobile-desktop',
        label: 'Mobile vs Desktop',
        description: 'Send mobile users to one URL and desktop users to another.',
        create: () => [
            {
                priority: 1,
                destination_url: '',
                enabled: true,
                conditions: [
                    {
                        condition_type: 'device_type',
                        operator: 'equals',
                        value: 'mobile',
                    },
                ],
            },
            {
                priority: 2,
                destination_url: '',
                enabled: true,
                conditions: [
                    {
                        condition_type: 'device_type',
                        operator: 'equals',
                        value: 'desktop',
                    },
                ],
            },
        ],
    },
    {
        id: 'country-split',
        label: 'Country split',
        description: 'Route specific countries to a localized destination.',
        create: () => [
            {
                priority: 1,
                destination_url: '',
                enabled: true,
                conditions: [
                    {
                        condition_type: 'country',
                        operator: 'in',
                        values: ['US', 'CA'],
                    },
                ],
            },
        ],
    },
    {
        id: 'referrer-split',
        label: 'Referrer split',
        description: 'Send traffic from a campaign domain to a tailored page.',
        create: () => [
            {
                priority: 1,
                destination_url: '',
                enabled: true,
                conditions: [
                    {
                        condition_type: 'referrer_domain',
                        operator: 'contains',
                        value: 'instagram.com',
                    },
                ],
            },
        ],
    },
];

const previewContext = ref({
    country: '',
    device_type: 'desktop',
    operating_system: '',
    browser: '',
    referrer_domain: '',
    referrer_path: '',
    utm_source: '',
    utm_medium: '',
    utm_campaign: '',
    language: '',
    day: '',
    hour: null as number | null,
    timezone: '',
});

onMounted(() => {
    if (!rules.value.length) {
        rules.value = [createRule(1)];
    }
});

const operatorOptionsFor = (condition: RuleCondition) =>
    operatorsByType[condition.condition_type] ?? operatorsByType.country;

const addRule = () => {
    rules.value.push(createRule(rules.value.length + 1));
    normalizeRulePriorities();
};

const removeRule = (index: number) => {
    rules.value.splice(index, 1);
    normalizeRulePriorities();
};

const moveRule = (index: number, direction: -1 | 1) => {
    const target = index + direction;

    if (target < 0 || target >= rules.value.length) {
        return;
    }

    const [rule] = rules.value.splice(index, 1);
    rules.value.splice(target, 0, rule);
    normalizeRulePriorities();
};

const normalizeRulePriorities = () => {
    rules.value.forEach((rule, index) => {
        rule.priority = index + 1;
    });
};

const addCondition = (rule: Rule) => {
    rule.conditions.push(createCondition());
};

const removeCondition = (rule: Rule, index: number) => {
    rule.conditions.splice(index, 1);
};

const addListValue = (condition: RuleCondition) => {
    condition.values.push('');
};

const removeListValue = (condition: RuleCondition, index: number) => {
    condition.values.splice(index, 1);
};

const onConditionTypeChange = (condition: RuleCondition) => {
    const availableOperators = operatorOptionsFor(condition);
    condition.operator = availableOperators[0].value;
    condition.value = '';
    condition.values = [''];
    condition.time = {
        timezone: '',
        days: [],
        hours: {
            start: null,
            end: null,
        },
    };
};

const operatorExpectsList = (operator: string) =>
    operator === 'in' || operator === 'not_in';

const operatorExpectsNoValue = (operator: string) =>
    operator === 'exists' || operator === 'not_exists';

const localValidationErrors = computed(() => {
    const errors: string[] = [];

    if (!fallbackDestination.value.trim()) {
        errors.push('Fallback destination is required.');
    }

    const priorities = rules.value.map((rule) => rule.priority);
    const uniquePriorities = new Set(priorities);

    if (uniquePriorities.size !== priorities.length) {
        errors.push('Rule priorities must be unique.');
    }

    rules.value.forEach((rule, ruleIndex) => {
        if (!rule.destination_url.trim()) {
            errors.push(`Rule ${ruleIndex + 1} needs a destination URL.`);
        }

        rule.conditions.forEach((condition, conditionIndex) => {
            if (condition.condition_type === 'country') {
                const values = operatorExpectsList(condition.operator)
                    ? condition.values
                    : [condition.value];

                values.forEach((value) => {
                    if (value && !/^[A-Za-z]{2}$/.test(value)) {
                        errors.push(
                            `Rule ${ruleIndex + 1}, condition ${conditionIndex + 1} needs ISO country codes.`,
                        );
                    }
                });
            }
        });
    });

    return errors;
});

const applyTemplate = (templateId: string) => {
    const template = templateOptions.find((option) => option.id === templateId);

    if (!template) {
        return;
    }

    const templateRules = template.create().map((rule, index) => {
        const conditions = rule.conditions.map((condition) => ({
            id: createCondition().id,
            condition_type: condition.condition_type,
            operator: condition.operator,
            value: condition.value ?? '',
            values: condition.values ?? [''],
            time: {
                timezone: '',
                days: [],
                hours: {
                    start: null,
                    end: null,
                },
            },
        }));

        return {
            id: createRule(index + 1).id,
            priority: index + 1,
            destination_url: rule.destination_url,
            enabled: rule.enabled,
            conditions,
        } satisfies Rule;
    });

    rules.value = templateRules.length ? templateRules : [createRule(1)];
    normalizeRulePriorities();
};

const previewResult = computed(() => {
    const context = {
        country: previewContext.value.country.trim().toUpperCase() || null,
        device_type: previewContext.value.device_type || null,
        operating_system: previewContext.value.operating_system || null,
        browser: previewContext.value.browser || null,
        referrer_domain: previewContext.value.referrer_domain.trim().toLowerCase() || null,
        referrer_path: previewContext.value.referrer_path.trim().toLowerCase() || null,
        utm_source: previewContext.value.utm_source.trim().toLowerCase() || null,
        utm_medium: previewContext.value.utm_medium.trim().toLowerCase() || null,
        utm_campaign: previewContext.value.utm_campaign.trim().toLowerCase() || null,
        language: previewContext.value.language.trim().toLowerCase() || null,
        day: previewContext.value.day || null,
        hour: previewContext.value.hour,
        timezone: previewContext.value.timezone.trim() || null,
    };

    for (const rule of rules.value) {
        if (!rule.enabled) {
            continue;
        }

        const matches = rule.conditions.every((condition) =>
            conditionMatchesPreview(condition, context),
        );

        if (matches) {
            return {
                rule,
                destination: rule.destination_url,
            };
        }
    }

    return {
        rule: null,
        destination: fallbackDestination.value || 'Fallback',
    };
});

const conditionMatchesPreview = (
    condition: RuleCondition,
    context: {
        country: string | null;
        device_type: string | null;
        operating_system: string | null;
        browser: string | null;
        referrer_domain: string | null;
        referrer_path: string | null;
        utm_source: string | null;
        utm_medium: string | null;
        utm_campaign: string | null;
        language: string | null;
        day: string | null;
        hour: number | null;
        timezone: string | null;
    },
): boolean => {
    if (condition.condition_type === 'time_window') {
        return previewTimeWindowMatch(condition, context);
    }

    const target = matchTarget(condition.condition_type, context);

    return previewValueMatch(target, condition);
};

const matchTarget = (
    type: string,
    context: Record<string, string | number | null>,
) => {
    switch (type) {
        case 'country':
            return context.country;
        case 'device_type':
            return context.device_type;
        case 'operating_system':
            return context.operating_system;
        case 'browser':
            return context.browser;
        case 'referrer_domain':
            return context.referrer_domain;
        case 'referrer_path':
            return context.referrer_path;
        case 'utm_source':
            return context.utm_source;
        case 'utm_medium':
            return context.utm_medium;
        case 'utm_campaign':
            return context.utm_campaign;
        case 'language':
            return context.language;
        default:
            return null;
    }
};

const previewValueMatch = (
    target: string | number | null,
    condition: RuleCondition,
): boolean => {
    if (operatorExpectsNoValue(condition.operator)) {
        return condition.operator === 'exists'
            ? target !== null && String(target).trim() !== ''
            : target === null || String(target).trim() === '';
    }

    if (target === null || String(target).trim() === '') {
        return false;
    }

    const normalizedTarget = String(target).toLowerCase();
    const values = operatorExpectsList(condition.operator)
        ? condition.values.map((value) => value.trim().toLowerCase()).filter(Boolean)
        : [condition.value.trim().toLowerCase()].filter(Boolean);

    if (values.length === 0) {
        return false;
    }

    switch (condition.operator) {
        case 'equals':
            return normalizedTarget === values[0];
        case 'not_equals':
            return normalizedTarget !== values[0];
        case 'in':
            return values.includes(normalizedTarget);
        case 'not_in':
            return !values.includes(normalizedTarget);
        case 'contains':
            return normalizedTarget.includes(values[0]);
        case 'not_contains':
            return !normalizedTarget.includes(values[0]);
        case 'starts_with':
            return normalizedTarget.startsWith(values[0]);
        case 'ends_with':
            return normalizedTarget.endsWith(values[0]);
        default:
            return false;
    }
};

const previewTimeWindowMatch = (
    condition: RuleCondition,
    context: {
        day: string | null;
        hour: number | null;
        timezone: string | null;
    },
): boolean => {
    if (condition.operator !== 'equals') {
        return false;
    }

    const timezone = condition.time.timezone.trim();

    if (!timezone) {
        return false;
    }

    if (context.timezone && context.timezone !== timezone) {
        return false;
    }

    if (condition.time.days.length) {
        if (!context.day) {
            return false;
        }

        if (!condition.time.days.includes(context.day)) {
            return false;
        }
    }

    const start = condition.time.hours.start;
    const end = condition.time.hours.end;

    if (start !== null && end !== null) {
        if (context.hour === null) {
            return false;
        }

        if (start <= end) {
            if (context.hour < start || context.hour > end) {
                return false;
            }
        } else if (context.hour < start && context.hour > end) {
            return false;
        }
    }

    return true;
};
</script>

<template>
    <div class="grid gap-6">
        <div class="grid gap-2">
            <Label for="fallback_destination_url">
                Fallback destination
            </Label>
            <Input
                id="fallback_destination_url"
                v-model="fallbackDestination"
                name="fallback_destination_url"
                type="url"
                placeholder="https://yourbrand.com/fallback"
                autocomplete="off"
            />
            <InputError :message="errors.fallback_destination_url" />
        </div>

        <div class="grid gap-3">
            <div class="grid gap-2">
                <p class="text-sm font-semibold">Rule templates</p>
                <div class="grid gap-2 sm:grid-cols-3">
                    <button
                        v-for="template in templateOptions"
                        :key="template.id"
                        type="button"
                        class="rounded-xl border border-border/70 bg-card p-3 text-left text-xs transition hover:border-primary/40 hover:bg-primary/5"
                        @click="applyTemplate(template.id)"
                    >
                        <p class="text-sm font-semibold">
                            {{ template.label }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ template.description }}
                        </p>
                    </button>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm font-semibold">
                    Routing rules
                </p>
                <Button
                    type="button"
                    variant="ghost"
                    class="h-8 px-2 text-xs"
                    @click="addRule"
                >
                    Add rule
                </Button>
            </div>

            <div
                v-if="localValidationErrors.length"
                class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-600"
            >
                <ul class="grid gap-1">
                    <li
                        v-for="message in localValidationErrors"
                        :key="message"
                    >
                        {{ message }}
                    </li>
                </ul>
            </div>

            <div class="grid gap-4">
                <section
                    v-for="(rule, ruleIndex) in rules"
                    :key="rule.id"
                    class="rounded-xl border border-border/70 bg-background p-4"
                >
                    <input
                        type="hidden"
                        :name="`rules[${ruleIndex}][priority]`"
                        :value="rule.priority"
                    >
                    <input
                        type="hidden"
                        :name="`rules[${ruleIndex}][enabled]`"
                        value="0"
                    >
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2 text-sm font-semibold">
                            <span>Rule</span>
                            <span class="rounded-md border border-border/70 px-2 py-0.5 text-xs">
                                Priority {{ rule.priority }}
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="flex items-center gap-2 text-xs text-muted-foreground">
                                <input
                                    type="checkbox"
                                    :checked="rule.enabled"
                                    :name="`rules[${ruleIndex}][enabled]`"
                                    value="1"
                                    class="size-3.5 rounded border-input"
                                    @change="rule.enabled = ($event.target as HTMLInputElement).checked"
                                >
                                Enabled
                            </label>
                            <div class="flex items-center gap-1">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    class="h-7 px-2 text-xs"
                                    :disabled="ruleIndex === 0"
                                    @click="moveRule(ruleIndex, -1)"
                                >
                                    Up
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    class="h-7 px-2 text-xs"
                                    :disabled="ruleIndex === rules.length - 1"
                                    @click="moveRule(ruleIndex, 1)"
                                >
                                    Down
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    class="h-7 px-2 text-xs text-rose-500"
                                    :disabled="rules.length === 1"
                                    @click="removeRule(ruleIndex)"
                                >
                                    Remove
                                </Button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-2">
                        <Label :for="`rules.${ruleIndex}.destination`">
                            Destination URL
                        </Label>
                        <Input
                            :id="`rules.${ruleIndex}.destination`"
                            v-model="rule.destination_url"
                            :name="`rules[${ruleIndex}][destination_url]`"
                            type="url"
                            placeholder="https://yourbrand.com/offer"
                            autocomplete="off"
                        />
                        <InputError
                            :message="errors[`rules.${ruleIndex}.destination_url`]"
                        />
                    </div>

                    <div class="mt-4 grid gap-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                Conditions (all must match)
                            </p>
                            <Button
                                type="button"
                                variant="ghost"
                                class="h-7 px-2 text-xs"
                                @click="addCondition(rule)"
                            >
                                Add condition
                            </Button>
                        </div>

                        <div class="grid gap-3">
                            <div
                                v-for="(condition, conditionIndex) in rule.conditions"
                                :key="condition.id"
                                class="rounded-lg border border-border/70 bg-card p-3"
                            >
                                <div class="grid gap-2 md:grid-cols-[1.2fr_1fr]">
                                    <div class="grid gap-1">
                                        <Label class="text-xs">
                                            Type
                                        </Label>
                                        <select
                                            v-model="condition.condition_type"
                                            :name="`rules[${ruleIndex}][conditions][${conditionIndex}][condition_type]`"
                                            :class="selectClass"
                                            @change="onConditionTypeChange(condition)"
                                        >
                                            <option
                                                v-for="option in conditionTypeOptions"
                                                :key="option.value"
                                                :value="option.value"
                                            >
                                                {{ option.label }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="grid gap-1">
                                        <Label class="text-xs">
                                            Operator
                                        </Label>
                                        <select
                                            v-model="condition.operator"
                                            :name="`rules[${ruleIndex}][conditions][${conditionIndex}][operator]`"
                                            :class="selectClass"
                                        >
                                            <option
                                                v-for="option in operatorOptionsFor(condition)"
                                                :key="option.value"
                                                :value="option.value"
                                            >
                                                {{ option.label }}
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <div
                                    v-if="condition.condition_type !== 'time_window'"
                                    class="mt-3 grid gap-2"
                                >
                                    <Label class="text-xs">
                                        Value
                                    </Label>
                                    <div
                                        v-if="operatorExpectsNoValue(condition.operator)"
                                        class="text-xs text-muted-foreground"
                                    >
                                        No value needed for this operator.
                                    </div>
                                    <template v-else-if="operatorExpectsList(condition.operator)">
                                        <div class="grid gap-2">
                                            <div
                                                v-for="(value, valueIndex) in condition.values"
                                                :key="`${condition.id}-value-${valueIndex}`"
                                                class="flex items-center gap-2"
                                            >
                                                <Input
                                                    v-model="condition.values[valueIndex]"
                                                    :name="`rules[${ruleIndex}][conditions][${conditionIndex}][value][]`"
                                                    placeholder="Value"
                                                />
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    class="h-8 px-2 text-xs text-rose-500"
                                                    :disabled="condition.values.length === 1"
                                                    @click="removeListValue(condition, valueIndex)"
                                                >
                                                    Remove
                                                </Button>
                                            </div>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                class="h-7 w-fit px-2 text-xs"
                                                @click="addListValue(condition)"
                                            >
                                                Add value
                                            </Button>
                                        </div>
                                    </template>
                                    <template v-else>
                                        <Input
                                            v-model="condition.value"
                                            :name="`rules[${ruleIndex}][conditions][${conditionIndex}][value]`"
                                            placeholder="Value"
                                        />
                                    </template>
                                    <InputError
                                        :message="errors[`rules.${ruleIndex}.conditions.${conditionIndex}.value`]"
                                    />
                                </div>

                                <div
                                    v-else
                                    class="mt-3 grid gap-3"
                                >
                                    <div class="grid gap-2">
                                        <Label class="text-xs">
                                            Timezone
                                        </Label>
                                        <Input
                                            v-model="condition.time.timezone"
                                            :name="`rules[${ruleIndex}][conditions][${conditionIndex}][value][timezone]`"
                                            placeholder="America/New_York"
                                        />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label class="text-xs">
                                            Days (optional)
                                        </Label>
                                        <div class="flex flex-wrap gap-2 text-xs text-muted-foreground">
                                            <label
                                                v-for="day in dayOptions"
                                                :key="day"
                                                class="flex items-center gap-2"
                                            >
                                                <input
                                                    v-model="condition.time.days"
                                                    type="checkbox"
                                                    :value="day"
                                                    :name="`rules[${ruleIndex}][conditions][${conditionIndex}][value][days][]`"
                                                    class="size-3.5 rounded border-input"
                                                >
                                                {{ day }}
                                            </label>
                                        </div>
                                    </div>
                                    <div class="grid gap-2">
                                        <Label class="text-xs">
                                            Hours (optional)
                                        </Label>
                                        <div class="flex items-center gap-2">
                                            <Input
                                                v-model.number="condition.time.hours.start"
                                                :name="`rules[${ruleIndex}][conditions][${conditionIndex}][value][hours][start]`"
                                                type="number"
                                                min="0"
                                                max="23"
                                                placeholder="Start"
                                            />
                                            <Input
                                                v-model.number="condition.time.hours.end"
                                                :name="`rules[${ruleIndex}][conditions][${conditionIndex}][value][hours][end]`"
                                                type="number"
                                                min="0"
                                                max="23"
                                                placeholder="End"
                                            />
                                        </div>
                                    </div>
                                    <InputError
                                        :message="errors[`rules.${ruleIndex}.conditions.${conditionIndex}.value`]"
                                    />
                                </div>

                                <div class="mt-3 flex justify-end">
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        class="h-7 px-2 text-xs text-rose-500"
                                        :disabled="rule.conditions.length === 1"
                                        @click="removeCondition(rule, conditionIndex)"
                                    >
                                        Remove condition
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <div class="grid gap-4 rounded-xl border border-dashed border-border/70 bg-background p-4">
            <div class="flex items-center justify-between gap-3">
                <p class="text-sm font-semibold">Preview evaluation</p>
                <span class="text-xs text-muted-foreground">
                    Simulate a visit to see which rule wins
                </span>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <div class="grid gap-2">
                    <Label class="text-xs">Country</Label>
                    <Input v-model="previewContext.country" placeholder="US" />
                </div>
                <div class="grid gap-2">
                    <Label class="text-xs">Device type</Label>
                    <select v-model="previewContext.device_type" :class="selectClass">
                        <option value="">Select</option>
                        <option value="mobile">Mobile</option>
                        <option value="desktop">Desktop</option>
                        <option value="tablet">Tablet</option>
                    </select>
                </div>
                <div class="grid gap-2">
                    <Label class="text-xs">OS</Label>
                    <select v-model="previewContext.operating_system" :class="selectClass">
                        <option value="">Select</option>
                        <option value="ios">iOS</option>
                        <option value="android">Android</option>
                        <option value="windows">Windows</option>
                        <option value="macos">macOS</option>
                        <option value="linux">Linux</option>
                    </select>
                </div>
                <div class="grid gap-2">
                    <Label class="text-xs">Browser</Label>
                    <select v-model="previewContext.browser" :class="selectClass">
                        <option value="">Select</option>
                        <option value="chrome">Chrome</option>
                        <option value="safari">Safari</option>
                        <option value="firefox">Firefox</option>
                        <option value="edge">Edge</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="grid gap-2">
                    <Label class="text-xs">Referrer domain</Label>
                    <Input v-model="previewContext.referrer_domain" placeholder="instagram.com" />
                </div>
                <div class="grid gap-2">
                    <Label class="text-xs">Referrer path</Label>
                    <Input v-model="previewContext.referrer_path" placeholder="/stories" />
                </div>
                <div class="grid gap-2">
                    <Label class="text-xs">UTM source</Label>
                    <Input v-model="previewContext.utm_source" placeholder="newsletter" />
                </div>
                <div class="grid gap-2">
                    <Label class="text-xs">UTM medium</Label>
                    <Input v-model="previewContext.utm_medium" placeholder="email" />
                </div>
                <div class="grid gap-2">
                    <Label class="text-xs">UTM campaign</Label>
                    <Input v-model="previewContext.utm_campaign" placeholder="spring-launch" />
                </div>
                <div class="grid gap-2">
                    <Label class="text-xs">Language</Label>
                    <Input v-model="previewContext.language" placeholder="en-US" />
                </div>
            </div>
            <div class="grid gap-4 md:grid-cols-3">
                <div class="grid gap-2">
                    <Label class="text-xs">Day</Label>
                    <select v-model="previewContext.day" :class="selectClass">
                        <option value="">Select</option>
                        <option v-for="day in dayOptions" :key="day" :value="day">
                            {{ day }}
                        </option>
                    </select>
                </div>
                <div class="grid gap-2">
                    <Label class="text-xs">Hour (0-23)</Label>
                    <Input v-model.number="previewContext.hour" type="number" min="0" max="23" />
                </div>
                <div class="grid gap-2">
                    <Label class="text-xs">Timezone</Label>
                    <Input v-model="previewContext.timezone" placeholder="America/New_York" />
                </div>
            </div>
            <div class="rounded-lg border border-border/70 bg-card px-3 py-2 text-xs text-muted-foreground">
                <p class="text-xs font-semibold text-foreground">Preview result</p>
                <p v-if="previewResult?.rule">
                    Rule priority {{ previewResult.rule.priority }} →
                    <span class="text-foreground">{{ previewResult.destination || 'Destination missing' }}</span>
                </p>
                <p v-else>
                    Fallback →
                    <span class="text-foreground">{{ previewResult?.destination || 'Destination missing' }}</span>
                </p>
            </div>
        </div>
    </div>
</template>
