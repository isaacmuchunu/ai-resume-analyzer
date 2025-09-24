import React from 'react'
import { Button } from './ui/Button'
import { ArrowRight, CheckCircle } from 'lucide-react'
import { Link } from '@inertiajs/react'
export const CTA = () => {
  return (
    <section className="py-28 bg-gradient-to-br from-slate-800 to-slate-900 relative overflow-hidden">
      {/* Background pattern */}
      <div className="absolute inset-0 opacity-10 pointer-events-none">
        <div className="absolute right-0 bottom-0 -mb-64 -mr-64 h-[40rem] w-[40rem] rounded-full bg-slate-700"></div>
        <div className="absolute left-0 top-0 -mt-32 -ml-64 h-[40rem] w-[40rem] rounded-full bg-slate-700"></div>
      </div>
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div className="lg:grid lg:grid-cols-12 lg:gap-20 items-center">
          <div className="lg:col-span-7">
            <h2 className="text-4xl font-extrabold text-white sm:text-5xl lg:text-6xl leading-tight">
              Transform Your Hiring Process with AI-Powered Resume Analysis
            </h2>
            <p className="mt-8 max-w-3xl text-xl text-slate-200 leading-relaxed">
              Join thousands of organizations leveraging our AI platform for
              smarter, faster resume evaluation and candidate selection.
            </p>
            <div className="mt-12 grid grid-cols-1 sm:grid-cols-2 gap-8">
              <div className="flex items-start">
                <div className="flex-shrink-0 mt-1">
                  <div className="h-8 w-8 rounded-full bg-slate-700 flex items-center justify-center">
                    <CheckCircle className="h-5 w-5 text-slate-300" />
                  </div>
                </div>
                <div className="ml-4">
                  <h3 className="text-xl font-bold text-white mb-2">
                    Reduce Hiring Time
                  </h3>
                  <p className="text-slate-300 leading-relaxed">
                    Cut screening time by up to 75% with automated resume
                    analysis and intelligent candidate matching.
                  </p>
                </div>
              </div>
              <div className="flex items-start">
                <div className="flex-shrink-0 mt-1">
                  <div className="h-8 w-8 rounded-full bg-slate-700 flex items-center justify-center">
                    <CheckCircle className="h-5 w-5 text-slate-300" />
                  </div>
                </div>
                <div className="ml-4">
                  <h3 className="text-xl font-bold text-white mb-2">
                    Improve Quality
                  </h3>
                  <p className="text-slate-300 leading-relaxed">
                    Identify top candidates with objective, AI-powered insights
                    that eliminate bias and highlight true potential.
                  </p>
                </div>
              </div>
            </div>
          </div>
          <div className="mt-16 lg:mt-0 lg:col-span-5">
            <div className="bg-white rounded-2xl shadow-2xl p-10">
              <h3 className="text-2xl font-bold text-gray-900 mb-4">
                Start Your Free Trial
              </h3>
              <p className="text-gray-600 mb-8 leading-relaxed">
                Get full access to all features for 14 days, no credit card
                required.
              </p>
              <form className="space-y-5">
                <div>
                  <label
                    htmlFor="email"
                    className="block text-sm font-semibold text-gray-700 mb-2"
                  >
                    Work Email
                  </label>
                  <input
                    type="email"
                    id="email"
                    placeholder="you@company.com"
                    className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-slate-500 focus:border-slate-500 transition-all"
                  />
                </div>
                <Link href="/register">
                  <Button
                    className="w-full shadow-lg hover:shadow-xl transition-all"
                    size="lg"
                  >
                    <span>Get Started Free</span>
                    <ArrowRight className="ml-2 h-5 w-5 transition-transform group-hover:translate-x-1" />
                  </Button>
                </Link>
              </form>
              <p className="mt-6 text-sm text-center text-gray-500">
                By signing up, you agree to our Terms and Privacy Policy
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>
  )
}
