import React from 'react'
import { Star } from 'lucide-react'
export const Testimonials = () => {
  const testimonials = [
    {
      content:
        'After using ResumeAI, I received interview calls from 3 companies within a week. The AI feedback helped me highlight my achievements in a way that really stood out.',
      author: 'Sarah Johnson',
      role: 'Marketing Manager',
      avatar: 'https://randomuser.me/api/portraits/women/23.jpg',
      company: 'Previously at Google',
    },
    {
      content:
        "The keyword optimization feature is a game-changer. My resume now passes through ATS systems with ease, and I've seen a 70% increase in response rate.",
      author: 'Michael Chen',
      role: 'Software Engineer',
      avatar: 'https://randomuser.me/api/portraits/men/54.jpg',
      company: 'Previously at Amazon',
    },
    {
      content:
        'As a career coach, I recommend ResumeAI to all my clients. The industry-specific recommendations are spot-on and save hours of manual revision work.',
      author: 'Emily Rodriguez',
      role: 'Career Coach',
      avatar: 'https://randomuser.me/api/portraits/women/45.jpg',
      company: 'Career Accelerator Inc.',
    },
  ]
  return (
    <section className="py-28 bg-gradient-to-b from-gray-50 to-gray-100 overflow-hidden relative">
      {/* Background design elements */}
      <div className="absolute inset-0 overflow-hidden pointer-events-none">
        <div className="absolute -right-40 -top-40 w-80 h-80 bg-slate-200 rounded-full opacity-30"></div>
        <div className="absolute -left-40 -bottom-40 w-80 h-80 bg-slate-200 rounded-full opacity-30"></div>
      </div>
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div className="text-center mb-20">
          <h2 className="text-3xl font-extrabold text-gray-900 sm:text-4xl lg:text-5xl">
            What Our Users Say
          </h2>
          <p className="mt-6 max-w-2xl mx-auto text-xl text-gray-600 leading-relaxed">
            Thousands of professionals have improved their job search with
            ResumeAI
          </p>
        </div>
        <div className="grid gap-10 md:grid-cols-2 lg:grid-cols-3">
          {testimonials.map((testimonial, index) => (
            <div
              key={index}
              className="bg-white p-10 rounded-2xl shadow-xl border border-gray-100 flex flex-col transform transition-all duration-300 hover:-translate-y-2 hover:shadow-2xl"
            >
              <div className="flex mb-6">
                {[...Array(5)].map((_, i) => (
                  <Star
                    key={i}
                    className="h-5 w-5 text-yellow-400 fill-current mr-1"
                  />
                ))}
              </div>
              <p className="text-gray-700 text-lg leading-relaxed flex-grow mb-8 italic">
                "{testimonial.content}"
              </p>
              <div className="flex items-center">
                <img
                  src={testimonial.avatar}
                  alt={testimonial.author}
                  className="h-14 w-14 rounded-full mr-4 object-cover ring-2 ring-gray-100"
                />
                <div>
                  <h4 className="font-bold text-gray-900 text-lg">
                    {testimonial.author}
                  </h4>
                  <div className="text-sm text-gray-600 font-medium">
                    {testimonial.role}
                  </div>
                  <div className="text-sm text-gray-500">
                    {testimonial.company}
                  </div>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}
