<script setup lang="ts">
const route = useRoute()
const email = computed(() => String(route.query.email || ''))
</script>

<template>
  <div class="page-shell flex items-center justify-center px-4 py-12">
    <div class="card max-w-xl text-center">
      <h1 class="text-3xl font-semibold">Thank you for applying!</h1>
      <p class="mt-4 text-slate-600">
        We have received your application. If you have further questions,
        please contact
        <a :href="`mailto:${email}`" class="font-semibold text-blue-600">{{ email }}</a>.
      </p>
    </div>
  </div>
</template>
