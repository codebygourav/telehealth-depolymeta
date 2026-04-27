// const withPWA = require('next-pwa');

// /** @type {import('next').NextConfig} */
// const nextConfig = {
//   reactStrictMode: true,
//   output: "standalone",
//   compiler: {
//     removeConsole: process.env.NODE_ENV !== "development"
//   },
//   turbopack: {},
//   images: {
//     remotePatterns: [
//       {
//         protocol: 'https',
//         hostname: 'telehealthwebapplive.cmcludhiana.in',
//         port: '',
//         pathname: '/storage/**',
//       },
//     ],
//   },
//   pwa: {
//     dest: "public",
//     register: true,
//     skipWaiting: true,
//     disable: process.env.NODE_ENV === 'development',
//   },
// };

// export default process.env.NODE_ENV === 'development' ? nextConfig : withPWA(nextConfig);


import withPWAInit from "@ducanh2912/next-pwa";
import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  reactStrictMode: true,
  output: "standalone",
  compiler: {
    removeConsole: process.env.NODE_ENV !== "development",
  },
  images: {
    remotePatterns: [
      {
        protocol: "https",
        hostname: "telehealthwebapplive.cmcludhiana.in",
        pathname: "/**",
      },
    ],
  },
  turbopack: {},
};

const withPWA = withPWAInit({
  dest: "public",
  register: true,
  disable: process.env.NODE_ENV === "development",
});

export default withPWA(nextConfig);