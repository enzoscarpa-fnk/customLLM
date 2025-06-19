<script setup>
import { ref, watch, onMounted } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

const props = defineProps({
    modelValue: String,
    existingData: Object
})

const emit = defineEmits(['update:modelValue', 'dataUpdated'])

const behavior = ref(props.modelValue || '')
const isEditing = ref(false)
const isLoading = ref(false)

const existingBehavior = ref('')

const presets = [
    {
        name: "Professional & Formal",
        icon: "👔",
        description: "Formal tone with data-backed explanations",
        template: "Use a professional and formal tone. Provide explanations backed by data and recent research. Structure responses clearly with proper citations when relevant."
    },
    {
        name: "Casual & Friendly",
        icon: "😊",
        description: "Conversational and approachable style",
        template: "Use a casual, friendly tone. Explain things in simple terms with analogies and examples. Keep the conversation light and engaging while being helpful."
    },
    {
        name: "Educational & Detailed",
        icon: "📚",
        description: "Comprehensive explanations with examples",
        template: "Provide detailed, educational responses. Break down complex concepts step-by-step. Include practical examples and analogies to make learning intuitive."
    },
    {
        name: "Concise & Direct",
        icon: "⚡",
        description: "Brief, to-the-point responses",
        template: "Keep responses concise and direct. Focus on key points without unnecessary elaboration. Use bullet points or numbered lists when appropriate."
    }
]

const customOptions = [
    {
        label: "Response Format",
        options: ["Paragraphs", "Bullet Points", "Numbered Lists", "Mixed Format"]
    },
    {
        label: "Explanation Style",
        options: ["Step-by-step", "With Examples", "With Analogies", "Technical Details"]
    },
    {
        label: "Tone Preference",
        options: ["Professional", "Casual", "Encouraging", "Neutral"]
    }
]

// Initialize existing data
const initializeExistingData = () => {
    existingBehavior.value = props.existingData?.behavior || ''
}

onMounted(() => {
    initializeExistingData()
})

watch(behavior, (newValue) => {
    emit('update:modelValue', newValue)
})

watch(() => props.modelValue, (newValue) => {
    behavior.value = newValue || ''
})

watch(() => props.existingData, (newValue) => {
    initializeExistingData()
}, { deep: true })

const applyPreset = (preset) => {
    if (behavior.value) {
        behavior.value += '\n\n' + preset.template
    } else {
        behavior.value = preset.template
    }
}

const addCustomOption = (option) => {
    const addition = `\n\nPrefer ${option.toLowerCase()} in responses.`
    if (!behavior.value.includes(option.toLowerCase())) {
        behavior.value += addition
    }
}

const editExisting = () => {
    behavior.value = existingBehavior.value
    isEditing.value = true
    setTimeout(() => {
        document.getElementById('behavior')?.focus()
    }, 100)
}

const saveChanges = async () => {
    if (!behavior.value.trim()) {
        return
    }

    isLoading.value = true

    try {
        router.post(route('instructions.update'), {
            type: 'behavior',
            data: behavior.value
        }, {
            preserveState: true,
            onSuccess: (page) => {
                existingBehavior.value = behavior.value
                isEditing.value = false
                emit('dataUpdated', page.props.userInstructions)
            },
            onError: (errors) => {
                console.error('Error saving changes:', errors)
            }
        })
    } catch (error) {
        console.error('Error saving changes:', error)
    } finally {
        isLoading.value = false
    }
}

const deleteExisting = async () => {
    if (!confirm('Are you sure you want to delete your behavior settings?')) {
        return
    }

    isLoading.value = true

    try {
        router.delete(route('instructions.delete'), {
            data: { type: 'behavior' },
            preserveState: true,
            onSuccess: (page) => {
                existingBehavior.value = ''
                behavior.value = ''
                isEditing.value = false
                emit('dataUpdated', page.props.userInstructions)
            },
            onError: (errors) => {
                console.error('Error deleting:', errors)
            }
        })
    } catch (error) {
        console.error('Error deleting:', error)
    } finally {
        isLoading.value = false
    }
}

