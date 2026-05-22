// pages/hr/monthly-pl-accrual-logs/index.tsx
import { useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { CrudTable } from '@/components/CrudTable';
import { useTranslation } from 'react-i18next';
import { Pagination } from '@/components/ui/pagination';
import { SearchAndFilterBar } from '@/components/ui/search-and-filter-bar';

export default function MonthlyPlAccrualLogs() {
  const { t } = useTranslation();
  const { auth, logs, companies, leaveTypes, filters: pageFilters = {} } = usePage().props as any;
  const permissions = auth?.permissions || [];
  const userType = auth?.user?.type;

  const [selectedCompany, setSelectedCompany] = useState(pageFilters.company_id || 'all');
  const [selectedYear, setSelectedYear] = useState(pageFilters.year || new Date().getFullYear().toString());
  const [selectedMonth, setSelectedMonth] = useState(pageFilters.month || 'all');
  const [selectedLeaveType, setSelectedLeaveType] = useState(pageFilters.leave_type_id || 'all');
  const [selectedStatus, setSelectedStatus] = useState(pageFilters.status || 'all');
  const [batchId, setBatchId] = useState(pageFilters.batch_id || '');
  const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
  const [showFilters, setShowFilters] = useState(false);

  const hasActiveFilters = () =>
    searchTerm !== '' ||
    batchId !== '' ||
    selectedCompany !== 'all' ||
    selectedYear !== '' ||
    selectedMonth !== 'all' ||
    selectedLeaveType !== 'all' ||
    selectedStatus !== 'all';

  const activeFilterCount = () =>
    (searchTerm ? 1 : 0) +
    (batchId ? 1 : 0) +
    (selectedCompany !== 'all' ? 1 : 0) +
    (selectedYear !== '' ? 1 : 0) +
    (selectedMonth !== 'all' ? 1 : 0) +
    (selectedLeaveType !== 'all' ? 1 : 0) +
    (selectedStatus !== 'all' ? 1 : 0);

  const applyFilters = () => {
    router.get(route('hr.monthly-pl-accrual-logs.index'), {
      page: 1,
      search: searchTerm || undefined,
      batch_id: batchId || undefined,
      company_id: selectedCompany !== 'all' ? selectedCompany : undefined,
      year: selectedYear || undefined,
      month: selectedMonth !== 'all' ? selectedMonth : undefined,
      leave_type_id: selectedLeaveType !== 'all' ? selectedLeaveType : undefined,
      status: selectedStatus !== 'all' ? selectedStatus : undefined,
      per_page: pageFilters.per_page,
    }, { preserveState: true, preserveScroll: true });
  };

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    applyFilters();
  };

  const handleSort = (field: string) => {
    const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';
    router.get(route('hr.monthly-pl-accrual-logs.index'), {
      sort_field: field,
      sort_direction: direction,
      page: 1,
      search: searchTerm || undefined,
      batch_id: batchId || undefined,
      company_id: selectedCompany !== 'all' ? selectedCompany : undefined,
      year: selectedYear || undefined,
      month: selectedMonth !== 'all' ? selectedMonth : undefined,
      leave_type_id: selectedLeaveType !== 'all' ? selectedLeaveType : undefined,
      status: selectedStatus !== 'all' ? selectedStatus : undefined,
      per_page: pageFilters.per_page,
    }, { preserveState: true, preserveScroll: true });
  };

  const handleResetFilters = () => {
    setSearchTerm('');
    setBatchId('');
    setSelectedCompany('all');
    setSelectedYear(new Date().getFullYear().toString());
    setSelectedMonth('all');
    setSelectedLeaveType('all');
    setSelectedStatus('all');
    setShowFilters(false);
    router.get(route('hr.monthly-pl-accrual-logs.index'), {
      page: 1,
      per_page: pageFilters.per_page,
    }, { preserveState: true, preserveScroll: true });
  };

  const companyOptions = [
    { value: 'all', label: t('All Companies'), disabled: true },
    ...(companies || []).map((c: any) => ({
      value: c.id.toString(),
      label: c.name,
    })),
  ];

  const monthOptions = [
    { value: 'all', label: t('All Months'), disabled: true },
    { value: '1', label: t('January') },
    { value: '2', label: t('February') },
    { value: '3', label: t('March') },
    { value: '4', label: t('April') },
    { value: '5', label: t('May') },
    { value: '6', label: t('June') },
    { value: '7', label: t('July') },
    { value: '8', label: t('August') },
    { value: '9', label: t('September') },
    { value: '10', label: t('October') },
    { value: '11', label: t('November') },
    { value: '12', label: t('December') },
  ];

  const statusOptions = [
    { value: 'all', label: t('All Statuses'), disabled: true },
    { value: 'credited', label: t('Credited') },
    { value: 'no_change', label: t('No change') },
  ];

  const leaveTypeOptions = [
    { value: 'all', label: t('All Leave Types'), disabled: true },
    ...(leaveTypes || []).map((lt: any) => ({
      value: lt.id.toString(),
      label: lt.name,
    })),
  ];

  const yearOptions = (() => {
    const currentYear = new Date().getFullYear();
    const years = [];
    for (let y = currentYear + 1; y >= currentYear - 5; y--) {
      years.push({ value: y.toString(), label: y.toString() });
    }
    return years;
  })();

  const companyColumn = {
    key: 'company_id',
    label: t('Company'),
    render: (value: number) => `#${value}`,
  };

  const dataColumns = [
    {
      key: 'employee',
      label: t('Employee'),
      render: (_: unknown, row: any) => row.employee?.name || `#${row.employee_id}`,
    },
    {
      key: 'leave_type',
      label: t('Leave Type'),
      render: (_: unknown, row: any) => (
        <div className="flex items-center gap-2">
          <span
            className="inline-block h-2 w-2 rounded-full"
            style={{ backgroundColor: row.leave_type?.color || '#94a3b8' }}
          />
          <span>{row.leave_type?.name || '-'}</span>
        </div>
      ),
    },
    {
      key: 'period',
      label: t('Period'),
      sortable: true,
      render: (_: unknown, row: any) => `${row.year}-${String(row.month).padStart(2, '0')}`,
    },
    {
      key: 'working_days',
      label: t('Worked days'),
      render: (v: number) => <span className="font-mono">{Number(v ?? 0).toFixed(2)}</span>,
    },
    {
      key: 'entitled_days',
      label: t('PL entitled'),
      render: (v: number) => <span className="font-mono">{v}</span>,
    },
    {
      key: 'previous_credited_days',
      label: t('Prev. credited'),
      render: (v: number) => <span className="font-mono">{Number(v ?? 0).toFixed(2)}</span>,
    },
    {
      key: 'delta_applied',
      label: t('Delta'),
      render: (v: number) => <span className="font-mono">{Number(v ?? 0).toFixed(2)}</span>,
    },
    {
      key: 'allocated_after',
      label: t('Allocated after'),
      render: (v: number | null) => (
        <span className="font-mono">{v === null || v === undefined ? '-' : Number(v).toFixed(2)}</span>
      ),
    },
    {
      key: 'status',
      label: t('Status'),
      render: (value: string) => {
        const statusColors: Record<string, string> = {
          credited: 'bg-green-50 text-green-700 ring-green-600/20',
          no_change: 'bg-gray-50 text-gray-700 ring-gray-600/20',
        };
        const cls = statusColors[value] || 'bg-gray-50 text-gray-700 ring-gray-600/20';
        return (
          <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${cls}`}>
            {value}
          </span>
        );
      },
    },
    {
      key: 'batch_id',
      label: t('Batch'),
      render: (v: string) => <span className="font-mono text-xs">{v ? `${v.slice(0, 8)}…` : '-'}</span>,
    },
    {
      key: 'processor',
      label: t('Processed by'),
      render: (_: unknown, row: any) => row.processor?.name || '-',
    },
    {
      key: 'processed_at',
      label: t('Processed At'),
      sortable: true,
      render: (value: string) =>
        window.appSettings?.formatDateTimeSimple(value, true) || new Date(value).toLocaleString(),
    },
    {
      key: 'message',
      label: t('Message'),
      render: (value: string) => <span className="text-xs text-muted-foreground">{value || '-'}</span>,
    },
  ];

  const columns =
    userType === 'superadmin'
      ? [dataColumns[0], companyColumn, ...dataColumns.slice(1)]
      : dataColumns;

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Leave Balances'), href: route('hr.leave-balances.index') },
    { title: t('Monthly PL accrual logs') },
  ];

  return (
    <PageTemplate
      title={t('Monthly PL accrual logs')}
      url="/hr/monthly-pl-accrual-logs"
      breadcrumbs={breadcrumbs}
      noPadding
      actions={[]}
    >
      <div className="mb-4 rounded-lg border border-dashed border-muted-foreground/25 bg-white p-4 shadow dark:bg-gray-900">
        <h3 className="mb-1 text-sm font-semibold">{t('About this log')}</h3>
        <p className="text-xs text-muted-foreground">
          {t(
            'Each row is one employee after a Monthly PL accrual run from Leave Balances. Rows sharing the same batch id are from the same run. Filter by period, employee name, or paste a full batch UUID from the success message.'
          )}
        </p>
      </div>

      <div className="mb-4 rounded-lg bg-white p-4 shadow dark:bg-gray-900">
        <SearchAndFilterBar
          searchTerm={searchTerm}
          onSearchChange={setSearchTerm}
          onSearch={handleSearch}
          filters={[
            ...(userType === 'superadmin'
              ? [
                  {
                    name: 'company_id',
                    label: t('Company'),
                    type: 'select',
                    value: selectedCompany,
                    onChange: setSelectedCompany,
                    options: companyOptions,
                    searchable: true,
                  },
                ]
              : []),
            {
              name: 'year',
              label: t('Year'),
              type: 'select',
              value: selectedYear,
              onChange: setSelectedYear,
              options: yearOptions,
            },
            {
              name: 'month',
              label: t('Month'),
              type: 'select',
              value: selectedMonth,
              onChange: setSelectedMonth,
              options: monthOptions,
            },
            {
              name: 'leave_type_id',
              label: t('Leave Type'),
              type: 'select',
              value: selectedLeaveType,
              onChange: setSelectedLeaveType,
              options: leaveTypeOptions,
            },
            {
              name: 'status',
              label: t('Status'),
              type: 'select',
              value: selectedStatus,
              onChange: setSelectedStatus,
              options: statusOptions,
            },
            {
              name: 'batch_id',
              label: t('Batch ID'),
              type: 'text',
              value: batchId,
              onChange: setBatchId,
              placeholder: t('Full UUID optional'),
            },
          ]}
          showFilters={showFilters}
          setShowFilters={setShowFilters}
          hasActiveFilters={hasActiveFilters}
          activeFilterCount={activeFilterCount}
          onResetFilters={handleResetFilters}
          onApplyFilters={applyFilters}
          currentPerPage={pageFilters.per_page?.toString() || '20'}
          onPerPageChange={(value) => {
            router.get(route('hr.monthly-pl-accrual-logs.index'), {
              page: 1,
              per_page: parseInt(value, 10),
              search: searchTerm || undefined,
              batch_id: batchId || undefined,
              company_id: selectedCompany !== 'all' ? selectedCompany : undefined,
              year: selectedYear || undefined,
              month: selectedMonth !== 'all' ? selectedMonth : undefined,
              leave_type_id: selectedLeaveType !== 'all' ? selectedLeaveType : undefined,
              status: selectedStatus !== 'all' ? selectedStatus : undefined,
            }, { preserveState: true, preserveScroll: true });
          }}
        />
      </div>

      <div className="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-900">
        <CrudTable
          columns={columns}
          actions={[]}
          data={logs?.data || []}
          from={logs?.from || 1}
          onAction={() => {}}
          sortField={pageFilters.sort_field}
          sortDirection={pageFilters.sort_direction}
          onSort={handleSort}
          permissions={permissions}
          entityPermissions={{
            view: 'manage-leave-balances',
            create: 'manage-leave-balances',
            edit: 'manage-leave-balances',
            delete: 'manage-leave-balances',
          }}
        />

        <Pagination
          from={logs?.from || 0}
          to={logs?.to || 0}
          total={logs?.total || 0}
          links={logs?.links}
          entityName={t('monthly PL accrual logs')}
          onPageChange={(url) => router.get(url)}
        />
      </div>
    </PageTemplate>
  );
}
