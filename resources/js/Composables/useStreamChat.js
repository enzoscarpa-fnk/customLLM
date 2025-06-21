import { ref, shallowRef } from 'vue'
import { useStream } from '@laravel/stream-vue'

export function useStreamChat() {
    const streamInstance = shallowRef(null)
    const isStreaming = ref(false)

    const getCsrfToken = () => {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        if (!token) {
            console.warn('⚠️ Token CSRF non trouvé dans les meta tags')
        }
        return token
    }

    const initStream = (url, handlers) => {
        console.log('🔄 Initialisation du stream avec URL:', url)

        // Nettoyer l'ancienne instance si elle existe
        if (streamInstance.value) {
            streamInstance.value = null
        }

        // Créer une nouvelle instance de stream
        streamInstance.value = useStream(url, {
            onData: (data) => {
                if (handlers.onData) {
                    handlers.onData(data)
                }
            },
            onFinish: () => {
                console.log('✅ Stream terminé')
                isStreaming.value = false
                if (handlers.onFinish) {
                    handlers.onFinish()
                }
            },
            onError: (error) => {
                console.error('❌ Erreur de streaming:', error)
                isStreaming.value = false
                if (handlers.onError) {
                    handlers.onError(error)
                }
            }
        })

        return {
            send: (data) => {
                if (!streamInstance.value || isStreaming.value) {
                    console.warn('⚠️ Stream non disponible ou déjà en cours')
                    return false
                }

                const dataWithCsrf = {
                    ...data,
                    _token: getCsrfToken()
                }

                console.log('📤 Envoi de données via stream:', dataWithCsrf)
                isStreaming.value = true
                streamInstance.value.send(dataWithCsrf)
                return true
            }
        }
    }

    const cleanup = () => {
        if (streamInstance.value) {
            streamInstance.value = null
        }
        isStreaming.value = false
    }

    return {
        initStream,
        isStreaming,
        cleanup
    }
}
