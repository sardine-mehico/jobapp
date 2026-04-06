<script setup lang="ts">
const api = useApi()

useHead({
  title: 'Currently Available Jobs'
})

type PublicJob = {
  id: string
  job_id: string
  summary_line: string
  preview_text: string
  apply_url: string | null
}

const jobs = ref<PublicJob[]>([])
const loading = ref(true)

if (process.client) {
  const hostname = window.location.hostname

  if (hostname.startsWith('admin.')) {
    await navigateTo('/employer/login')
  }
}

onMounted(async () => {
  try {
    jobs.value = await api<PublicJob[]>('/public/jobs')
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="page-shell px-4 py-8 sm:py-12">
    <div class="mx-auto w-full max-w-[760px] space-y-6">
      <div class="application-header">
        <h1 class="application-title">Currently Available Jobs</h1>
      </div>

      <div v-if="loading" class="card text-center text-slate-500">
        Loading active jobs...
      </div>

      <div v-else-if="!jobs.length" class="card text-center text-slate-500">
        No active jobs available right now.
      </div>

      <div v-else class="space-y-4">
        <div v-for="job in jobs" :key="job.id" class="card">
          <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
            <NuxtLink :to="`/jobs/${job.id}`" class="block flex-1 space-y-3 text-inherit no-underline">
              <div class="text-base font-semibold text-slate-900">
                {{ job.summary_line || job.job_id }}
              </div>
              <div class="public-job-preview whitespace-pre-line text-sm leading-7 text-slate-600">
                {{ job.preview_text }}
              </div>
            </NuxtLink>

            <div v-if="job.apply_url" class="w-full sm:w-auto sm:shrink-0">
              <a
                :href="job.apply_url"
                class="btn-primary public-apply-button w-full justify-center text-center no-underline sm:min-w-[180px]"
              >
                Apply Here
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
