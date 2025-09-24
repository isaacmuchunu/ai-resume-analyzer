import React, { useState } from 'react'
import { ChevronDown, ChevronUp } from 'lucide-react'
export const FAQ = () => {
  const [openIndex, setOpenIndex] = useState(0)
  const faqs = [
    {
      question: 'How does ResumeAI improve my chances of getting interviews?',
      answer:
        'ResumeAI analyzes your resume against industry standards and job descriptions, identifying missing keywords, improving formatting, and highlighting achievements in a way that appeals to both ATS systems and human recruiters. Our users report a 40-70% increase in interview callbacks.',
    },
    {
      question: 'Is my resume data secure?',
      answer:
        "Yes, we take security very seriously. Your data is encrypted both in transit and at rest. We're SOC 2 compliant and never share your personal information with third parties. Your resume data is only used to provide you with analysis and recommendations.",
    },
    {
      question: 'How accurate is the AI analysis?',
      answer:
        'Our AI model has been trained on millions of resumes and thousands of job descriptions across various industries. It achieves over 95% accuracy in identifying key improvement areas and has been validated by professional recruiters and hiring managers.',
    },
    {
      question: 'Can I use ResumeAI for different job applications?',
      answer:
        'Absolutely! With our Pro plan, you can analyze your resume against specific job descriptions to tailor your application for each position. This targeted approach significantly increases your chances of passing ATS screening and landing interviews.',
    },
    {
      question: 'How long does the analysis take?',
      answer:
        "The initial analysis takes just 30-60 seconds. For more comprehensive reports with industry-specific recommendations, it may take up to 2 minutes. You'll receive instant feedback on ATS compatibility and basic improvements, followed by detailed suggestions.",
    },
  ]
  return (
    <section className="py-24 bg-white">
      <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center mb-16">
          <h2 className="text-3xl font-extrabold text-gray-900 sm:text-4xl">
            Frequently Asked Questions
          </h2>
          <p className="mt-4 text-xl text-gray-500">
            Everything you need to know about ResumeAI
          </p>
        </div>
        <div className="space-y-6">
          {faqs.map((faq, index) => (
            <div
              key={index}
              className={`border rounded-xl overflow-hidden transition-all ${openIndex === index ? 'border-slate-300 shadow-sm' : 'border-gray-200'}`}
            >
              <button
                onClick={() => setOpenIndex(openIndex === index ? -1 : index)}
                className="w-full flex items-center justify-between p-6 text-left"
              >
                <h3 className="text-lg font-medium text-gray-900">
                  {faq.question}
                </h3>
                {openIndex === index ? (
                  <ChevronUp className="h-5 w-5 text-slate-700" />
                ) : (
                  <ChevronDown className="h-5 w-5 text-gray-500" />
                )}
              </button>
              <div
                className={`px-6 overflow-hidden transition-all ${openIndex === index ? 'max-h-96 pb-6' : 'max-h-0'}`}
              >
                <p className="text-gray-600">{faq.answer}</p>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}
