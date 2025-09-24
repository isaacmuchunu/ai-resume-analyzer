import React from 'react'
const stats = [
  {
    value: '99%',
    label: 'ATS Compatibility',
  },
  {
    value: '10M+',
    label: 'Resumes Summarized',
  },
  {
    value: '1000+',
    label: 'Organizations',
  },
  {
    value: '50+',
    label: 'Industries Supported',
  },
]
export const Stats = () => {
  return (
    <section className="py-16 bg-white">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid grid-cols-2 gap-8 md:grid-cols-4">
          {stats.map((stat, index) => (
            <div key={index} className="text-center">
              <div className="text-4xl font-extrabold text-slate-800">
                {stat.value}
              </div>
              <div className="mt-2 text-base font-medium text-gray-500">
                {stat.label}
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}
