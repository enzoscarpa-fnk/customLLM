<script setup>
import { ref, computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { useChat } from '@/Composables/useChat'

const props = defineProps({
    models: Array,
    selectedModel: String,
    conversationId: Number
})

const emit = defineEmits(['update-model'])

const { createNewConversation, sendMessage, isLoading } = useChat()

const form = useForm({
    message: '',
    model: props.selectedModel
})

// Watch for changes in selectedModel prop and update form
watch(() => props.selectedModel, (newModel) => {
    if (newModel && form.model !== newModel) {
        form.model = newModel
    }
}, { immediate: true })

const isNewConversation = computed(() => {
    return !props.conversationId
})

const submit = async () => {
    if (!form.message.trim()) return

    const message = form.message
    const model = form.model

    // Update parent component about model change
    emit('update-model', model)

    if (isNewConversation.value) {
        await createNewConversation(message, model)
    } else {
        await sendMessage(message, props.conversationId, model)
    }

    form.reset('message')
}

const updateModel = (model) => {
    form.model = model
    emit('update-model', model)
}

const handleKeydown = (event) => {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault()
        submit()
    }
}
</script>

<template>
    <form @submit.prevent="submit" class="space-y-4">
        <!-- Model Selector -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                AI Model
            </label>
            <select
                v-model="form.model"
                @change="updateModel(form.model)"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                :disabled="isLoading"
            >
                <option value="" disabled>Select a model</option>
                <option
                    v-for="model in models"
                    :key="model.id"
                    :value="model.id"
                >
                    {{ model.name }}
                </option>
            </select>
        </div>

        <!-- Message Input -->
        <div class="relative">
            <textarea
                v-model="form.message"
                @keydown="handleKeydown"
                rows="3"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 pr-12"
                placeholder="Type your message... (Enter to send, Shift+Enter for new line)"
                :disabled="isLoading"
            ></textarea>

            <!-- Send Button -->
            <button
                type="submit"
                :disabled="isLoading || !form.message.trim()"
                class="absolute bottom-2 right-2 inline-flex items-center p-2 bg-blue-600 border border-transparent rounded-md font-semibold text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <svg v-if="isLoading" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                </svg>
            </button>
        </div>

        <!-- Loading Indicator -->
        <div v-if="isLoading" class="flex items-center justify-center text-sm text-gray-500">
            <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Generating response...
        </div>
    </form>
</template>
