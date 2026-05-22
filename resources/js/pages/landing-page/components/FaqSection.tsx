import React, { useState } from 'react';
import { ChevronDown, ChevronUp } from 'lucide-react';
import { usePage } from '@inertiajs/react';
import { getCookie, isDemoMode } from '@/utils/helpers';

interface Faq {
  id: number;
  question: string;
  answer: string;
}

interface FaqSectionProps {
  brandColor?: string;
  faqs: Faq[];
  settings?: any;
  sectionData?: {
    title?: string;
    subtitle?: string;
    cta_text?: string;
    button_text?: string;
    default_faqs?: Array<{
      question: string;
      answer: string;
    }>;
  };
}

export default function FaqSection({ faqs, settings, sectionData, brandColor = '#3b82f6' }: FaqSectionProps) {
  const [openFaq, setOpenFaq] = useState<number | null>(null);
  const { props } = usePage();
  const isDemo = isDemoMode(props);
  
  let themeMode = 'light';
  if (isDemo) {
    const themeSettings = getCookie('themeSettings');
    if (themeSettings) {
      try {
        const parsed = JSON.parse(themeSettings);
        themeMode = parsed.appearance || 'light';
      } catch {
        themeMode = 'light';
      }
    }
  } else {
    themeMode = (props as any).globalSettings?.themeMode || 'light';
  }
  
  const isDark = themeMode === 'dark';

  // Default FAQs if none provided
  const defaultFaqs = [
    {
      id: 1,
      question: 'How does HRM work?',
      answer: 'HRM is an all-in-one HR platform that helps you manage employees, payroll, attendance, recruitment, and performance efficiently.'
    },
    {
      id: 2,
      question: 'Can I automate payroll and leave tracking?',
      answer: 'Yes! HRM allows you to automate payroll calculations, generate payslips, and track employee leaves and attendance seamlessly.'
    },
    {
      id: 3,
      question: 'Is my employee data secure?',
      answer: 'Absolutely. HRM uses enterprise-grade security measures to keep all sensitive HR data safe and confidential.'
    },
    {
      id: 4,
      question: 'Can I manage recruitment and onboarding?',
      answer: 'Yes, HRM provides applicant tracking, interview management, and digital onboarding tools to simplify hiring.'
    },
    {
      id: 5,
      question: 'Does HRM support performance evaluations?',
      answer: 'Yes, you can set goals, track KPIs, and run performance reviews directly within the platform.'
    },
    {
      id: 6,
      question: 'Can HRM generate HR reports?',
      answer: 'HRM offers advanced analytics and reporting features to give insights on attendance, payroll, and workforce performance.'
    },
    {
      id: 7,
      question: 'What plans are available and can I upgrade anytime?',
      answer: 'We offer flexible plans for different team sizes. You can start with the free plan and upgrade as your organization grows.'
    },

  ];

  const backendFaqs = sectionData?.faqs?.map((faq, index) => ({
    id: index + 1,
    ...faq
  })) || defaultFaqs;

  const displayFaqs = faqs.length > 0 ? faqs : backendFaqs;

  const toggleFaq = (id: number) => {
    setOpenFaq(openFaq === id ? null : id);
  };

  return (
    <section className={`py-12 sm:py-16 lg:py-20 ${isDark ? 'bg-gray-900' : 'bg-white'}`}>
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center mb-8 sm:mb-12 lg:mb-16">
          <h2 className={`text-3xl md:text-4xl font-bold ${isDark ? 'text-white' : 'text-gray-900'} mb-4`}>
            {sectionData?.title || 'Frequently Asked Questions'}
          </h2>
          <p className={`text-lg ${isDark ? 'text-gray-300' : 'text-gray-600'} leading-relaxed font-medium`}>
            {sectionData?.subtitle || 'Got questions? We\'ve got answers. If you can\'t find what you\'re looking for, feel free to contact our support team.'}
          </p>
        </div>

        <div className="space-y-2 sm:space-y-3">
          {displayFaqs.map((faq) => (
            <div
              key={faq.id}
              className={`${isDark ? 'bg-gray-800 border-gray-700' : 'bg-gray-50 border-gray-200'} border rounded-lg`}
            >
              <button
                onClick={() => toggleFaq(faq.id)}
                className={`w-full px-6 py-4 text-left flex justify-between items-center ${isDark ? 'hover:bg-gray-700' : 'hover:bg-gray-100'} transition-colors`}
                aria-expanded={openFaq === faq.id}
                aria-controls={`faq-answer-${faq.id}`}
                aria-describedby={`faq-question-${faq.id}`}
              >
                <h3 className={`text-lg font-semibold ${isDark ? 'text-white' : 'text-gray-900'} pr-4`} id={`faq-question-${faq.id}`}>
                  {faq.question}
                </h3>
                {openFaq === faq.id ? (
                  <ChevronUp className={`w-5 h-5 ${isDark ? 'text-gray-400' : 'text-gray-600'} flex-shrink-0`} aria-hidden="true" />
                ) : (
                  <ChevronDown className={`w-5 h-5 ${isDark ? 'text-gray-400' : 'text-gray-600'} flex-shrink-0`} aria-hidden="true" />
                )}
              </button>

              {openFaq === faq.id && (
                <div className={`px-6 pb-4 border-t ${isDark ? 'border-gray-700' : 'border-gray-200'}`} id={`faq-answer-${faq.id}`} role="region" aria-labelledby={`faq-question-${faq.id}`}>
                  <p className={`${isDark ? 'text-gray-300' : 'text-gray-600'} leading-relaxed pt-4`}>
                    {faq.answer}
                  </p>
                </div>
              )}
            </div>
          ))}
        </div>

        {(sectionData?.cta_text || sectionData?.button_text) && (
          <div className="text-center mt-8 sm:mt-12">
            <p className={`${isDark ? 'text-gray-300' : 'text-gray-600'} mb-4`}>
              {sectionData?.cta_text || 'Still have questions?'}
            </p>
            <a
              href="#contact"
              className="inline-flex items-center gap-2 px-6 py-3 rounded-lg transition-all font-semibold border"
              style={{ backgroundColor: brandColor, color: 'white', borderColor: brandColor }}
              onMouseEnter={(e) => {
                e.currentTarget.style.backgroundColor = 'white';
                e.currentTarget.style.color = brandColor;
              }}
              onMouseLeave={(e) => {
                e.currentTarget.style.backgroundColor = brandColor;
                e.currentTarget.style.color = 'white';
              }}
            >
              {sectionData?.button_text || 'Contact Support'}
            </a>
          </div>
        )}
      </div>
    </section>
  );
}