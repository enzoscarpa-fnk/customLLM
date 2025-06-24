<script setup>
import { ref, watch, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import IconCommands from '@/Components/icons/IconCommands.vue';

const props = defineProps({
    modelValue: Array,
    existingData: Object
})

const emit = defineEmits(['update:modelValue', 'dataUpdated'])

const commands = ref(props.modelValue || [])
const isLoading = ref(false)

const existingCommands = ref(props.existingData?.custom_commands || [])

// Watch for changes in existingData prop
watch(() => props.existingData?.custom_commands, (newValue) => {
    existingCommands.value = newValue || []
}, { deep: true, immediate: true })

const newCommand = ref({
    name: '',
    description: '',
    response: ''
})

const predefinedCommands = [
    {
        name: "/weather",
        description: "Get current weather and forecasts",
        response: "Display the current weather and forecast for the specified location or user's location."
    },
    {
        name: "/quote",
        description: "Get an inspirational quote",
        response: "Provide an inspirational quote related to the specified topic or a general motivational quote."
    },
    {
        name: "/summary",
        description: "Summarize content",
        response: "Create a concise summary of the provided text or topic, highlighting key points."
    },
    {
        name: "/explain",
        description: "Explain complex topics simply",
        response: "Break down complex topics into simple, easy-to-understand explanations with examples."
    },
    {
        name: "/brainstorm",
        description: "Generate creative ideas",
        response: "Generate creative ideas and suggestions for the specified topic or problem."
    },
    {
        name: "/feedback",
        description: "Provide constructive feedback",
        response: "Analyze the provided content and give constructive feedback with suggestions for improvement."
    }
]

const isValidCommand = computed(() => {
    return newCommand.value.name.startsWith('/') &&
        newCommand.value.name.length > 1 &&
        newCommand.value.description.trim() &&
        newCommand.value.response.trim()
})

watch(commands, (newValue) => {
    emit('update:modelValue', newValue)
}, { deep: true })

watch(() => props.modelValue, (newValue) => {
    commands.value = newValue || []
}, { deep: true })

const resetNewCommand = () => {
    newCommand.value = {
        name: '',
        description: '',
        response: ''
    }
}

const formatCommandName = () => {
    if (newCommand.value.name && !newCommand.value.name.startsWith('/')) {
        newCommand.value.name = '/' + newCommand.value.name
    }
}

const editExistingCommand = (command) => {
    newCommand.value = { ...command }
    setTimeout(() => {
        document.getElementById('command-name')?.focus()
    }, 100)
}

const removeExistingCommand = async (command) => {
    if (!confirm(`Are you sure you want to delete the command "${command.name}"?`)) {
        return
    }

    isLoading.value = true

    try {
        router.delete(route('instructions.deleteCommand'), {
            data: { command_name: command.name },
            preserveState: true,
            onSuccess: (page) => {
                emit('dataUpdated', page.props.userInstructions)
            },
            onError: (errors) => {
                console.error('Error deleting command:', errors)
            }
        })
    } catch (error) {
        console.error('Error deleting command:', error)
    } finally {
        isLoading.value = false
    }
}

// Fixed saveCommand method - properly merge instead of overwrite
const saveCommand = async (command) => {
    if (!command.name.startsWith('/') || !command.description.trim() || !command.response.trim()) {
        return
    }

    isLoading.value = true

    try {
        // Get current commands and properly merge
        const currentCommands = [...(existingCommands.value || [])]
        const existingIndex = currentCommands.findIndex(cmd => cmd.name === command.name)

        if (existingIndex !== -1) {
            // Update existing command
            currentCommands[existingIndex] = { ...command }
        } else {
            // Add new command
            currentCommands.push({ ...command })
        }

        router.post(route('instructions.update'), {
            type: 'custom_commands',
            data: currentCommands
        }, {
            preserveState: true,
            onSuccess: (page) => {
                emit('dataUpdated', page.props.userInstructions)
                resetNewCommand()
            },
            onError: (errors) => {
                console.error('Error saving command:', errors)
            }
        })
    } catch (error) {
        console.error('Error saving command:', error)
    } finally {
        isLoading.value = false
    }
}

const clearAllCommands = async () => {
    if (!confirm('Are you sure you want to delete all commands?')) {
        return
    }

    isLoading.value = true

    try {
        router.delete(route('instructions.delete'), {
            data: { type: 'custom_commands' },
            preserveState: true,
            onSuccess: (page) => {
                commands.value = []
                emit('dataUpdated', page.props.userInstructions)
            },
            onError: (errors) => {
                console.error('Error clearing commands:', errors)
            }
        })
    } catch (error) {
        console.error('Error clearing commands:', error)
    } finally {
        isLoading.value = false
    }
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <h3 class="text-lg font-medium text-yellow-400 flex items-center">
                <IconCommands class="mr-2 w-5 h-5" />
                Custom Commands
            </h3>
            <p class="mt-1 text-sm text-neutral-400">
                Create custom commands to quickly access specific types of responses or perform common tasks.
            </p>
        </div>

        <!-- Existing Commands -->
        <div v-if="existingCommands.length > 0" class="bg-yellow-400 p-4 [clip-path:polygon(0_0,100%_0,100%_100%,15px_100%,0_calc(100%-14px))]">
            <div class="flex items-center justify-between mb-3">
                <h4 class="text-sm font-medium text-neutral-900 flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Your Commands ({{ existingCommands.length }})
                </h4>
                <button
                    @click="clearAllCommands"
                    :disabled="isLoading"
                    class="text-xs text-rose-600 hover:text-rose-600 hover:bg-neutral-800 px-2 py-1 transition-colors disabled:opacity-50 [clip-path:polygon(0_0,100%_0,100%_100%,8px_100%,0_16px)]"
                >
                    Clear All
                </button>
            </div>
            <div class="space-y-3">
                <div
                    v-for="(command, index) in existingCommands"
                    :key="`existing-${command.name}-${index}`"
                    class="group p-4 bg-neutral-800 text-neutral-200 hover:text-yellow-400 hover:bg-neutral-900 transition-colors [clip-path:polygon(0_0,100%_0,100%_100%,15px_100%,0_calc(100%-16px))]"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center mb-2">
                                <code class="px-2 py-1 bg-yellow-400 text-neutral-900 text-sm font-mono [clip-path:polygon(0_0,100%_0,100%_100%,12px_100%,0_16px)]">
                                    {{ command.name }}
                                </code>
                                <span class="ml-2 text-sm font-medium">
                                    {{ command.description }}
                                </span>
                            </div>
                            <p class="text-sm text-neutral-500 mb-2">{{ command.response }}</p>
                        </div>
                        <div class="ml-4 flex space-x-2">
                            <button
                                @click="editExistingCommand(command)"
                                :disabled="isLoading"
                                class="p-1 text-neutral-200 hover:text-yellow-400 hover:bg-neutral-800 transition-colors disabled:opacity-50 [clip-path:polygon(0_0,100%_0,100%_100%,8px_100%,0_16px)]"
                                title="Edit command"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </button>
                            <button
                                @click="removeExistingCommand(command)"
                                :disabled="isLoading"
                                class="p-1 text-rose-600 hover:text-rose-600 hover:bg-neutral-800 transition-colors disabled:opacity-50 [clip-path:polygon(0_0,100%_0,100%_100%,8px_100%,0_16px)]"
                                title="Remove command"
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

        <!-- Empty State -->
        <div v-else class="bg-neutral-800 p-4 text-center [clip-path:polygon(0_0,100%_0,100%_100%,15px_100%,0_calc(100%-14px))]">
            <div class="text-neutral-300 mb-3">
                <svg class="mx-auto h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
            </div>
            <p class="text-sm font-medium text-neutral-300 mb-1">No Commands Yet</p>
            <p class="text-xs text-neutral-400">Create your first custom command below</p>
        </div>

        <!-- Add New Command -->
        <div class="bg-neutral-800 p-4 [clip-path:polygon(0_0,100%_0,100%_100%,15px_100%,0_calc(100%-14px))]">
            <h4 class="font-medium text-yellow-400 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Add New Command
            </h4>

            <div class="space-y-4">
                <!-- Command Name -->
                <div>
                    <label for="command-name" class="block text-sm font-medium text-neutral-400 mb-1">
                        Command Name
                    </label>
                    <input
                        id="command-name"
                        v-model="newCommand.name"
                        @blur="formatCommandName"
                        type="text"
                        class="w-full shadow-sm text-sm text-neutral-800 [clip-path:polygon(0_0,100%_0,100%_100%,15px_100%,0_calc(100%-14px))]"
                        placeholder="/mycommand"
                        :disabled="isLoading"
                    >
                    <p class="mt-1 text-xs text-neutral-400">Must start with "/" (e.g., /weather, /help)</p>
                </div>

                <!-- Description -->
                <div>
                    <label for="command-desc" class="block text-sm font-medium text-neutral-400 mb-1">
                        Description
                    </label>
                    <input
                        id="command-desc"
                        v-model="newCommand.description"
                        type="text"
                        class="w-full shadow-sm text-sm text-neutral-800 [clip-path:polygon(0_0,100%_0,100%_100%,15px_100%,0_calc(100%-14px))]"
                        placeholder="Brief description of what this command does"
                        :disabled="isLoading"
                    >
                </div>

                <!-- Response Template -->
                <div>
                    <label for="command-response" class="block text-sm font-medium text-neutral-400 mb-1">
                        Response Template
                    </label>
                    <textarea
                        id="command-response"
                        v-model="newCommand.response"
                        rows="3"
                        class="w-full shadow-sm text-sm text-neutral-800 [clip-path:polygon(0_0,100%_0,100%_100%,15px_100%,0_calc(100%-14px))]"
                        placeholder="Define how the assistant should respond when this command is used..."
                        :disabled="isLoading"
                    ></textarea>
                </div>

                <!-- Add Button -->
                <button
                    @click="saveCommand(newCommand)"
                    :disabled="!isValidCommand || isLoading"
                    class="px-4 py-2 bg-cyan-700 text-sm text-white hover:bg-cyan-800 focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed [clip-path:polygon(0_0,100%_0,100%_100%,15px_100%,0_calc(100%-14px))]"
                >
                    <span v-if="isLoading">Saving...</span>
                    <span v-else>{{ newCommand.name && existingCommands.find(c => c.name === newCommand.name) ? 'Update Command' : 'Add Command' }}</span>
                </button>
            </div>
        </div>

        <!-- Predefined Commands -->
        <div>
            <div class="text-md font-semibold text-yellow-400 mb-2 flex items-center">
                <svg class="w-5 h-5 mr-2 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Suggested Commands
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div
                    v-for="command in predefinedCommands"
                    :key="command.name"
                    class="group p-3 bg-neutral-800 text-neutral-200 hover:text-neutral-900 hover:bg-yellow-400 transition-colors cursor-pointer [clip-path:polygon(0_0,100%_0,100%_100%,15px_100%,0_64px)]"
                    @click="saveCommand(command)"
                >
                    <div class="flex items-center justify-between mb-2">
                        <code class="px-2 py-1 bg-yellow-400 text-neutral-900 group-hover:bg-neutral-800 group-hover:text-yellow-400 text-sm font-mono transition-colors [clip-path:polygon(0_0,100%_0,100%_100%,12px_100%,0_16px)]">
                            {{ command.name }}
                        </code>
                        <span class="text-xs text-yellow-400 group-hover:text-neutral-900">Click to add</span>
                    </div>
                    <p class="text-sm text-neutral-500">{{ command.description }}</p>
                </div>
            </div>
        </div>

        <!-- Usage Instructions -->
        <div class="bg-neutral-800 p-4 [clip-path:polygon(0_0,100%_0,100%_100%,15px_100%,0_calc(100%-16px))]">
            <div class="text-md font-semibold text-yellow-400 mb-2 flex items-center">
                <svg class="w-6 h-6 mr-2 mb-1 text-yellow-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                    <path d="M11 3a1 1 0 10-2 0v1a1 1 0 102 0V3zM15.657 5.757a1 1 0 00-1.414-1.414l-.707.707a1 1 0 001.414 1.414l.707-.707zM18 10a1 1 0 01-1 1h-1a1 1 0 110-2h1a1 1 0 011 1zM5.05 6.464A1 1 0 106.464 5.05l-.707-.707a1 1 0 00-1.414 1.414l.707.707zM5 10a1 1 0 01-1 1H3a1 1 0 110-2h1a1 1 0 011 1zM8 16v-1h4v1a2 2 0 11-4 0zM12 14c.015-.34.208-.646.477-.859a4 4 0 10-4.954 0c.27.213.462.519.477.859h4z"/>
                </svg>
                How to Use Commands
            </div>
            <ul class="text-sm text-yellow-400 space-y-1">
                <li class="flex items-start">
                    <span class="mr-2 mt-0.5">•</span>
                    <span>Type your command in any conversation (e.g., "/weather Paris")</span>
                </li>
                <li class="flex items-start">
                    <span class="mr-2 mt-0.5">•</span>
                    <span>Commands work across all conversations and models</span>
                </li>
                <li class="flex items-start">
                    <span class="mr-2 mt-0.5">•</span>
                    <span>You can add parameters after the command for specific requests</span>
                </li>
            </ul>
        </div>
    </div>
</template>
