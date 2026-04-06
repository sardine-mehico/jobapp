export function useAuth() {
  const api = useApi()
  const user = useState<any | null>('auth_user', () => null)
  const token = useState<string | null>('auth_token', () => process.client ? localStorage.getItem('jobapp_token') : null)

  async function login(email: string, password: string) {
    const response = await api<{ token: string; user: any }>('/auth/login', {
      method: 'POST',
      body: { email, password }
    })

    token.value = response.token
    user.value = response.user
    localStorage.setItem('jobapp_token', response.token)

    return response
  }

  async function fetchUser() {
    if (!token.value) {
      return null
    }

    try {
      user.value = await api('/auth/user')
      return user.value
    } catch {
      logout(false)
      return null
    }
  }

  async function logout(shouldCallApi = true) {
    if (shouldCallApi && token.value) {
      try {
        await api('/auth/logout', { method: 'POST' })
      } catch {
        // Ignore logout API failures and clear client state anyway.
      }
    }

    token.value = null
    user.value = null

    if (process.client) {
      localStorage.removeItem('jobapp_token')
    }
  }

  return {
    user,
    token,
    login,
    fetchUser,
    logout
  }
}
