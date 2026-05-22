import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Download } from 'lucide-react';
import { toast } from '@/components/custom-toast';
import { useTranslation } from 'react-i18next';
import { ColumnMappingModal } from './ColumnMappingModal';

interface ImportModalProps {
  isOpen: boolean;
  onClose: () => void;
  title: string;
  importRoute: string;
  parseRoute: string;
  sampleRoute?: string;
  importNotes: string;
  databaseFields: { key: string; required?: boolean }[];
  modalSize?: 'sm' | 'md' | 'lg' | 'xl';
}

export function ImportModal({
  isOpen,
  onClose,
  title,
  importRoute,
  parseRoute,
  sampleRoute,
  importNotes,
  databaseFields,
  modalSize = 'lg'
}: ImportModalProps) {
  const { t } = useTranslation();
  const { globalSettings } = usePage().props as any;
  const [file, setFile] = useState<File | null>(null);
  const [isImporting, setIsImporting] = useState(false);
  const [showMappingModal, setShowMappingModal] = useState(false);
  const [excelColumns, setExcelColumns] = useState<string[]>([]);
  const [parsedData, setParsedData] = useState<Record<string, string>[]>([]);
  const [previewData, setPreviewData] = useState<Record<string, string>[]>([]);


  const handleDownloadSample = async () => {
    if (!sampleRoute) return;
    
    try {
      const response = await fetch(route(sampleRoute), {
        method: 'GET',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
        },
      });

      if (!response.ok) {
        const data = await response.json().catch(() => ({}));
        toast.error(t(data.error || 'Failed to download template'));
        return;
      }

      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      
      // Extract filename from Content-Disposition header or use default
      const contentDisposition = response.headers.get('Content-Disposition');
      let filename = 'sample-template.xlsx';
      if (contentDisposition) {
        const filenameMatch = contentDisposition.match(/filename="?(.+?)"?$/i);
        if (filenameMatch) {
          filename = filenameMatch[1];
        }
      }
      
      a.download = filename;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      document.body.removeChild(a);
    } catch (error) {
      toast.error(t('Failed to download template'));
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!file) {
      toast.error(t('Please select a file to import'));
      return;
    }

    const formData = new FormData();
    formData.append('file', file);

    setIsImporting(true);
    if (!globalSettings?.is_demo) {
      toast.loading(t('Parsing file...'));
    }

    try {
      const response = await fetch(route(parseRoute), {
        method: 'POST',
        body: formData,
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        }
      });

      const data = await response.json();

      if (data.excelColumns && data.previewData) {
        setExcelColumns(data.excelColumns);
        setParsedData(data.previewData);
        setPreviewData(data.previewData || []);
        if (!globalSettings?.is_demo) {
          toast.dismiss();
        }
        onClose();
        setShowMappingModal(true);
      } else {
        if (!globalSettings?.is_demo) {
          toast.dismiss();
        }
        if (data.message) {
          toast.error(t(data.message));
        } else {
          toast.error(t('Failed to parse file'));
        }
      }
    } catch (error: any) {
      if (!globalSettings?.is_demo) {
        toast.dismiss();
      }
      if (error?.message) {
        toast.error(t(error.message));
      } else {
        toast.error(t('Network error or invalid response'));
      }
    } finally {
      setIsImporting(false);
    }
  };

  const handleMappingClose = () => {
    setShowMappingModal(false);
    setFile(null);
    setExcelColumns([]);
    setParsedData([]);
    setPreviewData([]);
  };

  const handleClose = () => {
    if (!isImporting && !showMappingModal) {
      setFile(null);
      onClose();
    }
  };

  const modalSizeClass = {
    sm: 'sm:max-w-sm',
    md: 'sm:max-w-md',
    lg: 'sm:max-w-lg',
    xl: 'sm:max-w-xl'
  }[modalSize];

  return (
    <>
      <Dialog open={isOpen} onOpenChange={handleClose}>
        <DialogContent className={modalSizeClass}>
          <DialogHeader>
            <DialogTitle>{title}</DialogTitle>
          </DialogHeader>
          <div className="space-y-4">
            {sampleRoute && (
              <div className="flex items-center justify-between p-3 border border-gray-200 rounded-md">
                <p className="text-sm text-gray-700">{t('Download sample template for required format')}</p>
                <Button
                  type="button"
                  variant="ghost"
                  size="sm"
                  onClick={handleDownloadSample}
                  className="ml-3 text-blue-600 hover:text-blue-800"
                >
                  <Download className="h-4 w-4" />
                </Button>
              </div>
            )}

            <div className="space-y-2">
              <Label htmlFor="file">{t('Select File')} <span className="text-red-500">*</span></Label>
              <Input
                id="file"
                type="file"
                accept=".xlsx,.xls,.csv"
                onChange={(e) => setFile(e.target.files?.[0] || null)}
                disabled={isImporting}
                required
              />
            </div>

            <div className="bg-blue-50 border border-blue-200 rounded-md p-3">
              <h4 className="text-sm font-medium text-blue-800 mb-2">{t('Import Notes:')}</h4>
              <p className="text-xs text-blue-700">{importNotes}</p>
            </div>
          </div>
          <DialogFooter>
            <Button type="button" variant="outline" onClick={handleClose} disabled={isImporting}>
              {t('Cancel')}
            </Button>
            <Button type="button" onClick={handleSubmit} disabled={isImporting}>
              {t('Import')}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
      <ColumnMappingModal
        isOpen={showMappingModal}
        onClose={handleMappingClose}
        excelColumns={excelColumns}
        databaseFields={databaseFields}
        importRoute={importRoute}
        data={parsedData}
        previewData={previewData}
      />
    </>
  );
}
