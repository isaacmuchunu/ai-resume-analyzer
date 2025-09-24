import { Head, Link, usePage, router } from '@inertiajs/react';
import { Card } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import { FileText, BarChart2, Download, RefreshCcw } from 'lucide-react';

interface ResumeItem {
	id: number;
	original_filename: string;
	file_size: number;
	file_type: string;
	parsing_status: string;
	analysis_status: string;
	created_at: string;
	latest_analysis?: {
		id: number;
		overall_score: number;
		ats_score: number;
		content_score: number;
		format_score: number;
		keyword_score: number;
		created_at: string;
	} | null;
}

interface PageProps {
	tenant: {
		name: string;
		branding?: Record<string, unknown>;
	};
	resumes: {
		data: ResumeItem[];
		links?: { url: string | null; label: string; active: boolean }[];
	};
}

export default function ResumesIndex() {
	const { props } = usePage<any>();
	const { tenant, resumes } = props;

	return (
		<>
			<Head title="Your Resumes" />
			<div className="min-h-screen bg-gray-50 dark:bg-gray-900 py-12">
				<div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
					<div className="flex items-center justify-between">
						<h1 className="text-2xl font-bold text-gray-900 dark:text-white">Your Resumes</h1>
						<Link href="/resumes/upload">
							<Button>Upload New</Button>
						</Link>
					</div>

					{resumes.data.length === 0 ? (
						<Card className="p-8 text-center">
							<p className="text-gray-600 dark:text-gray-400">No resumes uploaded yet.</p>
							<div className="mt-4">
								<Link href="/resumes/upload">
									<Button>Upload your first resume</Button>
								</Link>
							</div>
						</Card>
					) : (
						<div className="space-y-4">
							{resumes.data.map((resume: ResumeItem) => (
								<Card key={resume.id} className="p-6 flex items-center justify-between">
									<div className="flex items-start gap-4">
										<FileText className="h-6 w-6 text-slate-600 mt-1" />
										<div>
											<Link href={`/resumes/${resume.id}`} className="font-semibold text-gray-900 dark:text-white hover:underline">
												{resume.original_filename}
											</Link>
											<div className="mt-1 flex items-center gap-2">
												<StatusBadge label="Parsing" status={resume.parsing_status} />
												<StatusBadge label="Analysis" status={resume.analysis_status} />
											</div>
											{resume.latest_analysis && (
												<div className="mt-2 inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
													<BarChart2 className="h-4 w-4" />
													<span>Overall score: {resume.latest_analysis.overall_score}</span>
												</div>
											)}
										</div>
									</div>
									<div className="flex items-center gap-2">
										<Link href={`/resumes/${resume.id}/download`}>
											<Button variant="ghost" size="sm"><Download className="h-4 w-4 mr-2" />Download</Button>
										</Link>
										<Link href={`/resumes/${resume.id}`}>
											<Button size="sm">View Details</Button>
										</Link>
										<Button variant="ghost" size="sm" onClick={() => router.post(`/resumes/${resume.id}/reanalyze`)}>
											<RefreshCcw className="h-4 w-4 mr-2" /> Reanalyze
										</Button>
									</div>
								</Card>
							))}
						</div>
					)}
				</div>
			</div>
		</>
	);
}

function StatusBadge({ label, status }: { label: string; status: string }) {
	const variant = status === 'completed' ? 'success' : status === 'processing' ? 'info' : status === 'failed' ? 'destructive' : 'outline';
	return (
		<div className="flex items-center gap-2">
			<span className="text-xs text-gray-500 dark:text-gray-400">{label}</span>
			<Badge variant={variant as any} className="capitalize">{status}</Badge>
		</div>
	);
}

