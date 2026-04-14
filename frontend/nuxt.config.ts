import tailwindcss from '@tailwindcss/vite'

const gaId = process.env.NUXT_PUBLIC_GA_ID || ''
export default defineNuxtConfig({
  compatibilityDate: '2025-03-01',
  devtools: { enabled: true },
  ssr: false,
  modules: [
    '@vite-pwa/nuxt'
  ],
  pwa: {
    manifest: {
      name: 'JobApp',
      short_name: 'JobApp',
      description: 'JobApp Portal',
      theme_color: '#ffffff',
      icons: [
        {
          src: 'icon-192.png',
          sizes: '192x192',
          type: 'image/png'
        },
        {
          src: 'icon-512.png',
          sizes: '512x512',
          type: 'image/png'
        },
        {
          src: 'icon-192-maskable.png',
          sizes: '192x192',
          type: 'image/png',
          purpose: 'maskable'
        },
        {
          src: 'icon-512-maskable.png',
          sizes: '512x512',
          type: 'image/png',
          purpose: 'maskable'
        }
      ]
    },
    workbox: {
      navigateFallback: '/'
    },
    devOptions: {
      enabled: true,
      type: 'module'
    }
  },
  css: ['~/assets/css/main.css'],
  vite: {
    plugins: [tailwindcss()]
  },
  app: {
    head: {
      title: 'JobApp',
      meta: [
        { name: 'viewport', content: 'width=device-width, initial-scale=1' },
        { name: 'theme-color', content: '#ffffff' }
      ],
      link: [
        { rel: 'icon', type: 'image/x-icon', href: '/favicon.ico' },
        { rel: 'apple-touch-icon', href: '/apple-touch-icon.png' },
        { rel: 'preconnect', href: 'https://fonts.googleapis.com' },
        { rel: 'preconnect', href: 'https://fonts.gstatic.com', crossorigin: '' },
        { rel: 'stylesheet', href: 'https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap' }
      ],
      script: gaId ? [
        {
          src: `https://www.googletagmanager.com/gtag/js?id=${gaId}`,
          async: true,
          tagPosition: 'head'
        },
        {
          innerHTML: `
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '${gaId}', { send_page_view: false });
          `,
          tagPosition: 'head'
        }
      ] : []
    }
  },
  runtimeConfig: {
    public: {
      apiBase: process.env.NUXT_PUBLIC_API_BASE || 'http://localhost:9000/api',
      ga4Id: process.env.NUXT_PUBLIC_GA4_ID || '',
      gaId: process.env.NUXT_PUBLIC_GA_ID || ''
    }
  }
})
