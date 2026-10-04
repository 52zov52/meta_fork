// https://nuxt.com/docs/api/configuration/nuxt-config
const apiUrl = process.env.API_URL || 'http://localhost:8000'
const frontendUrl = process.env.FRONTEND_URL || 'http://localhost:3000'
const apiPathStorage = process.env.API_PATH_STORAGE || `${apiUrl.replace(/\/$/, '')}/storage/`

export default defineNuxtConfig({
  compatibilityDate: '2026-03-05',
  devtools: { enabled: false },
  future: {
    compatibilityVersion: 4,
  },
  app: {
    head: {
      title: 'ЮУ (ИУБиП): Metaвселенная',
      charset: 'utf-16',
      viewport: 'width=device-width, initial-scale=1, maximum-scale=1',
    },
  },
  routeRules: {
    '/': { redirect: '/auth/login' },
    '/ratings': { redirect: '/student/ratings' },
    '/student': { redirect: '/student/my-activities' },
  },
  runtimeConfig: {
    public: {
      FRONTEND_URL: frontendUrl,
      API_URL: apiUrl,
      API_PATH_STORAGE: apiPathStorage
    }
  },
  components: [
    {
      path: '~/components',
      pathPrefix: false,
    },
  ],
  css: ['~/assets/css/main.css'],
  icon: {
    clientBundle: {
      scan: true,
      icons: [
        'heroicons:home',
        'heroicons:bell',
        'heroicons:cog-6-tooth',
        'heroicons:building-storefront',
        'heroicons:trophy',
        'lucide:arrow-down',
        'lucide:arrow-left',
        'lucide:arrow-right',
        'lucide:arrow-up',
        'lucide:arrow-up-right',
        'lucide:loader-circle',
        'lucide:check',
        'lucide:chevron-down',
        'lucide:chevron-up',
        'lucide:chevron-right',
        'lucide:chevron-left',
        'lucide:chevrons-left',
        'lucide:chevrons-right',
        'lucide:circle-alert',
        'lucide:circle-check',
        'lucide:circle-x',
        'lucide:copy',
        'lucide:copy-check',
        'lucide:ellipsis',
        'lucide:eye',
        'lucide:eye-off',
        'lucide:file',
        'lucide:folder',
        'lucide:folder-open',
        'lucide:grip-vertical',
        'lucide:hash',
        'lucide:info',
        'lucide:lightbulb',
        'lucide:minus',
        'lucide:upload',
        'lucide:x',
        'lucide:menu',
        'lucide:monitor',
        'lucide:moon',
        'lucide:panel-left-close',
        'lucide:panel-left-open',
        'lucide:plus',
        'lucide:rotate-ccw',
        'lucide:search',
        'lucide:square',
        'lucide:sun',
        'lucide:triangle-alert',
      ],
    },
  },
  ui: {
    fonts: false,
    colorMode: false
  },
  ssr: false,
  imports: {
    dirs: ['~/composables/student-api'],
  },
  nitro: {
    compressPublicAssets: {
      gzip: true,
    },
  },
  vite: {
    optimizeDeps: {
      include: ['@unovis/vue', '@unovis/ts', '@vueuse/core', 'html-to-image'],
    },
  },
  modules: ['@nuxt/ui', 'nuxt-auth-sanctum', '@nuxt/icon', '@nuxt/eslint'],
  sanctum: {
    baseUrl: apiUrl,
    origin: frontendUrl,
    redirectIfUnauthenticated: true,
    endpoints: {
      user: '/user',
    },
    redirect: {
      keepRequestedRoute: true,
      onLogin: false,
      onLogout: '/auth/login',
      onAuthOnly: '/auth/login',
      onGuestOnly: false,
    },
  },
  hooks: {
    'pages:extend'(pages) {
      if (process.env.NODE_ENV === 'production') {
        const debugPages = pages.filter((page) => page.path.startsWith('/debug'))
        for (const page of debugPages) {
          const index = pages.indexOf(page)
          if (index !== -1) pages.splice(index, 1)
        }
      }
    },
  },
})
