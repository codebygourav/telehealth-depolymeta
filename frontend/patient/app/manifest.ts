import type { MetadataRoute } from 'next'

export default function manifest(): MetadataRoute.Manifest {
    return {
        name: 'CMC Telehealth PWA',
        short_name: 'CMC Telehealth PWA',
        description: 'A Progressive Web App built with Next.js',
        start_url: '/',
        display: 'standalone',
        scope: "/",
        background_color: '#ffffff',
        theme_color: '#000000',
        icons: [
            {
                src: 'icons/icon-192x192.png',
                sizes: '192x192',
                type: 'image/png',
            },
            {
                src: 'icons/icon-512x512.png',
                sizes: '512x512',
                type: 'image/png',
            },
        ],
    }
}