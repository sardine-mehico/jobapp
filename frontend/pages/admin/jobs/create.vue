<script setup lang="ts">
definePageMeta({ middleware: 'auth' })

const api = useApi()
const { logout, fetchUser } = useAuth()
const sidebarOpen = ref(false)
const form = reactive({
  advertisement: '<p></p>',
  contact_email: 'agibson1974@gmail.com',
  is_active: true
})
const loading = ref(false)
const errors = ref<Record<string, string[]>>({})

async function submit() {
  loading.value = true
  errors.value = {}

  try {
    await api<any>('/jobs', {
      method: 'POST',
      body: form
    })

    await navigateTo('/admin/jobs')
  } catch (error: any) {
    errors.value = error?.data?.errors || {}
  } finally {
    loading.value = false
  }
}

async function signOut() {
  await logout()
  await navigateTo('/employer/login')
}

onMounted(fetchUser)
</script>

<template>
  <div class="page-shell employer-ui">
    <AdminSidebar :open="sidebarOpen" @close="sidebarOpen = false" @logout="signOut" />

    <main class="admin-main md:ml-72">
      <div class="admin-content space-y-6">
        <div class="flex items-center justify-between mb-5">
          <NuxtLink class="btn-admin-outline" to="/admin/jobs">Back</NuxtLink>
          <div class="flex items-center gap-3">
            <button class="btn-admin-outline md:!hidden" @click="sidebarOpen = true">Menu</button>
          </div>
        </div>

        <form class="grid gap-6 xl:grid-cols-[minmax(0,1.7fr)_minmax(320px,1fr)]" @submit.prevent="submit">
          <div class="card space-y-4">
            <div class="admin-panel-header">
              <div>
                <h2 class="admin-panel-title">Job advertisement</h2>
              </div>
            </div>
            <RichTextEditor v-model="form.advertisement" placeholder="Write the job advertisement..." />
            <p v-if="errors.advertisement?.[0]" class="text-sm text-red-600">{{ errors.advertisement[0] }}</p>
          </div>

          <div class="space-y-6">
            <div class="card space-y-3">
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
              </div>
            </div>



            <div class="flex flex-wrap gap-3">
              <button :disabled="loading" class="btn-admin-primary" type="submit">{{ loading ? 'Saving...' : 'Create Job' }}</button>
              <NuxtLink class="btn-admin-outline" to="/admin/jobs">Cancel</NuxtLink>
            </div>
          </div>
        </form>
      </div>
    </main>
  </div>
</template>
