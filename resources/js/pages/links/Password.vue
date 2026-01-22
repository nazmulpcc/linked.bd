<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { unlock } from '@/routes/links';
import { Form, Head } from '@inertiajs/vue3';

defineProps<{
    slug: string;
    shortUrl: string;
}>();
</script>

<template>
    <Head title="Protected link" />

    <AuthLayout
        title="Protected link"
        description="Enter the password to continue."
    >
        <Form
            v-bind="unlock(slug)"
            class="flex flex-col gap-4"
            #default="{ errors, processing }"
        >
            <div class="flex flex-col gap-2">
                <label class="text-sm font-medium" for="password">Password</label>
                <Input
                    id="password"
                    name="password"
                    type="password"
                    autocomplete="current-password"
                />
                <p v-if="errors.password" class="text-sm text-destructive">
                    {{ errors.password }}
                </p>
            </div>

            <div class="text-xs text-muted-foreground">
                You are unlocking <span class="font-semibold">{{ shortUrl }}</span>
            </div>

            <Button type="submit" :disabled="processing">
                {{ processing ? 'Checking...' : 'Unlock link' }}
            </Button>
        </Form>
    </AuthLayout>
</template>
