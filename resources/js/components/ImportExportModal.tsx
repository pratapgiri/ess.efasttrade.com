import { useState } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Upload, Download, X } from 'lucide-react';
import { useTranslation } from 'react-i18next';

interface ImportExportModalProps {
  isOpen: boolean;
  onClose: () => void;
  onExport: () => void;
  onImport: (file: File) => void;
}

export function ImportExportModal({ isOpen, onClose, onExport, onImport }: ImportExportModalProps) {
  const { t } = useTranslation();
  const [selectedFile, setSelectedFile] = useState<File | null>(null);
  const [dragActive, setDragActive] = useState(false);

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files && e.target.files[0]) {
      setSelectedFile(e.target.files[0]);
    }
  };

  const handleDrag = (e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    if (e.type === "dragenter" || e.type === "dragover") {
      setDragActive(true);
    } else if (e.type === "dragleave") {
      setDragActive(false);
    }
  };

  const handleDrop = (e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    setDragActive(false);
    if (e.dataTransfer.files && e.dataTransfer.files[0]) {
      setSelectedFile(e.dataTransfer.files[0]);
    }
  };

  const handleImportClick = () => {
    if (selectedFile) {
      onImport(selectedFile);
      setSelectedFile(null);
    }
  };

  const handleClose = () => {
    setSelectedFile(null);
    onClose();
  };

  return (
    <Dialog open={isOpen} onOpenChange={handleClose}>
      <DialogContent className="sm:max-w-[500px]">
        <DialogHeader>
          <DialogTitle>{t('Import / Export Employees')}</DialogTitle>
        </DialogHeader>
        
        <div className="space-y-6 py-4">
          {/* Export Section */}
          <div className="space-y-3">
            <h3 className="text-sm font-medium">{t('Export')}</h3>
            <p className="text-sm text-muted-foreground">{t('Download employee data as CSV file')}</p>
            <Button onClick={onExport} className="w-full" variant="outline">
              <Download className="h-4 w-4 mr-2" />
              {t('Export to CSV')}
            </Button>
          </div>

          <div className="relative">
            <div className="absolute inset-0 flex items-center">
              <span className="w-full border-t" />
            </div>
            <div className="relative flex justify-center text-xs uppercase">
              <span className="bg-background px-2 text-muted-foreground">{t('Or')}</span>
            </div>
          </div>

          {/* Import Section */}
          <div className="space-y-3">
            <h3 className="text-sm font-medium">{t('Import')}</h3>
            <p className="text-sm text-muted-foreground">{t('Upload CSV or Excel file to import employees')}</p>
            
            <div
              className={`border-2 border-dashed rounded-lg p-6 text-center cursor-pointer transition-colors ${
                dragActive ? 'border-primary bg-primary/5' : 'border-gray-300 hover:border-gray-400'
              }`}
              onDragEnter={handleDrag}
              onDragLeave={handleDrag}
              onDragOver={handleDrag}
              onDrop={handleDrop}
              onClick={() => document.getElementById('file-upload')?.click()}
            >
              <Upload className="h-10 w-10 mx-auto mb-3 text-gray-400" />
              <p className="text-sm text-gray-600 mb-1">
                {selectedFile ? selectedFile.name : t('Click to upload or drag and drop')}
              </p>
              <p className="text-xs text-gray-500">{t('CSV or Excel files only')}</p>
              <input
                id="file-upload"
                type="file"
                className="hidden"
                accept=".csv,.xlsx,.xls"
                onChange={handleFileChange}
              />
            </div>

            {selectedFile && (
              <div className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <span className="text-sm truncate">{selectedFile.name}</span>
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={(e) => {
                    e.stopPropagation();
                    setSelectedFile(null);
                  }}
                >
                  <X className="h-4 w-4" />
                </Button>
              </div>
            )}

            <Button 
              onClick={handleImportClick} 
              className="w-full" 
              disabled={!selectedFile}
            >
              <Upload className="h-4 w-4 mr-2" />
              {t('Import File')}
            </Button>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
}
