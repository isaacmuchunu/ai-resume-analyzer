import { PropsWithChildren } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { User, FileText, BarChart3, Settings, LogOut, Menu, X } from 'lucide-react';
import { useState } from 'react';

interface AppLayoutProps extends PropsWithChildren {
    title?: string;
    tenant?: {
        name: string;
        branding?: {
            logo?: string;
            primaryColor?: string;
            secondaryColor?: string;
        };
    };
    user?: {
        id: number;
        email: string;
        first_name: string;
        last_name: string;
        role: string;
    };
}

export default function AppLayout({ children, title, tenant, user }: AppLayoutProps) {
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const { url } = usePage();

    const navigation = [
        { name: 'Dashboard', href: '/', icon: BarChart3 },
        { name: 'Resumes', href: '/resumes', icon: FileText },
        { name: 'Settings', href: '/settings', icon: Settings },
    ];

    const isActive = (href: string) => {
        return url === href || (href !== '/' && url.startsWith(href));
    };

    return (
        <>
            <Head title={title ? `${title} - ${tenant?.name || 'Resume Analyzer'}` : tenant?.name || 'Resume Analyzer'} />

            <div className="min-h-screen bg-gray-50">
                {/* Mobile sidebar overlay */}
                {sidebarOpen && (
                    <div className="fixed inset-0 z-40 lg:hidden">
                        <div className="fixed inset-0 bg-black bg-opacity-25" onClick={() => setSidebarOpen(false)} />
                    </div>
                )}

                {/* Mobile sidebar */}
                <div className={`fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-lg transform transition-transform lg:hidden ${
                    sidebarOpen ? 'translate-x-0' : '-translate-x-full'
                }`}>
                    <div className="flex items-center justify-between p-4 border-b">
                        <div className="flex items-center space-x-2">
                            {tenant?.branding?.logo ? (
                                <img src={tenant.branding.logo} alt={tenant.name} className="h-8 w-auto" />
                            ) : (
                                <div className="h-8 w-8 bg-blue-600 rounded flex items-center justify-center">
                                    <FileText className="h-5 w-5 text-white" />
                                </div>
                            )}
                            <span className="font-bold text-gray-900">{tenant?.name || 'Resume Analyzer'}</span>
                        </div>
                        <button onClick={() => setSidebarOpen(false)}>
                            <X className="h-6 w-6 text-gray-400" />
                        </button>
                    </div>
                    <nav className="mt-4">
                        {navigation.map((item) => {
                            const Icon = item.icon;
                            return (
                                <Link
                                    key={item.name}
                                    href={item.href}
                                    className={`flex items-center px-4 py-2 text-sm font-medium transition-colors ${
                                        isActive(item.href)
                                            ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-700'
                                            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                                    }`}
                                    onClick={() => setSidebarOpen(false)}
                                >
                                    <Icon className="mr-3 h-5 w-5" />
                                    {item.name}
                                </Link>
                            );
                        })}
                    </nav>
                </div>

                {/* Desktop sidebar */}
                <div className="hidden lg:fixed lg:inset-y-0 lg:left-0 lg:z-50 lg:block lg:w-64 lg:bg-white lg:shadow-lg">
                    <div className="flex items-center px-6 py-4 border-b">
                        <div className="flex items-center space-x-2">
                            {tenant?.branding?.logo ? (
                                <img src={tenant.branding.logo} alt={tenant.name} className="h-8 w-auto" />
                            ) : (
                                <div className="h-8 w-8 bg-blue-600 rounded flex items-center justify-center">
                                    <FileText className="h-5 w-5 text-white" />
                                </div>
                            )}
                            <span className="font-bold text-gray-900">{tenant?.name || 'Resume Analyzer'}</span>
                        </div>
                    </div>
                    <nav className="mt-4">
                        {navigation.map((item) => {
                            const Icon = item.icon;
                            return (
                                <Link
                                    key={item.name}
                                    href={item.href}
                                    className={`flex items-center px-6 py-3 text-sm font-medium transition-colors ${
                                        isActive(item.href)
                                            ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-700'
                                            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                                    }`}
                                >
                                    <Icon className="mr-3 h-5 w-5" />
                                    {item.name}
                                </Link>
                            );
                        })}
                    </nav>
                </div>

                {/* Main content */}
                <div className="lg:pl-64">
                    {/* Top navigation */}
                    <div className="sticky top-0 z-10 bg-white shadow-sm border-b">
                        <div className="flex items-center justify-between px-4 py-3">
                            <button
                                onClick={() => setSidebarOpen(true)}
                                className="lg:hidden p-2 rounded-md text-gray-400 hover:text-gray-500"
                            >
                                <Menu className="h-6 w-6" />
                            </button>

                            <div className="flex items-center space-x-4">
                                {user && (
                                    <div className="flex items-center space-x-3">
                                        <div className="flex items-center space-x-2">
                                            <div className="h-8 w-8 bg-gray-200 rounded-full flex items-center justify-center">
                                                <User className="h-5 w-5 text-gray-600" />
                                            </div>
                                            <div className="hidden sm:block">
                                                <p className="text-sm font-medium text-gray-900">
                                                    {user.first_name} {user.last_name}
                                                </p>
                                                <p className="text-xs text-gray-500">{user.email}</p>
                                            </div>
                                        </div>
                                        <Link
                                            href="/logout"
                                            method="post"
                                            className="p-2 text-gray-400 hover:text-gray-500"
                                        >
                                            <LogOut className="h-5 w-5" />
                                        </Link>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Page content */}
                    <main className="flex-1">
                        {children}
                    </main>
                </div>
            </div>
        </>
    );
}