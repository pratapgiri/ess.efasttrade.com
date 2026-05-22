import { FileQuestion } from 'lucide-react';
import { useTranslation } from 'react-i18next';

interface ClaimEmptyStateProps {
    title?: string;
    description?: string;
}

export function ClaimEmptyState({
    title,
    description,
}: ClaimEmptyStateProps) {
    const { t } = useTranslation();

    return (
        <div className="flex flex-col items-center justify-center rounded-lg border border-dashed bg-muted/20 px-6 py-16 text-center">
            <FileQuestion className="mb-3 h-10 w-10 text-muted-foreground" />
            <p className="text-sm font-medium">{title ?? t('No claims found')}</p>
            <p className="mt-1 max-w-sm text-sm text-muted-foreground">
                {description ?? t('Try changing the month, status, or search filters.')}
            </p>
        </div>
    );
}
