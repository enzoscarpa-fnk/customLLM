<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
    modelValue: String
})

const emit = defineEmits(['update:modelValue'])

const aboutYou = ref(props.modelValue || '')

const examples = [
    "I'm an entrepreneur in the green technology sector, looking to innovate in renewable energy.",
    "I'm a painter exploring the connections between traditional art and digital media.",
    "I'm a software developer specializing in Vue.js and Laravel applications.",
    "I'm a marketing professional focused on digital strategies for small businesses."
]

const suggestions = [
    "Share your profession and areas of interest for targeted responses",
    "Express your personal or professional goals for tailored assistance",
    "Mention your knowledge level on certain topics to adjust explanation complexity",
    "Include your preferred communication style and learning preferences"
]

watch(aboutYou, (newValue) => {
    emit('update:modelValue', newValue)
})

watch(() => props.modelValue, (newValue) => {
    aboutYou.value = newValue || ''
})

const insertExample = (example) => {
    if (aboutYou.value) {
        aboutYou.value += '\n\n' + example
    } else {
        aboutYou.value = example
    }
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <h3 class="text-lg font-medium text-gray-900 flex items-center">
                <span class="mr-2">👤</span>
                About You
            </h3>
            <p class="mt-1 text-sm text-gray-600">
                Tell the assistant about yourself, your interests, and your expertise. This helps the AI provide more relevant and personalized responses.
            </p>
        </div>

        <!-- Main Input -->
        <div>
            <label for="about-you" class="block text-sm font-medium text-gray-700 mb-2">
                Your Information
            </label>
            <textarea
                id="about-you"
                v-model="aboutYou"
                rows="8"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                placeholder="Describe yourself, your profession, interests, goals, and expertise level..."
            ></textarea>
            <p class="mt-2 text-xs text-gray-500">
                {{ aboutYou.length }}/2000 characters
            </p>
        </div>

        <!-- Tips & Suggestions -->
        <div class="bg-blue-50 rounded-lg p-4">
            <h4 class="text-sm font-medium text-blue-900 mb-2">💡 Tips for Better Results</h4>
            <ul class="text-sm text-blue-800 space-y-1">
                <li v-for="suggestion in suggestions" :key="suggestion" class="flex items-start">
                    <span class="mr-2 mt-0.5">•</span>
                    <span>{{ suggestion }}</span>
                </li>
            </ul>
        </div>

        <!-- Examples -->
        <div>
            <h4 class="text-sm font-medium text-gray-900 mb-3">📝 Example Descriptions</h4>
            <div class="space-y-2">
                <div
                    v-for="(example, index) in examples"
                    :key="index"
                    class="p-3 bg-gray-50 rounded-md border border-gray-200 hover:bg-gray-100 transition-colors cursor-pointer"
                    @click="insertExample(example)"
                >
                    <p class="text-sm text-gray-700">{{ example }}</p>
                    <p class="text-xs text-gray-500 mt-1">Click to add to your description</p>
                </div>
            </div>
        </div>
    </div>
</template>
