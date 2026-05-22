import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { useTranslation } from 'react-i18next';

interface ClaimAmountCardProps {
    label: string;
    amount: number | string;
    variant?: 'default' | 'passed' | 'claimed';
    className?: string;
}

export function ClaimAmountCard({ label, amount, variant = 'default', className }: ClaimAmountCardProps) {
    const { t } = useTranslation();
    const num = typeof amount === 'string' ? parseFloat(amount) || 0 : amount;

    return (
        <Card className={cn('border-dashed', className)}>
            <CardContent className="p-4">
                <p className="text-xs font-medium text-muted-foreground">{t(label)}</p>
                <p
                    className={cn('mt-1 text-2xl font-semibold tabular-nums', {
                        'text-primary': variant === 'claimed',
                        'text-emerald-600 dark:text-emerald-400': variant === 'passed',
                    })}
                >
                    {num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                </p>
            </CardContent>
        </Card>
    );
}
