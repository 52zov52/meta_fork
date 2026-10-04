export function useApiError() {
  const toast = useToast()

  function show(error: unknown, fallback: string = 'Ошибка сервера') {
    const errorData = (error as { response?: { _data?: unknown }; data?: unknown })?.response?._data || (error as { data?: unknown }).data
    const parsedData = errorData && typeof errorData === 'object' ? errorData as Record<string, unknown> : {}

    // Backend uses 'message' for validation and 'error' for others (404, 500 prod)
    const title = (parsedData.message || parsedData.error || fallback) as string
    
    let description: string | undefined

    if (parsedData.errors && typeof parsedData.errors === 'object') {
      const errors = parsedData.errors as Record<string, string[]>
      const messages: string[] = []
      
      for (const [field, fieldErrors] of Object.entries(errors)) {
        if (Array.isArray(fieldErrors)) {
          fieldErrors.forEach(err => messages.push(`${field}: ${err}`))
        }
      }
      
      if (messages.length > 0) {
        description = messages.join('\n')
      }
    }

    toast.add({
      title,
      description,
      color: 'error',
      ui: {
        description: 'whitespace-pre-line'
      }
    })
  }

  return {
    show,
  }
}
