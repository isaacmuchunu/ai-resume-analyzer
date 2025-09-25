import { Head } from '@inertiajs/react';
import { Card } from '@/components/ui/Card';
import { Badge } from '@/components/ui/Badge';
import {
    Users,
    FileText,
    Activity,
    CreditCard,
    Building2,
    DollarSign,
    TrendingUp,
    Eye,
    Calendar,
    BarChart3,
    UserCheck,
    AlertCircle
} from 'lucide-react';
import AdminLayout from '@/layouts/AdminLayout';

interface DashboardStats {
    total_users: number;
    total_resumes: number;
    total_analyses: number;
    active_subscriptions: number;
    total_tenants: number;
    revenue_this_month: number;
}

interface RecentUser {
    id: number;
    full_name: string;
    email: string;
    role: string;
    created_at: string;
    resumes_count: number;
    current_plan: string;
}

interface RecentActivity {
    id: number;
    description: string;
    created_at: string;
    user: {
        name: string;
        email: string;
    } | null;
    properties: any;
}

interface MonthlyAnalytic {
    month: string;
    users: number;
    resumes: number;
    analyses: number;
}

interface TopUser {
    id: number;
    full_name: string;
    email: string;
    resumes_count: number;
    current_plan: string;
}

interface Props {
    stats: DashboardStats;
    recent_users: RecentUser[];
    recent_activities: RecentActivity[];
    monthly_analytics: MonthlyAnalytic[];
    top_users: TopUser[];
}

const StatCard = ({ title, value, description, icon, trend, trendValue }: {
    title: string;
    value: string | number;
    description: string;
    icon: React.ReactNode;
    trend?: 'up' | 'down' | 'neutral';
    trendValue?: string;
}) => (
    <Card className="p-6 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition-shadow">
        <div className="flex items-center justify-between">
            <div className="flex items-center space-x-4">
                <div className="p-3 bg-slate-50 dark:bg-slate-900/20 rounded-lg">
                    <div className="text-slate-600 dark:text-slate-400">
                        {icon}
                    </div>
                </div>
                <div>
                    <p className="text-sm font-medium text-gray-600 dark:text-gray-400">
                        {title}
                    </p>
                    <p className="text-2xl font-bold text-gray-900 dark:text-gray-100">
                        {typeof value === 'number' ? value.toLocaleString() : value}
                    </p>
                </div>
            </div>
        </div>
        <div className="mt-4 flex items-center justify-between">
            <p className="text-xs text-gray-500 dark:text-gray-400">
                {description}
            </p>
            {trend && trendValue && (
                <div className={`flex items-center text-xs ${
                    trend === 'up' ? 'text-green-600' :
                    trend === 'down' ? 'text-red-600' :
                    'text-gray-500'
                }`}>
                    {trend === 'up' && <TrendingUp className="h-3 w-3 mr-1" />}
                    {trendValue}
                </div>
            )}
        </div>
    </Card>
);

