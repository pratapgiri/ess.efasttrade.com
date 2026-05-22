import { useMemo, useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { router, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { toast } from '@/components/custom-toast';

type EmployeeOption = { id: number; name: string };
type LineItem = { from_time: string; to_time: string; task_note: string };
type ExistingTimesheet = {
  id: number;
  employee_id: number;
  date: string;
  work_mode: 'office' | 'home' | 'hybrid';
  overall_note: string | null;
  status: 'draft' | 'pending' | 'approved' | 'rejected';
  items: Array<{ from_time: string; to_time: string; task_note: string | null }>;
};

const DEFAULT_ROWS: LineItem[] = [
  { from_time: '09:30', to_time: '10:30', task_note: '' },
  { from_time: '10:30', to_time: '11:30', task_note: '' },
  { from_time: '11:30', to_time: '12:30', task_note: '' },
  { from_time: '12:30', to_time: '13:30', task_note: '' },
  { from_time: '14:30', to_time: '15:30', task_note: '' },
  { from_time: '15:30', to_time: '16:30', task_note: '' },
  { from_time: '16:30', to_time: '17:30', task_note: '' },
  { from_time: '17:30', to_time: '18:30', task_note: '' },
];

const toDisplayDate = (date: string) => {
  const [year, month, day] = date.split('-');
  return `${day}/${month}/${year}`;
};

const toServerDate = (value: string) => {
  const parts = value.split('/');
  if (parts.length !== 3) return '';
  const [day, month, year] = parts;
  if (!day || !month || !year) return '';
  return `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
};

export default function DailyTimesheetForm() {
  const { t } = useTranslation();
  const { employees = [], timesheet = null, filters = {}, isReadOnly = false } = usePage().props as unknown as {
    employees: EmployeeOption[];
    timesheet: ExistingTimesheet | null;
    filters: { date?: string };
    isReadOnly?: boolean;
  };

  const [employeeId, setEmployeeId] = useState(String(timesheet?.employee_id || employees[0]?.id || ''));
  const [displayDate, setDisplayDate] = useState(toDisplayDate(timesheet?.date || filters.date || new Date().toISOString().slice(0, 10)));
  const [workMode, setWorkMode] = useState<'office' | 'home' | 'hybrid'>(timesheet?.work_mode || 'office');
  const [overallNote, setOverallNote] = useState(timesheet?.overall_note || '');
  const [lineItems, setLineItems] = useState<LineItem[]>(
    timesheet?.items?.length
      ? timesheet.items.map((item) => ({
          from_time: item.from_time,
          to_time: item.to_time,
          task_note: item.task_note || '',
        }))
      : DEFAULT_ROWS
  );

  const isLocked = Boolean(isReadOnly) || timesheet?.status === 'approved';
  const parsedDate = useMemo(() => toServerDate(displayDate.trim()), [displayDate]);

  const addRow = () => {
    const last = lineItems[lineItems.length - 1];
    setLineItems((prev) => [
      ...prev,
      {
        from_time: last?.to_time || '09:30',
        to_time: last?.to_time || '10:30',
        task_note: '',
      },
    ]);
  };

  const updateRow = (index: number, field: keyof LineItem, value: string) => {
    setLineItems((prev) => prev.map((row, idx) => (idx === index ? { ...row, [field]: value } : row)));
  };

  const resetForm = () => {
    if (!window.confirm(t('Are you sure to Reset the Entire form?'))) return;
    setWorkMode('office');
    setOverallNote('');
    setLineItems(DEFAULT_ROWS);
  };

  const buildPayload = () => ({
    employee_id: Number(employeeId),
    date: parsedDate,
    work_mode: workMode,
    overall_note: overallNote || undefined,
    line_items: lineItems.map((row) => ({
      from_time: row.from_time,
      to_time: row.to_time,
      task_note: row.task_note,
    })),
  });

  const validateBeforeSubmit = () => {
    if (!employeeId) {
      toast.error(t('Employee is required.'));
      return false;
    }
    if (!parsedDate) {
      toast.error(t('Please enter date in DD/MM/YYYY format.'));
      return false;
    }
    if (lineItems.length === 0) {
      toast.error(t('At least one line item is required.'));
      return false;
    }

    const hasInvalid = lineItems.some((row) => !row.from_time || !row.to_time);
    if (hasInvalid) {
      toast.error(t('Each line item must have from and to time.'));
      return false;
    }
    return true;
  };

  const saveDraft = () => {
    if (isLocked) return;
    if (!validateBeforeSubmit()) return;

    router.post(route('hr.daily-timesheets.save-draft'), buildPayload(), {
      preserveScroll: true,
      onSuccess: (page: any) => {
        if (page.props.flash?.success) toast.success(t(page.props.flash.success));
        if (page.props.flash?.error) toast.error(t(page.props.flash.error));
      },
      onError: (errors) => {
        toast.error(Object.values(errors).join(', ') || t('Failed to save draft.'));
      },
    });
  };

  const submitForApproval = () => {
    if (isLocked) return;
    if (!validateBeforeSubmit()) return;
    if (!window.confirm(t('Are you sure to Submit the Form?'))) return;

    router.post(route('hr.daily-timesheets.submit'), buildPayload(), {
      onError: (errors) => {
        toast.error(Object.values(errors).join(', ') || t('Failed to submit timesheet.'));
      },
    });
  };

  return (
    <PageTemplate
      title={t('Employee Daily Timesheet')}
      description=""
      url="/hr/daily-timesheets/form"
      breadcrumbs={[
        { title: t('Dashboard'), href: route('dashboard') },
        { title: t('Time Tracking') },
        { title: t('Employee Daily Timesheet') },
      ]}
      noPadding
    >
      <div className="rounded-lg bg-white p-4 shadow dark:bg-gray-900">
        <div className="grid grid-cols-1 gap-3 md:grid-cols-3">
          <div>
            <Label>{t('Employee')}</Label>
            <select
              className="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
              value={employeeId}
              onChange={(e) => setEmployeeId(e.target.value)}
            >
              {employees.map((employee) => (
                <option key={employee.id} value={employee.id}>
                  {employee.name}
                </option>
              ))}
            </select>
          </div>
          <div>
            <Label>{t('Date (DD/MM/YYYY)')}</Label>
              <Input value={displayDate} onChange={(e) => setDisplayDate(e.target.value)} placeholder="DD/MM/YYYY" disabled={isLocked} />
          </div>
          <div>
            <Label>{t('Work Mode')}</Label>
            <select
              className="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
              value={workMode}
              onChange={(e) => setWorkMode(e.target.value as 'office' | 'home' | 'hybrid')}
              disabled={isLocked}
            >
              <option value="office">{t('Work from Office')}</option>
              <option value="home">{t('Work from Home')}</option>
              <option value="hybrid">{t('Hybrid')}</option>
            </select>
          </div>
        </div>

        <div className="mt-3">
          <Label>{t('Overall Note')}</Label>
          <Textarea
            className="mt-1"
            value={overallNote}
            onChange={(e) => setOverallNote(e.target.value)}
            placeholder={t('Any dependency, blocker or remark')}
            disabled={isLocked}
          />
        </div>
      </div>

      <div className="mt-4 rounded-lg bg-white p-4 shadow dark:bg-gray-900">
        <div className="mb-3 flex items-center justify-between">
          <h3 className="text-sm font-semibold">{t('Work line items')}</h3>
          <Button type="button" variant="outline" onClick={addRow} disabled={isLocked}>
            {t('Add Row')}
          </Button>
        </div>

        <div className="overflow-auto">
          <table className="min-w-full text-sm">
            <thead className="bg-muted/40">
              <tr>
                <th className="px-2 py-2 text-left">{t('From')}</th>
                <th className="px-2 py-2 text-left">{t('To')}</th>
                <th className="px-2 py-2 text-left">{t('Task/Work Note')}</th>
              </tr>
            </thead>
            <tbody>
              {lineItems.map((row, index) => (
                <tr key={`${index}-${row.from_time}`} className="border-t">
                  <td className="px-2 py-2">
                    <Input type="time" value={row.from_time} onChange={(e) => updateRow(index, 'from_time', e.target.value)} disabled={isLocked} />
                  </td>
                  <td className="px-2 py-2">
                    <Input type="time" value={row.to_time} onChange={(e) => updateRow(index, 'to_time', e.target.value)} disabled={isLocked} />
                  </td>
                  <td className="px-2 py-2">
                    <Input value={row.task_note} onChange={(e) => updateRow(index, 'task_note', e.target.value)} placeholder={t('Work done in this slot')} disabled={isLocked} />
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      <div className="mt-4 flex flex-wrap gap-2">
        <Button type="button" onClick={saveDraft} disabled={isLocked}>
          {t('Submit as Draft')}
        </Button>
        <Button type="button" variant="secondary" onClick={submitForApproval} disabled={isLocked}>
          {t('Submit for Approval')}
        </Button>
        <Button type="button" variant="outline" onClick={resetForm} disabled={isLocked}>
          {t('Reset')}
        </Button>
      </div>
    </PageTemplate>
  );
}

