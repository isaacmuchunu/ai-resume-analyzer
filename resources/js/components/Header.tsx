import React, { useState } from 'react'
import { FileText, Menu, X } from 'lucide-react'
import { Button } from './ui/Button'
import { Link } from '@inertiajs/react'
export const Header = () => {
  const [isMenuOpen, setIsMenuOpen] = useState(false)
  return (
    <header className="bg-white sticky top-0 z-50 shadow-sm">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
        <div className="flex items-center space-x-3">
          <div className="h-10 w-10 bg-slate-800 rounded-lg flex items-center justify-center">
            <FileText className="h-6 w-6 text-white" />
          </div>
          <span className="text-2xl font-bold text-gray-900">ResumeAI</span>
        </div>
        {/* Mobile menu button */}
        <div className="md:hidden">
          <button
            onClick={() => setIsMenuOpen(!isMenuOpen)}
            className="text-gray-600 hover:text-gray-900 focus:outline-none"
          >
            {isMenuOpen ? (
              <X className="h-6 w-6" />
            ) : (
              <Menu className="h-6 w-6" />
            )}
          </button>
        </div>
        {/* Desktop navigation */}
        <nav className="hidden md:flex items-center space-x-8">
          <a
            href="#features"
            className="text-gray-600 hover:text-slate-800 font-medium transition-colors"
          >
            Features
          </a>
          <a
            href="#pricing"
            className="text-gray-600 hover:text-slate-800 font-medium transition-colors"
          >
            Pricing
          </a>
          <Link href="/login">
            <Button variant="ghost" className="text-gray-600">
              Sign In
            </Button>
          </Link>
          <Link href="/register">
            <Button>
              Get Started
            </Button>
          </Link>
        </nav>
      </div>
      {/* Mobile navigation */}
      {isMenuOpen && (
        <div className="md:hidden bg-white border-t border-gray-200">
          <div className="px-2 pt-2 pb-3 space-y-1">
            <a
              href="#features"
              className="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-slate-800 hover:bg-gray-50"
              onClick={() => setIsMenuOpen(false)}
            >
              Features
            </a>
            <a
              href="#pricing"
              className="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-slate-800 hover:bg-gray-50"
              onClick={() => setIsMenuOpen(false)}
            >
              Pricing
            </a>
            <Link
              href="/login"
              className="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-slate-800 hover:bg-gray-50"
              onClick={() => setIsMenuOpen(false)}
            >
              Sign In
            </Link>
            <div className="px-3 py-2">
              <Link href="/register">
                <Button className="w-full">
                  Get Started
                </Button>
              </Link>
            </div>
          </div>
        </div>
      )}
    </header>
  )
}
