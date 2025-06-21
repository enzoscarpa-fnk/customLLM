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

        if (currentController.value) {
            currentController.value.abort()
        }

        return {
            send: async (data) => {
                if (isStreaming.value) {
                    console.warn('⚠️ Stream déjà en cours')
                    return false
                }

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

                    const reader = response.body.getReader()
                    const decoder = new TextDecoder()
                    let fullResponse = ''

                    while (true) {
                        const { done, value } = await reader.read()

                        if (done) {
                            isStreaming.value = false

                            // Vérifier si on a reçu un ID de conversation
                            const conversationMatch = fullResponse.match(/__CONVERSATION_ID__:(\d+)__END__/)
                            if (conversationMatch) {
                                const conversationId = conversationMatch[1]
                                if (handlers.onConversationCreated) {
                                    handlers.onConversationCreated(conversationId)
                                }
                            }

                            if (handlers.onFinish) {
                                handlers.onFinish()
                            }
                            break
                        }

                        const chunk = decoder.decode(value, { stream: true })
                        fullResponse += chunk

                        // Ne pas afficher les métadonnées de conversation
                        if (!chunk.includes('__CONVERSATION_ID__')) {
                            if (handlers.onData) {
                                handlers.onData(chunk)
                            }
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
