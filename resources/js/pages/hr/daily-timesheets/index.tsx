import { PageTemplate } from '@/components/page-template';
import { router, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useState } from 'react';
import { Pagination } from '@/components/ui/pagination';
import { toast } from '@/components/custom-toast';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { Eye, CheckCircle, XCircle, Trash2 } from 'lucide-react';

type TimesheetRow = {
  id: number;
  date: string;
  work_mode: string;
  status: string;
  overall_note: string | null;
  employee?: { id: number; name: string };
  items?: Array<{ id: number; line_no: number; from_time: string; to_time: string; task_note: string | null }>;
};

export default function DailyTimesheetIndex() {
  const { t } = useTranslation();
  const { mode = 'employee', timesheets, filters = {}, employees = [] } = usePage().props as unknown as {
    mode: 'employee' | 'approval';
    timesheets: {
      data: TimesheetRow[];
      from: number;
      to: number;
      total: number;
      links: any[];
    };
    filters: { status?: string; employee_id?: string; date_from?: string; date_to?: string; per_page?: number };
    employees: Array<{ id: number; name: string }>;
  };

  const isApprovalMode = mode === 'approval';
  const hasRoute = (name: string) => {
    try {
      return route().has(name as any);
    } catch {
      return false;
    }
  };
  const dailyTimesheetFormRoute = hasRoute('hr.daily-timesheets.form')
    ? route('hr.daily-timesheets.form')
    : route('hr.time-entries.index');
  const [status, setStatus] = useState(filters.status || 'all');
  const [employeeId, setEmployeeId] = useState(filters.employee_id || 'all');
  const [dateFrom, setDateFrom] = useState(filters.date_from || '');
  const [dateTo, setDateTo] = useState(filters.date_to || '');
  const [viewRow, setViewRow] = useState<TimesheetRow | null>(null);

  const baseRoute = isApprovalMode ? 'hr.daily-timesheets.approvals.index' : 'hr.daily-timesheets.my.index';
  const title = isApprovalMode ? t('Timesheet Approvals') : t('My Timesheets');

  const applyFilters = () => {
    router.get(
      route(baseRoute),
      {
        status: status !== 'all' ? status : undefined,
        employee_id: isApprovalMode && employeeId !== 'all' ? employeeId : undefined,
        date_from: !isApprovalMode ? dateFrom || undefined : undefined,
        date_to: !isApprovalMode ? dateTo || undefined : undefined,
      },
      { preserveState: true, preserveScroll: true }
    );
  };

  const handleStatusUpdate = (id: number, nextStatus: 'approved' | 'rejected') => {
    const confirmText = nextStatus === 'approved' ? t('Approve this timesheet?') : t('Reject this timesheet?');
    if (!window.confirm(confirmText)) return;

    router.put(
      route('hr.daily-timesheets.update-status', id),
      { status: nextStatus, manager_comments: '' },
      {
        preserveScroll: true,
        onSuccess: (page: any) => {
          if (page.props.flash?.success) toast.success(t(page.props.flash.success));
          if (page.props.flash?.error) toast.error(t(page.props.flash.error));
        },
      }
    );
  };

  const handleDelete = (id: number) => {
    if (!window.confirm(t('Are you sure you want to delete this pending timesheet?'))) return;
    router.delete(route('hr.daily-timesheets.destroy', id), {
      preserveScroll: true,
      onSuccess: (page: any) => {
        if (page.props.flash?.success) toast.success(t(page.props.flash.success));
        if (page.props.flash?.error) toast.error(t(page.props.flash.error));
      },
      onError: (errors) => {
        toast.error(Object.values(errors).join(', ') || t('Failed to delete timesheet.'));
      },
    });
  };

  return (
    <PageTemplate
      title={title}
      description=""
      url={isApprovalMode ? '/hr/daily-timesheets/approvals' : '/hr/daily-timesheets/my'}
      breadcrumbs={[
        { title: t('Dashboard'), href: route('dashboard') },
        { title: t('Time Tracking') },
        { title },
      ]}
      actions={
        isApprovalMode
          ? []
          : [
              {
                label: t('Add Daily Timesheet'),
                variant: 'default',
                onClick: () => router.get(dailyTimesheetFormRoute),
              },
            ]
      }
      noPadding
    >
      <div className="mb-4 rounded-lg bg-white p-4 shadow dark:bg-gray-900">
        <div className="grid grid-cols-1 gap-3 md:grid-cols-5">
          <div>
            <label className="text-sm">{t('Status')}</label>
            <select className="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" value={status} onChange={(e) => setStatus(e.target.value)}>
              <option value="all">{t('All')}</option>
              <option value="draft">{t('Draft')}</option>
              <option value="pending">{t('Pending')}</option>
              <option value="approved">{t('Approved')}</option>
              <option value="rejected">{t('Rejected')}</option>
            </select>
          </div>
          {isApprovalMode && (
            <div>
              <label className="text-sm">{t('Employee')}</label>
              <select className="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" value={employeeId} onChange={(e) => setEmployeeId(e.target.value)}>
                <option value="all">{t('All Employees')}</option>
                {employees.map((employee) => (
                  <option key={employee.id} value={employee.id}>
                    {employee.name}
                  </option>
                ))}
              </select>
            </div>
          )}
          {!isApprovalMode && (
            <>
              <div>
                <label className="text-sm">{t('Date From')}</label>
                <Input className="mt-1" type="date" value={dateFrom} onChange={(e) => setDateFrom(e.target.value)} />
              </div>
              <div>
                <label className="text-sm">{t('Date To')}</label>
                <Input className="mt-1" type="date" value={dateTo} onChange={(e) => setDateTo(e.target.value)} />
              </div>
            </>
          )}
          <div className="flex items-end">
            <Button type="button" onClick={applyFilters}>
              {t('Apply')}
            </Button>
          </div>
        </div>
      </div>

      <div className="rounded-lg bg-white shadow dark:bg-gray-900">
        <div className="overflow-auto">
          <table className="min-w-full text-sm">
            <thead className="bg-muted/40">
              <tr>
                {isApprovalMode && <th className="px-3 py-2 text-left">{t('Employee')}</th>}
                <th className="px-3 py-2 text-left">{t('Date')}</th>
                <th className="px-3 py-2 text-left">{t('Work Mode')}</th>
                <th className="px-3 py-2 text-left">{t('Status')}</th>
                <th className="px-3 py-2 text-left">{t('Overall Note')}</th>
                <th className="px-3 py-2 text-left">{t('Action')}</th>
              </tr>
            </thead>
            <tbody>
              {timesheets.data.map((row) => (
                <tr key={row.id} className="border-t">
                  {isApprovalMode && <td className="px-3 py-2">{row.employee?.name || '-'}</td>}
                  <td className="px-3 py-2">{window.appSettings?.formatDateTimeSimple(row.date, false) || row.date}</td>
                  <td className="px-3 py-2">{row.work_mode}</td>
                  <td className="px-3 py-2 capitalize">{row.status}</td>
                  <td className="px-3 py-2">{row.overall_note || '-'}</td>
                  <td className="px-3 py-2">
                    {!isApprovalMode ? (
                      <div className="flex gap-2">
                        <Button
                          type="button"
                          variant="link"
                          className="h-auto p-0"
                          onClick={() =>
                            router.get(
                              hasRoute('hr.daily-timesheets.form')
                                ? route('hr.daily-timesheets.form', { timesheet_id: row.id })
                                : route('hr.time-entries.index')
                            )
                          }
                        >
                          {t('View / Edit')}
                        </Button>
                        {row.status === 'pending' && (
                          <Button type="button" variant="link" className="h-auto p-0 text-red-600" onClick={() => handleDelete(row.id)}>
                            {t('Delete')}
                          </Button>
                        )}
                      </div>
                    ) : row.status === 'pending' ? (
                      <div className="flex items-center gap-1">
                        <TooltipProvider>
                          <Tooltip>
                            <TooltipTrigger asChild>
                              <Button type="button" variant="ghost" size="icon" className="h-8 w-8 text-blue-500" onClick={() => setViewRow(row)}>
                                <Eye size={16} />
                              </Button>
                            </TooltipTrigger>
                            <TooltipContent>{t('View')}</TooltipContent>
                          </Tooltip>
                        </TooltipProvider>
                        <TooltipProvider>
                          <Tooltip>
                            <TooltipTrigger asChild>
                              <Button type="button" variant="ghost" size="icon" className="h-8 w-8 text-green-500" onClick={() => handleStatusUpdate(row.id, 'approved')}>
                                <CheckCircle size={16} />
                              </Button>
                            </TooltipTrigger>
                            <TooltipContent>{t('Approve')}</TooltipContent>
                          </Tooltip>
                        </TooltipProvider>
                        <TooltipProvider>
                          <Tooltip>
                            <TooltipTrigger asChild>
                              <Button type="button" variant="ghost" size="icon" className="h-8 w-8 text-red-500" onClick={() => handleStatusUpdate(row.id, 'rejected')}>
                                <XCircle size={16} />
                              </Button>
                            </TooltipTrigger>
                            <TooltipContent>{t('Reject')}</TooltipContent>
                          </Tooltip>
                        </TooltipProvider>
                        <TooltipProvider>
                          <Tooltip>
                            <TooltipTrigger asChild>
                              <Button type="button" variant="ghost" size="icon" className="h-8 w-8 text-red-500" onClick={() => handleDelete(row.id)}>
                                <Trash2 size={16} />
                              </Button>
                            </TooltipTrigger>
                            <TooltipContent>{t('Delete')}</TooltipContent>
                          </Tooltip>
                        </TooltipProvider>
                      </div>
                    ) : (
                      <div className="flex items-center gap-1">
                        <TooltipProvider>
                          <Tooltip>
                            <TooltipTrigger asChild>
                              <Button type="button" variant="ghost" size="icon" className="h-8 w-8 text-blue-500" onClick={() => setViewRow(row)}>
                                <Eye size={16} />
                              </Button>
                            </TooltipTrigger>
                            <TooltipContent>{t('View')}</TooltipContent>
                          </Tooltip>
                        </TooltipProvider>
                      </div>
                    )}
                  </td>
                </tr>
              ))}
              {timesheets.data.length === 0 && (
                <tr>
                  <td className="px-3 py-8 text-center text-muted-foreground" colSpan={isApprovalMode ? 6 : 5}>
                    {t('No timesheets found.')}
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>

        <Pagination
          from={timesheets.from || 0}
          to={timesheets.to || 0}
          total={timesheets.total || 0}
          links={timesheets.links}
          entityName={t('timesheets')}
          onPageChange={(url) => router.get(url)}
        />
      </div>

      <Dialog open={Boolean(viewRow)} onOpenChange={(open) => !open && setViewRow(null)}>
        <DialogContent className="max-h-[80vh] overflow-y-auto sm:max-w-3xl">
          <DialogHeader>
            <DialogTitle>{t('Timesheet Details')}</DialogTitle>
          </DialogHeader>

          {viewRow && (
            <div className="space-y-4">
              <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
                <div>
                  <p className="text-xs text-muted-foreground">{t('Employee')}</p>
                  <p className="text-sm font-medium">{viewRow.employee?.name || '-'}</p>
                </div>
                <div>
                  <p className="text-xs text-muted-foreground">{t('Date')}</p>
                  <p className="text-sm font-medium">{window.appSettings?.formatDateTimeSimple(viewRow.date, false) || viewRow.date}</p>
                </div>
                <div>
                  <p className="text-xs text-muted-foreground">{t('Work Mode')}</p>
                  <p className="text-sm font-medium capitalize">{viewRow.work_mode}</p>
                </div>
                <div>
                  <p className="text-xs text-muted-foreground">{t('Status')}</p>
                  <p className="text-sm font-medium capitalize">{viewRow.status}</p>
                </div>
              </div>

              <div>
                <p className="text-xs text-muted-foreground">{t('Overall Note')}</p>
                <p className="text-sm">{viewRow.overall_note || '-'}</p>
              </div>

              <div>
                <p className="mb-2 text-xs text-muted-foreground">{t('Work line items')}</p>
                <div className="overflow-auto rounded-md border">
                  <table className="min-w-full text-sm">
                    <thead className="bg-muted/40">
                      <tr>
                        <th className="px-3 py-2 text-left">{t('No')}</th>
                        <th className="px-3 py-2 text-left">{t('From')}</th>
                        <th className="px-3 py-2 text-left">{t('To')}</th>
                        <th className="px-3 py-2 text-left">{t('Task')}</th>
                      </tr>
                    </thead>
                    <tbody>
                      {(viewRow.items || []).map((item) => (
                        <tr key={item.id || item.line_no} className="border-t">
                          <td className="px-3 py-2">{item.line_no}</td>
                          <td className="px-3 py-2">{(item.from_time || '').slice(0, 5)}</td>
                          <td className="px-3 py-2">{(item.to_time || '').slice(0, 5)}</td>
                          <td className="px-3 py-2">{item.task_note || '-'}</td>
                        </tr>
                      ))}
                      {(viewRow.items || []).length === 0 && (
                        <tr>
                          <td className="px-3 py-4 text-center text-muted-foreground" colSpan={4}>
                            {t('No line items found.')}
                          </td>
                        </tr>
                      )}
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          )}
        </DialogContent>
      </Dialog>
    </PageTemplate>
  );
}

