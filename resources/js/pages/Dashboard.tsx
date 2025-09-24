import { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import AppLayout from '@/layouts/AppLayout';
import FileUpload from '@/components/FileUpload';
import { Button } from '@/components/ui/Button';
import {
    FileText,
    BarChart3,
    TrendingUp,
    Clock,
    CheckCircle,
    AlertTriangle,
    Upload,
    Zap,
    Target,
    Award
} from 'lucide-react';
import { cn, formatScore, getScoreColor, getScoreGrade, formatDate } from '@/lib/utils';

interface DashboardProps {
    tenant: {
        name: string;
        plan: string;
        branding?: any;
    };
    user: {
        id: number;
        email: string;
        first_name: string;
        last_name: string;
        role: string;
    };
    stats: {
        total_resumes: number;
        analyzed_resumes: number;
        average_score: number;
        recent_uploads: number;
    };
    recent_resumes: Array<{
        id: number;
        original_filename: string;
        analysis_status: string;
        created_at: string;
        latest_analysis?: {
            overall_score: number;
        };
    }>;
}

export default function Dashboard({ tenant, user, stats, recent_resumes }: DashboardProps) {
    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const [isUploading, setIsUploading] = useState(false);
    const [uploadProgress, setUploadProgress] = useState(0);
    const [uploadError, setUploadError] = useState<string | null>(null);

    const { data, setData, post, processing } = useForm({
        file: null as File | null,
        target_role: '',
        target_industry: '',
    });

    const handleFileSelect = (file: File) => {
        setSelectedFile(file);
        setData('file', file);
        setUploadError(null);
    };

    const handleFileRemove = () => {
        setSelectedFile(null);
        setData('file', null);
        setUploadError(null);
    };

    const handleUpload = () => {
        if (!selectedFile) return;

        setIsUploading(true);
        setUploadProgress(0);

        const formData = new FormData();
        formData.append('file', selectedFile);
        formData.append('target_role', data.target_role);
        formData.append('target_industry', data.target_industry);

        // Simulate upload progress
        const progressInterval = setInterval(() => {
            setUploadProgress(prev => {
                if (prev >= 90) {
                    clearInterval(progressInterval);
                    return prev;
                }
                return prev + 10;
            });
        }, 200);

        post('/resumes/upload', {
            data: formData,
            onSuccess: () => {
                setUploadProgress(100);
                setTimeout(() => {
                    setIsUploading(false);
                    setSelectedFile(null);
                    setData('file', null);
                    router.reload();
                }, 1000);
            },
            onError: (errors) => {
                clearInterval(progressInterval);
                setIsUploading(false);
                setUploadError(errors.file || 'Upload failed. Please try again.');
            },
        });
    };

    const quickStats = [
        {
            name: 'Total Resumes',
            value: stats.total_resumes,
            icon: FileText,
            color: 'text-blue-600',
            bgColor: 'bg-blue-50',
        },
        {
            name: 'Analyzed',
            value: stats.analyzed_resumes,
            icon: CheckCircle,
            color: 'text-green-600',
            bgColor: 'bg-green-50',
        },
        {
            name: 'Average Score',
            value: stats.average_score > 0 ? formatScore(stats.average_score) : 'N/A',
            icon: BarChart3,
            color: getScoreColor(stats.average_score),
            bgColor: stats.average_score >= 80 ? 'bg-green-50' : stats.average_score >= 60 ? 'bg-yellow-50' : 'bg-red-50',
        },
        {
            name: 'Recent Uploads',
            value: stats.recent_uploads,
            icon: TrendingUp,
            color: 'text-purple-600',
            bgColor: 'bg-purple-50',
        },
    ];

    const getStatusIcon = (status: string) => {
        switch (status) {
            case 'completed':
                return <CheckCircle className="h-4 w-4 text-green-500" />;
            case 'processing':
                return <Clock className="h-4 w-4 text-yellow-500" />;
            case 'failed':
                return <AlertTriangle className="h-4 w-4 text-red-500" />;
            default:
                return <Clock className="h-4 w-4 text-gray-400" />;
        }
    };

    const getStatusText = (status: string) => {
        switch (status) {
            case 'completed':
                return 'Analyzed';
            case 'processing':
                return 'Processing';
            case 'failed':
                return 'Failed';
            default:
                return 'Pending';
        }
    };

    return (
        <AppLayout title="Dashboard" tenant={tenant} user={user}>
            <div className="p-6 space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">Dashboard</h1>
                        <p className="text-gray-600">Welcome back, {user.first_name}!</p>
                    </div>
                    <div className="mt-4 sm:mt-0">
                        <Button
                            onClick={() => router.visit('/resumes')}
                            className="inline-flex items-center"
                        >
                            <FileText className="h-4 w-4 mr-2" />
                            View All Resumes
                        </Button>
                    </div>
                </div>

                {/* Quick Stats */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    {quickStats.map((stat) => {
                        const Icon = stat.icon;
                        return (
                            <div key={stat.name} className="bg-white rounded-lg border p-6">
                                <div className="flex items-center">
                                    <div className={cn('p-2 rounded-lg', stat.bgColor)}>
                                        <Icon className={cn('h-6 w-6', stat.color)} />
                                    </div>
                                    <div className="ml-4">
                                        <p className="text-sm font-medium text-gray-600">{stat.name}</p>
                                        <p className={cn('text-2xl font-bold', stat.color)}>
                                            {stat.value}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        );
                    })}
                </div>

                {/* Main Content Grid */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* File Upload Section */}
                    <div className="lg:col-span-2">
                        <div className="bg-white rounded-lg border p-6">
                            <div className="flex items-center space-x-2 mb-6">
                                <Upload className="h-5 w-5 text-blue-600" />
                                <h2 className="text-lg font-semibold text-gray-900">Upload Resume</h2>
                            </div>

                            <div className="space-y-4">
                                <FileUpload
                                    onFileSelect={handleFileSelect}
                                    onFileRemove={handleFileRemove}
                                    selectedFile={selectedFile}
                                    isUploading={isUploading}
                                    uploadProgress={uploadProgress}
                                    error={uploadError}
                                />

                                {selectedFile && (
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                                Target Role (Optional)
                                            </label>
                                            <input
                                                type="text"
                                                value={data.target_role}
                                                onChange={(e) => setData('target_role', e.target.value)}
                                                placeholder="e.g., Software Engineer"
                                                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                disabled={isUploading}
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                                Target Industry (Optional)
                                            </label>
                                            <input
                                                type="text"
                                                value={data.target_industry}
                                                onChange={(e) => setData('target_industry', e.target.value)}
                                                placeholder="e.g., Technology"
                                                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                disabled={isUploading}
                                            />
                                        </div>
                                    </div>
                                )}

                                {selectedFile && !isUploading && (
                                    <div className="flex space-x-3">
                                        <Button
                                            onClick={handleUpload}
                                            disabled={processing}
                                            className="flex-1"
                                        >
                                            <Zap className="h-4 w-4 mr-2" />
                                            Analyze Resume
                                        </Button>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Features Highlight */}
                    <div className="space-y-6">
                        <div className="bg-white rounded-lg border p-6">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">
                                AI-Powered Analysis
                            </h3>
                            <div className="space-y-3">
                                <div className="flex items-start space-x-3">
                                    <Target className="h-5 w-5 text-blue-500 mt-0.5" />
                                    <div>
                                        <p className="text-sm font-medium text-gray-900">ATS Optimization</p>
                                        <p className="text-xs text-gray-600">99% accuracy against major ATS systems</p>
                                    </div>
                                </div>
                                <div className="flex items-start space-x-3">
                                    <Award className="h-5 w-5 text-green-500 mt-0.5" />
                                    <div>
                                        <p className="text-sm font-medium text-gray-900">Content Analysis</p>
                                        <p className="text-xs text-gray-600">Professional writing standards check</p>
                                    </div>
                                </div>
                                <div className="flex items-start space-x-3">
                                    <BarChart3 className="h-5 w-5 text-purple-500 mt-0.5" />
                                    <div>
                                        <p className="text-sm font-medium text-gray-900">Skills Assessment</p>
                                        <p className="text-xs text-gray-600">Market demand analysis</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Recent Resumes */}
                {recent_resumes.length > 0 && (
                    <div className="bg-white rounded-lg border">
                        <div className="p-6 border-b">
                            <h2 className="text-lg font-semibold text-gray-900">Recent Resumes</h2>
                        </div>
                        <div className="divide-y">
                            {recent_resumes.map((resume) => (
                                <div key={resume.id} className="p-6 hover:bg-gray-50 transition-colors">
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center space-x-3">
                                            <FileText className="h-5 w-5 text-gray-400" />
                                            <div>
                                                <p className="text-sm font-medium text-gray-900">
                                                    {resume.original_filename}
                                                </p>
                                                <p className="text-xs text-gray-500">
                                                    Uploaded {formatDate(resume.created_at)}
                                                </p>
                                            </div>
                                        </div>
                                        <div className="flex items-center space-x-4">
                                            {resume.latest_analysis && (
                                                <div className="text-right">
                                                    <p className={cn('text-sm font-medium', getScoreColor(resume.latest_analysis.overall_score))}>
                                                        {getScoreGrade(resume.latest_analysis.overall_score)} ({resume.latest_analysis.overall_score}/100)
                                                    </p>
                                                    <p className="text-xs text-gray-500">Score</p>
                                                </div>
                                            )}
                                            <div className="flex items-center space-x-2">
                                                {getStatusIcon(resume.analysis_status)}
                                                <span className="text-sm text-gray-600">
                                                    {getStatusText(resume.analysis_status)}
                                                </span>
                                            </div>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => router.visit(`/resumes/${resume.id}`)}
                                            >
                                                View
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}