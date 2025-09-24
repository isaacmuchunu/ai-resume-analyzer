import { Link } from '@inertiajs/react';
import { Card, CardContent } from '@/components/ui/Card';
import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { FileText, Download, Trash2, RotateCcw } from 'lucide-react';
import { formatDateTime } from '@/lib/utils';

interface ResumeCardProps {
    resume: {
        id: number;
        original_filename: string;
        analysis_status: string;
        parsing_status: string;
        created_at: string;
        file_size_human: string;
        file_type: string;
        latest_analysis?: {
            overall_score: number;
            overall_grade: string;
            ats_score: number;
            content_score: number;
            format_score: number;
            keyword_score: number;
        };
    };
    onDelete?: (id: number) => void;
    onReanalyze?: (id: number) => void;
}

export function ResumeCard({ resume, onDelete, onReanalyze }: ResumeCardProps) {
    const getStatusBadge = (status: string) => {
        const variants = {
            completed: { variant: 'success' as const, label: 'Analyzed' },
            processing: { variant: 'warning' as const, label: 'Processing' },
            pending: { variant: 'info' as const, label: 'Pending' },
            failed: { variant: 'destructive' as const, label: 'Failed' },
        };

        const config = variants[status as keyof typeof variants] || {
            variant: 'secondary' as const,
            label: status,
        };

        return <Badge variant={config.variant}>{config.label}</Badge>;
    };

    const getScoreColor = (score: number) => {
        if (score >= 80) return 'text-green-600';
        if (score >= 60) return 'text-yellow-600';
        return 'text-red-600';
    };

    return (
        <Card className="hover:shadow-md transition-shadow">
            <CardContent className="p-4">
                <div className="flex items-start justify-between">
                    <div className="flex items-start space-x-3 flex-1 min-w-0">
                        <div className="flex-shrink-0">
                            <div className="h-10 w-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                                <FileText className="h-5 w-5 text-green-600" />
                            </div>
                        </div>

                        <div className="flex-1 min-w-0">
                            <Link
                                href={`/resumes/${resume.id}`}
                                className="block"
                            >
                                <h3 className="text-sm font-medium text-gray-900 dark:text-gray-100 truncate hover:text-green-600 transition-colors">
                                    {resume.original_filename}
                                </h3>
                            </Link>

                            <div className="mt-1 flex items-center space-x-2 text-xs text-gray-500">
                                <span>{resume.file_size_human}</span>
                                <span>•</span>
                                <span>{resume.file_type.toUpperCase()}</span>
                                <span>•</span>
                                <span>{formatDateTime(resume.created_at)}</span>
                            </div>

                            <div className="mt-2 flex items-center space-x-2">
                                {getStatusBadge(resume.analysis_status)}
                                {resume.latest_analysis && (
                                    <div className="flex items-center space-x-1">
                                        <span className="text-xs text-gray-500">Score:</span>
                                        <span className={`text-xs font-medium ${getScoreColor(resume.latest_analysis.overall_score)}`}>
                                            {resume.latest_analysis.overall_score}/100
                                        </span>
                                        <Badge variant="outline" className="text-xs">
                                            {resume.latest_analysis.overall_grade}
                                        </Badge>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    <div className="flex items-center space-x-1 ml-2">
                        {resume.analysis_status === 'completed' && (
                            <Button
                                variant="ghost"
                                size="sm"
                                asChild
                            >
                                <Link href={`/resumes/${resume.id}/download`}>
                                    <Download className="h-4 w-4" />
                                </Link>
                            </Button>
                        )}

                        {resume.analysis_status === 'failed' && onReanalyze && (
                            <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => onReanalyze(resume.id)}
                            >
                                <RotateCcw className="h-4 w-4" />
                            </Button>
                        )}

                        {onDelete && (
                            <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => onDelete(resume.id)}
                            >
                                <Trash2 className="h-4 w-4 text-red-500" />
                            </Button>
                        )}
                    </div>
                </div>

                {resume.latest_analysis && (
                    <div className="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                        <div className="grid grid-cols-4 gap-2 text-xs">
                            <div className="text-center">
                                <div className="font-medium text-gray-900 dark:text-gray-100">
                                    {resume.latest_analysis.ats_score}
                                </div>
                                <div className="text-gray-500">ATS</div>
                            </div>
                            <div className="text-center">
                                <div className="font-medium text-gray-900 dark:text-gray-100">
                                    {resume.latest_analysis.content_score}
                                </div>
                                <div className="text-gray-500">Content</div>
                            </div>
                            <div className="text-center">
                                <div className="font-medium text-gray-900 dark:text-gray-100">
                                    {resume.latest_analysis.format_score}
                                </div>
                                <div className="text-gray-500">Format</div>
                            </div>
                            <div className="text-center">
                                <div className="font-medium text-gray-900 dark:text-gray-100">
                                    {resume.latest_analysis.keyword_score}
                                </div>
                                <div className="text-gray-500">Keywords</div>
                            </div>
                        </div>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}