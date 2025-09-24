import { ReactNode } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card';
import { cn } from '@/lib/utils';

interface StatsCardProps {
    title: string;
    value: string | number;
    description?: string;
    icon?: ReactNode;
    trend?: 'up' | 'down' | 'neutral';
    trendValue?: string;
    className?: string;
}

export function StatsCard({
    title,
    value,
    description,
    icon,
    trend,
    trendValue,
    className,
}: StatsCardProps) {
    const getTrendColor = () => {
        switch (trend) {
            case 'up':
                return 'text-green-600';
            case 'down':
                return 'text-red-600';
            default:
                return 'text-gray-600';
        }
    };

    const getTrendIcon = () => {
        switch (trend) {
            case 'up':
                return '↗️';
            case 'down':
                return '↘️';
            default:
                return '→';
        }
    };

    return (
        <Card className={cn('', className)}>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">{title}</CardTitle>
                {icon && <div className="h-4 w-4 text-muted-foreground">{icon}</div>}
            </CardHeader>
            <CardContent>
                <div className="text-2xl font-bold">{value}</div>
                {description && (
                    <p className="text-xs text-muted-foreground mt-1">
                        {description}
                    </p>
                )}
                {trend && trendValue && (
                    <div className={cn('flex items-center text-xs mt-1', getTrendColor())}>
                        <span className="mr-1">{getTrendIcon()}</span>
                        <span>{trendValue}</span>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}