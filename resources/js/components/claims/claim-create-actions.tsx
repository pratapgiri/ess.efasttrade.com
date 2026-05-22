import { Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useTranslation } from 'react-i18next';
import { cn } from '@/lib/utils';
import type { ClaimFormAction } from '@/types/claims';

interface ClaimCreateActionsProps {
    loading?: boolean;
    className?: string;
    onAction: (action: ClaimFormAction) => void;
}

export function ClaimCreateActions({ loading = false, className, onAction }: ClaimCreateActionsProps) {
    const { t } = useTranslation();

    return (
        <div className={cn('flex flex-wrap gap-2', className)}>
            <Button type="button" variant="outline" disabled={loading} onClick={() => onAction('draft')}>
                {loading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                {t('Save Draft')}
            </Button>
            <Button type="button" disabled={loading} onClick={() => onAction('forward')}>
                {loading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                {t('Forward')}
            </Button>
        </div>
    );
}
