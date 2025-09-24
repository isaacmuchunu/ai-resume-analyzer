import { useState } from 'react';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Label } from '@/components/ui/label';
import { Card } from '@/components/ui/Card';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import {
    ArrowLeft,
    User,
    Mail,
    Lock,
    Bell,
    Palette,
    Trash2,
    Save,
    Eye,
    EyeOff,
    Check,
    X
} from 'lucide-react';

interface SettingsProps {
    user: {
        id: number;
        first_name: string;
        last_name: string;
        email: string;
        initials: string;
        role: string;
    };
}

export default function Settings({ user }: SettingsProps) {
    const [activeTab, setActiveTab] = useState('profile');
    const [showCurrentPassword, setShowCurrentPassword] = useState(false);
    const [showNewPassword, setShowNewPassword] = useState(false);

    // Profile form
    const profileForm = useForm({
        first_name: user.first_name,
        last_name: user.last_name,
        email: user.email,
    });

    // Password form
    const passwordForm = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    // Notification preferences
    const notificationForm = useForm({
        email_notifications: true,
        browser_notifications: true,
        marketing_emails: false,
        analysis_complete: true,
        weekly_reports: true,
    });

    const handleProfileUpdate = (e: React.FormEvent) => {
        e.preventDefault();
        profileForm.put(route('settings.profile.update'));
    };

    const handlePasswordUpdate = (e: React.FormEvent) => {
        e.preventDefault();
        passwordForm.put(route('settings.password.update'), {
            onSuccess: () => {
                passwordForm.reset();
            },
        });
    };

    const handleNotificationUpdate = (e: React.FormEvent) => {
        e.preventDefault();
        notificationForm.put(route('settings.notifications.update'));
    };

    const handleDeleteAccount = () => {
        if (confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
            router.delete(route('settings.account.delete'));
        }
    };

    const tabs = [
        { id: 'profile', label: 'Profile', icon: User },
        { id: 'password', label: 'Password', icon: Lock },
        { id: 'notifications', label: 'Notifications', icon: Bell },
        { id: 'appearance', label: 'Appearance', icon: Palette },
        { id: 'danger', label: 'Danger Zone', icon: Trash2 },
    ];

    return (
        <>
            <Head title="Settings" />

            <div className="min-h-screen bg-gray-50 dark:bg-gray-900 py-12">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-8">
                        <Button variant="ghost" onClick={() => router.get('/dashboard')} className="mb-4">
                            <ArrowLeft className="h-4 w-4 mr-2" />
                            Back to Dashboard
                        </Button>

                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                            Account Settings
                        </h1>
                        <p className="text-gray-600 dark:text-gray-400">
                            Manage your account preferences and security settings
                        </p>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-4 gap-8">
                        {/* Sidebar */}
                        <div className="lg:col-span-1">
                            <Card className="p-4">
                                <div className="space-y-1">
                                    {tabs.map((tab) => {
                                        const Icon = tab.icon;
                                        return (
                                            <button
                                                key={tab.id}
                                                onClick={() => setActiveTab(tab.id)}
                                                className={`w-full flex items-center space-x-3 px-3 py-2 rounded-lg text-left transition-colors ${
                                                    activeTab === tab.id
                                                        ? 'bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200'
                                                        : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'
                                                }`}
                                            >
                                                <Icon className="h-4 w-4" />
                                                <span className="text-sm font-medium">{tab.label}</span>
                                            </button>
                                        );
                                    })}
                                </div>
                            </Card>
                        </div>

                        {/* Main Content */}
                        <div className="lg:col-span-3">
                            {/* Profile Tab */}
                            {activeTab === 'profile' && (
                                <Card className="p-8">
                                    <div className="flex items-center space-x-4 mb-8">
                                        <Avatar className="h-16 w-16">
                                            <AvatarFallback className="bg-slate-100 text-slate-700 text-lg font-semibold">
                                                {user.initials}
                                            </AvatarFallback>
                                        </Avatar>
                                        <div>
                                            <h2 className="text-xl font-semibold text-gray-900 dark:text-white">
                                                Profile Information
                                            </h2>
                                            <p className="text-gray-600 dark:text-gray-400">
                                                Update your personal information
                                            </p>
                                        </div>
                                    </div>

                                    <form onSubmit={handleProfileUpdate} className="space-y-6">
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div>
                                                <Label htmlFor="first_name">First Name</Label>
                                                <Input
                                                    id="first_name"
                                                    value={profileForm.data.first_name}
                                                    onChange={(e) => profileForm.setData('first_name', e.target.value)}
                                                    className="mt-1"
                                                />
                                                {profileForm.errors.first_name && (
                                                    <p className="mt-1 text-sm text-red-600">
                                                        {profileForm.errors.first_name}
                                                    </p>
                                                )}
                                            </div>

                                            <div>
                                                <Label htmlFor="last_name">Last Name</Label>
                                                <Input
                                                    id="last_name"
                                                    value={profileForm.data.last_name}
                                                    onChange={(e) => profileForm.setData('last_name', e.target.value)}
                                                    className="mt-1"
                                                />
                                                {profileForm.errors.last_name && (
                                                    <p className="mt-1 text-sm text-red-600">
                                                        {profileForm.errors.last_name}
                                                    </p>
                                                )}
                                            </div>
                                        </div>

                                        <div>
                                            <Label htmlFor="email">Email Address</Label>
                                            <Input
                                                id="email"
                                                type="email"
                                                value={profileForm.data.email}
                                                onChange={(e) => profileForm.setData('email', e.target.value)}
                                                className="mt-1"
                                            />
                                            {profileForm.errors.email && (
                                                <p className="mt-1 text-sm text-red-600">
                                                    {profileForm.errors.email}
                                                </p>
                                            )}
                                        </div>

                                        <div className="flex justify-end">
                                            <Button
                                                type="submit"
                                                disabled={profileForm.processing}
                                                className="bg-slate-700 hover:bg-slate-800"
                                            >
                                                <Save className="h-4 w-4 mr-2" />
                                                Save Changes
                                            </Button>
                                        </div>
                                    </form>
                                </Card>
                            )}

                            {/* Password Tab */}
                            {activeTab === 'password' && (
                                <Card className="p-8">
                                    <div className="mb-8">
                                        <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                                            Change Password
                                        </h2>
                                        <p className="text-gray-600 dark:text-gray-400">
                                            Ensure your account is using a long, random password to stay secure
                                        </p>
                                    </div>

                                    <form onSubmit={handlePasswordUpdate} className="space-y-6">
                                        <div>
                                            <Label htmlFor="current_password">Current Password</Label>
                                            <div className="relative mt-1">
                                                <Input
                                                    id="current_password"
                                                    type={showCurrentPassword ? 'text' : 'password'}
                                                    value={passwordForm.data.current_password}
                                                    onChange={(e) => passwordForm.setData('current_password', e.target.value)}
                                                    className="pr-10"
                                                />
                                                <button
                                                    type="button"
                                                    onClick={() => setShowCurrentPassword(!showCurrentPassword)}
                                                    className="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500"
                                                >
                                                    {showCurrentPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                                                </button>
                                            </div>
                                            {passwordForm.errors.current_password && (
                                                <p className="mt-1 text-sm text-red-600">
                                                    {passwordForm.errors.current_password}
                                                </p>
                                            )}
                                        </div>

                                        <div>
                                            <Label htmlFor="password">New Password</Label>
                                            <div className="relative mt-1">
                                                <Input
                                                    id="password"
                                                    type={showNewPassword ? 'text' : 'password'}
                                                    value={passwordForm.data.password}
                                                    onChange={(e) => passwordForm.setData('password', e.target.value)}
                                                    className="pr-10"
                                                />
                                                <button
                                                    type="button"
                                                    onClick={() => setShowNewPassword(!showNewPassword)}
                                                    className="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500"
                                                >
                                                    {showNewPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                                                </button>
                                            </div>
                                            {passwordForm.errors.password && (
                                                <p className="mt-1 text-sm text-red-600">
                                                    {passwordForm.errors.password}
                                                </p>
                                            )}
                                        </div>

                                        <div>
                                            <Label htmlFor="password_confirmation">Confirm New Password</Label>
                                            <Input
                                                id="password_confirmation"
                                                type="password"
                                                value={passwordForm.data.password_confirmation}
                                                onChange={(e) => passwordForm.setData('password_confirmation', e.target.value)}
                                                className="mt-1"
                                            />
                                        </div>

                                        <div className="flex justify-end">
                                            <Button
                                                type="submit"
                                                disabled={passwordForm.processing}
                                                className="bg-slate-700 hover:bg-slate-800"
                                            >
                                                <Save className="h-4 w-4 mr-2" />
                                                Update Password
                                            </Button>
                                        </div>
                                    </form>
                                </Card>
                            )}

                            {/* Notifications Tab */}
                            {activeTab === 'notifications' && (
                                <Card className="p-8">
                                    <div className="mb-8">
                                        <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                                            Notification Preferences
                                        </h2>
                                        <p className="text-gray-600 dark:text-gray-400">
                                            Choose what notifications you want to receive
                                        </p>
                                    </div>

                                    <form onSubmit={handleNotificationUpdate} className="space-y-6">
                                        <div className="space-y-4">
                                            {[
                                                { key: 'email_notifications', label: 'Email Notifications', description: 'Receive notifications via email' },
                                                { key: 'browser_notifications', label: 'Browser Notifications', description: 'Show notifications in your browser' },
                                                { key: 'analysis_complete', label: 'Analysis Complete', description: 'When resume analysis is finished' },
                                                { key: 'weekly_reports', label: 'Weekly Reports', description: 'Summary of your activity each week' },
                                                { key: 'marketing_emails', label: 'Marketing Emails', description: 'Product updates and tips' },
                                            ].map((notification) => (
                                                <div key={notification.key} className="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                                                    <div>
                                                        <h4 className="text-sm font-medium text-gray-900 dark:text-white">
                                                            {notification.label}
                                                        </h4>
                                                        <p className="text-sm text-gray-500 dark:text-gray-400">
                                                            {notification.description}
                                                        </p>
                                                    </div>
                                                    <button
                                                        type="button"
                                                        onClick={() => notificationForm.setData(notification.key as any, !notificationForm.data[notification.key as keyof typeof notificationForm.data])}
                                                        className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${
                                                            notificationForm.data[notification.key as keyof typeof notificationForm.data]
                                                                ? 'bg-slate-600'
                                                                : 'bg-gray-200 dark:bg-gray-700'
                                                        }`}
                                                    >
                                                        <span className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${
                                                            notificationForm.data[notification.key as keyof typeof notificationForm.data]
                                                                ? 'translate-x-6'
                                                                : 'translate-x-1'
                                                        }`} />
                                                    </button>
                                                </div>
                                            ))}
                                        </div>

                                        <div className="flex justify-end">
                                            <Button
                                                type="submit"
                                                disabled={notificationForm.processing}
                                                className="bg-slate-700 hover:bg-slate-800"
                                            >
                                                <Save className="h-4 w-4 mr-2" />
                                                Save Preferences
                                            </Button>
                                        </div>
                                    </form>
                                </Card>
                            )}

                            {/* Appearance Tab */}
                            {activeTab === 'appearance' && (
                                <Card className="p-8">
                                    <div className="mb-8">
                                        <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                                            Appearance
                                        </h2>
                                        <p className="text-gray-600 dark:text-gray-400">
                                            Customize the look and feel of the application
                                        </p>
                                    </div>

                                    <div className="space-y-6">
                                        <div>
                                            <Label className="text-base font-medium">Theme</Label>
                                            <p className="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                                Choose your preferred color scheme
                                            </p>
                                            <div className="grid grid-cols-3 gap-3">
                                                {['light', 'dark', 'system'].map((theme) => (
                                                    <button
                                                        key={theme}
                                                        className="p-4 border border-gray-200 dark:border-gray-700 rounded-lg text-center hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                                                    >
                                                        <div className="capitalize font-medium text-gray-900 dark:text-white">
                                                            {theme}
                                                        </div>
                                                    </button>
                                                ))}
                                            </div>
                                        </div>
                                    </div>
                                </Card>
                            )}

                            {/* Danger Zone Tab */}
                            {activeTab === 'danger' && (
                                <Card className="p-8 border-red-200 dark:border-red-800">
                                    <div className="mb-8">
                                        <h2 className="text-xl font-semibold text-red-600 dark:text-red-400 mb-2">
                                            Danger Zone
                                        </h2>
                                        <p className="text-gray-600 dark:text-gray-400">
                                            Irreversible and destructive actions
                                        </p>
                                    </div>

                                    <div className="space-y-6">
                                        <div className="border border-red-200 dark:border-red-800 rounded-lg p-6">
                                            <div className="flex items-start justify-between">
                                                <div>
                                                    <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                                        Delete Account
                                                    </h3>
                                                    <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                                        Once you delete your account, there is no going back. Please be certain.
                                                    </p>
                                                    <ul className="text-sm text-gray-500 dark:text-gray-400 space-y-1">
                                                        <li>• All your resumes and analysis data will be permanently deleted</li>
                                                        <li>• Your subscription will be cancelled immediately</li>
                                                        <li>• You will lose access to all premium features</li>
                                                    </ul>
                                                </div>
                                                <Button
                                                    variant="destructive"
                                                    onClick={handleDeleteAccount}
                                                    className="ml-4"
                                                >
                                                    <Trash2 className="h-4 w-4 mr-2" />
                                                    Delete Account
                                                </Button>
                                            </div>
                                        </div>
                                    </div>
                                </Card>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}