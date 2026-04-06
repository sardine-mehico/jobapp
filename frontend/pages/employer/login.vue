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
        <div class="admin-login-aside">
          <div class="text-sm font-semibold uppercase tracking-[0.28em] text-blue-100">Employer Portal</div>
          <h1 class="mt-4 text-4xl font-semibold tracking-tight text-white">Manage hiring with less friction.</h1>
          <p>Keep job ads, applicant reviews, and employer notes together in a cleaner workspace designed for quick decisions.</p>

          <ul class="admin-login-list">
            <li>Track read and unread applications at a glance.</li>
            <li>Edit employer-side job and applicant records from one place.</li>
            <li>Review rankings, links, and job activity in a consistent blue UI.</li>
          </ul>
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
