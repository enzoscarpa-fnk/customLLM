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
        <div class="flex flex-col sm:flex-row sm:items-end space-y-2 sm:space-y-0 sm:space-x-3">
            <label class="block text-sm font-semibold text-yellow-400 sm:mb-3 shrink-0">
                AI Model
            </label>
            <select
                v-model="form.model"
                @change="updateModel(form.model)"
                class="w-full sm:w-auto appearance-none bg-yellow-400 text-neutral-900 px-4 py-2 pr-8 focus:border-yellow-400 focus:outline-none [clip-path:polygon(0_0,100%_0,100%_100%,15px_100%,0_calc(100%-13px))]"
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
                class="appearance-none resize-none w-full border-gray-300 shadow-sm focus:ring-yellow-400 text-sm pr-12 [clip-path:polygon(0_0,100%_0,100%_calc(100%-16px),calc(100%-18px)_100%,0_100%)]"
                placeholder="Type your message..."
                :disabled="isStreaming"
            ></textarea>

            <!-- Send Button -->
            <button
                type="submit"
                :disabled="isStreaming"
                class="[clip-path:polygon(50%_0,100%_50%,50%_100%,0_50%)] absolute bottom-8 right-2 inline-flex items-center p-2 bg-neutral-800 border border-transparent rounded-md font-semibold text-neutral-200  hover:text-yellow-400 focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <svg v-if="isStreaming" class="animate-spin h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg v-else class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <polygon points="12,2 22,12 18,16 12,10 6,16 2,12" />
                </svg>
            </button>
        </div>
    </form>
</template>
