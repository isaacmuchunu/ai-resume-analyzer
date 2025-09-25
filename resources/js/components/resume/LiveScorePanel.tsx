import React, { useMemo } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import { Progress } from '@/components/ui/Progress';
import { 
  Target,
  TrendingUp,
  TrendingDown,
  BarChart3,
  Eye,
  Zap,
  AlertTriangle,
  CheckCircle,
  Clock,
  RefreshCw
} from 'lucide-react';
import { 
  LiveScores, 
  SectionScores, 
  LiveScorePanelProps,
  SectionType,
  SECTION_TYPE_NAMES
} from '@/types/resume';
import { cn } from '@/lib/utils';

export const LiveScorePanel: React.FC<LiveScorePanelProps> = ({
  scores,
  sectionScores,
  onScoreClick,
  onRefresh,
  isLoading = false,
  lastUpdated,
}) => {
  const overallGrade = useMemo(() => {
    const score = scores.overall;
    if (score >= 90) return { grade: 'A+', color: 'text-green-600 bg-green-50', description: 'Excellent' };
    if (score >= 85) return { grade: 'A', color: 'text-green-600 bg-green-50', description: 'Great' };
    if (score >= 80) return { grade: 'A-', color: 'text-green-600 bg-green-50', description: 'Very Good' };
    if (score >= 75) return { grade: 'B+', color: 'text-blue-600 bg-blue-50', description: 'Good' };
    if (score >= 70) return { grade: 'B', color: 'text-blue-600 bg-blue-50', description: 'Above Average' };
    if (score >= 65) return { grade: 'B-', color: 'text-yellow-600 bg-yellow-50', description: 'Average' };
    if (score >= 60) return { grade: 'C+', color: 'text-yellow-600 bg-yellow-50', description: 'Below Average' };
    if (score >= 55) return { grade: 'C', color: 'text-orange-600 bg-orange-50', description: 'Needs Work' };
    if (score >= 50) return { grade: 'C-', color: 'text-red-600 bg-red-50', description: 'Poor' };
    return { grade: 'F', color: 'text-red-600 bg-red-50', description: 'Failing' };
  }, [scores.overall]);

  const scoreMetrics = useMemo(() => [
    {
      key: 'ats_compatibility',
      label: 'ATS Compatibility',
      value: scores.ats_compatibility,
      icon: <Target className="w-4 h-4" />,
      description: 'How well ATS systems can read your resume',
    },
    {
      key: 'keyword_density',
      label: 'Keyword Density',
      value: scores.keyword_density,
      icon: <BarChart3 className="w-4 h-4" />,
      description: 'Relevant keywords for your target role',
    },
    {
      key: 'format_score',
      label: 'Format Score',
      value: scores.format_score,
      icon: <Eye className="w-4 h-4" />,
      description: 'Resume structure and formatting quality',
    },
    {
      key: 'content_quality',
      label: 'Content Quality',
      value: scores.content_quality,
      icon: <Zap className="w-4 h-4" />,
      description: 'Impact and effectiveness of your content',
    },
  ], [scores]);

  const getScoreColor = (score: number) => {
    if (score >= 80) return 'text-green-600';
    if (score >= 60) return 'text-yellow-600';
    return 'text-red-600';
  };

  const getScoreBackground = (score: number) => {
    if (score >= 80) return 'bg-green-500';
    if (score >= 60) return 'bg-yellow-500';
    return 'bg-red-500';
  };

  const getScoreStatus = (score: number) => {
    if (score >= 80) return { icon: <CheckCircle className="w-4 h-4" />, status: 'Excellent' };
    if (score >= 60) return { icon: <AlertTriangle className="w-4 h-4" />, status: 'Good' };
    return { icon: <AlertTriangle className="w-4 h-4" />, status: 'Needs Improvement' };
  };

  const sortedSectionScores = useMemo(() => {
    return [...sectionScores].sort((a, b) => a.score - b.score);
  }, [sectionScores]);

  if (isLoading) {
    return (
      <Card>
        <CardContent className="p-6">
          <div className="flex items-center justify-center space-x-2">
            <div className="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
            <span>Calculating scores...</span>
          </div>
        </CardContent>
      </Card>
    );
  }

  return (
    <div className="space-y-4">
      {/* Overall Score Card */}
      <Card className="border-2 border-blue-200 bg-gradient-to-r from-blue-50 to-indigo-50">
        <CardHeader className="pb-3">
          <div className="flex items-center justify-between">
            <CardTitle className="flex items-center space-x-2">
              <Target className="w-5 h-5 text-blue-600" />
              <span>Overall ATS Score</span>
            </CardTitle>
            {onRefresh && (
              <Button
                size="sm"
                variant="outline"
                onClick={onRefresh}
                disabled={isLoading}
                className="h-8"
              >
                <RefreshCw className={cn("w-3 h-3 mr-1", isLoading && "animate-spin")} />
                Refresh
              </Button>
            )}
          </div>
        </CardHeader>
        <CardContent>
          <div className="flex items-center justify-between mb-4">
            <div className="flex items-center space-x-4">
              <div className="text-4xl font-bold text-blue-900">
                {scores.overall}
              </div>
              <div className="text-2xl text-gray-400">/100</div>
              <Badge className={cn("text-sm font-semibold", overallGrade.color)}>
                {overallGrade.grade}
              </Badge>
            </div>
            <div className="text-right">
              <div className="text-lg font-medium text-gray-900">
                {overallGrade.description}
              </div>
              {scores.improvement_potential > 0 && (
                <div className="text-sm text-green-600 flex items-center">
                  <TrendingUp className="w-3 h-3 mr-1" />
                  +{scores.improvement_potential} potential
                </div>
              )}
            </div>
          </div>

          <Progress 
            value={scores.overall} 
            className="h-3 mb-2"
            indicatorClassName={getScoreBackground(scores.overall)}
          />

          {lastUpdated && (
            <div className="flex items-center text-xs text-gray-500 mt-2">
              <Clock className="w-3 h-3 mr-1" />
              Last updated: {new Date(lastUpdated).toLocaleTimeString()}
            </div>
          )}
        </CardContent>
      </Card>

      {/* Score Breakdown */}
      <Card>
        <CardHeader className="pb-3">
          <CardTitle className="text-lg">Score Breakdown</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            {scoreMetrics.map((metric) => {
              const status = getScoreStatus(metric.value);
              return (
                <div key={metric.key} className="p-3 border border-gray-200 rounded-lg">
                  <div className="flex items-center justify-between mb-2">
                    <div className="flex items-center space-x-2">
                      <div className="text-gray-500">{metric.icon}</div>
                      <span className="font-medium text-sm">{metric.label}</span>
                    </div>
                    <div className={cn("flex items-center space-x-1", getScoreColor(metric.value))}>
                      {status.icon}
                      <span className="font-bold">{metric.value}</span>
                    </div>
                  </div>
                  <Progress 
                    value={metric.value} 
                    className="h-2 mb-1"
                    indicatorClassName={getScoreBackground(metric.value)}
                  />
                  <p className="text-xs text-gray-600">{metric.description}</p>
                </div>
              );
            })}
          </div>
        </CardContent>
      </Card>

      {/* Section Scores */}
      <Card>
        <CardHeader className="pb-3">
          <CardTitle className="text-lg">Section Performance</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-3">
            {sortedSectionScores.map((section) => {
              const status = getScoreStatus(section.score);
              return (
                <div 
                  key={section.section_type} 
                  className={cn(
                    "flex items-center justify-between p-3 border border-gray-200 rounded-lg transition-colors",
                    onScoreClick && "hover:bg-gray-50 cursor-pointer"
                  )}
                  onClick={() => onScoreClick?.(section.section_type)}
                >
                  <div className="flex items-center space-x-3">
                    <div className={cn("flex items-center space-x-1", getScoreColor(section.score))}>
                      {status.icon}
                    </div>
                    <div>
                      <div className="font-medium text-sm">
                        {SECTION_TYPE_NAMES[section.section_type]}
                      </div>
                      <div className="text-xs text-gray-600">
                        {status.status}
                      </div>
                    </div>
                  </div>
                  
                  <div className="flex items-center space-x-3">
                    <div className="w-20">
                      <Progress 
                        value={section.score} 
                        className="h-2"
                        indicatorClassName={getScoreBackground(section.score)}
                      />
                    </div>
                    <div className={cn("font-bold text-sm w-8 text-right", getScoreColor(section.score))}>
                      {section.score}
                    </div>
                  </div>
                </div>
              );
            })}
          </div>
        </CardContent>
      </Card>

      {/* Quick Insights */}
      <Card className="border-yellow-200 bg-yellow-50">
        <CardHeader className="pb-3">
          <CardTitle className="text-lg text-yellow-800">Quick Insights</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-2">
            {scores.overall < 70 && (
              <div className="flex items-start space-x-2 text-sm">
                <AlertTriangle className="w-4 h-4 text-yellow-600 mt-0.5 flex-shrink-0" />
                <span className="text-yellow-800">
                  Your resume needs improvement to pass most ATS systems effectively.
                </span>
              </div>
            )}
            
            {scores.keyword_density < 60 && (
              <div className="flex items-start space-x-2 text-sm">
                <BarChart3 className="w-4 h-4 text-yellow-600 mt-0.5 flex-shrink-0" />
                <span className="text-yellow-800">
                  Add more relevant keywords to improve your chances of being found by recruiters.
                </span>
              </div>
            )}
            
            {scores.ats_compatibility < 70 && (
              <div className="flex items-start space-x-2 text-sm">
                <Target className="w-4 h-4 text-yellow-600 mt-0.5 flex-shrink-0" />
                <span className="text-yellow-800">
                  Improve formatting and structure for better ATS compatibility.
                </span>
              </div>
            )}

            {scores.improvement_potential > 20 && (
              <div className="flex items-start space-x-2 text-sm">
                <TrendingUp className="w-4 h-4 text-green-600 mt-0.5 flex-shrink-0" />
                <span className="text-green-800">
                  Great potential for improvement! Apply suggested changes to boost your score significantly.
                </span>
              </div>
            )}
          </div>
        </CardContent>
      </Card>
    </div>
  );
};

