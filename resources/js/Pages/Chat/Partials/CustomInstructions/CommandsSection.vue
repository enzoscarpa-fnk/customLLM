<script setup>
import { ref, watch, computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
    modelValue: Array,
    existingData: Object
})

const emit = defineEmits(['update:modelValue', 'dataUpdated'])

const commands = ref(props.modelValue || [])
const isLoading = ref(false)

const existingCommands = ref(props.existingData?.custom_commands || [])

watch(() => props.existingData?.custom_commands, (newValue) => {
    existingCommands.value = newValue || []
}, { deep: true })

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

const saveCommand = async (command) => {
    if (!command.name.startsWith('/') || !command.description.trim() || !command.response.trim()) {
        return
    }

    isLoading.value = true

    try {
        const updatedCommands = [...existingCommands.value]
        const existingIndex = updatedCommands.findIndex(cmd => cmd.name === command.name)

        if (existingIndex !== -1) {
            updatedCommands[existingIndex] = command
        } else {
            updatedCommands.push(command)
        }

        router.post(route('instructions.update'), {
            type: 'custom_commands',
            data: updatedCommands
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
            <h3 class="text-lg font-medium text-gray-900 flex items-center">
                <span class="mr-2">⚡</span>
                Custom Commands
            </h3>
            <p class="mt-1 text-sm text-gray-600">
                Create custom commands to quickly access specific types of responses or perform common tasks.
            </p>
        </div>

        <!-- Existing Commands -->
        <div v-if="existingCommands.length > 0">
            <div class="flex items-center justify-between mb-3">
                <h4 class="text-sm font-medium text-gray-900 flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Your Commands ({{ existingCommands.length }})
                </h4>
                <button
                    @click="clearAllCommands"
                    :disabled="isLoading"
                    class="text-xs text-red-600 hover:text-red-800 hover:bg-red-50 px-2 py-1 rounded transition-colors disabled:opacity-50"
                >
                    Clear All
                </button>
            </div>
            <div class="space-y-3">
                <div
                    v-for="(command, index) in existingCommands"
                    :key="`existing-${command.name}-${index}`"
                    class="group p-4 bg-purple-50 rounded-lg border border-purple-200 hover:border-purple-300 transition-all"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center mb-2">
                                <code class="px-2 py-1 bg-purple-100 text-purple-800 rounded text-sm font-mono">
                                    {{ command.name }}
                                </code>
                                <span class="ml-2 text-sm font-medium text-gray-900">
                                    {{ command.description }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-600 mb-2">{{ command.response }}</p>
                            <div class="flex space-x-2">
                                <button
                                    @click="editExistingCommand(command)"
                                    :disabled="isLoading"
                                    class="text-xs text-purple-600 hover:text-purple-800 hover:bg-purple-100 px-2 py-1 rounded transition-colors disabled:opacity-50"
                                >
                                    Edit
                                </button>
                                <button
                                    @click="removeExistingCommand(command)"
                                    :disabled="isLoading"
                                    class="text-xs text-red-600 hover:text-red-800 hover:bg-red-50 px-2 py-1 rounded transition-colors disabled:opacity-50"
                                >
                                    Delete
                                </button>
                            </div>
                        </div>
                        <div class="ml-4 flex space-x-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button
                                @click="editExistingCommand(command)"
                                :disabled="isLoading"
                                class="p-1 text-purple-600 hover:text-purple-800 hover:bg-purple-100 rounded transition-colors disabled:opacity-50"
                                title="Edit command"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </button>
                            <button
                                @click="removeExistingCommand(command)"
                                :disabled="isLoading"
                                class="p-1 text-red-500 hover:text-red-700 hover:bg-red-50 rounded transition-colors disabled:opacity-50"
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
        <div v-else class="bg-gray-50 border border-gray-200 rounded-lg p-6 text-center">
            <div class="text-gray-400 mb-3">
                <svg class="mx-auto h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
            </div>
            <h4 class="text-sm font-medium text-gray-900 mb-1">No Commands Yet</h4>
            <p class="text-sm text-gray-500">Create your first custom command below</p>
        </div>

        <!-- Add New Command -->
        <div class="border border-gray-200 rounded-lg p-4">
            <h4 class="text-sm font-medium text-gray-900 mb-4">➕ Add New Command</h4>

            <div class="space-y-4">
                <!-- Command Name -->
                <div>
                    <label for="command-name" class="block text-sm font-medium text-gray-700 mb-1">
                        Command Name
                    </label>
                    <input
                        id="command-name"
                        v-model="newCommand.name"
                        @blur="formatCommandName"
                        type="text"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="/mycommand"
                        :disabled="isLoading"
                    >
                    <p class="mt-1 text-xs text-gray-500">Must start with "/" (e.g., /weather, /help)</p>
                </div>

                <!-- Description -->
                <div>
                    <label for="command-desc" class="block text-sm font-medium text-gray-700 mb-1">
                        Description
                    </label>
                    <input
                        id="command-desc"
                        v-model="newCommand.description"
                        type="text"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Brief description of what this command does"
                        :disabled="isLoading"
                    >
                </div>

                <!-- Response Template -->
                <div>
                    <label for="command-response" class="block text-sm font-medium text-gray-700 mb-1">
                        Response Template
                    </label>
                    <textarea
                        id="command-response"
                        v-model="newCommand.response"
                        rows="3"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Define how the assistant should respond when this command is used..."
                        :disabled="isLoading"
                    ></textarea>
                </div>

                <!-- Add Button -->
                <button
                    @click="saveCommand(newCommand)"
                    :disabled="!isValidCommand || isLoading"
                    class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span v-if="isLoading">Saving...</span>
                    <span v-else>Save Command</span>
                </button>
            </div>
        </div>

        <!-- Predefined Commands -->
        <div>
            <h4 class="text-sm font-medium text-gray-900 mb-3">🎯 Suggested Commands</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div
                    v-for="command in predefinedCommands"
                    :key="command.name"
                    class="p-3 border border-gray-200 rounded-lg hover:border-blue-300 hover:bg-blue-50 transition-all cursor-pointer"
                    @click="saveCommand(command)"
                >
                    <div class="flex items-center justify-between mb-2">
                        <code class="px-2 py-1 bg-gray-100 text-gray-800 rounded text-sm font-mono">
                            {{ command.name }}
                        </code>
                        <span class="text-xs text-blue-600">Click to add</span>
                    </div>
                    <p class="text-sm text-gray-600">{{ command.description }}</p>
                </div>
            </div>
        </div>

        <!-- Usage Instructions -->
        <div class="bg-yellow-50 rounded-lg p-4">
            <h4 class="text-sm font-medium text-yellow-900 mb-2">💡 How to Use Commands</h4>
            <ul class="text-sm text-yellow-800 space-y-1">
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
