<script setup lang="ts">
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import DynamicLinkForm from '@/Pages/links/components/DynamicLinkForm.vue';
import StaticLinkForm from '@/Pages/links/components/StaticLinkForm.vue';
import { index as bulkIndex } from '@/routes/bulk-imports';
import { Head, Link } from '@inertiajs/vue3';
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

const activeTab = ref<'static' | 'dynamic'>('static');
const isStatic = computed(() => activeTab.value === 'static');

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
                <div
                    v-if="domains.length"
                    class="space-y-6"
                >
                    <div class="flex flex-wrap items-center gap-3 rounded-full border border-border/70 bg-muted p-2">
                        <Button
                            type="button"
                            size="sm"
                            :variant="isStatic ? 'default' : 'ghost'"
                            class="rounded-full"
                            @click="activeTab = 'static'"
                        >
                            Short link
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            :variant="!isStatic ? 'default' : 'ghost'"
                            class="rounded-full"
                            @click="activeTab = 'dynamic'"
                        >
                            Dynamic link
                        </Button>
                        <Link :href="bulkIndex()" class="inline-flex">
                            <Button type="button" size="sm" variant="ghost" class="rounded-full">
                                Bulk import
                            </Button>
                        </Link>
                    </div>

                    <StaticLinkForm
                        v-if="isStatic"
                        :domains="domains"
                        :guestTtlDays="guestTtlDays"
                        :isGuest="isGuest"
                        :selectClass="selectClass"
                        :turnstileSiteKey="turnstileSiteKey"
                    />

                    <DynamicLinkForm
                        v-else
                        :domains="domains"
                        :guestTtlDays="guestTtlDays"
                        :isGuest="isGuest"
                        :selectClass="selectClass"
                        :turnstileSiteKey="turnstileSiteKey"
                    />
                </div>
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
