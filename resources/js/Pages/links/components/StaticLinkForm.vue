<script setup lang="ts">
import { Button } from '@/components/ui/button';
import StaticLinkFields from '@/Pages/links/components/StaticLinkFields.vue';
import { store } from '@/routes/links';
import { Form } from '@inertiajs/vue3';
import { ref } from 'vue';

type Domain = {
    id: number;
    hostname: string;
    type: 'platform' | 'custom';
    redirection_id: number | null;
};

const props = defineProps<{
    domains: Domain[];
    guestTtlDays: number;
    isGuest: boolean;
    turnstileSiteKey: string | null;
    selectClass: string;
}>();

const selectedDomainId = ref(
    props.domains.length ? String(props.domains[0].id) : '',
);
const showAdvanced = ref(false);
const showPassword = ref(false);
const showExpiry = ref(false);
const rootRedirect = ref(false);
</script>

<template>
    <Form
        :action="store().url"
        method="post"
        v-slot="{ errors, processing }"
        class="grid gap-6"
    >
        <input type="hidden" name="link_type" value="static" />

        <StaticLinkFields
            v-model:selectedDomainId="selectedDomainId"
            v-model:showAdvanced="showAdvanced"
            v-model:showPassword="showPassword"
            v-model:showExpiry="showExpiry"
            v-model:rootRedirect="rootRedirect"
            :domains="domains"
            :errors="errors"
            :guestTtlDays="guestTtlDays"
            :isGuest="isGuest"
            :selectClass="selectClass"
            :turnstileSiteKey="turnstileSiteKey"
        />

        <div class="flex flex-wrap gap-3">
            <Button type="submit" :disabled="processing">
                {{ processing ? 'Creating...' : 'Create link' }}
            </Button>
        </div>
    </Form>
</template>
