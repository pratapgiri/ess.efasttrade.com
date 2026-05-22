import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Plus, Search } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { CLAIM_STATUS_FILTER_OPTIONS } from '@/config/claims/claim-statuses';

interface ClaimFiltersProps {
    monthYear: string;
    onMonthYearChange: (value: string) => void;
    status?: string;
    onStatusChange?: (value: string) => void;
    showStatusFilter?: boolean;
    searchTerm?: string;
    onSearchChange?: (value: string) => void;
    onSearch?: () => void;
    employeeId?: string;
    onEmployeeChange?: (value: string) => void;
    employees?: { id: number; name: string }[];
    showEmployeeFilter?: boolean;
    showAddButton?: boolean;
    onAddClick?: () => void;
    addButtonLabel?: string;
}

export function ClaimFilters({
    monthYear,
    onMonthYearChange,
    status = 'all',
    onStatusChange,
    showStatusFilter = false,
    searchTerm = '',
    onSearchChange,
    onSearch,
    employeeId = 'all',
    onEmployeeChange,
    employees = [],
    showEmployeeFilter = false,
    showAddButton = false,
    onAddClick,
    addButtonLabel,
}: ClaimFiltersProps) {
    const { t } = useTranslation();

    return (
        <div className="flex flex-col gap-4 rounded-lg border bg-card p-4 sm:flex-row sm:flex-wrap sm:items-end sm:justify-between">
            <div className="flex flex-wrap items-end gap-3">
                <div className="space-y-1.5">
                    <Label htmlFor="claim-month-year" className="text-xs text-muted-foreground">
                        {t('Month-Year')}
                    </Label>
                    <Input
                        id="claim-month-year"
                        type="month"
                        className="w-[160px]"
                        value={monthYear}
                        onChange={(e) => onMonthYearChange(e.target.value)}
                    />
                </div>

                {showStatusFilter && onStatusChange && (
                    <div className="space-y-1.5">
                        <Label className="text-xs text-muted-foreground">{t('Status')}</Label>
                        <Select value={status} onValueChange={onStatusChange}>
                            <SelectTrigger className="w-[160px]">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {CLAIM_STATUS_FILTER_OPTIONS.map((opt) => (
                                    <SelectItem key={opt.value} value={opt.value}>
                                        {t(opt.label)}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                )}

                {showEmployeeFilter && onEmployeeChange && (
                    <div className="space-y-1.5">
                        <Label className="text-xs text-muted-foreground">{t('Employee')}</Label>
                        <Select value={employeeId} onValueChange={onEmployeeChange}>
                            <SelectTrigger className="w-[200px]">
                                <SelectValue placeholder={t('All Employees')} />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">{t('All Employees')}</SelectItem>
                                {employees.map((emp) => (
                                    <SelectItem key={emp.id} value={String(emp.id)}>
                                        {emp.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                )}

                {onSearchChange && (
                    <form
                        className="flex gap-2"
                        onSubmit={(e) => {
                            e.preventDefault();
                            onSearch?.();
                        }}
                    >
                        <div className="relative w-full min-w-[12rem] sm:w-52">
                            <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input
                                placeholder={t('Search...')}
                                className="pl-9"
                                value={searchTerm}
                                onChange={(e) => onSearchChange(e.target.value)}
                            />
                        </div>
                        <Button type="submit" size="sm" variant="secondary">
                            <Search className="mr-1.5 h-4 w-4" />
                            {t('Search')}
                        </Button>
                    </form>
                )}
            </div>

            {showAddButton && onAddClick && (
                <Button onClick={onAddClick} className="w-full shrink-0 sm:w-auto">
                    <Plus className="mr-2 h-4 w-4" />
                    {addButtonLabel ?? t('Add Claim')}
                </Button>
            )}
        </div>
    );
}
