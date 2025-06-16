<script setup>
import { ref, onMounted, nextTick, watch } from 'vue'
import MarkdownIt from 'markdown-it'
import hljs from 'highlight.js'

const props = defineProps({
    messages: Array
})

const messagesContainer = ref(null)

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

const renderMarkdown = (content) => {
    return md.render(content)
}

const scrollToBottom = () => {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
        }
    })
}

const formatTime = (dateString) => {
    return new Date(dateString).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit'
    })
}

// Scroll to bottom when messages change
watch(() => props.messages, () => {
    scrollToBottom()
}, { deep: true })

onMounted(() => {
    scrollToBottom()
})
</script>

<template>
    <div
        ref="messagesContainer"
        class="h-full overflow-y-auto p-4 space-y-4"
    >
        <div v-if="messages.length === 0" class="text-center text-gray-500 mt-8">
            No messages yet. Start the conversation!
        </div>

        <div
            v-for="message in messages"
            :key="message.id"
            class="flex"
            :class="{
                'justify-end': message.role === 'user',
                'justify-start': message.role === 'assistant'
            }"
        >
            <!-- User Message -->
            <div
                v-if="message.role === 'user'"
                class="max-w-3xl bg-blue-600 text-white rounded-lg px-4 py-2"
            >
                <div class="whitespace-pre-wrap text-white">
                    {{ message.content }}
                </div>
                <div class="text-xs mt-1 text-right text-blue-100">
                    {{ formatTime(message.created_at) }}
                </div>
            </div>

            <!-- Assistant Message -->
            <div
                v-else-if="message.role === 'assistant'"
                class="max-w-3xl bg-gray-100 text-gray-900 rounded-lg px-4 py-2"
            >
                <div
                    class="prose prose-sm max-w-none prose-gray prose-pre:bg-gray-200 prose-pre:text-gray-800"
                    v-html="renderMarkdown(message.content)"
                ></div>
                <div class="text-xs mt-1 text-left text-gray-500">
                    {{ formatTime(message.created_at) }}
                </div>
            </div>
        </div>
    </div>
</template>
