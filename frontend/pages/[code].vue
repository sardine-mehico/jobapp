<script setup lang="ts">
const route = useRoute()
const api = useApi()

useHead({
  title: 'Job Application'
})

const currentPage = ref(1)
const loading = ref(true)
const invalidLink = ref(false)
const submitting = ref(false)
const clientErrors = ref<Record<string, string>>({})
const serverErrors = ref<Record<string, string[]>>({})
const positivePoints = ref('')
const contactEmail = ref('')
const jobId = ref('')

const form = reactive({
  name: '',
  suburb: '',
  contact_no: '',
  email: '',
  availability: '',
  visa_status: '',
  visa_other: '',
  reliable_transport: null as boolean | null,
  driving_licence: null as boolean | null,
  has_abn: null as boolean | null,
  criminal_conviction: null as boolean | null,
  police_clearance: null as boolean | null,
  workers_comp: null as boolean | null,
  education: '',
  work_exp_1: '',
  work_exp_2: '',
  references: '',
  declaration: false
})

const visaOptions = [
  { label: 'Student Visa', value: 'student_visa' },
  { label: 'Partner Visa', value: 'partner_visa' },
  { label: 'TR', value: 'tr' },
  { label: 'Australian PR', value: 'australian_pr' },
  { label: 'Australian Citizen', value: 'australian_citizen' },
  { label: 'Other', value: 'other' }
]

function getFirstServerError(field: string) {
  return serverErrors.value[field]?.[0]
}

function normalizeEmail() {
  form.email = form.email.trim().toLowerCase()
}

function validatePage(page = currentPage.value) {
  clientErrors.value = {}

  if (page === 1) {
    normalizeEmail()
    if (!form.name.trim()) clientErrors.value.name = 'Name is required.'
    if (!form.suburb.trim()) clientErrors.value.suburb = 'Suburb is required.'
    if (!form.contact_no.trim()) clientErrors.value.contact_no = 'Contact number is required.'
    if (!form.email.trim()) clientErrors.value.email = 'Email is required.'
    else if (!/^\S+@\S+\.\S+$/.test(form.email)) clientErrors.value.email = 'Enter a valid email address.'
    if (!form.visa_status) clientErrors.value.visa_status = 'Visa status is required.'
    if (form.visa_status === 'other' && !form.visa_other.trim()) clientErrors.value.visa_other = 'Please explain your visa type.'
  }

  if (page === 2) {
    if (!form.availability.trim()) clientErrors.value.availability = 'Availability is required.'
    for (const field of ['reliable_transport', 'driving_licence', 'has_abn', 'criminal_conviction', 'police_clearance', 'workers_comp'] as const) {
      if (form[field] === null) clientErrors.value[field] = 'Please select an option.'
    }
  }

  if (page === 3) {
    if (!form.education.trim()) clientErrors.value.education = 'Education is required.'
    if (!form.work_exp_1.trim()) clientErrors.value.work_exp_1 = 'Job 1 is required.'
    if (!form.references.trim()) clientErrors.value.references = 'References are required.'
    if (!form.declaration) clientErrors.value.declaration = 'You must confirm the declaration.'
  }

  return Object.keys(clientErrors.value).length === 0
}

function nextPage() {
  if (validatePage()) {
    currentPage.value += 1
  }
}

async function submit() {
  normalizeEmail()

  if (!validatePage(3)) {
    return
  }

  submitting.value = true
  serverErrors.value = {}

  try {
    await api(`/apply/${route.params.code}`, {
      method: 'POST',
      body: {
        name: form.name,
        suburb: form.suburb,
        contact_no: form.contact_no,
        email: form.email,
        availability: form.availability,
        visa_status: form.visa_status,
        visa_other: form.visa_status === 'other' ? form.visa_other : null,
        reliable_transport: form.reliable_transport,
        driving_licence: form.driving_licence,
        has_abn: form.has_abn,
        criminal_conviction: form.criminal_conviction,
        police_clearance: form.police_clearance,
        workers_comp: form.workers_comp,
        education: form.education,
        work_exp_1: form.work_exp_1,
        work_exp_2: form.work_exp_2,
        references: form.references
      }
    })

    await navigateTo(`/thank-you?email=${encodeURIComponent(contactEmail.value)}`)
  } catch (error: any) {
    serverErrors.value = error?.data?.errors || {}
  } finally {
    submitting.value = false
  }
}

