export default defineNuxtRouteMiddleware((to) => {
  if (process.server) {
    return
  }

  const token = localStorage.getItem('jobapp_token')

  if (!token && to.path !== '/employer/login') {
    return navigateTo('/employer/login')
  }
})
