<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TurnstileWidget from '@/components/TurnstileWidget.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { store } from '@/routes/links';
import { Form, Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type Domain = {
    id: number;
    hostname: string;
    type: 'platform' | 'custom';
};

type RuleCondition = {
    id: string;
    condition_type: string;
    operator: string;
    value: string;
    values: string[];
    time: {
        timezone: string;
        days: string[];
        hours: {
            start: number | null;
            end: number | null;
        };
    };
};

type Rule = {
    id: string;
    priority: number;
    destination_url: string;
    enabled: boolean;
    conditions: RuleCondition[];
};

const props = defineProps<{
    domains: Domain[];
    guestTtlDays: number;
    isGuest: boolean;
    turnstileSiteKey: string | null;
}>();

const linkType = ref<'static' | 'dynamic'>('static');
const selectedDomainId = ref(
    props.domains.length ? String(props.domains[0].id) : '',
);
const showAdvanced = ref(false);
const showPassword = ref(false);
const showExpiry = ref(false);
const fallbackDestination = ref('');

let ruleCounter = 0;
let conditionCounter = 0;

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

const createCondition = (): RuleCondition => ({
    id: `condition-${conditionCounter++}`,
    condition_type: 'country',
    operator: 'equals',
    value: '',
    values: [''],
    time: {
        timezone: '',
        days: [],
        hours: {
            start: null,
            end: null,
        },
    },
});

const createRule = (priority: number): Rule => ({
    id: `rule-${ruleCounter++}`,
    priority,
    destination_url: '',
    enabled: true,
    conditions: [createCondition()],
});

const rules = ref<Rule[]>([createRule(1)]);

const selectedDomain = computed(() =>
    props.domains.find((domain) => String(domain.id) === selectedDomainId.value),
);

const isCustomDomain = computed(() => selectedDomain.value?.type === 'custom');
const hasSingleDomain = computed(() => props.domains.length === 1);
const isDynamic = computed(() => linkType.value === 'dynamic');

const selectClass =
    'border-input text-foreground dark:bg-input/30 h-9 w-full rounded-md border bg-transparent px-3 py-1 text-base shadow-xs outline-none transition-[color,box-shadow] focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] md:text-sm';

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

const operatorOptionsFor = (condition: RuleCondition) =>
    operatorsByType[condition.condition_type] ?? operatorsByType.country;

const localValidationErrors = computed(() => {
    if (!isDynamic.value) {
        return [];
    }

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
</script>

<template>
    <Head title="Create link" />

    <AppLayout
        title="Create a short link"
        description="Pick a domain, drop in the destination, and ship it."
    >
        <div class="grid gap-8 lg:grid-cols-[2fr_1fr]">
            <section class="rounded-2xl border border-border/70 bg-card p-6">
                <Form
                    v-if="domains.length"
                    v-bind="store.form()"
                    v-slot="{ errors, processing }"
                    class="grid gap-6"
                >
                    <div class="grid gap-2">
                        <Label>Link type</Label>
                        <div class="flex flex-wrap gap-3 text-sm">
                            <label class="flex items-center gap-2">
                                <input
                                    v-model="linkType"
                                    type="radio"
                                    name="link_type"
                                    value="static"
                                    class="size-4 rounded-full border-input"
                                >
                                Static
                            </label>
                            <label class="flex items-center gap-2">
                                <input
                                    v-model="linkType"
                                    type="radio"
                                    name="link_type"
                                    value="dynamic"
                                    class="size-4 rounded-full border-input"
                                >
                                Dynamic
                            </label>
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="destination_url">
                            Destination URL
                        </Label>
                        <Input
                            v-if="!isDynamic"
                            id="destination_url"
                            name="destination_url"
                            type="url"
                            placeholder="https://yourbrand.com/launch"
                            autocomplete="off"
                        />
                        <InputError :message="errors.destination_url" />
                    </div>

                    <input
                        v-if="!showAdvanced"
                        type="hidden"
                        name="domain_id"
                        :value="selectedDomainId"
                    >

                    <div class="grid gap-3">
                        <Button
                            type="button"
                            variant="ghost"
                            class="w-fit px-0 text-sm"
                            @click="showAdvanced = !showAdvanced"
                        >
                            {{ showAdvanced ? 'Hide' : 'Show' }} advanced settings
                        </Button>
                        <p
                            v-if="hasSingleDomain"
                            class="text-xs text-muted-foreground"
                        >
                            <span v-if="isGuest">
                                Only one domain is available. Sign in to add more
                                domains and customize this.
                            </span>
                            <span v-else>
                                Only one domain is available. Add more domains if
                                you want to customize this.
                            </span>
                        </p>
                        <InputError :message="errors.domain_id" />
                    </div>

                    <div v-if="showAdvanced" class="grid gap-6">
                        <div v-if="!hasSingleDomain" class="grid gap-2">
                            <Label for="domain_id">Domain</Label>
                            <select
                                id="domain_id"
                                name="domain_id"
                                :class="selectClass"
                                v-model="selectedDomainId"
                            >
                                <option
                                    v-for="domain in domains"
                                    :key="domain.id"
                                    :value="String(domain.id)"
                                >
                                    {{ domain.hostname }}
                                    <span v-if="domain.type === 'platform'">
                                        (platform)
                                    </span>
                                </option>
                            </select>
                        </div>

                        <div v-if="isCustomDomain" class="grid gap-2">
                            <Label for="alias">Custom alias (optional)</Label>
                            <Input
                                id="alias"
                                name="alias"
                                placeholder="launch"
                                autocomplete="off"
                            />
                            <InputError :message="errors.alias" />
                        </div>

                        <div class="grid gap-3">
                            <label class="flex items-center gap-3 text-sm">
                                <input
                                    v-model="showPassword"
                                    type="checkbox"
                                    class="size-4 rounded border-input"
                                >
                                Add a password
                            </label>
                            <div v-if="showPassword" class="grid gap-2">
                                <Label for="password">Password</Label>
                                <Input
                                    id="password"
                                    name="password"
                                    type="password"
                                    placeholder="••••••••"
                                    autocomplete="new-password"
                                />
                                <InputError :message="errors.password" />
                            </div>
                        </div>

                        <div v-if="!isGuest" class="grid gap-3">
                            <label class="flex items-center gap-3 text-sm">
                                <input
                                    v-model="showExpiry"
                                    type="checkbox"
                                    class="size-4 rounded border-input"
                                >
                                Add an expiration
                            </label>
                            <div v-if="showExpiry" class="grid gap-2">
                                <Label for="expires_at">Expiration date</Label>
                                <Input
                                    id="expires_at"
                                    name="expires_at"
                                    type="datetime-local"
                                />
                                <InputError :message="errors.expires_at" />
                            </div>
                        </div>
                    </div>

                    <div v-if="isDynamic" class="grid gap-6">
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
                            <InputError
                                :message="errors.fallback_destination_url"
                            />
                        </div>

                        <div class="grid gap-3">
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
                    </div>

                    <p
                        v-if="isGuest"
                        class="text-xs text-muted-foreground"
                    >
                        Guest links expire after {{ guestTtlDays }} days by
                        default.
                    </p>

                    <div v-if="turnstileSiteKey" class="grid gap-2">
                        <TurnstileWidget :site-key="turnstileSiteKey" />
                        <InputError :message="errors['cf-turnstile-response']" />
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <Button type="submit" :disabled="processing">
                            {{ processing ? 'Creating...' : 'Create link' }}
                        </Button>
                    </div>
                </Form>
                <div
                    v-else
                    class="rounded-xl border border-dashed border-border/70 p-6 text-sm text-muted-foreground"
                >
                    No verified domains are available yet. Add and verify a
                    custom domain first.
                </div>
            </section>

            <aside class="rounded-2xl border border-border/70 bg-card p-6">
                <p class="text-sm font-semibold">What happens next</p>
                <ul class="mt-4 grid gap-3 text-sm text-muted-foreground">
                    <li>We generate a short code or alias for your domain.</li>
                    <li>A QR code is queued in the background.</li>
                    <li>You can copy the short link immediately.</li>
                </ul>
            </aside>
        </div>
    </AppLayout>
</template>
