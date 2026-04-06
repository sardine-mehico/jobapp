<script setup lang="ts">
definePageMeta({ middleware: 'auth' })

const route = useRoute()
const api = useApi()
const runtimeConfig = useRuntimeConfig()
const { logout, fetchUser } = useAuth()
const application = ref<any | null>(null)
const draft = ref<any | null>(null)
const loading = ref(true)
const exporting = ref(false)
const saving = ref(false)
const editMode = ref(false)
const saveMessage = ref('')
const saveError = ref('')
const sidebarOpen = ref(false)
let saveMessageTimeout: ReturnType<typeof setTimeout> | null = null

const visaStatusLabels: Record<string, string> = {
  student_visa: 'Student Visa',
  partner_visa: 'Partner Visa',
  tr: 'TR',
  australian_pr: 'Australian PR',
  australian_citizen: 'Australian Citizen',
  other: 'Other'
}

const booleanQuestions = [
  ['reliable_transport', 'Do you have a reliable transport?'],
  ['driving_licence', 'Do you have a valid driving licence?'],
  ['has_abn', 'Do you have an ABN?'],
  ['criminal_conviction', 'Have you been convicted of any crimes in Australia or overseas (have any pending criminal charges)?'],
  ['police_clearance', 'Do you have a current police clearance? or are you willing to apply for one?'],
  ['workers_comp', 'Have you ever claimed Workers Compensation, Accident / Sickness Insurance?']
] as const

const rankingOptions = [
  { value: 1, label: '1 - Ideal' },
  { value: 2, label: '2 - Desirable' },
  { value: 3, label: '3 - Acceptable' },
  { value: 4, label: '4 - Not suitable' },
  { value: 5, label: '5 - Avoid if Possible' },
  { value: 6, label: '6 - DO NOT HIRE' }
]

const applicationIsRead = computed(() => {
  return application.value?.employer_ranking !== null && application.value?.employer_ranking !== undefined
})

const applicationRankingLabel = computed(() => {
  const ranking = application.value?.employer_ranking
  return rankingOptions.find((option) => option.value === ranking)?.label || 'Unranked'
})

function flagClass(color: 'yellow' | 'red') {
  return color === 'yellow'
    ? 'rounded-md bg-yellow-100 px-3 py-3'
    : 'rounded-md bg-yellow-100 px-3 py-3'
}

function yesNo(value: boolean | null | undefined) {
  return value ? 'Yes' : 'No'
}

function visaStatusLabel(value: string | null | undefined) {
  return value ? visaStatusLabels[value] || value : ''
}

function cloneApplication(source: any | null) {
  if (!source) {
    return null
  }

  return {
    name: source.name || '',
    suburb: source.suburb || '',
    contact_no: source.contact_no || '',
    email: source.email || '',
    availability: source.availability || '',
    visa_status: source.visa_status || '',
    visa_other: source.visa_other || '',
    reliable_transport: source.reliable_transport,
    driving_licence: source.driving_licence,
    has_abn: source.has_abn,
    criminal_conviction: source.criminal_conviction,
    police_clearance: source.police_clearance,
    workers_comp: source.workers_comp,
    education: source.education || '',
    work_exp_1: source.work_exp_1 || '',
    work_exp_2: source.work_exp_2 || '',
    references: source.references || '',
    employer_ranking: source.employer_ranking ?? null,
    employer_notes: source.employer_notes || ''
  }
}

function normalizedDraftPayload(source: any) {
  return {
    name: source.name || '',
    suburb: source.suburb || '',
    contact_no: source.contact_no || '',
    email: (source.email || '').trim().toLowerCase(),
    availability: source.availability || '',
    visa_status: source.visa_status || '',
    visa_other: source.visa_status === 'other' ? (source.visa_other || '') : null,
    reliable_transport: source.reliable_transport,
    driving_licence: source.driving_licence,
    has_abn: source.has_abn,
    criminal_conviction: source.criminal_conviction,
    police_clearance: source.police_clearance,
    workers_comp: source.workers_comp,
    education: source.education || '',
    work_exp_1: source.work_exp_1 || '',
    work_exp_2: source.work_exp_2 || null,
    references: source.references || '',
    employer_ranking: source.employer_ranking || null,
    employer_notes: source.employer_notes || null
  }
}

