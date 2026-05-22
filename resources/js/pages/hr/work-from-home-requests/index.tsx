import { useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { CrudTable } from '@/components/CrudTable';
import { CrudFormModal } from '@/components/CrudFormModal';
import { CrudDeleteModal } from '@/components/CrudDeleteModal';
import { SearchAndFilterBar } from '@/components/ui/search-and-filter-bar';
import { Pagination } from '@/components/ui/pagination';
import { useTranslation } from 'react-i18next';
import { toast } from '@/components/custom-toast';
import MediaPicker from '@/components/MediaPicker';
import { hasPermission } from '@/utils/authorization';
import { getImagePath } from '@/utils/helpers';

export default function WorkFromHomeRequests() {
  const { t } = useTranslation();
  const { auth, wfhRequests, employees, filters: pageFilters = {}, globalSettings } = usePage().props as any;
  const permissions = auth?.permissions || [];
  const canManageAnyWfh = hasPermission(permissions, 'manage-any-wfh-applications') || hasPermission(permissions, 'manage-wfh-applications');

  const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
  const [selectedEmployee, setSelectedEmployee] = useState(pageFilters.employee_id || 'all');
  const [selectedStatus, setSelectedStatus] = useState(pageFilters.status || 'all');
  const [showFilters, setShowFilters] = useState(false);
  const [isFormModalOpen, setIsFormModalOpen] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [currentItem, setCurrentItem] = useState<any>(null);
  const [formMode, setFormMode] = useState<'create' | 'edit' | 'view'>('create');

  const applyFilters = () => {
    router.get(route('hr.wfh-applications.index'), {
      page: 1,
      search: searchTerm || undefined,
      employee_id: selectedEmployee !== 'all' ? selectedEmployee : undefined,
      status: selectedStatus !== 'all' ? selectedStatus : undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  const handleFormSubmit = (formData: any) => {
    const isEdit = formMode === 'edit' && currentItem;
    if (!globalSettings?.is_demo) toast.loading(t(isEdit ? 'Updating WFH request...' : 'Submitting WFH request...'));

    const requestOptions = {
      onSuccess: (page: any) => {
        setIsFormModalOpen(false);
        if (!globalSettings?.is_demo) toast.dismiss();
        if (page?.props?.flash?.success) toast.success(t(page.props.flash.success));
        else if (page?.props?.flash?.error) toast.error(t(page.props.flash.error));
      },
      onError: (errors: any) => {
        if (!globalSettings?.is_demo) toast.dismiss();
        toast.error(typeof errors === 'string' ? errors : `${isEdit ? 'Failed to update' : 'Failed to submit'} WFH request: ${Object.values(errors).join(', ')}`);
      },
      onFinish: () => {
        if (!globalSettings?.is_demo) toast.dismiss();
      },
    };

    if (isEdit) {
      router.put(route('hr.wfh-applications.update', currentItem.id), formData, requestOptions);
    } else {
      router.post(route('hr.wfh-applications.store'), formData, requestOptions);
    }
  };

  const handleDeleteConfirm = () => {
    if (!currentItem) return;
    if (!globalSettings?.is_demo) toast.loading(t('Deleting WFH request...'));
    router.delete(route('hr.wfh-applications.destroy', currentItem.id), {
      onSuccess: (page: any) => {
        setIsDeleteModalOpen(false);
        if (!globalSettings?.is_demo) toast.dismiss();
        if (page.props.flash.success) toast.success(t(page.props.flash.success));
        else if (page.props.flash.error) toast.error(t(page.props.flash.error));
      },
      onError: (errors) => {
        if (!globalSettings?.is_demo) toast.dismiss();
        toast.error(typeof errors === 'string' ? errors : `Failed to delete WFH request: ${Object.values(errors).join(', ')}`);
      }
    });
  };

  const handleStatusUpdate = (item: any, status: 'approved' | 'rejected') => {
    if (!globalSettings?.is_demo) toast.loading(t(status === 'approved' ? 'Approving request...' : 'Rejecting request...'));
    router.put(route('hr.wfh-applications.update-status', item.id), { status, manager_comments: '' }, {
      onSuccess: (page: any) => {
        if (!globalSettings?.is_demo) toast.dismiss();
        if (page.props.flash.success) toast.success(t(page.props.flash.success));
        else if (page.props.flash.error) toast.error(t(page.props.flash.error));
      },
      onError: (errors) => {
        if (!globalSettings?.is_demo) toast.dismiss();
        toast.error(typeof errors === 'string' ? errors : `Failed to update WFH request: ${Object.values(errors).join(', ')}`);
      }
    });
  };

  const columns = [
    { key: 'employee', label: t('Employee'), render: (_: any, row: any) => row.employee?.name || '-' },
    { key: 'designation', label: t('Designation'), render: (value: string) => value || '-' },
    { key: 'department', label: t('Department'), render: (value: string) => value || '-' },
    { key: 'start_date', label: t('WFH Start Date'), sortable: true, render: (v: string) => window.appSettings?.formatDateTimeSimple(v, false) || new Date(v).toLocaleDateString() },
    { key: 'end_date', label: t('WFH End Date'), sortable: true, render: (v: string) => window.appSettings?.formatDateTimeSimple(v, false) || new Date(v).toLocaleDateString() },
    { key: 'status', label: t('Status'), render: (value: string) => {
      const statusColors = { pending: 'bg-yellow-50 text-yellow-700 ring-yellow-600/20', approved: 'bg-green-50 text-green-700 ring-green-600/20', rejected: 'bg-red-50 text-red-700 ring-red-600/20' };
      return <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${statusColors[value as keyof typeof statusColors]}`}>{value.charAt(0).toUpperCase() + value.slice(1)}</span>;
    } },
    { key: 'created_at', label: t('Applied On'), sortable: true, render: (v: string) => window.appSettings?.formatDateTimeSimple(v, false) || new Date(v).toLocaleDateString() },
  ];

  const actions = [
    { label: t('View'), icon: 'Eye', action: 'view', className: 'text-blue-500', requiredPermission: 'view-wfh-applications' },
    { label: t('Edit'), icon: 'Edit', action: 'edit', className: 'text-amber-500', requiredPermission: 'edit-wfh-applications', condition: (item: any) => item.status === 'pending' },
    { label: t('Approve'), icon: 'CheckCircle', action: 'approve', className: 'text-green-500', requiredPermission: 'approve-wfh-applications', condition: (item: any) => item.status === 'pending' },
    { label: t('Reject'), icon: 'XCircle', action: 'reject', className: 'text-red-500', requiredPermission: 'reject-wfh-applications', condition: (item: any) => item.status === 'pending' },
    {
      label: t('Delete'),
      icon: 'Trash2',
      action: 'delete',
      className: 'text-red-500',
      requiredPermission: 'delete-wfh-applications',
      condition: (item: any) => hasPermission(permissions, 'manage-any-wfh-applications') && ['pending', 'approved', 'rejected'].includes(item.status)
    },
  ];

  const handleAction = (action: string, item: any) => {
    setCurrentItem(item);
    if (action === 'view') {
      setFormMode('view');
      setIsFormModalOpen(true);
      return;
    }
    if (action === 'edit') {
      setFormMode('edit');
      setIsFormModalOpen(true);
      return;
    }
    if (action === 'delete') {
      setIsDeleteModalOpen(true);
      return;
    }
    if (action === 'approve') handleStatusUpdate(item, 'approved');
    if (action === 'reject') handleStatusUpdate(item, 'rejected');
  };

  const employeeOptions = [
    { value: 'all', label: t('All Employees'), disabled: true },
    ...(employees || []).map((emp: any) => ({ value: emp.id.toString(), label: emp.name }))
  ];

  const statusOptions = [
    { value: 'all', label: t('All Statuses'), disabled: true },
    { value: 'pending', label: t('Pending') },
    { value: 'approved', label: t('Approved') },
    { value: 'rejected', label: t('Rejected') }
  ];

  const selectedEmployeeMeta = (employees || []).find((emp: any) => String(emp.id) === String(currentItem?.employee_id));
  const currentUserEmployeeMeta = (employees || []).find((emp: any) => String(emp.id) === String(auth?.user?.id));

  const pageActions: any[] = [];
  if (hasPermission(permissions, 'create-wfh-applications')) {
    pageActions.push({
      label: t('Add WFH Request'),
      icon: <Plus className="h-4 w-4 mr-2" />,
      variant: 'default',
      onClick: () => { setCurrentItem(null); setFormMode('create'); setIsFormModalOpen(true); }
    });
  }

  return (
    <PageTemplate title={t('WFH (Work From Home) Requests')} description={t('Manage work-from-home requests')} url="/hr/wfh-applications" actions={pageActions} breadcrumbs={[{ title: t('Dashboard'), href: route('dashboard') }, { title: t('WFH (Work From Home) Requests') }]} noPadding>
      <div className="bg-white dark:bg-gray-900 rounded-lg shadow mb-4 p-4">
        <SearchAndFilterBar
          searchTerm={searchTerm}
          onSearchChange={setSearchTerm}
          onSearch={(e) => { e.preventDefault(); applyFilters(); }}
          filters={[
            { name: 'employee_id', label: t('Employee'), type: 'select', value: selectedEmployee, onChange: setSelectedEmployee, options: employeeOptions, searchable: true },
            { name: 'status', label: t('Status'), type: 'select', value: selectedStatus, onChange: setSelectedStatus, options: statusOptions }
          ]}
          showFilters={showFilters}
          setShowFilters={setShowFilters}
          hasActiveFilters={() => searchTerm !== '' || selectedEmployee !== 'all' || selectedStatus !== 'all'}
          activeFilterCount={() => (searchTerm ? 1 : 0) + (selectedEmployee !== 'all' ? 1 : 0) + (selectedStatus !== 'all' ? 1 : 0)}
          onResetFilters={() => {
            setSearchTerm('');
            setSelectedEmployee('all');
            setSelectedStatus('all');
            router.get(route('hr.wfh-applications.index'), { page: 1, per_page: pageFilters.per_page }, { preserveState: true, preserveScroll: true });
          }}
          onApplyFilters={applyFilters}
          currentPerPage={pageFilters.per_page?.toString() || '10'}
          onPerPageChange={(value) => {
            router.get(route('hr.wfh-applications.index'), { page: 1, per_page: parseInt(value), search: searchTerm || undefined, employee_id: selectedEmployee !== 'all' ? selectedEmployee : undefined, status: selectedStatus !== 'all' ? selectedStatus : undefined }, { preserveState: true, preserveScroll: true });
          }}
        />
      </div>

      <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
        <CrudTable columns={columns} actions={actions} data={wfhRequests?.data || []} from={wfhRequests?.from || 1} onAction={handleAction} sortField={pageFilters.sort_field} sortDirection={pageFilters.sort_direction} onSort={(field: string) => {
          const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';
          router.get(route('hr.wfh-applications.index'), { sort_field: field, sort_direction: direction, page: 1, search: searchTerm || undefined, employee_id: selectedEmployee !== 'all' ? selectedEmployee : undefined, status: selectedStatus !== 'all' ? selectedStatus : undefined, per_page: pageFilters.per_page }, { preserveState: true, preserveScroll: true });
        }} permissions={permissions} entityPermissions={{ view: 'view-wfh-applications', edit: 'edit-wfh-applications', delete: 'delete-wfh-applications' }} />

        <Pagination from={wfhRequests?.from || 0} to={wfhRequests?.to || 0} total={wfhRequests?.total || 0} links={wfhRequests?.links} entityName={t('wfh requests')} onPageChange={(url) => router.get(url)} />
      </div>

      <CrudFormModal
        isOpen={isFormModalOpen}
        onClose={() => setIsFormModalOpen(false)}
        onSubmit={handleFormSubmit}
        formConfig={{
          fields: [
            {
              name: 'employee_id',
              label: t('Employee name'),
              type: 'custom',
              required: true,
              render: (field: any, formData: any, handleChange: any) => (
                formMode === 'view' ? (
                  <div className="p-2 border rounded-md bg-gray-50">
                    {(employees || []).find((emp: any) => String(emp.id) === String(formData[field.name]))?.name || '-'}
                  </div>
                ) : (
                <select
                  id={field.name}
                  name={field.name}
                  className="w-full border rounded-md px-3 py-2 bg-background"
                  value={String(formData[field.name] || '')}
                  onChange={(e) => {
                    const employeeId = e.target.value;
                    const employee = (employees || []).find((emp: any) => String(emp.id) === String(employeeId));
                    handleChange(field.name, employeeId);
                    handleChange('designation', employee?.designation || '');
                    handleChange('department', employee?.department || '');
                  }}
                  disabled={!canManageAnyWfh}
                >
                  <option value="" disabled>{t('Select employee')}</option>
                  {(employees || []).map((emp: any) => (
                    <option key={emp.id} value={String(emp.id)}>
                      {emp.name}
                    </option>
                  ))}
                </select>
                )
              )
            },
            { name: 'start_date', label: t('WFH start date'), type: 'date', required: true },
            { name: 'end_date', label: t('WFH end date'), type: 'date', required: true },
            { name: 'reason', label: t('Reason'), type: 'textarea', required: true },
            {
              name: 'attachment',
              label: t('Attached file'),
              type: 'custom',
              render: (field: any, formData: any, handleChange: any) => (
                <div className="space-y-2">
                  {formData[field.name] ? (
                    <button
                      type="button"
                      className="text-primary underline text-sm"
                      onClick={() => window.open(String(formData.attachment_url || getImagePath(String(formData[field.name]))), '_blank')}
                    >
                      {t('View Attached File')}
                    </button>
                  ) : (
                    formMode === 'view' ? <div className="p-2 border rounded-md bg-gray-50">-</div> : null
                  )}
                  {formMode !== 'view' && (
                    <MediaPicker value={String(formData[field.name] || '')} onChange={(url) => handleChange(field.name, url)} placeholder={t('Select attachment file...')} />
                  )}
                </div>
              )
            },
            {
              name: 'designation',
              label: t("Employee's designation"),
              type: 'custom',
              required: false,
              render: (field: any, formData: any, handleChange: any) => (
                formMode === 'view' ? (
                  <div className="p-2 border rounded-md bg-gray-50">{formData[field.name] || '-'}</div>
                ) : (
                  <input
                    id={field.name}
                    name={field.name}
                    type="text"
                    className="w-full border rounded-md px-3 py-2 bg-background"
                    value={String(formData[field.name] || '')}
                    onChange={(e) => handleChange(field.name, e.target.value)}
                    disabled={!canManageAnyWfh}
                  />
                )
              )
            },
            {
              name: 'department',
              label: t("Employee's department"),
              type: 'custom',
              required: false,
              render: (field: any, formData: any, handleChange: any) => (
                formMode === 'view' ? (
                  <div className="p-2 border rounded-md bg-gray-50">{formData[field.name] || '-'}</div>
                ) : (
                  <input
                    id={field.name}
                    name={field.name}
                    type="text"
                    className="w-full border rounded-md px-3 py-2 bg-background"
                    value={String(formData[field.name] || '')}
                    onChange={(e) => handleChange(field.name, e.target.value)}
                    disabled={!canManageAnyWfh}
                  />
                )
              )
            },
          ],
          modalSize: 'lg'
        }}
        initialData={currentItem ? {
          ...currentItem,
          start_date: currentItem.start_date ? String(currentItem.start_date).slice(0, 10) : '',
          end_date: currentItem.end_date ? String(currentItem.end_date).slice(0, 10) : '',
        } : {
          employee_id: canManageAnyWfh ? '' : String(auth?.user?.id || ''),
          designation: currentUserEmployeeMeta?.designation || '',
          department: currentUserEmployeeMeta?.department || '',
        }}
        title={formMode === 'view' ? t('View WFH Request') : (formMode === 'edit' ? t('Edit WFH Request') : t('Add New WFH Request'))}
        mode={formMode}
      />

      <CrudDeleteModal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        onConfirm={handleDeleteConfirm}
        itemName={`${currentItem?.employee?.name || ''} (${currentItem?.start_date || ''} - ${currentItem?.end_date || ''})`}
        entityName="wfh request"
      />
    </PageTemplate>
  );
}
