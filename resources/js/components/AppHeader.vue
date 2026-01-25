<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { getInitials } from '@/composables/useInitials';
import { create, index as linksIndex } from '@/routes/links';
import { index as bulkImportsIndex } from '@/routes/bulk-imports';
import { dashboard, home, login } from '@/routes';
import { index as domainsIndex } from '@/routes/domains';
import type { NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

type AppNavItem = NavItem & {
    requiresAuth?: boolean;
};

const page = usePage();
const user = computed(() => page.props.auth.user);
const { isCurrentUrl, whenCurrentUrl } = useCurrentUrl();

const navItems = computed<AppNavItem[]>(() => [
    {
        title: 'Create',
        href: create(),
    },
    {
        title: 'Dashboard',
        href: dashboard(),
        requiresAuth: true,
    },
    {
        title: 'Links',
        href: linksIndex(),
        requiresAuth: true,
    },
    {
        title: 'Domains',
        href: domainsIndex(),
        requiresAuth: true,
    },
    {
        title: 'Bulk',
        href: bulkImportsIndex(),
        requiresAuth: true,
    },
]);

const visibleNavItems = computed(() =>
    navItems.value.filter((item) => !item.requiresAuth || user.value),
);
</script>

<template>
    <header
        class="sticky top-0 z-40 border-b border-border/60 bg-background/80 backdrop-blur"
    >
        <div
            class="mx-auto flex h-16 w-full max-w-6xl items-center gap-4 px-4"
        >
            <Link :href="home()" class="flex items-center gap-3">
                <AppLogo />
            </Link>

            <nav class="hidden items-center gap-2 md:flex">
                <Link
                    v-for="item in visibleNavItems"
                    :key="item.title"
                    :href="item.href"
                    class="rounded-full px-3 py-1.5 text-sm font-medium text-muted-foreground transition hover:text-foreground"
                    :class="
                        whenCurrentUrl(
                            item.href,
                            'bg-muted text-foreground',
                        )
                    "
                >
                    {{ item.title }}
                </Link>
            </nav>

            <div class="ml-auto flex items-center gap-2">
                <Link :href="create()" class="md:hidden">
                    <Button size="sm">Create</Button>
                </Link>

                <Link v-if="!user" :href="login()">
                    <Button variant="ghost" size="sm">Sign in</Button>
                </Link>

                <DropdownMenu v-else>
                    <DropdownMenuTrigger as-child>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="relative size-10 w-auto rounded-full p-1 focus-visible:ring-2 focus-visible:ring-ring"
                        >
                            <Avatar
                                class="size-8 overflow-hidden rounded-full"
                            >
                                <AvatarImage
                                    v-if="user?.avatar"
                                    :src="user.avatar"
                                    :alt="user.name"
                                />
                                <AvatarFallback
                                    class="rounded-full bg-muted font-semibold text-foreground"
                                >
                                    {{ getInitials(user?.name) }}
                                </AvatarFallback>
                            </Avatar>
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-56">
                        <UserMenuContent :user="user" />
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </div>
    </header>
</template>
