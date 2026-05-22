import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { ChevronDown, ChevronUp, GripVertical, Trash2 } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { WORKFLOW_ROLE_TYPES } from '@/config/claims/claim-statuses';
import type { WorkflowLevel } from '@/types/claims';

interface WorkflowLevelCardProps {
    level: WorkflowLevel;
    index: number;
    total: number;
    approvers?: { id: number; name: string }[];
    departments?: { id: number; name: string }[];
    onChange: (level: WorkflowLevel) => void;
    onRemove: () => void;
    onMoveUp: () => void;
    onMoveDown: () => void;
}

export function WorkflowLevelCard({
    level,
    index,
    total,
    approvers = [],
    departments = [],
    onChange,
    onRemove,
    onMoveUp,
    onMoveDown,
}: WorkflowLevelCardProps) {
    const { t } = useTranslation();

    return (
        <Card className="border-dashed shadow-none">
            <CardHeader className="flex flex-row items-center gap-2 space-y-0 py-3">
                <GripVertical className="h-4 w-4 shrink-0 text-muted-foreground" aria-hidden />
                <span className="text-sm font-medium">
                    {t('Level')} {level.serial_number}
                </span>
                <div className="ml-auto flex gap-1">
                    <Button type="button" variant="ghost" size="icon" className="h-8 w-8" onClick={onMoveUp} disabled={index === 0}>
                        <ChevronUp className="h-4 w-4" />
                    </Button>
                    <Button type="button" variant="ghost" size="icon" className="h-8 w-8" onClick={onMoveDown} disabled={index >= total - 1}>
                        <ChevronDown className="h-4 w-4" />
                    </Button>
                    <Button type="button" variant="ghost" size="icon" className="h-8 w-8 text-destructive" onClick={onRemove}>
                        <Trash2 className="h-4 w-4" />
                    </Button>
                </div>
            </CardHeader>
            <CardContent className="grid gap-4 pb-4 sm:grid-cols-2 lg:grid-cols-4">
                <div className="space-y-1.5">
                    <Label>{t('Sr No')}</Label>
                    <Input
                        type="number"
                        min={1}
                        value={level.serial_number}
                        onChange={(e) => onChange({ ...level, serial_number: Number(e.target.value) || 1 })}
                    />
                </div>
                <div className="space-y-1.5">
                    <Label>{t('Role Type')}</Label>
                    <Select value={level.emp_type} onValueChange={(v) => onChange({ ...level, emp_type: v })}>
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {WORKFLOW_ROLE_TYPES.map((type) => (
                                <SelectItem key={type} value={type}>
                                    {type}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
                <div className="space-y-1.5">
                    <Label>{t('Employee')}</Label>
                    <Select
                        value={level.approver_user_id || 'none'}
                        onValueChange={(v) => onChange({ ...level, approver_user_id: v === 'none' ? '' : v })}
                    >
                        <SelectTrigger>
                            <SelectValue placeholder={t('Auto resolve')} />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="none">{t('Auto resolve')}</SelectItem>
                            {approvers.map((a) => (
                                <SelectItem key={a.id} value={String(a.id)}>
                                    {a.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
                <div className="space-y-1.5">
                    <Label>{t('Department')}</Label>
                    <Select
                        value={level.department_id || 'none'}
                        onValueChange={(v) => onChange({ ...level, department_id: v === 'none' ? '' : v })}
                    >
                        <SelectTrigger>
                            <SelectValue placeholder={t('Any')} />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="none">{t('Any')}</SelectItem>
                            {departments.map((d) => (
                                <SelectItem key={d.id} value={String(d.id)}>
                                    {d.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
            </CardContent>
        </Card>
    );
}
