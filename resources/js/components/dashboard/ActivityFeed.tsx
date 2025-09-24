import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card';
import { Badge } from '@/components/ui/Badge';
import {
    FileText,
    Upload,
    Download,
    Trash2,
    LogIn,
    LogOut,
    Settings,
    CreditCard,
    RotateCcw
} from 'lucide-react';
import { formatDateTime } from '@/lib/utils';

interface ActivityItem {
    id: number;
    description: string;
    created_at: string;
    properties?: any;
}

interface ActivityFeedProps {
    activities: ActivityItem[];
    className?: string;
}

export function ActivityFeed({ activities, className }: ActivityFeedProps) {
    const getActivityIcon = (description: string) => {
        const iconMap = {
            'resume uploaded': Upload,
            'resume analyzed': FileText,
            'resume downloaded': Download,
            'resume deleted': Trash2,
            'user logged in': LogIn,
            'user logged out': LogOut,
            'subscription changed': CreditCard,
            'resume reanalyzed': RotateCcw,
            'profile updated': Settings,
        };

        const IconComponent = Object.entries(iconMap).find(([key]) =>
            description.toLowerCase().includes(key)
        )?.[1] || FileText;

        return <IconComponent className="h-4 w-4" />;
    };

    const getActivityColor = (description: string) => {
        if (description.includes('failed') || description.includes('deleted')) {
            return 'text-red-600 bg-red-50 dark:bg-red-900/20';
        }
        if (description.includes('completed') || description.includes('uploaded')) {
            return 'text-green-600 bg-green-50 dark:bg-green-900/20';
        }
        if (description.includes('processing') || description.includes('pending')) {
            return 'text-yellow-600 bg-yellow-50 dark:bg-yellow-900/20';
        }
        return 'text-blue-600 bg-blue-50 dark:bg-blue-900/20';
    };

    const formatDescription = (description: string) => {
        return description.charAt(0).toUpperCase() + description.slice(1);
    };

    if (activities.length === 0) {
        return (
            <Card className={className}>
                <CardHeader>
                    <CardTitle className="text-lg">Recent Activity</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="text-center py-6 text-gray-500">
                        <FileText className="h-12 w-12 mx-auto mb-2 opacity-50" />
                        <p>No recent activity</p>
                        <p className="text-sm mt-1">Upload your first resume to get started</p>
                    </div>
                </CardContent>
            </Card>
        );
    }

    return (
        <Card className={className}>
            <CardHeader>
                <CardTitle className="text-lg">Recent Activity</CardTitle>
            </CardHeader>
            <CardContent>
                <div className="space-y-3">
                    {activities.map((activity, index) => {
                        const isLast = index === activities.length - 1;

                        return (
                            <div key={activity.id} className="relative">
                                <div className="flex space-x-3">
                                    <div className={`flex-shrink-0 rounded-full p-2 ${getActivityColor(activity.description)}`}>
                                        {getActivityIcon(activity.description)}
                                    </div>

                                    <div className="flex-1 min-w-0">
                                        <p className="text-sm text-gray-900 dark:text-gray-100">
                                            {formatDescription(activity.description)}
                                        </p>

                                        {activity.properties && Object.keys(activity.properties).length > 0 && (
                                            <div className="mt-1 flex flex-wrap gap-1">
                                                {Object.entries(activity.properties).map(([key, value]) => {
                                                    if (key === 'timestamp' || key === 'ip_address' || key === 'user_agent') return null;

                                                    return (
                                                        <Badge key={key} variant="outline" className="text-xs">
                                                            {key}: {String(value)}
                                                        </Badge>
                                                    );
                                                })}
                                            </div>
                                        )}

                                        <p className="text-xs text-gray-500 mt-1">
                                            {formatDateTime(activity.created_at)}
                                        </p>
                                    </div>
                                </div>

                                {!isLast && (
                                    <div className="absolute left-4 top-10 h-6 w-px bg-gray-200 dark:bg-gray-700"></div>
                                )}
                            </div>
                        );
                    })}
                </div>

                {activities.length >= 10 && (
                    <div className="mt-4 pt-3 border-t border-gray-200 dark:border-gray-700 text-center">
                        <button className="text-sm text-green-600 hover:text-green-700 font-medium">
                            View all activity
                        </button>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}