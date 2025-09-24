import { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import {
    ArrowLeft,
    BarChart3,
    TrendingUp,
    TrendingDown,
    FileText,
    Award,
    Target,
    Download,
    Calendar,
    Filter,
    Eye
} from 'lucide-react';

interface AnalyticsProps {
    analytics: {
        total_resumes: number;
        analyzed_resumes: number;
        average_score: number;
        monthly_uploads: number;
        score_trend: 'up' | 'down' | 'neutral';
        top_skills: string[];
        skill_gaps: string[];
        monthly_data: Array<{
            month: string;
            uploads: number;
            average_score: number;
        }>;
        score_distribution: Array<{
            range: string;
            count: number;
            percentage: number;
        }>;
        ats_compatibility: {
            average: number;
            trend: 'up' | 'down' | 'neutral';
        };
        industry_comparison: {
            your_average: number;
            industry_average: number;
            percentile: number;
        };
    };
}

export default function Analytics({ analytics }: AnalyticsProps) {
    const [timeRange, setTimeRange] = useState('6months');
    const [selectedMetric, setSelectedMetric] = useState('score');

    const timeRanges = [
        { value: '1month', label: 'Last Month' },
        { value: '3months', label: 'Last 3 Months' },
        { value: '6months', label: 'Last 6 Months' },
        { value: '1year', label: 'Last Year' },
    ];

    const metrics = [
        { value: 'score', label: 'Resume Score' },
        { value: 'uploads', label: 'Upload Activity' },
        { value: 'ats', label: 'ATS Compatibility' },
    ];

    const getTrendIcon = (trend: 'up' | 'down' | 'neutral') => {
        switch (trend) {
            case 'up':
                return <TrendingUp className="h-4 w-4 text-green-500" />;
            case 'down':
                return <TrendingDown className="h-4 w-4 text-red-500" />;
            default:
                return <BarChart3 className="h-4 w-4 text-gray-500" />;
        }
    };

    const getScoreColor = (score: number) => {
        if (score >= 80) return 'text-green-600';
        if (score >= 60) return 'text-yellow-600';
        return 'text-red-600';
    };

    const getScoreBackground = (score: number) => {
        if (score >= 80) return 'bg-green-100 dark:bg-green-900/20';
        if (score >= 60) return 'bg-yellow-100 dark:bg-yellow-900/20';
        return 'bg-red-100 dark:bg-red-900/20';
    };

    return (
        <>
            <Head title="Analytics" />

            <div className="min-h-screen bg-gray-50 dark:bg-gray-900 py-12">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-8">
                        <Button variant="ghost" onClick={() => router.get('/dashboard')} className="mb-4">
                            <ArrowLeft className="h-4 w-4 mr-2" />
                            Back to Dashboard
                        </Button>

                        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h1 className="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                                    Analytics
                                </h1>
                                <p className="text-gray-600 dark:text-gray-400">
                                    Insights into your resume performance and improvement trends
                                </p>
                            </div>

                            <div className="mt-4 sm:mt-0 flex items-center space-x-3">
                                <select
                                    value={timeRange}
                                    onChange={(e) => setTimeRange(e.target.value)}
                                    className="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-sm"
                                >
                                    {timeRanges.map((range) => (
                                        <option key={range.value} value={range.value}>
                                            {range.label}
                                        </option>
                                    ))}
                                </select>

                                <Button variant="outline" size="sm">
                                    <Download className="h-4 w-4 mr-2" />
                                    Export
                                </Button>
                            </div>
                        </div>
                    </div>

                    {/* Key Metrics */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <Card className="p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-600 dark:text-gray-400">
                                        Total Resumes
                                    </p>
                                    <p className="text-2xl font-bold text-gray-900 dark:text-white">
                                        {analytics.total_resumes}
                                    </p>
                                </div>
                                <div className="h-12 w-12 bg-slate-100 dark:bg-slate-800 rounded-lg flex items-center justify-center">
                                    <FileText className="h-6 w-6 text-slate-600 dark:text-slate-400" />
                                </div>
                            </div>
                        </Card>

                        <Card className="p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-600 dark:text-gray-400">
                                        Average Score
                                    </p>
                                    <div className="flex items-center space-x-2">
                                        <p className={`text-2xl font-bold ${getScoreColor(analytics.average_score)}`}>
                                            {analytics.average_score}/100
                                        </p>
                                        {getTrendIcon(analytics.score_trend)}
                                    </div>
                                </div>
                                <div className={`h-12 w-12 ${getScoreBackground(analytics.average_score)} rounded-lg flex items-center justify-center`}>
                                    <Award className={`h-6 w-6 ${getScoreColor(analytics.average_score)}`} />
                                </div>
                            </div>
                        </Card>

                        <Card className="p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-600 dark:text-gray-400">
                                        ATS Compatibility
                                    </p>
                                    <div className="flex items-center space-x-2">
                                        <p className="text-2xl font-bold text-gray-900 dark:text-white">
                                            {analytics.ats_compatibility.average}%
                                        </p>
                                        {getTrendIcon(analytics.ats_compatibility.trend)}
                                    </div>
                                </div>
                                <div className="h-12 w-12 bg-blue-100 dark:bg-blue-900/20 rounded-lg flex items-center justify-center">
                                    <Target className="h-6 w-6 text-blue-600 dark:text-blue-400" />
                                </div>
                            </div>
                        </Card>

                        <Card className="p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-600 dark:text-gray-400">
                                        Industry Percentile
                                    </p>
                                    <p className="text-2xl font-bold text-gray-900 dark:text-white">
                                        {analytics.industry_comparison.percentile}th
                                    </p>
                                </div>
                                <div className="h-12 w-12 bg-purple-100 dark:bg-purple-900/20 rounded-lg flex items-center justify-center">
                                    <BarChart3 className="h-6 w-6 text-purple-600 dark:text-purple-400" />
                                </div>
                            </div>
                        </Card>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                        {/* Score Trend Chart */}
                        <Card className="p-6">
                            <div className="flex items-center justify-between mb-6">
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                                    Score Trend
                                </h3>
                                <select
                                    value={selectedMetric}
                                    onChange={(e) => setSelectedMetric(e.target.value)}
                                    className="px-3 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800"
                                >
                                    {metrics.map((metric) => (
                                        <option key={metric.value} value={metric.value}>
                                            {metric.label}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div className="space-y-4">
                                {analytics.monthly_data.map((month, index) => (
                                    <div key={month.month} className="flex items-center justify-between">
                                        <div className="flex items-center space-x-3">
                                            <div className="text-sm font-medium text-gray-900 dark:text-white w-16">
                                                {month.month}
                                            </div>
                                            <div className="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                                <div
                                                    className="bg-slate-600 h-2 rounded-full"
                                                    style={{ width: `${(month.average_score / 100) * 100}%` }}
                                                ></div>
                                            </div>
                                        </div>
                                        <div className="text-sm font-semibold text-gray-900 dark:text-white ml-4">
                                            {selectedMetric === 'score' ? `${month.average_score}/100` : `${month.uploads} uploads`}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </Card>

                        {/* Score Distribution */}
                        <Card className="p-6">
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-6">
                                Score Distribution
                            </h3>

                            <div className="space-y-4">
                                {analytics.score_distribution.map((distribution) => (
                                    <div key={distribution.range} className="flex items-center justify-between">
                                        <div className="flex items-center space-x-3">
                                            <div className="text-sm font-medium text-gray-900 dark:text-white w-20">
                                                {distribution.range}
                                            </div>
                                            <div className="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                                <div
                                                    className="bg-slate-600 h-2 rounded-full"
                                                    style={{ width: `${distribution.percentage}%` }}
                                                ></div>
                                            </div>
                                        </div>
                                        <div className="text-sm font-semibold text-gray-900 dark:text-white ml-4">
                                            {distribution.count} ({distribution.percentage}%)
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </Card>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        {/* Top Skills */}
                        <Card className="p-6">
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-6">
                                Top Performing Skills
                            </h3>

                            <div className="space-y-3">
                                {analytics.top_skills.map((skill, index) => (
                                    <div key={skill} className="flex items-center space-x-3">
                                        <div className="flex-shrink-0 w-6 h-6 bg-green-100 dark:bg-green-900/20 rounded-full flex items-center justify-center">
                                            <span className="text-xs font-bold text-green-600 dark:text-green-400">
                                                {index + 1}
                                            </span>
                                        </div>
                                        <div className="flex-1">
                                            <div className="text-sm font-medium text-gray-900 dark:text-white">
                                                {skill}
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </Card>

                        {/* Skill Gaps */}
                        <Card className="p-6">
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-6">
                                Identified Skill Gaps
                            </h3>

                            <div className="space-y-3">
                                {analytics.skill_gaps.map((skill, index) => (
                                    <div key={skill} className="flex items-center space-x-3">
                                        <div className="flex-shrink-0 w-6 h-6 bg-amber-100 dark:bg-amber-900/20 rounded-full flex items-center justify-center">
                                            <span className="text-xs font-bold text-amber-600 dark:text-amber-400">
                                                {index + 1}
                                            </span>
                                        </div>
                                        <div className="flex-1">
                                            <div className="text-sm font-medium text-gray-900 dark:text-white">
                                                {skill}
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>

                            <div className="mt-6 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
                                <h4 className="text-sm font-semibold text-amber-800 dark:text-amber-200 mb-2">
                                    Improvement Suggestion
                                </h4>
                                <p className="text-sm text-amber-700 dark:text-amber-300">
                                    Consider adding these skills to your resume to improve your match rate with job descriptions.
                                </p>
                            </div>
                        </Card>
                    </div>

                    {/* Industry Comparison */}
                    <Card className="p-6 mt-8">
                        <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-6">
                            Industry Comparison
                        </h3>

                        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div className="text-center">
                                <div className="text-2xl font-bold text-slate-600 dark:text-slate-400 mb-1">
                                    {analytics.industry_comparison.your_average}
                                </div>
                                <div className="text-sm text-gray-600 dark:text-gray-400">
                                    Your Average
                                </div>
                            </div>

                            <div className="text-center">
                                <div className="text-2xl font-bold text-gray-400 mb-1">
                                    {analytics.industry_comparison.industry_average}
                                </div>
                                <div className="text-sm text-gray-600 dark:text-gray-400">
                                    Industry Average
                                </div>
                            </div>

                            <div className="text-center">
                                <div className="text-2xl font-bold text-green-600 dark:text-green-400 mb-1">
                                    {analytics.industry_comparison.percentile}th
                                </div>
                                <div className="text-sm text-gray-600 dark:text-gray-400">
                                    Percentile
                                </div>
                            </div>
                        </div>

                        <div className="mt-6 p-4 bg-slate-50 dark:bg-slate-800/50 rounded-lg">
                            <p className="text-sm text-slate-700 dark:text-slate-300">
                                {analytics.industry_comparison.percentile >= 75 ? (
                                    <>🎉 Excellent! Your resumes score higher than {analytics.industry_comparison.percentile}% of professionals in your industry.</>
                                ) : analytics.industry_comparison.percentile >= 50 ? (
                                    <>👍 Good work! You're performing above average compared to your industry peers.</>
                                ) : (
                                    <>📈 There's room for improvement. Consider our suggestions to boost your resume scores.</>
                                )}
                            </p>
                        </div>
                    </Card>
                </div>
            </div>
        </>
    );
}