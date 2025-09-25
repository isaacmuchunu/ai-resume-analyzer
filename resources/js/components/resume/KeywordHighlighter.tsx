import React, { useMemo, useState } from 'react';

interface KeywordHighlighterProps {
    text: string;
    keywords: string[];
    missingKeywords: string[];
    onKeywordClick?: (keyword: string, type: 'found' | 'missing') => void;
    className?: string;
    showTooltips?: boolean;
    caseSensitive?: boolean;
}

interface HighlightSegment {
    text: string;
    type: 'text' | 'found' | 'missing' | 'suggestion';
    keyword?: string;
    position: number;
}

export function KeywordHighlighter({
    text,
    keywords = [],
    missingKeywords = [],
    onKeywordClick,
    className = '',
    showTooltips = true,
    caseSensitive = false
}: KeywordHighlighterProps) {
    const [hoveredKeyword, setHoveredKeyword] = useState<string | null>(null);

    // Process text and create highlight segments
    const segments = useMemo(() => {
        if (!text || (!keywords.length && !missingKeywords.length)) {
            return [{ text, type: 'text' as const, position: 0 }];
        }

        const allKeywords = [...keywords, ...missingKeywords];
        const segments: HighlightSegment[] = [];
        let currentPosition = 0;
        const processedText = caseSensitive ? text : text.toLowerCase();
        
        // Create a pattern to match all keywords
        const keywordPattern = allKeywords
            .filter(k => k.trim().length > 0)
            .map(keyword => {
                const searchKeyword = caseSensitive ? keyword : keyword.toLowerCase();
                // Escape special regex characters
                return searchKeyword.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            })
            .join('|');

        if (!keywordPattern) {
            return [{ text, type: 'text' as const, position: 0 }];
        }

        const regex = new RegExp(`\\b(${keywordPattern})\\b`, caseSensitive ? 'g' : 'gi');
        let match;

        while ((match = regex.exec(processedText)) !== null) {
            const matchStart = match.index;
            const matchEnd = matchStart + match[0].length;
            const matchedKeyword = text.substring(matchStart, matchEnd);
            
            // Add text before the match
            if (matchStart > currentPosition) {
                segments.push({
                    text: text.substring(currentPosition, matchStart),
                    type: 'text',
                    position: currentPosition
                });
            }
            
            // Determine if this is a found or missing keyword
            const isFound = keywords.some(k => 
                caseSensitive 
                    ? k === matchedKeyword 
                    : k.toLowerCase() === matchedKeyword.toLowerCase()
            );
            
            // Add the highlighted keyword
            segments.push({
                text: matchedKeyword,
                type: isFound ? 'found' : 'missing',
                keyword: matchedKeyword,
                position: matchStart
            });
            
            currentPosition = matchEnd;
        }
        
        // Add remaining text
        if (currentPosition < text.length) {
            segments.push({
                text: text.substring(currentPosition),
                type: 'text',
                position: currentPosition
            });
        }
        
        return segments;
    }, [text, keywords, missingKeywords, caseSensitive]);

    const handleKeywordClick = (keyword: string, type: 'found' | 'missing') => {
        onKeywordClick?.(keyword, type);
    };

    const getHighlightClass = (type: HighlightSegment['type']) => {
        switch (type) {
            case 'found':
                return 'bg-green-100 text-green-800 border-b border-green-300 cursor-pointer hover:bg-green-200 transition-colors';
            case 'missing':
                return 'bg-red-100 text-red-800 border-b border-red-300 cursor-pointer hover:bg-red-200 transition-colors';
            case 'suggestion':
                return 'bg-yellow-100 text-yellow-800 border-b border-yellow-300 cursor-pointer hover:bg-yellow-200 transition-colors';
            default:
                return '';
        }
    };

    const getTooltipContent = (segment: HighlightSegment) => {
        if (!segment.keyword) return '';
        
        switch (segment.type) {
            case 'found':
                return `✓ Found keyword: "${segment.keyword}"`;
            case 'missing':
                return `⚠ Missing keyword: "${segment.keyword}" - Consider adding this to improve ATS score`;
            case 'suggestion':
                return `💡 Suggested keyword: "${segment.keyword}"`;
            default:
                return '';
        }
    };

    return (
        <div className={`relative ${className}`}>
            <div className="whitespace-pre-wrap">
                {segments.map((segment, index) => {
                    if (segment.type === 'text') {
                        return <span key={index}>{segment.text}</span>;
                    }

                    return (
                        <span key={index} className="relative inline">
                            <span
                                className={`px-1 py-0.5 rounded-sm ${getHighlightClass(segment.type)}`}
                                onClick={() => segment.keyword && handleKeywordClick(segment.keyword, segment.type as 'found' | 'missing')}
                                onMouseEnter={() => showTooltips && setHoveredKeyword(segment.keyword || null)}
                                onMouseLeave={() => showTooltips && setHoveredKeyword(null)}
                                title={showTooltips ? getTooltipContent(segment) : undefined}
                            >
                                {segment.text}
                            </span>
                            
                            {/* Custom tooltip */}
                            {showTooltips && hoveredKeyword === segment.keyword && (
                                <div className="absolute z-10 px-3 py-2 text-sm text-white bg-gray-900 rounded-md shadow-lg -top-8 left-1/2 transform -translate-x-1/2 whitespace-nowrap">
                                    {getTooltipContent(segment)}
                                    <div className="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-gray-900"></div>
                                </div>
                            )}
                        </span>
                    );
                })}
            </div>
        </div>
    );
}

