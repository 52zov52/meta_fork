<template>
  <AppLayoutHeaderNav>
    <template #header>
      <slot name="header" />
    </template>
    <slot />
  </AppLayoutHeaderNav>
</template>

<script setup>
/** Layout для публичных страниц. Шапка по auth. */
</script>
