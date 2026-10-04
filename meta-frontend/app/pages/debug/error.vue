<script setup>
const route = useRoute()
const code = Number(route.query.code) || 500
throw createError({ statusCode: code, fatal: code >= 500 })
</script>