function buildChangedPayload() {
  if (!application.value || !draft.value) {
    return {}
  }

  const original = normalizedDraftPayload(cloneApplication(application.value))
  const current = normalizedDraftPayload(draft.value)

  return Object.fromEntries(
    Object.entries(current).filter(([key, value]) => original[key as keyof typeof original] !== value)
  )
}

function beginEdit() {
  draft.value = cloneApplication(application.value)
  saveError.value = ''
  editMode.value = true
}

function cancelEdit() {
  draft.value = cloneApplication(application.value)
  saveError.value = ''
  editMode.value = false
}

function showSavedMessage(message: string) {
  saveMessage.value = message

  if (saveMessageTimeout) {
    clearTimeout(saveMessageTimeout)
  }

  saveMessageTimeout = setTimeout(() => {
    saveMessage.value = ''
    saveMessageTimeout = null
  }, 3000)
}

async function loadApplication() {
  loading.value = true
  try {
    application.value = await api(`/applications/${route.params.id}`)
    draft.value = cloneApplication(application.value)
  } finally {
    loading.value = false
  }
}

async function save() {
  if (!draft.value) {
    return
  }

  saving.value = true
  saveError.value = ''

  try {
    const payload = buildChangedPayload()

    if (!Object.keys(payload).length) {
      editMode.value = false
      showSavedMessage('No changes to save.')
      return
    }

    application.value = await api(`/applications/${route.params.id}`, {
      method: 'PATCH',
      body: payload
    })

    draft.value = cloneApplication(application.value)
    editMode.value = false
    showSavedMessage('Saved successfully.')
  } catch (error: any) {
    saveError.value = error?.data?.message || 'Could not save changes.'
  } finally {
    saving.value = false
  }
}

async function exportPdf() {
  if (!process.client || !application.value) {
    return
  }

  exporting.value = true

  try {
    const token = localStorage.getItem('jobapp_token')
    const response = await $fetch.raw(`/applications/${application.value.id}/export-pdf`, {
      baseURL: runtimeConfig.public.apiBase,
      responseType: 'blob',
      headers: token ? { Authorization: `Bearer ${token}` } : {}
    })

    const blob = response._data instanceof Blob ? response._data : new Blob([response._data], { type: 'application/pdf' })
    const fileName = `${application.value.name || 'Application'} Job Application.pdf`
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')

    link.href = url
    link.download = fileName
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } finally {
    exporting.value = false
  }
}

async function signOut() {
  await logout()
  await navigateTo('/employer/login')
}

onMounted(async () => {
  await fetchUser()
  await loadApplication()
})
</script>

