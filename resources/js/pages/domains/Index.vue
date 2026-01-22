<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { disable, destroy, store, verify } from '@/routes/domains';
import { Form, Head } from '@inertiajs/vue3';

type Domain = {
    id: number;
    hostname: string;
    status: 'pending_verification' | 'verified' | 'disabled';
    verification_method: string | null;
    verification_token: string | null;
    links_count: number;
};

defineProps<{
    domains: Domain[];
}>();

const statusLabel: Record<Domain['status'], string> = {
    pending_verification: 'Pending verification',
    verified: 'Verified',
    disabled: 'Disabled',
};

const statusVariant: Record<Domain['status'], 'default' | 'secondary' | 'outline'> = {
    pending_verification: 'outline',
    verified: 'secondary',
    disabled: 'default',
};

const recordName = (hostname: string) => `_shortlink.${hostname}`;
</script>

<template>
    <Head title="Domains" />

    <AppLayout
        title="Domains"
        description="Verify and manage the hostnames you want to use for short links."
    >
        <div class="grid gap-8">
            <section class="rounded-2xl border border-border/70 bg-card p-6">
                <Form
                    v-bind="store()"
                    v-slot="{ errors, processing }"
                    class="grid gap-4"
                >
                    <div class="grid gap-2">
                        <Label for="hostname">Custom domain</Label>
                        <Input
                            id="hostname"
                            name="hostname"
                            placeholder="go.yourbrand.com"
                            autocomplete="off"
                        />
                        <InputError :message="errors.hostname" />
                    </div>
                    <div class="flex flex-wrap items-center gap-3 text-sm text-muted-foreground">
                        <span>Add the hostname you control. We'll give you a DNS TXT record.</span>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <Button type="submit" :disabled="processing">
                            Add domain
                        </Button>
                    </div>
                </Form>
            </section>

            <section v-if="domains.length" class="grid gap-4">
                <div
                    v-for="domain in domains"
                    :key="domain.id"
                    class="rounded-2xl border border-border/70 bg-card p-6"
                >
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="space-y-1">
                            <p class="text-xs font-semibold uppercase text-muted-foreground">
                                Domain
                            </p>
                            <p class="text-lg font-semibold">{{ domain.hostname }}</p>
                        </div>
                        <Badge :variant="statusVariant[domain.status]">
                            {{ statusLabel[domain.status] }}
                        </Badge>
                    </div>

                    <div v-if="domain.status === 'pending_verification'" class="mt-4 grid gap-3 text-sm">
                        <p class="text-muted-foreground">
                            Add the TXT record below, then verify.
                        </p>
                        <div class="grid gap-2 rounded-xl border border-dashed border-border/70 p-4">
                            <div>
                                <p class="text-xs font-semibold uppercase text-muted-foreground">
                                    TXT record name
                                </p>
                                <p class="font-medium">{{ recordName(domain.hostname) }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase text-muted-foreground">
                                    TXT record value
                                </p>
                                <p class="font-mono text-xs">{{ domain.verification_token }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 text-sm text-muted-foreground">
                        <span>{{ domain.links_count }}</span>
                        <span> links</span>
                    </div>

                    <div class="mt-6 flex flex-wrap gap-2">
                        <Form
                            v-if="domain.status === 'pending_verification'"
                            v-bind="verify(domain.id)"
                        >
                            <Button type="submit" size="sm">Verify now</Button>
                        </Form>

                        <Form
                            v-if="domain.status !== 'disabled'"
                            v-bind="disable(domain.id)"
                        >
                            <Button type="submit" size="sm" variant="secondary">
                                Disable
                            </Button>
                        </Form>

                        <Form v-if="domain.links_count === 0" v-bind="destroy(domain.id)">
                            <Button type="submit" size="sm" variant="ghost">
                                Remove
                            </Button>
                        </Form>
                    </div>
                </div>
            </section>

            <section
                v-else
                class="rounded-2xl border border-dashed border-border/70 bg-card p-8 text-sm text-muted-foreground"
            >
                No domains yet. Add your first hostname to get started.
            </section>
        </div>
    </AppLayout>
</template>
