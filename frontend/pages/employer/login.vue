<script setup lang="ts">
const { login } = useAuth()

const form = reactive({
  email: '',
  password: ''
})

const errorMessage = ref('')
const loading = ref(false)

async function submit() {
  loading.value = true
  errorMessage.value = ''

  try {
    await login(form.email, form.password)
    await navigateTo('/admin/dashboard')
  } catch (error: any) {
    errorMessage.value = error?.data?.errors?.email?.[0] || 'Unable to sign in.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="page-shell employer-ui flex items-center justify-center px-4 py-8 sm:py-12">
    <div class="admin-login-shell">
      <div class="admin-login-card">
        <div class="admin-login-aside flex items-center justify-center">
          <h1 class="text-4xl font-semibold tracking-tight text-white text-center">Employer Portal</h1>
        </div>

        <div class="admin-login-form">
          <div class="admin-eyebrow">Sign in</div>
          <h2 class="mt-3 text-3xl font-semibold text-slate-900">Welcome back</h2>
          <p class="mt-2 text-slate-500">Use your employer account to manage jobs and review applications.</p>

          <form class="mt-8 space-y-4" @submit.prevent="submit">
            <div>
              <label class="label">Email</label>
              <input v-model="form.email" class="input" type="email" />
            </div>

            <div>
              <label class="label">Password</label>
              <input v-model="form.password" class="input" type="password" />
            </div>

            <p v-if="errorMessage" class="text-sm text-red-600">{{ errorMessage }}</p>

            <button :disabled="loading" class="btn-primary w-full" type="submit">{{ loading ? 'Signing in...' : 'Login' }}</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>
