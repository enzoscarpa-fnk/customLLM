<script setup>
import { ref, watch, onMounted } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import ConfirmationModal from '@/Components/ConfirmationModal.vue'
import IconBehavior from '@/Components/icons/IconBehavior.vue';

const props = defineProps({
    modelValue: String,
    existingData: Object
})

const emit = defineEmits(['update:modelValue', 'dataUpdated', 'show-success', 'show-error'])

const behavior = ref(props.modelValue || '')
const isEditing = ref(false)
const isLoading = ref(false)

const existingBehavior = ref('')

const showDeleteConfirmation = ref(false)

const presets = [
    {
        name: "Professional & Formal",
        description: "Formal tone with data-backed explanations",
        template: "Use a professional and formal tone. Provide explanations backed by data and recent research. Structure responses clearly with proper citations when relevant."
    },
    {
        name: "Casual & Friendly",
        description: "Conversational and approachable style",
        template: "Use a casual, friendly tone. Explain things in simple terms with analogies and examples. Keep the conversation light and engaging while being helpful."
    },
    {
        name: "Educational & Detailed",
        description: "Comprehensive explanations with examples",
        template: "Provide detailed, educational responses. Break down complex concepts step-by-step. Include practical examples and analogies to make learning intuitive."
    },
    {
        name: "Concise & Direct",
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
        router.post('/instructions/update', {
            type: 'behavior',
            data: behavior.value
        }, {
            preserveState: true,
            onSuccess: (page) => {
                existingBehavior.value = behavior.value
                isEditing.value = false
                emit('dataUpdated', page.props.userInstructions)
                emit('show-success', 'Behavior settings updated successfully!')
            },
            onError: (errors) => {
                console.error('Error saving changes:', errors)
                emit('show-error', 'Failed to save behavior settings. Please try again.')
            }
        })
    } catch (error) {
        console.error('Error saving changes:', error)
        emit('show-error', 'An error occurred while saving behavior settings.')
    } finally {
        isLoading.value = false
    }
}

const deleteExisting = () => {
    showDeleteConfirmation.value = true
}

const confirmDelete = async () => {
    showDeleteConfirmation.value = false
    isLoading.value = true

    try {
        router.post('/instructions/update', {
            type: 'behavior'
        }, {
            preserveState: true,
            onSuccess: (page) => {
                existingBehavior.value = ''
                behavior.value = ''
                isEditing.value = false
                emit('dataUpdated', page.props.userInstructions)
                emit('show-success', 'Behavior settings deleted successfully!')
            },
            onError: (errors) => {
                console.error('Error deleting:', errors)
                emit('show-error', 'Failed to delete behavior settings. Please try again.')
            }
        })
    } catch (error) {
        console.error('Error deleting:', error)
        emit('show-error', 'An error occurred while deleting behavior settings.')
    } finally {
        isLoading.value = false
    }
}

