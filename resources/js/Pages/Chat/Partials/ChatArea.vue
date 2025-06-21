<script setup>
import { ref, computed, watch, nextTick, shallowRef } from 'vue'
import { useStream } from '@laravel/stream-vue'
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

const localMessages = ref([...props.messages])
const isCreatingConversation = ref(false)
const showInstructionsModal = ref(false)

const messageInputRef = ref(null)

// Utiliser shallowRef pour stocker l'instance useStream
const streamInstance = shallowRef(null)
const isStreaming = ref(false)

// Fonction pour créer une nouvelle instance de stream
const createStreamInstance = (url) => {
    console.log('🔄 Création nouvelle instance stream pour URL:', url)

    return useStream(url, {
        onData: (data) => {
            const lastMessage = localMessages.value[localMessages.value.length - 1]
            if (lastMessage && lastMessage.role === 'assistant') {
                lastMessage.content = (lastMessage.content || '') + data
            }
        },
        onFinish: () => {
            isStreaming.value = false
            if (!props.activeConversation) {
                window.location.reload()
            }
        },
        onError: (error) => {
            console.error('Streaming error:', error)
            isStreaming.value = false
            if (localMessages.value.length > 0) {
                const lastMessage = localMessages.value[localMessages.value.length - 1]
                if (lastMessage.role === 'assistant' && !lastMessage.content) {
                    localMessages.value.pop()
                }
            }
        },
    })
}

// Initialiser l'instance stream
const initializeStream = () => {
    const url = props.activeConversation && props.activeConversation.id
        ? `/chat/${props.activeConversation.id}/stream`
        : '/chat/stream'

    console.log('🚀 Initialisation stream avec URL:', url)
    streamInstance.value = createStreamInstance(url)
}

// Watcher pour recréer l'instance quand la conversation change
watch(() => props.activeConversation, (newConversation, oldConversation) => {
    console.log('👀 Conversation changée:', { old: oldConversation?.id, new: newConversation?.id })

    // Recréer l'instance stream avec la nouvelle URL
    nextTick(() => {
        initializeStream()
    })
}, { immediate: true })

// Fonction pour envoyer un message
const sendStreamMessage = (data) => {
    if (!streamInstance.value || isStreaming.value) {
        console.warn('⚠️ Stream non disponible ou déjà en cours')
        return
    }

    console.log('📤 Envoi message via stream:', data)
    isStreaming.value = true
    streamInstance.value.send(data)
}

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
    return hasActiveConversation.value || isCreatingConversation.value
})

const updateModel = (model) => {
    emit('update-model', model)
}

const handleMessageSent = (messageData) => {
    console.log('📨 handleMessageSent:', messageData)

    if (!hasActiveConversation.value) {
        isCreatingConversation.value = true
    }

    // 1. Ajouter le message utilisateur
    const userMessage = {
        id: 'temp-user-' + Date.now(),
        role: 'user',
        content: messageData.message,
        created_at: new Date().toISOString(),
    }
    localMessages.value.push(userMessage)

    // 2. Ajouter un message vide pour l'assistant
    const assistantMessage = {
        id: 'temp-assistant-' + Date.now(),
        role: 'assistant',
        content: '',
        created_at: new Date().toISOString(),
    }
    localMessages.value.push(assistantMessage)

    // 3. Envoyer via le stream
    sendStreamMessage({
        message: messageData.message,
        model: messageData.model,
    })
}

const closeInstructions = () => {
    showInstructionsModal.value = false
}

const onInstructionsSaved = () => {
    window.location.reload()
}

watch(() => props.messages, (newMessages) => {
    if (!isStreaming.value) {
        const hasTemporaryMessages = localMessages.value.some(msg =>
            typeof msg.id === 'string' && msg.id.startsWith('temp-')
        )

        if (!hasTemporaryMessages || newMessages.length > localMessages.value.length) {
            localMessages.value = [...newMessages]
        }
    }
}, { deep: true })

watch(() => props.activeConversation, (newConversation) => {
    if (!isStreaming.value) {
        localMessages.value = [...props.messages]
    }
    if (newConversation) {
        isCreatingConversation.value = false
    }
}, { immediate: true })
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
            <div v-else-if="isCreatingConversation" class="text-center">
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
                :messages="localMessages"
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
            :show="showInstructionsModal"
            :user-instructions="userInstructions"
            @close="closeInstructions"
            @saved="onInstructionsSaved"
        />
    </div>
</template>
