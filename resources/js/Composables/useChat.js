import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

export function useChat() {
    const conversations = ref([])
    const activeConversation = ref(null)
    const messages = ref([])
    const isLoading = ref(false)
    const selectedModel = ref('')

    const sortedConversations = computed(() => {
        return conversations.value.sort((a, b) =>
            new Date(b.last_message_at) - new Date(a.last_message_at)
        )
    })

    const initializeSelectedModel = (model) => {
        if (!selectedModel.value && model) {
            selectedModel.value = model
        }
    }

    const setActiveConversation = (conversation) => {
        activeConversation.value = conversation
        if (conversation) {
            router.get(route('chat.show', conversation.id))
        }
    }

    const createNewConversation = async (message, model) => {
        isLoading.value = true

        try {
            await router.post(route('chat.store'), {
                message,
                model
            }, {
                preserveState: false,
                onSuccess: () => {
                    // Redirect handled by controller
                }
            })
        } finally {
            isLoading.value = false
        }
    }

    const sendMessage = async (message, conversationId, model) => {
        isLoading.value = true

        try {
            await router.post(route('chat.message', conversationId), {
                message,
                model
            }, {
                preserveState: false,
                onSuccess: () => {
                    // Redirect handled by controller
                }
            })
        } finally {
            isLoading.value = false
        }
    }

    const updateSelectedModel = (model) => {
        selectedModel.value = model
    }

    return {
        conversations,
        activeConversation,
        messages,
        isLoading,
        selectedModel,
        sortedConversations,
        initializeSelectedModel,
        setActiveConversation,
        createNewConversation,
        sendMessage,
        updateSelectedModel
    }
}
