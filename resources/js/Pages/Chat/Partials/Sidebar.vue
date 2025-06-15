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
                    class="p-3 rounded-lg cursor-pointer transition-colors hover:bg-gray-100"
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
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                {{ conversation.model_name.split('/').pop() }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
