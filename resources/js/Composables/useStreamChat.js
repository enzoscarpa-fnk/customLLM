import { ref } from 'vue'

export function useStreamChat() {
    const isStreaming = ref(false)
    const currentController = ref(null)
    const displayQueue = ref([])
    const isDisplaying = ref(false)

    const getCsrfToken = () => {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        if (!token) {
            console.warn('⚠️ Token CSRF non trouvé dans les meta tags')
        }
        return token
    }

    const displayChunksWithDelay = async (handlers, delayMs = 30) => {
        if (isDisplaying.value || displayQueue.value.length === 0) return

        isDisplaying.value = true

        while (displayQueue.value.length > 0) {
            const chunk = displayQueue.value.shift()
            if (handlers.onData) {
                handlers.onData(chunk)
            }

            // Délai entre chaque caractère/chunk
            if (displayQueue.value.length > 0) {
                await new Promise(resolve => setTimeout(resolve, delayMs))
            }
        }

        isDisplaying.value = false
    }

    const initStream = (url, handlers) => {

        if (currentController.value) {
            currentController.value.abort()
        }

        return {
            send: async (data) => {
                if (isStreaming.value) {
                    console.warn('⚠️ Stream déjà en cours')
                    return false
                }

                console.log('📤 Envoi de données via fetch stream:', data)
                isStreaming.value = true

                // Créer un nouveau AbortController
                currentController.value = new AbortController()

                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'text/event-stream',
                            'X-CSRF-TOKEN': getCsrfToken(),
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify(data),
                        signal: currentController.value.signal
                    })

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`)
                    }

                    console.log('✅ Réponse reçue, début du streaming')

                    const reader = response.body.getReader()
                    const decoder = new TextDecoder()

                    while (true) {
                        const { done, value } = await reader.read()

                        if (done) {
                            // Attendre que tous les chunks soient affichés
                            while (displayQueue.value.length > 0) {
                                await new Promise(resolve => setTimeout(resolve, 50))
                            }

                            isStreaming.value = false
                            if (handlers.onFinish) {
                                handlers.onFinish()
                            }
                            break
                        }

                        const chunk = decoder.decode(value, { stream: true })

                        displayQueue.value.push(chunk)

                        displayChunksWithDelay(handlers, 20)
                    }

                    return true

                } catch (error) {
                    console.error('❌ Erreur de streaming:', error)
                    isStreaming.value = false
                    if (handlers.onError) {
                        handlers.onError(error)
                    }
                    return false
                }
            }
        }
    }

    const cleanup = () => {
        if (currentController.value) {
            currentController.value.abort()
            currentController.value = null
        }
        isStreaming.value = false
    }

    return {
        initStream,
        isStreaming,
        cleanup
    }
}
