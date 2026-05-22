import React, { useState, useEffect } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { Menu, X } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { isUserRegistrationEnabled, getImagePath, getCookie, isDemoMode } from '@/utils/helpers';

interface CustomPage {
  id: number;
  title: string;
  slug: string;
}

interface HeaderProps {
  brandColor?: string;
  settings: {
    company_name: string;
  };
  sectionData?: any;
  customPages?: CustomPage[];
}

export default function Header({ settings, sectionData, customPages = [], brandColor = '#3b82f6' }: HeaderProps) {
  const { t } = useTranslation();
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const [isScrolled, setIsScrolled] = useState(false);
  const { props } = usePage();
  const isSaas = (props as any).globalSettings?.is_saas;
  const auth = (props as any).auth;
  const globalSettings = (props as any).globalSettings || {};
  const isDemo = isDemoMode(props);
  
  const logoLight = globalSettings.logoLight || 'logo/logo-light.png';
  const logoDark = globalSettings.logoDark || 'logo/logo-dark.png';
  
  let themeMode = 'light';
  if (isDemo) {
    const themeSettings = getCookie('themeSettings');
    if (themeSettings) {
      try {
        const parsed = JSON.parse(themeSettings);
        themeMode = parsed.appearance || 'light'        
      } catch {
        themeMode = 'light';
      }
    }
  } else {
    themeMode = globalSettings.themeMode || 'light';
  }
  
  const isDark = themeMode === 'dark';

  useEffect(() => {
    const handleScroll = () => {
      setIsScrolled(window.scrollY > 20);
    };
    window.addEventListener('scroll', handleScroll);
    return () => window.removeEventListener('scroll', handleScroll);
  }, []);

  const menuItems = customPages.map(page => ({
    name: page.title,
    href: route('custom-page.show', page.slug)
  }));


  const isTransparent = sectionData?.transparent;
  const backgroundColor = sectionData?.background_color || '#ffffff';
  const buttonStyle = sectionData?.button_style || 'filled'; // 'filled' or 'outline'
  const textColor = sectionData?.text_color || '';
  

  const navTextColor = textColor || (isDark ? '#d1d5db' : '#4b5563');

  const getButtonStyles = (isHovered: boolean) => {
    if (buttonStyle === 'outline') {
      return isHovered
        ? { backgroundColor: brandColor, color: 'white', borderColor: brandColor }
        : { backgroundColor: 'transparent', color: brandColor, borderColor: brandColor };
    }
    return isHovered
      ? { backgroundColor: 'white', color: brandColor, borderColor: brandColor }
      : { backgroundColor: brandColor, color: 'white', borderColor: brandColor };
  };
  
  const getHeaderClasses = () => {
    if (isTransparent) {
      return isScrolled 
        ? `bg-white/95 ${isDark ? 'dark:bg-gray-900/95' : ''} backdrop-blur-xl shadow-lg border-b border-gray-200/50 ${isDark ? 'dark:border-gray-700/50' : ''}`
        : 'bg-transparent';
    }
    return isScrolled 
      ? `shadow-lg border-b border-gray-200/50 ${isDark ? 'dark:border-gray-700/50' : ''}`
      : '';
  };

  const getHeaderStyle = () => {
    if (isTransparent) return {};
    return { backgroundColor };
  };

  return (
    <header 
      className={`fixed top-0 left-0 right-0 z-50 transition-all duration-300 ${getHeaderClasses()}`}
      style={getHeaderStyle()}
    >
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between items-center h-16">
          {/* Logo */}
          <div className="flex-shrink-0">
            <Link href={route("home")} className="flex items-center">
              <img 
                src={getImagePath(isDark ? logoLight : logoDark)} 
                alt={settings.company_name}
                className="h-8 w-auto"
                onError={(e) => {
                  e.currentTarget.style.display = 'none';
                  e.currentTarget.nextElementSibling?.classList.remove('hidden');
                }}
              />
              <span className={`hidden text-2xl font-bold ${isDark ? 'text-white' : 'text-gray-900'} transition-colors`}>
                {settings.company_name}
              </span>
            </Link>
          </div>

          {/* Desktop Navigation */}
          <nav className="hidden md:flex items-center space-x-8" role="navigation" aria-label="Main navigation">
            <Link
              href={route('home')}
              className="text-sm font-medium transition-colors relative group"
              style={{ color: navTextColor }}
              onMouseEnter={(e) => e.currentTarget.style.color = brandColor}
              onMouseLeave={(e) => e.currentTarget.style.color = navTextColor}
            >
              {t('Home')}
              <span 
                className="absolute -bottom-1 left-0 w-0 h-0.5 transition-all group-hover:w-full" 
                style={{ backgroundColor: brandColor }}
                aria-hidden="true"
              ></span>
            </Link>
            {menuItems.map((item) => (
              <Link
                key={item.name}
                href={item.href}
                className="text-sm font-medium transition-colors relative group"
                style={{ color: navTextColor }}
                onMouseEnter={(e) => e.currentTarget.style.color = brandColor}
                onMouseLeave={(e) => e.currentTarget.style.color = navTextColor}
              >
                {item.name}
                <span 
                  className="absolute -bottom-1 left-0 w-0 h-0.5 transition-all group-hover:w-full" 
                  style={{ backgroundColor: brandColor }}
                  aria-hidden="true"
                ></span>
              </Link>
            ))}
          </nav>

          {/* Auth Buttons */}
          <div className="hidden md:flex items-center gap-4">
            {auth?.user ? (
              <Link
                href={route('dashboard')}
                className="px-6 py-2.5 rounded-lg text-sm font-semibold transition-colors border"
                style={getButtonStyles(false)}
                onMouseEnter={(e) => Object.assign(e.currentTarget.style, getButtonStyles(true))}
                onMouseLeave={(e) => Object.assign(e.currentTarget.style, getButtonStyles(false))}
              >
                {t('Dashboard')}
              </Link>
            ) : (
              <>
                <Link
                  href={route('login')}
                  className="text-sm font-medium transition-colors"
                  style={{ color: navTextColor }}
                  onMouseEnter={(e) => e.currentTarget.style.color = brandColor}
                  onMouseLeave={(e) => e.currentTarget.style.color = navTextColor}
                >
                  {t('Login')}
                </Link>
                {isSaas && isUserRegistrationEnabled() && (
                  <Link
                    href={route('register')}
                    className="px-6 py-2.5 rounded-lg text-sm font-semibold transition-colors border"
                    style={getButtonStyles(false)}
                    onMouseEnter={(e) => Object.assign(e.currentTarget.style, getButtonStyles(true))}
                    onMouseLeave={(e) => Object.assign(e.currentTarget.style, getButtonStyles(false))}
                  >
                    {t('Get Started')}
                  </Link>
                )}
              </>
            )}
          </div>

          {/* Mobile menu button */}
          <div className="md:hidden">
            <button
              onClick={() => setIsMenuOpen(!isMenuOpen)}
              className={`p-2 ${isDark ? 'text-gray-300 hover:text-white hover:bg-gray-800' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'} rounded-lg transition-colors`}
              aria-label={isMenuOpen ? 'Close navigation menu' : 'Open navigation menu'}
              aria-expanded={isMenuOpen}
              aria-controls="mobile-menu"
            >
              {isMenuOpen ? <X size={20} /> : <Menu size={20} />}
            </button>
          </div>
        </div>

        {/* Mobile Navigation */}
        {isMenuOpen && (
          <div className={`md:hidden border-t ${isDark ? 'border-gray-700' : 'border-gray-200'}`} id="mobile-menu">
            <div 
              className={`px-4 py-6 space-y-4 ${isDark ? 'bg-gray-900' : 'bg-white'}`}
              style={isTransparent ? {} : { backgroundColor }}
            >
              <Link
                href={route('home')}
                className="block text-base font-medium transition-colors"
                style={{ color: navTextColor }}
                onClick={() => setIsMenuOpen(false)}
                onMouseEnter={(e) => e.currentTarget.style.color = brandColor}
                onMouseLeave={(e) => e.currentTarget.style.color = navTextColor}
              >
                {t('Home')}
              </Link>
              {menuItems.map((item) => (
                <Link
                  key={item.name}
                  href={item.href}
                  className="block text-base font-medium transition-colors"
                  style={{ color: navTextColor }}
                  onClick={() => setIsMenuOpen(false)}
                  onMouseEnter={(e) => e.currentTarget.style.color = brandColor}
                  onMouseLeave={(e) => e.currentTarget.style.color = navTextColor}
                >
                  {item.name}
                </Link>
              ))}
              <div className={`pt-4 space-y-3 border-t ${isDark ? 'border-gray-700' : 'border-gray-200'}`}>
                {auth?.user ? (
                  <Link
                    href={route('dashboard')}
                    className="block w-full text-center py-2.5 rounded-lg text-sm font-semibold transition-colors border"
                    style={getButtonStyles(false)}
                    onMouseEnter={(e) => Object.assign(e.currentTarget.style, getButtonStyles(true))}
                    onMouseLeave={(e) => Object.assign(e.currentTarget.style, getButtonStyles(false))}
                  >
                    {t('Dashboard')}
                  </Link>
                ) : (
                  <>
                    <Link
                      href={route('login')}
                      className="block w-full text-center py-2.5 text-sm font-medium transition-colors"
                      style={{ color: navTextColor }}
                      onMouseEnter={(e) => e.currentTarget.style.color = brandColor}
                      onMouseLeave={(e) => e.currentTarget.style.color = navTextColor}
                    >
                      {t('Login')}
                    </Link>
                    {isSaas && isUserRegistrationEnabled() && (
                      <Link
                        href={route('register')}
                        className="block w-full text-center py-2.5 rounded-lg text-sm font-semibold transition-colors border"
                        style={getButtonStyles(false)}
                        onMouseEnter={(e) => Object.assign(e.currentTarget.style, getButtonStyles(true))}
                        onMouseLeave={(e) => Object.assign(e.currentTarget.style, getButtonStyles(false))}
                      >
                        {t('Get Started')}
                      </Link>
                    )}
                  </>
                )}
              </div>
            </div>
          </div>
        )}
      </div>
    </header>
  );
}
