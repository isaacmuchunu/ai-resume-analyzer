import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import AppLayout from '@/layouts/AppLayout';
import { formatDistanceToNow } from 'date-fns';

interface Notification {
    id: string;
    type: string;
    title: string;
    message: string;
    data: Record<string, any>;
    read_at: string | null;
    created_at: string;
    is_read: boolean;
}

interface Props {
    notifications: {
        data: Notification[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    unread_count: number;
}

export default function Index({ notifications, unread_count }: Props) {
    const markAsRead = async (notificationId: string) => {
        try {
            await fetch(`/notifications/${notificationId}/read`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });
            // Refresh the page to update the UI
            window.location.reload();
        } catch (error) {
            console.error('Failed to mark notification as read:', error);
        }
    };

    const markAllAsRead = async () => {
        try {
            await fetch('/notifications/mark-all-read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });
            window.location.reload();
        } catch (error) {
            console.error('Failed to mark all notifications as read:', error);
        }
    };

    const deleteNotification = async (notificationId: string) => {
        if (!confirm('Are you sure you want to delete this notification?')) return;

        try {
            await fetch(`/notifications/${notificationId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });
            window.location.reload();
        } catch (error) {
            console.error('Failed to delete notification:', error);
        }
    };

    const getNotificationIcon = (type: string) => {
        switch (type) {
            case 'analysis_complete':
                return '📊';
            case 'subscription_expiring':
                return '⏰';
            case 'subscription_expired':
                return '⚠️';
            case 'weekly_report':
                return '📈';
            case 'welcome':
                return '👋';
            case 'password_changed':
                return '🔒';
            default:
                return '🔔';
        }
    };

    const getNotificationColor = (type: string) => {
        switch (type) {
            case 'analysis_complete':
                return 'bg-slate-100 text-slate-800';
            case 'subscription_expiring':
                return 'bg-yellow-100 text-yellow-800';
            case 'subscription_expired':
                return 'bg-red-100 text-red-800';
            case 'weekly_report':
                return 'bg-blue-100 text-blue-800';
            case 'welcome':
                return 'bg-purple-100 text-purple-800';
            case 'password_changed':
                return 'bg-indigo-100 text-indigo-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    };

    return (
        <AppLayout>
            <Head title="Notifications" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                        <div className="p-6">
                            <div className="flex justify-between items-center mb-6">
                                <div>
                                    <h2 className="text-2xl font-bold text-gray-900">Notifications</h2>
                                    {unread_count > 0 && (
                                        <p className="text-sm text-gray-600 mt-1">
                                            {unread_count} unread notification{unread_count !== 1 ? 's' : ''}
                                        </p>
                                    )}
                                </div>
                                <div className="flex space-x-2">
                                    {unread_count > 0 && (
                                        <Button
                                            onClick={markAllAsRead}
                                            variant="outline"
                                            size="sm"
                                        >
                                            Mark All as Read
                                        </Button>
                                    )}
                                    <Button
                                        onClick={() => window.location.href = '/notifications/test'}
                                        variant="outline"
                                        size="sm"
                                    >
                                        Test Notification
                                    </Button>
                                </div>
                            </div>

                            {notifications.data.length === 0 ? (
                                <div className="text-center py-12">
                                    <div className="text-6xl mb-4">🔔</div>
                                    <h3 className="text-lg font-medium text-gray-900 mb-2">No notifications yet</h3>
                                    <p className="text-gray-600">
                                        You'll receive notifications here when your resumes are analyzed or other important events occur.
                                    </p>
                                </div>
                            ) : (
                                <div className="space-y-4">
                                    {notifications.data.map((notification) => (
                                        <div
                                            key={notification.id}
                                            className={`border rounded-lg p-4 ${
                                                !notification.is_read
                                                    ? 'bg-blue-50 border-blue-200'
                                                    : 'bg-white border-gray-200'
                                            }`}
                                        >
                                            <div className="flex items-start justify-between">
                                                <div className="flex items-start space-x-3 flex-1">
                                                    <div className="flex-shrink-0">
                                                        <div className="w-10 h-10 rounded-full flex items-center justify-center text-lg">
                                                            {getNotificationIcon(notification.type)}
                                                        </div>
                                                    </div>
                                                    <div className="flex-1 min-w-0">
                                                        <div className="flex items-center space-x-2 mb-1">
                                                            <h4 className="text-sm font-medium text-gray-900">
                                                                {notification.title}
                                                            </h4>
                                                            <Badge
                                                                variant="secondary"
                                                                className={getNotificationColor(notification.type)}
                                                            >
                                                                {notification.type.replace('_', ' ')}
                                                            </Badge>
                                                            {!notification.is_read && (
                                                                <Badge variant="default" className="bg-blue-600">
                                                                    New
                                                                </Badge>
                                                            )}
                                                        </div>
                                                        <p className="text-sm text-gray-600 mb-2">
                                                            {notification.message}
                                                        </p>
                                                        <p className="text-xs text-gray-500">
                                                            {formatDistanceToNow(new Date(notification.created_at), { addSuffix: true })}
                                                        </p>
                                                    </div>
                                                </div>
                                                <div className="flex items-center space-x-2 ml-4">
                                                    {!notification.is_read && (
                                                        <Button
                                                            onClick={() => markAsRead(notification.id)}
                                                            variant="ghost"
                                                            size="sm"
                                                        >
                                                            Mark as Read
                                                        </Button>
                                                    )}
                                                    <Button
                                                        onClick={() => deleteNotification(notification.id)}
                                                        variant="ghost"
                                                        size="sm"
                                                        className="text-red-600 hover:text-red-800"
                                                    >
                                                        Delete
                                                    </Button>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}

                            {notifications.last_page > 1 && (
                                <div className="mt-6 flex justify-center">
                                    <div className="flex space-x-2">
                                        {Array.from({ length: notifications.last_page }, (_, i) => i + 1).map((page) => (
                                            <Link
                                                key={page}
                                                href={`/notifications?page=${page}`}
                                                className={`px-3 py-2 text-sm border rounded ${
                                                    page === notifications.current_page
                                                        ? 'bg-blue-600 text-white border-blue-600'
                                                        : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                                                }`}
                                            >
                                                {page}
                                            </Link>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
