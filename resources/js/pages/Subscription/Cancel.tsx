import { Head, router } from '@inertiajs/react';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { XCircle, ArrowLeft, RefreshCw } from 'lucide-react';

interface CancelProps {
    message: string;
}

export default function SubscriptionCancel({ message }: CancelProps) {
    return (
        <>
            <Head title="Checkout Cancelled" />

            <div className="min-h-screen bg-gray-50 dark:bg-gray-900 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
                <Card className="max-w-md w-full p-8 text-center">
                    <div className="mb-6">
                        <XCircle className="h-16 w-16 text-gray-400 mx-auto mb-4" />
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                            Checkout Cancelled
                        </h1>
                        <p className="text-gray-600 dark:text-gray-400">
                            {message}
                        </p>
                    </div>

                    <div className="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 mb-6">
                        <p className="text-sm text-gray-700 dark:text-gray-300">
                            No charges were made to your account. You can try again anytime or
                            continue using the free features.
                        </p>
                    </div>

                    <div className="space-y-3">
                        <Button
                            onClick={() => router.get('/subscription')}
                            className="w-full bg-slate-600 hover:bg-slate-700 text-white"
                        >
                            <RefreshCw className="h-4 w-4 mr-2" />
                            Try Again
                        </Button>

                        <Button
                            variant="outline"
                            onClick={() => router.get('/dashboard')}
                            className="w-full"
                        >
                            <ArrowLeft className="h-4 w-4 mr-2" />
                            Back to Dashboard
                        </Button>

                        <Button
                            variant="ghost"
                            onClick={() => router.get('/resumes/upload')}
                            className="w-full text-sm"
                        >
                            Continue with Free Plan
                        </Button>
                    </div>
                </Card>
            </div>
        </>
    );
}