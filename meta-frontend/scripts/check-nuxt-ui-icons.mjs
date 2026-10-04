#!/usr/bin/env node

import { readFileSync } from 'node:fs'

const UI_DEFAULTS_PATH = 'node_modules/@nuxt/ui/dist/shared/ui.DI_ypO2T.mjs'
const NUXT_CONFIG_PATH = 'nuxt.config.ts'

function extractNuxtUiDefaultLucideIcons(source) {
  const block = (source.match(/const icons = \{([\s\S]*?)\n\};/) || [])[1] || ''
  const defaults = [...block.matchAll(/:\s*"i-lucide-([^"]+)"/g)].map((m) => `lucide:${m[1]}`)
  return [...new Set(defaults)].sort()
}

function extractConfiguredIcons(source) {
  const iconsBlock = (source.match(/icons:\s*\[([\s\S]*?)\]/) || [])[1] || ''
  return [...iconsBlock.matchAll(/['"]([^'"]+)['"]/g)].map((m) => m[1])
}

try {
  const uiDefaultsSource = readFileSync(UI_DEFAULTS_PATH, 'utf8')
  const nuxtConfigSource = readFileSync(NUXT_CONFIG_PATH, 'utf8')

  const defaults = extractNuxtUiDefaultLucideIcons(uiDefaultsSource)
  const configured = extractConfiguredIcons(nuxtConfigSource)
  const configuredSet = new Set(configured)

  const missing = defaults.filter((icon) => !configuredSet.has(icon))

  console.log(`Nuxt UI default lucide icons: ${defaults.length}`)
  console.log(`Configured in nuxt.config.ts: ${defaults.length - missing.length}`)

  if (!missing.length) {
    console.log('✅ All Nuxt UI default lucide icons are present in icon.clientBundle.icons')
    process.exit(0)
  }

  console.log(`❌ Missing default icons: ${missing.length}`)
  for (const icon of missing) {
    console.log(`- ${icon}`)
  }
  process.exit(1)
} catch (error) {
  console.error('Failed to check Nuxt UI default icons')
  console.error(error instanceof Error ? error.message : String(error))
  process.exit(2)
}
