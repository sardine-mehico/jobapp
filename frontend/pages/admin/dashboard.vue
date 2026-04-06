<script setup lang="ts">
definePageMeta({ middleware: 'auth' })

const api = useApi()
const { logout, fetchUser } = useAuth()
const sidebarOpen = ref(false)
const jobs = ref<any[]>([])

const totalJobs = computed(() => jobs.value.length)
const activeJobs = computed(() => jobs.value.filter((job) => job.is_active).length)
const totalApplications = computed(() => jobs.value.reduce((sum, job) => sum + Number(job.applications_count || 0), 0))
const totalLinks = computed(() => jobs.value.reduce((sum, job) => sum + (job.tracking_links?.length || 0), 0))

async function loadJobs() {
  jobs.value = await api('/jobs')
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
            <div class="admin-eyebrow">Overview</div>
            <h1 class="admin-title">Employer dashboard</h1>
            <p class="admin-subtitle">Track live roles, candidate volume, and shareable job links from a clearer blue workspace.</p>
          </div>
          <div class="admin-toolbar">
            <button class="btn-admin-outline lg:hidden" @click="sidebarOpen = true">Menu</button>
          </div>
        </section>

        <section class="admin-stat-grid">
          <article class="admin-stat-card">
            <div class="admin-stat-label">Total Jobs</div>
            <div class="admin-stat-value">{{ totalJobs }}</div>
            <p class="admin-stat-meta">All roles currently stored in the system.</p>
          </article>
          <article class="admin-stat-card">
            <div class="admin-stat-label">Active Jobs</div>
            <div class="admin-stat-value">{{ activeJobs }}</div>
            <p class="admin-stat-meta">Jobs still visible and ready to accept applications.</p>
          </article>
          <article class="admin-stat-card">
            <div class="admin-stat-label">Applications</div>
            <div class="admin-stat-value">{{ totalApplications }}</div>
            <p class="admin-stat-meta">Combined applicant volume across every job.</p>
          </article>
          <article class="admin-stat-card">
            <div class="admin-stat-label">Tracked Links</div>
            <div class="admin-stat-value">{{ totalLinks }}</div>
            <p class="admin-stat-meta">Trackable links available for external posts and ads.</p>
          </article>
        </section>

        <section class="card space-y-4">
          <div class="admin-panel-header">
            <div>
              <h2 class="admin-panel-title">Hiring snapshot</h2>
              <p class="admin-panel-subtitle">Use this table to compare job activity and focus on the roles that are moving.</p>
            </div>
          </div>

          <div class="table-wrap">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
              <thead class="text-left">
                <tr>
                  <th class="px-4 py-3 font-medium">Job</th>
                  <th class="px-4 py-3 font-medium">Primary Link</th>
                  <th class="px-4 py-3 font-medium">Applications</th>
                  <th class="px-4 py-3 font-medium">Ideal (1)</th>
                  <th class="px-4 py-3 font-medium">Desirable (2)</th>
                  <th class="px-4 py-3 font-medium">Acceptable (3)</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 bg-white">
                <tr v-for="job in jobs" :key="job.id" class="hover:bg-blue-50/60">
                  <td class="px-4 py-4">
                    <div class="flex flex-col gap-2">
                      <span class="font-semibold text-slate-900">{{ job.job_id }}</span>
                      <span :class="['admin-badge', job.is_active ? 'admin-badge--success' : 'admin-badge--neutral']">
                        {{ job.is_active ? 'Active' : 'Inactive' }}
                      </span>
                    </div>
                  </td>
                  <td class="px-4 py-4">
                    <a
                      v-if="job.tracking_links?.length"
                      :href="job.tracking_links[0].url"
                      class="admin-link break-all"
                      target="_blank"
                    >
                      {{ job.tracking_links[0].url }}
                    </a>
                    <span v-else class="text-slate-400">No links</span>
                  </td>
                  <td class="px-4 py-4 font-medium text-slate-900">{{ job.applications_count }}</td>
                  <td class="px-4 py-4"><span class="admin-badge admin-badge--success">{{ job.ranking_counts['1'] }}</span></td>
                  <td class="px-4 py-4"><span class="admin-badge admin-badge--primary">{{ job.ranking_counts['2'] }}</span></td>
                  <td class="px-4 py-4"><span class="admin-badge admin-badge--neutral">{{ job.ranking_counts['3'] }}</span></td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </div>
    </main>
  </div>
</template>
