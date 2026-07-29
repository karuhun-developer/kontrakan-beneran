import { ModalStackProvider, putConfig } from '@inertiaui/modal-react';
import { createInertiaApp } from '@inertiajs/react';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import PortalLayout from '@/layouts/portal-layout';
import SettingsLayout from '@/layouts/settings/layout';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Default modals to a right-side slideover (flyout) with theme-aware panels.
putConfig({
    type: 'slideover',
    slideover: {
        position: 'right',
        maxWidth: 'lg',
        closeButton: true,
        closeExplicitly: false,
        closeOnClickOutside: true,
        paddingClasses: '',
        panelClasses: 'bg-background text-foreground min-h-screen',
    },
    modal: {
        position: 'center',
        maxWidth: '2xl',
        closeButton: true,
        closeExplicitly: false,
        closeOnClickOutside: true,
        paddingClasses: '',
        panelClasses: 'bg-background text-foreground rounded-xl',
    },
});

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'welcome':
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('portal/'):
                return PortalLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    strictMode: true,
    withApp(app) {
        return (
            <TooltipProvider delayDuration={0}>
                <ModalStackProvider>{app}</ModalStackProvider>
                <Toaster />
            </TooltipProvider>
        );
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
