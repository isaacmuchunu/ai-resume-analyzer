import { useState, PropsWithChildren } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import { Button } from '@/components/ui/Button';
import {
    LayoutDashboard,
    Users,
    FileText,
    BarChart3,
    Settings,
    Shield,
    Menu,
    X,
    LogOut,
    Bell,
    Search,
    ChevronDown,
    Home,
    Activity,
    CreditCard,
    Building2
} from 'lucide-react';

interface User {
    id: number;
    full_name: string;
    email: string;
    role: string;
    initials: string;
}

interface PageProps {
    auth: {
        user: User;
    };
}

const navigation = [
    { name: 'Dashboard', href: 'admin.dashboard', icon: LayoutDashboard, current: true },
    { name: 'Users', href: 'admin.users.index', icon: Users },
    { name: 'Resumes', href: '#', icon: FileText },
    { name: 'Analytics', href: '#', icon: BarChart3 },
    { name: 'Subscriptions', href: '#', icon: CreditCard },
    { name: 'Tenants', href: '#', icon: Building2 },
    { name: 'System Logs', href: '#', icon: Activity },
    { name: 'Settings', href: '#', icon: Settings },
];

export default function AdminLayout({ children }: PropsWithChildren) {
    const { auth } = usePage<PageProps>().props;
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const [userMenuOpen, setUserMenuOpen] = useState(false);

    const handleLogout = () => {
        router.post(route('admin.logout'));
    };

    const currentRoute = route().current();

    return (
        <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
            {/* Mobile sidebar */}
            <div className={`fixed inset-0 z-50 lg:hidden ${sidebarOpen ? '' : 'hidden'}`}>
                <div className="fixed inset-0 bg-gray-900/80" onClick={() => setSidebarOpen(false)} />
                <div className="fixed inset-y-0 left-0 w-64 bg-white dark:bg-gray-800 shadow-xl">
                    <div className="flex h-16 items-center justify-between px-6 border-b border-gray-200 dark:border-gray-700">
                        <div className="flex items-center space-x-3">
                            <Shield className="h-8 w-8 text-green-600" />
                            <span className="text-xl font-bold text-gray-900 dark:text-gray-100">
                                Admin
                            </span>
                        </div>
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => setSidebarOpen(false)}
                            className="p-2"
                        >
                            <X className="h-4 w-4" />
                        </Button>
                    </div>
                    <nav className="mt-6 px-3">
                        {navigation.map((item) => {
                            const isCurrent = currentRoute === item.href;
                            return (
                                <Link
                                    key={item.name}
                                    href={item.href === '#' ? '#' : route(item.href)}
                                    className={`group flex items-center px-3 py-2 text-sm font-medium rounded-lg mb-1 transition-colors ${
                                        isCurrent
                                            ? 'bg-green-100 dark:bg-green-900/20 text-green-700 dark:text-green-300'
                                            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                                    }`}
                                >
                                    <item.icon className="mr-3 h-5 w-5 flex-shrink-0" />
                                    {item.name}
                                </Link>
                            );
                        })}
                    </nav>
                </div>
            </div>

            {/* Static sidebar for desktop */}
            <div className="hidden lg:fixed lg:inset-y-0 lg:flex lg:w-64 lg:flex-col">
                <div className="flex flex-col flex-grow bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700">
                    <div className="flex h-16 items-center px-6 border-b border-gray-200 dark:border-gray-700">
                        <div className="flex items-center space-x-3">
                            <Shield className="h-8 w-8 text-green-600" />
                            <span className="text-xl font-bold text-gray-900 dark:text-gray-100">
                                Admin Panel
                            </span>
                        </div>
                    </div>
                    <nav className="mt-6 px-3 flex-1">
                        {navigation.map((item) => {
                            const isCurrent = currentRoute === item.href;
                            return (
                                <Link
                                    key={item.name}
                                    href={item.href === '#' ? '#' : route(item.href)}
                                    className={`group flex items-center px-3 py-2 text-sm font-medium rounded-lg mb-1 transition-colors ${
                                        isCurrent
                                            ? 'bg-green-100 dark:bg-green-900/20 text-green-700 dark:text-green-300'
                                            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                                    }`}
                                >
                                    <item.icon className="mr-3 h-5 w-5 flex-shrink-0" />
                                    {item.name}
                                </Link>
                            );
                        })}
                    </nav>
                </div>
            </div>

            {/* Main content */}
            <div className="lg:pl-64 flex flex-col flex-1">
                {/* Top navigation */}
                <div className="sticky top-0 z-40 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <div className="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
                        <div className="flex items-center">
                            <Button
                                variant="ghost"
                                size="sm"
                                className="lg:hidden mr-2"
                                onClick={() => setSidebarOpen(true)}
                            >
                                <Menu className="h-5 w-5" />
                            </Button>

                            {/* Breadcrumb */}
                            <div className="flex items-center space-x-2 text-sm">
                                <Link
                                    href={route('welcome')}
                                    className="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 flex items-center"
                                >
                                    <Home className="h-4 w-4 mr-1" />
                                    Main Site
                                </Link>
                                <span className="text-gray-400 dark:text-gray-600">/</span>
                                <span className="text-gray-900 dark:text-gray-100 font-medium">
                                    Admin Panel
                                </span>
                            </div>
                        </div>

                        <div className="flex items-center space-x-4">
                            {/* Search */}
                            <div className="hidden md:block">
                                <div className="relative">
                                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                                    <input
                                        type="text"
                                        placeholder="Search..."
                                        className="pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                    />
                                </div>
                            </div>

                            {/* Notifications */}
                            <Button variant="ghost" size="sm" className="relative">
                                <Bell className="h-5 w-5" />
                                <span className="absolute -top-1 -right-1 h-4 w-4 bg-red-500 text-white text-xs rounded-full flex items-center justify-center">
                                    2
                                </span>
                            </Button>

                            {/* User menu */}
                            <div className="relative">
                                <Button
                                    variant="ghost"
                                    className="flex items-center space-x-2 p-2"
                                    onClick={() => setUserMenuOpen(!userMenuOpen)}
                                >
                                    <div className="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                                        <span className="text-sm font-semibold text-green-600 dark:text-green-400">
                                            {auth.user.initials || 'SA'}
                                        </span>
                                    </div>
                                    <div className="hidden md:block text-left">
                                        <p className="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {auth.user.full_name}
                                        </p>
                                        <p className="text-xs text-gray-500 dark:text-gray-400 capitalize">
                                            {auth.user.role.replace('_', ' ')}
                                        </p>
                                    </div>
                                    <ChevronDown className="h-4 w-4 text-gray-500" />
                                </Button>

                                {userMenuOpen && (
                                    <div className="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1">
                                        <div className="px-4 py-2 border-b border-gray-200 dark:border-gray-700">
                                            <p className="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {auth.user.full_name}
                                            </p>
                                            <p className="text-xs text-gray-500 dark:text-gray-400">
                                                {auth.user.email}
                                            </p>
                                        </div>
                                        <Link
                                            href="#"
                                            className="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                                        >
                                            Profile Settings
                                        </Link>
                                        <Link
                                            href="#"
                                            className="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                                        >
                                            System Settings
                                        </Link>
                                        <div className="border-t border-gray-200 dark:border-gray-700 my-1"></div>
                                        <button
                                            onClick={handleLogout}
                                            className="flex items-center w-full px-4 py-2 text-sm text-red-700 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20"
                                        >
                                            <LogOut className="h-4 w-4 mr-2" />
                                            Sign Out
                                        </button>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>

                {/* Page content */}
                <main className="flex-1">
                    {children}
                </main>
            </div>
        </div>
    );
}