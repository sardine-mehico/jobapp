<script setup lang="ts">
definePageMeta({ middleware: 'auth' })

const route = useRoute()
const api = useApi()
const { logout, fetchUser } = useAuth()

const loading = ref(true)
const saving = ref(false)
const deleting = ref(false)
const linkLoading = ref(false)
const sidebarOpen = ref(false)
const job = ref<any | null>(null)
const form = reactive({
  advertisement: '<p></p>',
  contact_email: '',
  is_active: true
})
const errors = ref<Record<string, string[]>>({})

async function loadJob() {
  loading.value = true
  try {
    const data = await api<any>(`/jobs/${route.params.id}`)
    data.tracking_links = [...(data.tracking_links || [])].sort((a: any, b: any) => {
      return new Date(a.created_at).getTime() - new Date(b.created_at).getTime()
    })
    job.value = data
    form.advertisement = data.advertisement
    form.contact_email = data.contact_email
    form.is_active = data.is_active
  } finally {
    loading.value = false
  }
}

async function saveJob() {
  saving.value = true
  errors.value = {}

  try {
    job.value = await api<any>(`/jobs/${route.params.id}`, {
      method: 'PUT',
      body: form
    })

    await navigateTo('/admin/jobs', { replace: true })
  } catch (error: any) {
    errors.value = error?.data?.errors || {}
  } finally {
    saving.value = false
  }
}

async function deleteJob() {
  if (!job.value) {
    return
  }

  const confirmed = window.confirm(`Delete job ${job.value.job_id}? This cannot be undone.`)

  if (!confirmed) {
    return
  }

  deleting.value = true

  try {
    await api(`/jobs/${route.params.id}`, { method: 'DELETE' })
    await navigateTo('/admin/jobs', { replace: true })
  } finally {
    deleting.value = false
  }
}

async function generateLink() {
  linkLoading.value = true
  try {
    await api(`/jobs/${route.params.id}/tracking-links`, { method: 'POST' })
    await loadJob()
  } finally {
    linkLoading.value = false
  }
}

async function updateLink(link: any) {
  await api(`/tracking-links/${link.id}`, {
    method: 'PUT',
    body: {
      label: link.label || null,
      external_post_url: link.external_post_url || null
    }
  })
}

async function deleteLink(id: string) {
  await api(`/tracking-links/${id}`, { method: 'DELETE' })
  await loadJob()
}

function copyLink(url: string) {
  navigator.clipboard.writeText(url)
}

async function signOut() {
  await logout()
  await navigateTo('/employer/login')
}

onMounted(async () => {
  await fetchUser()
  await loadJob()
})
</script>

<template>
  <div class="page-shell employer-ui">
    <AdminSidebar :open="sidebarOpen" @close="sidebarOpen = false" @logout="signOut" />

    <main class="admin-main lg:ml-72">
      <div class="admin-content space-y-6">
        <section class="admin-hero">
          <div>
            <NuxtLink class="admin-link" to="/admin/jobs">Back to Jobs</NuxtLink>
            <div class="admin-eyebrow mt-4">Edit Job</div>
            <h1 class="admin-title">{{ job?.job_id || 'Loading job' }}</h1>
            <p class="admin-subtitle">Refine the posting, update contact details, and manage every tracking link from one place.</p>
          </div>
          <div class="admin-toolbar">
            <button class="btn-admin-outline lg:hidden" @click="sidebarOpen = true">Menu</button>
            <span v-if="job" :class="['admin-badge', form.is_active ? 'admin-badge--success' : 'admin-badge--neutral']">
              {{ form.is_active ? 'Active' : 'Inactive' }}
            </span>
          </div>
        </section>

        <div v-if="loading" class="card text-slate-500">Loading job...</div>

        <template v-else-if="job">
          <div class="grid gap-6 xl:grid-cols-[minmax(0,1.7fr)_minmax(320px,1fr)]">
            <div class="space-y-6">
              <div class="card space-y-4">
                <div class="admin-panel-header">
                  <div>
                    <h2 class="admin-panel-title">Job advertisement</h2>
                    <p class="admin-panel-subtitle">Keep the posting current so linked ads stay accurate everywhere the role is shared.</p>
                  </div>
                </div>
                <RichTextEditor v-model="form.advertisement" placeholder="Write the job advertisement..." />
                <p v-if="errors.advertisement?.[0]" class="text-sm text-red-600">{{ errors.advertisement[0] }}</p>
              </div>

              <div class="card space-y-4">
                <div class="admin-panel-header">
                  <div>
                    <h2 class="admin-panel-title">Job advertisement links</h2>
                    <p class="admin-panel-subtitle">Generate, label, and monitor every shareable tracking link in one list.</p>
                  </div>
                  <button :disabled="linkLoading" class="btn-admin-primary" type="button" @click="generateLink">
                    {{ linkLoading ? 'Generating...' : 'Generate New Link' }}
                  </button>
                </div>

                <div v-if="!job.tracking_links.length" class="admin-empty text-sm">
                  No tracking links yet.
                </div>

                <div v-for="link in job.tracking_links" :key="link.id" class="rounded-md border border-slate-200 bg-slate-50/70 p-4">
                  <div class="flex flex-col gap-3 lg:flex-row lg:items-start">
                    <div class="flex-1 space-y-3">
                      <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <a :href="link.url" class="admin-link break-all" target="_blank">{{ link.url }}</a>
                        <div class="flex flex-wrap gap-2">
                          <span class="admin-badge admin-badge--neutral">Visits {{ link.visit_count }}</span>
                          <button class="btn-admin-outline" type="button" @click="copyLink(link.url)">Copy</button>
                        </div>
                      </div>

                      <input v-model="link.label" class="input" placeholder="Label" @blur="updateLink(link)" />
                      <input v-model="link.external_post_url" class="input" placeholder="External Post URL" @blur="updateLink(link)" />
                    </div>

                    <button class="btn-admin-outline" type="button" @click="deleteLink(link.id)">Delete</button>
                  </div>
                </div>
              </div>
            </div>

            <div class="space-y-6">
              <div class="card space-y-4">
                <div class="admin-panel-header">
                  <div>
                    <h2 class="admin-panel-title">Job settings</h2>
                    <p class="admin-panel-subtitle">Control where responses go and whether applicants can still access the posting.</p>
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
                  <p v-if="errors.is_active?.[0]" class="mt-2 text-sm text-red-600">{{ errors.is_active[0] }}</p>
                </div>
              </div>

              <div class="admin-note">
                <strong>Quick reminder:</strong> generating a new link is useful when you want to track different job boards or campaigns separately without creating another role.
              </div>

              <div class="flex flex-wrap items-center gap-3">
                <button :disabled="saving" class="btn-admin-primary" type="button" @click="saveJob">{{ saving ? 'Saving...' : 'Save Job' }}</button>
                <button :disabled="deleting" class="btn-admin-outline" type="button" @click="deleteJob">{{ deleting ? 'Deleting...' : 'Delete Job' }}</button>
              </div>
            </div>
          </div>
        </template>
      </div>
    </main>
  </div>
</template>
