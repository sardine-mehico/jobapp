// analytics.client.ts
// Google Analytics 4 integration for Nuxt SPA.
// To enable: set NUXT_PUBLIC_GA4_ID in your .env file.
// No ID → plugin is completely inert.

declare global {
  interface Window {
    dataLayer: unknown[]
    gtag?: (...args: unknown[]) => void
  }
}

export default defineNuxtPlugin((nuxtApp) => {
  const { public: config } = useRuntimeConfig()
  const id = (config.ga4Id as string | undefined)?.trim()

  // Bail out if no ID is configured
  if (!id) return

  // ── Bootstrap dataLayer + gtag shim before the script loads ──
  window.dataLayer = window.dataLayer || []
  window.gtag = function (...args: unknown[]) {
    window.dataLayer.push(args)
  }
  window.gtag('js', new Date())
  // 'config' fires one automatic page_view on load – ideal for initial visit
  window.gtag('config', id)

  // ── Inject the official gtag/js script into <head> ──
  useHead({
    script: [
      {
        key: 'gtag-script',
        src: `https://www.googletagmanager.com/gtag/js?id=${id}`,
        async: true,
        tagPriority: 'high',
      },
    ],
  })

  // ── Track subsequent SPA route changes ──
  // GA4 auto-fires a page_view on initial load (via 'config' above).
  // For SPA navigation we send a manual event on every route change.
  let isFirstRoute = true

  nuxtApp.$router.afterEach((to) => {
    // Skip the first navigation to avoid a duplicate of the auto page_view
    if (isFirstRoute) {
      isFirstRoute = false
      return
    }

    window.gtag('event', 'page_view', {
      page_path: to.fullPath,
      page_location: window.location.origin + to.fullPath,
      page_title: document.title,
    })
  })
})
