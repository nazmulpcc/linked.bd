<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TurnstileWidget from '@/components/TurnstileWidget.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { computed } from 'vue';

type Domain = {
    id: number;
    hostname: string;
    type: 'platform' | 'custom';
};

const props = defineProps<{
    domains: Domain[];
    isGuest: boolean;
    guestTtlDays: number;
    turnstileSiteKey: string | null;
    errors: Record<string, string>;
    selectClass: string;
}>();

const selectedDomainId = defineModel<string>('selectedDomainId', {
    required: true,
});
const showAdvanced = defineModel<boolean>('showAdvanced', {
    required: true,
});
const showPassword = defineModel<boolean>('showPassword', {
    required: true,
});
const showExpiry = defineModel<boolean>('showExpiry', {
    required: true,
});

const selectedDomain = computed(() =>
    props.domains.find((domain) => String(domain.id) === selectedDomainId.value),
);
const isCustomDomain = computed(() => selectedDomain.value?.type === 'custom');
const hasSingleDomain = computed(() => props.domains.length === 1);
</script>

<template>
    <div class="grid gap-2">
        <Label for="destination_url">Destination URL</Label>
        <Input
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
</template>
