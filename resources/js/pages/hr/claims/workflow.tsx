// pages/hr/claims/workflow.tsx — Claim Workflow Setup
import { useMemo, useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { Loader2, Plus, Pencil } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { toast } from '@/components/custom-toast';
import { WorkflowLevelCard, ClaimEmptyState } from '@/components/claims';
import { CLAIM_TYPES, getClaimTypeLabel } from '@/config/claims';
import { router, usePage } from '@inertiajs/react';
import type { ClaimTypeKey } from '@/config/claims';
import type { WorkflowLevel, WorkflowListItem } from '@/types/claims';
import { randomUUID } from '@/utils/crypto-polyfill';

function newLevel(serial: number): WorkflowLevel {
    return {
        id: randomUUID(),
        serial_number: serial,
        emp_type: 'Manager',
        approver_user_id: '',
        department_id: '',
    };
}

export default function ClaimWorkflowPage() {
    const { t } = useTranslation();
    const {
        workflows: initialWorkflows = [],
        approvers = [],
        departments = [],
    } = usePage().props as {
        workflows?: WorkflowListItem[];
        approvers?: { id: number; name: string }[];
        departments?: { id: number; name: string }[];
    };
    const [workflows, setWorkflows] = useState<WorkflowListItem[]>(initialWorkflows);
    const [saving, setSaving] = useState(false);
    const [modalOpen, setModalOpen] = useState(false);
    const [editing, setEditing] = useState<WorkflowListItem | null>(null);
    const [workflowName, setWorkflowName] = useState('');
    const [workflowClaimType, setWorkflowClaimType] = useState<ClaimTypeKey>('expense');
    const [levels, setLevels] = useState<WorkflowLevel[]>([newLevel(1), newLevel(2)]);

    const visibleClaimTypes = useMemo(() => CLAIM_TYPES, []);

    const openCreate = () => {
        setEditing(null);
        setWorkflowName('');
        setWorkflowClaimType('expense');
        setLevels([newLevel(1), newLevel(2), newLevel(3), newLevel(4)]);
        setModalOpen(true);
    };

    const openEdit = (wf: WorkflowListItem) => {
        setEditing(wf);
        setWorkflowName(wf.workflow_name);
        setWorkflowClaimType(wf.claim_type);
        setLevels(
            wf.levels.length > 0
                ? wf.levels.map((l) => ({ ...l, id: l.id || randomUUID() }))
                : [newLevel(1)]
        );
        setModalOpen(true);
    };

    const moveLevel = (index: number, direction: 'up' | 'down') => {
        const next = [...levels];
        const swap = direction === 'up' ? index - 1 : index + 1;
        if (swap < 0 || swap >= next.length) return;
        [next[index], next[swap]] = [next[swap], next[index]];
        next.forEach((l, i) => (l.serial_number = i + 1));
        setLevels(next);
    };

    const removeLevel = (index: number) => {
        const next = levels.filter((_, i) => i !== index);
        next.forEach((l, i) => (l.serial_number = i + 1));
        setLevels(next);
    };

    const saveWorkflow = () => {
        if (!workflowName.trim()) {
            toast.error(t('Workflow name is required'));
            return;
        }
        toast.loading(t('Saving workflow...'));
        setSaving(true);
        router.post(
            route('hr.claim-workflows.store'),
            {
                claim_type: workflowClaimType,
                workflow_name: workflowName,
                levels: levels.map((l) => ({
                    serial_number: l.serial_number,
                    emp_type: l.emp_type,
                    approver_user_id: l.approver_user_id || null,
                    department_id: l.department_id || null,
                })),
            },
            {
                preserveScroll: true,
                onFinish: () => {
                    setSaving(false);
                    toast.dismiss();
                },
                onSuccess: (page) => {
                    setModalOpen(false);
                    const flash = page.props.flash as { success?: string; error?: string } | undefined;
                    if (flash?.success) toast.success(t(flash.success));
                    else if (flash?.error) toast.error(t(flash.error));
                },
                onError: () => toast.error(t('Failed to save workflow')),
            }
        );
    };

    return (
        <PageTemplate
            title={t('Claim Workflow Setup')}
            description={t('Configure multi-level approval routes per claim type')}
            url="/hr/claim-workflows"
            actions={[
                {
                    label: t('Add Workflow'),
                    icon: <Plus className="mr-2 h-4 w-4" />,
                    variant: 'default',
                    onClick: openCreate,
                },
            ]}
            breadcrumbs={[
                { title: t('Dashboard'), href: route('dashboard') },
                { title: t('HR Management') },
                { title: t('Claim Workflows') },
            ]}
        >
            {workflows.length === 0 ? (
                <ClaimEmptyState title={t('No workflows configured')} />
            ) : (
                <div className="overflow-x-auto rounded-lg border bg-card">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{t('Workflow Name')}</TableHead>
                                <TableHead>{t('Claim Type')}</TableHead>
                                <TableHead>{t('Company')}</TableHead>
                                <TableHead>{t('Total Levels')}</TableHead>
                                <TableHead>{t('Status')}</TableHead>
                                <TableHead className="text-right">{t('Actions')}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {workflows.map((wf) => (
                                <TableRow key={wf.id}>
                                    <TableCell className="font-medium">{wf.workflow_name}</TableCell>
                                    <TableCell>{t(getClaimTypeLabel(wf.claim_type))}</TableCell>
                                    <TableCell>{wf.company_name}</TableCell>
                                    <TableCell>{wf.total_levels}</TableCell>
                                    <TableCell>
                                        <Badge variant={wf.status === 'active' ? 'default' : 'secondary'}>
                                            {wf.status}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <Button variant="ghost" size="sm" onClick={() => openEdit(wf)}>
                                            <Pencil className="mr-1 h-4 w-4" />
                                            {t('Edit')}
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            )}

            <Dialog open={modalOpen} onOpenChange={setModalOpen}>
                <DialogContent
                    modalId="claim-workflow-modal"
                    className="max-h-[90vh] w-[calc(100%-2rem)] max-w-2xl gap-0 overflow-hidden bg-background p-0 sm:w-full"
                >
                    <DialogHeader className="border-b px-6 py-4">
                        <DialogTitle>{editing ? t('Edit Workflow') : t('Add Workflow')}</DialogTitle>
                    </DialogHeader>
                    <div className="max-h-[calc(90vh-11rem)] overflow-y-auto px-6 py-5">
                        <div className="space-y-4">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-1.5">
                                    <Label>{t('Workflow Name')}</Label>
                                    <Input value={workflowName} onChange={(e) => setWorkflowName(e.target.value)} />
                                </div>
                                <div className="space-y-1.5">
                                    <Label>{t('Claim Type')}</Label>
                                    <Select
                                        value={workflowClaimType}
                                        onValueChange={(v) => setWorkflowClaimType(v as ClaimTypeKey)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {visibleClaimTypes.map((type) => (
                                                <SelectItem key={type.key} value={type.key}>
                                                    {t(type.label)}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                            <div className="flex items-center justify-between">
                                <Label className="text-base">{t('Approval Levels')}</Label>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setLevels((prev) => [...prev, newLevel(prev.length + 1)])}
                                >
                                    <Plus className="mr-1 h-4 w-4" />
                                    {t('Add Level')}
                                </Button>
                            </div>
                            <div className="space-y-3">
                                {levels.map((level, index) => (
                                    <WorkflowLevelCard
                                        key={level.id}
                                        level={level}
                                        index={index}
                                        total={levels.length}
                                        approvers={approvers}
                                        departments={departments}
                                        onChange={(updated) =>
                                            setLevels((prev) => prev.map((l, i) => (i === index ? updated : l)))
                                        }
                                        onRemove={() => removeLevel(index)}
                                        onMoveUp={() => moveLevel(index, 'up')}
                                        onMoveDown={() => moveLevel(index, 'down')}
                                    />
                                ))}
                            </div>
                        </div>
                    </div>
                    <DialogFooter className="border-t bg-background px-6 py-3">
                        <Button variant="outline" disabled={saving} onClick={() => setModalOpen(false)}>
                            {t('Close')}
                        </Button>
                        <Button disabled={saving} onClick={saveWorkflow}>
                            {saving && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                            {t('Save Workflow')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </PageTemplate>
    );
}
