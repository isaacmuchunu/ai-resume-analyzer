// Base Resume Interface
export interface Resume {
  id: string;
  user_id: string;
  title: string;
  content: string;
  file_path?: string;
  file_type?: string;
  analysis_result?: ATSAnalysis;
  ats_score?: number;
  status: 'draft' | 'analyzed' | 'optimized';
  created_at: string;
  updated_at: string;
  sections?: ResumeSection[];
}

export interface ResumeData extends Resume {
  sections: ResumeSection[];
  suggestions: ATSSuggestion[];
  live_scores: LiveScores;
}

// Resume Section Types
export interface ResumeSection {
  id: string;
  resume_id: string;
  section_type: SectionType;
  title: string;
  content: SectionContent;
  ats_score: number;
  order_index: number;
  metadata?: Record<string, any>;
  created_at: string;
  updated_at: string;
  pending_suggestions_count?: number;
  has_critical_issues?: boolean;
}

export type SectionType = 
  | 'contact'
  | 'summary'
  | 'experience'
  | 'education'
  | 'skills'
  | 'projects'
  | 'certifications'
  | 'achievements'
  | 'languages'
  | 'volunteer'
  | 'other';

export interface SectionContent {
  [key: string]: any;
}

// Contact Section Content
export interface ContactSectionData {
  name?: string;
  email?: string;
  phone?: string;
  location?: string;
  linkedin?: string;
  website?: string;
  github?: string;
  raw_content?: string;
}

// Summary Section Content
export interface SummarySectionData {
  text: string;
  word_count: number;
  keywords: string[];
}

// Experience Section Content
export interface ExperienceSectionData {
  experiences: ExperienceItem[];
}

export interface ExperienceItem {
  company: string;
  position: string;
  duration: string;
  location?: string;
  description: string;
  achievements: string[];
}

// Education Section Content
export interface EducationSectionData {
  educations: EducationItem[];
}

export interface EducationItem {
  degree: string;
  institution: string;
  graduation_date?: string;
  gpa?: string;
  location?: string;
  details?: string;
}

// Skills Section Content
export interface SkillsSectionData {
  skills: string[];
  categories: SkillCategories;
}

export interface SkillCategories {
  technical: string[];
  soft: string[];
  languages: string[];
  tools: string[];
}

// Projects Section Content
export interface ProjectsSectionData {
  projects: ProjectItem[];
}

export interface ProjectItem {
  name: string;
  description: string;
  technologies: string[];
  url?: string;
  duration?: string;
}

// Certifications Section Content
export interface CertificationsSectionData {
  certifications: CertificationItem[];
}

export interface CertificationItem {
  name: string;
  issuer?: string;
  date?: string;
  credential_id?: string;
}

// Generic Section Content
export interface GenericSectionData {
  text: string;
  items: string[];
}

// ATS Suggestion Types
export interface ATSSuggestion {
  id: string;
  resume_id: string;
  section_id?: string;
  suggestion_type: SuggestionType;
  priority: SuggestionPriority;
  title: string;
  description: string;
  original_text?: string;
  suggested_text?: string;
  ats_impact: number;
  reason?: string;
  status: SuggestionStatus;
  applied_at?: string;
  metadata?: Record<string, any>;
  created_at: string;
  updated_at: string;
  section?: ResumeSection;
}

export type SuggestionType = 
  | 'keyword'
  | 'format'
  | 'content'
  | 'structure'
  | 'achievement'
  | 'grammar'
  | 'ats_compatibility';

export type SuggestionPriority = 'critical' | 'high' | 'medium' | 'low';

export type SuggestionStatus = 'pending' | 'applied' | 'dismissed' | 'expired';

// Job Optimization Types
export interface JobOptimization {
  id: string;
  resume_id: string;
  job_title: string;
  job_description: string;
  required_skills?: string[];
  missing_skills?: string[];
  keyword_gaps?: string[];
  match_score: number;
  optimization_data?: OptimizationData;
  industry_keywords?: string[];
  is_active: boolean;
  created_at: string;
  updated_at: string;
}

