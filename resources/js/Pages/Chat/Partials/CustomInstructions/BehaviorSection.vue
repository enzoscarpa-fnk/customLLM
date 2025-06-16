<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
    modelValue: String
})

const emit = defineEmits(['update:modelValue'])

const behavior = ref(props.modelValue || '')

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

watch(behavior, (newValue) => {
    emit('update:modelValue', newValue)
})

watch(() => props.modelValue, (newValue) => {
    behavior.value = newValue || ''
})

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

        <!-- Quick Presets -->
        <div>
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
        <div>
            <label for="behavior" class="block text-sm font-medium text-gray-700 mb-2">
                Custom Behavior Instructions
            </label>
            <textarea
                id="behavior"
                v-model="behavior"
                rows="8"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                placeholder="Describe how you want the assistant to behave, communicate, and format responses..."
            ></textarea>
            <p class="mt-2 text-xs text-gray-500">
                {{ behavior.length }}/2000 characters
            </p>
        </div>

        <!-- Custom Options -->
        <div>
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
        <div v-if="behavior" class="bg-green-50 rounded-lg p-4">
            <h4 class="text-sm font-medium text-green-900 mb-2">✅ Current Behavior Settings</h4>
            <div class="text-sm text-green-800 bg-white rounded p-3 border border-green-200">
                <p class="whitespace-pre-wrap">{{ behavior }}</p>
            </div>
        </div>
    </div>
</template>
