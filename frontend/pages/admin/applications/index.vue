<script setup lang="ts">
definePageMeta({ middleware: 'auth' })

const api = useApi()
const { logout, fetchUser } = useAuth()
const sidebarOpen = ref(false)
const filtersOpen = ref(false)
const applications = ref<any[]>([])
const jobs = ref<any[]>([])
const meta = ref<any>({})
const loading = ref(true)
const search = ref('')
const sort = reactive({ field: 'submitted_at', direction: 'desc' })
const filters = reactive({
  suburb: '',
  date_from: '',
  date_to: '',
  job_id: '',
  ranking: ''
})

const visibleUnreadCount = computed(() => applications.value.filter((application) => !isRead(application)).length)
const visibleReadCount = computed(() => applications.value.filter((application) => isRead(application)).length)
const flaggedCount = computed(() => applications.value.filter((application) => application.criminal_conviction || application.workers_comp).length)
const totalApplications = computed(() => Number(meta.value.total || applications.value.length || 0))

function isRead(application: any) {
  return application.employer_ranking !== null && application.employer_ranking !== undefined
}

function statusLabel(application: any) {
  return isRead(application) ? 'Read' : 'Unread'
}

function statusClass(application: any) {
  return isRead(application) ? 'admin-badge admin-badge--neutral' : 'admin-badge admin-badge--primary'
}

function rowClass(application: any) {
  if (isRead(application)) {
    if (application.criminal_conviction || application.workers_comp) return 'bg-red-50 text-slate-600'
    if (!application.reliable_transport || !application.driving_licence) return 'bg-amber-50 text-slate-600'
    return 'bg-slate-50 text-slate-600'
  }

  if (application.criminal_conviction || application.workers_comp) return 'bg-red-100 text-slate-900'
  if (!application.reliable_transport || !application.driving_licence) return 'bg-yellow-100 text-slate-900'
  return 'bg-white text-slate-900'
}

async function loadData(page = 1) {
  loading.value = true
  try {
    const [applicationData, jobsData] = await Promise.all([
      api<any>('/applications', {
        query: {
          page,
          search: search.value || undefined,
          sort: sort.field,
          direction: sort.direction,
          ...filters
        }
      }),
      api<any[]>('/jobs')
    ])

    applications.value = applicationData.data
    meta.value = applicationData
    jobs.value = jobsData
  } finally {
    loading.value = false
  }
}

async function clearSearch() {
  search.value = ''
  await loadData()
}

function toggleSort(field: string) {
  if (sort.field === field) {
    sort.direction = sort.direction === 'asc' ? 'desc' : 'asc'
  } else {
    sort.field = field
    sort.direction = 'asc'
  }

  loadData()
}

function sortIcon(field: string) {
  if (sort.field !== field) {
    return '<>'
  }

  return sort.direction === 'asc' ? '^' : 'v'
}

function resetFilters() {
  search.value = ''
  filters.suburb = ''
  filters.date_from = ''
  filters.date_to = ''
  filters.job_id = ''
  filters.ranking = ''
  loadData()
}

async function signOut() {
  await logout()
  await navigateTo('/employer/login')
}

async function openApplication(applicationId: string) {
  await navigateTo(`/admin/applications/${applicationId}`)
}

onMounted(async () => {
  await fetchUser()
  await loadData()
})
</script>

