<script setup lang="ts">
import AppHeader from '@/components/AppHeader.vue';
import FlashMessages from '@/components/FlashMessages.vue';
import Heading from '@/components/Heading.vue';
import AppShell from '@/components/AppShell.vue';
import type { BreadcrumbItem } from '@/types';

type Props = {
    title?: string;
    description?: string;
    breadcrumbs?: BreadcrumbItem[];
};

defineProps<Props>();
</script>

<template>
    <AppShell variant="header">
        <AppHeader />
        <div class="flex-1 bg-background">
            <div class="relative">
                <div
                    class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top,_var(--tw-gradient-stops))] from-amber-50/80 via-transparent to-transparent dark:from-emerald-500/10"
                ></div>
                <div class="relative mx-auto w-full max-w-6xl px-4 py-10 animate-in fade-in slide-in-from-bottom-2 duration-700">
                    <FlashMessages class="mb-6" />
                    <div
                        v-if="$slots.header || title"
                        class="mb-8 flex flex-col gap-3"
                    >
                        <slot name="header">
                            <Heading
                                v-if="title"
                                :title="title"
                                :description="description"
                            />
                        </slot>
                    </div>
                    <slot />
                </div>
            </div>
        </div>
    </AppShell>
</template>
