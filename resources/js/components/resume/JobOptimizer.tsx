import React, { useState, useCallback } from 'react';
import { 
    ATSSuggestion, 
    LiveScores, 
    JobOptimizationResult 
} from '@/types/resume';

interface JobOptimizerProps {
    resumeId: string;
    onOptimizationComplete: (result: JobOptimizationResult) => void;
    onClose: () => void;
    isVisible: boolean;
}

export function JobOptimizer({ 
    resumeId, 
    onOptimizationComplete, 
    onClose, 
    isVisible 
}: JobOptimizerProps) {
    const [jobDescription, setJobDescription] = useState('');
    const [jobTitle, setJobTitle] = useState('');
    const [isOptimizing, setIsOptimizing] = useState(false);
    const [optimizationResult, setOptimizationResult] = useState<JobOptimizationResult | null>(null);
    const [error, setError] = useState<string | null>(null);

    const handleOptimization = useCallback(async () => {
        if (!jobDescription.trim()) {
            setError('Please enter a job description.');
            return;
        }

        setIsOptimizing(true);
        setError(null);

        try {
            // Note: This would use the ResumeAnalysisAPI service
            // For now, using a mock implementation
            const mockResult: JobOptimizationResult = {
                job_title: jobTitle || 'Untitled Position',
                match_score: 78,
                suggestions: [],
                live_scores: {
                    overall: 78,
                    ats_compatibility: 85,
                    keyword_density: 70,
                    format_score: 82,
                    content_quality: 75,
                    improvement_potential: 22
                },
                keyword_gaps: ['Python', 'Machine Learning', 'React'],
                missing_skills: ['Docker', 'Kubernetes', 'AWS'],
                optimization_summary: {
                    critical_changes: 3,
                    suggested_changes: 8,
                    potential_score_increase: 15
                }
            };

            setOptimizationResult(mockResult);
            onOptimizationComplete(mockResult);
        } catch (err) {
            setError('Failed to optimize for job. Please try again.');
            console.error('Job optimization failed:', err);
        } finally {
            setIsOptimizing(false);
        }
    }, [jobDescription, jobTitle, resumeId, onOptimizationComplete]);

    const handleClearResults = () => {
        setOptimizationResult(null);
        setError(null);
    };

    if (!isVisible) return null;

    return (
        <div className="bg-white shadow-sm rounded-lg border border-gray-200 mb-6">
            <div className="border-b border-gray-200 px-6 py-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h3 className="text-lg font-medium text-gray-900">
                            Job-Specific Optimization
                        </h3>
                        <p className="text-sm text-gray-500 mt-1">
                            Optimize your resume for a specific job posting
                        </p>
                    </div>
                    <button
                        onClick={onClose}
                        className="text-gray-400 hover:text-gray-500 focus:outline-none focus:text-gray-500"
                    >
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <div className="p-6">
                <div className="space-y-6">
                    {/* Job Title Input */}
                    <div>
                        <label htmlFor="job-title" className="block text-sm font-medium text-gray-700 mb-2">
                            Job Title (Optional)
                        </label>
                        <input
                            type="text"
                            id="job-title"
                            className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            placeholder="e.g., Senior Software Engineer"
                            value={jobTitle}
                            onChange={(e) => setJobTitle(e.target.value)}
                        />
                    </div>

                    {/* Job Description Input */}
                    <div>
                        <label htmlFor="job-description" className="block text-sm font-medium text-gray-700 mb-2">
                            Job Description <span className="text-red-500">*</span>
                        </label>
                        <textarea
                            id="job-description"
                            rows={8}
                            className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            placeholder="Paste the complete job description here to get targeted optimization suggestions..."
                            value={jobDescription}
                            onChange={(e) => setJobDescription(e.target.value)}
                        />
                        <p className="text-xs text-gray-500 mt-1">
                            Include requirements, responsibilities, and preferred qualifications for best results
                        </p>
                    </div>

                    {/* Error Message */}
                    {error && (
                        <div className="bg-red-50 border border-red-200 rounded-md p-4">
                            <div className="flex">
                                <svg className="w-5 h-5 text-red-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p className="text-sm text-red-800">{error}</p>
                            </div>
                        </div>
                    )}

                    {/* Optimization Results */}
                    {optimizationResult && (
                        <div className="bg-blue-50 border border-blue-200 rounded-md p-4">
                            <div className="flex items-start justify-between mb-4">
                                <h4 className="text-lg font-medium text-blue-900">
                                    Optimization Results
                                </h4>
                                <button
                                    onClick={handleClearResults}
                                    className="text-blue-600 hover:text-blue-700 text-sm font-medium"
                                >
                                    Clear Results
                                </button>
                            </div>
                            
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                <div className="text-center">
                                    <div className="text-2xl font-bold text-blue-600">
                                        {optimizationResult.match_score}%
                                    </div>
                                    <div className="text-sm text-blue-800">Job Match Score</div>
                                </div>
                                <div className="text-center">
                                    <div className="text-2xl font-bold text-orange-600">
                                        +{optimizationResult.optimization_summary.potential_score_increase}
                                    </div>
                                    <div className="text-sm text-orange-800">Potential Increase</div>
                                </div>
                                <div className="text-center">
                                    <div className="text-2xl font-bold text-green-600">
                                        {optimizationResult.optimization_summary.suggested_changes}
                                    </div>
                                    <div className="text-sm text-green-800">Suggestions</div>
                                </div>
                            </div>

                            {/* Missing Keywords */}
                            {optimizationResult.keyword_gaps.length > 0 && (
                                <div className="mb-4">
                                    <h5 className="font-medium text-gray-900 mb-2">Missing Keywords:</h5>
                                    <div className="flex flex-wrap gap-2">
                                        {optimizationResult.keyword_gaps.map((keyword, index) => (
                                            <span
                                                key={index}
                                                className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800"
                                            >
                                                {keyword}
                                            </span>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {/* Missing Skills */}
                            {optimizationResult.missing_skills.length > 0 && (
                                <div className="mb-4">
                                    <h5 className="font-medium text-gray-900 mb-2">Missing Skills:</h5>
                                    <div className="flex flex-wrap gap-2">
                                        {optimizationResult.missing_skills.map((skill, index) => (
                                            <span
                                                key={index}
                                                className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800"
                                            >
                                                {skill}
                                            </span>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>
                    )}

                    {/* Action Buttons */}
                    <div className="flex justify-end space-x-3">
                        <button
                            onClick={onClose}
                            className="px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            Close
                        </button>
                        <button
                            onClick={handleOptimization}
                            disabled={!jobDescription.trim() || isOptimizing}
                            className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {isOptimizing ? (
                                <>
                                    <svg className="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Analyzing...
                                </>
                            ) : (
                                <>
                                    <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                    Optimize Resume
                                </>
                            )}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}

// Quick optimization tips component
export function OptimizationTips() {
    return (
        <div className="bg-yellow-50 border border-yellow-200 rounded-md p-4 mb-6">
            <div className="flex items-start">
                <svg className="w-5 h-5 text-yellow-400 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                    <h4 className="text-sm font-medium text-yellow-800 mb-2">
                        💡 Optimization Tips
                    </h4>
                    <ul className="text-sm text-yellow-700 space-y-1">
                        <li>• Include the complete job description for best results</li>
                        <li>• Pay attention to required vs. preferred qualifications</li>
                        <li>• Look for industry-specific keywords and technical terms</li>
                        <li>• Consider the company culture and values mentioned</li>
                    </ul>
                </div>
            </div>
        </div>
    );
}