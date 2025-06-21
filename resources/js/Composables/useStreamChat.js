import { ref } from 'vue'

export function useStreamChat() {
    const isStreaming = ref(false)
    const currentController = ref(null)

    const getCsrfToken = () => {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        if (!token) {
            console.warn('⚠️ Token CSRF non trouvé dans les meta tags')
        }
        return token
    }

    const initStream = (url, handlers) => {
        console.log('🔄 Initialisation du stream avec URL:', url)

        // Nettoyer l'ancien controller si il existe
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
                            console.log('✅ Stream terminé')
                            isStreaming.value = false
                            if (handlers.onFinish) {
                                handlers.onFinish()
                            }
                            break
                        }

                        const chunk = decoder.decode(value, { stream: true })
                        console.log('📥 Chunk reçu:', chunk)

                        if (handlers.onData) {
                            handlers.onData(chunk)
                        }
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
