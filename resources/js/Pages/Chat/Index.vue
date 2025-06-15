<script setup>
import { ref, onMounted, watch } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Sidebar from './Partials/Sidebar.vue'
import ChatArea from './Partials/ChatArea.vue'
import { useChat } from '@/Composables/useChat'

const props = defineProps({
    conversations: Array,
    activeConversation: Object,
    messages: Array,
    models: Array,
    userPreferredModel: String
})

const {
    conversations,
    activeConversation,
    messages,
    selectedModel,
    initializeSelectedModel,
    setActiveConversation,
    updateSelectedModel
} = useChat()

onMounted(() => {
    conversations.value = props.conversations
    activeConversation.value = props.activeConversation
    messages.value = props.messages

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
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
