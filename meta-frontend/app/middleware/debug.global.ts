export default defineNuxtRouteMiddleware((to) => {
  if (!import.meta.dev && to.path.startsWith('/debug')) {
    return showError({ statusCode: 404 })
  }
})