export interface OptimizationData {
  sections: SectionOptimization[];
  generated_at: string;
  suggestions?: OptimizationSuggestion[];
  skill_recommendations?: SkillRecommendation[];
  keyword_recommendations?: KeywordRecommendation[];
}

export interface SectionOptimization {
  section_id: string;
  section_type: SectionType;
  current_score: number;
  suggestions: ATSSuggestion[];
  missing_keywords: string[];
}

export interface OptimizationSuggestion {
  id: string;
  section: SectionType;
  action: 'add' | 'modify' | 'remove' | 'reorder';
  content: string;
  reason: string;
  priority: number;
  ats_impact?: number;
}

export interface SkillRecommendation {
  skill: string;
  category: 'technical' | 'soft' | 'tool' | 'language';
  importance: number;
  reason: string;
  examples?: string[];
}

export interface KeywordRecommendation {
  keyword: string;
  context: string;
  frequency_in_job: number;
  missing_from_resume: boolean;
  suggested_placement: SectionType[];
}

// Analysis and Scoring Types
export interface AnalysisResult {
  section_score: number;
  suggestions: ATSSuggestion[];
  live_scores: LiveScores;
  keywords_found: string[];
  keywords_missing: string[];
  improvement_areas: string[];
}

export interface JobOptimizationResult {
  job_title: string;
  match_score: number;
  suggestions: ATSSuggestion[];
  live_scores: LiveScores;
  keyword_gaps: string[];
  missing_skills: string[];
  optimization_summary: {
    critical_changes: number;
    suggested_changes: number;
    potential_score_increase: number;
  };
}

export interface ATSAnalysis {
  ats_score: number;
  suggestions: ATSSuggestion[];
  keywords: string[];
  improvements: string[];
  formatting_issues: string[];
}

export interface LiveScores {
  overall: number;
  ats_compatibility: number;
  keyword_density: number;
  format_score: number;
  content_quality: number;
  improvement_potential: number;
}

export interface SectionScores {
  section_type: SectionType;
  score: number;
  max_score?: number;
}

// Keyword Analysis Types
export interface KeywordSuggestion {
  keyword: string;
  type: 'missing' | 'underused' | 'overused';
  importance: number;
  suggestion: string;
  context?: string;
}

export interface KeywordAnalysis {
  target_role: string;
  suggested_keywords: string[];
  current_keywords: string[];
  missing_keywords: string[];
  keyword_density: number;
  industry_keywords: string[];
  skill_keywords: string[];
  action_keywords: string[];
  optimization_suggestions: string[];
  suggestions: KeywordSuggestion[];
  total_count: number;
  density: number;
  missing_critical: string[];
}

// Real-time Analysis Types
export interface RealTimeAnalysisRequest {
  section_type: SectionType;
  content: string;
  job_description?: string;
}

export interface RealTimeAnalysisResponse {
  success: boolean;
  data?: ATSAnalysis;
  errors?: Record<string, string[]>;
  message?: string;
}

export interface KeywordSuggestionsRequest {
  content: string;
  target_role: string;
}

export interface KeywordSuggestionsResponse {
  success: boolean;
  data?: KeywordAnalysis;
  errors?: Record<string, string[]>;
  message?: string;
}

export interface ATSPreviewRequest {
  resume_text: string;
}

export interface ATSPreviewResponse {
  success: boolean;
  data?: {
    overall_ats_score: number;
    recommendations: RecommendationItem[];
    section_scores: SectionScores[];
  };
  errors?: Record<string, string[]>;
  message?: string;
}

export interface RecommendationItem {
  type: SuggestionType;
  priority: SuggestionPriority;
  title: string;
  description: string;
  ats_impact: number;
}

export interface JobOptimizationRequest {
  job_title: string;
  job_description: string;
}

export interface JobOptimizationResponse {
  success: boolean;
  data?: {
    optimization_id: string;
    match_score: number;
    optimizations: SectionOptimization[];
    summary: OptimizationSummary;
  };
  errors?: Record<string, string[]>;
  message?: string;
}