const cancelEdit = () => {
    behavior.value = existingBehavior.value
    isEditing.value = false
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <h3 class="text-lg font-medium text-gray-900 flex items-center">
                <span class="mr-2">🎯</span>
                Assistant Behavior
            </h3>
            <p class="mt-1 text-sm text-gray-600">
                Define how you want the assistant to interact with you. This includes tone, response format, and explanation style.
            </p>
        </div>

        <!-- Current Behavior Section -->
        <div v-if="existingBehavior && existingBehavior.trim() && !isEditing" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <h4 class="text-sm font-medium text-blue-900 mb-2 flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Current Behavior Settings
                    </h4>
                    <div class="text-sm text-blue-800 bg-white rounded p-3 border border-blue-200">
                        <p class="whitespace-pre-wrap">{{ existingBehavior }}</p>
                    </div>
                </div>
                <div class="ml-4 flex space-x-2">
                    <button
                        @click="editExisting"
                        :disabled="isLoading"
                        class="p-1 text-blue-600 hover:text-blue-800 hover:bg-blue-100 rounded transition-colors disabled:opacity-50"
                        title="Edit behavior settings"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </button>
                    <button
                        @click="deleteExisting"
                        :disabled="isLoading"
                        class="p-1 text-red-500 hover:text-red-700 hover:bg-red-50 rounded transition-colors disabled:opacity-50"
                        title="Delete behavior settings"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Empty State for Current Behavior -->
        <div v-else-if="!existingBehavior || !existingBehavior.trim()" class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-center">
            <div class="text-gray-400 mb-2">
                <svg class="mx-auto h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
            </div>
            <p class="text-sm text-gray-500">No behavior settings configured yet</p>
            <p class="text-xs text-gray-400 mt-1">Choose a preset or create custom behavior below</p>
        </div>

        <!-- Quick Presets -->
        <div v-if="!isEditing">
            <h4 class="text-sm font-medium text-gray-900 mb-3">🚀 Quick Presets</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div
                    v-for="preset in presets"
                    :key="preset.name"
                    class="p-4 border border-gray-200 rounded-lg hover:border-blue-300 hover:bg-blue-50 transition-all cursor-pointer"
                    @click="applyPreset(preset)"
                >
                    <div class="flex items-center mb-2">
                        <span class="text-lg mr-2">{{ preset.icon }}</span>
                        <h5 class="font-medium text-gray-900">{{ preset.name }}</h5>
                    </div>
                    <p class="text-sm text-gray-600">{{ preset.description }}</p>
                </div>
            </div>
        </div>

        <!-- Custom Behavior Input -->
        <div v-if="isEditing || !existingBehavior || !existingBehavior.trim()">
            <label for="behavior" class="block text-sm font-medium text-gray-700 mb-2">
                {{ existingBehavior && existingBehavior.trim() ? 'Edit Behavior Instructions' : 'Custom Behavior Instructions' }}
            </label>
            <textarea
                id="behavior"
                v-model="behavior"
                rows="8"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                placeholder="Describe how you want the assistant to behave, communicate, and format responses..."
                :disabled="isLoading"
            ></textarea>
            <p class="mt-2 text-xs text-gray-500">
                {{ behavior.length }}/2000 characters
            </p>

            <!-- Action Buttons for Editing -->
            <div v-if="isEditing" class="flex space-x-3 mt-4">
                <button
                    @click="saveChanges"
                    :disabled="isLoading || !behavior.trim()"
                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span v-if="isLoading">Saving...</span>
                    <span v-else>Save Changes</span>
                </button>
                <button
                    @click="cancelEdit"
                    :disabled="isLoading"
                    class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 disabled:opacity-50"
                >
                    Cancel
                </button>
            </div>
        </div>

        <!-- Custom Options -->
        <div v-if="!isEditing">
            <h4 class="text-sm font-medium text-gray-900 mb-3">⚙️ Additional Options</h4>
            <div class="space-y-4">
                <div v-for="category in customOptions" :key="category.label">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        {{ category.label }}
                    </label>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="option in category.options"
                            :key="option"
                            @click="addCustomOption(option)"
                            class="px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded-full hover:bg-blue-100 hover:text-blue-700 transition-colors"
                        >
                            {{ option }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Preview -->
        <div v-if="behavior && behavior !== existingBehavior && !isEditing" class="bg-yellow-50 rounded-lg p-4">
            <h4 class="text-sm font-medium text-yellow-900 mb-2">📝 Preview Changes</h4>
            <div class="text-sm text-yellow-800 bg-white rounded p-3 border border-yellow-200">
                <p class="whitespace-pre-wrap">{{ behavior }}</p>
            </div>
        </div>
    </div>
</template>
