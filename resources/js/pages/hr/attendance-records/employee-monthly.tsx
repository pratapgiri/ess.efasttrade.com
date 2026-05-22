import { useMemo, useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { router, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { toast } from '@/components/custom-toast';
import MediaPicker from '@/components/MediaPicker';

type DayItem = {
  key: string;
  date_label: string;
  clock_in: string | null;
  clock_out: string | null;
  status: string | null;
  is_holiday: boolean;
  is_month_closed: boolean;
  leave: { status: string; id: number } | null;
  regularization: { id: number; status: string } | null;
  attendance_record_id: number | null;
  can_apply_ar: boolean;
  can_apply_leave: boolean;
};

type LeaveTypeOption = {
  id: number;
  name: string;
};

export default function EmployeeMonthlyAttendance() {
  const { t } = useTranslation();
  const { auth, days = [], leaveTypes = [], filters = {}, policy = {} } = usePage().props as unknown as {
    auth: { user: { id: number } };
    days: DayItem[];
    leaveTypes: LeaveTypeOption[];
    filters: { month?: number; year?: number };
    policy: { allowBackdatedAttendanceRequests?: boolean };
  };

  const [month, setMonth] = useState(String(filters.month || new Date().getMonth() + 1));
  const [year, setYear] = useState(String(filters.year || new Date().getFullYear()));
  const [activeDay, setActiveDay] = useState<DayItem | null>(null);
  const [isArModalOpen, setIsArModalOpen] = useState(false);
  const [isLeaveModalOpen, setIsLeaveModalOpen] = useState(false);
  const [arClockIn, setArClockIn] = useState('');
  const [arClockOut, setArClockOut] = useState('');
  const [arReason, setArReason] = useState('');
  const [leaveTypeId, setLeaveTypeId] = useState('');
  const [leaveIsHalfDay, setLeaveIsHalfDay] = useState('0');
  const [leaveHalfDayPart, setLeaveHalfDayPart] = useState('first_half');
  const [leaveStartDate, setLeaveStartDate] = useState('');
  const [leaveEndDate, setLeaveEndDate] = useState('');
  const [leaveAttachment, setLeaveAttachment] = useState('');
  const [leaveReason, setLeaveReason] = useState('');

  const monthOptions = useMemo(
    () =>
      Array.from({ length: 12 }, (_, i) => ({
        value: String(i + 1),
        label: new Date(2000, i, 1).toLocaleString('default', { month: 'long' }),
      })),
    []
  );

  const applyFilters = () => {
    router.get(
      route('hr.my-attendance-register.index'),
      { month, year },
      { preserveState: true, preserveScroll: true }
    );
  };

  const openArModal = (day: DayItem) => {
    setActiveDay(day);
    setArClockIn(day.clock_in ? day.clock_in.slice(0, 5) : '');
    setArClockOut(day.clock_out ? day.clock_out.slice(0, 5) : '');
    setArReason('');
    setIsArModalOpen(true);
  };

  const openLeaveModal = (day: DayItem) => {
    setActiveDay(day);
    setLeaveTypeId('');
    setLeaveIsHalfDay('0');
    setLeaveHalfDayPart('first_half');
    setLeaveStartDate(day.key);
    setLeaveEndDate(day.key);
    setLeaveAttachment('');
    setLeaveReason('');
    setIsLeaveModalOpen(true);
  };

  const submitRegularization = () => {
    if (!activeDay) return;
    if (!arReason.trim()) {
      toast.error(t('Reason is required.'));
      return;
    }

    router.post(
      route('hr.attendance-regularizations.store'),
      {
        employee_id: auth.user.id,
        attendance_record_id: activeDay.attendance_record_id || undefined,
        date: activeDay.key,
        requested_clock_in: arClockIn || undefined,
        requested_clock_out: arClockOut || undefined,
        reason: arReason.trim(),
      },
      {
        preserveScroll: true,
        onSuccess: (page: any) => {
          if (page.props.flash?.success) toast.success(t(page.props.flash.success));
          if (page.props.flash?.error) toast.error(t(page.props.flash.error));
          setIsArModalOpen(false);
        },
        onError: (errors) => {
          toast.error(Object.values(errors).join(', ') || t('Failed to submit regularization request.'));
        },
      }
    );
  };

  const submitLeave = () => {
    if (!activeDay) return;
    if (!leaveTypeId) {
      toast.error(t('Please select leave type.'));
      return;
    }
    if (!leaveReason.trim()) {
      toast.error(t('Reason is required.'));
      return;
    }
    if (!leaveStartDate || !leaveEndDate) {
      toast.error(t('Start date and end date are required.'));
      return;
    }
    if (leaveIsHalfDay === '1' && leaveStartDate !== leaveEndDate) {
      toast.error(t('For half day leave, start and end date must be same.'));
      return;
    }

    router.post(
      route('hr.leave-applications.store'),
      {
        employee_id: auth.user.id,
        leave_type_id: leaveTypeId,
        start_date: leaveStartDate,
        end_date: leaveIsHalfDay === '1' ? leaveStartDate : leaveEndDate,
        is_half_day: leaveIsHalfDay === '1',
        half_day_part: leaveIsHalfDay === '1' ? leaveHalfDayPart : undefined,
        reason: leaveReason.trim(),
        attachment: leaveAttachment || undefined,
      },
      {
        preserveScroll: true,
        onSuccess: (page: any) => {
          if (page.props.flash?.success) toast.success(t(page.props.flash.success));
          if (page.props.flash?.error) toast.error(t(page.props.flash.error));
          setIsLeaveModalOpen(false);
        },
        onError: (errors) => {
          toast.error(Object.values(errors).join(', ') || t('Failed to submit leave request.'));
        },
      }
    );
  };

  return (
    <PageTemplate
      title={t('My Attendance Register')}
      description={t('Monthly clock-in and clock-out details')}
      url="/hr/my-attendance-register"
      breadcrumbs={[
        { title: t('Dashboard'), href: route('dashboard') },
        { title: t('Attendance') },
        { title: t('My Attendance Register') },
      ]}
      noPadding
    >
      <div className="bg-white dark:bg-gray-900 rounded-lg shadow mb-4 p-4">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
          <div>
            <Label>{t('Month')}</Label>
            <Select value={month} onValueChange={setMonth}>
              <SelectTrigger className="mt-1">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {monthOptions.map((option) => (
                  <SelectItem key={option.value} value={option.value}>
                    {option.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
          <div>
            <Label>{t('Year')}</Label>
            <Input className="mt-1" type="number" value={year} onChange={(e) => setYear(e.target.value)} />
          </div>
          <div>
            <Button type="button" className="w-full md:w-auto" onClick={applyFilters}>
              {t('Apply')}
            </Button>
          </div>
        </div>
        {!policy.allowBackdatedAttendanceRequests && (
          <p className="mt-3 text-xs text-muted-foreground">{t('Backdated requests are currently disabled by admin.')}</p>
        )}
      </div>

      <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-auto">
        <table className="min-w-full text-sm">
          <thead className="bg-muted/50">
            <tr>
              <th className="px-3 py-2 text-left">{t('Date')}</th>
              <th className="px-3 py-2 text-left">{t('Check-in')}</th>
              <th className="px-3 py-2 text-left">{t('Check-out')}</th>
              <th className="px-3 py-2 text-left">{t('Action')}</th>
            </tr>
          </thead>
          <tbody>
            {days.map((day) => {
              const rowClass = day.is_holiday ? 'bg-red-50 dark:bg-red-950/20' : '';
              return (
                <tr key={day.key} className={`border-t ${rowClass}`}>
                  <td className="px-3 py-2">{day.is_holiday ? t('Holiday') : day.date_label}</td>
                  <td className="px-3 py-2">
                    {day.is_holiday ? t('Holiday') : day.clock_in ? window.appSettings?.formatTime(day.clock_in) || day.clock_in : '-:-'}
                  </td>
                  <td className="px-3 py-2">
                    {day.is_holiday ? t('Holiday') : day.clock_out ? window.appSettings?.formatTime(day.clock_out) || day.clock_out : '-:-'}
                  </td>
                  <td className="px-3 py-2">
                    {day.is_holiday ? (
                      <span className="text-muted-foreground">{t('Holiday')}</span>
                    ) : day.is_month_closed ? (
                      <span className="text-xs text-red-600">{t('Month Closed')}</span>
                    ) : (
                      <div className="flex flex-wrap gap-2">
                        <Button type="button" variant="link" className="h-auto p-0 text-sky-700" disabled={!day.can_apply_ar} onClick={() => openArModal(day)}>
                          {day.regularization?.status === 'pending' ? t('Regularise (Pending)') : t('Regularise')}
                        </Button>
                        <Button type="button" variant="link" className="h-auto p-0 text-sky-700" disabled={!day.can_apply_leave} onClick={() => openLeaveModal(day)}>
                          {day.leave?.status === 'pending' ? t('Apply Leave (Pending)') : t('Apply Leave')}
                        </Button>
                      </div>
                    )}
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>

      <Dialog open={isArModalOpen} onOpenChange={setIsArModalOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>{t('Apply Attendance Regularization')}</DialogTitle>
          </DialogHeader>
          <div className="space-y-3">
            <div>
              <Label>{t('Requested Clock In')}</Label>
              <Input type="time" value={arClockIn} onChange={(e) => setArClockIn(e.target.value)} />
            </div>
            <div>
              <Label>{t('Requested Clock Out')}</Label>
              <Input type="time" value={arClockOut} onChange={(e) => setArClockOut(e.target.value)} />
            </div>
            <div>
              <Label>{t('Reason')}</Label>
              <Textarea value={arReason} onChange={(e) => setArReason(e.target.value)} />
            </div>
            <div className="flex justify-end gap-2">
              <Button type="button" variant="outline" onClick={() => setIsArModalOpen(false)}>
                {t('Cancel')}
              </Button>
              <Button type="button" onClick={submitRegularization}>
                {t('Submit')}
              </Button>
            </div>
          </div>
        </DialogContent>
      </Dialog>

      <Dialog open={isLeaveModalOpen} onOpenChange={setIsLeaveModalOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>{t('Apply Leave')}</DialogTitle>
          </DialogHeader>
          <div className="space-y-3">
            <div>
              <Label>{t('Leave Type')}</Label>
              <select
                className="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                value={leaveTypeId}
                onChange={(e) => setLeaveTypeId(e.target.value)}
              >
                <option value="">{t('Select leave type')}</option>
                {leaveTypes.map((leaveType) => (
                  <option key={leaveType.id} value={String(leaveType.id)}>
                    {leaveType.name}
                  </option>
                ))}
              </select>
            </div>
            <div>
              <Label>{t('Leave Duration')}</Label>
              <select
                className="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                value={leaveIsHalfDay}
                onChange={(e) => setLeaveIsHalfDay(e.target.value)}
              >
                <option value="0">{t('Full Day')}</option>
                <option value="1">{t('Half Day')}</option>
              </select>
            </div>
            {leaveIsHalfDay === '1' && (
              <div>
                <Label>{t('Which half?')}</Label>
                <select
                  className="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                  value={leaveHalfDayPart}
                  onChange={(e) => setLeaveHalfDayPart(e.target.value)}
                >
                  <option value="first_half">{t('First half (AM / before break)')}</option>
                  <option value="second_half">{t('Second half (PM / after break)')}</option>
                </select>
              </div>
            )}
            <div>
              <Label>{t('Start Date')}</Label>
              <Input type="date" value={leaveStartDate} onChange={(e) => setLeaveStartDate(e.target.value)} />
            </div>
            <div>
              <Label>{t('End Date')}</Label>
              <Input type="date" value={leaveIsHalfDay === '1' ? leaveStartDate : leaveEndDate} onChange={(e) => setLeaveEndDate(e.target.value)} disabled={leaveIsHalfDay === '1'} />
            </div>
            <div>
              <Label>{t('Reason')}</Label>
              <Textarea value={leaveReason} onChange={(e) => setLeaveReason(e.target.value)} />
            </div>
            <div>
              <Label>{t('Attachment')}</Label>
              <MediaPicker
                value={leaveAttachment}
                onChange={(url) => setLeaveAttachment(url)}
                placeholder={t('Select attachment file...')}
              />
            </div>
            <div className="flex justify-end gap-2">
              <Button type="button" variant="outline" onClick={() => setIsLeaveModalOpen(false)}>
                {t('Cancel')}
              </Button>
              <Button type="button" onClick={submitLeave}>
                {t('Submit')}
              </Button>
            </div>
          </div>
        </DialogContent>
      </Dialog>
    </PageTemplate>
  );
}
