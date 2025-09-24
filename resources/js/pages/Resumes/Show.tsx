import { Head, usePage, Link, router } from '@inertiajs/react';
import { Card } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import { ArrowLeft, BarChart2, FileText, RefreshCcw } from 'lucide-react';

interface AnalysisResult {
	id: number;
	analysis_type: string;
	overall_score: number | null;
	ats_score: number | null;
	content_score: number | null;
	format_score: number | null;
	keyword_score: number | null;
	detailed_scores?: Record<string, unknown> | null;
	recommendations?: string[] | null;
	extracted_skills?: Record<string, unknown> | null;
	missing_skills?: string[] | null;
	keywords?: string[] | null;
	sections_analysis?: Record<string, unknown> | null;
	ai_insights?: string | null;
	created_at: string;
}

interface PageProps {
	tenant: { name: string };
	resume: {
		id: number;
		original_filename: string;
		file_size: number;
		file_type: string;
		parsing_status: string;
		analysis_status: string;
		metadata: Record<string, any> | null;
		created_at: string;
		updated_at: string;
	};
	analysis_results: AnalysisResult[];
}

export default function ResumeShow() {
	const { props } = usePage<any>();
	const { resume, analysis_results } = props;

	const handleReanalyze = () => {
		router.post(`/resumes/${resume.id}/reanalyze`);
	};

	return (
		<>
			<Head title={`Resume • ${resume.original_filename}`} />
			<div className="min-h-screen bg-gray-50 dark:bg-gray-900 py-12">
				<div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
					<div className="flex items-center justify-between">
						<Link href="/resumes">
							<Button variant="ghost"><ArrowLeft className="h-4 w-4 mr-2" /> Back</Button>
						</Link>
						<div className="flex items-center gap-2">
							<Button onClick={handleReanalyze} variant="primary" size="sm">
								<RefreshCcw className="h-4 w-4 mr-2" /> Reanalyze
							</Button>
						</div>
					</div>

					<Card className="p-6">
						<div className="flex items-start gap-4">
							<FileText className="h-6 w-6 text-slate-600 mt-1" />
							<div>
								<h1 className="text-xl font-semibold text-gray-900 dark:text-white">{resume.original_filename}</h1>
								<div className="mt-1 flex items-center gap-2">
									<StatusBadge label="Parsing" status={resume.parsing_status} />
									<StatusBadge label="Analysis" status={resume.analysis_status} />
								</div>
								{resume.metadata && (
									<div className="mt-2 text-sm text-gray-700 dark:text-gray-300">
										{resume.metadata.target_role && <div>Target role: {resume.metadata.target_role}</div>}
										{resume.metadata.target_industry && <div>Target industry: {resume.metadata.target_industry}</div>}
									</div>
								)}
							</div>
						</div>
					</Card>

					<div className="space-y-4">
						{analysis_results.length === 0 ? (
							<Card className="p-6 text-center text-gray-600 dark:text-gray-400">No analysis results yet.</Card>
						) : (
							analysis_results.map((result: AnalysisResult) => (
								<Card key={result.id} className="p-6">
									<div className="flex items-center justify-between">
										<div className="flex items-center gap-2">
											<BarChart2 className="h-5 w-5 text-slate-600" />
											<h2 className="font-semibold text-gray-900 dark:text-white">{result.analysis_type} analysis</h2>
										</div>
										<div className="text-sm text-gray-600 dark:text-gray-400">{new Date(result.created_at).toLocaleString()}</div>
									</div>

									<div className="mt-4 grid grid-cols-2 md:grid-cols-5 gap-3">
										<Score label="Overall" value={result.overall_score} />
										<Score label="ATS" value={result.ats_score} />
										<Score label="Content" value={result.content_score} />
										<Score label="Format" value={result.format_score} />
										<Score label="Keywords" value={result.keyword_score} />
									</div>

									{result.recommendations && result.recommendations.length > 0 && (
										<div className="mt-6">
											<h3 className="font-medium text-gray-900 dark:text-white mb-2">Recommendations</h3>
											<ul className="list-disc ml-6 text-gray-700 dark:text-gray-300 space-y-1">
												{result.recommendations.map((rec: string, idx: number) => (
													<li key={idx}>{rec}</li>
												))}
											</ul>
										</div>
									)}

									{result.ai_insights && (
										<div className="mt-6">
											<h3 className="font-medium text-gray-900 dark:text-white mb-2">AI Insights</h3>
											<p className="whitespace-pre-wrap text-gray-700 dark:text-gray-300 text-sm">{result.ai_insights}</p>
										</div>
									)}

									{/* Sections analysis */}
									{result.sections_analysis && (
										<div className="mt-6">
											<h3 className="font-medium text-gray-900 dark:text-white mb-2">Sections</h3>
											<div className="grid grid-cols-1 md:grid-cols-2 gap-3">
												{Object.entries(result.sections_analysis).map(([name, data]) => (
													<Card key={name as string} className="p-4">
														<div className="flex items-center justify-between">
															<div className="font-medium capitalize text-gray-900 dark:text-white">{name}</div>
															<div className="text-xs text-gray-500 dark:text-gray-400">{(data as any).present ? 'Present' : 'Missing'}</div>
														</div>
														{Array.isArray((data as any).recommendations) && (data as any).recommendations.length > 0 && (
															<ul className="mt-2 list-disc ml-5 text-sm text-gray-700 dark:text-gray-300 space-y-1">
																{(data as any).recommendations.map((r: string, idx: number) => (<li key={idx}>{r}</li>))}
															</ul>
														)}
													</Card>
												))}
											</div>
										</div>
									)}

									{/* Skills and keywords */}
									{(result.extracted_skills || result.keywords) && (
										<div className="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
											{result.extracted_skills && (
												<Card className="p-4">
													<h3 className="font-medium text-gray-900 dark:text-white mb-2">Extracted Skills</h3>
													<div className="flex flex-wrap gap-2">
														{Object.entries(result.extracted_skills).map(([group, values]: [string, any]) => (
															<div key={group} className="space-x-2">
																<span className="text-xs uppercase text-gray-500 dark:text-gray-400">{group}</span>
																{Array.isArray(values) && values.map((v: any, idx: number) => (
																	<Badge key={String(v)+idx} variant="info">{String(v)}</Badge>
																))}
															</div>
														))}
													</div>
												</Card>
											)}
											{result.keywords && (
												<Card className="p-4">
													<h3 className="font-medium text-gray-900 dark:text-white mb-2">Keywords</h3>
													<div className="flex flex-wrap gap-2">
														{result.keywords.map((k: string, idx: number) => (
															<Badge key={k+idx} variant="outline">{k}</Badge>
														))}
													</div>
												</Card>
											)}
										</div>
									)}
								</Card>
							))
						)}
					</div>
				</div>
			</div>
		</>
	);
}

function Score({ label, value }: { label: string; value: number | null }) {
	return (
		<div className="rounded-md border border-gray-200 dark:border-gray-700 p-3 text-center">
			<div className="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{label}</div>
			<div className="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{value ?? '—'}</div>
		</div>
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

