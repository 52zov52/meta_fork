<template>
  <AppLayoutHeaderNav force-variant="student">
    <template #header>
      <slot name="header" />
    </template>
    <slot />
  </AppLayoutHeaderNav>
</template>

<script setup>
/** Layout для страниц кабинета студента (/student/*). Шапка всегда student nav. */
</script>
