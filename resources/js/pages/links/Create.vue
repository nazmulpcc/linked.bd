<script setup lang="ts">
import InputError from '@/components/InputError.vue';
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

const props = defineProps<{
    domains: Domain[];
    guestTtlDays: number;
    isGuest: boolean;
}>();

const selectedDomainId = ref(
    props.domains.length ? String(props.domains[0].id) : '',
);
const showAdvanced = ref(false);
const showPassword = ref(false);
const showExpiry = ref(false);

const selectedDomain = computed(() =>
    props.domains.find((domain) => String(domain.id) === selectedDomainId.value),
);

const isCustomDomain = computed(() => selectedDomain.value?.type === 'custom');

const selectClass =
    'border-input text-foreground dark:bg-input/30 h-9 w-full rounded-md border bg-transparent px-3 py-1 text-base shadow-xs outline-none transition-[color,box-shadow] focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] md:text-sm';
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
                        <InputError :message="errors.domain_id" />
                    </div>

                    <div v-if="showAdvanced" class="grid gap-6">
                        <div class="grid gap-2">
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

                        <div class="grid gap-3">
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
                            <p
                                v-else-if="isGuest"
                                class="text-xs text-muted-foreground"
                            >
                                Guest links expire after {{ guestTtlDays }} days by
                                default.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <Button type="submit" :disabled="processing">
                            Create link
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