<template>
  <div class="page-shell employer-ui">
    <AdminSidebar :open="sidebarOpen" @close="sidebarOpen = false" @logout="signOut" />

    <main class="admin-main lg:ml-72">
      <div class="admin-content space-y-6">
        <section class="admin-hero">
          <div>
            <NuxtLink class="admin-link" to="/admin/applications">Back to Applications</NuxtLink>
            <div class="admin-eyebrow mt-4">Application Detail</div>
            <h1 class="admin-title">{{ application?.name || 'Application detail' }}</h1>
            <p class="admin-subtitle">Review candidate details, apply a ranking, and make employer-side corrections without affecting the public application form.</p>
          </div>
          <div class="admin-toolbar">
            <button class="btn-admin-outline lg:hidden" @click="sidebarOpen = true">Menu</button>
            <span v-if="application" :class="['admin-badge', applicationIsRead ? 'admin-badge--neutral' : 'admin-badge--primary']">
              {{ applicationIsRead ? 'Read' : 'Unread' }}
            </span>
          </div>
        </section>

        <div v-if="loading" class="card text-slate-500">Loading application...</div>

        <template v-else-if="application">
          <section class="admin-stat-grid">
            <article class="admin-stat-card">
              <div class="admin-stat-label">Job</div>
              <div class="admin-stat-value">{{ application.job?.job_id || '-' }}</div>
              <p class="admin-stat-meta">The role this candidate applied for.</p>
            </article>
            <article class="admin-stat-card">
              <div class="admin-stat-label">Submitted</div>
              <div class="mt-3 text-lg font-semibold leading-normal text-slate-900">{{ new Date(application.submitted_at).toLocaleString() }}</div>
              <p class="admin-stat-meta">Original submission timestamp.</p>
            </article>
            <article class="admin-stat-card">
              <div class="admin-stat-label">Review State</div>
              <div class="admin-stat-value">{{ applicationIsRead ? 'Read' : 'Unread' }}</div>
              <p class="admin-stat-meta">Applications are marked read only after a ranking is given.</p>
            </article>
            <article class="admin-stat-card">
              <div class="admin-stat-label">Employer Ranking</div>
              <div class="mt-3 text-lg font-semibold leading-normal text-slate-900">{{ applicationRankingLabel }}</div>
              <p class="admin-stat-meta">Current employer assessment for this candidate.</p>
            </article>
          </section>

          <div v-if="editMode && draft" class="grid gap-6 xl:grid-cols-[minmax(0,1.65fr)_minmax(320px,1fr)]">
            <div class="card space-y-5">
              <div class="admin-panel-header">
                <div>
                  <h2 class="admin-panel-title">Applicant details</h2>
                  <p class="admin-panel-subtitle">Adjust the stored employer-side copy of the submission when details need correcting.</p>
                </div>
              </div>

              <div class="grid gap-4 md:grid-cols-2">
                <div>
                  <label class="label">Full Name</label>
                  <input v-model="draft.name" class="input" />
                </div>
                <div>
                  <label class="label">Suburb</label>
                  <input v-model="draft.suburb" class="input" />
                </div>
                <div>
                  <label class="label">Contact No</label>
                  <input v-model="draft.contact_no" class="input" />
                </div>
                <div>
                  <label class="label">Email</label>
                  <input v-model="draft.email" class="input" type="email" />
                </div>
                <div class="md:col-span-2">
                  <label class="label">Visa / Residency Status</label>
                  <select v-model="draft.visa_status" class="input">
                    <option value="">Select visa status</option>
                    <option value="student_visa">Student Visa</option>
                    <option value="partner_visa">Partner Visa</option>
                    <option value="tr">TR</option>
                    <option value="australian_pr">Australian PR</option>
                    <option value="australian_citizen">Australian Citizen</option>
                    <option value="other">Other</option>
                  </select>
                </div>
                <div v-if="draft.visa_status === 'other'" class="md:col-span-2">
                  <label class="label">Please explain visa type</label>
                  <input v-model="draft.visa_other" class="input" />
                </div>
                <div class="md:col-span-2">
                  <label class="label">Which days/times are you available?</label>
                  <textarea v-model="draft.availability" class="input min-h-28" />
                </div>
              </div>

              <div v-for="[field, label] in booleanQuestions" :key="field" class="space-y-2">
                <div class="text-sm font-medium text-slate-800">{{ label }}</div>
                <div class="flex flex-wrap gap-3">
                  <label class="flex items-center gap-2 rounded-md border border-slate-200 px-4 py-2 text-sm">
                    <input v-model="draft[field]" :value="true" type="radio" />
                    <span>Yes</span>
                  </label>
                  <label class="flex items-center gap-2 rounded-md border border-slate-200 px-4 py-2 text-sm">
                    <input v-model="draft[field]" :value="false" type="radio" />
                    <span>No</span>
                  </label>
                </div>
              </div>

              <div class="grid gap-4">
                <div>
                  <label class="label">Education</label>
                  <textarea v-model="draft.education" class="input min-h-28" />
                </div>
                <div>
                  <label class="label">Job 1</label>
                  <textarea v-model="draft.work_exp_1" class="input min-h-28" />
                </div>
                <div>
                  <label class="label">Job 2</label>
                  <textarea v-model="draft.work_exp_2" class="input min-h-28" />
                </div>
                <div>
                  <label class="label">References</label>
                  <textarea v-model="draft.references" class="input min-h-28" />
                </div>
              </div>
            </div>

            <div class="space-y-6">
              <div class="card space-y-4">
                <div class="admin-panel-header">
                  <div>
                    <h2 class="admin-panel-title">Employer review</h2>
                    <p class="admin-panel-subtitle">Saving a ranking marks the application as read in the list view.</p>
                  </div>
                </div>

                <div>
                  <label class="label">Employer Ranking</label>
                  <select v-model="draft.employer_ranking" class="input">
                    <option :value="null">Unranked</option>
                    <option v-for="option in rankingOptions" :key="option.value" :value="option.value">
                      {{ option.label }}
                    </option>
                  </select>
                </div>

                <div>
                  <label class="label">Employer Notes</label>
                  <textarea v-model="draft.employer_notes" class="input min-h-40" />
                </div>

                <div class="flex flex-wrap items-center gap-3">
                  <button :disabled="saving" class="btn-admin-primary" @click="save">
                    {{ saving ? 'Saving...' : 'Save' }}
                  </button>
                  <button class="btn-admin-outline" @click="cancelEdit">Cancel</button>
                  <button class="btn-admin-outline" :disabled="exporting || saving" @click="exportPdf">
                    {{ exporting ? 'Exporting...' : 'Export PDF' }}
                  </button>
                </div>

                <span v-if="saveMessage" class="text-sm font-medium text-emerald-700">{{ saveMessage }}</span>
                <span v-if="saveError" class="text-sm font-medium text-red-600">{{ saveError }}</span>
              </div>

              <div class="admin-note">
                <strong>Note:</strong> this edit mode is employer-only. It updates the stored application record for internal review and does not change the public applicant form experience.
              </div>
            </div>
          </div>

          <div v-else class="grid gap-6 xl:grid-cols-[minmax(0,1.65fr)_minmax(320px,1fr)]">
            <div class="card space-y-4">
              <div class="admin-panel-header">
                <div>
                  <h2 class="admin-panel-title">Applicant profile</h2>
                  <p class="admin-panel-subtitle">Everything submitted by the candidate, kept in a format that is easier to scan during review.</p>
                </div>
              </div>

              <div class="grid gap-4 md:grid-cols-2">
                <div><div class="text-xs uppercase tracking-[0.2em] text-slate-400">Full Name</div><div>{{ application.name }}</div></div>
                <div><div class="text-xs uppercase tracking-[0.2em] text-slate-400">Suburb</div><div>{{ application.suburb }}</div></div>
                <div><div class="text-xs uppercase tracking-[0.2em] text-slate-400">Contact No</div><div>{{ application.contact_no }}</div></div>
                <div><div class="text-xs uppercase tracking-[0.2em] text-slate-400">Email</div><div>{{ application.email }}</div></div>
                <div class="md:col-span-2"><div class="text-xs uppercase tracking-[0.2em] text-slate-400">Visa / Residency Status</div><div>{{ visaStatusLabel(application.visa_status) }}</div></div>
                <div v-if="application.visa_status === 'other' && application.visa_other" class="md:col-span-2"><div class="text-xs uppercase tracking-[0.2em] text-slate-400">Please explain visa type</div><div>{{ application.visa_other }}</div></div>
                <div class="md:col-span-2"><div class="text-xs uppercase tracking-[0.2em] text-slate-400">Which days/times are you available?</div><div>{{ application.availability }}</div></div>
                <div :class="['md:col-span-2', !application.reliable_transport ? flagClass('yellow') : '']">
                  <div class="text-xs uppercase tracking-[0.2em] text-slate-400">Do you have a reliable transport?</div>
                  <div>{{ yesNo(application.reliable_transport) }}</div>
                </div>
                <div class="md:col-span-2">
                  <div class="text-xs uppercase tracking-[0.2em] text-slate-400">Do you have a valid driving licence?</div>
                  <div>{{ yesNo(application.driving_licence) }}</div>
                </div>
                <div :class="['md:col-span-2', !application.has_abn ? flagClass('yellow') : '']">
                  <div class="text-xs uppercase tracking-[0.2em] text-slate-400">Do you have an ABN?</div>
                  <div>{{ yesNo(application.has_abn) }}</div>
                </div>
                <div :class="['md:col-span-2', application.criminal_conviction ? flagClass('red') : '']">
                  <div class="text-xs uppercase tracking-[0.2em] text-slate-400">Have you been convicted of any crimes in Australia or overseas (have any pending criminal charges)?</div>
                  <div>{{ yesNo(application.criminal_conviction) }}</div>
                </div>
                <div :class="['md:col-span-2', !application.police_clearance ? flagClass('yellow') : '']">
                  <div class="text-xs uppercase tracking-[0.2em] text-slate-400">Do you have a current police clearance? or are you willing to apply for one?</div>
                  <div>{{ yesNo(application.police_clearance) }}</div>
                </div>
                <div :class="['md:col-span-2', application.workers_comp ? flagClass('red') : '']">
                  <div class="text-xs uppercase tracking-[0.2em] text-slate-400">Have you ever claimed Workers Compensation, Accident / Sickness Insurance?</div>
                  <div>{{ yesNo(application.workers_comp) }}</div>
                </div>
                <div class="md:col-span-2"><div class="text-xs uppercase tracking-[0.2em] text-slate-400">Education</div><div>{{ application.education }}</div></div>
                <div class="md:col-span-2 border-b border-slate-200 pb-2 text-base font-semibold text-slate-900">Work History</div>
                <div class="md:col-span-2"><div class="text-xs uppercase tracking-[0.2em] text-slate-400">Job 1</div><div>{{ application.work_exp_1 }}</div></div>
                <div class="md:col-span-2"><div class="text-xs uppercase tracking-[0.2em] text-slate-400">Job 2</div><div>{{ application.work_exp_2 }}</div></div>
                <div class="md:col-span-2"><div class="text-xs uppercase tracking-[0.2em] text-slate-400">References</div><div>{{ application.references }}</div></div>
              </div>
            </div>

            <div class="space-y-6">
              <div class="card space-y-4">
                <div class="admin-panel-header">
                  <div>
                    <h2 class="admin-panel-title">Employer review</h2>
                    <p class="admin-panel-subtitle">Record the final assessment and keep internal notes beside the application.</p>
                  </div>
                </div>

                <div>
                  <div class="label">Employer Ranking</div>
                  <div class="text-base font-medium text-slate-900">{{ applicationRankingLabel }}</div>
                </div>

                <div>
                  <div class="label">Employer Notes</div>
                  <div class="whitespace-pre-wrap text-slate-700">{{ application.employer_notes || '-' }}</div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                  <button class="btn-admin-primary" @click="beginEdit">Edit Application</button>
                  <button class="btn-admin-outline" :disabled="exporting" @click="exportPdf">
                    {{ exporting ? 'Exporting...' : 'Export PDF' }}
                  </button>
                </div>

                <span v-if="saveMessage" class="text-sm font-medium text-emerald-700">{{ saveMessage }}</span>
              </div>

              <div class="card space-y-4">
                <div class="admin-panel-header">
                  <div>
                    <h2 class="admin-panel-title">Quick checks</h2>
                    <p class="admin-panel-subtitle">Useful decision points surfaced on the side so they are easy to revisit while ranking.</p>
                  </div>
                </div>

                <div class="admin-kv">
                  <div class="admin-kv-label">Reliable transport</div>
                  <div class="admin-kv-value">{{ yesNo(application.reliable_transport) }}</div>
                </div>
                <div class="admin-kv">
                  <div class="admin-kv-label">Driving licence</div>
                  <div class="admin-kv-value">{{ yesNo(application.driving_licence) }}</div>
                </div>
                <div class="admin-kv">
                  <div class="admin-kv-label">Police clearance</div>
                  <div class="admin-kv-value">{{ yesNo(application.police_clearance) }}</div>
                </div>
                <div class="admin-kv">
                  <div class="admin-kv-label">Criminal conviction</div>
                  <div class="admin-kv-value">{{ yesNo(application.criminal_conviction) }}</div>
                </div>
                <div class="admin-kv">
                  <div class="admin-kv-label">Workers compensation history</div>
                  <div class="admin-kv-value">{{ yesNo(application.workers_comp) }}</div>
                </div>
              </div>
            </div>
          </div>
        </template>
      </div>
    </main>
  </div>
</template>
