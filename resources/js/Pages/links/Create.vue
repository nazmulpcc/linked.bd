<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import DynamicLinkFields from '@/Pages/links/components/DynamicLinkFields.vue';
import StaticLinkFields from '@/Pages/links/components/StaticLinkFields.vue';
import { createRule, type Rule } from '@/Pages/links/components/dynamicTypes';
import LinkController from '@/actions/App/Http/Controllers/Links/LinkController';
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
const rules = ref<Rule[]>([createRule(1)]);

const isDynamic = computed(() => linkType.value === 'dynamic');

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
                    v-bind="LinkController.store.form()"
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

                    <StaticLinkFields
                        v-if="!isDynamic"
                        v-model:selectedDomainId="selectedDomainId"
                        v-model:showAdvanced="showAdvanced"
                        v-model:showPassword="showPassword"
                        v-model:showExpiry="showExpiry"
                        :domains="domains"
                        :errors="errors"
                        :guestTtlDays="guestTtlDays"
                        :isGuest="isGuest"
                        :selectClass="selectClass"
                        :turnstileSiteKey="turnstileSiteKey"
                    />

                    <DynamicLinkFields
                        v-else
                        v-model:fallbackDestination="fallbackDestination"
                        v-model:rules="rules"
                        v-model:selectedDomainId="selectedDomainId"
                        v-model:showAdvanced="showAdvanced"
                        v-model:showPassword="showPassword"
                        v-model:showExpiry="showExpiry"
                        :domains="domains"
                        :errors="errors"
                        :guestTtlDays="guestTtlDays"
                        :isGuest="isGuest"
                        :selectClass="selectClass"
                        :turnstileSiteKey="turnstileSiteKey"
                    />

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
