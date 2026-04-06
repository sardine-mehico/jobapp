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
  return isRead(application) ? 'bg-white text-slate-500' : 'bg-white text-slate-900'
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

async function toggleFlag(event: Event, application: any) {
  event.stopPropagation()
  application.is_flagged = !application.is_flagged
  try {
    await api(`/applications/${application.id}`, {
      method: 'PATCH',
      body: { is_flagged: application.is_flagged }
    })
  } catch (error) {
    application.is_flagged = !application.is_flagged
  }
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


        <section class="card">
          <div class="admin-panel-header">
            <div>
              <h2 class="admin-panel-title">Search applications</h2>
            </div>
          </div>
          <div class="mt-5 flex flex-wrap gap-3 items-center">
            <input v-model="search" class="input !w-64" placeholder="Search applications" @keyup.enter="loadData()" />
            <button class="btn-admin-primary" @click="loadData()">Search</button>
            <button class="btn-admin-outline" @click="filtersOpen = !filtersOpen">{{ filtersOpen ? 'Hide Filters' : 'Filters' }}</button>
            <button class="btn-admin-outline md:!hidden ml-auto" @click="sidebarOpen = true">Menu</button>
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
            </div>
          </div>

          <div class="table-wrap overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
              <thead class="text-left text-xs tracking-wide text-slate-500">
                <tr>
                  <th class="px-4 py-3 font-medium w-12 text-center">Flag</th>
                  <th class="px-4 py-3 font-medium">
                    <button class="flex items-center gap-2" @click="toggleSort('submitted_at')">
                      <span>Date submitted</span>
                      <span class="font-mono text-xs text-slate-400">{{ sortIcon('submitted_at') }}</span>
                    </button>
                  </th>
                  <th class="px-4 py-3 font-medium">
                    <button class="flex items-center gap-2" @click="toggleSort('name')">
                      <span>Name</span>
                      <span class="font-mono text-xs text-slate-400">{{ sortIcon('name') }}</span>
                    </button>
                  </th>
                  <th class="px-4 py-3 font-medium">Contact no</th>
                  <th class="px-4 py-3 font-medium">Status</th>
                  <th class="px-4 py-3 font-medium">
                    <button class="flex items-center gap-2" @click="toggleSort('suburb')">
                      <span>Suburb</span>
                      <span class="font-mono text-xs text-slate-400">{{ sortIcon('suburb') }}</span>
                    </button>
                  </th>
                  <th class="px-4 py-3 font-medium">Job id</th>
                  <th class="px-4 py-3 font-medium">
                    <button class="flex items-center gap-2" @click="toggleSort('employer_ranking')">
                      <span>Employer ranking</span>
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
                  <td class="px-4 py-3 text-center">
                    <button type="button" @click="toggleFlag($event, application)" class="focus:outline-none transition-transform hover:scale-110" title="Toggle flag">
                      <svg v-if="application.is_flagged" class="w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M5 3v18h2V14h7.52l1.6 3H21V6h-7.52l-1.6-3H5z"/>
                      </svg>
                      <svg v-else class="w-5 h-5 text-slate-300 hover:text-emerald-300" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12.36 6l.4 2H18v6h-3.36l-.4-2H7V6h5.36M14 4H5v17h2v-7h5.6l.4 2h7V6h-5.6L14 4z"/>
                      </svg>
                    </button>
                  </td>
                  <td class="px-4 py-3">{{ new Date(application.submitted_at).toLocaleString() }}</td>
                  <td :class="['px-4 py-3', isRead(application) ? 'font-medium' : 'font-semibold']">{{ application.name }}</td>
                  <td class="px-4 py-3">{{ application.contact_no || '-' }}</td>
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