<template>
  <div class="page-shell employer-ui">
    <AdminSidebar :open="sidebarOpen" @close="sidebarOpen = false" @logout="signOut" />

    <main class="admin-main lg:ml-72">
      <div class="admin-content space-y-6">
        <section class="admin-hero">
          <div>
            <div class="admin-eyebrow">Applications</div>
            <h1 class="admin-title">Candidate review queue</h1>
            <p class="admin-subtitle">Unread applications stay prominent until you give them a ranking, making the list feel closer to an email inbox than a spreadsheet.</p>
          </div>
          <div class="admin-toolbar">
            <button class="btn-admin-outline" @click="filtersOpen = !filtersOpen">{{ filtersOpen ? 'Hide Filters' : 'Filters' }}</button>
            <button class="btn-admin-outline lg:hidden" @click="sidebarOpen = true">Menu</button>
          </div>
        </section>

        <section class="admin-stat-grid">
          <article class="admin-stat-card">
            <div class="admin-stat-label">Results</div>
            <div class="admin-stat-value">{{ totalApplications }}</div>
            <p class="admin-stat-meta">Applications matching your current search and filters.</p>
          </article>
          <article class="admin-stat-card">
            <div class="admin-stat-label">Unread</div>
            <div class="admin-stat-value">{{ visibleUnreadCount }}</div>
            <p class="admin-stat-meta">Visible applications still waiting on an employer ranking.</p>
          </article>
          <article class="admin-stat-card">
            <div class="admin-stat-label">Read</div>
            <div class="admin-stat-value">{{ visibleReadCount }}</div>
            <p class="admin-stat-meta">Visible applications already reviewed and ranked.</p>
          </article>
          <article class="admin-stat-card">
            <div class="admin-stat-label">Flagged</div>
            <div class="admin-stat-value">{{ flaggedCount }}</div>
            <p class="admin-stat-meta">Visible applications with higher-risk compensation or conviction flags.</p>
          </article>
        </section>

        <section class="card">
          <div class="admin-panel-header">
            <div>
              <h2 class="admin-panel-title">Search applications</h2>
              <p class="admin-panel-subtitle">Search names, suburbs, visa details, notes, or ranking values to narrow the queue quickly.</p>
            </div>
          </div>
          <div class="mt-5 flex flex-col gap-3 sm:flex-row">
            <input v-model="search" class="input" placeholder="Search applications" @keyup.enter="loadData()" />
            <button class="btn-admin-primary sm:self-start" @click="loadData()">Search</button>
            <button class="btn-admin-outline sm:self-start" @click="clearSearch">Clear</button>
          </div>
        </section>

        <section v-if="filtersOpen" class="card">
          <div class="admin-panel-header">
            <div>
              <h2 class="admin-panel-title">Filters</h2>
              <p class="admin-panel-subtitle">Use filters when you want to focus on one suburb, date range, job, or ranking band.</p>
            </div>
          </div>
          <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <input v-model="filters.suburb" class="input" placeholder="Suburb" />
            <input v-model="filters.date_from" class="input" type="date" />
            <input v-model="filters.date_to" class="input" type="date" />
            <select v-model="filters.job_id" class="input">
              <option value="">All Jobs</option>
              <option v-for="job in jobs" :key="job.id" :value="job.id">{{ job.job_id }}</option>
            </select>
            <select v-model="filters.ranking" class="input">
              <option value="">All Rankings</option>
              <option v-for="rank in [1, 2, 3, 4, 5, 6]" :key="rank" :value="String(rank)">{{ rank }}</option>
            </select>
            <div class="flex gap-3 md:col-span-2 xl:col-span-5">
              <button class="btn-admin-primary" @click="loadData()">Apply Filters</button>
              <button class="btn-admin-outline" @click="resetFilters">Reset</button>
            </div>
          </div>
        </section>

        <section class="card space-y-4">
          <div class="admin-panel-header">
            <div>
              <h2 class="admin-panel-title">Application list</h2>
              <p class="admin-panel-subtitle">Unread candidates stay brighter and bolder, while ranked applications soften into the background like read email.</p>
            </div>
          </div>

          <div class="table-wrap overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
              <thead class="text-left">
                <tr>
                  <th class="px-4 py-3 font-medium">
                    <button class="flex items-center gap-2" @click="toggleSort('submitted_at')">
                      <span>Date Submitted</span>
                      <span class="font-mono text-xs text-slate-400">{{ sortIcon('submitted_at') }}</span>
                    </button>
                  </th>
                  <th class="px-4 py-3 font-medium">
                    <button class="flex items-center gap-2" @click="toggleSort('name')">
                      <span>Name</span>
                      <span class="font-mono text-xs text-slate-400">{{ sortIcon('name') }}</span>
                    </button>
                  </th>
                  <th class="px-4 py-3 font-medium">Status</th>
                  <th class="px-4 py-3 font-medium">
                    <button class="flex items-center gap-2" @click="toggleSort('suburb')">
                      <span>Suburb</span>
                      <span class="font-mono text-xs text-slate-400">{{ sortIcon('suburb') }}</span>
                    </button>
                  </th>
                  <th class="px-4 py-3 font-medium">Job ID</th>
                  <th class="px-4 py-3 font-medium">
                    <button class="flex items-center gap-2" @click="toggleSort('employer_ranking')">
                      <span>Employer Ranking</span>
                      <span class="font-mono text-xs text-slate-400">{{ sortIcon('employer_ranking') }}</span>
                    </button>
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200">
                <tr
                  v-for="application in applications"
                  :key="application.id"
                  :class="[rowClass(application), 'cursor-pointer transition-colors hover:bg-blue-50/60']"
                  @click="openApplication(application.id)"
                >
                  <td class="px-4 py-3">{{ new Date(application.submitted_at).toLocaleString() }}</td>
                  <td :class="['px-4 py-3', isRead(application) ? 'font-medium' : 'font-semibold']">{{ application.name }}</td>
                  <td class="px-4 py-3">
                    <span :class="statusClass(application)">
                      {{ statusLabel(application) }}
                    </span>
                  </td>
                  <td class="px-4 py-3">{{ application.suburb }}</td>
                  <td class="px-4 py-3">{{ application.job?.job_id }}</td>
                  <td class="px-4 py-3">{{ application.employer_ranking || '-' }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="flex items-center justify-between text-sm text-slate-500">
            <div>Page {{ meta.current_page || 1 }} of {{ meta.last_page || 1 }}</div>
            <div class="flex gap-3">
              <button :disabled="!meta.prev_page_url" class="btn-admin-outline" @click="loadData((meta.current_page || 1) - 1)">Previous</button>
              <button :disabled="!meta.next_page_url" class="btn-admin-outline" @click="loadData((meta.current_page || 1) + 1)">Next</button>
            </div>
          </div>
        </section>
      </div>
    </main>
  </div>
</template>
