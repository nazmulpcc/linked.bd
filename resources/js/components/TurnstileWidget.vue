<script setup lang="ts">
import { onMounted } from 'vue';

const props = defineProps<{
    siteKey: string;
}>();

onMounted(() => {
    if (typeof document === 'undefined') {
        return;
    }

    if (document.querySelector('script[data-turnstile]')) {
        return;
    }

    const script = document.createElement('script');
    script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js';
    script.async = true;
    script.defer = true;
    script.dataset.turnstile = '1';
    document.head.appendChild(script);
});
</script>

<template>
    <div class="cf-turnstile" :data-sitekey="siteKey"></div>
</template>
