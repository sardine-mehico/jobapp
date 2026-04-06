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

    <main class="admin-main lg:ml-72">
      <div class="admin-content space-y-6">
        <section class="admin-hero">
          <div>
            <div class="admin-eyebrow">Jobs</div>
            <h1 class="admin-title">Job management</h1>
            <p class="admin-subtitle">Create roles, keep posting links organized, and quickly see which jobs are live or paused.</p>
          </div>
          <div class="admin-toolbar">
            <button class="btn-admin-outline lg:hidden" @click="sidebarOpen = true">Menu</button>
            <NuxtLink class="btn-admin-primary" to="/admin/jobs/create">New Job</NuxtLink>
          </div>
        </section>

        <section class="admin-stat-grid">
          <article class="admin-stat-card">
            <div class="admin-stat-label">Visible Jobs</div>
            <div class="admin-stat-value">{{ jobs.length }}</div>
            <p class="admin-stat-meta">Results returned for the current search.</p>
          </article>
          <article class="admin-stat-card">
            <div class="admin-stat-label">Active</div>
            <div class="admin-stat-value">{{ activeJobs }}</div>
            <p class="admin-stat-meta">Jobs that applicants can still open and submit.</p>
          </article>
          <article class="admin-stat-card">
            <div class="admin-stat-label">Inactive</div>
            <div class="admin-stat-value">{{ inactiveJobs }}</div>
            <p class="admin-stat-meta">Roles currently hidden from new applicants.</p>
          </article>
          <article class="admin-stat-card">
            <div class="admin-stat-label">Applications</div>
            <div class="admin-stat-value">{{ totalApplications }}</div>
            <p class="admin-stat-meta">Combined volume across the jobs shown here.</p>
          </article>
        </section>

        <section class="card">
          <div class="admin-panel-header">
            <div>
              <h2 class="admin-panel-title">Find a job fast</h2>
              <p class="admin-panel-subtitle">Search by job ID or role content, then jump straight into editing.</p>
            </div>
          </div>
          <div class="mt-5 flex flex-col gap-3 sm:flex-row">
            <input v-model="search" class="input" placeholder="Search jobs" @keyup.enter="loadJobs" />
            <button class="btn-admin-primary sm:self-start" @click="loadJobs">Search</button>
            <button class="btn-admin-outline sm:self-start" @click="clearSearch">Clear</button>
          </div>
        </section>

        <section class="card space-y-4">
          <div class="admin-panel-header">
            <div>
              <h2 class="admin-panel-title">All jobs</h2>
              <p class="admin-panel-subtitle">Active jobs stay bold and easy to scan, while paused roles remain available without competing for attention.</p>
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
