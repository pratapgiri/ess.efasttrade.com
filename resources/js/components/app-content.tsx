import { SidebarInset } from '@/components/ui/sidebar';
import * as React from 'react';
import { useLayout } from '@/contexts/LayoutContext';
import { cn } from '@/lib/utils';

interface AppContentProps extends React.ComponentProps<'main'> {
    variant?: 'header' | 'sidebar';
}

// export function AppContent({ variant = 'header', children, ...props }: AppContentProps) {
//     if (variant === 'sidebar') {
//         return <SidebarInset {...props}>{children}</SidebarInset>;
//     }

//     return (
//         <main className="mx-auto flex h-full w-full max-w-7xl flex-1 flex-col gap-4 rounded-xl" {...props}>
//             {children}
//         </main>
//     );
// }

export function AppContent({ variant = 'header', children, className, ...props }: AppContentProps) {
    const { position } = useLayout();
    if (variant === 'sidebar') {
        return (
            <SidebarInset className={cn('min-w-0 overflow-x-hidden', className)} {...props}>
                <div dir={position === 'right' ? 'rtl' : 'ltr'} className="min-w-0">
                    {children}
                </div>
            </SidebarInset>
        );
    }

    return (
        <main className={cn('mx-auto flex h-full w-full max-w-7xl flex-1 flex-col gap-4 rounded-xl min-w-0', className)} {...props}>
            <div dir={position === 'right' ? 'rtl' : 'ltr'} className="min-w-0">
                {children}
            </div>
        </main>
    );
}