// Mini version for compact display
export const CompactScorePanel: React.FC<{
  score: number;
  label?: string;
  showGrade?: boolean;
  onClick?: () => void;
}> = ({ score, label = "ATS Score", showGrade = true, onClick }) => {
  const getScoreColor = (score: number) => {
    if (score >= 80) return 'text-green-600 bg-green-50 border-green-200';
    if (score >= 60) return 'text-yellow-600 bg-yellow-50 border-yellow-200';
    return 'text-red-600 bg-red-50 border-red-200';
  };

  const getGrade = (score: number) => {
    if (score >= 90) return 'A+';
    if (score >= 85) return 'A';
    if (score >= 80) return 'A-';
    if (score >= 75) return 'B+';
    if (score >= 70) return 'B';
    if (score >= 65) return 'B-';
    if (score >= 60) return 'C+';
    if (score >= 55) return 'C';
    if (score >= 50) return 'C-';
    return 'F';
  };

  return (
    <div 
      className={cn(
        "flex items-center space-x-2 px-3 py-2 border rounded-lg transition-colors",
        getScoreColor(score),
        onClick && "cursor-pointer hover:shadow-md"
      )}
      onClick={onClick}
    >
      <Target className="w-4 h-4" />
      <span className="text-sm font-medium">{label}</span>
      <div className="flex items-center space-x-1">
        <span className="font-bold">{score}</span>
        {showGrade && (
          <Badge variant="secondary" className="text-xs">
            {getGrade(score)}
          </Badge>
        )}
      </div>
    </div>
  );
};

// Progress indicator for individual metrics
export const ScoreProgress: React.FC<{
  label: string;
  score: number;
  maxScore?: number;
  showImprovement?: boolean;
  improvement?: number;
}> = ({ label, score, maxScore = 100, showImprovement = false, improvement = 0 }) => {
  const getScoreBackground = (score: number) => {
    if (score >= 80) return 'bg-green-500';
    if (score >= 60) return 'bg-yellow-500';
    return 'bg-red-500';
  };

  return (
    <div className="space-y-1">
      <div className="flex items-center justify-between text-sm">
        <span className="font-medium">{label}</span>
        <div className="flex items-center space-x-1">
          <span className="font-bold">{score}/{maxScore}</span>
          {showImprovement && improvement > 0 && (
            <span className="text-green-600 text-xs">
              (+{improvement})
            </span>
          )}
        </div>
      </div>
      <Progress 
        value={(score / maxScore) * 100} 
        className="h-2"
        indicatorClassName={getScoreBackground(score)}
      />
    </div>
  );
};