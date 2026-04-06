<script setup lang="ts">
definePageMeta({ middleware: 'auth' })

const api = useApi()
const { logout, fetchUser } = useAuth()
const sidebarOpen = ref(false)
const form = reactive({
  advertisement: '<p></p>',
  contact_email: 'agibson1974@gmail.com',
  is_active: true
})
const loading = ref(false)
const errors = ref<Record<string, string[]>>({})

async function submit() {
  loading.value = true
  errors.value = {}

  try {
    await api<any>('/jobs', {
      method: 'POST',
      body: form
    })

    await navigateTo('/admin/jobs')
  } catch (error: any) {
    errors.value = error?.data?.errors || {}
  } finally {
    loading.value = false
  }
}

async function signOut() {
  await logout()
  await navigateTo('/employer/login')
}

onMounted(fetchUser)
</script>

<template>
  <div class="page-shell employer-ui">
    <AdminSidebar :open="sidebarOpen" @close="sidebarOpen = false" @logout="signOut" />

    <main class="admin-main lg:ml-72">
      <div class="admin-content space-y-6">
        <section class="admin-hero">
          <div>
            <NuxtLink class="admin-link" to="/admin/jobs">Back to Jobs</NuxtLink>
            <div class="admin-eyebrow mt-4">Create Job</div>
            <h1 class="admin-title">Launch a new role</h1>
            <p class="admin-subtitle">Write the advertisement, choose whether the role is live, and set the contact email employers should monitor.</p>
          </div>
          <div class="admin-toolbar">
            <button class="btn-admin-outline lg:hidden" @click="sidebarOpen = true">Menu</button>
          </div>
        </section>

        <form class="grid gap-6 xl:grid-cols-[minmax(0,1.7fr)_minmax(320px,1fr)]" @submit.prevent="submit">
          <div class="card space-y-4">
            <div class="admin-panel-header">
              <div>
                <h2 class="admin-panel-title">Job advertisement</h2>
                <p class="admin-panel-subtitle">Use the editor to create a polished job listing that is easy to share and review later.</p>
              </div>
            </div>
            <RichTextEditor v-model="form.advertisement" placeholder="Write the job advertisement..." />
            <p v-if="errors.advertisement?.[0]" class="text-sm text-red-600">{{ errors.advertisement[0] }}</p>
          </div>

          <div class="space-y-6">
            <div class="card space-y-3">
              <div class="admin-panel-header">
                <div>
                  <h2 class="admin-panel-title">Job settings</h2>
                  <p class="admin-panel-subtitle">These details control who receives responses and whether the role is immediately visible.</p>
                </div>
              </div>

              <div>
                <label class="label">Contact Email</label>
                <input v-model="form.contact_email" class="input" type="email" />
                <p v-if="errors.contact_email?.[0]" class="mt-1 text-sm text-red-600">{{ errors.contact_email[0] }}</p>
              </div>

              <div>
                <label class="label">Active</label>
                <div class="binary-grid">
                  <label class="binary-option">
                    <input v-model="form.is_active" :value="true" type="radio" />
                    Yes
                  </label>
                  <label class="binary-option">
                    <input v-model="form.is_active" :value="false" type="radio" />
                    No
                  </label>
                </div>
              </div>
            </div>

            <div class="admin-note">
              <strong>Tip:</strong> active jobs can be shared as soon as you generate their tracking links, so this page is a good place to set the posting tone before publishing.
            </div>

            <div class="flex flex-wrap gap-3">
              <button :disabled="loading" class="btn-admin-primary" type="submit">{{ loading ? 'Saving...' : 'Create Job' }}</button>
              <NuxtLink class="btn-admin-outline" to="/admin/jobs">Cancel</NuxtLink>
            </div>
          </div>
        </form>
      </div>
    </main>
  </div>
</template>