export default function AdminDashboard({
    stats,
    recent_users,
    recent_activities,
    monthly_analytics,
    top_users
}: Props) {
    return (
        <AdminLayout>
            <Head title="Admin Dashboard" />

            <div className="p-6 space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-gray-100">
                            Admin Dashboard
                        </h1>
                        <p className="text-gray-600 dark:text-gray-400 mt-1">
                            System overview and management interface
                        </p>
                    </div>
                    <Badge variant="outline" className="px-3 py-1">
                        <Activity className="h-3 w-3 mr-1" />
                        Live Data
                    </Badge>
                </div>

                {/* Stats Grid */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-6">
                    <StatCard
                        title="Total Users"
                        value={stats.total_users}
                        description="Registered users"
                        icon={<Users className="h-5 w-5" />}
                        trend="neutral"
                    />
                    <StatCard
                        title="Total Resumes"
                        value={stats.total_resumes}
                        description="Uploaded resumes"
                        icon={<FileText className="h-5 w-5" />}
                        trend="neutral"
                    />
                    <StatCard
                        title="Analyses"
                        value={stats.total_analyses}
                        description="Completed analyses"
                        icon={<BarChart3 className="h-5 w-5" />}
                        trend="neutral"
                    />
                    <StatCard
                        title="Active Subscriptions"
                        value={stats.active_subscriptions}
                        description="Paying customers"
                        icon={<CreditCard className="h-5 w-5" />}
                        trend="neutral"
                    />
                    <StatCard
                        title="Total Tenants"
                        value={stats.total_tenants}
                        description="Tenant instances"
                        icon={<Building2 className="h-5 w-5" />}
                        trend="neutral"
                    />
                    <StatCard
                        title="Monthly Revenue"
                        value={`$${stats.revenue_this_month.toFixed(2)}`}
                        description="Current month"
                        icon={<DollarSign className="h-5 w-5" />}
                        trend="up"
                        trendValue="Current period"
                    />
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Recent Users */}
                    <Card className="p-6 bg-white dark:bg-gray-800">
                        <div className="flex items-center justify-between mb-4">
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                Recent Users
                            </h3>
                            <Eye className="h-4 w-4 text-gray-500" />
                        </div>
                        <div className="space-y-3">
                            {recent_users.map((user) => (
                                <div key={user.id} className="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div className="flex items-center space-x-3">
                                        <div className="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                                            <span className="text-xs font-semibold text-green-600 dark:text-green-400">
                                                {user.full_name.split(' ').map(n => n[0]).join('')}
                                            </span>
                                        </div>
                                        <div>
                                            <p className="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {user.full_name}
                                            </p>
                                            <p className="text-xs text-gray-500 dark:text-gray-400">
                                                {user.email}
                                            </p>
                                        </div>
                                    </div>
                                    <div className="text-right">
                                        <Badge variant={user.role === 'admin' ? 'default' : 'secondary'}>
                                            {user.current_plan}
                                        </Badge>
                                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            {user.resumes_count} resumes
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </Card>

                    {/* Recent Activities */}
                    <Card className="p-6 bg-white dark:bg-gray-800">
                        <div className="flex items-center justify-between mb-4">
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                Recent Activities
                            </h3>
                            <Activity className="h-4 w-4 text-gray-500" />
                        </div>
                        <div className="space-y-3">
                            {recent_activities.slice(0, 8).map((activity) => (
                                <div key={activity.id} className="flex items-start space-x-3 p-2">
                                    <div className="w-2 h-2 bg-green-500 rounded-full mt-2 flex-shrink-0"></div>
                                    <div className="flex-1 min-w-0">
                                        <p className="text-sm text-gray-900 dark:text-gray-100">
                                            {activity.description}
                                        </p>
                                        <div className="flex items-center space-x-2 mt-1">
                                            <p className="text-xs text-gray-500 dark:text-gray-400">
                                                {activity.user?.name || 'System'}
                                            </p>
                                            <span className="text-xs text-gray-400">•</span>
                                            <p className="text-xs text-gray-500 dark:text-gray-400">
                                                {new Date(activity.created_at).toLocaleDateString()}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </Card>
                </div>

                {/* Top Users and Monthly Analytics */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Top Users */}
                    <Card className="p-6 bg-white dark:bg-gray-800">
                        <div className="flex items-center justify-between mb-4">
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                Top Active Users
                            </h3>
                            <UserCheck className="h-4 w-4 text-gray-500" />
                        </div>
                        <div className="space-y-3">
                            {top_users.map((user, index) => (
                                <div key={user.id} className="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div className="flex items-center space-x-3">
                                        <div className="w-6 h-6 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                                            <span className="text-xs font-bold text-green-600 dark:text-green-400">
                                                {index + 1}
                                            </span>
                                        </div>
                                        <div>
                                            <p className="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {user.full_name}
                                            </p>
                                            <p className="text-xs text-gray-500 dark:text-gray-400">
                                                {user.current_plan} plan
                                            </p>
                                        </div>
                                    </div>
                                    <div className="text-right">
                                        <p className="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                            {user.resumes_count}
                                        </p>
                                        <p className="text-xs text-gray-500 dark:text-gray-400">
                                            resumes
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </Card>

                    {/* Monthly Analytics Summary */}
                    <Card className="p-6 bg-white dark:bg-gray-800">
                        <div className="flex items-center justify-between mb-4">
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                Monthly Trends
                            </h3>
                            <Calendar className="h-4 w-4 text-gray-500" />
                        </div>
                        <div className="space-y-4">
                            {monthly_analytics.slice(-3).map((month, index) => (
                                <div key={month.month} className="border-l-4 border-green-500 pl-4">
                                    <h4 className="font-medium text-gray-900 dark:text-gray-100">
                                        {month.month}
                                    </h4>
                                    <div className="grid grid-cols-3 gap-4 mt-2">
                                        <div className="text-center">
                                            <p className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                                {month.users}
                                            </p>
                                            <p className="text-xs text-gray-500 dark:text-gray-400">
                                                Users
                                            </p>
                                        </div>
                                        <div className="text-center">
                                            <p className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                                {month.resumes}
                                            </p>
                                            <p className="text-xs text-gray-500 dark:text-gray-400">
                                                Resumes
                                            </p>
                                        </div>
                                        <div className="text-center">
                                            <p className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                                {month.analyses}
                                            </p>
                                            <p className="text-xs text-gray-500 dark:text-gray-400">
                                                Analyses
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </Card>
                </div>

                {/* System Status */}
                <Card className="p-6 bg-white dark:bg-gray-800">
                    <div className="flex items-center justify-between mb-4">
                        <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            System Status
                        </h3>
                        <div className="flex items-center space-x-2">
                            <div className="w-2 h-2 bg-slate-500 rounded-full"></div>
                            <span className="text-sm text-slate-600 dark:text-slate-400">All Systems Operational</span>
                        </div>
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div className="text-center p-4 bg-slate-50 dark:bg-slate-900/20 rounded-lg">
                            <div className="text-slate-600 dark:text-slate-400 mb-2">
                                <Activity className="h-6 w-6 mx-auto" />
                            </div>
                            <p className="text-sm font-medium text-gray-900 dark:text-gray-100">API Status</p>
                            <p className="text-xs text-slate-600 dark:text-slate-400 mt-1">Online</p>
                        </div>
                        <div className="text-center p-4 bg-slate-50 dark:bg-slate-900/20 rounded-lg">
                            <div className="text-slate-600 dark:text-slate-400 mb-2">
                                <FileText className="h-6 w-6 mx-auto" />
                            </div>
                            <p className="text-sm font-medium text-gray-900 dark:text-gray-100">File Processing</p>
                            <p className="text-xs text-slate-600 dark:text-slate-400 mt-1">Active</p>
                        </div>
                        <div className="text-center p-4 bg-slate-50 dark:bg-slate-900/20 rounded-lg">
                            <div className="text-slate-600 dark:text-slate-400 mb-2">
                                <CreditCard className="h-6 w-6 mx-auto" />
                            </div>
                            <p className="text-sm font-medium text-gray-900 dark:text-gray-100">Billing System</p>
                            <p className="text-xs text-slate-600 dark:text-slate-400 mt-1">Operational</p>
                        </div>
                    </div>
                </Card>
            </div>
        </AdminLayout>
    );
}