import { useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { router, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
type DayColumn = {
  key: string;
  day: string;
  label: string;
  is_sunday?: boolean;
  is_holiday?: boolean;
};

type CellData = {
  clock_in: string | null;
  clock_out: string | null;
  total_hours?: number;
  status: string;
  notes: string | null;
  is_lop?: boolean;
  /** Fractional LOP when leave is partially unpaid (e.g. 0.5 for half-day LWP) */
  lop_days?: number;
} | null;

type RowData = {
  id: number;
  name: string;
  employee_id: string;
  date_of_joining?: string | null;
  daily: Record<string, CellData>;
};

export default function MusterRoll() {
  const EMPLOYEE_COL_WIDTH = 220;
  const AVG_HOURS_COL_WIDTH = 150;
  const NET_PAYABLE_COL_WIDTH = 170;
  const DAY_COL_WIDTH = 90;

  const { t } = useTranslation();
  const { rows = [], days = [], filters = {}, range = {}, monthClosure = {} } = usePage().props as unknown as {
    rows: RowData[];
    days: DayColumn[];
    filters: { period_type?: string; month?: number; year?: number; week_start?: string | null };
    range: { from?: string; to?: string };
    monthClosure: { is_month_period?: boolean; selected_month?: string; is_closed?: boolean };
  };
  const [periodType, setPeriodType] = useState(filters.period_type || 'month');
  const [month, setMonth] = useState(String(filters.month || new Date().getMonth() + 1));
  const [year, setYear] = useState(String(filters.year || new Date().getFullYear()));
  const [weekStart, setWeekStart] = useState(filters.week_start || '');

  const applyFilters = () => {
    router.get(
      route('hr.muster-roll.index'),
      {
        period_type: periodType,
        month: periodType === 'month' ? month : undefined,
        year: year,
        week_start: periodType === 'week' ? weekStart : undefined,
      },
      { preserveState: true, preserveScroll: true }
    );
  };

  const handleCloseMonth = () => {
    router.post(
      route('hr.muster-roll.close-month'),
      { month: Number(month), year: Number(year) },
      { preserveScroll: true }
    );
  };

  const handleReopenMonth = () => {
    router.post(
      route('hr.muster-roll.reopen-month'),
      { month: Number(month), year: Number(year) },
      { preserveScroll: true }
    );
  };

  const notesMatchLop = (notes: string | null | undefined) => {
    if (!notes) return false;
    return /\b(lop|lwp)\b|loss\s+of\s+pay|leave\s+without\s+pay/i.test(notes);
  };

  const notesMatchWfh = (notes: string | null | undefined) => {
    if (!notes) return false;
    return /\bwfh\b|work\s*[-\s]?from\s*home/i.test(notes);
  };

  type CellKind = 'holiday' | 'leave' | 'half_day_leave' | 'wfh' | 'lop' | 'absent' | 'empty';

  const getCellKind = (day: DayColumn, cell: CellData, row?: RowData | null): CellKind => {
    const joinRaw = row?.date_of_joining?.trim();
    const periodFrom = range.from || '';
    if (joinRaw && periodFrom && joinRaw > periodFrom && day.key < joinRaw) {
      return 'empty';
    }

    const isHolidayDay = !!day.is_holiday || !!day.is_sunday;
    const todayKey = new Date().toLocaleDateString('en-CA');
    const isFutureDay = day.key > todayKey;

    if (cell) {
      // Holiday should override leave/WFH/LOP-style markers on that date.
      if (isHolidayDay && cell.status !== 'present' && cell.status !== 'half_day') return 'holiday';
      if (cell.is_lop || notesMatchLop(cell.notes)) return 'lop';
      if (notesMatchWfh(cell.notes)) return 'wfh';
      if (cell.status === 'on_leave') return 'leave';
      if (cell.status === 'half_day' && /^leave:/i.test((cell.notes || '').trim())) return 'half_day_leave';
      if (cell.status === 'absent') return 'absent';
      if (cell.status === 'holiday') return 'holiday';
    }
    if (!cell && isHolidayDay) return 'holiday';
    if (!cell && !isFutureDay) return 'absent';
    return 'empty';
  };

  const renderCell = (day: DayColumn, cell: CellData, row?: RowData | null) => {
    const kind = getCellKind(day, cell, row);
    const hasWorkingTimes = !!(cell?.clock_in || cell?.clock_out);
    const inTime = cell?.clock_in ? window.appSettings?.formatTime(cell.clock_in) || cell.clock_in : '-';
    const outTime = cell?.clock_out ? window.appSettings?.formatTime(cell.clock_out) || cell.clock_out : '-';
    const statusText = cell?.status ? cell.status.replace(/_/g, ' ') : '-';
    const notes = cell?.notes?.trim() || '-';

    let marker: string | null = null;
    if (!hasWorkingTimes) {
      if (kind === 'wfh') marker = 'WFH';
      else if (kind === 'leave') marker = 'L';
      else if (kind === 'half_day_leave') marker = 'HL';
      else if (kind === 'holiday') marker = 'H';
      else if (kind === 'lop') marker = 'LOP';
      else if (kind === 'absent') marker = 'A';
      else marker = '-';
    }

    const tooltip = [
      `${t('Status')}: ${statusText}`,
      `${t('Check-in')}: ${inTime}`,
      `${t('Check-out')}: ${outTime}`,
      `${t('Notes')}: ${notes}`,
    ].join('\n');

    if (kind === 'half_day_leave' && hasWorkingTimes) {
      return (
        <div className="text-[11px] leading-4" title={tooltip}>
          <div className="mb-0.5 flex justify-center">
            <span className="inline-flex min-w-[34px] items-center justify-center rounded px-1.5 py-0.5 text-[11px] font-semibold tracking-wide">
              HL
            </span>
          </div>
          <div className="font-mono text-green-700 dark:text-green-300">{inTime}</div>
          <div className="font-mono text-red-700 dark:text-red-300">{outTime}</div>
        </div>
      );
    }

    if (hasWorkingTimes) {
      return (
        <div className="text-[11px] leading-4" title={tooltip}>
          <div className="font-mono text-green-700 dark:text-green-300">{inTime}</div>
          <div className="font-mono text-red-700 dark:text-red-300">{outTime}</div>
        </div>
      );
    }

    return (
      <span className="inline-flex min-w-[34px] items-center justify-center rounded px-1.5 py-1 text-[11px] font-semibold tracking-wide" title={tooltip}>
        {marker}
      </span>
    );
  };

  const getAverageWorkingHoursData = (row: RowData) => {
    let totalHours = 0;
    let checkedInDays = 0;

    Object.values(row.daily).forEach((cell) => {
      if (!cell) return;
      if (!cell.clock_in) return;
      const isCountableStatus = cell.status === 'present' || cell.status === 'half_day';
      if (!isCountableStatus) return;

      checkedInDays += 1;
      totalHours += Number(cell.total_hours || 0);
    });

    const average = checkedInDays === 0 ? 0 : totalHours / checkedInDays;

    return {
      totalHours: totalHours.toFixed(2),
      checkedInDays,
      average: average.toFixed(2),
    };
  };

  const getNetPayableDaysData = (row: RowData) => {
    const periodFrom = range.from || '';
    const periodTo = range.to || '';
    const joinRaw = row.date_of_joining?.trim() || '';
    const effectiveStart =
      joinRaw && periodFrom && joinRaw > periodFrom ? joinRaw : periodFrom;

    const eligibleDays = days.filter((day) => {
      if (effectiveStart && day.key < effectiveStart) return false;
      if (periodTo && day.key > periodTo) return false;
      return true;
    });
    const totalWorkingDays = eligibleDays.length;
    let lopDays = 0;
    let absentDays = 0;

    eligibleDays.forEach((day) => {
      const kind = getCellKind(day, row.daily[day.key], row);

      if (kind === 'lop') {
        const frac = row.daily[day.key]?.lop_days;
        lopDays += frac != null && frac > 0 ? Number(frac) : 1;
      } else if (kind === 'absent') {
        absentDays += 1;
      }
    });

    const netPayableDays = Math.max(0, totalWorkingDays - lopDays - absentDays);

    return {
      totalWorkingDays: totalWorkingDays.toFixed(2),
      lopDays: lopDays.toFixed(2),
      absentDays: absentDays.toFixed(2),
      netPayableDays: netPayableDays.toFixed(2),
    };
  };

  const getHeaderClass = (day: DayColumn) => {
    if (day.is_holiday || day.is_sunday) {
      return 'bg-red-100 text-red-800 dark:bg-red-950/60 dark:text-red-200';
    }
    return 'bg-muted dark:bg-muted';
  };

  const headerDayThClass =
    'sticky top-0 z-[55] border-b border-border px-2 py-2 text-center text-xs font-semibold shadow-[0_1px_0_0_hsl(var(--border))]';

  const getCellClass = (day: DayColumn, cell: CellData, row?: RowData | null) => {
    const kind = getCellKind(day, cell, row);
    switch (kind) {
      case 'holiday':
        return 'bg-red-100 dark:bg-red-950/50';
      case 'leave':
        return 'bg-green-100 dark:bg-green-950/50';
      case 'half_day_leave':
        return 'bg-green-100 dark:bg-green-950/50';
      case 'wfh':
        return 'bg-sky-100 dark:bg-sky-950/45';
      case 'lop':
        return 'bg-gray-200 text-gray-900 dark:bg-gray-700 dark:text-gray-100';
      case 'absent':
        return 'bg-gray-300 text-gray-900 dark:bg-gray-600 dark:text-gray-100';
      default:
        return '';
    }
  };

  const tableTotalWidth = EMPLOYEE_COL_WIDTH + AVG_HOURS_COL_WIDTH + (days.length * DAY_COL_WIDTH) + NET_PAYABLE_COL_WIDTH;

  const monthOptions = Array.from({ length: 12 }, (_, i) => ({
    value: String(i + 1),
    label: new Date(2000, i, 1).toLocaleString('default', { month: 'long' }),
  }));

  return (
    <PageTemplate
      title={t('Attendance Register')}
      description={t('Employee-wise attendance view')}
      url="/hr/muster-roll"
      breadcrumbs={[
        { title: t('Dashboard'), href: route('dashboard') },
        { title: t('Attendance'), href: route('hr.attendance-records.index') },
        { title: t('Attendance Register') },
      ]}
      noPadding
    >
      <div className="min-w-0 max-w-full overflow-x-hidden">
        <div className="bg-white dark:bg-gray-900 rounded-lg shadow mb-4 p-4">
        <div className="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
          <div>
            <label className="block text-sm font-medium mb-1">{t('View By')}</label>
            <select className="w-full border rounded-md px-3 py-2 bg-background" value={periodType} onChange={(e) => setPeriodType(e.target.value)}>
              <option value="month">{t('Month')}</option>
              <option value="week">{t('Week')}</option>
              <option value="year">{t('Year')}</option>
            </select>
          </div>

          {periodType === 'month' && (
            <div>
              <label className="block text-sm font-medium mb-1">{t('Month')}</label>
              <select className="w-full border rounded-md px-3 py-2 bg-background" value={month} onChange={(e) => setMonth(e.target.value)}>
                {monthOptions.map((m) => (
                  <option key={m.value} value={m.value}>
                    {m.label}
                  </option>
                ))}
              </select>
            </div>
          )}

          {periodType === 'week' && (
            <div>
              <label className="block text-sm font-medium mb-1">{t('Week Start')}</label>
              <input type="date" className="w-full border rounded-md px-3 py-2 bg-background" value={weekStart} onChange={(e) => setWeekStart(e.target.value)} />
            </div>
          )}

          <div>
            <label className="block text-sm font-medium mb-1">{t('Year')}</label>
            <input type="number" className="w-full border rounded-md px-3 py-2 bg-background" value={year} onChange={(e) => setYear(e.target.value)} />
          </div>

          <div>
            <button onClick={applyFilters} className="w-full md:w-auto px-4 py-2 bg-primary text-primary-foreground rounded-md">
              {t('Apply')}
            </button>
          </div>
          {monthClosure?.is_month_period && (
            <div>
              {monthClosure?.is_closed ? (
                <button onClick={handleReopenMonth} className="w-full md:w-auto px-4 py-2 rounded-md border border-red-300 text-red-700">
                  {t('Reopen Month')}
                </button>
              ) : (
                <button onClick={handleCloseMonth} className="w-full md:w-auto px-4 py-2 rounded-md border border-emerald-300 text-emerald-700">
                  {t('Close Month')}
                </button>
              )}
            </div>
          )}
        </div>

        <p className="text-xs text-muted-foreground mt-3">
          {t('Showing')}: {range.from} - {range.to}
        </p>

        <div className="flex flex-wrap gap-3 mt-4 text-xs">
          <span className="inline-flex items-center gap-1.5">
            <span className="inline-block h-3 w-3 rounded-sm bg-red-100 dark:bg-red-950/50 border border-red-200" />
            {t('Holiday')}
          </span>
          <span className="inline-flex items-center gap-1.5">
            <span className="inline-block h-3 w-3 rounded-sm bg-green-100 dark:bg-green-950/50 border border-green-200" />
            {t('Leave')}
          </span>
          <span className="inline-flex items-center gap-1.5">
            <span className="inline-block h-3 w-3 rounded-sm bg-sky-100 dark:bg-sky-950/45 border border-sky-200" />
            {t('WFH')} <span className="text-muted-foreground">(WFH, work from home)</span>
          </span>
          <span className="inline-flex items-center gap-1.5">
            <span className="inline-block h-3 w-3 rounded-sm bg-gray-200 dark:bg-gray-700 border border-gray-300 dark:border-gray-600" />
            {t('LOP')} <span className="text-muted-foreground">(LOP, LWP)</span>
          </span>
          <span className="inline-flex items-center gap-1.5">
            <span className="inline-block h-3 w-3 rounded-sm bg-black dark:bg-neutral-950" />
            {t('Absent')}
          </span>
        </div>
        </div>

      <div className="min-w-0 max-w-full overflow-hidden rounded-lg bg-white shadow dark:bg-gray-900">
        {rows.length === 0 ? (
          <div className="px-3 py-6 text-center text-sm text-muted-foreground">
            {t('No employee attendance data found for selected period.')}
          </div>
        ) : (
          <div className="relative isolate max-h-[calc(100dvh-13rem)] w-full min-w-0 max-w-full overflow-auto overscroll-contain">
            <table className="border-separate border-spacing-0 table-fixed" style={{ width: `${tableTotalWidth}px` }}>
              <thead>
                <tr>
                  <th className="sticky left-0 top-0 z-[70] w-[220px] min-w-[220px] max-w-[220px] border-b border-border bg-muted px-3 py-2 text-left text-xs font-semibold shadow-[2px_0_0_0_hsl(var(--border)),0_1px_0_0_hsl(var(--border))]">
                    {t('Employee')}
                  </th>
                  <th
                    className="sticky top-0 z-[70] w-[150px] min-w-[150px] max-w-[150px] border-b border-border bg-yellow-200 px-3 py-2 text-center text-xs font-semibold shadow-[2px_0_0_0_hsl(var(--border)),0_1px_0_0_hsl(var(--border))] dark:bg-yellow-800/70"
                    style={{ left: `${EMPLOYEE_COL_WIDTH}px` }}
                  >
                    {t('Avg. Working Hours')}
                  </th>
                  {days.map((day) => (
                    <th key={day.key} className={`${headerDayThClass} min-w-[90px] ${getHeaderClass(day)}`}>
                      <div>{day.day}</div>
                      <div className="text-muted-foreground">{day.label}</div>
                    </th>
                  ))}
                  <th className="sticky top-0 z-[55] min-w-[170px] border-b border-border bg-blue-100 px-3 py-2 text-center text-xs font-semibold shadow-[0_1px_0_0_hsl(var(--border))] dark:bg-blue-900/50">
                    {t('Net Payable Days')}
                  </th>
                </tr>
              </thead>
              <tbody>
                {rows.map((row) => {
                  const avgHoursData = getAverageWorkingHoursData(row);
                  const payableDaysData = getNetPayableDaysData(row);
                  return (
                    <tr key={row.id} className="border-b">
                      <td className="sticky left-0 z-[35] w-[220px] min-w-[220px] max-w-[220px] border-r border-border bg-white px-3 py-2 align-middle shadow-[2px_0_0_0_hsl(var(--border))] dark:bg-gray-900">
                        <div className="truncate font-medium">{row.name}</div>
                        <div className="text-xs text-muted-foreground">{row.employee_id || '-'}</div>
                      </td>
                      <td
                        className="sticky z-[36] w-[150px] min-w-[150px] max-w-[150px] border-r border-border bg-yellow-100 px-3 py-2 text-center align-middle shadow-[2px_0_0_0_hsl(var(--border))] dark:bg-yellow-900/50"
                        style={{ left: `${EMPLOYEE_COL_WIDTH}px` }}
                      >
                        <div className="inline-flex items-center gap-1.5 text-[11px]">
                          <span className="font-semibold">{avgHoursData.average} hrs</span>
                          <span
                            className="inline-flex h-4 w-4 items-center justify-center rounded-full border border-gray-400 text-[10px] font-semibold text-gray-700 dark:text-gray-200 cursor-help"
                            title={`${avgHoursData.average} hrs = ${avgHoursData.totalHours} / ${avgHoursData.checkedInDays} (Total Hours / Present/Half-day Days)`}
                            aria-label={t('Average hour calculation info')}
                          >
                            i
                          </span>
                        </div>
                      </td>
                      {days.map((day) => (
                        <td key={`${row.id}-${day.key}`} className={`min-w-[90px] px-2 py-2 text-center align-middle ${getCellClass(day, row.daily[day.key], row)}`}>
                          {renderCell(day, row.daily[day.key], row)}
                        </td>
                      ))}
                      <td className="min-w-[170px] bg-blue-50 px-3 py-2 text-center align-middle dark:bg-blue-950/35">
                        <div className="inline-flex items-center gap-1.5 text-[11px]">
                          <span className="font-semibold">{payableDaysData.netPayableDays}</span>
                          <span
                            className="inline-flex h-4 w-4 items-center justify-center rounded-full border border-gray-400 text-[10px] font-semibold text-gray-700 dark:text-gray-200 cursor-help"
                            title={`${payableDaysData.netPayableDays} = ${payableDaysData.totalWorkingDays} - ${payableDaysData.lopDays} - ${payableDaysData.absentDays} (${t('Total working days')} - ${t('LOP')} - ${t('Absent')})`}
                            aria-label={t('Net payable days calculation info')}
                          >
                            i
                          </span>
                        </div>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        )}
      </div>
      </div>
    </PageTemplate>
  );
}
