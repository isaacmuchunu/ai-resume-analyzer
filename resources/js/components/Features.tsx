import React from 'react'
import { Zap, Users, Target, Shield, BarChart3, Globe } from 'lucide-react'
const features = [
  {
    icon: <Zap className="h-6 w-6 text-slate-700" />,
    title: 'AI-Powered Insights',
    description:
      'Powered by Anthropic Claude, our platform delivers concise summaries and actionable resume feedback.',
  },
  {
    icon: <Users className="h-6 w-6 text-slate-700" />,
    title: 'Multi-Tenant SaaS',
    description:
      'Built for organizations with full tenant isolation, custom branding, and white-label solutions.',
  },
  {
    icon: <Target className="h-6 w-6 text-slate-700" />,
    title: 'ATS Optimization',
    description:
      '99% compatibility with major ATS systems ensures your resume passes automated screening.',
  },
  {
    icon: <Shield className="h-6 w-6 text-slate-700" />,
    title: 'Enterprise Security',
    description:
      'SOC 2 compliant with tenant-specific encryption for robust data protection.',
  },
  {
    icon: <BarChart3 className="h-6 w-6 text-slate-700" />,
    title: 'Real-Time Analytics',
    description:
      'Track success with detailed analytics, benchmarking, and ROI measurement tools.',
  },
  {
    icon: <Globe className="h-6 w-6 text-slate-700" />,
    title: 'Global Scalability',
    description:
      'Cloud-native architecture supports unlimited users and seamless scaling.',
  },
]
export const Features = () => {
  return (
    <section id="features" className="py-28 bg-white relative">
      {/* Background pattern */}
      <div className="absolute inset-0 bg-slate-50 opacity-50 pointer-events-none">
        <div
          className="absolute inset-0"
          style={{
            backgroundImage:
              'radial-gradient(circle at 25px 25px, #eee 2%, transparent 0%)',
            backgroundSize: '50px 50px',
          }}
        ></div>
      </div>
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div className="text-center mb-20">
          <h2 className="text-3xl font-extrabold text-gray-900 sm:text-4xl lg:text-5xl">
            Why Choose ResumeAI?
          </h2>
          <p className="mt-6 max-w-2xl mx-auto text-xl text-gray-600 leading-relaxed">
            Our platform delivers unmatched AI-driven insights, scalability, and
            security to surpass traditional tools.
          </p>
        </div>
        <div className="mt-20 grid gap-10 md:grid-cols-2 lg:grid-cols-3">
          {features.map((feature, index) => (
            <div
              key={index}
              className="relative p-8 bg-white rounded-xl border border-gray-100 shadow-lg hover:shadow-xl transition-all duration-300 group"
            >
              <div className="h-14 w-14 bg-slate-100 rounded-xl flex items-center justify-center mb-6 group-hover:bg-slate-200 transition-colors">
                {feature.icon}
              </div>
              <h3 className="text-xl font-bold text-gray-900 mb-3">
                {feature.title}
              </h3>
              <p className="text-gray-600 leading-relaxed">
                {feature.description}
              </p>
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}
