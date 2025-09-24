import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Label } from '@/components/ui/Label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/Card';
import { FileText, ArrowLeft, Mail } from 'lucide-react';

export default function ForgotPassword({ status }: { status?: string }) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/forgot-password');
    };

    return (
        <>
            <Head title="Forgot Password" />

            <div className="min-h-screen bg-gradient-to-br from-green-50 to-green-100 dark:from-gray-950 dark:to-green-950/20 flex items-center justify-center p-4">
                <div className="w-full max-w-md">
                    {/* Logo */}
                    <div className="flex items-center justify-center mb-8">
                        <div className="flex items-center space-x-3">
                            <div className="h-12 w-12 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center">
                                <FileText className="h-7 w-7 text-white" />
                            </div>
                            <span className="text-2xl font-bold bg-gradient-to-r from-green-600 to-green-700 bg-clip-text text-transparent">
                                AI Resume Analyzer
                            </span>
                        </div>
                    </div>

                    <Card className="border-0 shadow-xl bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm">
                        <CardHeader className="space-y-1 pb-4">
                            <div className="flex items-center justify-center mb-4">
                                <div className="h-16 w-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                                    <Mail className="h-8 w-8 text-green-600" />
                                </div>
                            </div>
                            <CardTitle className="text-2xl font-bold text-center">Reset your password</CardTitle>
                            <CardDescription className="text-center">
                                Enter your email address and we'll send you a link to reset your password.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {status && (
                                <div className="mb-4 p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg">
                                    <p className="text-sm text-green-700 dark:text-green-300">{status}</p>
                                </div>
                            )}

                            <form onSubmit={submit} className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="email">Email address</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        value={data.email}
                                        onChange={(e) => setData('email', e.target.value)}
                                        placeholder="Enter your email address"
                                        className="h-11"
                                        required
                                        autoFocus
                                    />
                                    {errors.email && (
                                        <p className="text-sm text-red-600">{errors.email}</p>
                                    )}
                                </div>

                                <Button
                                    type="submit"
                                    disabled={processing}
                                    className="w-full h-11 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white font-medium"
                                >
                                    {processing ? 'Sending reset link...' : 'Send reset link'}
                                </Button>
                            </form>

                            <div className="mt-6">
                                <Link
                                    href="/login"
                                    className="flex items-center justify-center space-x-2 text-sm text-gray-600 hover:text-green-600 transition-colors"
                                >
                                    <ArrowLeft className="h-4 w-4" />
                                    <span>Back to sign in</span>
                                </Link>
                            </div>

                            <div className="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                                <div className="text-center">
                                    <p className="text-sm text-gray-600 mb-2">Don't have an account?</p>
                                    <Link
                                        href="/register"
                                        className="font-medium text-green-600 hover:text-green-700 hover:underline"
                                    >
                                        Create a free account
                                    </Link>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Security Notice */}
                    <div className="mt-6 text-center">
                        <p className="text-xs text-gray-500">
                            For security reasons, we'll only send the reset link if an account exists with this email.
                        </p>
                    </div>
                </div>
            </div>
        </>
    );
}