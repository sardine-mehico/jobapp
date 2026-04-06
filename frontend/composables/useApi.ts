export function useApi() {
  const config = useRuntimeConfig()

  return async function api<T>(path: string, options: Record<string, any> = {}): Promise<T> {
    const headers = new Headers(options.headers || {})

    if (process.client) {
      const token = localStorage.getItem('jobapp_token')

      if (token) {
        headers.set('Authorization', `Bearer ${token}`)
      }
    }

    return await $fetch<T>(path, {
      baseURL: config.public.apiBase,
      ...options,
      headers
    })
  }
}