// Keyword Legend Component
interface KeywordLegendProps {
    foundCount: number;
    missingCount: number;
    className?: string;
}

export function KeywordLegend({ foundCount, missingCount, className = '' }: KeywordLegendProps) {
    return (
        <div className={`flex items-center space-x-4 text-sm ${className}`}>
            <div className="flex items-center">
                <div className="w-3 h-3 bg-green-100 border border-green-300 rounded mr-2"></div>
                <span className="text-gray-700">
                    Found ({foundCount})
                </span>
            </div>
            <div className="flex items-center">
                <div className="w-3 h-3 bg-red-100 border border-red-300 rounded mr-2"></div>
                <span className="text-gray-700">
                    Missing ({missingCount})
                </span>
            </div>
        </div>
    );
}

// Keyword Stats Component
interface KeywordStatsProps {
    totalKeywords: number;
    foundKeywords: number;
    missingKeywords: number;
    keywordDensity?: number;
    className?: string;
}

export function KeywordStats({ 
    totalKeywords, 
    foundKeywords, 
    missingKeywords, 
    keywordDensity,
    className = '' 
}: KeywordStatsProps) {
    const foundPercentage = totalKeywords > 0 ? Math.round((foundKeywords / totalKeywords) * 100) : 0;
    const missingPercentage = totalKeywords > 0 ? Math.round((missingKeywords / totalKeywords) * 100) : 0;

    return (
        <div className={`bg-gray-50 rounded-lg p-4 ${className}`}>
            <h4 className="text-sm font-medium text-gray-900 mb-3">Keyword Analysis</h4>
            
            <div className="grid grid-cols-2 gap-4 mb-4">
                <div className="text-center">
                    <div className="text-2xl font-bold text-green-600">{foundKeywords}</div>
                    <div className="text-xs text-gray-500">Found ({foundPercentage}%)</div>
                </div>
                <div className="text-center">
                    <div className="text-2xl font-bold text-red-600">{missingKeywords}</div>
                    <div className="text-xs text-gray-500">Missing ({missingPercentage}%)</div>
                </div>
            </div>

            {/* Progress bar */}
            <div className="w-full bg-gray-200 rounded-full h-2 mb-3">
                <div 
                    className="bg-green-500 h-2 rounded-full transition-all duration-300"
                    style={{ width: `${foundPercentage}%` }}
                ></div>
            </div>

            {keywordDensity !== undefined && (
                <div className="text-center">
                    <div className="text-sm text-gray-600">
                        Keyword Density: <span className="font-medium">{keywordDensity.toFixed(1)}%</span>
                    </div>
                </div>
            )}
        </div>
    );
}

// Interactive Keyword List Component
interface InteractiveKeywordListProps {
    keywords: string[];
    missingKeywords: string[];
    onKeywordAdd?: (keyword: string) => void;
    onKeywordRemove?: (keyword: string) => void;
    className?: string;
}

export function InteractiveKeywordList({
    keywords,
    missingKeywords,
    onKeywordAdd,
    onKeywordRemove,
    className = ''
}: InteractiveKeywordListProps) {
    return (
        <div className={`space-y-4 ${className}`}>
            {/* Found Keywords */}
            {keywords.length > 0 && (
                <div>
                    <h5 className="text-sm font-medium text-gray-900 mb-2">Found Keywords</h5>
                    <div className="flex flex-wrap gap-2">
                        {keywords.map((keyword, index) => (
                            <span
                                key={index}
                                className="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800"
                            >
                                {keyword}
                                {onKeywordRemove && (
                                    <button
                                        onClick={() => onKeywordRemove(keyword)}
                                        className="ml-1.5 hover:text-green-600"
                                    >
                                        <svg className="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                )}
                            </span>
                        ))}
                    </div>
                </div>
            )}

            {/* Missing Keywords */}
            {missingKeywords.length > 0 && (
                <div>
                    <h5 className="text-sm font-medium text-gray-900 mb-2">Missing Keywords</h5>
                    <div className="flex flex-wrap gap-2">
                        {missingKeywords.map((keyword, index) => (
                            <span
                                key={index}
                                className="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800"
                            >
                                {keyword}
                                {onKeywordAdd && (
                                    <button
                                        onClick={() => onKeywordAdd(keyword)}
                                        className="ml-1.5 hover:text-red-600"
                                        title={`Add "${keyword}" to resume`}
                                    >
                                        <svg className="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                        </svg>
                                    </button>
                                )}
                            </span>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}