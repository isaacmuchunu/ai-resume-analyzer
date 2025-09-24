import { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import { Card } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import { Input } from '@/components/ui/Input';
import { Sparkles, Save } from 'lucide-react';

interface PageProps {
    resume: { id: number; original_filename: string };
    content: string;
    version: number;
}

export default function Editor() {
    const { resume, content: initialContent, version } = usePage<PageProps>().props;
    const [content, setContent] = useState<string>(initialContent || '');
    const [selection, setSelection] = useState<{ start: number; end: number } | null>(null);
    const [busy, setBusy] = useState(false);
    const [lastSavedVersion, setLastSavedVersion] = useState<number>(version || 0);
    const [versions, setVersions] = useState<Array<{ id: number; version_number: number; title?: string; created_at: string }>>([]);

    const loadVersions = async () => {
        const res = await fetch(`/resumes/${resume.id}/editor/versions`);
        const data = await res.json();
        if (Array.isArray(data?.versions)) setVersions(data.versions);
    };

    const getSelectedText = () => {
        if (!selection) return '';
        return content.slice(selection.start, selection.end);
    };

    const handleSuggest = async (type: 'rewrite' | 'shorten' | 'expand' | 'quantify') => {
        const text = getSelectedText();
        if (!text) return alert('Select some text first.');
        setBusy(true);
        try {
            const res = await fetch(`/resumes/${resume.id}/editor/suggest`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ type, text }),
            });
            const data = await res.json();
            if (data?.suggestion?.suggested_text && selection) {
                const before = content.slice(0, selection.start);
                const after = content.slice(selection.end);
                setContent(before + data.suggestion.suggested_text + after);
            }
        } finally {
            setBusy(false);
        }
    };

    const handleSave = async () => {
        setBusy(true);
        try {
            const res = await fetch(`/resumes/${resume.id}/editor/save`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ content }),
            });
            const data = await res.json();
            if (data?.version?.version_number) setLastSavedVersion(data.version.version_number);
        } finally {
            setBusy(false);
        }
    };

    return (
        <>
            <Head title={`Editor • ${resume.original_filename}`} />
            <div className="min-h-screen bg-gray-50 dark:bg-gray-900 py-12">
                <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                    <div className="flex items-center justify-between">
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Interactive Editor</h1>
                        <div className="flex items-center gap-2">
                            <Badge variant="outline">v{lastSavedVersion}</Badge>
                            <Button onClick={handleSave} disabled={busy}>
                                <Save className="h-4 w-4 mr-2" /> Save Version
                            </Button>
                        </div>
                    </div>

                    <Card className="p-4">
                        <div className="flex flex-wrap gap-2 mb-3">
                            <Button variant="secondary" size="sm" onClick={() => handleSuggest('rewrite')} disabled={busy}>
                                <Sparkles className="h-4 w-4 mr-2" /> Rewrite
                            </Button>
                            <Button variant="secondary" size="sm" onClick={() => handleSuggest('shorten')} disabled={busy}>
                                <Sparkles className="h-4 w-4 mr-2" /> Shorten
                            </Button>
                            <Button variant="secondary" size="sm" onClick={() => handleSuggest('expand')} disabled={busy}>
                                <Sparkles className="h-4 w-4 mr-2" /> Expand
                            </Button>
                            <Button variant="secondary" size="sm" onClick={() => handleSuggest('quantify')} disabled={busy}>
                                <Sparkles className="h-4 w-4 mr-2" /> Quantify
                            </Button>
                        </div>

                        <textarea
                            value={content}
                            onChange={(e) => setContent(e.target.value)}
                            onSelect={(e) => {
                                const target = e.target as HTMLTextAreaElement;
                                setSelection({ start: target.selectionStart, end: target.selectionEnd });
                            }}
                            className="w-full min-h-[420px] px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-slate-500 focus:border-transparent"
                            placeholder="Start editing your resume..."
                        />
                    </Card>

                    <Card className="p-4">
                        <div className="flex items-center justify-between mb-3">
                            <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Versions</h2>
                            <Button variant="outline" size="sm" onClick={loadVersions}>Refresh</Button>
                        </div>
                        {versions.length === 0 ? (
                            <div className="text-sm text-gray-600 dark:text-gray-400">No versions yet.</div>
                        ) : (
                            <div className="space-y-2">
                                {versions.map((v) => (
                                    <div key={v.id} className="flex items-center justify-between text-sm">
                                        <div className="text-gray-800 dark:text-gray-200">v{v.version_number} • {v.title || 'Untitled'} • {new Date(v.created_at).toLocaleString()}</div>
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            onClick={async () => {
                                                const res = await fetch(`/resumes/${resume.id}/editor/versions/${v.id}/restore`, { method: 'POST' });
                                                const data = await res.json();
                                                if (data?.content) {
                                                    setContent(data.content);
                                                    setLastSavedVersion(v.version_number);
                                                }
                                            }}
                                        >
                                            Restore
                                        </Button>
                                    </div>
                                ))}
                            </div>
                        )}
                    </Card>
                </div>
            </div>
        </>
    );
}


