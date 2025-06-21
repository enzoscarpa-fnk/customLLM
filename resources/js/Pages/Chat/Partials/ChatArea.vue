<script setup>
import { reactive, ref, computed, watch, onBeforeUnmount, onMounted } from 'vue'
import { useStreamChat } from '@/Composables/useStreamChat'
import MessageList from './MessageList.vue'
import MessageInput from './MessageInput.vue'
import CustomInstructionsModal from './CustomInstructions/CustomInstructionsModal.vue'

const props = defineProps({
    activeConversation: Object,
    messages: Array,
    models: Array,
    selectedModel: String,
    userInstructions: Object
})

const emit = defineEmits(['update-model'])

const state = reactive({
    messages: [...props.messages],
    isCreatingConversation: false,
    showInstructionsModal: false
})

const messageInputRef = ref(null)

// Utiliser le nouveau composable de streaming
const { initStream, isStreaming, cleanup } = useStreamChat()
const streamController = ref(null)

onMounted(() => {
    initializeStream()
})

const initializeStream = () => {
    const url = props.activeConversation && props.activeConversation.id
        ? `/chat/${props.activeConversation.id}/stream`
        : '/chat/stream'

    console.log('🚀 Initialisation stream avec URL:', url)

    streamController.value = initStream(url, {
        onData: (data) => {
            console.log('📥 Données reçues dans ChatArea:', data)
            const lastMessage = state.messages[state.messages.length - 1]
            if (lastMessage && lastMessage.role === 'assistant') {
                lastMessage.content = (lastMessage.content || '') + data
                console.log('📝 Message mis à jour:', lastMessage.content.substring(0, 50) + '...')
            }
        },
        onFinish: () => {
            console.log('✅ Stream terminé dans ChatArea')
            if (!props.activeConversation) {
                setTimeout(() => {
                    window.location.reload()
                }, 1000)
            }
        },
        onError: (error) => {
            console.error('❌ Erreur de streaming dans ChatArea:', error)
            if (state.messages.length > 0) {
                const lastMessage = state.messages[state.messages.length - 1]
                if (lastMessage.role === 'assistant' && !lastMessage.content) {
                    state.messages.pop()
                }
            }
        }
    })
}

watch(() => props.activeConversation, (newConversation, oldConversation) => {
    console.log('👀 Conversation changée:', { old: oldConversation?.id, new: newConversation?.id })
    initializeStream()
})

const sendStreamMessage = async (data) => {
    if (!streamController.value) {
        console.warn('⚠️ Stream non initialisé')
        return false
    }

    console.log('📤 Envoi message via stream:', data)
    return await streamController.value.send(data)
}

onBeforeUnmount(() => {
    cleanup()
})

defineExpose({
    focusInput: () => {
        if (messageInputRef.value) {
            messageInputRef.value.focusTextarea()
        }
    }
})

const hasActiveConversation = computed(() => {
    return props.activeConversation !== null
})

const shouldShowMessages = computed(() => {
    return hasActiveConversation.value || state.isCreatingConversation
})

const updateModel = (model) => {
    emit('update-model', model)
}

const handleMessageSent = async (messageData) => {
    console.log('📨 handleMessageSent:', messageData)

    if (!hasActiveConversation.value) {
        state.isCreatingConversation = true
    }

    // 1. Ajouter le message utilisateur
    const userMessage = {
        id: 'temp-user-' + Date.now(),
        role: 'user',
        content: messageData.message,
        created_at: new Date().toISOString(),
    }
    state.messages.push(userMessage)

    // 2. Ajouter un message vide pour l'assistant
    const assistantMessage = {
        id: 'temp-assistant-' + Date.now(),
        role: 'assistant',
        content: '',
        created_at: new Date().toISOString(),
    }
    state.messages.push(assistantMessage)

    console.log('📋 Messages après ajout:', state.messages.length)

    // 3. Envoyer via le stream
    const success = await sendStreamMessage({
        message: messageData.message,
        model: messageData.model,
    })

    if (!success) {
        console.error('❌ Échec de l\'envoi du message')
        // Supprimer les messages temporaires en cas d'échec
        state.messages.pop() // assistant message
        state.messages.pop() // user message
    }
}

const closeInstructions = () => {
    state.showInstructionsModal = false
}

const onInstructionsSaved = () => {
    window.location.reload()
}

watch(() => props.messages, (newMessages) => {
    if (!isStreaming.value) {
        const hasTemporaryMessages = state.messages.some(msg =>
            typeof msg.id === 'string' && msg.id.startsWith('temp-')
        )

        if (!hasTemporaryMessages || newMessages.length > state.messages.length) {
            state.messages = [...newMessages]
        }
    }
}, { deep: true })

watch(() => props.activeConversation, (newConversation) => {
    if (!isStreaming.value) {
        state.messages = [...props.messages]
    }
    if (newConversation) {
        state.isCreatingConversation = false
    }
})
</script>

<template>
    <div class="h-full flex flex-col">
        <!-- Header -->
        <div class="border-b border-gray-200 p-4">
            <div v-if="hasActiveConversation" class="flex items-center justify-between">
                <div>
                    <h1 class="text-lg font-semibold text-gray-900">
                        {{ activeConversation.title }}
                    </h1>
                    <p class="text-sm text-gray-500">
                        Model: {{ activeConversation.model_name }}
                    </p>
                </div>
            </div>
            <div v-else-if="state.isCreatingConversation" class="text-center">
                <h1 class="text-lg font-semibold text-gray-900">
                    New Conversation
                </h1>
                <p class="text-sm text-gray-500">
                    {{ isStreaming ? 'AI is responding...' : 'Creating your conversation...' }}
                </p>
            </div>
            <div v-else class="text-center">
                <h1 class="text-lg font-semibold text-gray-900">
                    Start a new conversation
                </h1>
                <p class="text-sm text-gray-500">
                    Choose a model and send your first message
                </p>
            </div>
        </div>

        <!-- Messages Area -->
        <div class="flex-1 overflow-hidden">
            <MessageList
                v-if="shouldShowMessages"
                :messages="state.messages"
                :is-streaming="isStreaming"
            />
            <div v-else class="h-full flex items-center justify-center">
                <div class="text-center text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No conversation selected</h3>
                    <p class="mt-1 text-sm text-gray-500">Start a new chat to begin messaging</p>
                </div>
            </div>
        </div>

        <!-- Message Input -->
        <div class="border-t border-gray-200 p-4">
            <MessageInput
                ref="messageInputRef"
                :models="models"
                :selected-model="selectedModel"
                :conversation-id="activeConversation?.id"
                :user-instructions="userInstructions"
                :is-streaming="isStreaming"
                @update-model="updateModel"
                @message-sent="handleMessageSent"
            />
        </div>

        <!-- Instructions Modal -->
        <CustomInstructionsModal
            :show="state.showInstructionsModal"
            :user-instructions="userInstructions"
            @close="closeInstructions"
            @saved="onInstructionsSaved"
        />
    </div>
</template>
