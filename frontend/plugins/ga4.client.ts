declare global {
  interface Window {
    dataLayer: unknown[]
    gtag?: (...args: unknown[]) => void
  }
}

function isTrackedPath(path: string) {
  if (path === '/' || path === '/thank-you') {
    return true
  }

  if (
    path === '/employer/login' ||
    path.startsWith('/admin')
  ) {
    return false
  }

  return true
}

function loadGoogleTag(measurementId: string) {
  const scriptId = 'jobapp-ga4-script'

  if (document.getElementById(scriptId)) {
    return
  }

  const script = document.createElement('script')
  script.id = scriptId
  script.async = true
  script.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(measurementId)}`
  document.head.appendChild(script)
}

function initGoogleAnalytics(measurementId: string) {
  window.dataLayer = window.dataLayer || []

  window.gtag = function gtag(...args: unknown[]) {
    window.dataLayer.push(args)
  }

  window.gtag('js', new Date())
  window.gtag('config', measurementId, {
    send_page_view: false,
  })
}

function trackPageView(measurementId: string, fullPath: string) {
  if (!window.gtag) {
    return
  }

  const [pathWithoutQuery, search = ''] = fullPath.split('?')

  if (!isTrackedPath(pathWithoutQuery || '/')) {
    return
  }

  window.gtag('event', 'page_view', {
    page_path: fullPath,
    page_location: window.location.href,
    page_title: document.title,
    send_to: measurementId,
    ...(search ? { page_search: `?${search}` } : {}),
  })
}

export default defineNuxtPlugin((nuxtApp) => {
  const runtimeConfig = useRuntimeConfig()
  const measurementId = String(runtimeConfig.public.ga4Id || '').trim()
  const isProduction = import.meta.env.PROD

  if (!measurementId || !isProduction || !process.client) {
    return
  }

  loadGoogleTag(measurementId)
  initGoogleAnalytics(measurementId)

  let lastTrackedPath = ''

  const maybeTrackCurrentRoute = () => {
    const fullPath = nuxtApp.$router.currentRoute.value.fullPath

    if (fullPath === lastTrackedPath) {
      return
    }

    lastTrackedPath = fullPath
    trackPageView(measurementId, fullPath)
  }

  // Track the initial page load (fires once on first mount)
  nuxtApp.hook('app:mounted', () => {
    setTimeout(maybeTrackCurrentRoute, 0)
  })

  nuxtApp.hook('page:finish', maybeTrackCurrentRoute)

  nuxtApp.$router.afterEach(() => {
    queueMicrotask(maybeTrackCurrentRoute)
  })
})
