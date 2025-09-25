import React, { useState, useMemo } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import { 
  AlertCircle,
  CheckCircle,
  X,
  TrendingUp,
  Filter,
  Eye,
  EyeOff,
  Lightbulb,
  Zap,
  Target,
  Clock,
  ArrowRight
} from 'lucide-react';
import { 
  ATSSuggestion, 
  ATSSuggestionCardProps,
  SuggestionPriority,
  SuggestionType,
  SUGGESTION_TYPE_NAMES,
  PRIORITY_NAMES,
  PRIORITY_COLORS
} from '@/types/resume';
import { cn } from '@/lib/utils';

interface ATSSuggestionPanelProps {
  suggestions: ATSSuggestion[];
  onApplySuggestion: (suggestion: ATSSuggestion) => Promise<void>;
  onDismissSuggestion: (suggestion: ATSSuggestion) => Promise<void>;
  onViewDetails?: (suggestion: ATSSuggestion) => void;
  isLoading?: boolean;
  showDismissed?: boolean;
}

export const ATSSuggestionPanel: React.FC<ATSSuggestionPanelProps> = ({
  suggestions,
  onApplySuggestion,
  onDismissSuggestion,
  onViewDetails,
  isLoading = false,
  showDismissed = false,
}) => {
  const [filter, setFilter] = useState<{
    priority: SuggestionPriority | 'all';
    type: SuggestionType | 'all';
    status: 'pending' | 'all';
  }>({
    priority: 'all',
    type: 'all',
    status: 'pending',
  });

  const [processingIds, setProcessingIds] = useState<Set<string>>(new Set());

  const filteredSuggestions = useMemo(() => {
    return suggestions.filter(suggestion => {
      if (filter.priority !== 'all' && suggestion.priority !== filter.priority) {
        return false;
      }
      if (filter.type !== 'all' && suggestion.suggestion_type !== filter.type) {
        return false;
      }
      if (filter.status !== 'all' && suggestion.status !== filter.status) {
        return false;
      }
      return true;
    });
  }, [suggestions, filter]);

  const suggestionStats = useMemo(() => {
    const stats = {
      total: suggestions.length,
      pending: suggestions.filter(s => s.status === 'pending').length,
      critical: suggestions.filter(s => s.priority === 'critical' && s.status === 'pending').length,
      high: suggestions.filter(s => s.priority === 'high' && s.status === 'pending').length,
      totalImpact: suggestions
        .filter(s => s.status === 'pending')
        .reduce((sum, s) => sum + s.ats_impact, 0),
    };
    return stats;
  }, [suggestions]);

  const handleApplySuggestion = async (suggestion: ATSSuggestion) => {
    setProcessingIds(prev => new Set(prev).add(suggestion.id));
    try {
      await onApplySuggestion(suggestion);
    } finally {
      setProcessingIds(prev => {
        const newSet = new Set(prev);
        newSet.delete(suggestion.id);
        return newSet;
      });
    }
  };

  const handleDismissSuggestion = async (suggestion: ATSSuggestion) => {
    setProcessingIds(prev => new Set(prev).add(suggestion.id));
    try {
      await onDismissSuggestion(suggestion);
    } finally {
      setProcessingIds(prev => {
        const newSet = new Set(prev);
        newSet.delete(suggestion.id);
        return newSet;
      });
    }
  };

  if (isLoading) {
    return (
      <Card>
        <CardContent className="p-6">
          <div className="flex items-center justify-center space-x-2">
            <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
            <span>Loading suggestions...</span>
          </div>
        </CardContent>
      </Card>
    );
  }

  return (
    <div className="space-y-4">
      {/* Summary Stats */}
      <Card>
        <CardHeader className="pb-3">
          <CardTitle className="flex items-center space-x-2">
            <Lightbulb className="w-5 h-5 text-yellow-500" />
            <span>ATS Optimization Suggestions</span>
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
            <div className="text-center">
              <div className="text-2xl font-bold text-blue-600">{suggestionStats.pending}</div>
              <div className="text-sm text-gray-600">Pending</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-red-600">{suggestionStats.critical}</div>
              <div className="text-sm text-gray-600">Critical</div>
            </div>
            <div className="text-2xl font-bold text-orange-600">{suggestionStats.high}</div>
              <div className="text-sm text-gray-600">High Priority</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-green-600">+{suggestionStats.totalImpact}</div>
              <div className="text-sm text-gray-600">Potential Score</div>
            </div>
          </div>

          {/* Filters */}
          <div className="flex flex-wrap gap-2 mb-4">
            <select
              value={filter.priority}
              onChange={(e) => setFilter(prev => ({ ...prev, priority: e.target.value as SuggestionPriority | 'all' }))}
              className="px-3 py-1 border border-gray-300 rounded-md text-sm"
            >
              <option value="all">All Priorities</option>
              <option value="critical">Critical</option>
              <option value="high">High</option>
              <option value="medium">Medium</option>
              <option value="low">Low</option>
            </select>

            <select
              value={filter.type}
              onChange={(e) => setFilter(prev => ({ ...prev, type: e.target.value as SuggestionType | 'all' }))}
              className="px-3 py-1 border border-gray-300 rounded-md text-sm"
            >
              <option value="all">All Types</option>
              <option value="keyword">Keywords</option>
              <option value="format">Format</option>
              <option value="content">Content</option>
              <option value="structure">Structure</option>
              <option value="achievement">Achievements</option>
            </select>

            <select
              value={filter.status}
              onChange={(e) => setFilter(prev => ({ ...prev, status: e.target.value as 'pending' | 'all' }))}
              className="px-3 py-1 border border-gray-300 rounded-md text-sm"
            >
              <option value="pending">Pending Only</option>
              <option value="all">All Status</option>
            </select>
          </div>
        </CardContent>
      </Card>

      {/* Suggestions List */}
      <div className="space-y-3">
        {filteredSuggestions.length === 0 ? (
          <Card>
            <CardContent className="p-6 text-center">
              <CheckCircle className="w-12 h-12 text-green-500 mx-auto mb-3" />
              <h3 className="text-lg font-medium text-gray-900 mb-2">
                {filter.status === 'pending' ? 'No pending suggestions!' : 'No suggestions found'}
              </h3>
              <p className="text-gray-600">
                {filter.status === 'pending' 
                  ? 'Your resume looks great! All suggestions have been addressed.'
                  : 'Try adjusting your filters to see more suggestions.'
                }
              </p>
            </CardContent>
          </Card>
        ) : (
          filteredSuggestions.map((suggestion) => (
            <ATSSuggestionCard
              key={suggestion.id}
              suggestion={suggestion}
              onApply={() => handleApplySuggestion(suggestion)}
              onDismiss={() => handleDismissSuggestion(suggestion)}
              onViewDetails={onViewDetails}
              isApplying={processingIds.has(suggestion.id)}
              isDismissing={processingIds.has(suggestion.id)}
            />
          ))
        )}
      </div>

      {/* Quick Actions */}
      {suggestionStats.pending > 0 && (
        <Card className="border-blue-200 bg-blue-50">
          <CardContent className="p-4">
            <div className="flex items-center justify-between">
              <div>
                <h4 className="font-medium text-blue-900">Quick Actions</h4>
                <p className="text-sm text-blue-700">
                  Apply critical suggestions to boost your ATS score quickly
                </p>
              </div>
              <div className="flex space-x-2">
                <Button
                  size="sm"
                  className="bg-blue-600 hover:bg-blue-700"
                  disabled={suggestionStats.critical === 0}
                >
                  <Zap className="w-3 h-3 mr-1" />
                  Fix Critical ({suggestionStats.critical})
                </Button>
              </div>
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
};

export const ATSSuggestionCard: React.FC<ATSSuggestionCardProps & {
  onViewDetails?: (suggestion: ATSSuggestion) => void;
}> = ({
  suggestion,
  onApply,
  onDismiss,
  onViewDetails,
  isApplying = false,
  isDismissing = false,
}) => {
  const [isExpanded, setIsExpanded] = useState(false);

  const getPriorityIcon = (priority: SuggestionPriority) => {
    switch (priority) {
      case 'critical':
        return <AlertCircle className="w-4 h-4" />;
      case 'high':
        return <TrendingUp className="w-4 h-4" />;
      case 'medium':
        return <Target className="w-4 h-4" />;
      case 'low':
        return <Clock className="w-4 h-4" />;
      default:
        return <AlertCircle className="w-4 h-4" />;
    }
  };

  const getTypeIcon = (type: SuggestionType) => {
    switch (type) {
      case 'keyword':
        return '🔍';
      case 'format':
        return '📝';
      case 'content':
        return '✍️';
      case 'structure':
        return '📋';
      case 'achievement':
        return '🏆';
      case 'grammar':
        return '✅';
      default:
        return '💡';
    }
  };

  const isProcessing = isApplying || isDismissing;

  return (
    <Card className={cn(
      "border-l-4 transition-all duration-200",
      suggestion.priority === 'critical' && "border-l-red-500 bg-red-50/50",
      suggestion.priority === 'high' && "border-l-orange-500 bg-orange-50/50",
      suggestion.priority === 'medium' && "border-l-yellow-500 bg-yellow-50/50",
      suggestion.priority === 'low' && "border-l-blue-500 bg-blue-50/50",
      suggestion.status !== 'pending' && "opacity-75",
      isProcessing && "ring-2 ring-blue-200"
    )}>
      <CardContent className="p-4">
        <div className="flex items-start justify-between space-x-4">
          <div className="flex-1 space-y-2">
            <div className="flex items-center space-x-2">
              <Badge 
                variant="secondary" 
                className={cn("text-xs", PRIORITY_COLORS[suggestion.priority])}
              >
                {getPriorityIcon(suggestion.priority)}
                <span className="ml-1">{PRIORITY_NAMES[suggestion.priority]}</span>
              </Badge>
              
              <Badge variant="outline" className="text-xs">
                {getTypeIcon(suggestion.suggestion_type)} {SUGGESTION_TYPE_NAMES[suggestion.suggestion_type]}
              </Badge>
              
              {suggestion.ats_impact > 0 && (
                <Badge variant="outline" className="text-xs text-green-600">
                  <TrendingUp className="w-3 h-3 mr-1" />
                  +{suggestion.ats_impact}
                </Badge>
              )}
            </div>

            <div>
              <h4 className="font-medium text-gray-900">{suggestion.title}</h4>
              <p className="text-sm text-gray-600 mt-1">{suggestion.description}</p>
            </div>

            {suggestion.reason && (
              <div className="text-xs text-gray-500 bg-gray-50 px-2 py-1 rounded">
                💡 {suggestion.reason}
              </div>
            )}

            {/* Before/After Preview */}
            {(suggestion.original_text || suggestion.suggested_text) && (
              <div className="mt-3">
                <Button
                  size="sm"
                  variant="ghost"
                  onClick={() => setIsExpanded(!isExpanded)}
                  className="text-xs p-1 h-auto"
                >
                  {isExpanded ? <EyeOff className="w-3 h-3 mr-1" /> : <Eye className="w-3 h-3 mr-1" />}
                  {isExpanded ? 'Hide' : 'Show'} Preview
                </Button>

                {isExpanded && (
                  <div className="mt-2 space-y-2 text-xs">
                    {suggestion.original_text && (
                      <div>
                        <div className="text-gray-500 font-medium">Before:</div>
                        <div className="bg-red-50 border border-red-200 p-2 rounded text-red-800">
                          {suggestion.original_text}
                        </div>
                      </div>
                    )}
                    {suggestion.suggested_text && (
                      <div>
                        <div className="text-gray-500 font-medium">After:</div>
                        <div className="bg-green-50 border border-green-200 p-2 rounded text-green-800">
                          {suggestion.suggested_text}
                        </div>
                      </div>
                    )}
                  </div>
                )}
              </div>
            )}
          </div>

          {/* Actions */}
          <div className="flex flex-col space-y-2">
            {suggestion.status === 'pending' && (
              <>
                <Button
                  size="sm"
                  onClick={onApply}
                  disabled={isProcessing}
                  className="text-xs h-8"
                >
                  {isApplying ? (
                    <div className="animate-spin rounded-full h-3 w-3 border-b-2 border-white mr-1"></div>
                  ) : (
                    <CheckCircle className="w-3 h-3 mr-1" />
                  )}
                  {isApplying ? 'Applying...' : 'Apply'}
                </Button>
                
                <Button
                  size="sm"
                  variant="outline"
                  onClick={onDismiss}
                  disabled={isProcessing}
                  className="text-xs h-8"
                >
                  {isDismissing ? (
                    <div className="animate-spin rounded-full h-3 w-3 border-b-2 border-gray-600 mr-1"></div>
                  ) : (
                    <X className="w-3 h-3 mr-1" />
                  )}
                  {isDismissing ? 'Dismissing...' : 'Dismiss'}
                </Button>
              </>
            )}

            {suggestion.status === 'applied' && (
              <Badge variant="secondary" className="text-xs bg-green-50 text-green-700">
                <CheckCircle className="w-3 h-3 mr-1" />
                Applied
              </Badge>
            )}

            {suggestion.status === 'dismissed' && (
              <Badge variant="secondary" className="text-xs bg-gray-50 text-gray-700">
                <X className="w-3 h-3 mr-1" />
                Dismissed
              </Badge>
            )}

            {onViewDetails && (
              <Button
                size="sm"
                variant="ghost"
                onClick={() => onViewDetails(suggestion)}
                className="text-xs h-8 px-2"
              >
                <ArrowRight className="w-3 h-3" />
              </Button>
            )}
          </div>
        </div>
      </CardContent>
    </Card>
  );
};