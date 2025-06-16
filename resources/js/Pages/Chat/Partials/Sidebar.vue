<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
    conversations: Array,
    activeConversation: Object
})

const emit = defineEmits(['select-conversation'])

const sortedConversations = computed(() => {
    return props.conversations.sort((a, b) =>
        new Date(b.last_message_at) - new Date(a.last_message_at)
    )
})

const selectConversation = (conversation) => {
    emit('select-conversation', conversation)
}

const startNewChat = () => {
    router.get(route('chat.index'))
}

const deleteConversation = (conversation, event) => {
    event.stopPropagation()

    if (confirm('Are you sure you want to delete this conversation?')) {
        router.delete(route('chat.destroy', conversation.id), {
            preserveState: false, // Force full page reload to update the conversations list
            onSuccess: () => {
                // Optionally redirect to chat index if the user deleted the active conversation
                if (props.activeConversation?.id === conversation.id) {
                    router.get(route('chat.index'))
                }
            }
        })
    }
}

const formatDate = (dateString) => {
    const date = new Date(dateString)
    const now = new Date()
    const diffTime = Math.abs(now - date)
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))

    if (diffDays === 1) return 'Today'
    if (diffDays === 2) return 'Yesterday'
    if (diffDays <= 7) return `${diffDays} days ago`
    return date.toLocaleDateString()
}
</script>

<template>
    <div class="h-full flex flex-col">
        <!-- New Chat Button -->
        <div class="p-4 border-b border-gray-200">
            <button
                @click="startNewChat"
                class="w-full flex items-center justify-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
            >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                New Chat
            </button>
        </div>

        <!-- Conversations List -->
        <div class="flex-1 overflow-y-auto">
            <div v-if="sortedConversations.length === 0" class="p-4 text-gray-500 text-center">
                No conversations yet
            </div>

            <div v-else class="space-y-1 p-2">
                <div
                    v-for="conversation in sortedConversations"
                    :key="conversation.id"
                    @click="selectConversation(conversation)"
                    class="group p-3 rounded-lg cursor-pointer transition-colors hover:bg-gray-100"
                    :class="{
                        'bg-blue-50 border-l-4 border-blue-500': activeConversation?.id === conversation.id,
                        'hover:bg-gray-50': activeConversation?.id !== conversation.id
                    }"
                >
                    <div class="flex justify-between items-start">
                        <div class="flex-1 min-w-0">
                            <h3 class="text-sm font-medium text-gray-900 truncate">
                                {{ conversation.title }}
                            </h3>
                            <p class="text-xs text-gray-500 mt-1">
                                {{ formatDate(conversation.last_message_at) }}
                            </p>
                        </div>
                        <div class="ml-2 flex-shrink-0">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-200 text-gray-800">
                                {{ conversation.model_name.split('/').pop() }}
                            </span>
                            <button
                                @click="deleteConversation(conversation, $event)"
                                class="ml-2 align-sub opacity-0 group-hover:opacity-100 p-1 text-red-500 hover:text-red-700 hover:bg-gray-200 rounded transition-all"
                                title="Delete conversation"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
