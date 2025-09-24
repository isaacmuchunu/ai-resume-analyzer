import React from 'react'
import { Upload, Search, FileCheck, Award } from 'lucide-react'
export const HowItWorks = () => {
  const steps = [
    {
      icon: <Upload className="h-7 w-7 text-slate-700" />,
      title: 'Upload Your Resume',
      description:
        'Upload your current resume in PDF, DOCX, or TXT format. Our system accepts all standard resume file types.',
    },
    {
      icon: <Search className="h-7 w-7 text-slate-700" />,
      title: 'AI Analysis',
      description:
        'Our advanced AI analyzes your resume against industry standards, job descriptions, and ATS requirements.',
    },
    {
      icon: <FileCheck className="h-7 w-7 text-slate-700" />,
      title: 'Get Detailed Feedback',
      description:
        "Receive personalized recommendations to improve your resume's content, format, and keywords.",
    },
    {
      icon: <Award className="h-7 w-7 text-slate-700" />,
      title: 'Land More Interviews',
      description:
        'Implement our suggestions to create an optimized resume that stands out to both ATS and hiring managers.',
    },
  ]
  return (
    <section className="py-24 bg-white">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center">
          <h2 className="text-3xl font-extrabold text-gray-900 sm:text-4xl">
            How It Works
          </h2>
          <p className="mt-4 max-w-2xl mx-auto text-xl text-gray-500">
            Our simple four-step process helps you optimize your resume for
            success
          </p>
        </div>
        <div className="mt-16 relative">
          {/* Connection line */}
          <div className="hidden lg:block absolute top-24 left-0 w-full h-1 bg-gray-100"></div>
          <div className="grid gap-10 md:grid-cols-2 lg:grid-cols-4">
            {steps.map((step, index) => (
              <div key={index} className="relative">
                <div className="flex flex-col items-center">
                  <div className="flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 mb-6 relative z-10">
                    {step.icon}
                    <div className="absolute -top-2 -right-2 h-6 w-6 rounded-full bg-slate-700 text-white flex items-center justify-center text-sm font-medium">
                      {index + 1}
                    </div>
                  </div>
                  <h3 className="text-xl font-bold text-gray-900 mb-2">
                    {step.title}
                  </h3>
                  <p className="text-center text-gray-500">
                    {step.description}
                  </p>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </section>
  )
}
