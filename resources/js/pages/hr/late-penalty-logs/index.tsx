// pages/hr/late-penalty-logs/index.tsx
import { useMemo, useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { CrudTable } from '@/components/CrudTable';
import { useTranslation } from 'react-i18next';
import { Pagination } from '@/components/ui/pagination';
import { SearchAndFilterBar } from '@/components/ui/search-and-filter-bar';
import { Button } from '@/components/ui/button';
import { toast } from '@/components/custom-toast';

function previousCalendarMonth(): { year: number; month: number } {
  const d = new Date();
  d.setDate(1);
  d.setMonth(d.getMonth() - 1);
  return { year: d.getFullYear(), month: d.getMonth() + 1 };
}

export default function LatePenaltyLogs() {
  const { t } = useTranslation();
  const { auth, logs, companies, filters: pageFilters = {} } = usePage().props as any;
  const permissions = auth?.permissions || [];
  const userType = auth?.user?.type;

  // State
  const [selectedCompany, setSelectedCompany] = useState(pageFilters.company_id || 'all');
  const [selectedYear, setSelectedYear] = useState(pageFilters.year || new Date().getFullYear().toString());
  const [selectedMonth, setSelectedMonth] = useState(pageFilters.month || 'all');
  const [selectedStatus, setSelectedStatus] = useState(pageFilters.status || 'all');
  const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
  const [showFilters, setShowFilters] = useState(false);
  const [calculating, setCalculating] = useState(false);

  const defaultPenaltyPeriod = useMemo(() => previousCalendarMonth(), []);
  const [penaltyYear, setPenaltyYear] = useState(() => String(defaultPenaltyPeriod.year));
  const [penaltyMonth, setPenaltyMonth] = useState(() => String(defaultPenaltyPeriod.month));

  const hasActiveFilters = () => {
    return (
      searchTerm !== '' ||
      selectedCompany !== 'all' ||
      selectedYear !== '' ||
      selectedMonth !== 'all' ||
      selectedStatus !== 'all'
    );
  };

  const activeFilterCount = () => {
    return (searchTerm ? 1 : 0) +
      (selectedCompany !== 'all' ? 1 : 0) +
      (selectedYear !== '' ? 1 : 0) +
      (selectedMonth !== 'all' ? 1 : 0) +
      (selectedStatus !== 'all' ? 1 : 0);
  };

  const applyFilters = () => {
    router.get(route('hr.late-penalty-logs.index'), {
      page: 1,
      search: searchTerm || undefined,
      company_id: selectedCompany !== 'all' ? selectedCompany : undefined,
      year: selectedYear || undefined,
      month: selectedMonth !== 'all' ? selectedMonth : undefined,
      status: selectedStatus !== 'all' ? selectedStatus : undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    applyFilters();
  };

  const handleSort = (field: string) => {
    const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';

    router.get(route('hr.late-penalty-logs.index'), {
      sort_field: field,
      sort_direction: direction,
      page: 1,
      search: searchTerm || undefined,
      company_id: selectedCompany !== 'all' ? selectedCompany : undefined,
      year: selectedYear || undefined,
      month: selectedMonth !== 'all' ? selectedMonth : undefined,
      status: selectedStatus !== 'all' ? selectedStatus : undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  const runPenaltyCalculation = () => {
    if (userType === 'superadmin' && selectedCompany === 'all') {
      toast.error(t('Please select a company before calculating penalties.'));
      return;
    }
    setCalculating(true);
    router.post(
      route('hr.late-penalty-logs.calculate'),
      {
        year: parseInt(penaltyYear, 10),
        month: parseInt(penaltyMonth, 10),
        ...(userType === 'superadmin' ? { company_id: parseInt(selectedCompany, 10) } : {}),
      },
      {
        preserveScroll: true,
        onFinish: () => setCalculating(false),
        onSuccess: (page: any) => {
          const flash = page.props?.flash as { success?: string; error?: string } | undefined;
          if (flash?.success) toast.success(t(flash.success));
          if (flash?.error) toast.error(t(flash.error));
        },
      }
    );
  };

  const handleResetFilters = () => {
    setSearchTerm('');
    setSelectedCompany('all');
    setSelectedYear(new Date().getFullYear().toString());
    setSelectedMonth('all');
    setSelectedStatus('all');
    setShowFilters(false);

    router.get(route('hr.late-penalty-logs.index'), {
      page: 1,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  const companyOptions = [
    { value: 'all', label: t('All Companies'), disabled: true },
    ...(companies || []).map((c: any) => ({
      value: c.id.toString(),
      label: c.name
    }))
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
    { value: '12', label: t('December') }
  ];

  const statusOptions = [
    { value: 'all', label: t('All Statuses'), disabled: true },
    { value: 'applied', label: t('Applied') },
    { value: 'skipped', label: t('Skipped') },
    { value: 'dry_run', label: t('Dry Run') },
    { value: 'failed', label: t('Failed') }
  ];

  const yearOptions = (() => {
    const currentYear = new Date().getFullYear();
    const years = [];
    for (let y = currentYear + 1; y >= currentYear - 5; y--) {
      years.push({ value: y.toString(), label: y.toString() });
    }
    return years;
  })();

  const columns = [
    {
      key: 'employee',
      label: t('Employee'),
      render: (_: any, row: any) => row.employee?.name || `#${row.employee_id}`
    },
    {
      key: 'company_id',
      label: t('Company'),
      render: (value: any) => `#${value}`
    },
    {
      key: 'period',
      label: t('Period'),
      sortable: true,
      render: (_: any, row: any) => `${row.year}-${String(row.month).padStart(2, '0')}`
    },
    {
      key: 'late_count',
      label: t('Late Count'),
      render: (value: number) => <span className="font-mono">{value}</span>
    },
    {
      key: 'expected_penalty_days',
      label: t('Expected Penalty'),
      render: (value: any) => <span className="font-mono">{Number(value || 0).toFixed(2)}</span>
    },
    {
      key: 'already_applied_days',
      label: t('Already Applied'),
      render: (value: any) => <span className="font-mono">{Number(value || 0).toFixed(2)}</span>
    },
    {
      key: 'applied_now_days',
      label: t('Applied Now'),
      render: (value: any) => <span className="font-mono">{Number(value || 0).toFixed(2)}</span>
    },
    {
      key: 'status',
      label: t('Status'),
      render: (value: string) => {
        const statusColors = {
          applied: 'bg-green-50 text-green-700 ring-green-600/20',
          skipped: 'bg-gray-50 text-gray-700 ring-gray-600/20',
          dry_run: 'bg-blue-50 text-blue-700 ring-blue-600/20',
          failed: 'bg-red-50 text-red-700 ring-red-600/20',
        };

        const cls = statusColors[value as keyof typeof statusColors] || 'bg-gray-50 text-gray-700 ring-gray-600/20';

        return (
          <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${cls}`}>
            {value}
          </span>
        );
      }
    },
    {
      key: 'processed_at',
      label: t('Processed At'),
      sortable: true,
      render: (value: string) => window.appSettings?.formatDateTimeSimple(value, true) || new Date(value).toLocaleString()
    },
    {
      key: 'message',
      label: t('Message'),
      render: (value: string) => <span className="text-xs text-muted-foreground">{value || '-'}</span>
    }
  ];

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('HR Management'), href: route('hr.late-penalty-logs.index') },
    { title: t('Late Penalty Logs') }
  ];

  return (
    <PageTemplate
      title={t('Late Penalty Logs')}
      url="/hr/late-penalty-logs"
      breadcrumbs={breadcrumbs}
      noPadding
      actions={[]}
    >
      {/* Search and filters */}
      <div className="bg-white dark:bg-gray-900 rounded-lg shadow mb-4 p-4">
        <SearchAndFilterBar
          searchTerm={searchTerm}
          onSearchChange={setSearchTerm}
          onSearch={handleSearch}
          filters={[
            ...(userType === 'superadmin' ? [{
              name: 'company_id',
              label: t('Company'),
              type: 'select',
              value: selectedCompany,
              onChange: setSelectedCompany,
              options: companyOptions,
              searchable: true
            }] : []),
            {
              name: 'year',
              label: t('Year'),
              type: 'select',
              value: selectedYear,
              onChange: setSelectedYear,
              options: yearOptions
            },
            {
              name: 'month',
              label: t('Month'),
              type: 'select',
              value: selectedMonth,
              onChange: setSelectedMonth,
              options: monthOptions
            },
            {
              name: 'status',
              label: t('Status'),
              type: 'select',
              value: selectedStatus,
              onChange: setSelectedStatus,
              options: statusOptions
            }
          ]}
          showFilters={showFilters}
          setShowFilters={setShowFilters}
          hasActiveFilters={hasActiveFilters}
          activeFilterCount={activeFilterCount}
          onResetFilters={handleResetFilters}
          onApplyFilters={applyFilters}
          currentPerPage={pageFilters.per_page?.toString() || '10'}
          onPerPageChange={(value) => {
            router.get(route('hr.late-penalty-logs.index'), {
              page: 1,
              per_page: parseInt(value, 10),
              search: searchTerm || undefined,
              company_id: selectedCompany !== 'all' ? selectedCompany : undefined,
              year: selectedYear || undefined,
              month: selectedMonth !== 'all' ? selectedMonth : undefined,
              status: selectedStatus !== 'all' ? selectedStatus : undefined
            }, { preserveState: true, preserveScroll: true });
          }}
        />
      </div>

      <div className="bg-white dark:bg-gray-900 rounded-lg shadow mb-4 p-4 border border-dashed border-muted-foreground/25">
        <h3 className="text-sm font-semibold mb-1">{t('Calculate late penalty')}</h3>
        <p className="text-xs text-muted-foreground mb-3">
          {t(
            'Runs monthly late-penalty logic: 10-minute grace from shift start; after grace and up to +30 minutes counts as Late (first 2 are free, from 3rd each adds 0.5 leave day); after +30 minutes each entry directly adds 0.5 leave day. Penalty uses the Paid Leave leave type (must exist for your company).'
          )}
        </p>
        <div className="flex flex-wrap items-end gap-3">
          <div>
            <label className="block text-xs font-medium mb-1">{t('Year')}</label>
            <select
              className="border rounded-md px-3 py-2 text-sm bg-background min-w-[100px]"
              value={penaltyYear}
              onChange={(e) => setPenaltyYear(e.target.value)}
            >
              {yearOptions.map((y) => (
                <option key={y.value} value={y.value}>
                  {y.label}
                </option>
              ))}
            </select>
          </div>
          <div>
            <label className="block text-xs font-medium mb-1">{t('Month')}</label>
            <select
              className="border rounded-md px-3 py-2 text-sm bg-background min-w-[140px]"
              value={penaltyMonth}
              onChange={(e) => setPenaltyMonth(e.target.value)}
            >
              {monthOptions
                .filter((m) => m.value !== 'all')
                .map((m) => (
                  <option key={m.value} value={m.value}>
                    {m.label}
                  </option>
                ))}
            </select>
          </div>
          {userType === 'superadmin' && (
            <div>
              <label className="block text-xs font-medium mb-1">{t('Company')}</label>
              <select
                className="border rounded-md px-3 py-2 text-sm bg-background min-w-[200px]"
                value={selectedCompany}
                onChange={(e) => setSelectedCompany(e.target.value)}
              >
                {companyOptions
                  .filter((o: { value: string; disabled?: boolean }) => !o.disabled)
                  .map((c: { value: string; label: string }) => (
                    <option key={c.value} value={c.value}>
                      {c.label}
                    </option>
                  ))}
              </select>
            </div>
          )}
          <Button type="button" onClick={runPenaltyCalculation} disabled={calculating}>
            {calculating ? t('Calculating…') : t('Calculate penalty')}
          </Button>
        </div>
      </div>

      {/* Table */}
      <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
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
            view: 'manage-attendance-records',
            create: 'manage-attendance-records',
            edit: 'manage-attendance-records',
            delete: 'manage-attendance-records'
          }}
        />

        <Pagination
          from={logs?.from || 0}
          to={logs?.to || 0}
          total={logs?.total || 0}
          links={logs?.links}
          entityName={t('late penalty logs')}
          onPageChange={(url) => router.get(url)}
        />
      </div>
    </PageTemplate>
  );
}