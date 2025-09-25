import axios, { AxiosResponse, AxiosError } from 'axios';
import { 
    AnalysisResult, 
    ATSSuggestion, 
    LiveScores, 
    ResumeSection,
    KeywordAnalysis,
    JobOptimizationResult
} from '@/types/resume';

// Configure axios defaults
axios.defaults.headers.common['Accept'] = 'application/json';
axios.defaults.headers.common['Content-Type'] = 'application/json';
axios.defaults.timeout = 30000; // 30 second timeout for AI analysis

// Cache for storing analysis results to reduce API calls
class AnalysisCache {
    private cache: Map<string, { data: any; timestamp: number; ttl: number }> = new Map();

    set(key: string, data: any, ttlSeconds: number = 300): void {
        this.cache.set(key, {
            data,
            timestamp: Date.now(),
            ttl: ttlSeconds * 1000
        });
    }

    get(key: string): any | null {
        const entry = this.cache.get(key);
        if (!entry) return null;

        if (Date.now() - entry.timestamp > entry.ttl) {
            this.cache.delete(key);
            return null;
        }

        return entry.data;
    }

    clear(): void {
        this.cache.clear();
    }

    invalidatePattern(pattern: string): void {
        for (const [key] of this.cache) {
            if (key.includes(pattern)) {
                this.cache.delete(key);
            }
        }
    }
}

const analysisCache = new AnalysisCache();

// Retry configuration
interface RetryConfig {
    maxRetries: number;
    baseDelay: number;
    maxDelay: number;
    retryCondition?: (error: AxiosError) => boolean;
}

const defaultRetryConfig: RetryConfig = {
    maxRetries: 3,
    baseDelay: 1000,
    maxDelay: 10000,
    retryCondition: (error: AxiosError) => {
        // Retry on network errors or 5xx server errors
        return !error.response || (error.response.status >= 500 && error.response.status < 600);
    }
};

// Utility function for exponential backoff
const delay = (ms: number): Promise<void> => new Promise(resolve => setTimeout(resolve, ms));

// Generic retry wrapper
async function withRetry<T>(
    operation: () => Promise<T>,
    config: RetryConfig = defaultRetryConfig
): Promise<T> {
    let lastError: AxiosError;
    
    for (let attempt = 0; attempt <= config.maxRetries; attempt++) {
        try {
            return await operation();
        } catch (error) {
            lastError = error as AxiosError;
            
            // Don't retry if it's the last attempt or retry condition is not met
            if (attempt === config.maxRetries || !config.retryCondition?.(lastError)) {
                throw lastError;
            }
            
            // Calculate delay with exponential backoff
            const delayMs = Math.min(
                config.baseDelay * Math.pow(2, attempt),
                config.maxDelay
            );
            
            console.warn(`API call failed (attempt ${attempt + 1}/${config.maxRetries + 1}). Retrying in ${delayMs}ms...`, {
                error: lastError.message,
                status: lastError.response?.status
            });
            
            await delay(delayMs);
        }
    }
    
    throw lastError!;
}

// Error handling utility
function handleApiError(error: AxiosError, context: string): never {
    console.error(`API Error in ${context}:`, {
        message: error.message,
        status: error.response?.status,
        data: error.response?.data,
        url: error.config?.url
    });

    let userMessage = `Failed to ${context.toLowerCase()}. Please try again.`;
    
    if (error.response?.status === 429) {
        userMessage = 'Too many requests. Please wait a moment before trying again.';
    } else if (error.response?.status === 403) {
        userMessage = 'You don\'t have permission to perform this action.';
    } else if (error.response?.status === 404) {
        userMessage = 'Resume not found. Please refresh the page.';
    } else if (error.response?.status >= 500) {
        userMessage = 'Server error. Our team has been notified.';
    } else if (!error.response) {
        userMessage = 'Network error. Please check your connection.';
    }
    
    throw new Error(userMessage);
}

export class ResumeAnalysisAPI {
    /**
     * Analyze a specific resume section for ATS compatibility
     */
    static async analyzeSectionForATS(
        resumeId: string, 
        sectionId: string, 
        content: any, 
        jobDescription?: string
    ): Promise<AnalysisResult> {
        const cacheKey = `section-analysis-${resumeId}-${sectionId}-${JSON.stringify(content).substring(0, 100)}`;
        
        // Check cache first
        const cached = analysisCache.get(cacheKey);
        if (cached) {
            return cached;
        }

        try {
            const result = await withRetry(async () => {
                const response: AxiosResponse<AnalysisResult> = await axios.post(
                    `/api/resumes/${resumeId}/analyze-section`,
                    {
                        section_id: sectionId,
                        content,
                        job_description: jobDescription || null
                    }
                );
                return response.data;
            });

            // Cache the result for 5 minutes
            analysisCache.set(cacheKey, result, 300);
            return result;
        } catch (error) {
            handleApiError(error as AxiosError, 'analyze section');
        }
    }

    /**
     * Get keyword suggestions for resume optimization
     */
    static async getKeywordSuggestions(
        resumeId: string, 
        targetRole: string, 
        currentContent?: string
    ): Promise<KeywordAnalysis> {
        const cacheKey = `keyword-suggestions-${resumeId}-${targetRole}`;
        
        const cached = analysisCache.get(cacheKey);
        if (cached) {
            return cached;
        }

        try {
            const result = await withRetry(async () => {
                const response: AxiosResponse<KeywordAnalysis> = await axios.post(
                    `/api/resumes/${resumeId}/keyword-suggestions`,
                    {
                        target_role: targetRole,
                        current_content: currentContent || null
                    }
                );
                return response.data;
            });

            analysisCache.set(cacheKey, result, 600); // Cache for 10 minutes
            return result;
        } catch (error) {
            handleApiError(error as AxiosError, 'get keyword suggestions');
        }
    }

