declare global {
  interface Window {
    gtag: (...args: unknown[]) => void;
    dataLayer: unknown[];
  }
}

export default defineNuxtPlugin(() => {
  const config = useRuntimeConfig()
  const gaId = config.public.gaId

  console.log('[Analytics] Plugin loaded, gaId:', gaId)

  // Return early if gaId is empty or falsy
  if (!gaId) {
    console.log('[Analytics] No GA ID, skipping Route Tracking')
    return
  }

  // Track page views on route changes
  const router = useRouter()
  router.afterEach((to) => {
    // Guard to check if gtag is a function
    if (typeof window.gtag !== 'function') return

    nextTick(() => {
      window.gtag('event', 'page_view', {
        page_path: to.fullPath,
        page_location: window.location.href,
        page_title: document.title,
        send_to: gaId
      })
    })
  })
})
