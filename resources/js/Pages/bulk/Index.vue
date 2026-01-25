<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { index } from '@/routes/bulk-imports';
import { type BreadcrumbItem } from '@/types';
import BulkImportController from '@/actions/App/Http/Controllers/BulkImports/BulkImportController';
import { Form, Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type Domain = {
    id: number;
    hostname: string;
    type: 'platform' | 'custom';
};

const props = defineProps<{
    domains: Domain[];
}>();

const urls = ref('');
const deduplicate = ref(true);
const showDefaults = ref(false);
const selectedDomainId = ref(
    props.domains.length ? String(props.domains[0].id) : '',
);

const lines = computed(() =>
    urls.value
        .split(/\r\n|\r|\n/)
        .map((line) => line.trim())
        .filter((line) => line.length > 0),
);

const uniqueLines = computed(() => {
    if (!deduplicate.value) {
        return lines.value;
    }

    return Array.from(new Set(lines.value));
});

const invalidLines = computed(() =>
    uniqueLines.value.filter(
        (line) => !/^https?:\/\//i.test(line),
    ),
);

const validCount = computed(
    () => uniqueLines.value.length - invalidLines.value.length,
);

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Bulk shorten',
        href: index().url,
    },
];
</script>

<template>
    <Head title="Bulk shorten" />

    <AppLayout
        :breadcrumbs="breadcrumbItems"
        title="Bulk shorten"
        description="Paste a list of URLs and generate short links in one batch."
    >
        <div class="grid gap-8 lg:grid-cols-[2fr_1fr]">
            <section class="rounded-2xl border border-border/70 bg-card p-6">
                <Form
                    v-if="domains.length"
                    v-bind="BulkImportController.store.form()"
                    v-slot="{ errors, processing }"
                    class="grid gap-6"
                >
                    <div class="grid gap-2">
                        <Label for="urls">Paste your URLs</Label>
                        <textarea
                            id="urls"
                            name="urls"
                            rows="10"
                            v-model="urls"
                            class="border-input text-foreground dark:bg-input/30 w-full rounded-xl border bg-transparent px-3 py-3 text-sm shadow-xs outline-none transition-[color,box-shadow] focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                            placeholder="https://example.com/first
https://example.com/second
https://example.com/third"
                        ></textarea>
                        <InputError :message="errors.urls" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="domain_id">Domain</Label>
                        <select
                            id="domain_id"
                            name="domain_id"
                            v-model="selectedDomainId"
                            class="border-input text-foreground dark:bg-input/30 h-10 w-full rounded-md border bg-transparent px-3 py-1 text-sm shadow-xs outline-none transition-[color,box-shadow] focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
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
                        <InputError :message="errors.domain_id" />
                    </div>

                    <div class="grid gap-3">
                        <label class="flex items-center gap-3 text-sm">
                            <input
                                v-model="deduplicate"
                                type="checkbox"
                                name="deduplicate"
                                value="1"
                                class="size-4 rounded border-input"
                            >
                            Deduplicate identical URLs
                        </label>
                    </div>

                    <div class="grid gap-3">
                        <Button
                            type="button"
                            variant="ghost"
                            class="w-fit px-0 text-sm"
                            @click="showDefaults = !showDefaults"
                        >
                            {{ showDefaults ? 'Hide' : 'Show' }} default options
                        </Button>

                        <div v-if="showDefaults" class="grid gap-6">
                            <div class="grid gap-2">
                                <Label for="password">Default password</Label>
                                <Input
                                    id="password"
                                    name="password"
                                    type="password"
                                    placeholder="Leave blank for none"
                                    autocomplete="new-password"
                                />
                                <InputError :message="errors.password" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="expires_at">Default expiration</Label>
                                <Input
                                    id="expires_at"
                                    name="expires_at"
                                    type="datetime-local"
                                />
                                <InputError :message="errors.expires_at" />
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <Button type="submit" :disabled="processing">
                            {{ processing ? 'Starting...' : 'Start bulk shorten' }}
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
                <Heading
                    variant="small"
                    title="Summary"
                    description="Review your batch before starting."
                />

                <div class="mt-4 grid gap-4 text-sm">
                    <div class="flex items-center justify-between">
                        <span>Total lines</span>
                        <span class="font-semibold">{{ lines.length }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Unique URLs</span>
                        <span class="font-semibold">{{ uniqueLines.length }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Valid URLs</span>
                        <span class="font-semibold">{{ validCount }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Invalid URLs</span>
                        <span class="font-semibold">{{ invalidLines.length }}</span>
                    </div>
                </div>

                <div v-if="invalidLines.length" class="mt-6 text-xs text-muted-foreground">
                    <p class="font-medium text-foreground">Invalid examples</p>
                    <ul class="mt-2 space-y-2">
                        <li
                            v-for="(line, idx) in invalidLines.slice(0, 4)"
                            :key="idx"
                            class="rounded-md border border-border/60 bg-muted/40 px-2 py-1"
                        >
                            {{ line }}
                        </li>
                    </ul>
                </div>
            </aside>
        </div>
    </AppLayout>
</template>
