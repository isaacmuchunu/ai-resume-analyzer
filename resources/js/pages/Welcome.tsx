import { Head, Link } from '@inertiajs/react';
import { FileText, Zap, Target, Award, BarChart3, Users, Shield, Globe } from 'lucide-react';
import { Button } from '@/components/ui/Button';

export default function Welcome() {
    return (
        <>
            <Head title="AI Resume Summarizer - NextGen SaaS Platform" />

            <div className="min-h-screen bg-gray-900 text-gray-100 font-sans">
                {/* Header */}
                <header className="bg-green-900 shadow-lg sticky top-0 z-50">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
                        <div className="flex items-center space-x-3">
                            <div className="h-10 w-10 bg-green-500 rounded-full flex items-center justify-center">
                                <FileText className="h-6 w-6 text-white" />
                            </div>
                            <span className="text-2xl font-bold text-white">AI Resume Summarizer</span>
                        </div>
                        <nav className="flex items-center space-x-6">
                            <Link href="#features" className="text-gray-300 hover:text-green-400 transition-colors">Features</Link>
                            <Link href="#pricing" className="text-gray-300 hover:text-green-400 transition-colors">Pricing</Link>
                            <Button variant="ghost" className="text-gray-300 hover:text-white hover:bg-green-800 rounded-full px-4 py-2" asChild>
                                <Link href="/login">Sign In</Link>
                            </Button>
                            <Button className="bg-green-500 hover:bg-green-600 text-white rounded-full px-6 py-2 transition-colors" asChild>
                                <Link href="/register">Get Started</Link>
                            </Button>
                        </nav>
                    </div>
                </header>

                {/* Hero Section */}
                <section className="py-24 bg-gradient-to-br from-green-950 to-gray-900">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                        <h1 className="text-4xl md:text-6xl font-extrabold text-white mb-6 leading-tight">
                            Revolutionize Resumes with <span className="text-green-400">AI Precision</span>
                        </h1>
                        <p className="text-lg md:text-xl text-gray-300 mb-10 max-w-3xl mx-auto">
                            Unlock the power of advanced AI and multi-tenant architecture for concise, insightful resume summaries tailored for all.
                        </p>
                        <div className="flex flex-col sm:flex-row gap-4 justify-center">
                            <Button size="lg" className="bg-green-500 hover:bg-green-600 text-white rounded-full px-8 py-3 transition-transform transform hover:scale-105" asChild>
                                <Link href="/register">Start Free Now</Link>
                            </Button>
                            <Button size="lg" variant="outline" className="border-green-400 text-green-400 hover:bg-green-800 rounded-full px-8 py-3 transition-transform transform hover:scale-105">
                                <Link href="#features">Explore Features</Link>
                            </Button>
                        </div>
                    </div>
                </section>

                {/* Features Section */}
                <section id="features" className="py-24 bg-gray-900">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="text-center mb-16">
                            <h2 className="text-3xl md:text-4xl font-bold text-white mb-4">Why Choose Us?</h2>
                            <p className="text-lg text-gray-300 max-w-2xl mx-auto">
                                Our platform delivers unmatched AI-driven insights, scalability, and security to surpass traditional tools.
                            </p>
                        </div>
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                            <div className="bg-green-950 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-shadow">
                                <div className="h-12 w-12 bg-green-500 rounded-full flex items-center justify-center mb-4">
                                    <Zap className="h-6 w-6 text-white" />
                                </div>
                                <h3 className="text-xl font-semibold text-white mb-2">AI-Powered Insights</h3>
                                <p className="text-gray-300">
                                    Powered by Anthropic Claude, our platform delivers concise summaries and actionable resume feedback.
                                </p>
                            </div>
                            <div className="bg-green-950 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-shadow">
                                <div className="h-12 w-12 bg-green-500 rounded-full flex items-center justify-center mb-4">
                                    <Users className="h-6 w-6 text-white" />
                                </div>
                                <h3 className="text-xl font-semibold text-white mb-2">Multi-Tenant SaaS</h3>
                                <p className="text-gray-300">
                                    Built for organizations with full tenant isolation, custom branding, and white-label solutions.
                                </p>
                            </div>
                            <div className="bg-green-950 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-shadow">
                                <div className="h-12 w-12 bg-green-500 rounded-full flex items-center justify-center mb-4">
                                    <Target className="h-6 w-6 text-white" />
                                </div>
                                <h3 className="text-xl font-semibold text-white mb-2">ATS Optimization</h3>
                                <p className="text-gray-300">
                                    99% compatibility with major ATS systems ensures your resume passes automated screening.
                                </p>
                            </div>
                            <div className="bg-green-950 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-shadow">
                                <div className="h-12 w-12 bg-green-500 rounded-full flex items-center justify-center mb-4">
                                    <Shield className="h-6 w-6 text-white" />
                                </div>
                                <h3 className="text-xl font-semibold text-white mb-2">Enterprise Security</h3>
                                <p className="text-gray-300">
                                    SOC 2 compliant with tenant-specific encryption for robust data protection.
                                </p>
                            </div>
                            <div className="bg-green-950 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-shadow">
                                <div className="h-12 w-12 bg-green-500 rounded-full flex items-center justify-center mb-4">
                                    <BarChart3 className="h-6 w-6 text-white" />
                                </div>
                                <h3 className="text-xl font-semibold text-white mb-2">Real-Time Analytics</h3>
                                <p className="text-gray-300">
                                    Track success with detailed analytics, benchmarking, and ROI measurement tools.
                                </p>
                            </div>
                            <div className="bg-green-950 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-shadow">
                                <div className="h-12 w-12 bg-green-500 rounded-full flex items-center justify-center mb-4">
                                    <Globe className="h-6 w-6 text-white" />
                                </div>
                                <h3 className="text-xl font-semibold text-white mb-2">Global Scalability</h3>
                                <p className="text-gray-300">
                                    Cloud-native architecture supports unlimited users and seamless scaling.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Pricing Section */}
                <section id="pricing" className="py-24 bg-green-950">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="text-center mb-16">
                            <h2 className="text-3xl md:text-4xl font-bold text-white mb-4">Simple Pricing Plans</h2>
                            <p className="text-lg text-gray-300 max-w-2xl mx-auto">
                                Choose a plan that fits your needs, with full access to AI summarization and multi-tenant features.
                            </p>
                        </div>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                            <div className="bg-gray-900 rounded-2xl p-6 shadow-lg flex flex-col">
                                <h3 className="text-2xl font-bold text-white mb-4">Basic</h3>
                                <div className="text-4xl font-bold text-green-400 mb-2">$0</div>
                                <p className="text-gray-400 mb-6">/month</p>
                                <ul className="space-y-4 mb-8 flex-grow">
                                    <li className="flex items-center text-gray-300">
                                        <Award className="h-5 w-5 text-green-400 mr-2" />
                                        Up to 5 summaries/month
                                    </li>
                                    <li className="flex items-center text-gray-300">
                                        <Award className="h-5 w-5 text-green-400 mr-2" />
                                        Basic AI insights
                                    </li>
                                    <li className="flex items-center text-gray-300">
                                        <Award className="h-5 w-5 text-green-400 mr-2" />
                                        Email support
                                    </li>
                                </ul>
                                <Button className="bg-green-500 hover:bg-green-600 text-white rounded-full px-6 py-3 mt-auto transition-transform transform hover:scale-105" asChild>
                                    <Link href="/register">Get Started</Link>
                                </Button>
                            </div>
                            <div className="bg-gray-900 rounded-2xl p-6 shadow-lg flex flex-col border-2 border-green-500 relative">
                                <div className="absolute top-0 right-4 -translate-y-1/2 bg-green-500 text-white px-3 py-1 rounded-full text-sm font-semibold">
                                    Most Popular
                                </div>
                                <h3 className="text-2xl font-bold text-white mb-4">Pro</h3>
                                <div className="text-4xl font-bold text-green-400 mb-2">$29</div>
                                <p className="text-gray-400 mb-6">/month</p>
                                <ul className="space-y-4 mb-8 flex-grow">
                                    <li className="flex items-center text-gray-300">
                                        <Award className="h-5 w-5 text-green-400 mr-2" />
                                        Unlimited summaries
                                    </li>
                                    <li className="flex items-center text-gray-300">
                                        <Award className="h-5 w-5 text-green-400 mr-2" />
                                        Advanced AI insights
                                    </li>
                                    <li className="flex items-center text-gray-300">
                                        <Award className="h-5 w-5 text-green-400 mr-2" />
                                        Priority support
                                    </li>
                                    <li className="flex items-center text-gray-300">
                                        <Award className="h-5 w-5 text-green-400 mr-2" />
                                        Custom branding
                                    </li>
                                </ul>
                                <Button className="bg-green-500 hover:bg-green-600 text-white rounded-full px-6 py-3 mt-auto transition-transform transform hover:scale-105" asChild>
                                    <Link href="/register">Get Started</Link>
                                </Button>
                            </div>
                            <div className="bg-gray-900 rounded-2xl p-6 shadow-lg flex flex-col">
                                <h3 className="text-2xl font-bold text-white mb-4">Enterprise</h3>
                                <div className="text-4xl font-bold text-green-400 mb-2">Custom</div>
                                <p className="text-gray-400 mb-6"></p>
                                <ul className="space-y-4 mb-8 flex-grow">
                                    <li className="flex items-center text-gray-300">
                                        <Award className="h-5 w-5 text-green-400 mr-2" />
                                        All Pro features
                                    </li>
                                    <li className="flex items-center text-gray-300">
                                        <Award className="h-5 w-5 text-green-400 mr-2" />
                                        Multi-tenant isolation
                                    </li>
                                    <li className="flex items-center text-gray-300">
                                        <Award className="h-5 w-5 text-green-400 mr-2" />
                                        Dedicated support
                                    </li>
                                    <li className="flex items-center text-gray-300">
                                        <Award className="h-5 w-5 text-green-400 mr-2" />
                                        White-label options
                                    </li>
                                    <li className="flex items-center text-gray-300">
                                        <Award className="h-5 w-5 text-green-400 mr-2" />
                                        API access
                                    </li>
                                </ul>
                                <Button className="bg-green-500 hover:bg-green-600 text-white rounded-full px-6 py-3 mt-auto transition-transform transform hover:scale-105" asChild>
                                    <Link href="/contact">Contact Sales</Link>
                                </Button>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Stats Section */}
                <section className="py-16 bg-gray-900">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                            <div>
                                <div className="text-3xl font-bold text-green-400 mb-2">99%</div>
                                <div className="text-gray-300">ATS Compatibility</div>
                            </div>
                            <div>
                                <div className="text-3xl font-bold text-green-400 mb-2">10M+</div>
                                <div className="text-gray-300">Resumes Summarized</div>
                            </div>
                            <div>
                                <div className="text-3xl font-bold text-green-400 mb-2">1000+</div>
                                <div className="text-gray-300">Organizations</div>
                            </div>
                            <div>
                                <div className="text-3xl font-bold text-green-400 mb-2">50+</div>
                                <div className="text-gray-300">Industries Supported</div>
                            </div>
                        </div>
                    </div>
                </section>

                {/* CTA Section */}
                <section className="py-24 bg-green-900">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                        <h2 className="text-3xl md:text-4xl font-bold text-white mb-6">Transform Your Hiring Process</h2>
                        <p className="text-lg text-gray-200 mb-10 max-w-3xl mx-auto">
                            Join thousands of organizations leveraging our AI platform for smarter, faster resume summarization.
                        </p>
                        <div className="flex flex-col sm:flex-row gap-4 justify-center">
                            <Button size="lg" className="bg-green-500 hover:bg-green-600 text-white rounded-full px-8 py-3 transition-transform transform hover:scale-105" asChild>
                                <Link href="/register">Start Free Trial</Link>
                            </Button>
                            <Button size="lg" variant="outline" className="border-white text-white hover:bg-green-800 rounded-full px-8 py-3 transition-transform transform hover:scale-105" asChild>
                                <Link href="/contact">Contact Sales</Link>
                            </Button>
                        </div>
                    </div>
                </section>

                {/* Footer */}
                <footer className="bg-green-950 text-white py-12">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="flex flex-col md:flex-row items-center justify-between">
                            <div className="flex items-center space-x-3 mb-4 md:mb-0">
                                <div className="h-10 w-10 bg-green-500 rounded-full flex items-center justify-center">
                                    <FileText className="h-6 w-6 text-white" />
                                </div>
                                <span className="text-xl font-bold">AI Resume Summarizer</span>
                            </div>
                            <div className="flex space-x-6">
                                <Link href="/privacy" className="text-gray-400 hover:text-green-400 transition-colors">Privacy</Link>
                                <Link href="/terms" className="text-gray-400 hover:text-green-400 transition-colors">Terms</Link>
                                <Link href="/contact" className="text-gray-400 hover:text-green-400 transition-colors">Contact</Link>
                            </div>
                        </div>
                        <p className="text-center text-gray-400 mt-6">
                            © 2025 AI Resume Summarizer. Powered by Laravel, React & Anthropic Claude.
                        </p>
                    </div>
                </footer>
            </div>
        </>
    );
}