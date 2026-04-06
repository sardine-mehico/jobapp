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
const showDeleteModal = ref(false)
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

function promptDeleteJob() {
  if (!job.value) return
  showDeleteModal.value = true
}

async function confirmDeleteJob() {
  showDeleteModal.value = false
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

    <main class="admin-main md:ml-72">
      <div class="admin-content space-y-6">
        <div class="flex items-center justify-between">
          <NuxtLink class="btn-admin-outline" to="/admin/jobs">Back</NuxtLink>
          <div class="flex items-center gap-3">
            <span v-if="job" :class="['admin-badge', form.is_active ? 'admin-badge--success' : 'admin-badge--neutral']">
              {{ form.is_active ? 'Active' : 'Inactive' }}
            </span>
            <button class="btn-admin-outline md:!hidden" @click="sidebarOpen = true">Menu</button>
          </div>
        </div>

        <div v-if="loading" class="card text-slate-500">Loading job...</div>

        <template v-else-if="job">
          <div class="grid gap-6 xl:grid-cols-[minmax(0,1.7fr)_minmax(320px,1fr)]">
            <div class="space-y-6">
              <div class="card space-y-4">
                <div class="admin-panel-header">
                  <div>
                    <h2 class="admin-panel-title">Job advertisement</h2>
                  </div>
                </div>
                <RichTextEditor v-model="form.advertisement" placeholder="Write the job advertisement..." />
                <p v-if="errors.advertisement?.[0]" class="text-sm text-red-600">{{ errors.advertisement[0] }}</p>
              </div>

              <div class="card space-y-4">
                <div class="admin-panel-header">
                  <div>
                    <h2 class="admin-panel-title">Job advertisement links</h2>
                    <p class="admin-panel-subtitle">Generate a new link for each job board (Seek, Facebook, Gumtree) you post to.</p>
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
                    <p class="admin-panel-subtitle">This is the email Applicants will see after they have filled the form</p>
                  </div>
                </div>

                <div>
                  <label class="label">Contact Email</label>
                  <input v-model="form.contact_email" class="input" type="email" />
                  <p v-if="errors.contact_email?.[0]" class="mt-1 text-sm text-red-600">{{ errors.contact_email[0] }}</p>
                </div>

                <div>
                  <label class="label">Job Active?</label>
                  <label class="relative inline-flex items-center cursor-pointer mt-2">
                    <input type="checkbox" v-model="form.is_active" class="sr-only peer">
                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    <span class="ml-3 text-sm font-medium text-slate-700">{{ form.is_active ? 'Yes' : 'No' }}</span>
                  </label>
                  <p v-if="errors.is_active?.[0]" class="mt-2 text-sm text-red-600">{{ errors.is_active[0] }}</p>
                </div>
              </div>



              <div class="flex flex-wrap items-center gap-3">
                <button :disabled="saving" class="btn-admin-primary" type="button" @click="saveJob">{{ saving ? 'Saving...' : 'Save Job' }}</button>
                <button :disabled="deleting" class="btn-admin-outline !border-red-200 !text-red-600 hover:!bg-red-50 hover:!border-red-300 ml-auto" type="button" @click="promptDeleteJob">{{ deleting ? 'Deleting...' : 'Delete Job' }}</button>
              </div>
            </div>
          </div>
        </template>
      </div>
    </main>

    <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 backdrop-blur-sm p-4">
      <div class="bg-white rounded-xl shadow-xl max-w-sm w-full p-6 text-center">
        <h3 class="text-xl font-bold text-slate-900 mb-2">Delete Job</h3>
        <p class="text-sm text-slate-600 mb-6">Are you sure you want to delete this job? This action cannot be undone and will permanently erase this record.</p>
        <div class="flex gap-3 justify-center">
          <button class="btn-admin-outline flex-1" type="button" @click="showDeleteModal = false">Cancel</button>
          <button class="btn-admin-primary !bg-red-600 hover:!bg-red-700 !border-red-600 flex-1" type="button" @click="confirmDeleteJob">Yes, Delete</button>
        </div>
      </div>
    </div>
  </div>
</template>
