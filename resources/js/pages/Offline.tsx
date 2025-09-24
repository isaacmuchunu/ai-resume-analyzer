import React from 'react';
import { Head } from '@inertiajs/react';
import { WifiOff, RefreshCw, Home, Upload } from 'lucide-react';
import { Button } from '@/components/ui/Button';

export default function Offline() {
    const handleRetry = () => {
        window.location.reload();
    };

    const handleGoHome = () => {
        window.location.href = '/';
    };

    const handleUpload = () => {
        window.location.href = '/resumes/upload';
    };

    return (
        <>
            <Head title="Offline - AI Resume Analyzer" />

            <div className="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 flex items-center justify-center px-4">
                <div className="max-w-md w-full">
                    {/* Offline Icon */}
                    <div className="text-center mb-8">
                        <div className="inline-flex items-center justify-center w-24 h-24 rounded-full bg-gray-200 dark:bg-gray-700 mb-6">
                            <WifiOff className="h-12 w-12 text-gray-500 dark:text-gray-400" />
                        </div>

                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                            You're Offline
                        </h1>

                        <p className="text-gray-600 dark:text-gray-400 text-center">
                            It looks like you've lost your internet connection. Don't worry,
                            some features are still available offline.
                        </p>
                    </div>

                    {/* Available Offline Features */}
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
                        <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            Available Offline
                        </h2>

                        <ul className="space-y-3">
                            <li className="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                <div className="w-2 h-2 bg-green-500 rounded-full mr-3"></div>
                                View previously loaded resumes
                            </li>
                            <li className="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                <div className="w-2 h-2 bg-green-500 rounded-full mr-3"></div>
                                Read cached analysis results
                            </li>
                            <li className="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                <div className="w-2 h-2 bg-green-500 rounded-full mr-3"></div>
                                Access settings and preferences
                            </li>
                            <li className="flex items-center text-sm text-gray-500 dark:text-gray-500">
                                <div className="w-2 h-2 bg-gray-400 rounded-full mr-3"></div>
                                Upload new resumes (requires connection)
                            </li>
                        </ul>
                    </div>

                    {/* Action Buttons */}
                    <div className="space-y-3">
                        <Button
                            onClick={handleRetry}
                            className="w-full"
                            size="lg"
                        >
                            <RefreshCw className="h-4 w-4 mr-2" />
                            Try Again
                        </Button>

                        <div className="grid grid-cols-2 gap-3">
                            <Button
                                onClick={handleGoHome}
                                variant="outline"
                                size="lg"
                            >
                                <Home className="h-4 w-4 mr-2" />
                                Home
                            </Button>

                            <Button
                                onClick={handleUpload}
                                variant="outline"
                                size="lg"
                                disabled
                            >
                                <Upload className="h-4 w-4 mr-2" />
                                Upload
                            </Button>
                        </div>
                    </div>

                    {/* Connection Status */}
                    <div className="mt-6 text-center">
                        <p className="text-xs text-gray-500 dark:text-gray-400">
                            Connection will be restored automatically when available
                        </p>

                        {/* Online/Offline Indicator */}
                        <div className="mt-2 flex items-center justify-center">
                            <div className="w-2 h-2 bg-red-500 rounded-full mr-2 animate-pulse"></div>
                            <span className="text-xs text-gray-500 dark:text-gray-400">
                                Offline
                            </span>
                        </div>
                    </div>

                    {/* Tips */}
                    <div className="mt-8 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <h3 className="text-sm font-medium text-blue-900 dark:text-blue-300 mb-2">
                            💡 Tip
                        </h3>
                        <p className="text-xs text-blue-800 dark:text-blue-400">
                            Files you upload while offline will be automatically synchronized
                            when your connection is restored.
                        </p>
                    </div>
                </div>
            </div>
        </>
    );
}