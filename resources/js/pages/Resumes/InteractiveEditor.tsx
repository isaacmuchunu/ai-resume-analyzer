import React, { useState, useEffect, useCallback } from 'react';
import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { SectionEditor } from '@/components/resume/SectionEditor';
import { ATSSuggestionPanel } from '@/components/resume/ATSSuggestionPanel';
import { LiveScorePanel } from '@/components/resume/LiveScorePanel';
import { ResumeAnalysisAPI } from '@/services/ResumeAnalysisAPI';
import { 
    Resume, 
    ResumeSection, 
    ATSSuggestion, 
    LiveScores, 
    AnalysisResult
} from '@/types/resume';

interface InteractiveEditorProps {
    resume: Resume;
    sections: ResumeSection[];
    suggestions: ATSSuggestion[];
    initialScores: LiveScores;
}

export default function InteractiveEditor({ 
    resume: initialResume, 
    sections: initialSections, 
    suggestions: initialSuggestions,
    initialScores 
}: InteractiveEditorProps) {
    const { auth } = usePage().props as any;
    
    // State management
    const [resume, setResume] = useState<Resume>(initialResume);
    const [sections, setSections] = useState<ResumeSection[]>(initialSections);
    const [suggestions, setSuggestions] = useState<ATSSuggestion[]>(initialSuggestions);
    const [scores, setScores] = useState<LiveScores>(initialScores);
    const [isAnalyzing, setIsAnalyzing] = useState(false);
    const [selectedSectionId, setSelectedSectionId] = useState<string | null>(
        sections.length > 0 ? sections[0].id : null
    );
    const [jobDescription, setJobDescription] = useState<string>('');
    const [showJobOptimizer, setShowJobOptimizer] = useState(false);
    const [hasUnsavedChanges, setHasUnsavedChanges] = useState(false);

    // Get the currently selected section
    const selectedSection = sections.find(section => section.id === selectedSectionId) || null;

    // Debounced analysis function
    const debouncedAnalysis = useCallback(
        debounce(async (sectionId: string, content: any) => {
            if (!sectionId || !content) return;
            
            try {
                setIsAnalyzing(true);
                const result: AnalysisResult = await ResumeAnalysisAPI.analyzeSectionForATS(
                    resume.id,
                    sectionId,
                    content,
                    jobDescription || undefined
                );
                
                // Update section scores
                setSections(prev => prev.map(section => 
                    section.id === sectionId 
                        ? { ...section, ats_score: result.section_score }
                        : section
                ));
                
                // Update suggestions
                setSuggestions(prev => [
                    ...prev.filter(s => s.section_id !== sectionId),
                    ...result.suggestions
                ]);
                
                // Update live scores
                setScores(result.live_scores);
                
            } catch (error) {
                console.error('Analysis failed:', error);
                // Using basic notification instead of toast
                console.warn('Failed to analyze section. Please try again.');
            } finally {
                setIsAnalyzing(false);
            }
        }, 800),
        [resume.id, jobDescription]
    );

    // Handle section content changes
    const handleSectionChange = useCallback((sectionId: string, newContent: any) => {
        setSections(prev => prev.map(section => 
            section.id === sectionId 
                ? { ...section, content: newContent }
                : section
        ));
        
        setHasUnsavedChanges(true);
        debouncedAnalysis(sectionId, newContent);
    }, [debouncedAnalysis]);

    // Handle suggestion application
    const handleApplySuggestion = useCallback(async (suggestionId: string) => {
        try {
            const suggestion = suggestions.find(s => s.id === suggestionId);
            if (!suggestion) return;

            // Apply the suggestion to the section
            if (suggestion.section_id) {
                const targetSection = sections.find(s => s.id === suggestion.section_id);
                if (targetSection) {
                    const updatedContent = applySuggestionToContent(
                        targetSection.content,
                        suggestion
                    );
                    handleSectionChange(suggestion.section_id, updatedContent);
                }
            }

            // Mark suggestion as applied
            const response = await axios.post(`/api/resumes/${resume.id}/suggestions/${suggestionId}/apply`);
            
            setSuggestions(prev => prev.map(s => 
                s.id === suggestionId 
                    ? { ...s, status: 'applied', applied_at: new Date().toISOString() }
                    : s
            ));
            
            toast.success('Suggestion applied successfully!');
        } catch (error) {
            console.error('Failed to apply suggestion:', error);
            toast.error('Failed to apply suggestion. Please try again.');
        }
    }, [suggestions, sections, resume.id, handleSectionChange]);

    // Handle suggestion dismissal
    const handleDismissSuggestion = useCallback(async (suggestionId: string) => {
        try {
            await axios.post(`/api/resumes/${resume.id}/suggestions/${suggestionId}/dismiss`);
            
            setSuggestions(prev => prev.map(s => 
                s.id === suggestionId 
                    ? { ...s, status: 'dismissed' }
                    : s
            ));
            
            toast.success('Suggestion dismissed.');
        } catch (error) {
            console.error('Failed to dismiss suggestion:', error);
            toast.error('Failed to dismiss suggestion. Please try again.');
        }
    }, [resume.id]);

    // Helper function to apply suggestion to content
    const applySuggestionToContent = (content: any, suggestion: ATSSuggestion): any => {
        if (typeof content === 'string') {
            return content.replace(suggestion.current_text, suggestion.suggested_text);
        }
        
        if (Array.isArray(content)) {
            return content.map(item => 
                typeof item === 'string' 
                    ? item.replace(suggestion.current_text, suggestion.suggested_text)
                    : item
            );
        }
        
        if (typeof content === 'object' && content !== null) {
            const updated = { ...content };
            Object.keys(updated).forEach(key => {
                if (typeof updated[key] === 'string') {
                    updated[key] = updated[key].replace(suggestion.current_text, suggestion.suggested_text);
                }
            });
            return updated;
        }
        
        return content;
    };

    // Save resume sections
    const handleSaveResume = useCallback(async () => {
        try {
            await axios.put(`/api/resumes/${resume.id}/sections`, {
                sections: sections.map(section => ({
                    id: section.id,
                    section_type: section.section_type,
                    title: section.title,
                    content: section.content,
                    order_index: section.order_index
                }))
            });
            
            setHasUnsavedChanges(false);
            toast.success('Resume saved successfully!');
        } catch (error) {
            console.error('Failed to save resume:', error);
            toast.error('Failed to save resume. Please try again.');
        }
    }, [resume.id, sections]);

    // Job-specific optimization
    const handleJobOptimization = useCallback(async () => {
        if (!jobDescription.trim()) {
            toast.error('Please enter a job description first.');
            return;
        }

        try {
            setIsAnalyzing(true);
            const response = await axios.post(`/api/resumes/${resume.id}/optimize-for-job`, {
                job_description: jobDescription
            });

            const result = response.data;
            setSuggestions(prev => [...prev, ...result.suggestions]);
            setScores(result.live_scores);
            
            toast.success(`Found ${result.suggestions.length} optimization suggestions!`);
        } catch (error) {
            console.error('Job optimization failed:', error);
            toast.error('Failed to optimize for job. Please try again.');
        } finally {
            setIsAnalyzing(false);
        }
    }, [resume.id, jobDescription]);

    // Auto-save effect
    useEffect(() => {
        if (hasUnsavedChanges) {
            const autoSave = setTimeout(() => {
                handleSaveResume();
            }, 10000); // Auto-save after 10 seconds of inactivity

            return () => clearTimeout(autoSave);
        }
    }, [hasUnsavedChanges, handleSaveResume]);

    // Keyboard shortcuts
    useEffect(() => {
        const handleKeyDown = (e: KeyboardEvent) => {
            if (e.ctrlKey || e.metaKey) {
                if (e.key === 's') {
                    e.preventDefault();
                    handleSaveResume();
                }
            }
        };

        document.addEventListener('keydown', handleKeyDown);
        return () => document.removeEventListener('keydown', handleKeyDown);
    }, [handleSaveResume]);

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            Interactive Resume Editor
                        </h2>
                        <p className="text-sm text-gray-600 mt-1">
                            {resume.title} • ATS Score: {scores.overall}/100
                        </p>
                    </div>
                    <div className="flex items-center space-x-3">
                        {hasUnsavedChanges && (
                            <span className="text-sm text-amber-600 font-medium">
                                Unsaved changes
                            </span>
                        )}
                        <button
                            onClick={() => setShowJobOptimizer(!showJobOptimizer)}
                            className="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                        >
                            <svg className="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6.294a2 2.0 002 2h1a3 3 0 01-3 3h-6a3 3 0 01-3-3V10a2 2 0 012-2h1V6a2 2 0 012-2z" />
                            </svg>
                            Job Optimizer
                        </button>
                        <button
                            onClick={handleSaveResume}
                            disabled={!hasUnsavedChanges}
                            className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            <svg className="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                            Save Resume
                        </button>
                    </div>
                </div>
            }
        >
            <Head title={`Edit ${resume.title} - Interactive Editor`} />

            <div className="py-6">
                <div className="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Job Optimizer Panel */}
                    {showJobOptimizer && (
                        <div className="bg-white shadow-sm rounded-lg border border-gray-200 mb-6 p-6">
                            <h3 className="text-lg font-medium text-gray-900 mb-4">
                                Job-Specific Optimization
                            </h3>
                            <div className="space-y-4">
                                <div>
                                    <label htmlFor="job-description" className="block text-sm font-medium text-gray-700 mb-2">
                                        Job Description
                                    </label>
                                    <textarea
                                        id="job-description"
                                        rows={6}
                                        className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        placeholder="Paste the job description here to get targeted optimization suggestions..."
                                        value={jobDescription}
                                        onChange={(e) => setJobDescription(e.target.value)}
                                    />
                                </div>
                                <div className="flex justify-end">
                                    <button
                                        onClick={handleJobOptimization}
                                        disabled={!jobDescription.trim() || isAnalyzing}
                                        className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        {isAnalyzing ? (
                                            <svg className="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        ) : (
                                            <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" />
                                            </svg>
                                        )}
                                        {isAnalyzing ? 'Analyzing...' : 'Optimize for Job'}
                                    </button>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Main Editor Layout */}
                    <div className="grid grid-cols-12 gap-6">
                        {/* Left Panel - Section Editor */}
                        <div className="col-span-12 lg:col-span-6 xl:col-span-5">
                            <div className="bg-white shadow-sm rounded-lg border border-gray-200">
                                <div className="border-b border-gray-200 px-6 py-4">
                                    <h3 className="text-lg font-medium text-gray-900">
                                        Resume Sections
                                    </h3>
                                    <p className="text-sm text-gray-500 mt-1">
                                        Edit each section to improve your ATS score
                                    </p>
                                </div>
                                <div className="p-6">
                                    <SectionEditor
                                        sections={sections}
                                        selectedSectionId={selectedSectionId}
                                        onSectionSelect={setSelectedSectionId}
                                        onSectionChange={handleSectionChange}
                                        isAnalyzing={isAnalyzing}
                                    />
                                </div>
                            </div>
                        </div>

                        {/* Right Panel - Suggestions and Scores */}
                        <div className="col-span-12 lg:col-span-6 xl:col-span-7 space-y-6">
                            {/* Live Score Panel */}
                            <LiveScorePanel
                                scores={scores}
                                sections={sections}
                                isAnalyzing={isAnalyzing}
                            />

                            {/* ATS Suggestions Panel */}
                            <ATSSuggestionPanel
                                suggestions={suggestions}
                                selectedSectionId={selectedSectionId}
                                onApplySuggestion={handleApplySuggestion}
                                onDismissSuggestion={handleDismissSuggestion}
                                isLoading={isAnalyzing}
                            />
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}