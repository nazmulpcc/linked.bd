<script setup lang="ts">
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

type Props = {
    shortUrl: string;
    destinationUrl: string;
    expiresAt: string | null;
    passwordProtected: boolean;
    qrReady: boolean;
    qrChannel: string | null;
    qrPreviewUrl: string | null;
    qrDownloadUrl: string | null;
    qrPngDownloadUrl: string | null;
};

const props = defineProps<Props>();
const copied = ref(false);
const qrReadyState = ref(props.qrReady);
const qrPreviewState = ref(props.qrPreviewUrl);
const qrDownloadState = ref(props.qrDownloadUrl);
const qrPngDownloadState = ref(props.qrPngDownloadUrl);

const channelName = props.qrChannel;

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

onMounted(() => {
    if (!channelName || qrReadyState.value || !window.Echo) {
        return;
    }

    window.Echo.channel(channelName)
        .listen(
            '.link.qr.generated',
            (event: { previewUrl: string; downloadUrl: string; pngDownloadUrl: string }) => {
            qrReadyState.value = true;
            qrPreviewState.value = event.previewUrl;
            qrDownloadState.value = event.downloadUrl;
            qrPngDownloadState.value = event.pngDownloadUrl;
        });
});

onBeforeUnmount(() => {
    if (channelName && window.Echo) {
        window.Echo.leave(channelName);
    }
});
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
                    <div v-if="qrReadyState && qrPreviewState" class="flex flex-col items-center gap-3">
                        <img
                            :src="qrPreviewState"
                            alt="QR code"
                            class="h-40 w-40 rounded-lg border border-border/70 bg-white p-2"
                        >
                        <p>Your QR code is ready to download.</p>
                    </div>
                    <div v-else class="flex flex-col items-center gap-3">
                        <div class="h-40 w-40 animate-pulse rounded-lg border border-border/70 bg-muted"></div>
                        <p>Generating your QR code…</p>
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    <Button
                        v-if="qrReadyState && qrDownloadState"
                        size="sm"
                        variant="secondary"
                        as-child
                    >
                        <a :href="qrDownloadState">Download SVG</a>
                    </Button>
                    <Button
                        v-if="qrReadyState && qrPngDownloadState"
                        size="sm"
                        variant="outline"
                        as-child
                    >
                        <a :href="qrPngDownloadState">Download PNG</a>
                    </Button>
                    <Button
                        v-else
                        size="sm"
                        variant="secondary"
                        disabled
                    >
                        Download QR
                    </Button>
                </div>
            </aside>
        </div>
    </AppLayout>
</template>
