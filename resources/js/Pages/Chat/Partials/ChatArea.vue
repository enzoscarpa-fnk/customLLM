<script setup>
import { ref, computed } from 'vue'
import MessageList from './MessageList.vue'
import MessageInput from './MessageInput.vue'

const props = defineProps({
    activeConversation: Object,
    messages: Array,
    models: Array,
    selectedModel: String
})

const emit = defineEmits(['update-model'])

const hasActiveConversation = computed(() => {
    return props.activeConversation !== null
})

const updateModel = (model) => {
    emit('update-model', model)
}
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
                v-if="hasActiveConversation"
                :messages="messages"
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
                :models="models"
                :selected-model="selectedModel"
                :conversation-id="activeConversation?.id"
                @update-model="updateModel"
            />
        </div>
    </div>
</template>
