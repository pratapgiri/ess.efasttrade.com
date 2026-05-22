import React, { useState } from 'react';
import { Link, usePage, useForm } from '@inertiajs/react';
import { Facebook, Twitter, Linkedin, Instagram, Mail, Phone, MapPin } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { toast } from '@/components/custom-toast';
import { getImagePath } from '@/utils/helpers';

interface FooterProps {
  brandColor?: string;
  settings: {
    company_name: string;
    contact_email: string;
    contact_phone: string;
    contact_address: string;
    footerText?: string;
  };
  sectionData?: {
    description?: string;
    newsletter_title?: string;
    newsletter_subtitle?: string;
    links?: any;
    social_links?: Array<{
      name: string;
      icon: string;
      href: string;
    }>;
    section_titles?: {
      product: string;
      company: string;
      support: string;
      legal: string;
    };
  };
}

export default function Footer({ settings, sectionData = {}, brandColor = '#3b82f6' }: FooterProps) {
  const { t } = useTranslation();
  const { globalSettings } = usePage().props as any;
  const currentYear = new Date().getFullYear();

  const themeMode = globalSettings?.themeMode || 'light';
  const isDark = themeMode === 'dark';
  const logoLight = globalSettings?.logoLight || 'logo/logo-light.png';
  const logoDark = globalSettings?.logoDark || 'logo/logo-dark.png';

  const { data, setData, post, processing, errors, reset } = useForm({
    email: ''
  });

  const handleNewsletterSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    toast.loading(t('Subscribing to newsletter...'));
    
    post(route('landing-page.subscribe'), {
      onSuccess: (page) => {
        reset();
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
          toast.error(t('Failed to subscribe: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };

  const footerLinks = sectionData.links || {
    product: [
      { name: 'Features', href: '#features' },
      { name: 'Pricing', href: '#pricing' },
      { name: 'Templates', href: '#' },
      { name: 'Integrations', href: '#' }
    ],
    company: [
      { name: 'About Us', href: '#about' },
      { name: 'Careers', href: '#' },
      { name: 'Press', href: '#' },
      { name: 'Contact', href: '#contact' }
    ],
    support: [
      { name: 'Help Center', href: '#' },
      { name: 'Documentation', href: '#' },
      { name: 'API Reference', href: '#' },
      { name: 'Status', href: '#' }
    ],
    legal: [
      { name: 'Privacy Policy', href: '#' },
      { name: 'Terms of Service', href: '#' },
      { name: 'Cookie Policy', href: '#' },
      { name: 'GDPR', href: '#' }
    ]
  };

  const iconMap: Record<string, any> = {
    Facebook,
    Twitter,
    Linkedin,
    Instagram
  };

  const socialLinks = sectionData.social_links || [
    {
      name: 'Facebook',
      icon: 'Facebook',
      href: '#'
    },
    {
      name: 'Twitter',
      icon: 'Twitter',
      href: '#'
    },
    {
      name: 'LinkedIn',
      icon: 'LinkedIn',
      href: '#'
    },
  ];

  return (
    <footer className="bg-gray-900 text-white">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Main Footer Content */}
        <div className="py-12 sm:py-16">
          <div className="grid lg:grid-cols-6 gap-8 sm:gap-12">
            {/* Company Info */}
            <div className="lg:col-span-2">
              <Link href="/" className="flex items-center mb-6 hover:opacity-80 transition-opacity">
                <img 
                  src={getImagePath(logoLight)} 
                  alt={settings.company_name}
                  className="h-8 w-auto"
                  onError={(e) => {
                    e.currentTarget.style.display = 'none';
                    e.currentTarget.nextElementSibling?.classList.remove('hidden');
                  }}
                />
                <span className="hidden text-2xl font-bold text-white">
                  {settings.company_name}
                </span>
              </Link>
              <p className="text-gray-400 mb-8 leading-relaxed">
                {sectionData.description || t('Simplifying HR management with an all-in-one modern platform. Connect, share, and grow your network effortlessly.')}
              </p>

              {/* Contact Info */}
              <div className="space-y-3">
                <div className="flex items-center gap-3">
                  <Mail className="w-4 h-4 text-gray-400" />
                  <span className="text-gray-400 text-sm">{settings.contact_email}</span>
                </div>
                <div className="flex items-center gap-3">
                  <Phone className="w-4 h-4 text-gray-400" />
                  <span className="text-gray-400 text-sm">{settings.contact_phone}</span>
                </div>
                <div className="flex items-center gap-3">
                  <MapPin className="w-4 h-4 text-gray-400" />
                  <span className="text-gray-400 text-sm">{settings.contact_address}</span>
                </div>
              </div>
            </div>

            {/* Product Links */}
            <div>
              <h3 className="text-white font-semibold mb-4">{sectionData.section_titles?.product || t('Product')}</h3>
              <ul className="space-y-3">
                {(footerLinks.product || []).map((link) => (
                  <li key={link.name}>
                    <a
                      href={link.href}
                      className="text-gray-400 hover:text-white transition-colors text-sm"
                    >
                      {link.name}
                    </a>
                  </li>
                ))}
              </ul>
            </div>

            {/* Company Links */}
            <div>
              <h3 className="text-white font-semibold mb-4">{sectionData.section_titles?.company || t('Company')}</h3>
              <ul className="space-y-3">
                {(footerLinks.company || []).map((link) => (
                  <li key={link.name}>
                    <a
                      href={link.href}
                      className="text-gray-400 hover:text-white transition-colors text-sm"
                    >
                      {link.name}
                    </a>
                  </li>
                ))}
              </ul>
            </div>

            {/* Support Links */}
            <div>
              <h3 className="text-white font-semibold mb-4">{sectionData.section_titles?.support || t('Support')}</h3>
              <ul className="space-y-3">
                {(footerLinks.support || []).map((link) => (
                  <li key={link.name}>
                    <a
                      href={link.href}
                      className="text-gray-400 hover:text-white transition-colors text-sm"
                    >
                      {link.name}
                    </a>
                  </li>
                ))}
              </ul>
            </div>

            {/* Legal Links */}
            <div>
              <h3 className="text-white font-semibold mb-4">{sectionData.section_titles?.legal || t('Legal')}</h3>
              <ul className="space-y-3">
                {(footerLinks.legal || []).map((link) => (
                  <li key={link.name}>
                    <a
                      href={link.href}
                      className="text-gray-400 hover:text-white transition-colors text-sm"
                    >
                      {link.name}
                    </a>
                  </li>
                ))}
              </ul>
            </div>
          </div>
        </div>

        {/* Newsletter Section */}
        {(sectionData.newsletter_title || sectionData.newsletter_subtitle) && (
          <div className="border-t border-gray-800 py-8 sm:py-12">
            <div className="text-center max-w-2xl mx-auto">
              <h3 className="text-xl font-bold text-white mb-4">
                {sectionData.newsletter_title || t('Stay Updated with Our Latest Features')}
              </h3>
              <p className="text-gray-400 mb-6">
                {sectionData.newsletter_subtitle || t('Join our newsletter for HR tips and product updates')}
              </p>
              <form onSubmit={handleNewsletterSubmit} className="flex flex-col sm:flex-row gap-4 max-w-md mx-auto">
                <div className="flex-1">
                  <input
                    type="email"
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    placeholder={t('Enter your email')}
                    className="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:ring-2 focus:ring-gray-600 focus:border-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
                    required
                    disabled={processing}
                  />
                  {errors.email && (
                    <p className="text-red-400 text-sm mt-1">{errors.email}</p>
                  )}
                </div>
                <button 
                  type="submit"
                  disabled={processing}
                  className="cursor-pointer text-white px-6 py-3 rounded-lg transition-colors font-semibold disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 min-w-[120px]" 
                  style={{ backgroundColor: brandColor }}
                >
                  {processing && (
                    <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                  )}
                  {processing ? t('Subscribing...') : t('Subscribe')}
                </button>
              </form>
            </div>
          </div>
        )}

        {/* Bottom Footer */}
        <div className="border-t border-gray-800 py-4 sm:py-6">
          <div className="flex flex-col md:flex-row justify-between items-center gap-3 sm:gap-4">
            {/* Copyright */}
            <div className="text-gray-400 text-sm">
              {globalSettings?.footerText || `© ${currentYear} ${settings.company_name}. ${t('All rights reserved.')}.`}
            </div>

            {/* Social Links */}
            {socialLinks.length > 0 && (
              <div className="flex items-center gap-4">
                <span className="text-gray-400 text-sm">{t('Follow us:')}</span>
                <div className="flex gap-3">
                  {socialLinks.map((social) => {
                    const IconComponent = iconMap[social.icon] || Facebook;
                    return (
                      <a
                        key={social.name}
                        href={social.href}
                        className="w-8 h-8 bg-gray-800 rounded-lg flex items-center justify-center hover:bg-gray-700 transition-colors"
                        aria-label={social.name}
                      >
                        <IconComponent className="w-4 h-4 text-gray-400" />
                      </a>
                    );
                  })}
                </div>
              </div>
            )}
          </div>
        </div>
      </div>
    </footer>
  );
}