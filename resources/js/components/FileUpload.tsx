import { useCallback, useState } from 'react';
import { useDropzone } from 'react-dropzone';
import { Upload, File, X, CheckCircle, AlertCircle } from 'lucide-react';
import { cn, formatFileSize } from '@/lib/utils';
import { Button } from '@/components/ui/Button';

interface FileUploadProps {
    onFileSelect: (file: File) => void;
    onFileRemove?: () => void;
    isUploading?: boolean;
    uploadProgress?: number;
    error?: string;
    selectedFile?: File | null;
    acceptedTypes?: string[];
    maxSize?: number;
}

export default function FileUpload({
    onFileSelect,
    onFileRemove,
    isUploading = false,
    uploadProgress = 0,
    error,
    selectedFile,
    acceptedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain'],
    maxSize = 10 * 1024 * 1024, // 10MB
}: FileUploadProps) {
    const [dragActive, setDragActive] = useState(false);

    const onDrop = useCallback(
        (acceptedFiles: File[]) => {
            if (acceptedFiles.length > 0) {
                onFileSelect(acceptedFiles[0]);
            }
        },
        [onFileSelect]
    );

    const { getRootProps, getInputProps, isDragActive, fileRejections } = useDropzone({
        onDrop,
        accept: {
            'application/pdf': ['.pdf'],
            'application/msword': ['.doc'],
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document': ['.docx'],
            'text/plain': ['.txt'],
        },
        maxSize,
        multiple: false,
    });

    const getFileIcon = (fileName: string) => {
        const extension = fileName.split('.').pop()?.toLowerCase();
        return <File className="h-8 w-8 text-blue-500" />;
    };

    const getFileTypeText = () => {
        return 'PDF, DOC, DOCX, or TXT files up to 10MB';
    };

    return (
        <div className="w-full">
            {!selectedFile ? (
                <div
                    {...getRootProps()}
                    className={cn(
                        'border-2 border-dashed rounded-lg p-8 text-center cursor-pointer transition-colors',
                        isDragActive
                            ? 'border-blue-500 bg-blue-50'
                            : 'border-gray-300 hover:border-blue-400 hover:bg-gray-50',
                        isUploading && 'pointer-events-none opacity-50'
                    )}
                >
                    <input {...getInputProps()} />

                    <div className="flex flex-col items-center space-y-4">
                        <Upload className={cn(
                            'h-12 w-12',
                            isDragActive ? 'text-blue-500' : 'text-gray-400'
                        )} />

                        <div>
                            <p className="text-lg font-medium text-gray-900">
                                {isDragActive ? 'Drop your resume here' : 'Upload your resume'}
                            </p>
                            <p className="text-sm text-gray-500 mt-1">
                                Drag and drop or click to browse
                            </p>
                            <p className="text-xs text-gray-400 mt-2">
                                {getFileTypeText()}
                            </p>
                        </div>
                    </div>
                </div>
            ) : (
                <div className="border rounded-lg p-4 bg-white">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center space-x-3">
                            {getFileIcon(selectedFile.name)}
                            <div>
                                <p className="text-sm font-medium text-gray-900">
                                    {selectedFile.name}
                                </p>
                                <p className="text-xs text-gray-500">
                                    {formatFileSize(selectedFile.size)}
                                </p>
                            </div>
                        </div>

                        <div className="flex items-center space-x-2">
                            {isUploading ? (
                                <div className="flex items-center space-x-2">
                                    <div className="w-20 bg-gray-200 rounded-full h-2">
                                        <div
                                            className="bg-blue-600 h-2 rounded-full transition-all duration-300"
                                            style={{ width: `${uploadProgress}%` }}
                                        />
                                    </div>
                                    <span className="text-xs text-gray-500">
                                        {uploadProgress}%
                                    </span>
                                </div>
                            ) : error ? (
                                <AlertCircle className="h-5 w-5 text-red-500" />
                            ) : (
                                <CheckCircle className="h-5 w-5 text-green-500" />
                            )}

                            {onFileRemove && !isUploading && (
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    onClick={onFileRemove}
                                    className="h-8 w-8"
                                >
                                    <X className="h-4 w-4" />
                                </Button>
                            )}
                        </div>
                    </div>

                    {error && (
                        <div className="mt-2 text-sm text-red-600">
                            {error}
                        </div>
                    )}
                </div>
            )}

            {fileRejections.length > 0 && (
                <div className="mt-2 space-y-1">
                    {fileRejections.map(({ file, errors }) => (
                        <div key={file.name} className="text-sm text-red-600">
                            {file.name}: {errors.map(e => e.message).join(', ')}
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}