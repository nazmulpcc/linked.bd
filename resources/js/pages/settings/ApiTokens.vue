<script setup lang="ts">
import ApiTokenController from '@/actions/App/Http/Controllers/Settings/ApiTokenController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { index } from '@/routes/api-tokens';
import { type BreadcrumbItem } from '@/types';
import { Form, Head } from '@inertiajs/vue3';
import { Check, Copy } from 'lucide-vue-next';
import { computed, ref } from 'vue';

type Ability = {
    value: string;
    label: string;
};

type AbilityGroup = {
    label: string;
    abilities: Ability[];
};

type Token = {
    id: number;
    name: string;
    abilities: string[];
    last_used_at?: string | null;
    created_at?: string | null;
};

type Props = {
    abilities: AbilityGroup[];
    tokens: Token[];
    newToken?: string | null;
};

const props = defineProps<Props>();
const copied = ref(false);
const tokenValue = computed(() => props.newToken ?? '');
const hasNewToken = computed(() => tokenValue.value.length > 0);

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'API tokens',
        href: index().url,
    },
];

const copyToken = async (): Promise<void> => {
    if (!tokenValue.value || !navigator?.clipboard) {
        return;
    }

    await navigator.clipboard.writeText(tokenValue.value);
    copied.value = true;

    setTimeout(() => {
        copied.value = false;
    }, 2000);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="API tokens" />

        <h1 class="sr-only">API Tokens</h1>

        <SettingsLayout>
            <div class="space-y-6">
                <Heading
                    variant="small"
                    title="API tokens"
                    description="Create tokens to access the API from external tools"
                />

                <Alert v-if="hasNewToken">
                    <AlertTitle>Copy your new token now</AlertTitle>
                    <AlertDescription class="space-y-4">
                        <p class="text-sm text-muted-foreground">
                            This token is shown only once. Store it somewhere safe.
                        </p>
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                            <code
                                class="break-all rounded-md border border-border/60 bg-muted px-3 py-2 text-xs text-foreground"
                            >
                                {{ tokenValue }}
                            </code>
                            <Button
                                type="button"
                                variant="secondary"
                                class="gap-2"
                                @click="copyToken"
                            >
                                <Check v-if="copied" class="size-4" />
                                <Copy v-else class="size-4" />
                                {{ copied ? 'Copied' : 'Copy' }}
                            </Button>
                        </div>
                    </AlertDescription>
                </Alert>

                <Form
                    v-bind="ApiTokenController.store.form()"
                    class="space-y-6"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-2">
                        <Label for="token_name">Token name</Label>
                        <Input
                            id="token_name"
                            name="name"
                            placeholder="My integration"
                            autocomplete="off"
                            required
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <Label class="text-sm font-medium">Abilities</Label>
                            <InputError
                                :message="
                                    errors.abilities ?? errors['abilities.0']
                                "
                            />
                        </div>

                        <div class="space-y-5">
                            <div
                                v-for="group in abilities"
                                :key="group.label"
                                class="space-y-3"
                            >
                                <p class="text-xs font-medium uppercase text-muted-foreground">
                                    {{ group.label }}
                                </p>
                                <div class="grid gap-2">
                                    <Label
                                        v-for="ability in group.abilities"
                                        :key="ability.value"
                                        :for="`ability-${ability.value}`"
                                        class="flex items-center gap-3 text-sm font-normal"
                                    >
                                        <Checkbox
                                            :id="`ability-${ability.value}`"
                                            name="abilities[]"
                                            :value="ability.value"
                                        />
                                        <span>{{ ability.label }}</span>
                                    </Label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <Button type="submit" :disabled="processing">
                        Create token
                    </Button>
                </Form>
            </div>

            <div class="space-y-6">
                <Heading
                    variant="small"
                    title="Active tokens"
                    description="Revoke tokens you no longer need"
                />

                <div v-if="tokens.length" class="space-y-4">
                    <div
                        v-for="token in tokens"
                        :key="token.id"
                        class="rounded-xl border border-border/60 bg-card p-4 shadow-sm"
                    >
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div class="space-y-1">
                                <p class="text-sm font-medium text-foreground">
                                    {{ token.name }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    Created
                                    {{
                                        token.created_at
                                            ? new Date(
                                                  token.created_at,
                                              ).toLocaleString()
                                            : '—'
                                    }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    Last used
                                    {{
                                        token.last_used_at
                                            ? new Date(
                                                  token.last_used_at,
                                              ).toLocaleString()
                                            : 'Never'
                                    }}
                                </p>
                            </div>

                            <Dialog>
                                <DialogTrigger as-child>
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        class="text-destructive hover:text-destructive"
                                    >
                                        Revoke
                                    </Button>
                                </DialogTrigger>
                                <DialogContent>
                                    <Form
                                        v-bind="
                                            ApiTokenController.destroy.form(
                                                token.id,
                                            )
                                        "
                                        class="grid gap-6"
                                    >
                                        <DialogHeader class="space-y-3">
                                            <DialogTitle>
                                                Revoke this token?
                                            </DialogTitle>
                                            <DialogDescription>
                                                This will permanently revoke
                                                <span
                                                    class="font-medium text-foreground"
                                                >
                                                    {{ token.name }}
                                                </span>
                                                and any integration using it will stop working.
                                            </DialogDescription>
                                        </DialogHeader>

                                        <DialogFooter class="gap-2">
                                            <DialogClose as-child>
                                                <Button
                                                    type="button"
                                                    variant="secondary"
                                                >
                                                    Cancel
                                                </Button>
                                            </DialogClose>
                                            <Button
                                                type="submit"
                                                variant="destructive"
                                            >
                                                Revoke token
                                            </Button>
                                        </DialogFooter>
                                    </Form>
                                </DialogContent>
                            </Dialog>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-2">
                            <Badge
                                v-for="ability in token.abilities"
                                :key="ability"
                                variant="outline"
                            >
                                {{ ability }}
                            </Badge>
                            <span
                                v-if="token.abilities.length === 0"
                                class="text-xs text-muted-foreground"
                            >
                                No abilities assigned
                            </span>
                        </div>
                    </div>
                </div>

                <div
                    v-else
                    class="rounded-2xl border border-dashed border-border/70 bg-card p-6 text-sm text-muted-foreground"
                >
                    No API tokens yet. Create one to get started.
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
