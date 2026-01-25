<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { index } from '@/routes/bulk-imports';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

type Job = {
    id: string;
    domain_id: number;
    total: number;
    status: string;
    created_at: string;
};

defineProps<{
    job: Job;
}>();

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
</script>

<template>
    <Head title="Bulk job" />

    <AppLayout
        :breadcrumbs="breadcrumbItems"
        title="Bulk job queued"
        description="We are preparing your bulk import. This page will show progress shortly."
    >
        <div class="rounded-2xl border border-border/70 bg-card p-6">
            <Heading
                variant="small"
                title="Job summary"
                description="Processing will appear here once the job starts."
            />

            <div class="mt-6 grid gap-4 text-sm">
                <div class="flex items-center justify-between">
                    <span>Status</span>
                    <span class="font-semibold capitalize">{{ job.status }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span>Total URLs</span>
                    <span class="font-semibold">{{ job.total }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span>Created at</span>
                    <span class="font-semibold">
                        {{ new Date(job.created_at).toLocaleString() }}
                    </span>
                </div>
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                <Button variant="secondary" as-child>
                    <Link :href="index()">Back to bulk shorten</Link>
                </Button>
            </div>
        </div>
    </AppLayout>
</template>
