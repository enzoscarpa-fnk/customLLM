<script setup>
import { ref, onMounted, watch } from 'vue'
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

onMounted(() => {
    conversations.value = props.conversations
    activeConversation.value = props.activeConversation
    messages.value = props.messages

    // Debug: Log the userInstructions prop
    console.log('=== Index.vue onMounted ===')
    console.log('userInstructions prop:', props.userInstructions)
    console.log('conversations:', props.conversations?.length || 0)
    console.log('activeConversation:', props.activeConversation?.id || 'none')
    console.log('messages:', props.messages?.length || 0)
    console.log('==========================')

    // Initialize selectedModel with user preference or active conversation model
    const initialModel = props.activeConversation?.model_name || props.userPreferredModel
    if (initialModel) {
        selectedModel.value = initialModel
    }
})

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
    // Refresh the page to get updated instructions
    console.log('Instructions saved, refreshing page...')
    window.location.reload()
}
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
                            />
                        </div>

                        <!-- Chat Area -->
                        <div class="flex-1">
                            <ChatArea
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
