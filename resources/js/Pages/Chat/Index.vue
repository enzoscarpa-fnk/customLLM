<script setup>
import { ref, onMounted, watch, nextTick } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Sidebar from './Partials/Sidebar.vue'
import ChatArea from './Partials/ChatArea.vue'
import CustomInstructionsModal from './Partials/CustomInstructions/CustomInstructionsModal.vue'
import { useChat } from '@/Composables/useChat'

// Modal state
const showInstructionsModal = ref(false)

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
}

const closeInstructionsModal = () => {
    showInstructionsModal.value = false
}

const handleInstructionsSaved = () => {
    console.log('Instructions saved, refreshing page...')
    window.location.reload()
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

    console.log('User preferred model:', props.userPreferredModel)
})
</script>

<template>
    <AppLayout title="Chat">
        <div class="py-2">
            <div class="mx-auto sm:px-6 lg:px-10">
                <div class="bg-white overflow-hidden sm:rounded-2xl">

                    <div class="flex h-[800px]">
                        <!-- Sidebar -->
                        <div class="w-1/4 border-r border-gray-200">
                            <Sidebar
                                :conversations="conversations"
                                :active-conversation="activeConversation"
                                @select-conversation="setActiveConversation"
                                @open-instructions="openInstructionsModal"
                                @new-conversation="handleNewConversation"
                            />
                        </div>

                        <!-- Chat Area -->
                        <div class="flex-1">
                            <ChatArea
                                ref="chatAreaRef"
                                :active-conversation="activeConversation"
                                :messages="messages"
                                :models="models"
                                :selected-model="selectedModel"
                                @update-model="updateSelectedModel"
                                @open-instructions="openInstructionsModal"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Custom Instructions Modal -->
        <CustomInstructionsModal
            :show="showInstructionsModal"
            :user-instructions="props.userInstructions"
            @close="closeInstructionsModal"
            @saved="handleInstructionsSaved"
        />
    </AppLayout>
</template>
