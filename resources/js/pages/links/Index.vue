<script setup lang="ts">
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { create, destroy, show } from '@/routes/links';
import { Form, Head, Link } from '@inertiajs/vue3';

type LinkItem = {
    id: number;
    domain: string | null;
    short_path: string | null;
    short_url: string;
    destination_url: string;
    click_count: number;
    last_accessed_at: string | null;
    expires_at: string | null;
    is_expired: boolean;
    qr_ready: boolean;
    qr_download_url: string | null;
};

type LinksPage = {
    data: LinkItem[];
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
};

defineProps<{
    links: LinksPage;
}>();
</script>

<template>
    <Head title="Links" />

    <AppLayout
        title="Links"
        description="Your most recent links and their performance."
    >
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-muted-foreground">
                Showing {{ links.data.length }} links
            </p>
            <Link :href="create()">
                <Button size="sm">Create link</Button>
            </Link>
        </div>

        <div v-if="links.data.length" class="mt-6 grid gap-4">
            <div
                v-for="link in links.data"
                :key="link.id"
                class="relative rounded-2xl border border-border/70 bg-card p-6"
            >
                <a
                    :href="link.short_url"
                    class="absolute inset-0 rounded-2xl"
                    target="_blank"
                    rel="noopener noreferrer"
                ></a>
                <div class="relative z-10 pointer-events-none">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="space-y-2">
                            <p class="text-xs font-semibold uppercase text-muted-foreground">
                                Short link
                            </p>
                            <p class="text-lg font-semibold">{{ link.short_url }}</p>
                            <p class="text-sm text-muted-foreground">
                                {{ link.destination_url }}
                            </p>
                        </div>
                        <div class="text-right text-sm text-muted-foreground">
                            <p>{{ link.click_count }} clicks</p>
                            <p v-if="link.last_accessed_at">
                                Last accessed
                                {{ new Date(link.last_accessed_at).toLocaleString() }}
                            </p>
                            <p v-else>No clicks yet</p>
                        </div>
                    </div>
                </div>

                <div class="relative z-10 mt-4 flex flex-wrap items-center justify-between gap-3 text-sm">
                    <div class="text-muted-foreground">
                        <span v-if="link.expires_at">
                            Expires
                            {{ new Date(link.expires_at).toLocaleString() }}
                        </span>
                        <span v-else>Never expires</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <Button size="sm" variant="ghost" as-child class="pointer-events-auto">
                            <Link :href="show(link.id)">View analytics</Link>
                        </Button>
                        <Button
                            v-if="link.qr_ready && link.qr_download_url"
                            size="sm"
                            variant="ghost"
                            as-child
                            class="pointer-events-auto"
                        >
                            <a :href="link.qr_download_url">Download QR</a>
                        </Button>
                        <Form v-bind="destroy.form(link)" class="pointer-events-auto">
                            <Button type="submit" size="sm" variant="ghost">
                                Delete
                            </Button>
                        </Form>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-else
            class="mt-6 rounded-2xl border border-dashed border-border/70 bg-card p-8 text-sm text-muted-foreground"
        >
            No links yet. Create your first short link.
        </div>
    </AppLayout>
</template>