onMounted(async () => {
  try {
    const data = await api<{ positive_points: string; contact_email: string; job_id: string }>(`/apply/${route.params.code}`)
    positivePoints.value = data.positive_points
    contactEmail.value = data.contact_email
    jobId.value = data.job_id
  } catch {
    await navigateTo('/', { replace: true })
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="page-shell px-4 py-8 sm:py-12">
    <div class="mx-auto max-w-[460px]">
      <div v-if="loading" class="card text-center text-slate-500">Loading application form...</div>

      <div v-else-if="invalidLink" class="card text-center">
        <h1 class="text-3xl font-semibold">Invalid link</h1>
        <p class="mt-3 text-slate-500">This application link could not be found.</p>
      </div>

      <div v-else class="space-y-6">
        <div class="application-header">
          <h1 class="application-title">Job Application</h1>
        </div>

        <div v-if="currentPage > 1" class="card progress-card">
          <div class="progress-meta">
            <span class="progress-label">{{ currentPage === 2 ? '' : 'Final Page' }}</span>
            <span class="progress-value">{{ currentPage === 2 ? '66%' : '100%' }}</span>
          </div>
          <div class="progress-bar">
            <span
              :class="currentPage === 3 ? 'is-complete' : ''"
              :style="{ width: currentPage === 2 ? '66%' : '100%' }"
            />
          </div>
        </div>

        <form class="card space-y-5" @submit.prevent="submit">
          <template v-if="currentPage === 1">
            <input v-model="form.name" class="input" placeholder="Full Name" />
            <p v-if="clientErrors.name || getFirstServerError('name')" class="text-sm text-red-600">{{ clientErrors.name || getFirstServerError('name') }}</p>

            <input v-model="form.suburb" class="input" placeholder="Suburb" />
            <p v-if="clientErrors.suburb || getFirstServerError('suburb')" class="text-sm text-red-600">{{ clientErrors.suburb || getFirstServerError('suburb') }}</p>

            <input v-model="form.contact_no" class="input" placeholder="Contact No" />
            <p v-if="clientErrors.contact_no || getFirstServerError('contact_no')" class="text-sm text-red-600">{{ clientErrors.contact_no || getFirstServerError('contact_no') }}</p>

            <input
              v-model="form.email"
              autocapitalize="none"
              autocomplete="email"
              class="input"
              placeholder="Email"
              spellcheck="false"
              type="email"
              @input="normalizeEmail"
            />
            <p v-if="clientErrors.email || getFirstServerError('email')" class="text-sm text-red-600">{{ clientErrors.email || getFirstServerError('email') }}</p>

            <div>
              <select v-model="form.visa_status" class="input">
                <option value="">Visa / Residency Status</option>
                <option v-for="option in visaOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
              </select>
              <p v-if="clientErrors.visa_status || getFirstServerError('visa_status')" class="mt-1 text-sm text-red-600">{{ clientErrors.visa_status || getFirstServerError('visa_status') }}</p>
            </div>

            <div v-if="form.visa_status === 'other'">
              <input v-model="form.visa_other" class="input" placeholder="Please explain visa type" />
              <p v-if="clientErrors.visa_other || getFirstServerError('visa_other')" class="mt-1 text-sm text-red-600">{{ clientErrors.visa_other || getFirstServerError('visa_other') }}</p>
            </div>

            <button class="btn-primary !min-h-12 w-full" type="button" @click="nextPage">Continue</button>
          </template>

          <template v-else-if="currentPage === 2">
            <div>
              <label class="label application-field-label">Which days/times are you available?</label>
              <textarea v-model="form.availability" class="input min-h-28" placeholder="monday all day, thursday after 6pm, all other days available after 3pm" />
              <p v-if="clientErrors.availability || getFirstServerError('availability')" class="mt-1 text-sm text-red-600">{{ clientErrors.availability || getFirstServerError('availability') }}</p>
            </div>

            <div v-for="field in [
              ['reliable_transport', 'Do you have a reliable transport?'],
              ['driving_licence', 'Do you have a valid driving licence?'],
              ['has_abn', 'Do you have an ABN?'],
              ['criminal_conviction', 'Have you been convicted of any crimes in Australia or overseas (have any pending criminal charges)?'],
              ['police_clearance', 'Do you have a current police clearance? or are you willing to apply for one?'],
              ['workers_comp', 'Have you ever claimed Workers Compensation, Accident / Sickness Insurance?']
            ]" :key="field[0]" class="space-y-2">
              <div class="application-question-text font-medium text-slate-800">{{ field[1] }}</div>
              <div class="binary-grid">
                <label class="binary-option">
                  <input v-model="form[field[0] as keyof typeof form]" :value="true" class="mr-2" type="radio" />
                  Yes
                </label>
                <label class="binary-option">
                  <input v-model="form[field[0] as keyof typeof form]" :value="false" class="mr-2" type="radio" />
                  No
                </label>
              </div>
              <p v-if="clientErrors[field[0]] || getFirstServerError(field[0])" class="text-sm text-red-600">{{ clientErrors[field[0]] || getFirstServerError(field[0]) }}</p>
            </div>

            <button class="btn-primary !min-h-12 w-full" type="button" @click="nextPage">Continue</button>
          </template>

          <template v-else>
            <div>
              <label class="label application-field-label">Education</label>
              <textarea
                v-model="form.education"
                class="input min-h-28"
                placeholder="2026, Bachelor in Human Resources -ongoing
2024, Certificate in Psychology"
              />
            </div>
            <p v-if="clientErrors.education || getFirstServerError('education')" class="text-sm text-red-600">{{ clientErrors.education || getFirstServerError('education') }}</p>

            <div class="section-label application-section-title">Work History</div>

            <div>
              <label class="label application-sub-label">Job 1</label>
              <textarea v-model="form.work_exp_1" class="input min-h-[7rem]" placeholder="June 2025 to March 2025, ABC Cleaning, Duties performed" />
              <p v-if="clientErrors.work_exp_1 || getFirstServerError('work_exp_1')" class="mt-1 text-sm text-red-600">{{ clientErrors.work_exp_1 || getFirstServerError('work_exp_1') }}</p>
            </div>

            <div>
              <label class="label application-sub-label">Job 2</label>
              <textarea v-model="form.work_exp_2" class="input min-h-[7rem]" placeholder="June 2025 to March 2025, ABC Cleaning, Duties performed" />
              <p v-if="clientErrors.work_exp_2 || getFirstServerError('work_exp_2')" class="mt-1 text-sm text-red-600">{{ clientErrors.work_exp_2 || getFirstServerError('work_exp_2') }}</p>
            </div>

            <div>
              <label class="label application-field-label">References</label>
              <textarea v-model="form.references" class="input min-h-[7rem]" placeholder="example: Anthony, 0452 112 221, Sparkle Cleaning Company -Manager" />
            </div>
            <p v-if="clientErrors.references || getFirstServerError('references')" class="text-sm text-red-600">{{ clientErrors.references || getFirstServerError('references') }}</p>

            <label class="declaration-row">
              <input v-model="form.declaration" type="checkbox" />
              <span>I declare that the information provided above is true and correct.</span>
            </label>
            <p v-if="clientErrors.declaration" class="text-sm text-red-600">{{ clientErrors.declaration }}</p>

            <button :disabled="submitting" class="btn-primary !min-h-12 w-full" type="submit">{{ submitting ? 'Submitting...' : 'Submit' }}</button>
          </template>
        </form>
      </div>
    </div>
  </div>
</template>
