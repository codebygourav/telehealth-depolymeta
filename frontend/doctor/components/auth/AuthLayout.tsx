import Link from 'next/link';
import { ReactNode } from 'react';

interface AuthLayoutProps {
    title: string;
    subtitle?: string;
    children: ReactNode;
}

const AuthLayout = ({ title, subtitle, children }: AuthLayoutProps) => {
    return (
        <div className="min-h-screen bg-linear-to-br from-primary/5 via-background to-secondary/5">
            <div className="flex min-h-screen items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
                <div className="w-full max-w-xl">

                    {/* Card Container */}
                    <div className="mt-8 bg-card py-8 px-6 shadow-xl rounded-xl border border-border sm:px-10">
                        {/* Header */}
                        <div className="mb-6 text-center">
                            <h2 className="text-2xl font-bold text-foreground">{title}</h2>
                            {subtitle && (
                                <p className="mt-2 text-sm text-muted-foreground">{subtitle}</p>
                            )}
                        </div>

                        {/* Content (Form / Message) */}
                        {children}
                    </div>

                    {/* Footer Links */}
                    {/* <div className="mt-6 text-center text-sm text-muted-foreground">
                        <Link href="/" className="hover:text-primary transition-colors">
                            ← Back to Home
                        </Link>
                    </div> */}
                </div>
            </div>
        </div>
    );
};

export default AuthLayout;