import { Head, router } from '@inertiajs/react';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { CheckCircle, Crown, ArrowRight } from 'lucide-react';

interface SuccessProps {
    sessionId: string;
    message: string;
}

export default function SubscriptionSuccess({ sessionId, message }: SuccessProps) {
    return (
        <>
            <Head title="Subscription Activated" />

            <div className="min-h-screen bg-gray-50 dark:bg-gray-900 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
                <Card className="max-w-md w-full p-8 text-center">
                    <div className="mb-6">
                        <CheckCircle className="h-16 w-16 text-green-500 mx-auto mb-4" />
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                            Subscription Activated!
                        </h1>
                        <p className="text-gray-600 dark:text-gray-400">
                            {message}
                        </p>
                    </div>

                    <div className="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 mb-6">
                        <div className="flex items-center justify-center mb-2">
                            <Crown className="h-5 w-5 text-green-600 dark:text-green-400 mr-2" />
                            <span className="font-medium text-green-800 dark:text-green-200">
                                Welcome to Premium!
                            </span>
                        </div>
                        <p className="text-sm text-green-700 dark:text-green-300">
                            You now have access to all premium features including unlimited resume analysis,
                            advanced AI insights, collaboration tools, and priority support.
                        </p>
                    </div>

                    <div className="space-y-3">
                        <Button
                            onClick={() => router.get('/dashboard')}
                            className="w-full bg-slate-600 hover:bg-slate-700 text-white"
                        >
                            <ArrowRight className="h-4 w-4 mr-2" />
                            Go to Dashboard
                        </Button>

                        <Button
                            variant="outline"
                            onClick={() => router.get('/resumes/upload')}
                            className="w-full"
                        >
                            Start Analyzing Resumes
                        </Button>

                        <Button
                            variant="ghost"
                            onClick={() => router.get('/subscription')}
                            className="w-full text-sm"
                        >
                            View Subscription Details
                        </Button>
                    </div>

                    {sessionId && (
                        <p className="text-xs text-gray-500 mt-6">
                            Session ID: {sessionId}
                        </p>
                    )}
                </Card>
            </div>
        </>
    );
}