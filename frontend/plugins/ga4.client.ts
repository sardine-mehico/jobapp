declare global {
  interface Window {
    dataLayer: unknown[]
    gtag?: (...args: unknown[]) => void
  }
}

function isExcludedPath(path: string) {
  return (
    path === '/employer/login' ||
    path.startsWith('/admin')
  )
}

export default defineNuxtPlugin((nuxtApp) => {
  const runtimeConfig = useRuntimeConfig()
  const measurementId = String(runtimeConfig.public.ga4Id || '').trim()
  const isProduction = import.meta.env.PROD

  if (!measurementId || !isProduction) {
    return
  }

  // Standard gtag bootstrap - dataLayer queue must exist before script loads
  window.dataLayer = window.dataLayer || []
  window.gtag = function gtag(...args: unknown[]) {
    window.dataLayer.push(args)
  }
  window.gtag('js', new Date())
  // Tell GA4 not to fire an automatic page_view on load - we'll do it manually for SPA navigation
  window.gtag('config', measurementId, { send_page_view: false })

  // Inject the gtag/js script (note: NO encodeURIComponent - the ID must be raw)
  const scriptId = 'jobapp-ga4-script'
  if (!document.getElementById(scriptId)) {
    const script = document.createElement('script')
    script.id = scriptId
    script.async = true
    script.src = `https://www.googletagmanager.com/gtag/js?id=${measurementId}`
    document.head.appendChild(script)
  }

  let lastTrackedPath = ''

  const sendPageView = (fullPath: string) => {
    const path = fullPath.split('?')[0] || '/'

    if (isExcludedPath(path)) return
    if (fullPath === lastTrackedPath) return

    lastTrackedPath = fullPath

    // Push directly to dataLayer - works even before gtag/js script finishes loading
    window.gtag!('event', 'page_view', {
      page_path: fullPath,
      page_location: window.location.origin + fullPath,
      page_title: document.title,
      send_to: measurementId,
    })
  }

  // Track initial page load (critical for SPA direct-URL visits)
  nuxtApp.hook('app:mounted', () => {
    const initialPath = nuxtApp.$router.currentRoute.value.fullPath
    sendPageView(initialPath)
  })

  // Track subsequent SPA navigations
  nuxtApp.$router.afterEach((to) => {
    sendPageView(to.fullPath)
  })
})
