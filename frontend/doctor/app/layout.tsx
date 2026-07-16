import type { Metadata } from "next";
import { Cormorant_Garamond, Manrope, Geist_Mono } from "next/font/google";
import { cn } from "@/lib/utils";
import "./globals.css";
import { Providers } from "./providers";
import Head from "next/head";

const cormorant = Cormorant_Garamond({
    subsets: ['latin'],
    weight: ['300', '400', '500', '600', '700'],
    variable: '--font-headings',
})

const manrope = Manrope({
    subsets: ['latin'],
    variable: '--font-sans',
})

const fontMono = Geist_Mono({
    subsets: ["latin"],
    variable: "--font-mono",
})

export const metadata: Metadata = {
    title: "Deploymeta Telehealth - Doctor",
    description: "A Progressive Web App for Doctors in Deploymeta Telehealth",
    manifest: "/manifest.webmanifest",
    icons: {
        icon: "/favicon.ico",
    },
};

export default function RootLayout({ children }: Readonly<{ children: React.ReactNode }>) {
    return (
        <html
            lang="en"
            className={cn("antialiased", fontMono.variable, "font-sans", manrope.variable, cormorant.variable)}
            suppressHydrationWarning
        >
            <Head>
                <link rel="icon" href="/favicon.ico" />
            </Head>
            <body className="min-h-full flex flex-col" suppressHydrationWarning>
                <Providers>
                    {children}
                </Providers>
            </body>
        </html>
    );
}