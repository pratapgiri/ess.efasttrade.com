import { cn } from '@/lib/utils';
import { CheckCircle2, Circle, XCircle, ArrowRight, Ban, Clock } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import type { WorkflowLogItem } from '@/types/claims';
import type { WorkflowStepDisplay } from '@/config/claims/claim-statuses';

interface WorkflowTimelineProps {
    logs?: WorkflowLogItem[];
    steps?: WorkflowStepDisplay[];
    currentPendingAt?: string;
    className?: string;
}

const stateStyles: Record<WorkflowStepDisplay['state'], string> = {
    completed: 'bg-emerald-100 text-emerald-700 ring-emerald-200 dark:bg-emerald-950 dark:text-emerald-300',
    current: 'bg-amber-100 text-amber-800 ring-amber-300 dark:bg-amber-950 dark:text-amber-200',
    pending: 'bg-muted text-muted-foreground ring-border',
    rejected: 'bg-red-100 text-red-700 ring-red-200 dark:bg-red-950 dark:text-red-300',
};

function StepIcon({ state }: { state: WorkflowStepDisplay['state'] }) {
    switch (state) {
        case 'completed':
            return <CheckCircle2 className="h-4 w-4" />;
        case 'current':
            return <Clock className="h-4 w-4" />;
        case 'rejected':
            return <XCircle className="h-4 w-4" />;
        default:
            return <Circle className="h-4 w-4" />;
    }
}

function LogIcon({ action }: { action: string }) {
    switch (action) {
        case 'approve':
            return <CheckCircle2 className="h-4 w-4 text-emerald-600" />;
        case 'reject':
            return <XCircle className="h-4 w-4 text-red-500" />;
        case 'cancel':
            return <Ban className="h-4 w-4 text-muted-foreground" />;
        default:
            return <ArrowRight className="h-4 w-4 text-primary" />;
    }
}

export function WorkflowTimeline({ logs = [], steps, currentPendingAt, className }: WorkflowTimelineProps) {
    const { t } = useTranslation();

    if (steps?.length) {
        return (
            <div className={cn('space-y-3', className)}>
                {currentPendingAt && (
                    <div className="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm dark:border-amber-900 dark:bg-amber-950/50">
                        <span className="font-medium text-amber-800 dark:text-amber-200">{t('Current stage')}:</span>{' '}
                        <span className="capitalize">{currentPendingAt}</span>
                    </div>
                )}
                <ol className="space-y-2">
                    {steps.map((step) => (
                        <li
                            key={step.serial_number}
                            className={cn(
                                'flex items-center gap-3 rounded-md border px-3 py-2 text-sm ring-1',
                                stateStyles[step.state]
                            )}
                        >
                            <StepIcon state={step.state} />
                            <span className="font-medium">
                                {t('Level')} {step.serial_number}: {step.emp_type}
                            </span>
                            <span className="ml-auto text-xs capitalize opacity-80">{t(step.state)}</span>
                        </li>
                    ))}
                </ol>
            </div>
        );
    }

    if (!logs.length) {
        return <p className={cn('text-sm text-muted-foreground', className)}>{t('No workflow history yet.')}</p>;
    }

    return (
        <div className={cn('space-y-0', className)}>
            {currentPendingAt && (
                <div className="mb-3 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm dark:border-amber-900 dark:bg-amber-950/50">
                    <span className="font-medium text-amber-800 dark:text-amber-200">{t('Current stage')}:</span>{' '}
                    <span className="capitalize">{currentPendingAt}</span>
                </div>
            )}
            <ol className="relative border-l border-border pl-6">
                {logs.map((log, index) => (
                    <li key={index} className="mb-6 ml-2 last:mb-0">
                        <span className="absolute -left-[1.15rem] flex h-6 w-6 items-center justify-center rounded-full bg-background ring-2 ring-border">
                            <LogIcon action={log.action} />
                        </span>
                        <div className="flex flex-col gap-0.5">
                            <span className="text-sm font-medium capitalize">
                                {log.action}
                                {log.from_pending_at && log.to_pending_at && (
                                    <span className="font-normal text-muted-foreground">
                                        {' '}
                                        ({log.from_pending_at} → {log.to_pending_at})
                                    </span>
                                )}
                            </span>
                            {log.performer && (
                                <span className="text-xs text-muted-foreground">
                                    {t('By')}: {log.performer}
                                </span>
                            )}
                            {log.remarks && <span className="text-xs text-muted-foreground">{log.remarks}</span>}
                            <time className="text-xs text-muted-foreground">{log.created_at}</time>
                        </div>
                    </li>
                ))}
            </ol>
        </div>
    );
}