export interface OptimizationSummary {
  total_suggestions: number;
  critical_issues: number;
  missing_skills_count?: number;
  keyword_gaps_count?: number;
}

// Section Update Types
export interface SectionUpdateRequest {
  content: SectionContent;
  analyze_realtime?: boolean;
}

export interface SectionUpdateResponse {
  success: boolean;
  data?: {
    section: ResumeSection;
    analysis?: ATSAnalysis;
  };
  errors?: Record<string, string[]>;
  message?: string;
}

// UI Component Props Types
export interface SectionEditorProps {
  section: ResumeSection;
  onUpdate: (content: SectionContent) => void;
  onAnalyze?: (analysis: ATSAnalysis) => void;
  isAnalyzing?: boolean;
  readOnly?: boolean;
}

export interface ATSSuggestionCardProps {
  suggestion: ATSSuggestion;
  onApply: (suggestion: ATSSuggestion) => void;
  onDismiss: (suggestion: ATSSuggestion) => void;
  isApplying?: boolean;
  isDismissing?: boolean;
}

export interface LiveScorePanelProps {
  scores: LiveScores;
  sectionScores: SectionScores[];
  onScoreClick?: (sectionType: SectionType) => void;
  isLoading?: boolean;
}

export interface KeywordHighlighterProps {
  text: string;
  keywords: string[];
  missingKeywords: string[];
  onKeywordClick?: (keyword: string) => void;
}

// Form Types for Job Optimization
export interface JobOptimizationForm {
  job_title: string;
  job_description: string;
  target_company?: string;
  industry?: string;
}

// Utility Types
export type SectionTypeDisplayNames = {
  [K in SectionType]: string;
};

export type SuggestionTypeDisplayNames = {
  [K in SuggestionType]: string;
};

export type PriorityDisplayNames = {
  [K in SuggestionPriority]: string;
};

export type StatusDisplayNames = {
  [K in SuggestionStatus]: string;
};

// Constants
export const SECTION_TYPE_NAMES: SectionTypeDisplayNames = {
  contact: 'Contact Information',
  summary: 'Professional Summary',
  experience: 'Work Experience',
  education: 'Education',
  skills: 'Skills',
  projects: 'Projects',
  certifications: 'Certifications',
  achievements: 'Achievements',
  languages: 'Languages',
  volunteer: 'Volunteer Experience',
  other: 'Other',
};

export const SUGGESTION_TYPE_NAMES: SuggestionTypeDisplayNames = {
  keyword: 'Keyword Optimization',
  format: 'Format Improvement',
  content: 'Content Enhancement',
  structure: 'Structure Optimization',
  achievement: 'Achievement Quantification',
  grammar: 'Grammar & Language',
  ats_compatibility: 'ATS Compatibility',
};

export const PRIORITY_NAMES: PriorityDisplayNames = {
  critical: 'Critical',
  high: 'High',
  medium: 'Medium',
  low: 'Low',
};

export const STATUS_NAMES: StatusDisplayNames = {
  pending: 'Pending',
  applied: 'Applied',
  dismissed: 'Dismissed',
  expired: 'Expired',
};

// Color mappings for UI
export const PRIORITY_COLORS = {
  critical: 'text-red-600 bg-red-50 border-red-200',
  high: 'text-orange-600 bg-orange-50 border-orange-200',
  medium: 'text-yellow-600 bg-yellow-50 border-yellow-200',
  low: 'text-blue-600 bg-blue-50 border-blue-200',
} as const;

export const SCORE_COLORS = {
  excellent: 'text-green-600 bg-green-50',
  good: 'text-blue-600 bg-blue-50',
  fair: 'text-yellow-600 bg-yellow-50',
  poor: 'text-red-600 bg-red-50',
} as const;

// Helper function types
export type GetScoreColor = (score: number) => keyof typeof SCORE_COLORS;
export type GetPriorityColor = (priority: SuggestionPriority) => string;
export type FormatSectionContent = (sectionType: SectionType, content: SectionContent) => string;