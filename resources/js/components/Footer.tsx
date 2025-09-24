import React from 'react'
import { FileText } from 'lucide-react'
export const Footer = () => {
  return (
    <footer className="bg-gray-900 py-12">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex flex-col md:flex-row items-center justify-between">
          <div className="flex items-center space-x-3 mb-6 md:mb-0">
            <div className="h-10 w-10 bg-slate-700 rounded-lg flex items-center justify-center">
              <FileText className="h-6 w-6 text-white" />
            </div>
            <span className="text-xl font-bold text-white">ResumeAI</span>
          </div>
          <div className="flex space-x-8">
            <a
              href="/privacy"
              className="text-gray-300 hover:text-gray-100 transition-colors"
            >
              Privacy
            </a>
            <a
              href="/terms"
              className="text-gray-300 hover:text-gray-100 transition-colors"
            >
              Terms
            </a>
            <a
              href="/contact"
              className="text-gray-300 hover:text-gray-100 transition-colors"
            >
              Contact
            </a>
          </div>
        </div>
        <div className="mt-8 border-t border-gray-800 pt-8">
          <p className="text-center text-gray-400">
            © 2025 ResumeAI. Powered by React & Anthropic Claude.
          </p>
        </div>
      </div>
    </footer>
  )
}
