import React, { useState, useEffect } from 'react';
import { X, Download, Smartphone } from 'lucide-react';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';

interface BeforeInstallPromptEvent extends Event {
    prompt: () => Promise<void>;
    userChoice: Promise<{ outcome: 'accepted' | 'dismissed' }>;
}

export default function PWAInstallPrompt() {
    const [deferredPrompt, setDeferredPrompt] = useState<BeforeInstallPromptEvent | null>(null);
    const [showInstallPrompt, setShowInstallPrompt] = useState(false);
    const [isIOS, setIsIOS] = useState(false);
    const [isInStandaloneMode, setIsInStandaloneMode] = useState(false);

    useEffect(() => {
        // Check if running on iOS
        const iOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
        setIsIOS(iOS);

        // Check if already installed (running in standalone mode)
        const standalone = window.matchMedia('(display-mode: standalone)').matches ||
                          (window.navigator as any).standalone === true;
        setIsInStandaloneMode(standalone);

        // Don't show prompt if already installed or user dismissed it recently
        const dismissed = localStorage.getItem('pwa-install-dismissed');
        if (standalone || dismissed) {
            return;
        }

        // Listen for the beforeinstallprompt event
        const handleBeforeInstallPrompt = (e: Event) => {
            e.preventDefault();
            setDeferredPrompt(e as BeforeInstallPromptEvent);

            // Show our custom install prompt after a delay
            setTimeout(() => {
                setShowInstallPrompt(true);
            }, 30000); // Show after 30 seconds
        };

        window.addEventListener('beforeinstallprompt', handleBeforeInstallPrompt);

        // For iOS devices, show install instructions after some time
        if (iOS && !standalone) {
            setTimeout(() => {
                setShowInstallPrompt(true);
            }, 45000); // Show after 45 seconds on iOS
        }

        return () => {
            window.removeEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
        };
    }, []);

    const handleInstall = async () => {
        if (!deferredPrompt) return;

        try {
            await deferredPrompt.prompt();
            const choiceResult = await deferredPrompt.userChoice;

            if (choiceResult.outcome === 'accepted') {
                console.log('User accepted the install prompt');
            } else {
                console.log('User dismissed the install prompt');
            }
        } catch (error) {
            console.error('Error during installation:', error);
        }

        setDeferredPrompt(null);
        setShowInstallPrompt(false);
    };

    const handleDismiss = () => {
        setShowInstallPrompt(false);
        localStorage.setItem('pwa-install-dismissed', Date.now().toString());
    };

    // Don't show if already installed
    if (isInStandaloneMode) {
        return null;
    }

    // Don't show if user dismissed
    if (!showInstallPrompt) {
        return null;
    }

    return (
        <div className="fixed bottom-4 left-4 right-4 z-50 md:left-auto md:right-4 md:max-w-sm">
            <Card className="p-4 shadow-lg border bg-white dark:bg-gray-800 animate-slide-up">
                <div className="flex items-start justify-between mb-3">
                    <div className="flex items-center">
                        <div className="flex items-center justify-center w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-lg mr-3">
                            <Smartphone className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div>
                            <h3 className="font-semibold text-gray-900 dark:text-white text-sm">
                                Install App
                            </h3>
                            <p className="text-xs text-gray-600 dark:text-gray-400">
                                Get the full experience
                            </p>
                        </div>
                    </div>
                    <button
                        onClick={handleDismiss}
                        className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                    >
                        <X className="h-4 w-4" />
                    </button>
                </div>

                <div className="space-y-3">
                    {isIOS ? (
                        // iOS Instructions
                        <div className="text-xs text-gray-600 dark:text-gray-400">
                            <p className="mb-2">To install this app on your iOS device:</p>
                            <ol className="list-decimal ml-4 space-y-1">
                                <li>Tap the share button in Safari</li>
                                <li>Select "Add to Home Screen"</li>
                                <li>Tap "Add" to confirm</li>
                            </ol>
                        </div>
                    ) : (
                        // Chrome/Android Instructions
                        <div className="text-xs text-gray-600 dark:text-gray-400 mb-3">
                            Install our app for faster access, offline support, and a better mobile experience.
                        </div>
                    )}

                    <div className="flex space-x-2">
                        {!isIOS && deferredPrompt && (
                            <Button
                                onClick={handleInstall}
                                size="sm"
                                className="flex-1"
                            >
                                <Download className="h-3 w-3 mr-1" />
                                Install
                            </Button>
                        )}
                        <Button
                            onClick={handleDismiss}
                            variant="outline"
                            size="sm"
                            className={!isIOS && deferredPrompt ? 'flex-1' : 'w-full'}
                        >
                            Not Now
                        </Button>
                    </div>
                </div>

                {/* Benefits list */}
                <div className="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                    <div className="text-xs text-gray-500 dark:text-gray-400">
                        ✓ Offline access &nbsp;&nbsp; ✓ Faster loading &nbsp;&nbsp; ✓ Push notifications
                    </div>
                </div>
            </Card>
        </div>
    );
}

// CSS for animations (add to your global CSS)
const styles = `
@keyframes slide-up {
    from {
        transform: translateY(100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.animate-slide-up {
    animation: slide-up 0.3s ease-out;
}
`;