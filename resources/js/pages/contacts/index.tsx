import { useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { hasPermission } from '@/utils/authorization';
import { CrudTable } from '@/components/CrudTable';
import { CrudDeleteModal } from '@/components/CrudDeleteModal';
import { CrudFormModal } from '@/components/CrudFormModal';
import { toast } from '@/components/custom-toast';
import { useTranslation } from 'react-i18next';
import { Pagination } from '@/components/ui/pagination';
import { SearchAndFilterBar } from '@/components/ui/search-and-filter-bar';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';

export default function Contacts() {
  const { t } = useTranslation();
  const { auth, contacts, filters: pageFilters = {}, globalSettings } = usePage().props as any;
  const permissions = auth?.permissions || [];

  // State
  const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
  const [showFilters, setShowFilters] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [isViewModalOpen, setIsViewModalOpen] = useState(false);
  const [isStatusModalOpen, setIsStatusModalOpen] = useState(false);
  const [isReplyModalOpen, setIsReplyModalOpen] = useState(false);
  const [currentItem, setCurrentItem] = useState<any>(null);

  // Check if any filters are active
  const hasActiveFilters = () => {
    return searchTerm !== '';
  };

  // Count active filters
  const activeFilterCount = () => {
    return (searchTerm !== '' ? 1 : 0);
  };

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    applyFilters();
  };

  const applyFilters = () => {
    router.get(route('contacts.index'), {
      page: 1,
      search: searchTerm || undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  const handleSort = (field: string) => {
    const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';

    router.get(route('contacts.index'), {
      sort_field: field,
      sort_direction: direction,
      page: 1,
      search: searchTerm || undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  const handleAction = (action: string, item: any) => {
    setCurrentItem(item);

    switch (action) {
      case 'view':
        setIsViewModalOpen(true);
        break;
      case 'update-status':
        setIsStatusModalOpen(true);
        break;
      case 'send-reply':
        setIsReplyModalOpen(true);
        break;
      case 'delete':
        setIsDeleteModalOpen(true);
        break;
    }
  };

  const handleStatusUpdate = (data: any) => {
    toast.loading(t('Updating contact status...'));

    router.put(route('contacts.update-status', currentItem.id), { status: data.status }, {
      onSuccess: (page) => {
        setIsStatusModalOpen(false);
        toast.dismiss();
        if (page.props.flash.success) {
          toast.success(t(page.props.flash.success));
        } else if (page.props.flash.error) {
          toast.error(t(page.props.flash.error));
        }
      },
      onError: (errors) => {
        toast.dismiss();
        if (typeof errors === 'string') {
          toast.error(t(errors));
        } else {
          toast.error(t('Failed to update contact status: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };

  const handleDeleteConfirm = () => {
    if (!globalSettings?.is_demo) {
      toast.loading(t('Deleting contact...'));
    }

    router.delete(route('contacts.destroy', currentItem.id), {
      onSuccess: (page) => {
        setIsDeleteModalOpen(false);
        if (!globalSettings?.is_demo) {
          toast.dismiss();
        }
        if (page.props.flash.success) {
          toast.success(t(page.props.flash.success));
        } else if (page.props.flash.error) {
          toast.error(t(page.props.flash.error));
        }
      },
      onError: (errors) => {
        if (!globalSettings?.is_demo) {
          toast.dismiss();
        }
        if (typeof errors === 'string') {
          toast.error(t(errors));
        } else {
          toast.error(t('Failed to delete contact: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };

  const handleReplySubmit = (data: any) => {
    if (!globalSettings?.is_demo) {
      toast.loading(t('Sending reply...'));
    }
    router.post(route('contacts.send-reply', currentItem.id), {
      subject: data.subject,
      message: data.message
    }, {
      onSuccess: (page) => {
        setIsReplyModalOpen(false);
        if (!globalSettings?.is_demo) {
          toast.dismiss();
        }
        if (page.props.flash.success) {
          toast.success(t(page.props.flash.success));
        } else if (page.props.flash.error) {
          toast.error(t(page.props.flash.error));
        }
      },
      onError: (errors) => {
        if (!globalSettings?.is_demo) {
          toast.dismiss();
        }
        if (typeof errors === 'string') {
          toast.error(t(errors));
        } else {
          toast.error(t('Failed to send reply: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };

  const handleResetFilters = () => {
    setSearchTerm('');
    setShowFilters(false);

    router.get(route('contacts.index'), {
      page: 1,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Landing Page'), href: route('landing-page') },
    { title: t('Contact Inquiries') }
  ];

  // Define table columns
  const columns = [
    {
      key: 'name',
      label: t('Name'),
      sortable: true,
      render: (value) => value || '-'
    },
    {
      key: 'email',
      label: t('Email'),
      sortable: true,
      render: (value) => value || '-'
    },
    {
      key: 'subject',
      label: t('Subject'),
      sortable: true,
      render: (value) => value || '-'
    },
    {
      key: 'status',
      label: t('Status'),
      render: (value) => {
        const statusColors = {
          'New': 'bg-blue-100 text-blue-800',
          'Contacted': 'bg-yellow-100 text-yellow-800',
          'Qualified': 'bg-green-100 text-green-800',
          'Converted': 'bg-purple-100 text-purple-800',
          'Closed': 'bg-gray-100 text-gray-800'
        };
        return (
          <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${statusColors[value] || 'bg-gray-100 text-gray-800'}`}>
            {value || 'New'}
          </span>
        );
      }
    },
    {
      key: 'created_at',
      label: t('Date'),
      sortable: true,
      render: (value) => window.appSettings?.formatDateTimeSimple(value, true) || new Date(value).toLocaleString()
    }
  ];

  // Define table actions
  const actions = [
    {
      label: t('View'),
      icon: 'Eye',
      action: 'view',
      className: 'text-blue-500',
      requiredPermission: 'view-contacts'
    },
    {
      label: t('Update Status'),
      icon: 'RefreshCw',
      action: 'update-status',
      className: 'text-green-500',
      requiredPermission: 'update-contact-status'
    },
    {
      label: t('Send Reply'),
      icon: 'Reply',
      action: 'send-reply',
      className: 'text-purple-500',
      requiredPermission: 'send-reply-contacts'
    },
    {
      label: t('Delete'),
      icon: 'Trash2',
      action: 'delete',
      className: 'text-red-500',
      requiredPermission: 'delete-contacts'
    }
  ];

  return (
    <PageTemplate
      title={t("Contact Inquiries")}
      url="/contacts"
      breadcrumbs={breadcrumbs}
      noPadding
    >
      {/* Search and filters section */}
      <div className="bg-white dark:bg-gray-900 rounded-lg shadow mb-4 p-4">
        <SearchAndFilterBar
          searchTerm={searchTerm}
          onSearchChange={setSearchTerm}
          onSearch={handleSearch}
          filters={[]}
          showFilters={showFilters}
          setShowFilters={setShowFilters}
          hasActiveFilters={hasActiveFilters}
          activeFilterCount={activeFilterCount}
          onResetFilters={handleResetFilters}
          onApplyFilters={applyFilters}
          currentPerPage={pageFilters.per_page?.toString() || "10"}
          onPerPageChange={(value) => {
            router.get(route('contacts.index'), {
              page: 1,
              per_page: parseInt(value),
              search: searchTerm || undefined
            }, { preserveState: true, preserveScroll: true });
          }}
        />
      </div>

      {/* Content section */}
      <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
        <CrudTable
          columns={columns}
          actions={actions}
          data={contacts?.data || []}
          from={contacts?.from || 1}
          onAction={handleAction}
          sortField={pageFilters.sort_field}
          sortDirection={pageFilters.sort_direction}
          onSort={handleSort}
          permissions={permissions}
          entityPermissions={{
            view: 'view-contacts',
            delete: 'delete-contacts'
          }}
        />

        {/* Pagination section */}
        <Pagination
          from={contacts?.from || 0}
          to={contacts?.to || 0}
          total={contacts?.total || 0}
          links={contacts?.links}
          entityName={t("contacts")}
          onPageChange={(url) => router.get(url)}
        />
      </div>

      {/* Delete Modal */}
      <CrudDeleteModal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        onConfirm={handleDeleteConfirm}
        itemName={`${currentItem?.name || ''} - ${currentItem?.subject || ''}`}
        itemType={t('contact')}
      />

      {/* View Modal */}
      <Dialog open={isViewModalOpen} onOpenChange={setIsViewModalOpen}>
        <DialogContent className="max-w-2xl">
          <DialogHeader>
            <DialogTitle>{t('Contact Details')}</DialogTitle>
          </DialogHeader>
          <div className="space-y-6">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">{t('Name')}</label>
                <p className="text-gray-900 dark:text-gray-100">{currentItem?.name || '-'}</p>
              </div>
              <div className="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">{t('Email')}</label>
                <p className="text-gray-900 dark:text-gray-100">{currentItem?.email || '-'}</p>
              </div>
            </div>
            <div className="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
              <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">{t('Subject')}</label>
              <p className="text-gray-900 dark:text-gray-100">{currentItem?.subject || '-'}</p>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">{t('Status')}</label>
                <div>
                  {(() => {
                    const status = currentItem?.status || 'New';
                    const statusColors = {
                      'New': 'bg-blue-100 text-blue-800',
                      'Contacted': 'bg-yellow-100 text-yellow-800',
                      'Qualified': 'bg-green-100 text-green-800',
                      'Converted': 'bg-purple-100 text-purple-800',
                      'Closed': 'bg-gray-100 text-gray-800'
                    };
                    return (
                      <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${statusColors[status] || 'bg-gray-100 text-gray-800'}`}>
                        {status}
                      </span>
                    );
                  })()}
                </div>
              </div>
              <div className="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">{t('Date')}</label>
                <p className="text-gray-900 dark:text-gray-100">{currentItem?.created_at ? (window.appSettings?.formatDateTimeSimple(currentItem.created_at, true) || new Date(currentItem.created_at).toLocaleString()) : '-'}</p>
              </div>
            </div>
            <div className="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
              <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">{t('Message')}</label>
              <p className="text-gray-900 dark:text-gray-100 whitespace-pre-wrap">{currentItem?.message || '-'}</p>
            </div>
          </div>
        </DialogContent>
      </Dialog>

      {/* Status Update Modal */}
      <CrudFormModal
        isOpen={isStatusModalOpen}
        onClose={() => setIsStatusModalOpen(false)}
        onSubmit={handleStatusUpdate}
        title={t('Update Contact Status')}
        mode="edit"
        formConfig={{
          fields: [
            {
              name: 'status',
              label: t('Status'),
              type: 'select',
              required: true,
              options: [
                { value: 'New', label: t('New') },
                { value: 'Contacted', label: t('Contacted') },
                { value: 'Qualified', label: t('Qualified') },
                { value: 'Converted', label: t('Converted') },
                { value: 'Closed', label: t('Closed') }
              ]
            }
          ]
        }}
        initialData={{ status: currentItem?.status || 'New' }}
      />

      {/* Reply Modal */}
      <CrudFormModal
        isOpen={isReplyModalOpen}
        onClose={() => setIsReplyModalOpen(false)}
        onSubmit={handleReplySubmit}
        title={t('Send Reply')}
        mode="create"
        submitButtonText={t('Send')}
        formConfig={{
          fields: [
            {
              name: 'email',
              label: t('Email'),
              type: 'text',
              defaultValue: currentItem?.email || '',
              disabled: true
            },
            {
              name: 'subject',
              label: t('Subject'),
              type: 'text',
              required: true,
              placeholder: t('Enter reply subject')
            },
            {
              name: 'message',
              label: t('Message'),
              type: 'textarea',
              required: true,
              placeholder: t('Enter your reply message')
            }
          ],
          modalSize: '2xl'
        }}
        initialData={{ email: currentItem?.email || '' }}
      />
    </PageTemplate>
  );
}