const cancelDelete = () => {
    showDeleteConfirmation.value = false
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
            <h3 class="text-lg font-medium text-yellow-400 flex items-center">
                <IconBehavior class="mr-2 mb-0.5 w-5 h-5" />
                Assistant Behavior
            </h3>
            <p class="mt-1 text-sm text-neutral-400">
                Define how you want the assistant to interact with you. This includes tone, response format, and explanation style.
            </p>
        </div>

        <!-- Current Behavior Section -->
        <div v-if="existingBehavior && existingBehavior.trim() && !isEditing" class="bg-yellow-400 p-4 [clip-path:polygon(0_0,100%_0,100%_100%,15px_100%,0_calc(100%-14px))]">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <h4 class="text-sm font-medium text-neutral-900 mb-2 flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Current Behavior Settings
                    </h4>
                    <div class="text-sm text-neutral-200 bg-neutral-800 p-3 [clip-path:polygon(0_0,100%_0,100%_100%,15px_100%,0_calc(100%-14px))]">
                        <p class="whitespace-pre-wrap">{{ existingBehavior }}</p>
                    </div>
                </div>
                <div class="ml-4 flex space-x-2">
                    <button
                        @click="editExisting"
                        :disabled="isLoading"
                        class="p-1 text-neutral-800 hover:text-yellow-400 hover:bg-neutral-800 transition-colors disabled:opacity-50 [clip-path:polygon(0_0,100%_0,100%_100%,8px_100%,0_16px)]"
                        title="Edit behavior settings"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </button>
                    <button
                        @click="deleteExisting"
                        :disabled="isLoading"
                        class="p-1 text-rose-600 hover:text-rose-600 hover:bg-neutral-800 transition-colors disabled:opacity-50 [clip-path:polygon(0_0,100%_0,100%_100%,8px_100%,0_16px)]"
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
        <div v-else-if="!existingBehavior || !existingBehavior.trim()" class="bg-neutral-800 p-4 text-center [clip-path:polygon(0_0,100%_0,100%_100%,15px_100%,0_calc(100%-14px))]">
            <div class="text-neutral-300 mb-2">
                <svg class="mx-auto h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
            </div>
            <p class="text-sm text-neutral-300">No behavior settings configured yet</p>
            <p class="text-xs text-neutral-400 mt-1">Choose a preset or create custom behavior below</p>
        </div>

        <!-- Quick Presets -->
        <div v-if="!isEditing">
            <div class="text-md font-semibold text-yellow-400 mb-2 flex items-center">
                <svg class="w-5 h-5 mr-2 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Quick Presets
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div
                    v-for="preset in presets"
                    :key="preset.name"
                    class="p-4 bg-neutral-800 text-yellow-400 hover:text-neutral-900 hover:bg-yellow-400 transition-colors cursor-pointer [clip-path:polygon(0_0,100%_0,100%_100%,15px_100%,0_calc(100%-14px))]"
                    @click="applyPreset(preset)"
                >
                    <div class="flex items-center mb-2">
                        <h5 class="font-medium">{{ preset.name }}</h5>
                    </div>
                    <p class="text-sm text-neutral-500">{{ preset.description }}</p>
                </div>
            </div>
        </div>

        <!-- Custom Behavior Input -->
        <div v-if="isEditing || !existingBehavior || !existingBehavior.trim()">
            <label for="behavior" class="block text-sm font-medium text-neutral-400 mb-2">
                {{ existingBehavior && existingBehavior.trim() ? 'Edit Behavior Instructions' : 'Custom Behavior Instructions' }}
            </label>
            <textarea
                id="behavior"
                v-model="behavior"
                rows="8"
                class="w-full border-yellow-400 shadow-sm text-sm text-neutral-800 [clip-path:polygon(0_0,100%_0,100%_100%,15px_100%,0_calc(100%-14px))]"
                placeholder="Describe how you want the assistant to behave, communicate, and format responses..."
                :disabled="isLoading"
            ></textarea>
            <p class="mt-2 text-xs text-neutral-400">
                {{ behavior.length }}/2000 characters
            </p>

            <!-- Action Buttons for Editing -->
            <div v-if="isEditing" class="flex space-x-3 mt-4">
                <button
                    @click="saveChanges"
                    :disabled="isLoading || !behavior.trim()"
                    class="px-4 py-2 bg-cyan-700 text-white hover:bg-cyan-800 focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed [clip-path:polygon(0_0,100%_0,100%_100%,15px_100%,0_26px)]"
                >
                    <span v-if="isLoading">Saving...</span>
                    <span v-else>Save Changes</span>
                </button>
                <button
                    @click="cancelEdit"
                    :disabled="isLoading"
                    class="px-4 py-2 bg-gray-300 text-gray-700 hover:bg-gray-400 focus:outline-none disabled:opacity-50 [clip-path:polygon(0_0,100%_0,100%_100%,15px_100%,0_26px)]"
                >
                    Cancel
                </button>
            </div>
        </div>

        <!-- Additional Options -->
        <div v-if="!isEditing">
            <div class="text-md font-semibold text-yellow-400 mb-2 flex items-center">
                <svg class="w-6 h-6 mr-2 mb-0.5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Additional Options
            </div>
            <div class="space-y-4">
                <div v-for="category in customOptions" :key="category.label">
                    <label class="block text-sm font-medium text-neutral-400 mb-2">
                        {{ category.label }}
                    </label>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="option in category.options"
                            :key="option"
                            @click="addCustomOption(option)"
                            class="px-3 py-1 text-sm bg-neutral-800 text-neutral-200 hover:bg-yellow-400 hover:text-neutral-900 transition-colors [clip-path:polygon(0_0,100%_0,100%_100%,8px_100%,0_20px)]"
                        >
                            {{ option }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Preview -->
        <div v-if="behavior && behavior !== existingBehavior && !isEditing" class="bg-neutral-800 p-4 [clip-path:polygon(0_0,100%_0,100%_100%,15px_100%,0_calc(100%-14px))]">
            <h4 class="text-sm font-medium text-yellow-400 mb-2 flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                Preview Changes
            </h4>
            <div class="text-sm text-neutral-200 bg-yellow-400 text-neutral-900 p-3 [clip-path:polygon(0_0,100%_0,100%_100%,15px_100%,0_calc(100%-14px))]">
                <p class="whitespace-pre-wrap">{{ behavior }}</p>
            </div>
        </div>

        <!-- Confirmation modal -->
        <ConfirmationModal
            :show="showDeleteConfirmation"
            @close="cancelDelete"
            max-width="md"
        >
            <template #title>
                Delete Behavior Settings
            </template>

            <template #content>
                Are you sure you want to delete your behavior settings? This action cannot be undone.
            </template>

            <template #footer>
                <button
                    @click="cancelDelete"
                    class="px-4 py-2 mr-3 text-sm font-medium text-neutral-200 bg-neutral-700 hover:bg-neutral-800 focus:outline-none transition-colors [clip-path:polygon(0_0,100%_0,100%_100%,12px_100%,0_26px)]"
                >
                    Keep
                </button>
                <button
                    @click="confirmDelete"
                    :disabled="isLoading"
                    class="px-4 py-2 text-sm font-medium text-neutral-100 bg-pink-600 hover:bg-pink-700 focus:outline-none transition-colors [clip-path:polygon(0_0,100%_0,100%_100%,12px_100%,0_26px)]"
                >
                    <span v-if="isLoading">Deleting...</span>
                    <span v-else>Delete</span>
                </button>
            </template>
        </ConfirmationModal>
    </div>
</template>
