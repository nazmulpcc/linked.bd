<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { create as linkCreate } from '@/routes/links';
import { history as bulkHistory, index as bulkIndex, show as bulkShow } from '@/routes/bulk-imports';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

type Job = {
    id: string;
    status: string;
    total_count: number;
    processed_count: number;
    success_count: number;
    failed_count: number;
    created_at: string;
};

type JobsPage = {
    data: Job[];
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
};

defineProps<{
    jobs: JobsPage;
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Bulk imports',
        href: bulkHistory().url,
    },
];
</script>

<template>
    <Head title="Bulk imports" />

    <AppLayout
        :breadcrumbs="breadcrumbItems"
        title="Bulk imports"
        description="Review previous bulk jobs and jump back into their progress."
    >
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="text-sm text-muted-foreground">
                {{ jobs.data.length ? `Showing ${jobs.data.length} jobs` : 'No jobs yet' }}
            </div>
            <div class="flex flex-wrap gap-2">
                <Link :href="bulkIndex()">
                    <Button size="sm" variant="secondary">New bulk job</Button>
                </Link>
                <Link :href="linkCreate()">
                    <Button size="sm">Create link</Button>
                </Link>
            </div>
        </div>

        <div v-if="jobs.data.length" class="mt-6 grid gap-4">
            <div
                v-for="job in jobs.data"
                :key="job.id"
                class="relative rounded-2xl border border-border/70 bg-card p-6"
            >
                <Link
                    :href="bulkShow(job.id)"
                    class="absolute inset-0 rounded-2xl"
                ></Link>
                <div class="relative z-10 pointer-events-none">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="space-y-2">
                            <p class="text-xs font-semibold uppercase text-muted-foreground">
                                Job {{ job.id }}
                            </p>
                            <p class="text-sm text-muted-foreground">
                                Created {{ new Date(job.created_at).toLocaleString() }}
                            </p>
                        </div>
                        <div class="text-right text-sm text-muted-foreground">
                            <p class="font-semibold capitalize text-foreground">
                                {{ job.status.replaceAll('_', ' ') }}
                            </p>
                            <p>{{ job.processed_count }} / {{ job.total_count }} processed</p>
                        </div>
                    </div>
                </div>

                <div class="relative z-10 mt-4 grid gap-3 text-sm text-muted-foreground">
                    <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                        <div>
                            <p class="text-xs uppercase">Succeeded</p>
                            <p class="text-foreground">{{ job.success_count }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase">Failed</p>
                            <p class="text-foreground">{{ job.failed_count }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase">Total</p>
                            <p class="text-foreground">{{ job.total_count }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase">Processed</p>
                            <p class="text-foreground">{{ job.processed_count }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="mt-6 rounded-2xl border border-dashed border-border/70 p-6 text-sm text-muted-foreground">
            <Heading
                variant="small"
                title="No bulk jobs yet"
                description="Start your first bulk import to see it listed here."
            />
            <div class="mt-4">
                <Link :href="bulkIndex()">
                    <Button size="sm">Start bulk shorten</Button>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
