<script setup>
import { ref, computed, watch, nextTick, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
    models: Array,
    selectedModel: String,
    conversationId: Number,
    userInstructions: Object,
    isStreaming: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['update-model', 'message-sent'])

const textareaRef = ref(null)

defineExpose({
    focusTextarea: () => {
        focusTextarea()
    }
})

const form = useForm({
    message: '',
    model: ''
})

// Watch for changes in selectedModel prop and update form
watch(() => props.selectedModel, (newModel) => {
    if (newModel && form.model !== newModel) {
        form.model = newModel
    }
}, { immediate: true })

watch(() => props.models, (newModels) => {
    if (newModels && newModels.length > 0 && !form.model && !props.selectedModel) {
        const defaultModel = newModels[0].id
        form.model = defaultModel
        emit('update-model', defaultModel)
    }
}, { immediate: true })

// Focus textarea when streaming finishes
watch(() => props.isStreaming, (newStreaming, oldStreaming) => {
    if (oldStreaming && !newStreaming) {
        nextTick(() => {
            focusTextarea()
        })
    }
})

const focusTextarea = () => {
    if (textareaRef.value) {
        textareaRef.value.focus()
    }
}

const isNewConversation = computed(() => {
    return !props.conversationId
})

const isDisabled = computed(() => {
    return props.isStreaming || !form.message.trim()
})

const submit = () => {
    if (isDisabled.value) return

    const message = form.message
    const model = form.model

    console.log('Émission message-sent:', { message, model }); // ← DEBUG

    // Emit message immediately to parent for streaming handling
    emit('message-sent', {
        message: message,
        model: model,
        role: 'user',
        created_at: new Date().toISOString()
    })

    // Update parent component about model change
    emit('update-model', model)

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

onMounted(() => {
    const initialModel = props.selectedModel || props.models?.[0]?.id || ''
    if (initialModel) {
        form.model = initialModel
        if (!props.selectedModel && initialModel) {
            emit('update-model', initialModel)
        }
    }
    focusTextarea()
})
</script>

<template>
    <form @submit.prevent="submit" class="space-y-4">
        <!-- Model Selector -->
        <div class="flex items-end space-x-3">
            <label class="block text-sm font-medium text-gray-700 mb-2 shrink-0">
                AI Model
            </label>
            <select
                v-model="form.model"
                @change="updateModel(form.model)"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                :disabled="isStreaming"
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
                ref="textareaRef"
                v-model="form.message"
                @keydown="handleKeydown"
                rows="3"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 pr-12"
                placeholder="Type your message..."
                :disabled="isStreaming"
            ></textarea>

            <!-- Send Button -->
            <button
                type="submit"
                :disabled="isStreaming"
                class="absolute bottom-2 right-2 inline-flex items-center p-2 bg-blue-600 border border-transparent rounded-md font-semibold text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <svg v-if="isStreaming" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                </svg>
            </button>
        </div>

        <!-- Loading Indicator -->
        <div v-if="isStreaming" class="flex items-center justify-center text-sm text-gray-500">
            <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            AI is responding...
        </div>
    </form>
</template>
