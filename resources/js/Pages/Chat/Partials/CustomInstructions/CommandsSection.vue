<script setup>
import { ref, watch, computed } from 'vue'

const props = defineProps({
    modelValue: Array
})

const emit = defineEmits(['update:modelValue'])

const commands = ref(props.modelValue || [])

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

const addCommand = () => {
    if (isValidCommand.value) {
        const commandExists = commands.value.some(cmd => cmd.name === newCommand.value.name)
        if (!commandExists) {
            commands.value.push({ ...newCommand.value })
            resetNewCommand()
        }
    }
}

const removeCommand = (index) => {
    commands.value.splice(index, 1)
}

const addPredefinedCommand = (command) => {
    const commandExists = commands.value.some(cmd => cmd.name === command.name)
    if (!commandExists) {
        commands.value.push({ ...command })
    }
}

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
        <div v-if="commands.length > 0">
            <h4 class="text-sm font-medium text-gray-900 mb-3">📋 Your Commands</h4>
            <div class="space-y-3">
                <div
                    v-for="(command, index) in commands"
                    :key="index"
                    class="p-4 bg-gray-50 rounded-lg border border-gray-200"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center mb-2">
                                <code class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-sm font-mono">
                                    {{ command.name }}
                                </code>
                                <span class="ml-2 text-sm font-medium text-gray-900">
                                    {{ command.description }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-600">{{ command.response }}</p>
                        </div>
                        <button
                            @click="removeCommand(index)"
                            class="ml-4 p-1 text-red-500 hover:text-red-700 hover:bg-red-50 rounded transition-colors"
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
                    ></textarea>
                </div>

                <!-- Add Button -->
                <button
                    @click="addCommand"
                    :disabled="!isValidCommand"
                    class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Add Command
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
                    @click="addPredefinedCommand(command)"
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
