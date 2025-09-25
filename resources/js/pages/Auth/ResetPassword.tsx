import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Label } from '@/components/ui/Label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/Card';
import { FileText, Eye, EyeOff, CheckCircle, Shield } from 'lucide-react';
import { useState } from 'react';

interface ResetPasswordProps {
    token: string;
    email: string;
}

export default function ResetPassword({ token, email }: ResetPasswordProps) {
    const { data, setData, post, processing, errors } = useForm({
        token: token,
        email: email,
        password: '',
        password_confirmation: '',
    });

    const [showPassword, setShowPassword] = useState(false);
    const [showPasswordConfirmation, setShowPasswordConfirmation] = useState(false);

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/reset-password');
    };

    const getPasswordStrength = (password: string) => {
        let strength = 0;
        if (password.length >= 8) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[a-z]/.test(password)) strength++;
        if (/\d/.test(password)) strength++;
        if (/[^A-Za-z\d]/.test(password)) strength++;
        return strength;
    };

    const passwordStrength = getPasswordStrength(data.password);
    const strengthColors = ['bg-red-500', 'bg-red-400', 'bg-yellow-400', 'bg-green-400', 'bg-green-500'];
    const strengthLabels = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];

    return (
        <>
            <Head title="Reset Password" />

            <div className="min-h-screen bg-gradient-to-br from-slate-50 to-blue-100 dark:from-gray-950 dark:to-blue-950/20 flex items-center justify-center p-4">
                <div className="w-full max-w-md">
                    {/* Logo */}
                    <div className="flex items-center justify-center mb-8">
                        <div className="flex items-center space-x-3">
                            <div className="h-12 w-12 bg-gradient-to-br from-slate-600 to-slate-700 rounded-full flex items-center justify-center">
                                <FileText className="h-7 w-7 text-white" />
                            </div>
                            <span className="text-2xl font-bold bg-gradient-to-r from-slate-700 to-slate-800 bg-clip-text text-transparent">
                                AI Resume Analyzer
                            </span>
                        </div>
                    </div>

                    <Card className="border-0 shadow-xl bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm">
                        <CardHeader className="space-y-1 pb-4">
                            <div className="flex items-center justify-center mb-4">
                                <div className="h-16 w-16 bg-slate-100 dark:bg-slate-900/30 rounded-full flex items-center justify-center">
                                    <Shield className="h-8 w-8 text-slate-600" />
                                </div>
                            </div>
                            <CardTitle className="text-2xl font-bold text-center">Set new password</CardTitle>
                            <CardDescription className="text-center">
                                Choose a strong password for your account
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={submit} className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="email">Email address</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        value={data.email}
                                        className="h-11 bg-gray-50 dark:bg-gray-800/50"
                                        disabled
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="password">New password</Label>
                                    <div className="relative">
                                        <Input
                                            id="password"
                                            type={showPassword ? 'text' : 'password'}
                                            value={data.password}
                                            onChange={(e) => setData('password', e.target.value)}
                                            placeholder="Enter your new password"
                                            className="h-11 pr-10"
                                            required
                                            autoFocus
                                        />
                                        <button
                                            type="button"
                                            className="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
                                            onClick={() => setShowPassword(!showPassword)}
                                        >
                                            {showPassword ? (
                                                <EyeOff className="h-4 w-4" />
                                            ) : (
                                                <Eye className="h-4 w-4" />
                                            )}
                                        </button>
                                    </div>

                                    {/* Password Strength Indicator */}
                                    {data.password && (
                                        <div className="space-y-2">
                                            <div className="flex space-x-1">
                                                {[...Array(5)].map((_, i) => (
                                                    <div
                                                        key={i}
                                                        className={`h-2 w-full rounded-full ${
                                                            i < passwordStrength
                                                                ? strengthColors[passwordStrength - 1]
                                                                : 'bg-gray-200'
                                                        }`}
                                                    />
                                                ))}
                                            </div>
                                            <p className="text-xs text-gray-600">
                                                Password strength: {strengthLabels[passwordStrength - 1] || 'Very Weak'}
                                            </p>
                                        </div>
                                    )}

                                    {errors.password && (
                                        <p className="text-sm text-red-600">{errors.password}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="password_confirmation">Confirm new password</Label>
                                    <div className="relative">
                                        <Input
                                            id="password_confirmation"
                                            type={showPasswordConfirmation ? 'text' : 'password'}
                                            value={data.password_confirmation}
                                            onChange={(e) => setData('password_confirmation', e.target.value)}
                                            placeholder="Confirm your new password"
                                            className="h-11 pr-10"
                                            required
                                        />
                                        <button
                                            type="button"
                                            className="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
                                            onClick={() => setShowPasswordConfirmation(!showPasswordConfirmation)}
                                        >
                                            {showPasswordConfirmation ? (
                                                <EyeOff className="h-4 w-4" />
                                            ) : (
                                                <Eye className="h-4 w-4" />
                                            )}
                                        </button>
                                    </div>

                                    {data.password_confirmation && data.password === data.password_confirmation && (
                                        <div className="flex items-center space-x-1 text-slate-600">
                                            <CheckCircle className="h-4 w-4" />
                                            <span className="text-xs">Passwords match</span>
                                        </div>
                                    )}

                                    {errors.password_confirmation && (
                                        <p className="text-sm text-red-600">{errors.password_confirmation}</p>
                                    )}
                                </div>

                                <Button
                                    type="submit"
                                    disabled={processing}
                                    className="w-full h-11 bg-gradient-to-r from-slate-600 to-slate-700 hover:from-slate-700 hover:to-slate-800 text-white font-medium"
                                >
                                    {processing ? 'Updating password...' : 'Update password'}
                                </Button>
                            </form>

                            {/* Password Requirements */}
                            <div className="mt-6 p-4 bg-gray-50 dark:bg-gray-800/30 rounded-lg">
                                <h4 className="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">
                                    Password requirements:
                                </h4>
                                <ul className="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                                    <li className={`flex items-center space-x-2 ${data.password.length >= 8 ? 'text-slate-600' : ''}`}>
                                        <CheckCircle className={`h-3 w-3 ${data.password.length >= 8 ? 'text-slate-600' : 'text-gray-400'}`} />
                                        <span>At least 8 characters</span>
                                    </li>
                                    <li className={`flex items-center space-x-2 ${/[A-Z]/.test(data.password) ? 'text-slate-600' : ''}`}>
                                        <CheckCircle className={`h-3 w-3 ${/[A-Z]/.test(data.password) ? 'text-slate-600' : 'text-gray-400'}`} />
                                        <span>One uppercase letter</span>
                                    </li>
                                    <li className={`flex items-center space-x-2 ${/[a-z]/.test(data.password) ? 'text-slate-600' : ''}`}>
                                        <CheckCircle className={`h-3 w-3 ${/[a-z]/.test(data.password) ? 'text-slate-600' : 'text-gray-400'}`} />
                                        <span>One lowercase letter</span>
                                    </li>
                                    <li className={`flex items-center space-x-2 ${/\d/.test(data.password) ? 'text-slate-600' : ''}`}>
                                        <CheckCircle className={`h-3 w-3 ${/\d/.test(data.password) ? 'text-slate-600' : 'text-gray-400'}`} />
                                        <span>One number</span>
                                    </li>
                                    <li className={`flex items-center space-x-2 ${/[^A-Za-z\d]/.test(data.password) ? 'text-slate-600' : ''}`}>
                                        <CheckCircle className={`h-3 w-3 ${/[^A-Za-z\d]/.test(data.password) ? 'text-slate-600' : 'text-gray-400'}`} />
                                        <span>One special character</span>
                                    </li>
                                </ul>
                            </div>

                            <div className="mt-6 text-center">
                                <p className="text-sm text-gray-600">
                                    Remember your password?{' '}
                                    <Link
                                        href="/login"
                                        className="font-medium text-slate-600 hover:text-slate-700 hover:underline"
                                    >
                                        Sign in
                                    </Link>
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}