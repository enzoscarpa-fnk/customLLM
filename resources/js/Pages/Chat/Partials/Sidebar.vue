<script setup>
import { ref, inject } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
    conversations: Array,
    activeConversation: Object
})

const emit = defineEmits(['selectConversation', 'openInstructions', 'newConversation'])

const sidebar = inject('sidebar')

const isCreating = ref(false)

const createNewConversation = async () => {
    isCreating.value = true

    try {
        router.visit('/chat', {
            onSuccess: () => {
                emit('newConversation')
                if (window.innerWidth < 768) {
                    sidebar.close()
                }
            }
        })
    } finally {
        isCreating.value = false
    }
}

const selectConversation = (conversation) => {
    emit('selectConversation', conversation)
    if (window.innerWidth < 768) {
        sidebar.close()
    }
}

const openInstructions = () => {
    emit('openInstructions')
    if (window.innerWidth < 768) {
        sidebar.close()
    }
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
    <div
        :class="[
            'h-full w-[360px] flex flex-col bg-neutral-900 text-neutral-200 transition-transform duration-300 ease-in-out',
            'fixed md:relative inset-y-0 left-0 z-40',
            sidebar.isOpen.value ? 'translate-x-0' : '-translate-x-full md:translate-x-0'
        ]"
    >
        <!-- Header -->
        <div class="p-4 border-b-2 border-r-2 border-yellow-400 bg-neutral-900">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-lg font-semibold">Conversations</h2>

                <button
                    @click="sidebar.close()"
                    class="md:hidden p-1 text-neutral-400 hover:text-yellow-400 transition-colors"
                    title="Close conversations"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="p-2">
                <button
                    @click="createNewConversation"
                    class="w-[280px] flex items-center justify-center bg-yellow-400 text-stone-900 text-sm font-semibold px-6 py-3 [clip-path:polygon(0_0,100%_0,100%_100%,15px_100%,0_30px)] hover:bg-amber-400 transition-colors"
                    :disabled="isCreating"
                >
                    <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="square" stroke-linejoin="bevel" stroke-width="3" d="M12 4v16m8-8H4"></path>
                    </svg>
                    {{ isCreating ? 'Creating...' : 'NEW CHAT' }}
                </button>
            </div>

            <!-- Settings Button -->
            <button
                @click="openInstructions"
                class="w-[280px] flex items-center ml-2 px-6 py-2 [clip-path:polygon(0_0,100%_0,100%_100%,15px_100%,0_22px)] text-sm text-yellow-400 hover:bg-yellow-400 hover:text-stone-900 transition-colors"
            >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                Custom Instructions
            </button>
        </div>

        <!-- Conversations List -->
        <div class="flex-1 overflow-y-auto scrollbar scrollbar-w-2 scrollbar-track-neutral-900 scrollbar-thumb-stone-600 h-full [direction:rtl]">
            <div v-if="conversations && conversations.length > 0" class="relative py-2 pl-2 space-y-1 before:content-[''] before:absolute before:top-0 before:bottom-0 before:right-0 before:w-[2px] before:bg-yellow-400 [direction:ltr]">
                <div
                    v-for="conversation in conversations"
                    :key="conversation.id"
                    @click="selectConversation(conversation)"
                    class="z-10 relative group flex items-center justify-between py-3 pl-3 cursor-pointer transition-colors"
                    :class="{
                        'bg-neutral-800 border-neutral-800 [clip-path:polygon(0_0,100%_0,100%_100%,15px_100%,0_50px)] text-yellow-400 transition-all': activeConversation?.id === conversation.id,
                        'hover:bg-neutral-800 border-r-2 border-yellow-400': activeConversation?.id !== conversation.id
                    }"
                >
                    <div class="flex-1 min-w-0">
                        <h3 class="text-sm font-medium truncate">
                            {{ conversation.title || 'New Chat' }}
                        </h3>
                        <p class="text-xs text-neutral-400 mt-1">
                            {{ conversation.updated_at ? new Date(conversation.updated_at).toLocaleDateString() : '' }} | {{ conversation.model_name }}
                        </p>
                    </div>
                    <button
                        @click="deleteConversation(conversation, $event)"
                        class="opacity-100 md:opacity-0 md:group-hover:opacity-100 p-1 text-gray-400 hover:text-red-500 transition-all"
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
