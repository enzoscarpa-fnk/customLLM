<script setup>
import { ref, onMounted, watch, nextTick, inject } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Sidebar from './Partials/Sidebar.vue'
import ChatArea from './Partials/ChatArea.vue'
import CustomInstructionsModal from './Partials/CustomInstructions/CustomInstructionsModal.vue'
import NotificationBanner from '@/Components/NotificationBanner.vue'
import { useChat } from '@/Composables/useChat'

const defaultSidebar = {
    close: () => console.log('Sidebar close called'),
    open: () => console.log('Sidebar open called'),
    toggle: () => console.log('Sidebar toggle called'),
    isOpen: ref(false)
}

const sidebar = inject('sidebar', defaultSidebar)

// Modal state
const showInstructionsModal = ref(false)
const notification = ref({
    show: false,
    type: 'success',
    message: ''
})

const showNotification = (data) => {
    notification.value = {
        show: true,
        type: data.type,
        message: data.message
    }
}

const closeNotification = () => {
    notification.value.show = false
}

const {
    conversations,
    activeConversation,
    messages,
    selectedModel,
    setActiveConversation,
    updateSelectedModel
} = useChat()

const props = defineProps({
    conversations: Array,
    activeConversation: Object,
    messages: Array,
    models: Array,
    userPreferredModel: String,
    userInstructions: Object
})

const chatAreaRef = ref(null)

const handleNewConversation = () => {
    activeConversation.value = null
    messages.value = []

    nextTick(() => {
        if (chatAreaRef.value) {
            chatAreaRef.value.focusInput()
        }
    })
}

// Watch for changes in activeConversation to update selectedModel
watch(() => props.activeConversation, (newConversation) => {
    if (newConversation && newConversation.model_name) {
        selectedModel.value = newConversation.model_name
    }
}, { immediate: true })

const openInstructionsModal = () => {
    showInstructionsModal.value = true
    if (window.innerWidth < 768) {
        sidebar.close()
    }
}

const closeInstructionsModal = () => {
    showInstructionsModal.value = false
}

onMounted(() => {
    conversations.value = props.conversations
    activeConversation.value = props.activeConversation
    messages.value = props.messages

    let initialModel = null

    if (props.activeConversation?.model_name) {
        initialModel = props.activeConversation.model_name
    } else if (props.userPreferredModel) {
        initialModel = props.userPreferredModel
    } else if (props.models && props.models.length > 0) {
        initialModel = props.models[0].id
    }

    if (initialModel) {
        selectedModel.value = initialModel
    }
})
</script>

<template>
    <AppLayout title="Chat">
        <div class="h-[calc(100vh-65px)] flex flex-col">
            <div class="flex-1 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-10 py-2 min-h-0">
                <div class="bg-zinc-900 overflow-hidden h-full w-full">

                    <div class="flex h-full w-full">
                        <!-- Sidebar - Garde la logique originale pour desktop -->
                        <div class="hidden md:flex min-w-[300px] w-[360px] max-w-[360px]">
                            <Sidebar
                                :conversations="conversations"
                                :active-conversation="activeConversation"
                                @select-conversation="setActiveConversation"
                                @open-instructions="openInstructionsModal"
                                @new-conversation="handleNewConversation"
                            />
                        </div>

                        <!-- Sidebar Mobile - Séparée et positionnée en fixed -->
                        <div class="md:hidden">
                            <Sidebar
                                :conversations="conversations"
                                :active-conversation="activeConversation"
                                @select-conversation="setActiveConversation"
                                @open-instructions="openInstructionsModal"
                                @new-conversation="handleNewConversation"
                            />
                        </div>

                        <!-- Chat Area -->
                        <div class="flex-1 min-w-0 overflow-hidden bg-neutral-800">
                            <ChatArea
                                ref="chatAreaRef"
                                :active-conversation="activeConversation"
                                :messages="messages"
                                :models="models"
                                :selected-model="selectedModel"
                                :user-instructions="props.userInstructions"
                                @update-model="updateSelectedModel"
                                @open-instructions="openInstructionsModal"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notification Banner -->
        <NotificationBanner
            :show="notification.show"
            :type="notification.type"
            :message="notification.message"
            @close="closeNotification"
        />

        <!-- Custom Instructions Modal -->
        <CustomInstructionsModal
            :show="showInstructionsModal"
            :user-instructions="props.userInstructions"
            @close="closeInstructionsModal"
            @show-notification="showNotification"
        />
    </AppLayout>
</template>

