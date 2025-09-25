import React from 'react'
import { Button } from './ui/Button'
import { Upload, Lock } from 'lucide-react'
import { Link } from '@inertiajs/react'
export const Hero = () => {
  return (
    <section className="relative min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 overflow-hidden">
      {/* Background decorative elements */}
      <div className="absolute inset-0">
        <img
          src="https://s3.envato.com/files/125379174/Typing%20PC%20Laptop%20Keyboard%20Slow%20Motion.jpg"
          alt="Background decoration"
          className="object-cover w-full h-full opacity-50"
        />
      </div>
      <div className="relative max-w-7xl mx-auto px-6 py-20">
        <div className="grid lg:grid-cols-2 gap-12 items-center">
          {/* Left Content */}
          <div className="space-y-8">
            <div className="space-y-4">
              <div className="inline-block px-3 py-1 bg-blue-100 text-blue-800 text-sm font-medium rounded-full">
                RESUME CHECKER
              </div>
              <h1 className="text-5xl lg:text-6xl font-bold text-gray-900 leading-tight">
                Is your resume{' '}
                <span className="text-gray-600">good enough?</span>
              </h1>
              <p className="text-xl text-gray-600 leading-relaxed">
                A free and fast AI resume checker doing 16 crucial checks to
                ensure your resume is ready to perform and get you interview
                callbacks.
              </p>
            </div>
            {/* File Upload Area */}
            <div className="bg-white border-2 border-dashed border-slate-300 rounded-lg p-8 text-center space-y-4">
              <div className="space-y-2">
                <p className="text-gray-700">
                  Drop your resume here or choose a file.
                </p>
                <p className="text-sm text-gray-500">
                  PDF & DOCX only. Max 2MB file size.
                </p>
              </div>
              <Link href="/register">
                <Button className="bg-slate-800 hover:bg-slate-900 text-white px-8 py-3 rounded-md font-medium transition-colors border-0">
                  <Upload className="w-5 h-5 mr-2" />
                  Upload Your Resume
                </Button>
              </Link>
              <div className="flex items-center justify-center space-x-2 text-sm text-gray-500">
                <Lock className="w-4 h-4" />
                <span>Privacy guaranteed</span>
              </div>
            </div>
          </div>
          {/* Right side content placeholder - keeping the grid layout but leaving empty */}
          <div className="hidden lg:block"></div>
        </div>
      </div>
    </section>
  )
}
