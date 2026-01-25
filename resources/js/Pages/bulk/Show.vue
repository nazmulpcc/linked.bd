<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { index } from '@/routes/bulk-imports';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

type Job = {
    id: string;
    status: string;
    total_count: number;
    processed_count: number;
    success_count: number;
    failed_count: number;
    created_at: string;
    started_at?: string | null;
    finished_at?: string | null;
};

type Item = {
    id: number;
    row_number: number;
    source_url: string;
    status: string;
    error_message: string | null;
    link_id: number | null;
    short_url: string | null;
    qr_status: string | null;
    qr_ready: boolean;
    qr_preview_url: string | null;
    qr_download_url: string | null;
    qr_png_download_url: string | null;
    updated_at: string | null;
};

const props = defineProps<{
    job: Job;
    items: Item[];
}>();

const jobState = ref<Job>({ ...props.job });
const itemsState = ref<Item[]>([...props.items]);

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Bulk shorten',
        href: index().url,
    },
    {
        title: 'Job',
        href: '#',
    },
];

const progress = computed(() => {
    if (!jobState.value.total_count) {
        return 0;
    }

    return Math.min(
        100,
        Math.round(
            (jobState.value.processed_count / jobState.value.total_count) * 100,
        ),
    );
});

const statusLabel = computed(() =>
    jobState.value.status.replaceAll('_', ' '),
);

const channelName = computed(() => `bulk-imports.${jobState.value.id}`);

const applyItemUpdates = (updates: Item[]) => {
    if (!updates.length) {
        return;
    }

    updates.forEach((update) => {
        const index = itemsState.value.findIndex((item) => item.id === update.id);

        if (index >= 0) {
            itemsState.value[index] = { ...itemsState.value[index], ...update };
            return;
        }

        itemsState.value.push(update);
    });

    itemsState.value.sort((a, b) => a.row_number - b.row_number);
};

onMounted(() => {
    if (!window.Echo) {
        return;
    }

    window.Echo.private(channelName.value).listen(
        '.bulk.import.updated',
        (event: { job?: Job; items?: Item[] }) => {
            if (event.job) {
                jobState.value = { ...jobState.value, ...event.job };
            }

            if (event.items) {
                applyItemUpdates(event.items);
            }
        },
    );
});

onBeforeUnmount(() => {
    if (window.Echo) {
        window.Echo.leave(channelName.value);
    }
});
</script>

<template>
    <Head title="Bulk job" />

    <AppLayout
        :breadcrumbs="breadcrumbItems"
        title="Bulk job"
        description="Track progress and see each generated short link as it becomes ready."
    >
        <div class="grid gap-8">
            <section class="rounded-2xl border border-border/70 bg-card p-6">
                <Heading
                    variant="small"
                    title="Job summary"
                    description="Updates in real time as the batch is processed."
                />

                <div class="mt-6 grid gap-4 text-sm">
                    <div class="flex items-center justify-between">
                        <span>Status</span>
                        <span class="font-semibold capitalize">{{ statusLabel }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Total URLs</span>
                        <span class="font-semibold">{{ jobState.total_count }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Processed</span>
                        <span class="font-semibold">{{ jobState.processed_count }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Succeeded</span>
                        <span class="font-semibold">{{ jobState.success_count }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Failed</span>
                        <span class="font-semibold">{{ jobState.failed_count }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Created at</span>
                        <span class="font-semibold">
                            {{ new Date(jobState.created_at).toLocaleString() }}
                        </span>
                    </div>
                    <div
                        v-if="jobState.started_at"
                        class="flex items-center justify-between"
                    >
                        <span>Started at</span>
                        <span class="font-semibold">
                            {{ new Date(jobState.started_at).toLocaleString() }}
                        </span>
                    </div>
                    <div
                        v-if="jobState.finished_at"
                        class="flex items-center justify-between"
                    >
                        <span>Finished at</span>
                        <span class="font-semibold">
                            {{ new Date(jobState.finished_at).toLocaleString() }}
                        </span>
                    </div>
                </div>

                <div class="mt-6">
                    <div class="flex items-center justify-between text-xs text-muted-foreground">
                        <span>Progress</span>
                        <span>{{ progress }}%</span>
                    </div>
                    <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-muted">
                        <div
                            class="h-full rounded-full bg-primary transition-all"
                            :style="{ width: `${progress}%` }"
                        ></div>
                    </div>
                </div>

                <div class="mt-6 flex flex-wrap gap-3">
                    <Button variant="secondary" as-child>
                        <Link :href="index()">Back to bulk shorten</Link>
                    </Button>
                </div>
            </section>

            <section class="rounded-2xl border border-border/70 bg-card p-6">
                <Heading
                    variant="small"
                    title="Items"
                    description="Each row updates as links and QR codes are generated."
                />

                <div v-if="!itemsState.length" class="mt-6 text-sm text-muted-foreground">
                    No items yet.
                </div>

                <div v-else class="mt-6 overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="border-b border-border/70 text-xs uppercase text-muted-foreground">
                            <tr>
                                <th class="py-3 pr-4">Row</th>
                                <th class="py-3 pr-4">Long URL</th>
                                <th class="py-3 pr-4">Short URL</th>
                                <th class="py-3 pr-4">QR</th>
                                <th class="py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/60">
                            <tr v-for="item in itemsState" :key="item.id">
                                <td class="py-4 pr-4 font-medium text-muted-foreground">
                                    {{ item.row_number }}
                                </td>
                                <td class="py-4 pr-4">
                                    <div class="max-w-[320px] truncate text-foreground">
                                        {{ item.source_url }}
                                    </div>
                                </td>
                                <td class="py-4 pr-4">
                                    <div v-if="item.short_url" class="flex flex-col gap-2">
                                        <a
                                            :href="item.short_url"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="font-medium text-primary"
                                        >
                                            {{ item.short_url }}
                                        </a>
                                        <div class="flex flex-wrap gap-2 text-xs text-muted-foreground">
                                            <a
                                                v-if="item.qr_download_url"
                                                :href="item.qr_download_url"
                                            >
                                                Download SVG
                                            </a>
                                            <a
                                                v-if="item.qr_png_download_url"
                                                :href="item.qr_png_download_url"
                                            >
                                                Download PNG
                                            </a>
                                        </div>
                                    </div>
                                    <span v-else class="text-xs text-muted-foreground">Pending</span>
                                </td>
                                <td class="py-4 pr-4">
                                    <div v-if="item.qr_ready && item.qr_preview_url" class="flex items-center gap-3">
                                        <img
                                            :src="item.qr_preview_url"
                                            alt="QR code"
                                            class="h-12 w-12 rounded-md border border-border/70 bg-white p-1"
                                        >
                                        <span class="text-xs text-muted-foreground">Ready</span>
                                    </div>
                                    <span v-else class="text-xs text-muted-foreground">Generating…</span>
                                </td>
                                <td class="py-4">
                                    <div class="flex flex-col gap-1">
                                        <span class="text-xs font-semibold uppercase">
                                            {{ item.status }}
                                        </span>
                                        <span
                                            v-if="item.error_message"
                                            class="text-xs text-destructive"
                                        >
                                            {{ item.error_message }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
