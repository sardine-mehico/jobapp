<script setup lang="ts">
definePageMeta({ middleware: 'auth' })

const api = useApi()
const { logout, fetchUser } = useAuth()
const sidebarOpen = ref(false)
const jobs = ref<any[]>([])
const search = ref('')

const activeJobs = computed(() => jobs.value.filter((job) => job.is_active).length)
const inactiveJobs = computed(() => jobs.value.filter((job) => !job.is_active).length)
const totalApplications = computed(() => jobs.value.reduce((sum, job) => sum + Number(job.applications_count || 0), 0))

function formatDateTime(value: string | null | undefined) {
  if (!value) {
    return '-'
  }

  return new Date(value).toLocaleString()
}

async function loadJobs() {
  jobs.value = await api('/jobs', {
    query: {
      search: search.value || undefined,
    }
  })
}

async function clearSearch() {
  search.value = ''
  await loadJobs()
}

async function signOut() {
  await logout()
  await navigateTo('/employer/login')
}

onMounted(async () => {
  await fetchUser()
  await loadJobs()
})
</script>

<template>
  <div class="page-shell employer-ui">
    <AdminSidebar :open="sidebarOpen" @close="sidebarOpen = false" @logout="signOut" />

    <main class="admin-main md:ml-72">
      <div class="admin-content space-y-6">

        <section class="card">
          <div class="admin-panel-header">
            <div>
              <h2 class="admin-panel-title">Search</h2>
            </div>
          </div>
          <div class="mt-5 flex flex-wrap gap-3 items-center">
            <input v-model="search" class="input !w-64" placeholder="Search jobs" @keyup.enter="loadJobs" />
            <button class="btn-admin-primary" @click="loadJobs">Search</button>
            <button class="btn-admin-outline" @click="clearSearch">Clear</button>
            <NuxtLink class="btn-admin-primary ml-auto" to="/admin/jobs/create">New Job</NuxtLink>
            <button class="btn-admin-outline md:!hidden" @click="sidebarOpen = true">Menu</button>
          </div>
        </section>

        <section class="card space-y-4">
          <div class="admin-panel-header">
            <div>
              <h2 class="admin-panel-title">All jobs</h2>
            </div>
          </div>

          <div class="table-wrap">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
              <thead class="text-left">
                <tr>
                  <th class="px-4 py-3 font-medium">Job</th>
                  <th class="px-4 py-3 font-medium">Created</th>
                  <th class="px-4 py-3 font-medium">Advertisement</th>
                  <th class="px-4 py-3 font-medium">Applications</th>
                  <th class="px-4 py-3 font-medium">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 bg-white">
                <tr v-for="job in jobs" :key="job.id" :class="job.is_active ? 'hover:bg-blue-50/60' : 'bg-slate-50 text-slate-500 hover:bg-blue-50/30'">
                  <td class="px-4 py-3">
                    <div class="flex flex-col gap-2">
                      <span :class="['font-semibold', job.is_active ? 'text-slate-900' : 'text-slate-500']">{{ job.job_id }}</span>
                      <span :class="['admin-badge', job.is_active ? 'admin-badge--success' : 'admin-badge--neutral']">
                        {{ job.is_active ? 'Active' : 'Inactive' }}
                      </span>
                    </div>
                  </td>
                  <td class="px-4 py-3">{{ formatDateTime(job.created_at) }}</td>
                  <td class="px-4 py-3 text-slate-600" v-html="job.advertisement.slice(0, 200) + (job.advertisement.length > 200 ? '...' : '')" />
                  <td class="px-4 py-3 font-medium text-slate-900">{{ job.applications_count }}</td>
                  <td class="px-4 py-3">
                    <div class="flex gap-2">
                      <NuxtLink :to="`/admin/jobs/${job.id}`" class="btn-admin-outline">Edit</NuxtLink>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </div>
    </main>
  </div>
</template>
