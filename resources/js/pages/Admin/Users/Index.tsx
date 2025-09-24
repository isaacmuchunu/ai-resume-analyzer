import { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import {
    Users,
    Search,
    Filter,
    MoreHorizontal,
    Eye,
    Edit,
    Trash2,
    Plus,
    Download,
    Calendar,
    Mail,
    Crown,
    User
} from 'lucide-react';
import AdminLayout from '@/layouts/AdminLayout';

interface User {
    id: number;
    full_name: string;
    email: string;
    role: string;
    created_at: string;
    resumes_count: number;
    current_plan: string;
    subscription_status: string;
    last_activity: string | null;
}

interface Filters {
    search: string;
    role: string;
    plan: string;
    sort: string;
    direction: string;
}

interface PaginatedUsers {
    data: User[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
}

interface Props {
    users: PaginatedUsers;
    filters: Filters;
}

export default function UsersIndex({ users, filters }: Props) {
    const [showFilters, setShowFilters] = useState(false);
    const [selectedUsers, setSelectedUsers] = useState<number[]>([]);

    const { data, setData, get, processing } = useForm({
        search: filters.search || '',
        role: filters.role || '',
        plan: filters.plan || '',
        sort: filters.sort || 'created_at',
        direction: filters.direction || 'desc',
    });

    const handleSearch = () => {
        get(route('admin.users.index'), {
            preserveState: true,
            replace: true,
        });
    };

    const handleSort = (field: string) => {
        const newDirection = data.sort === field && data.direction === 'asc' ? 'desc' : 'asc';
        setData('sort', field);
        setData('direction', newDirection);
        get(route('admin.users.index'), {
            preserveState: true,
            replace: true,
        });
    };

    const getRoleBadgeVariant = (role: string) => {
        switch (role) {
            case 'super_admin':
                return 'destructive';
            case 'admin':
                return 'default';
            default:
                return 'secondary';
        }
    };

    const getPlanBadgeVariant = (plan: string) => {
        switch (plan) {
            case 'enterprise':
                return 'destructive';
            case 'pro':
                return 'default';
            case 'basic':
                return 'outline';
            default:
                return 'secondary';
        }
    };

    const getStatusBadgeVariant = (status: string) => {
        switch (status) {
            case 'active':
                return 'default';
            case 'expired':
                return 'destructive';
            case 'cancelled':
                return 'outline';
            default:
                return 'secondary';
        }
    };

    return (
        <AdminLayout>
            <Head title="User Management" />

            <div className="p-6 space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-gray-100">
                            User Management
                        </h1>
                        <p className="text-gray-600 dark:text-gray-400 mt-1">
                            Manage system users and their permissions
                        </p>
                    </div>
                    <div className="flex items-center space-x-2">
                        <Button variant="outline" size="sm">
                            <Download className="h-4 w-4 mr-2" />
                            Export
                        </Button>
                        <Button size="sm" className="bg-green-600 hover:bg-green-700">
                            <Plus className="h-4 w-4 mr-2" />
                            Add User
                        </Button>
                    </div>
                </div>

                {/* Stats Cards */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <Card className="p-4">
                        <div className="flex items-center">
                            <Users className="h-8 w-8 text-blue-500" />
                            <div className="ml-3">
                                <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Total Users</p>
                                <p className="text-2xl font-bold text-gray-900 dark:text-gray-100">{users.total}</p>
                            </div>
                        </div>
                    </Card>
                    <Card className="p-4">
                        <div className="flex items-center">
                            <Crown className="h-8 w-8 text-purple-500" />
                            <div className="ml-3">
                                <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Admin Users</p>
                                <p className="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                    {users.data.filter(u => u.role === 'admin' || u.role === 'super_admin').length}
                                </p>
                            </div>
                        </div>
                    </Card>
                    <Card className="p-4">
                        <div className="flex items-center">
                            <User className="h-8 w-8 text-green-500" />
                            <div className="ml-3">
                                <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Regular Users</p>
                                <p className="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                    {users.data.filter(u => u.role === 'user').length}
                                </p>
                            </div>
                        </div>
                    </Card>
                    <Card className="p-4">
                        <div className="flex items-center">
                            <Calendar className="h-8 w-8 text-orange-500" />
                            <div className="ml-3">
                                <p className="text-sm font-medium text-gray-600 dark:text-gray-400">This Month</p>
                                <p className="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                    {users.data.filter(u =>
                                        new Date(u.created_at).getMonth() === new Date().getMonth()
                                    ).length}
                                </p>
                            </div>
                        </div>
                    </Card>
                </div>

                {/* Search and Filters */}
                <Card className="p-4">
                    <div className="flex items-center space-x-4">
                        <div className="flex-1 relative">
                            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                            <Input
                                placeholder="Search users by name, email..."
                                value={data.search}
                                onChange={(e) => setData('search', e.target.value)}
                                className="pl-10"
                                onKeyDown={(e) => e.key === 'Enter' && handleSearch()}
                            />
                        </div>
                        <Button
                            variant="outline"
                            onClick={() => setShowFilters(!showFilters)}
                            className="flex items-center"
                        >
                            <Filter className="h-4 w-4 mr-2" />
                            Filters
                        </Button>
                        <Button onClick={handleSearch} disabled={processing}>
                            Search
                        </Button>
                    </div>

                    {showFilters && (
                        <div className="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4 pt-4 border-t">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Role
                                </label>
                                <select
                                    value={data.role}
                                    onChange={(e) => setData('role', e.target.value)}
                                    className="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm"
                                >
                                    <option value="">All Roles</option>
                                    <option value="user">User</option>
                                    <option value="admin">Admin</option>
                                    <option value="super_admin">Super Admin</option>
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Plan
                                </label>
                                <select
                                    value={data.plan}
                                    onChange={(e) => setData('plan', e.target.value)}
                                    className="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm"
                                >
                                    <option value="">All Plans</option>
                                    <option value="free">Free</option>
                                    <option value="basic">Basic</option>
                                    <option value="pro">Pro</option>
                                    <option value="enterprise">Enterprise</option>
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Sort By
                                </label>
                                <select
                                    value={data.sort}
                                    onChange={(e) => setData('sort', e.target.value)}
                                    className="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm"
                                >
                                    <option value="created_at">Date Joined</option>
                                    <option value="first_name">Name</option>
                                    <option value="email">Email</option>
                                    <option value="last_activity">Last Activity</option>
                                </select>
                            </div>
                        </div>
                    )}
                </Card>

                {/* Users Table */}
                <Card>
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        <input
                                            type="checkbox"
                                            className="rounded border-gray-300 text-green-600 focus:ring-green-500"
                                            onChange={(e) => {
                                                if (e.target.checked) {
                                                    setSelectedUsers(users.data.map(u => u.id));
                                                } else {
                                                    setSelectedUsers([]);
                                                }
                                            }}
                                        />
                                    </th>
                                    <th
                                        className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700"
                                        onClick={() => handleSort('first_name')}
                                    >
                                        User
                                    </th>
                                    <th
                                        className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700"
                                        onClick={() => handleSort('role')}
                                    >
                                        Role
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Subscription
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Resumes
                                    </th>
                                    <th
                                        className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700"
                                        onClick={() => handleSort('created_at')}
                                    >
                                        Joined
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Last Activity
                                    </th>
                                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                {users.data.map((user) => (
                                    <tr key={user.id} className="hover:bg-gray-50 dark:hover:bg-gray-800">
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <input
                                                type="checkbox"
                                                className="rounded border-gray-300 text-green-600 focus:ring-green-500"
                                                checked={selectedUsers.includes(user.id)}
                                                onChange={(e) => {
                                                    if (e.target.checked) {
                                                        setSelectedUsers([...selectedUsers, user.id]);
                                                    } else {
                                                        setSelectedUsers(selectedUsers.filter(id => id !== user.id));
                                                    }
                                                }}
                                            />
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <div className="flex items-center">
                                                <div className="w-10 h-10 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                                                    <span className="text-sm font-semibold text-green-600 dark:text-green-400">
                                                        {user.full_name.split(' ').map(n => n[0]).join('').toUpperCase()}
                                                    </span>
                                                </div>
                                                <div className="ml-4">
                                                    <div className="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                        {user.full_name}
                                                    </div>
                                                    <div className="text-sm text-gray-500 dark:text-gray-400 flex items-center">
                                                        <Mail className="h-3 w-3 mr-1" />
                                                        {user.email}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <Badge variant={getRoleBadgeVariant(user.role)}>
                                                {user.role === 'super_admin' ? 'Super Admin' :
                                                 user.role === 'admin' ? 'Admin' : 'User'}
                                            </Badge>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <div className="flex items-center space-x-2">
                                                <Badge variant={getPlanBadgeVariant(user.current_plan)} className="capitalize">
                                                    {user.current_plan}
                                                </Badge>
                                                <Badge variant={getStatusBadgeVariant(user.subscription_status)} size="sm">
                                                    {user.subscription_status}
                                                </Badge>
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                            {user.resumes_count}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {new Date(user.created_at).toLocaleDateString()}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {user.last_activity ?
                                                new Date(user.last_activity).toLocaleDateString() :
                                                'Never'
                                            }
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div className="flex items-center justify-end space-x-2">
                                                <Link
                                                    href={route('admin.users.show', user.id)}
                                                    className="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                                >
                                                    <Eye className="h-4 w-4" />
                                                </Link>
                                                <button className="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300">
                                                    <Edit className="h-4 w-4" />
                                                </button>
                                                <button className="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                                    <Trash2 className="h-4 w-4" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {users.last_page > 1 && (
                        <div className="px-6 py-3 border-t border-gray-200 dark:border-gray-700">
                            <div className="flex items-center justify-between">
                                <div className="text-sm text-gray-700 dark:text-gray-300">
                                    Showing {((users.current_page - 1) * users.per_page) + 1} to{' '}
                                    {Math.min(users.current_page * users.per_page, users.total)} of{' '}
                                    {users.total} results
                                </div>
                                <div className="flex space-x-1">
                                    {users.links.map((link, index) => (
                                        <button
                                            key={index}
                                            onClick={() => link.url && router.get(link.url)}
                                            disabled={!link.url}
                                            className={`px-3 py-1 text-sm rounded-md ${
                                                link.active
                                                    ? 'bg-green-600 text-white'
                                                    : link.url
                                                    ? 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 border border-gray-300 dark:border-gray-600'
                                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-400 cursor-not-allowed'
                                            }`}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ))}
                                </div>
                            </div>
                        </div>
                    )}
                </Card>
            </div>
        </AdminLayout>
    );
}