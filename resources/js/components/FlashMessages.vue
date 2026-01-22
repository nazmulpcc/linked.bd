<script setup lang="ts">
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { usePage } from '@inertiajs/vue3';
import { AlertCircle, CheckCircle2, Info, TriangleAlert } from 'lucide-vue-next';
import { computed } from 'vue';

type FlashMessages = {
    success?: string;
    error?: string;
    warning?: string;
    info?: string;
};

const page = usePage();
const flash = computed(() => (page.props.flash || {}) as FlashMessages);
</script>

<template>
    <div
        v-if="flash.success || flash.error || flash.warning || flash.info"
        class="flex flex-col gap-3"
    >
        <Alert v-if="flash.success">
            <CheckCircle2 class="size-4" />
            <AlertTitle>Success</AlertTitle>
            <AlertDescription>{{ flash.success }}</AlertDescription>
        </Alert>
        <Alert v-if="flash.info">
            <Info class="size-4" />
            <AlertTitle>FYI</AlertTitle>
            <AlertDescription>{{ flash.info }}</AlertDescription>
        </Alert>
        <Alert v-if="flash.warning">
            <TriangleAlert class="size-4" />
            <AlertTitle>Heads up</AlertTitle>
            <AlertDescription>{{ flash.warning }}</AlertDescription>
        </Alert>
        <Alert v-if="flash.error" variant="destructive">
            <AlertCircle class="size-4" />
            <AlertTitle>Something went wrong</AlertTitle>
            <AlertDescription>{{ flash.error }}</AlertDescription>
        </Alert>
    </div>
</template>