    /**
     * Generate ATS preview of how the resume would be parsed
     */
    static async generateATSPreview(resumeId: string): Promise<{
        parsed_text: string;
        detected_sections: string[];
        compatibility_score: number;
        warnings: string[];
    }> {
        const cacheKey = `ats-preview-${resumeId}`;
        
        const cached = analysisCache.get(cacheKey);
        if (cached) {
            return cached;
        }

        try {
            const result = await withRetry(async () => {
                const response = await axios.post(`/api/resumes/${resumeId}/ats-preview`);
                return response.data;
            });

            analysisCache.set(cacheKey, result, 300);
            return result;
        } catch (error) {
            handleApiError(error as AxiosError, 'generate ATS preview');
        }
    }

    /**
     * Optimize resume for a specific job description
     */
    static async optimizeForJob(
        resumeId: string, 
        jobDescription: string
    ): Promise<JobOptimizationResult> {
        try {
            const result = await withRetry(async () => {
                const response: AxiosResponse<JobOptimizationResult> = await axios.post(
                    `/api/resumes/${resumeId}/optimize-for-job`,
                    { job_description: jobDescription }
                );
                return response.data;
            });

            // Invalidate related cache entries
            analysisCache.invalidatePattern(resumeId);
            
            return result;
        } catch (error) {
            handleApiError(error as AxiosError, 'optimize for job');
        }
    }

    /**
     * Apply a suggestion to the resume
     */
    static async applySuggestion(
        resumeId: string, 
        suggestionId: string
    ): Promise<{ success: boolean; updated_section?: ResumeSection }> {
        try {
            const result = await withRetry(async () => {
                const response = await axios.post(
                    `/api/resumes/${resumeId}/suggestions/${suggestionId}/apply`
                );
                return response.data;
            });

            // Invalidate cache for this resume
            analysisCache.invalidatePattern(resumeId);
            
            return result;
        } catch (error) {
            handleApiError(error as AxiosError, 'apply suggestion');
        }
    }

    /**
     * Dismiss a suggestion
     */
    static async dismissSuggestion(
        resumeId: string, 
        suggestionId: string
    ): Promise<{ success: boolean }> {
        try {
            const result = await withRetry(async () => {
                const response = await axios.post(
                    `/api/resumes/${resumeId}/suggestions/${suggestionId}/dismiss`
                );
                return response.data;
            });

            return result;
        } catch (error) {
            handleApiError(error as AxiosError, 'dismiss suggestion');
        }
    }

    /**
     * Save resume sections
     */
    static async saveSections(
        resumeId: string, 
        sections: Partial<ResumeSection>[]
    ): Promise<{ success: boolean; sections: ResumeSection[] }> {
        try {
            const result = await withRetry(async () => {
                const response = await axios.put(
                    `/api/resumes/${resumeId}/sections`,
                    { sections }
                );
                return response.data;
            });

            // Invalidate cache for this resume
            analysisCache.invalidatePattern(resumeId);
            
            return result;
        } catch (error) {
            handleApiError(error as AxiosError, 'save sections');
        }
    }

    /**
     * Get current live scores for a resume
     */
    static async getLiveScores(resumeId: string): Promise<LiveScores> {
        const cacheKey = `live-scores-${resumeId}`;
        
        const cached = analysisCache.get(cacheKey);
        if (cached) {
            return cached;
        }

        try {
            const result = await withRetry(async () => {
                const response: AxiosResponse<LiveScores> = await axios.get(
                    `/api/resumes/${resumeId}/live-scores`
                );
                return response.data;
            });

            analysisCache.set(cacheKey, result, 60); // Cache for 1 minute
            return result;
        } catch (error) {
            handleApiError(error as AxiosError, 'get live scores');
        }
    }

    /**
     * Get suggestions for a specific section or all sections
     */
    static async getSuggestions(
        resumeId: string, 
        sectionId?: string
    ): Promise<ATSSuggestion[]> {
        const cacheKey = `suggestions-${resumeId}${sectionId ? `-${sectionId}` : ''}`;
        
        const cached = analysisCache.get(cacheKey);
        if (cached) {
            return cached;
        }

        try {
            const result = await withRetry(async () => {
                const response: AxiosResponse<ATSSuggestion[]> = await axios.get(
                    `/api/resumes/${resumeId}/suggestions`,
                    { params: sectionId ? { section_id: sectionId } : {} }
                );
                return response.data;
            });

            analysisCache.set(cacheKey, result, 300);
            return result;
        } catch (error) {
            handleApiError(error as AxiosError, 'get suggestions');
        }
    }

    /**
     * Clear all cached data (useful when user logs out or switches resumes)
     */
    static clearCache(): void {
        analysisCache.clear();
    }

    /**
     * Clear cache for a specific resume
     */
    static clearResumeCache(resumeId: string): void {
        analysisCache.invalidatePattern(resumeId);
    }
}

// Hook for managing API loading states
export function useApiLoadingStates() {
    const [loadingStates, setLoadingStates] = React.useState<Record<string, boolean>>({});

    const setLoading = (key: string, loading: boolean) => {
        setLoadingStates(prev => ({ ...prev, [key]: loading }));
    };

    const isLoading = (key: string) => loadingStates[key] || false;

    const withLoading = async <T>(key: string, operation: () => Promise<T>): Promise<T> => {
        setLoading(key, true);
        try {
            return await operation();
        } finally {
            setLoading(key, false);
        }
    };

    return { isLoading, setLoading, withLoading };
}