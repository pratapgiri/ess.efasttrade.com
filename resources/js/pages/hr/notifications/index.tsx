import { useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { router, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Pagination } from '@/components/ui/pagination';
import { SearchAndFilterBar } from '@/components/ui/search-and-filter-bar';
import { Trash2 } from 'lucide-react';

export default function HrNotificationsIndex() {
  const { t } = useTranslation();
  const { notifications, filters = {}, eventOptions = [] } = usePage().props as any;

  const [search, setSearch] = useState(filters.search || '');
  const [eventKey, setEventKey] = useState(filters.event_key || 'all');
  const [status, setStatus] = useState(filters.status || 'all');
  const [selectedIds, setSelectedIds] = useState<number[]>([]);

  const rows = notifications?.data || [];
  const allSelectedOnPage = rows.length > 0 && rows.every((item: any) => selectedIds.includes(item.id));

  const toggleSelectAllOnPage = () => {
    if (allSelectedOnPage) {
      setSelectedIds((prev) => prev.filter((id) => !rows.some((row: any) => row.id === id)));
      return;
    }

    const pageIds = rows.map((item: any) => item.id);
    setSelectedIds((prev) => Array.from(new Set([...prev, ...pageIds])));
  };

  const toggleRowSelection = (id: number) => {
    setSelectedIds((prev) => (prev.includes(id) ? prev.filter((itemId) => itemId !== id) : [...prev, id]));
  };

  const applyFilters = () => {
    router.get(
      route('hr.notifications.index'),
      {
        page: 1,
        search: search || undefined,
        event_key: eventKey !== 'all' ? eventKey : undefined,
        status: status !== 'all' ? status : undefined,
        per_page: filters.per_page,
      },
      { preserveState: true, preserveScroll: true }
    );
  };

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('HR Notifications') },
  ];

  const deleteSingle = (id: number) => {
    if (!window.confirm(t('Delete this notification?'))) {
      return;
    }

    router.delete(route('hr.notifications.destroy', id), {
      preserveScroll: true,
      onSuccess: () => {
        setSelectedIds((prev) => prev.filter((itemId) => itemId !== id));
      },
    });
  };

  const deleteSelected = () => {
    if (selectedIds.length === 0) {
      return;
    }

    if (!window.confirm(t('Delete selected notifications?'))) {
      return;
    }

    router.delete(route('hr.notifications.bulk-destroy'), {
      preserveScroll: true,
      data: { ids: selectedIds },
      onSuccess: () => setSelectedIds([]),
    });
  };

  return (
    <PageTemplate title={t('HR Notifications')} description={t('Track and manage HR email notifications')} url="/hr/notifications" breadcrumbs={breadcrumbs} noPadding>
      <div className="bg-white dark:bg-gray-900 rounded-lg shadow mb-4 p-4">
        <SearchAndFilterBar
          searchTerm={search}
          onSearchChange={setSearch}
          onSearch={(e) => {
            e.preventDefault();
            applyFilters();
          }}
          filters={[
            {
              name: 'event_key',
              label: t('Event'),
              type: 'select',
              value: eventKey,
              onChange: setEventKey,
              options: [{ value: 'all', label: t('All Events') }, ...eventOptions.map((key: string) => ({ value: key, label: key }))],
            },
            {
              name: 'status',
              label: t('Status'),
              type: 'select',
              value: status,
              onChange: setStatus,
              options: [
                { value: 'all', label: t('All Status') },
                { value: 'sent', label: t('Sent') },
                { value: 'failed', label: t('Failed') },
                { value: 'skipped', label: t('Skipped') },
              ],
            },
          ]}
          showFilters={true}
          setShowFilters={() => {}}
          hasActiveFilters={() => !!search || eventKey !== 'all' || status !== 'all'}
          activeFilterCount={() => (search ? 1 : 0) + (eventKey !== 'all' ? 1 : 0) + (status !== 'all' ? 1 : 0)}
          onResetFilters={() => {
            setSearch('');
            setEventKey('all');
            setStatus('all');
            router.get(route('hr.notifications.index'), { page: 1, per_page: filters.per_page }, { preserveState: true, preserveScroll: true });
          }}
          onApplyFilters={applyFilters}
          currentPerPage={filters.per_page?.toString() || '10'}
          onPerPageChange={(value) =>
            router.get(
              route('hr.notifications.index'),
              {
                page: 1,
                per_page: parseInt(value, 10),
                search: search || undefined,
                event_key: eventKey !== 'all' ? eventKey : undefined,
                status: status !== 'all' ? status : undefined,
              },
              { preserveState: true, preserveScroll: true }
            )
          }
        />
      </div>

      <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
        <div className="flex items-center justify-between border-b px-4 py-3 dark:border-gray-700">
          <div className="text-sm text-muted-foreground">
            {selectedIds.length > 0 ? t('{{count}} selected', { count: selectedIds.length }) : t('Select notifications to delete')}
          </div>
          <button
            type="button"
            onClick={deleteSelected}
            disabled={selectedIds.length === 0}
            className="inline-flex items-center gap-2 rounded-md border border-red-200 px-3 py-1.5 text-sm text-red-600 disabled:cursor-not-allowed disabled:opacity-50 dark:border-red-900/40"
          >
            <Trash2 className="h-4 w-4" />
            {t('Delete Selected')}
          </button>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b bg-gray-50 dark:bg-gray-800 dark:border-gray-700">
                <th className="px-4 py-3 text-left font-medium text-gray-500">
                  <input
                    type="checkbox"
                    checked={allSelectedOnPage}
                    onChange={toggleSelectAllOnPage}
                    aria-label={t('Select all')}
                  />
                </th>
                <th className="px-4 py-3 text-left font-medium text-gray-500">{t('Event')}</th>
                <th className="px-4 py-3 text-left font-medium text-gray-500">{t('Title')}</th>
                <th className="px-4 py-3 text-left font-medium text-gray-500">{t('Recipient')}</th>
                <th className="px-4 py-3 text-left font-medium text-gray-500">{t('Status')}</th>
                <th className="px-4 py-3 text-left font-medium text-gray-500">{t('Created At')}</th>
                <th className="px-4 py-3 text-left font-medium text-gray-500">{t('Action')}</th>
              </tr>
            </thead>
            <tbody>
              {rows.map((item: any) => (
                <tr key={item.id} className="border-b hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800">
                  <td className="px-4 py-3">
                    <input
                      type="checkbox"
                      checked={selectedIds.includes(item.id)}
                      onChange={() => toggleRowSelection(item.id)}
                      aria-label={t('Select notification')}
                    />
                  </td>
                  <td className="px-4 py-3">{item.event_key}</td>
                  <td className="px-4 py-3">
                    <div className="font-medium">{item.title}</div>
                    <div className="text-xs text-muted-foreground line-clamp-1">{item.message}</div>
                  </td>
                  <td className="px-4 py-3">{item.recipient_email || '-'}</td>
                  <td className="px-4 py-3 capitalize">{item.status}</td>
                  <td className="px-4 py-3">{window.appSettings?.formatDateTimeSimple(item.created_at, true) || item.created_at}</td>
                  <td className="px-4 py-3">
                    <button
                      type="button"
                      onClick={() => deleteSingle(item.id)}
                      className="inline-flex items-center text-red-600 hover:text-red-700"
                    >
                      <Trash2 className="h-4 w-4" />
                    </button>
                  </td>
                </tr>
              ))}
              {rows.length === 0 && (
                <tr>
                  <td colSpan={7} className="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                    {t('No notifications found')}
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>

        <Pagination
          from={notifications?.from || 0}
          to={notifications?.to || 0}
          total={notifications?.total || 0}
          links={notifications?.links}
          entityName={t('notifications')}
          onPageChange={(url) => router.get(url)}
        />
      </div>
    </PageTemplate>
  );
}
