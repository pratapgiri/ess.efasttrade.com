import { Button } from '@/components/ui/button';
import { useTranslation } from 'react-i18next';

interface ClaimClientPaginationProps {
    currentPage: number;
    totalPages: number;
    onPageChange: (page: number) => void;
}

/** Client-side pagination helper (prefer server pagination on list pages). */
export function ClaimClientPagination({ currentPage, totalPages, onPageChange }: ClaimClientPaginationProps) {
    const { t } = useTranslation();

    if (totalPages <= 1) return null;

    return (
        <div className="flex items-center justify-center gap-2 border-t py-4">
            <Button variant="outline" size="sm" disabled={currentPage <= 1} onClick={() => onPageChange(currentPage - 1)}>
                {t('Previous')}
            </Button>
            <span className="px-2 text-sm text-muted-foreground">
                {currentPage} {t('of')} {totalPages}
            </span>
            <Button
                variant="outline"
                size="sm"
                disabled={currentPage >= totalPages}
                onClick={() => onPageChange(currentPage + 1)}
            >
                {t('Next')}
            </Button>
        </div>
    );
}
