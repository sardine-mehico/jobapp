<script setup lang="ts">
const route = useRoute()
const api = useApi()

type PublicJobDetail = {
  id: string
  job_id: string
  summary_line: string
  preview_text: string
  apply_url: string | null
  advertisement: string
}

const job = ref<PublicJobDetail | null>(null)
const loading = ref(true)
const notFound = ref(false)

useHead(() => ({
  title: job.value?.summary_line || 'Job Description'
}))

onMounted(async () => {
  try {
    job.value = await api<PublicJobDetail>(`/public/jobs/${route.params.id}`)
  } catch {
    await navigateTo('/', { replace: true })
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="page-shell px-4 py-8 sm:py-12">
    <div class="mx-auto max-w-[700px] space-y-6">
      <div v-if="loading" class="card text-center text-slate-500">
        Loading job description...
      </div>

      <div v-else-if="notFound || !job" class="card text-center">
        <h1 class="text-3xl font-semibold">Job not found</h1>
        <p class="mt-3 text-slate-500">This job is unavailable right now.</p>
      </div>

      <template v-else>
        <div class="card space-y-4">
          <NuxtLink class="text-sm font-medium text-blue-600" to="/">Back to Jobs</NuxtLink>
          <h1 class="text-2xl font-semibold text-slate-900">{{ job.summary_line || job.job_id }}</h1>
          <div class="flex justify-end">
            <a v-if="job.apply_url" :href="job.apply_url" class="btn-primary public-apply-button text-center no-underline">Apply Now</a>
          </div>
        </div>

        <div class="card">
          <div class="prose-content public-job-description" v-html="job.advertisement" />
        </div>

        <div class="card">
          <div class="flex justify-end">
            <a v-if="job.apply_url" :href="job.apply_url" class="btn-primary public-apply-button text-center no-underline">Apply Now</a>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>
