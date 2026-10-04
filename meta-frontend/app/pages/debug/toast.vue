<script setup lang="ts">
const { show: showApiError } = useApiError()

function simulateValidationError() {
  const error = {
    response: {
      _data: {
        message: "Ошибка валидации",
        errors: {
          email: ["validation.required", "validation.email"],
          last_name: ["validation.required"]
        }
      }
    }
  }
  showApiError(error)
}

function simulateApiError() {
  const error = {
    response: {
      _data: {
        error: "Произошла ошибка. Пожалуйста, попробуйте позже."
      }
    }
  }
  showApiError(error)
}
</script>

<template>
  <div class="p-8 space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold">Debug: Toast Error Handler</h1>
      <UButton to="/debug/error?code=500" color="neutral" variant="ghost">
        Test Fatal 500 Page →
      </UButton>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <UCard>
        <template #header>
          <h3 class="font-semibold">Laravel 422 (Validation)</h3>
        </template>
        <p class="text-sm text-gray-500 mb-4">Ожидается: "field: validation.key" для каждой ошибки.</p>
        <UButton color="error" variant="soft" @click="simulateValidationError">
          Simulate 422 Error
        </UButton>
      </UCard>

      <UCard>
        <template #header>
          <h3 class="font-semibold">Laravel API Error</h3>
        </template>
        <p class="text-sm text-gray-500 mb-4">Ожидается: Текст "Произошла ошибка..." из бэкенда.</p>
        <UButton color="error" variant="soft" @click="simulateApiError">
          Simulate API Error
        </UButton>
      </UCard>
    </div>

    <div class="mt-8 pt-8 border-t border-gray-200">
      <UButton to="/" color="primary" variant="link">
        ← Вернуться в профиль
      </UButton>
    </div>
  </div>
</template>
