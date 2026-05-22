import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import { useTranslation } from 'react-i18next';
import { CLAIM_STATUSES } from '@/config/claims/claim-statuses';
import type { ClaimStatus } from '@/types/claims';

interface ClaimStatusBadgeProps {
    status: ClaimStatus | string;
    className?: string;
}

export function ClaimStatusBadge({ status, className }: ClaimStatusBadgeProps) {
    const { t } = useTranslation();
    const def = CLAIM_STATUSES[status as ClaimStatus];

    if (!def) {
        return (
            <Badge variant="outline" className={className}>
                {status}
            </Badge>
        );
    }

    return (
        <Badge variant={def.badgeVariant} className={cn(def.className, className)}>
            {t(def.label)}
        </Badge>
    );
}
