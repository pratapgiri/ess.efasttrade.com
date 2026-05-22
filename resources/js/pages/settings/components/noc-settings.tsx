import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useTranslation } from 'react-i18next';
import { router, usePage } from '@inertiajs/react';
import { toast } from '@/components/custom-toast';
import { FileText, Save, Info } from 'lucide-react';
import { RichTextEditor } from '@/components/ui/rich-text-editor';
import { SettingsSection } from '@/components/settings-section';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import ReactCountryFlag from 'react-country-flag';

interface NocTemplate {
  id: number;
  language: string;
  content: string;
  variables?: string[];
}

interface NocSettingsProps {
  templates?: NocTemplate[];
}

export default function NocSettings({ templates = [] }: NocSettingsProps) {
  const { t } = useTranslation();
  const { globalSettings } = usePage().props as any;
  const availableLanguages = globalSettings?.availableLanguages || [];
  const [selectedLanguage, setSelectedLanguage] = useState('en');
  const [content, setContent] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [key, setKey] = useState(0); // Force re-render key

  // Get placeholders from template or use defaults
  const getPlaceholders = () => {
    const template = templates.find(t => t.language === selectedLanguage);
    if (template?.variables) {
      const variables = typeof template.variables === 'string' 
        ? JSON.parse(template.variables) 
        : template.variables;
      return variables.map((variable: string) => ({
        key: variable.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()),
        label: `{${variable}}`
      }));
    }
    return [
      { key: 'Date', label: '{date}' },
      { key: 'Company Name', label: '{company_name}' },
      { key: 'Employee Name', label: '{employee_name}' },
      { key: 'Designation', label: '{designation}' }
    ];
  };

  const placeholders = getPlaceholders();

  // Default template content
  const defaultTemplate = `
    <h2>No Objection Certificate</h2>
    
    <p>Date: {date}</p>
    
    <p>To Whom It May Concern,</p>
    
    <p>This is to certify that <strong>{employee_name}</strong> is currently employed with {company_name} as {designation}.</p>
    
    <p>We have no objection to the above mentioned employee for any official purposes.</p>
    
    <br>
    <p>Sincerely,</p>
    <p><strong>HR Department</strong><br>
    {company_name}</p>
  `;

  // Load template content when language changes
  useEffect(() => {
    const template = templates.find(t => t.language === selectedLanguage);
    if (template) {
      // Replace literal \n with actual newlines
      const cleanContent = template.content.replace(/\\n/g, '\n');
      setContent(cleanContent);
      setKey(prev => prev + 1);
    } else {
      setContent(defaultTemplate);
      setKey(prev => prev + 1);
    }
  }, [selectedLanguage, templates]);

  const handleSave = async () => {
    setIsLoading(true);
    const template = templates.find(t => t.language === selectedLanguage);
    try {
      router.post(route('settings.noc.update'), {
        templateId: template?.id,
        language: selectedLanguage,
        content: content
      }, {
        onSuccess: (page) => {
          if (page.props.flash.success) {
            toast.success(t(page.props.flash.success));
          } else if (page.props.flash.error) {
            toast.error(t(page.props.flash.error));
          }
          setContent(content);
          setKey(prev => prev + 1);
        },
        onError: (errors) => {
          if (typeof errors === 'string') {
            toast.error(t(errors));
          } else {
            toast.error(t('Failed to update NOC template: {{errors}}', { errors: Object.values(errors).join(', ') }));
          }
        },
        onFinish: () => setIsLoading(false)
      });
    } catch (error) {
      setIsLoading(false);
      toast.error(t('Failed to update NOC template'));
    }
  };

  return (
    <SettingsSection
      title={t("No Objection Certificate Settings")}
      description={t("Configure NOC templates for different languages")}
      action={
        <Button onClick={handleSave} disabled={isLoading} size="sm">
          <Save className="h-4 w-4 mr-2" />
          {isLoading ? t('Saving...') : t('Save Changes')}
        </Button>
      }
    >
      <div className="grid grid-cols-1 gap-6">
        <div>
          <Card>
            <CardHeader className="pb-3">
            </CardHeader>
            <CardContent>
              <div className="grid grid-cols-1 gap-6">
                {/* Language Selection and Placeholders */}
                <div className="space-y-6">
                  {/* Language Selection */}
                  <div className="flex justify-end">
                    <Select value={selectedLanguage} onValueChange={setSelectedLanguage}>
                      <SelectTrigger className="w-48">
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        {availableLanguages.map((lang: any) => (
                          <SelectItem key={lang.code} value={lang.code}>
                            <div className="flex items-center">
                              <ReactCountryFlag
                                countryCode={lang.countryCode}
                                svg
                                style={{ width: '1.2em', height: '1.2em' }}
                                className="mr-2"
                              />
                              <span>{lang.name}</span>
                            </div>
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  </div>

                  {/* Available Placeholders */}
                  <div className="space-y-3">
                    <div className="flex items-center justify-between">
                      <Label className="font-medium">{t("Available Placeholders")}</Label>
                      <TooltipProvider>
                        <Tooltip>
                          <TooltipTrigger asChild>
                            <Info className="h-4 w-4 text-muted-foreground" />
                          </TooltipTrigger>
                          <TooltipContent>
                            <p>{t("Use these placeholders in your template. They will be replaced with actual values.")}</p>
                          </TooltipContent>
                        </Tooltip>
                      </TooltipProvider>
                    </div>
                    <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                      {placeholders.map((placeholder) => (
                        <div
                          key={placeholder.label}
                          className="flex flex-col gap-1 p-3 bg-muted/30 rounded-md text-sm border"
                        >
                          <span className="font-medium text-foreground">{t(placeholder.key)}</span>
                          <code className="text-blue-600 font-mono text-xs">{placeholder.label}</code>
                        </div>
                      ))}
                    </div>
                  </div>
                </div>

                {/* Template Editor */}
                <div className="space-y-3">
                  <div className="flex items-center justify-between">
                    <Label className="font-medium">{t("Template Content")}</Label>
                    <TooltipProvider>
                      <Tooltip>
                        <TooltipTrigger asChild>
                          <Info className="h-4 w-4 text-muted-foreground" />
                        </TooltipTrigger>
                        <TooltipContent>
                          <p>{t("Design your NOC template using the rich text editor")}</p>
                        </TooltipContent>
                      </Tooltip>
                    </TooltipProvider>
                  </div>
                  <div className="w-full">
                    <RichTextEditor
                      key={key}
                      content={content}
                      onChange={setContent}
                      placeholder={t('Enter NOC template content...')}
                      className="w-full [&>div]:border-0 [&>div]:rounded-none [&_.ProseMirror]:min-h-[200px] [&_.ProseMirror]:w-full [&_.ProseMirror]:max-w-none [&_.ProseMirror]:border-0 [&_.ProseMirror]:outline-0 [&_.ProseMirror]:ring-0"
                    />
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </SettingsSection>
  );
}