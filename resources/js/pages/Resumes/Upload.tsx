import { useState, useRef } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card } from '@/components/ui/card';
import {
    Upload,
    FileText,
    X,
    CheckCircle2,
    AlertCircle,
    ArrowLeft,
    Loader2
} from 'lucide-react';

interface UploadProps {
    subscription?: {
        plan: string;
        remaining_resumes: number;
        can_upload: boolean;
    };
}

export default function ResumeUpload({ subscription }: UploadProps) {
    const [dragActive, setDragActive] = useState(false);
    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const [uploadProgress, setUploadProgress] = useState(0);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const { data, setData, post, processing, errors, reset } = useForm({
        resume_file: null as File | null,
        job_description: '',
    });

    const handleDrag = (e: React.DragEvent) => {
        e.preventDefault();
        e.stopPropagation();
        if (e.type === "dragenter" || e.type === "dragover") {
            setDragActive(true);
        } else if (e.type === "dragleave") {
            setDragActive(false);
        }
    };

    const handleDrop = (e: React.DragEvent) => {
        e.preventDefault();
        e.stopPropagation();
        setDragActive(false);

        if (e.dataTransfer.files && e.dataTransfer.files[0]) {
            handleFile(e.dataTransfer.files[0]);
        }
    };

    const handleFileInput = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.files && e.target.files[0]) {
            handleFile(e.target.files[0]);
        }
    };

    const handleFile = (file: File) => {
        // Validate file type
        const allowedTypes = ['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain'];
        if (!allowedTypes.includes(file.type)) {
            alert('Please upload a PDF, DOCX, or TXT file.');
            return;
        }

        // Validate file size (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert('File size must be less than 5MB.');
            return;
        }

        setSelectedFile(file);
        setData('resume_file', file);
    };

    const removeFile = () => {
        setSelectedFile(null);
        setData('resume_file', null);
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (!data.resume_file) {
            alert('Please select a file to upload.');
            return;
        }

        post(route('resumes.upload'), {
            forceFormData: true,
            onProgress: (progress) => {
                setUploadProgress(Math.round(progress.percentage || 0));
            },
            onSuccess: () => {
                setSelectedFile(null);
                setUploadProgress(0);
                reset();
            },
        });
    };

    const getFileSize = (bytes: number) => {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    };

    if (subscription && !subscription.can_upload) {
        return (
            <>
                <Head title="Upload Resume - Limit Reached" />

                <div className="min-h-screen bg-gray-50 dark:bg-gray-900 py-12">
                    <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="text-center mb-8">
                            <Button variant="ghost" onClick={() => router.get('/dashboard')} className="mb-4">
                                <ArrowLeft className="h-4 w-4 mr-2" />
                                Back to Dashboard
                            </Button>
                        </div>

                        <Card className="p-8 text-center">
                            <AlertCircle className="h-16 w-16 text-amber-500 mx-auto mb-6" />
                            <h1 className="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                                Upload Limit Reached
                            </h1>
                            <p className="text-gray-600 dark:text-gray-400 mb-6">
                                You've reached your monthly upload limit for the {subscription.plan} plan.
                            </p>
                            <Button onClick={() => router.get('/subscription/upgrade')}>
                                Upgrade Plan
                            </Button>
                        </Card>
                    </div>
                </div>
            </>
        );
    }

    return (
        <>
            <Head title="Upload Resume" />

            <div className="min-h-screen bg-gray-50 dark:bg-gray-900 py-12">
                <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="text-center mb-8">
                        <Button variant="ghost" onClick={() => router.get('/dashboard')} className="mb-4">
                            <ArrowLeft className="h-4 w-4 mr-2" />
                            Back to Dashboard
                        </Button>

                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                            Upload Your Resume
                        </h1>
                        <p className="text-gray-600 dark:text-gray-400">
                            Get AI-powered insights to improve your resume's performance
                        </p>

                        {subscription && (
                            <div className="mt-4 inline-flex items-center bg-slate-100 dark:bg-slate-800 rounded-full px-4 py-2 text-sm">
                                <span className="text-slate-700 dark:text-slate-300">
                                    {subscription.remaining_resumes} uploads remaining this month
                                </span>
                            </div>
                        )}
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-8">
                        {/* File Upload Area */}
                        <Card className="p-8">
                            <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-6">
                                Select Resume File
                            </h2>

                            <div
                                className={`relative border-2 border-dashed rounded-lg p-8 text-center transition-colors ${
                                    dragActive
                                        ? 'border-slate-400 bg-slate-50 dark:bg-slate-800/50'
                                        : 'border-gray-300 dark:border-gray-600 hover:border-slate-400 dark:hover:border-slate-500'
                                }`}
                                onDragEnter={handleDrag}
                                onDragLeave={handleDrag}
                                onDragOver={handleDrag}
                                onDrop={handleDrop}
                            >
                                <input
                                    ref={fileInputRef}
                                    type="file"
                                    accept=".pdf,.docx,.txt"
                                    onChange={handleFileInput}
                                    className="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                    disabled={processing}
                                />

                                {selectedFile ? (
                                    <div className="space-y-4">
                                        <div className="flex items-center justify-center space-x-3">
                                            <FileText className="h-8 w-8 text-slate-600" />
                                            <div className="text-left">
                                                <p className="font-medium text-gray-900 dark:text-white">
                                                    {selectedFile.name}
                                                </p>
                                                <p className="text-sm text-gray-500">
                                                    {getFileSize(selectedFile.size)}
                                                </p>
                                            </div>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                onClick={removeFile}
                                                disabled={processing}
                                            >
                                                <X className="h-4 w-4" />
                                            </Button>
                                        </div>

                                        <div className="flex items-center justify-center text-green-600 dark:text-green-400">
                                            <CheckCircle2 className="h-5 w-5 mr-2" />
                                            <span className="text-sm">Ready to upload</span>
                                        </div>
                                    </div>
                                ) : (
                                    <div className="space-y-4">
                                        <Upload className="h-12 w-12 text-gray-400 mx-auto" />
                                        <div>
                                            <h3 className="text-lg font-medium text-gray-900 dark:text-white">
                                                Drop your resume here
                                            </h3>
                                            <p className="text-gray-500 dark:text-gray-400">
                                                or <span className="text-slate-600 dark:text-slate-400 underline">browse files</span>
                                            </p>
                                        </div>
                                        <div className="text-xs text-gray-400 space-y-1">
                                            <p>Supported formats: PDF, DOCX, TXT</p>
                                            <p>Maximum file size: 5MB</p>
                                        </div>
                                    </div>
                                )}
                            </div>

                            {errors.resume_file && (
                                <p className="mt-2 text-sm text-red-600 dark:text-red-400">
                                    {errors.resume_file}
                                </p>
                            )}
                        </Card>

                        {/* Job Description (Optional) */}
                        <Card className="p-8">
                            <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-6">
                                Target Job Description (Optional)
                            </h2>
                            <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                Paste a job description to get targeted feedback on how well your resume matches the role.
                            </p>

                            <textarea
                                value={data.job_description}
                                onChange={(e) => setData('job_description', e.target.value)}
                                placeholder="Paste the job description here..."
                                className="w-full min-h-[120px] px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-slate-500 focus:border-transparent"
                                disabled={processing}
                            />
                        </Card>

                        {/* Upload Progress */}
                        {processing && (
                            <Card className="p-6">
                                <div className="flex items-center space-x-3 mb-3">
                                    <Loader2 className="h-5 w-5 animate-spin text-slate-600" />
                                    <span className="text-sm font-medium text-gray-900 dark:text-white">
                                        Uploading and analyzing...
                                    </span>
                                </div>
                                <div className="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div
                                        className="bg-slate-600 h-2 rounded-full transition-all duration-300"
                                        style={{ width: `${uploadProgress}%` }}
                                    ></div>
                                </div>
                                <p className="text-xs text-gray-500 mt-2">
                                    {uploadProgress}% complete
                                </p>
                            </Card>
                        )}

                        {/* Submit Button */}
                        <div className="flex justify-end">
                            <Button
                                type="submit"
                                disabled={!selectedFile || processing}
                                className="bg-slate-700 hover:bg-slate-800 text-white"
                            >
                                {processing ? (
                                    <>
                                        <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                                        Analyzing Resume...
                                    </>
                                ) : (
                                    <>
                                        <Upload className="h-4 w-4 mr-2" />
                                        Upload & Analyze Resume
                                    </>
                                )}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </>
    );
}