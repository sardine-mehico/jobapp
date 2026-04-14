export default defineNuxtPlugin(() => {
  const config = useRuntimeConfig()
  const gaId = config.public.gaId

  console.log('[Analytics] Plugin loaded, gaId:', gaId)

  // Return early if gaId is empty or falsy
  if (!gaId) {
    console.log('[Analytics] No GA ID, skipping')
    return
  }

  // Inject Google Analytics scripts into <head>
  useHead({
    script: [
      {
        src: `https://www.googletagmanager.com/gtag/js?id=${gaId}`,
        async: true
      },
      {
        innerHTML: `
          window.dataLayer = window.dataLayer || [];
          function gtag(){dataLayer.push(arguments);}
          gtag('js', new Date());
          gtag('config', '${gaId}', { send_page_view: false });
        `
      }
    ]
  })

  // Track page views on route changes
  const router = useRouter()
  router.afterEach((to) => {
    // Guard to check if gtag is a function
    if (typeof window.gtag === 'function') {
      window.gtag('event', 'page_view', {
        page_path: to.fullPath,
        page_title: document.title,
        send_to: gaId
      })
    }
  })
})
