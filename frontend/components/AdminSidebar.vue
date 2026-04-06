<script setup lang="ts">
const props = defineProps<{
  open: boolean
}>()

const emit = defineEmits<{
  close: []
  logout: []
}>()

const route = useRoute()

const links = [
  { label: 'Dashboard', to: '/admin/dashboard' },
  { label: 'Jobs', to: '/admin/jobs' },
  { label: 'Applications', to: '/admin/applications' }
]
</script>

<template>
  <div>
    <div
      v-if="props.open"
      class="fixed inset-0 z-30 bg-slate-950/50 backdrop-blur-sm lg:hidden"
      @click="emit('close')"
    />

    <aside
      :class="[
        'admin-sidebar fixed inset-y-0 left-0 z-40 flex w-72 flex-col text-white transition-transform duration-300 lg:translate-x-0',
        props.open ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
      ]"
    >
      <div class="admin-sidebar__brand">
        <div class="flex items-center justify-between gap-3">
          <div class="admin-sidebar__title">Job App</div>
          <button aria-label="Close sidebar" class="btn-admin-outline lg:hidden !px-3" @click="emit('close')">
            <svg aria-hidden="true" class="h-4 w-4" fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="2" viewBox="0 0 24 24">
              <path d="M6 6l12 12" />
              <path d="M18 6L6 18" />
            </svg>
          </button>
        </div>
      </div>

      <nav class="admin-sidebar__nav">
        <NuxtLink
          v-for="link in links"
          :key="link.to"
          :to="link.to"
          class="admin-sidebar__link"
          :class="{ 'is-active': route.path === link.to }"
          @click="emit('close')"
        >
          <span>{{ link.label }}</span>
          <span v-if="route.path === link.to" class="admin-sidebar__active">Current</span>
        </NuxtLink>
      </nav>

      <div class="admin-sidebar__footer">
        <button class="btn-admin-outline w-full justify-between text-left" @click="emit('logout')">
          <span>Logout</span>
          <span aria-hidden="true">-></span>
        </button>
      </div>
    </aside>
  </div>
</template>
