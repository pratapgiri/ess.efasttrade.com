import '../css/app.css';
import '../css/dark-mode.css';

import { installCryptoRandomUuidPolyfill } from './utils/crypto-polyfill';

installCryptoRandomUuidPolyfill();

import { createInertiaApp, router } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { lazy, Suspense } from 'react';
import { AppErrorBoundary } from './components/app-error-boundary';
import { LayoutProvider } from './contexts/LayoutContext';
import { SidebarProvider } from './contexts/SidebarContext';
import { BrandProvider } from './contexts/BrandContext';
import { ModalStackProvider } from './contexts/ModalStackContext';
import { initializeTheme } from './hooks/use-appearance';
import { CustomToast } from './components/custom-toast';
import { initializeGlobalSettings } from './utils/globalSettings';
import { initPerformanceMonitoring, lazyLoadImages } from './utils/performance';
import './i18n'; // Import i18n configuration
import './utils/axios-config'; // Import axios configuration
import i18n from './i18n';

if (typeof window !== 'undefined') {
    const originalAppendChild = document.head.appendChild;

    document.head.appendChild = function (node: any) {
        if (node?.tagName === 'SCRIPT' && node.src?.includes('envato.workdo.io')) {
            console.warn('Envato verify.js blocked');
            return node;
        }
        return originalAppendChild.call(this, node);
    };
}
// Initialize performance monitoring
initPerformanceMonitoring();

// Initialize lazy loading of images when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    lazyLoadImages();
});

// Add event listener for theme changes
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    // Re-apply theme when system preference changes
    const savedTheme = localStorage.getItem('themeSettings');
    if (savedTheme) {
        const themeSettings = JSON.parse(savedTheme);
        if (themeSettings.appearance === 'system') {
            initializeTheme();
        }
    }
});

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    resolve: (name) => resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),
    setup({ el, App, props }) {
        const root = createRoot(el);
        try {
            (window as any).page = props.initialPage;
        } catch (e) {
            console.warn('Could not set global page data:', e);
        }
        
        // Set demo mode globally
        try {
            (window as any).isDemo = props.initialPage.props?.is_demo || false;
        } catch (e) {
        }
        
        // Initialize global settings from shared data
        const globalSettings = props.initialPage.props.globalSettings || {};
        if (Object.keys(globalSettings).length > 0) {
            initializeGlobalSettings(globalSettings);
        }

        // Create a memoized render function to prevent unnecessary re-renders
        const renderApp = (appProps: any) => {
            const currentGlobalSettings = appProps.initialPage.props.globalSettings || {};
            const user = appProps.initialPage.props.auth?.user;
            
            return (
                <AppErrorBoundary>
                    <ModalStackProvider>
                        <LayoutProvider>
                            <SidebarProvider>
                                <BrandProvider globalSettings={currentGlobalSettings} user={user}>
                                    <Suspense fallback={<div className="flex h-screen w-full items-center justify-center text-foreground">Loading...</div>}>
                                        <App {...appProps} />
                                    </Suspense>
                                    <CustomToast />
                                </BrandProvider>
                            </SidebarProvider>
                        </LayoutProvider>
                    </ModalStackProvider>
                </AppErrorBoundary>
            );
        };

        const mountApp = () => root.render(renderApp(props));

        // Mount immediately — do not block on i18n (loadOnInitialization: false can leave the app blank)
        mountApp();

        if (!i18n.isInitialized) {
            i18n.on('initialized', mountApp);
        }

        // Fallback if i18n never fires initialized
        setTimeout(() => {
            if (!el.childNodes.length) {
                console.warn('i18n delayed — mounting app without waiting');
                mountApp();
            }
        }, 3000);
        
        router.on('exception', (event) => {
            console.error('Inertia request error:', event.detail);
        });

        window.addEventListener('error', (event) => {
            console.error('Uncaught error:', event.error ?? event.message);
        });
        window.addEventListener('unhandledrejection', (event) => {
            console.error('Unhandled promise rejection:', event.reason);
        });

        // Update shared globals on navigation (do not re-mount Inertia App with partial props — that causes a blank page)
        router.on('navigate', (event) => {
            try {
                (window as any).page = event.detail.page;
                (window as any).isDemo = event.detail.page.props?.is_demo || false;

                const pageGlobalSettings = event.detail.page.props?.globalSettings || {};
                if (Object.keys(pageGlobalSettings).length > 0) {
                    initializeGlobalSettings(pageGlobalSettings);
                }

                // Force dark mode check on navigation
                const savedTheme = localStorage.getItem('themeSettings');
                if (savedTheme) {
                    const themeSettings = JSON.parse(savedTheme);
                    const isDark = themeSettings.appearance === 'dark' || 
                        (themeSettings.appearance === 'system' && 
                         window.matchMedia('(prefers-color-scheme: dark)').matches);
                    document.documentElement.classList.toggle('dark', isDark);
                    document.body.classList.toggle('dark', isDark);
                }
            } catch (e) {
                console.error('Navigation error:', e);
            }
        });
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();

// Direction initialization is now handled by LayoutProvider and landing page
