<script setup lang="ts">
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type Props = {
    shortUrl: string;
    destinationUrl: string;
    expiresAt: string | null;
    passwordProtected: boolean;
    qrReady: boolean;
    qrPreviewUrl: string | null;
    qrDownloadUrl: string | null;
};

const props = defineProps<Props>();
const copied = ref(false);

const expiresLabel = computed(() =>
    props.expiresAt ? new Date(props.expiresAt).toLocaleString() : 'Never',
);

const copyLink = async () => {
    if (!navigator?.clipboard) {
        return;
    }

    await navigator.clipboard.writeText(props.shortUrl);
    copied.value = true;
    window.setTimeout(() => {
        copied.value = false;
    }, 1500);
};
</script>

<template>
    <Head title="Link created" />

    <AppLayout
        title="Link created"
        description="Your short link is ready to share."
    >
        <div class="grid gap-8 lg:grid-cols-[2fr_1fr]">
            <section class="rounded-2xl border border-border/70 bg-card p-6">
                <div class="space-y-6">
                    <div class="space-y-2">
                        <p class="text-xs font-semibold uppercase text-muted-foreground">
                            Short link
                        </p>
                        <div class="flex flex-wrap items-center gap-3">
                            <p class="text-lg font-semibold">{{ shortUrl }}</p>
                            <Button size="sm" variant="secondary" @click="copyLink">
                                {{ copied ? 'Copied' : 'Copy' }}
                            </Button>
                        </div>
                    </div>

                    <div class="grid gap-3 text-sm text-muted-foreground">
                        <div class="flex items-center justify-between">
                            <span>Destination</span>
                            <span class="text-foreground">{{ destinationUrl }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Password protection</span>
                            <span class="text-foreground">
                                {{ passwordProtected ? 'Enabled' : 'Off' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Expiration</span>
                            <span class="text-foreground">{{ expiresLabel }}</span>
                        </div>
                    </div>
                </div>
            </section>

            <aside class="rounded-2xl border border-border/70 bg-card p-6">
                <p class="text-sm font-semibold">QR code</p>
                <div class="mt-4 rounded-xl border border-dashed border-border/70 p-4 text-sm text-muted-foreground">
                    <div v-if="qrReady && qrPreviewUrl" class="flex flex-col items-center gap-3">
                        <img
                            :src="qrPreviewUrl"
                            alt="QR code"
                            class="h-40 w-40 rounded-lg border border-border/70 bg-white p-2"
                        >
                        <p>Your QR code is ready to download.</p>
                    </div>
                    <p v-else>Generating your QR code…</p>
                </div>
                <Button
                    v-if="qrReady && qrDownloadUrl"
                    class="mt-4"
                    size="sm"
                    variant="secondary"
                    as-child
                >
                    <a :href="qrDownloadUrl">Download QR</a>
                </Button>
                <Button
                    v-else
                    class="mt-4"
                    size="sm"
                    variant="secondary"
                    disabled
                >
                    Download QR
                </Button>
            </aside>
        </div>
    </AppLayout>
</template>
