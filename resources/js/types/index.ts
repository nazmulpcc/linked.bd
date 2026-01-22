export * from './auth';
export * from './navigation';
export * from './ui';

import type { Auth } from './auth';

export type AppPageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    name: string;
    auth: Auth;
    flash?: {
        success?: string;
        error?: string;
        warning?: string;
        info?: string;
    };
    sidebarOpen: boolean;
    [key: string]: unknown;
};
