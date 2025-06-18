<script setup>
import { router } from '@inertiajs/vue3'

const props = defineProps({
    conversations: Array,
    activeConversation: Object
})

const emit = defineEmits(['selectConversation', 'openInstructions'])

const selectConversation = (conversation) => {
    emit('selectConversation', conversation)
}

const openInstructions = () => {
    console.log('=== Sidebar openInstructions ===')
    console.log('Emitting openInstructions event')
    console.log('===============================')
    emit('openInstructions')
}

const deleteConversation = (conversation, event) => {
    event.stopPropagation()

    if (confirm('Are you sure you want to delete this conversation?')) {
        router.delete(`/chat/${conversation.id}`, {
            preserveState: false, // This will refresh the page data
            onSuccess: () => {
                console.log('Conversation deleted successfully')
            },
            onError: (errors) => {
                console.error('Error deleting conversation:', errors)
                alert('Failed to delete conversation. Please try again.')
            }
        })
    }
}
</script>

<template>
    <div class="h-full flex flex-col bg-gray-50">
        <!-- Header -->
        <div class="p-4 border-b border-gray-200 bg-white">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-lg font-semibold text-gray-900">Conversations</h2>
            </div>

            <!-- Settings Button -->
            <button
                @click="openInstructions"
                class="w-full flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md transition-colors"
            >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                Custom Instructions
            </button>
        </div>

        <!-- Conversations List -->
        <div class="flex-1 overflow-y-auto">
            <div v-if="conversations && conversations.length > 0" class="p-2 space-y-1">
                <div
                    v-for="conversation in conversations"
                    :key="conversation.id"
                    @click="selectConversation(conversation)"
                    class="group flex items-center justify-between p-3 rounded-lg cursor-pointer transition-colors"
                    :class="{
                        'bg-blue-100 border border-blue-200': activeConversation?.id === conversation.id,
                        'hover:bg-gray-100': activeConversation?.id !== conversation.id
                    }"
                >
                    <div class="flex-1 min-w-0">
                        <h3 class="text-sm font-medium text-gray-900 truncate">
                            {{ conversation.title || 'New Chat' }}
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ conversation.updated_at ? new Date(conversation.updated_at).toLocaleDateString() : '' }}
                        </p>
                    </div>
                    <button
                        @click="deleteConversation(conversation, $event)"
                        class="opacity-0 group-hover:opacity-100 p-1 text-gray-400 hover:text-red-500 transition-all"
                        title="Delete conversation"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="p-6 text-center">
                <div class="text-gray-400 mb-3">
                    <svg class="mx-auto h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                </div>
                <p class="text-sm text-gray-500">No conversations yet</p>
                <p class="text-xs text-gray-400 mt-1">Start a new chat to begin</p>
            </div>
        </div>
    </div>
</template>
