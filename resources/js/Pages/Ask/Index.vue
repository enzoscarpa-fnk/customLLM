<script setup>
import {ref, watch} from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import MarkdownIt from 'markdown-it'
import hljs from 'highlight.js'

const md = new MarkdownIt({
    html: true,
    linkify: true,
    typographer: true,
    highlight: (str, lang) => {
        if (lang && hljs.getLanguage(lang)) {
            try {
                return hljs.highlight(str, { language: lang }).value
            } catch (_) {}
        }
        return ''
    }
})

const props = defineProps({
    models: Array,
    selectedModel: String
})

const form = useForm({
    message: '',
    model: props.selectedModel
})

const submit = () => {
    form.post(route('ask.post'), {
        preserveScroll: true,
        onSuccess: () => form.reset('message')
    })
}

const responseHtml = ref('')
const errorMessage = ref('')

/* Watches for incoming flash message and transforms it in markdown->html */
watch(() => usePage().props.flash.message, (newVal) => {
    if (newVal) responseHtml.value = md.render(newVal)
})

watch(() => usePage().props.flash.error, (newVal) => {
    errorMessage.value = newVal
})
</script>

<template>
    <div class="max-w-3xl mx-auto p-6">
        <!-- Flash messages -->
        <div v-if="errorMessage" class="mb-4 p-4 bg-red-100 border-l-4 border-red-500 text-red-700">
            {{ errorMessage }}
        </div>

        <!-- Form -->
        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">AI Model</label>
                <select
                    v-model="form.model"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option
                        v-for="model in models"
                        :key="model.id"
                        :value="model.id"
                    >
                        {{ model.name }}
                    </option>
                </select>
            </div>

            <div>
        <textarea
            v-model="form.message"
            rows="4"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            placeholder="How can I help you?"
        ></textarea>
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
            >
                <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ form.processing ? 'Processing...' : 'Send' }}
            </button>
        </form>

        <!-- Formatted response -->
        <div
            v-if="responseHtml"
            class="mt-8 prose prose-slate max-w-none dark:prose-invert prose-pre:bg-gray-100"
            v-html="responseHtml"
        ></div>
    </div>
</template>
