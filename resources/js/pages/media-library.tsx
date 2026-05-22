import React, { useState, useEffect, useCallback, useRef } from 'react';
import { PageTemplate } from '@/components/page-template';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { toast } from 'sonner';
import { useTranslation } from 'react-i18next';
import { usePage } from '@inertiajs/react';
import { Upload, Search, X, Plus, Info, Copy, Download, MoreHorizontal, Image as ImageIcon, Calendar, HardDrive, BarChart3, Home, Star, Clock, Trash2, Folder, FolderOpen, Grid3X3, List, Settings } from 'lucide-react';
import { hasPermission } from '@/utils/authorization';

interface MediaItem {
  id: number;
  name: string;
  file_name: string;
  url: string;
  thumb_url: string;
  size: number;
  mime_type: string;
  created_at: string;
}

export default function MediaLibraryDemo() {
  const { t } = useTranslation();
  const { csrf_token, auth } = usePage().props as any;
  const permissions = auth?.permissions || [];
  const [media, setMedia] = useState<MediaItem[]>([]);
  const [allMedia, setAllMedia] = useState<MediaItem[]>([]); // For directory counting
  const [directories, setDirectories] = useState<any[]>([]);
  const [currentDirectory, setCurrentDirectory] = useState<number | null>(null);
  const [filteredMedia, setFilteredMedia] = useState<MediaItem[]>([]);
  const [loading, setLoading] = useState(false);
  const [searchTerm, setSearchTerm] = useState('');
  const [currentPage, setCurrentPage] = useState(1);
  const [isUploadModalOpen, setIsUploadModalOpen] = useState(false);
  const [uploading, setUploading] = useState(false);
  const [dragActive, setDragActive] = useState(false);
  const [showCreateDirectory, setShowCreateDirectory] = useState(false);
  const [newDirectoryName, setNewDirectoryName] = useState('');
  const [viewMode, setViewMode] = useState<'grid' | 'list'>('grid');
  const [sortBy, setSortBy] = useState<'name' | 'date' | 'size' | 'type'>('date');
  const [sortOrder, setSortOrder] = useState<'asc' | 'desc'>('desc');

  const [infoModalOpen, setInfoModalOpen] = useState(false);
  const [selectedMediaInfo, setSelectedMediaInfo] = useState<MediaItem | null>(null);
  const itemsPerPage = 12;

  const createDirectory = async () => {
    if (!newDirectoryName.trim()) return;

    try {
      const response = await fetch(route('api.media.directories.create'), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf_token,
        },
        body: JSON.stringify({ name: newDirectoryName }),
      });

      const result = await response.json();

      if (response.ok) {
        toast.success(result.message || t('Directory created successfully'));
        setNewDirectoryName('');
        setShowCreateDirectory(false);
        fetchMedia();
      } else {
        if (result.errors && Array.isArray(result.errors)) {
          result.errors.forEach((error: string) => toast.error(error));
        } else {
          toast.error(result.error || result.message || t('Failed to create directory'));
        }
      }
    } catch (error) {
      toast.error(t('Network error: Failed to create directory'));
    }
  };

  const fetchMedia = useCallback(async () => {
    setLoading(true);
    try {
      // Always fetch all media first for directory counting
      const allMediaResponse = await fetch(route('api.media.index'), {
        credentials: 'same-origin',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      });
      
      if (!allMediaResponse.ok) {
        throw new Error(`HTTP error! status: ${allMediaResponse.status}`);
      }
      
      const allMediaData = await allMediaResponse.json();
      const allMediaArray = Array.isArray(allMediaData.media) ? allMediaData.media : [];
      
      setAllMedia(allMediaArray);
      setDirectories(allMediaData.directories || []);
      
      // Now fetch filtered media if in a specific directory
      if (currentDirectory) {
        const params = new URLSearchParams();
        params.append('directory_id', currentDirectory.toString());
        
        const filteredResponse = await fetch(`${route('api.media.index')}?${params}`, {
          credentials: 'same-origin',
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
        });
        
        if (filteredResponse.ok) {
          const filteredData = await filteredResponse.json();
          const filteredArray = Array.isArray(filteredData.media) ? filteredData.media : [];
          setMedia(filteredArray);
          setFilteredMedia(filteredArray);
        } else {
          setMedia(allMediaArray);
          setFilteredMedia(allMediaArray);
        }
      } else {
        setMedia(allMediaArray);
        setFilteredMedia(allMediaArray);
      }
    } catch (error) {
      console.error('Failed to load media:', error);
      toast.error('Failed to load media');
    } finally {
      setLoading(false);
    }
  }, [currentDirectory]);

  useEffect(() => {
    fetchMedia();
  }, [fetchMedia]);



  useEffect(() => {
    let filtered = media.filter(item =>
      item.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
      item.file_name.toLowerCase().includes(searchTerm.toLowerCase())
    );

    // Apply sorting
    filtered = filtered.sort((a, b) => {
      let aValue, bValue;
      
      switch (sortBy) {
        case 'name':
          aValue = a.name.toLowerCase();
          bValue = b.name.toLowerCase();
          break;
        case 'date':
          aValue = new Date(a.created_at).getTime();
          bValue = new Date(b.created_at).getTime();
          break;
        case 'size':
          aValue = a.size;
          bValue = b.size;
          break;
        case 'type':
          aValue = a.mime_type;
          bValue = b.mime_type;
          break;
        default:
          aValue = new Date(a.created_at).getTime();
          bValue = new Date(b.created_at).getTime();
      }
      
      if (sortOrder === 'asc') {
        return aValue > bValue ? 1 : -1;
      } else {
        return aValue < bValue ? 1 : -1;
      }
    });

    setFilteredMedia(filtered);
    setCurrentPage(1);
  }, [searchTerm, media, sortBy, sortOrder]);



  const handleFileUpload = async (files: FileList) => {
    setUploading(true);

    const validFiles = Array.from(files);

    if (validFiles.length === 0) {
      setUploading(false);
      return;
    }

    const formData = new FormData();
    validFiles.forEach(file => {
      formData.append('files[]', file);
    });

    if (currentDirectory) {
      formData.append('directory_id', currentDirectory.toString());
    }

    try {
      const response = await fetch(route('api.media.batch'), {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        headers: {
          'X-CSRF-TOKEN': csrf_token,
          'X-Requested-With': 'XMLHttpRequest',
        },
      });

      const result = await response.json();

      if (response.ok) {
        setMedia(prev => [...result.data, ...prev]);
        setAllMedia(prev => [...result.data, ...prev]); // Update allMedia as well
        toast.success(result.message);

        // Show individual errors if any
        if (result.errors && result.errors.length > 0) {
          result.errors.forEach((error: string) => {
            toast.error(error);
          });
        }
      } else {
        // Handle validation errors and other errors
        if (result.errors && Array.isArray(result.errors)) {
          result.errors.forEach((error: string) => {
            toast.error(error);
          });
        } else if (result.message) {
          toast.error(result.message);
        } else {
          toast.error(t('Failed to upload files'));
        }
        
        // Show additional info if available
        if (result.allowed_types) {
          toast.error(t('Allowed types: {{types}}', { types: result.allowed_types }));
        }
        if (result.max_size_mb) {
          toast.error(t('Max file size: {{size}} MB', { size: result.max_size_mb }));
        }
      }
    } catch (error) {
      toast.error('Error uploading files');
    }

    setUploading(false);
    setIsUploadModalOpen(false);
  };

  const handleDrag = (e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    if (e.type === 'dragenter' || e.type === 'dragover') {
      setDragActive(true);
    } else if (e.type === 'dragleave') {
      setDragActive(false);
    }
  };

  const handleDrop = (e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    setDragActive(false);

    if (e.dataTransfer.files && e.dataTransfer.files[0]) {
      handleFileUpload(e.dataTransfer.files);
    }
  };

  const deleteMedia = async (id: number) => {
    try {
      const response = await fetch(route('api.media.destroy', id), {
        method: 'DELETE',
        credentials: 'same-origin',
        headers: {
          'X-CSRF-TOKEN': csrf_token,
          'X-Requested-With': 'XMLHttpRequest',
        },
      });

      const result = await response.json();

      if (response.ok) {
        setMedia(prev => prev.filter(item => item.id !== id));
        setAllMedia(prev => prev.filter(item => item.id !== id)); // Update allMedia as well
        toast.success(result.message || t('Media deleted successfully'));
      } else {
        if (result.errors && Array.isArray(result.errors)) {
          result.errors.forEach((error: string) => toast.error(error));
        } else {
          toast.error(result.error || result.message || t('Failed to delete media'));
        }
      }
    } catch (error) {
      toast.error(t('Error deleting media'));
    }
  };

  const handleCopyLink = (url: string) => {
    navigator.clipboard.writeText(url);
    toast.success('Image URL copied to clipboard');
  };

  const handleDownload = async (id: number, filename: string) => {
    try {
      const response = await fetch(route('api.media.download', id), {
        credentials: 'same-origin',
        headers: {
          'X-CSRF-TOKEN': csrf_token,
          'X-Requested-With': 'XMLHttpRequest',
        },
      });

      if (!response.ok) {
        const errorData = await response.json();
        if (errorData.errors && Array.isArray(errorData.errors)) {
          errorData.errors.forEach((error: string) => toast.error(error));
        } else {
          toast.error(errorData.error || errorData.message || t('Download failed'));
        }
        return;
      }

      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = filename;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);
      toast.success(t('Download started'));
    } catch (error) {
      toast.error(t('Download failed: Network error'));
    }
  };

  const handleShowInfo = (item: MediaItem) => {
    setSelectedMediaInfo(item);
    setInfoModalOpen(true);
  };

  const formatFileSize = (bytes: number) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  };



  const getFileIcon = (mimeType: string) => {
    if (mimeType.startsWith('image/')) return <ImageIcon className="h-4 w-4" />;
    if (mimeType.includes('pdf')) return <div className="h-4 w-4 bg-red-500 rounded text-white text-xs flex items-center justify-center font-bold">PDF</div>;
    if (mimeType.includes('word') || mimeType.includes('document')) return <div className="h-4 w-4 bg-blue-500 rounded text-white text-xs flex items-center justify-center font-bold">DOC</div>;
    if (mimeType.includes('csv') || mimeType.includes('spreadsheet')) return <div className="h-4 w-4 bg-green-500 rounded text-white text-xs flex items-center justify-center font-bold">CSV</div>;
    if (mimeType.startsWith('video/')) return <div className="h-4 w-4 bg-purple-500 rounded text-white text-xs flex items-center justify-center font-bold">VID</div>;
    if (mimeType.startsWith('audio/')) return <div className="h-4 w-4 bg-orange-500 rounded text-white text-xs flex items-center justify-center font-bold">AUD</div>;
    return <div className="h-4 w-4 bg-gray-500 rounded text-white text-xs flex items-center justify-center font-bold">FILE</div>;
  };

  const totalPages = Math.ceil(filteredMedia.length / itemsPerPage);
  const startIndex = (currentPage - 1) * itemsPerPage;
  const currentMedia = filteredMedia.slice(startIndex, startIndex + itemsPerPage);

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Media Library') }
  ];

  const pageActions = [
    {
      label: t('Upload Media'),
      icon: <Plus className="h-4 w-4" />,
      variant: 'default' as const,
      onClick: () => setIsUploadModalOpen(true)
    }
  ];

  const getSortLabel = () => {
    const labels = {
      name: t('Name'),
      date: t('Date'),
      size: t('Size'),
      type: t('Type')
    };
    return `${labels[sortBy]} ${sortOrder === 'asc' ? '↑' : '↓'}`;
  };

  return (
    <PageTemplate
      title={t('Media Library')}
      url="/examples/media-library-demo"
      breadcrumbs={breadcrumbs}
      actions={pageActions}
    >
      <div className="flex flex-col lg:flex-row gap-4 lg:gap-6">
        {/* Responsive Sidebar */}
        <div className="w-full md:w-72 lg:w-80 xl:w-64 lg:flex-shrink-0">
          <Card className="lg:h-[calc(100vh-12rem)] flex flex-col">
            <CardContent className="p-0 flex flex-col h-full">
              {/* Quick Access Section */}
              <div className="p-4 border-b">
                <h3 className="text-sm font-semibold text-muted-foreground tracking-wide mb-3">
                  {t('Quick Access')}
                </h3>
                <div className="space-y-1">
                  <Button
                    variant={currentDirectory === null ? "secondary" : "ghost"}
                    size="sm"
                    className="w-full justify-start h-9 text-sm px-3"
                    onClick={() => setCurrentDirectory(null)}
                  >
                    <Home className="h-4 w-4 mr-2 flex-shrink-0" />
                    <span className="truncate flex-1 text-left min-w-0">{t('All Files')}</span>
                    <Badge variant="outline" className="ml-2 text-xs flex-shrink-0 min-w-[2rem] justify-center">
                      {allMedia.length}
                    </Badge>
                  </Button>
                </div>
              </div>

              {/* Folders Section - Responsive */}
              <div className="p-4 border-b flex-1 min-h-0">
                <div className="flex items-center justify-between mb-3">
                  <h3 className="text-sm font-semibold text-muted-foreground tracking-wide">
                    {t('Folders')}
                  </h3>
                  {hasPermission(permissions, 'create-media-directories') && (
                    <Button
                      variant="ghost"
                      size="sm"
                      className="h-6 w-6 p-0"
                      onClick={() => setShowCreateDirectory(true)}
                    >
                      <Plus className="h-3 w-3" />
                    </Button>
                  )}
                </div>
                
                {showCreateDirectory && (
                  <div className="mb-3 space-y-2">
                    <Input
                      placeholder={t('Folder name...')}
                      value={newDirectoryName}
                      onChange={(e) => setNewDirectoryName(e.target.value)}
                      onKeyPress={(e) => e.key === 'Enter' && createDirectory()}
                      className="h-8 text-sm"
                    />
                    <div className="flex gap-2">
                      <Button onClick={createDirectory} size="sm" className="h-7 text-xs px-3 flex-1">
                        {t('Create')}
                      </Button>
                      <Button
                        variant="outline"
                        size="sm"
                        className="h-7 text-xs px-3 flex-1"
                        onClick={() => {
                          setShowCreateDirectory(false);
                          setNewDirectoryName('');
                        }}
                      >
                        {t('Cancel')}
                      </Button>
                    </div>
                  </div>
                )}
                
                {/* Responsive Scrollable Folders List */}
                <div className="space-y-1 overflow-y-auto max-h-[40vh] lg:max-h-[calc(100vh-32rem)] pr-1 scrollbar-thin scrollbar-thumb-muted scrollbar-track-transparent">
                  {directories.map((dir: any) => {
                    // Count files in this specific directory using allMedia
                    const dirFileCount = allMedia.filter(item => {
                      return item.directory_id === dir.id;
                    }).length;
                    
                    return (
                      hasPermission(permissions, 'manage-media-directories') && (
                        <Button
                          key={dir.id}
                          variant={currentDirectory === dir.id ? "secondary" : "ghost"}
                          size="sm"
                          className="w-full justify-start h-9 text-sm px-3"
                          onClick={() => setCurrentDirectory(dir.id)}
                          title={dir.name}
                        >
                          {currentDirectory === dir.id ? (
                            <FolderOpen className="h-4 w-4 mr-2 flex-shrink-0" />
                          ) : (
                            <Folder className="h-4 w-4 mr-2 flex-shrink-0" />
                          )}
                          <span className="truncate flex-1 text-left min-w-0">{dir.name}</span>
                          <Badge variant="outline" className="ml-2 text-xs flex-shrink-0 min-w-[2rem] justify-center">
                            {dirFileCount}
                          </Badge>
                        </Button>
                      )
                    );
                  })}
                  
                  {directories.length === 0 && !showCreateDirectory && (
                    <p className="text-xs text-muted-foreground text-center py-4">
                      {t('No folders yet')}
                    </p>
                  )}
                </div>
              </div>

              {/* Storage Section - Responsive */}
              <div className="p-4">
                <h3 className="text-sm font-semibold text-muted-foreground tracking-wide mb-3">
                  {t('Storage')}
                </h3>
                <div className="space-y-2">
                  <div className="flex items-center justify-between text-sm">
                    <span className="text-muted-foreground">{t('Used')}</span>
                    <span className="font-medium">
                      {formatFileSize(allMedia.reduce((acc, item) => acc + item.size, 0))}
                    </span>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Responsive Main Content */}
        <div className="flex-1 flex flex-col min-w-0 overflow-hidden">
          {/* Header Bar */}
          <Card className="mb-4 flex-shrink-0">
            <CardContent className="p-3 lg:p-4">
              <div className="flex flex-col lg:flex-row gap-3 lg:gap-4">
                {/* Search Section */}
                <div className="flex-1">
                  <div className="relative max-w-full lg:max-w-sm">
                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground h-4 w-4" />
                    <Input
                      placeholder={t('Search media files...')}
                      value={searchTerm}
                      onChange={(e) => setSearchTerm(e.target.value)}
                      className="pl-10 h-9 lg:h-10"
                    />
                  </div>
                  {searchTerm && (
                    <p className="text-xs text-muted-foreground mt-1">
                      {t('Showing results for "{{term}}"', { term: searchTerm })}
                    </p>
                  )}
                </div>

                {/* View Controls */}
                <div className="flex items-center gap-2">
                  <div className="flex items-center border rounded-md">
                    <Button
                      variant={viewMode === 'grid' ? 'default' : 'ghost'}
                      size="sm"
                      className="h-8 px-2"
                      onClick={() => setViewMode('grid')}
                    >
                      <Grid3X3 className="h-4 w-4" />
                    </Button>
                    <Button
                      variant={viewMode === 'list' ? 'default' : 'ghost'}
                      size="sm"
                      className="h-8 px-2"
                      onClick={() => setViewMode('list')}
                    >
                      <List className="h-4 w-4" />
                    </Button>
                  </div>
                  
                  <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                      <Button variant="outline" size="sm" className="h-8">
                        {getSortLabel()}
                      </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                      <DropdownMenuItem onClick={() => { setSortBy('name'); setSortOrder('asc'); }}>
                        {t('Name')} (A-Z)
                      </DropdownMenuItem>
                      <DropdownMenuItem onClick={() => { setSortBy('name'); setSortOrder('desc'); }}>
                        {t('Name')} (Z-A)
                      </DropdownMenuItem>
                      <DropdownMenuSeparator />
                      <DropdownMenuItem onClick={() => { setSortBy('date'); setSortOrder('desc'); }}>
                        {t('Date')} ({t('Newest')})
                      </DropdownMenuItem>
                      <DropdownMenuItem onClick={() => { setSortBy('date'); setSortOrder('asc'); }}>
                        {t('Date')} ({t('Oldest')})
                      </DropdownMenuItem>
                      <DropdownMenuSeparator />
                      <DropdownMenuItem onClick={() => { setSortBy('size'); setSortOrder('desc'); }}>
                        {t('Size')} ({t('Largest')})
                      </DropdownMenuItem>
                      <DropdownMenuItem onClick={() => { setSortBy('size'); setSortOrder('asc'); }}>
                        {t('Size')} ({t('Smallest')})
                      </DropdownMenuItem>
                      <DropdownMenuSeparator />
                      <DropdownMenuItem onClick={() => { setSortBy('type'); setSortOrder('asc'); }}>
                        {t('Type')}
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </div>

                {/* Stats Section - Responsive */}
                <div className="flex flex-wrap gap-3 lg:gap-6 items-center">
                  <div className="flex items-center gap-2">
                    <div className="p-1.5 bg-primary/10 rounded-md">
                      <ImageIcon className="h-4 w-4 text-primary" />
                    </div>
                    <span className="text-xs lg:text-sm font-semibold">{filteredMedia.length} {t('Files')}</span>
                  </div>

                  <div className="flex items-center gap-2">
                    <div className="p-1.5 bg-green-500/10 rounded-md">
                      <HardDrive className="h-4 w-4 text-green-600" />
                    </div>
                    <span className="text-xs lg:text-sm font-semibold">
                      {formatFileSize(filteredMedia.reduce((acc, item) => acc + item.size, 0))}
                    </span>
                  </div>

                  <div className="flex items-center gap-2">
                    <div className="p-1.5 bg-blue-500/10 rounded-md">
                      <ImageIcon className="h-4 w-4 text-blue-600" />
                    </div>
                    <span className="text-xs lg:text-sm font-semibold">
                      {filteredMedia.filter(item => item.mime_type.startsWith('image/')).length} {t('Images')}
                    </span>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Media Grid - Responsive */}
          <Card className="flex-1 flex flex-col min-h-0 lg:h-[calc(100vh-20rem)]">
            <CardContent className="p-3 lg:p-6 flex flex-col h-full overflow-hidden">
              {loading ? (
                <div className="text-center py-12">
                  <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto mb-4"></div>
                  <p className="text-muted-foreground">{t('Loading media...')}</p>
                </div>
              ) : currentMedia.length === 0 ? (
                <div className="text-center py-8 lg:py-16">
                  <div className="mx-auto w-16 h-16 lg:w-24 lg:h-24 bg-muted rounded-full flex items-center justify-center mb-4">
                    <ImageIcon className="h-8 w-8 lg:h-10 lg:w-10 text-muted-foreground" />
                  </div>
                  <h3 className="text-base lg:text-lg font-semibold mb-2">{t('No media files found')}</h3>
                  <p className="text-sm text-muted-foreground mb-4 lg:mb-6">
                    {searchTerm ? t('No results found for "{{term}}"', { term: searchTerm }) : t('Get started by uploading your first media file')}
                  </p>
                  {!searchTerm && (
                    <Button
                      onClick={() => setIsUploadModalOpen(true)}
                      size="lg"
                    >
                      <Plus className="h-4 w-4 mr-2" />
                      {t('Upload Media')}
                    </Button>
                  )}
                </div>
              ) : (
                <div className="flex flex-col h-full">
                  {/* Grid/List View - Responsive */}
                  <div className="flex-1 min-h-0 overflow-hidden">
                    {viewMode === 'grid' ? (
                      <div className="h-full overflow-y-auto pr-2">
                        <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-3 lg:gap-4">
                          {currentMedia.map((item) => (
                            <div
                              key={item.id}
                              className="group relative bg-card border rounded-lg overflow-hidden hover:shadow-md transition-all duration-200"
                            >
                              {/* File Preview Container */}
                              <div className="relative aspect-square bg-muted flex items-center justify-center cursor-pointer"
                                onClick={() => handleShowInfo(item)}
                              >
                                {item.mime_type.startsWith('image/') ? (
                                  <img
                                    src={item.thumb_url}
                                    alt={item.name}
                                    className="w-full h-full object-cover"
                                    onError={(e) => {
                                      e.currentTarget.src = item.url;
                                    }}
                                  />
                                ) : (
                                  <div className="flex flex-col items-center justify-center p-4">
                                    <div className="mb-2 text-2xl">
                                      {getFileIcon(item.mime_type)}
                                    </div>
                                    <div className="text-xs text-center font-medium text-muted-foreground truncate w-full">
                                      {item.mime_type.split('/')[1]?.toUpperCase() || 'FILE'}
                                    </div>
                                  </div>
                                )}

                                {/* Hover Overlay */}
                                <div className="absolute inset-0 bg-black/0 hover:bg-black/10 transition-all duration-200 flex items-center justify-center">
                                  <div className="opacity-0 hover:opacity-100 transition-opacity duration-200">
                                    <div className="bg-white/90 backdrop-blur-sm rounded-full p-2">
                                      <Search className="h-4 w-4 text-gray-700" />
                                    </div>
                                  </div>
                                </div>

                                {/* File Type Badge */}
                                <div className="absolute top-2 left-2">
                                  <Badge variant="secondary" className="text-xs bg-background/95">
                                    {item.mime_type.split('/')[1].toUpperCase()}
                                  </Badge>
                                </div>
                              </div>

                              {/* Card Content */}
                              <div className="p-3 space-y-2">
                                <div>
                                  <h3 className="text-sm font-medium truncate" title={item.name}>
                                    {item.name}
                                  </h3>
                                  <p className="text-xs text-muted-foreground flex items-center gap-1 mt-1">
                                    <HardDrive className="h-3 w-3" />
                                    {formatFileSize(item.size)}
                                  </p>
                                </div>

                                <div className="flex items-center justify-between text-xs text-muted-foreground">
                                  <span className="flex items-center gap-1">
                                    <Calendar className="h-3 w-3" />
                                    {window.appSettings?.formatDateTimeSimple(item.created_at,false) || new Date(item.created_at).toLocaleDateString()}
                                  </span>
                                </div>
                              </div>
                            </div>
                          ))}
                        </div>
                      </div>
                    ) : (
                      /* List View - Fixed Height with Scrolling */
                      <div 
                        className="h-full overflow-y-auto" 
                        style={{ maxHeight: 'calc(100vh - 28rem)' }}
                      >
                        <div className="space-y-3 p-2">
                          {currentMedia.map((item) => (
                            <div
                              key={item.id}
                              className="flex items-center gap-3 p-4 border rounded-lg hover:shadow-sm transition-all duration-200 bg-card cursor-pointer"
                              onClick={() => handleShowInfo(item)}
                            >
                              {/* File Icon/Thumbnail */}
                              <div className="flex-shrink-0 w-10 h-10 bg-muted rounded-md flex items-center justify-center">
                                {item.mime_type.startsWith('image/') ? (
                                  <img
                                    src={item.thumb_url}
                                    alt={item.name}
                                    className="w-full h-full object-cover rounded-md"
                                    onError={(e) => {
                                      e.currentTarget.src = item.url;
                                    }}
                                  />
                                ) : (
                                  getFileIcon(item.mime_type)
                                )}
                              </div>

                              {/* File Info */}
                              <div className="flex-1 min-w-0">
                                <div className="flex items-center gap-2">
                                  <h3 className="text-sm font-medium truncate" title={item.name}>
                                    {item.name}
                                  </h3>
                                  <Badge variant="secondary" className="text-xs flex-shrink-0">
                                    {item.mime_type.split('/')[1].toUpperCase()}
                                  </Badge>
                                </div>
                                <div className="flex items-center gap-4 text-xs text-muted-foreground mt-1">
                                  <span className="flex items-center gap-1">
                                    <HardDrive className="h-3 w-3" />
                                    {formatFileSize(item.size)}
                                  </span>
                                  <span className="flex items-center gap-1">
                                    <Calendar className="h-3 w-3" />
                                    {window.appSettings?.formatDateTimeSimple(item.created_at,false) || new Date(item.created_at).toLocaleDateString()}
                                  </span>
                                </div>
                              </div>
                            </div>
                          ))}
                        </div>
                      </div>
                    )}
                  </div>

                  {/* Pagination */}
                  {totalPages > 1 && (
                    <div className="flex flex-col sm:flex-row items-center justify-between gap-4 pt-6 border-t mt-6">
                      <div className="text-sm text-muted-foreground">
                        {t('Showing')} <span className="font-semibold">{startIndex + 1}</span> {t('to')} <span className="font-semibold">{Math.min(startIndex + itemsPerPage, filteredMedia.length)}</span> {t('of')} <span className="font-semibold">{filteredMedia.length}</span> {t('files')}
                      </div>

                      <div className="flex items-center gap-2">
                        <Button
                          variant="outline"
                          size="sm"
                          disabled={currentPage === 1}
                          onClick={() => setCurrentPage(prev => Math.max(prev - 1, 1))}
                        >
                          {t('Previous')}
                        </Button>

                        <div className="flex gap-1">
                          {Array.from({ length: Math.min(totalPages, 5) }, (_, i) => {
                            let page;
                            if (totalPages <= 5) {
                              page = i + 1;
                            } else if (currentPage <= 3) {
                              page = i + 1;
                            } else if (currentPage >= totalPages - 2) {
                              page = totalPages - 4 + i;
                            } else {
                              page = currentPage - 2 + i;
                            }

                            return (
                              <Button
                                key={page}
                                variant={currentPage === page ? 'default' : 'outline'}
                                size="sm"
                                className="w-10 h-8"
                                onClick={() => setCurrentPage(page)}
                              >
                                {page}
                              </Button>
                            );
                          })}
                        </div>

                        <Button
                          variant="outline"
                          size="sm"
                          disabled={currentPage === totalPages}
                          onClick={() => setCurrentPage(prev => Math.min(prev + 1, totalPages))}
                        >
                          {t('Next')}
                        </Button>
                      </div>
                    </div>
                  )}
                </div>
              )}
            </CardContent>
          </Card>
        </div>
      </div>

      {/* Upload Modal */}
      <Dialog open={isUploadModalOpen} onOpenChange={setIsUploadModalOpen}>
        <DialogContent className="max-w-lg mx-4 sm:mx-auto">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2">
              <Upload className="h-5 w-5" />
              {t('Upload Media Files')}
            </DialogTitle>
          </DialogHeader>

          <div className="space-y-6">
            <div
              className={`relative border-2 border-dashed rounded-xl p-6 sm:p-12 text-center transition-all duration-200 ${
                dragActive
                  ? 'border-blue-500 bg-blue-50 scale-[1.02]'
                  : 'border-gray-300 hover:border-gray-400 hover:bg-gray-50'
              }`}
              onDragEnter={handleDrag}
              onDragLeave={handleDrag}
              onDragOver={handleDrag}
              onDrop={handleDrop}
            >
              <div className={`transition-all duration-200 ${dragActive ? 'scale-110' : ''}`}>
                <div className="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                  <Upload
                    className={`h-8 w-8 transition-colors ${
                      dragActive ? 'text-blue-500' : 'text-gray-400'
                    }`}
                  />
                </div>
                <h3 className="text-lg font-medium mb-2">
                  {dragActive ? t('Drop files here') : t('Upload your images')}
                </h3>
                <p className="text-sm text-muted-foreground mb-6">
                  {t('Drag and drop your images here, or click to browse')}
                </p>

                <Input
                  type="file"
                  multiple
                  onChange={(e) => e.target.files && handleFileUpload(e.target.files)}
                  className="hidden"
                  id="file-upload-modal"
                />

                <Button
                  type="button"
                  onClick={() => document.getElementById('file-upload-modal')?.click()}
                  disabled={uploading}
                  size="lg"
                >
                  {uploading ? (
                    <>
                      <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                      {t('Uploading...')}
                    </>
                  ) : (
                    <>
                      <Plus className="h-4 w-4 mr-2" />
                      {t('Choose Files')}
                    </>
                  )}
                </Button>
              </div>

              {dragActive && <div className="absolute inset-0 bg-blue-500/10 rounded-xl" />}
            </div>
          </div>
        </DialogContent>
      </Dialog>

      {/* Enhanced Media Details Modal - Fully Responsive */}
      <Dialog open={infoModalOpen} onOpenChange={setInfoModalOpen}>
        <DialogContent className="max-w-[95vw] sm:max-w-[90vw] md:max-w-4xl lg:max-w-5xl xl:max-w-6xl mx-auto max-h-[95vh] sm:max-h-[90vh] p-0 overflow-hidden">
          <DialogHeader className="p-3 sm:p-4 md:p-6 pb-0">
            <DialogTitle className="flex items-center gap-2 text-sm sm:text-base md:text-lg">
              <Info className="h-4 w-4 sm:h-5 sm:w-5" />
              {t('Media Details')}
            </DialogTitle>
          </DialogHeader>

          {selectedMediaInfo && (
            <div className="flex flex-col lg:flex-row h-auto max-h-[calc(95vh-3rem)] sm:max-h-[calc(90vh-4rem)] lg:h-[calc(90vh-4rem)]">
              {/* Image Display Area - Fully Responsive */}
              <div className="flex-1 flex items-center justify-center bg-gray-50 p-3 sm:p-4 md:p-6 min-h-[200px] sm:min-h-[250px] md:min-h-[300px] lg:min-h-0 max-h-[40vh] lg:max-h-none overflow-hidden">
                {selectedMediaInfo.mime_type.startsWith('image/') ? (
                  <img
                    src={selectedMediaInfo.url}
                    alt={selectedMediaInfo.name}
                    className="max-w-full max-h-full object-contain rounded-lg shadow-lg"
                    onError={(e) => {
                      e.currentTarget.src = selectedMediaInfo.thumb_url;
                    }}
                  />
                ) : (
                  <div className="flex flex-col items-center justify-center h-full w-full">
                    <div className="mb-4 sm:mb-6 text-4xl sm:text-6xl md:text-8xl">{getFileIcon(selectedMediaInfo.mime_type)}</div>
                    <div className="text-base sm:text-lg md:text-xl font-medium text-muted-foreground">
                      {selectedMediaInfo.mime_type.split('/')[1]?.toUpperCase() || 'FILE'}
                    </div>
                    <div className="text-xs sm:text-sm text-muted-foreground mt-1 sm:mt-2 px-4 text-center">
                      {selectedMediaInfo.name}
                    </div>
                  </div>
                )}
              </div>

              {/* Details Sidebar - Fully Responsive */}
              <div className="w-full lg:w-80 xl:w-96 border-t lg:border-t-0 lg:border-l bg-background p-3 sm:p-4 md:p-6 overflow-y-auto max-h-[55vh] lg:max-h-none">
                <div className="space-y-4 sm:space-y-5 md:space-y-6">
                  {/* File Details */}
                  <div>
                    <h3 className="text-sm sm:text-base md:text-lg font-semibold mb-3 sm:mb-4">{t('File Information')}</h3>
                    <div className="space-y-2 sm:space-y-3">
                      <div>
                        <label className="text-xs sm:text-sm font-medium text-muted-foreground">{t('File Name')}</label>
                        <p className="text-xs sm:text-sm mt-1 break-all" title={selectedMediaInfo.file_name}>
                          {selectedMediaInfo.file_name}
                        </p>
                      </div>

                      <div>
                        <label className="text-xs sm:text-sm font-medium text-muted-foreground">{t('Display Name')}</label>
                        <p className="text-xs sm:text-sm mt-1">
                          {selectedMediaInfo.name}
                        </p>
                      </div>

                      <div>
                        <label className="text-xs sm:text-sm font-medium text-muted-foreground">{t('File Type')}</label>
                        <div className="mt-1">
                          <Badge variant="secondary" className="text-xs">{selectedMediaInfo.mime_type}</Badge>
                        </div>
                      </div>

                      <div>
                        <label className="text-xs sm:text-sm font-medium text-muted-foreground">{t('File Size')}</label>
                        <p className="text-xs sm:text-sm mt-1">{formatFileSize(selectedMediaInfo.size)}</p>
                      </div>

                      <div>
                        <label className="text-xs sm:text-sm font-medium text-muted-foreground">{t('Upload Date')}</label>
                        <p className="text-xs sm:text-sm mt-1">{window.appSettings?.formatDateTimeSimple(selectedMediaInfo.created_at,false) || new Date(selectedMediaInfo.created_at).toLocaleDateString()}</p>
                      </div>
                    </div>
                  </div>

                  {/* URL Section */}
                  <div>
                    <label className="text-xs sm:text-sm font-medium text-muted-foreground block mb-2">{t('File URL')}</label>
                    <div className="flex items-center gap-1.5 sm:gap-2 p-2 sm:p-3 bg-muted rounded-md">
                      <code className="text-[10px] sm:text-xs text-muted-foreground flex-1 break-all">
                        {selectedMediaInfo.url}
                      </code>
                      <Button
                        size="sm"
                        variant="ghost"
                        onClick={() => handleCopyLink(selectedMediaInfo.url)}
                        className="h-7 w-7 sm:h-8 sm:w-8 p-0 flex-shrink-0"
                      >
                        <Copy className="h-3 w-3 sm:h-4 sm:w-4" />
                      </Button>
                    </div>
                  </div>

                  {/* Action Buttons */}
                  <div className="space-y-2 sm:space-y-3">
                    <h3 className="text-sm sm:text-base md:text-lg font-semibold">{t('Actions')}</h3>
                    
                    <Button
                      onClick={() => handleCopyLink(selectedMediaInfo.url)}
                      className="w-full justify-start h-9 sm:h-10 text-xs sm:text-sm"
                      variant="outline"
                    >
                      <Copy className="h-3.5 w-3.5 sm:h-4 sm:w-4 mr-2" />
                      {t('Copy Link')}
                    </Button>

                    {hasPermission(permissions, 'download-media') && (
                      <Button
                        onClick={() => handleDownload(selectedMediaInfo.id, selectedMediaInfo.file_name)}
                        className="w-full justify-start h-9 sm:h-10 text-xs sm:text-sm"
                        variant="outline"
                      >
                        <Download className="h-3.5 w-3.5 sm:h-4 sm:w-4 mr-2" />
                        {t('Download')}
                      </Button>
                    )}

                    {hasPermission(permissions, 'delete-media') && (
                      <Button
                        onClick={() => {
                          deleteMedia(selectedMediaInfo.id);
                          setInfoModalOpen(false);
                        }}
                        className="w-full justify-start h-9 sm:h-10 text-xs sm:text-sm"
                        variant="destructive"
                      >
                        <Trash2 className="h-3.5 w-3.5 sm:h-4 sm:w-4 mr-2" />
                        {t('Delete File')}
                      </Button>
                    )}
                  </div>
                </div>
              </div>
            </div>
          )}
        </DialogContent>
      </Dialog>
    </PageTemplate>
  );
}