import { Head, Link, usePage, router } from '@inertiajs/react';
import {
    FileText,
    Plus,
    BarChart3,
    Settings,
    LogOut,
    Upload,
    TrendingUp,
    Crown,
    Users,
    Zap,
    Target,
    Award
} from 'lucide-react';
import { Button } from '@/components/ui/Button';
import { Avatar, AvatarFallback } from '@/components/ui/Avatar';
import { StatsCard } from '@/components/dashboard/StatsCard';
import { ResumeCard } from '@/components/dashboard/ResumeCard';
import { SubscriptionCard } from '@/components/dashboard/SubscriptionCard';
import { ActivityFeed } from '@/components/dashboard/ActivityFeed';

interface DashboardProps {
    tenant: {
        name: string;
        plan: string;
        branding: any;
    };
    user: {
        id: number;
        email: string;
        first_name: string;
        last_name: string;
        full_name: string;
        initials: string;
        role: string;
        current_plan: string;
    };
    stats: {
        total_resumes: number;
        analyzed_resumes: number;
        average_score: number;
        recent_uploads: number;
        processing_resumes: number;
        failed_analyses: number;
    };
    recent_resumes: any[];
    subscription?: any;
    weekly_analytics: any;
    recent_activity: any[];
}

export default function Dashboard() {
    const { tenant, user, stats, recent_resumes, subscription, weekly_analytics, recent_activity } = usePage<DashboardProps>().props;

    const handleLogout = () => {
        router.post('/logout');
    };

    const handleDeleteResume = (id: number) => {
        if (confirm('Are you sure you want to delete this resume?')) {
            router.delete(`/resumes/${id}`);
        }
    };

    const handleReanalyzeResume = (id: number) => {
        router.post(`/resumes/${id}/reanalyze`);
    };

    return (
        <>
            <Head title="Dashboard" />

            <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
                {/* Header */}
                <header className="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="flex justify-between items-center py-4">
                            {/* Logo */}
                            <div className="flex items-center space-x-3">
                                <div className="h-10 w-10 bg-slate-800 rounded-lg flex items-center justify-center">
                                    <FileText className="h-6 w-6 text-white" />
                                </div>
                                <div>
                                    <h1 className="text-xl font-bold text-gray-900 dark:text-white">
                                        {tenant.name}
                                    </h1>
                                </div>
                            </div>

                            {/* Navigation */}
                            <nav className="hidden md:flex items-center space-x-6">
                                <Link
                                    href="/dashboard"
                                    className="text-slate-700 hover:text-slate-800 font-medium"
                                >
                                    Dashboard
                                </Link>
                                <Link
                                    href="/resumes"
                                    className="text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white"
                                >
                                    Resumes
                                </Link>
                                <Link
                                    href="/analytics"
                                    className="text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white"
                                >
                                    Analytics
                                </Link>
                                <Link
                                    href="/subscription"
                                    className="text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white flex items-center space-x-1"
                                >
                                    <Crown className="h-4 w-4" />
                                    <span>Subscription</span>
                                </Link>
                            </nav>

                            {/* User Menu */}
                            <div className="flex items-center space-x-4">
                                <Button asChild>
                                    <Link href="/resumes/upload">
                                        <Plus className="h-4 w-4 mr-2" />
                                        Upload Resume
                                    </Link>
                                </Button>

                                <div className="relative group">
                                    <Avatar className="h-8 w-8 cursor-pointer">
                                        <AvatarFallback className="bg-slate-100 text-slate-700 text-sm font-medium">
                                            {user.initials}
                                        </AvatarFallback>
                                    </Avatar>

                                    <div className="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg py-1 hidden group-hover:block z-10">
                                        <div className="px-4 py-2 border-b border-gray-200 dark:border-gray-700">
                                            <p className="text-sm font-medium text-gray-900 dark:text-white">
                                                {user.full_name}
                                            </p>
                                            <p className="text-sm text-gray-500">{user.email}</p>
                                        </div>
                                        <Link
                                            href="/settings"
                                            className="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                                        >
                                            <Settings className="h-4 w-4 mr-2" />
                                            Settings
                                        </Link>
                                        <button
                                            onClick={handleLogout}
                                            className="flex items-center w-full px-4 py-2 text-sm text-red-600 hover:bg-gray-100 dark:hover:bg-gray-700"
                                        >
                                            <LogOut className="h-4 w-4 mr-2" />
                                            Sign Out
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </header>

                {/* Main Content */}
                <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    {/* Welcome Header */}
                    <div className="mb-8">
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                            Welcome back, {user.first_name}!
                        </h1>
                        <p className="text-gray-600 dark:text-gray-400">
                            Here's what's happening with your resumes today.
                        </p>
                    </div>

                    {/* Stats Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <StatsCard
                            title="Total Resumes"
                            value={stats.total_resumes}
                            description="All uploaded resumes"
                            icon={<FileText className="h-4 w-4" />}
                            trend={stats.recent_uploads > 0 ? 'up' : 'neutral'}
                            trendValue={`+${stats.recent_uploads} this week`}
                        />
                        <StatsCard
                            title="Analyzed"
                            value={stats.analyzed_resumes}
                            description="Successfully analyzed"
                            icon={<BarChart3 className="h-4 w-4" />}
                            trend="up"
                            trendValue="12% completion rate"
                        />
                        <StatsCard
                            title="Average Score"
                            value={stats.average_score > 0 ? `${stats.average_score}/100` : 'N/A'}
                            description="Overall performance"
                            icon={<Award className="h-4 w-4" />}
                            trend={stats.average_score >= 75 ? 'up' : stats.average_score >= 60 ? 'neutral' : 'down'}
                            trendValue={`${stats.average_score >= 75 ? 'Excellent' : stats.average_score >= 60 ? 'Good' : 'Needs work'}`}
                        />
                        <StatsCard
                            title="Processing"
                            value={stats.processing_resumes}
                            description="Currently analyzing"
                            icon={<Zap className="h-4 w-4" />}
                            trend="neutral"
                        />
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        {/* Main Content */}
                        <div className="lg:col-span-2 space-y-6">
                            {/* Recent Resumes */}
                            <div>
                                <div className="flex items-center justify-between mb-6">
                                    <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
                                        Recent Resumes
                                    </h2>
                                    <Button variant="outline" size="sm" asChild>
                                        <Link href="/resumes">
                                            View All
                                        </Link>
                                    </Button>
                                </div>

                                {recent_resumes.length > 0 ? (
                                    <div className="space-y-4">
                                        {recent_resumes.map((resume) => (
                                            <ResumeCard
                                                key={resume.id}
                                                resume={resume}
                                                onDelete={handleDeleteResume}
                                                onReanalyze={handleReanalyzeResume}
                                            />
                                        ))}
                                    </div>
                                ) : (
                                    <div className="bg-white dark:bg-gray-800 rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-600 p-8 text-center">
                                        <Upload className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                                        <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                            No resumes yet
                                        </h3>
                                        <p className="text-gray-600 dark:text-gray-400 mb-4">
                                            Upload your first resume to get started with AI-powered analysis.
                                        </p>
                                        <Button asChild>
                                            <Link href="/resumes/upload">
                                                <Plus className="h-4 w-4 mr-2" />
                                                Upload Resume
                                            </Link>
                                        </Button>
                                    </div>
                                )}
                            </div>

                            {/* Quick Actions */}
                            <div className="bg-white dark:bg-gray-800 rounded-xl p-6">
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                    Quick Actions
                                </h3>
                                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <Button variant="outline" className="flex flex-col items-center p-6 h-auto" asChild>
                                        <Link href="/resumes/upload">
                                            <Upload className="h-6 w-6 mb-2" />
                                            <span>Upload Resume</span>
                                        </Link>
                                    </Button>
                                    <Button variant="outline" className="flex flex-col items-center p-6 h-auto" asChild>
                                        <Link href="/analytics">
                                            <BarChart3 className="h-6 w-6 mb-2" />
                                            <span>View Analytics</span>
                                        </Link>
                                    </Button>
                                    <Button variant="outline" className="flex flex-col items-center p-6 h-auto" asChild>
                                        <Link href="/subscription/upgrade">
                                            <Crown className="h-6 w-6 mb-2" />
                                            <span>Upgrade Plan</span>
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        </div>

                        {/* Sidebar */}
                        <div className="space-y-6">
                            {/* Subscription Card */}
                            <SubscriptionCard subscription={subscription} />

                            {/* Activity Feed */}
                            <ActivityFeed activities={recent_activity || []} />

                            {/* AI Features Highlight */}
                            <div className="bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-900/20 dark:to-slate-800/20 rounded-xl p-6 border border-slate-200 dark:border-slate-800">
                                <h3 className="text-lg font-semibold text-slate-800 dark:text-slate-200 mb-4">
                                    AI-Powered Features
                                </h3>
                                <div className="space-y-3">
                                    <div className="flex items-start space-x-3">
                                        <Target className="h-5 w-5 text-slate-600 dark:text-slate-400 mt-0.5" />
                                        <div>
                                            <p className="text-sm font-medium text-slate-800 dark:text-slate-200">
                                                ATS Optimization
                                            </p>
                                            <p className="text-xs text-slate-600 dark:text-slate-300">
                                                99% compatibility with major ATS systems
                                            </p>
                                        </div>
                                    </div>
                                    <div className="flex items-start space-x-3">
                                        <Award className="h-5 w-5 text-slate-600 dark:text-slate-400 mt-0.5" />
                                        <div>
                                            <p className="text-sm font-medium text-slate-800 dark:text-slate-200">
                                                Content Analysis
                                            </p>
                                            <p className="text-xs text-slate-600 dark:text-slate-300">
                                                Professional writing standards
                                            </p>
                                        </div>
                                    </div>
                                    <div className="flex items-start space-x-3">
                                        <BarChart3 className="h-5 w-5 text-slate-600 dark:text-slate-400 mt-0.5" />
                                        <div>
                                            <p className="text-sm font-medium text-slate-800 dark:text-slate-200">
                                                Skills Assessment
                                            </p>
                                            <p className="text-xs text-slate-600 dark:text-slate-300">
                                                Market demand analysis
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </>
    );
}