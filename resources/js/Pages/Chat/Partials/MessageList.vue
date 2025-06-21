<script setup>
import { ref, onMounted, nextTick, watch, computed } from 'vue'
import MarkdownIt from 'markdown-it'
import hljs from 'highlight.js'

const props = defineProps({
    messages: Array,
    isStreaming: {
        type: Boolean,
        default: false
    }
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

// Utiliser computed pour s'assurer de la réactivité
const displayMessages = computed(() => {
    console.log('🔄 Messages mis à jour dans MessageList:', props.messages)
    return props.messages || []
})

// Computed pour détecter le dernier message assistant en cours de streaming
const lastAssistantMessage = computed(() => {
    const messages = displayMessages.value
    if (messages.length === 0) return null

    const lastMessage = messages[messages.length - 1]
    return lastMessage && lastMessage.role === 'assistant' ? lastMessage : null
})

// Computed pour vérifier si on est en train de streamer le dernier message
const isStreamingLastMessage = computed(() => {
    return props.isStreaming && lastAssistantMessage.value &&
        (lastAssistantMessage.value.content === '' ||
            typeof lastAssistantMessage.value.id === 'string' &&
            lastAssistantMessage.value.id.startsWith('temp-'))
})

const renderMarkdown = (content) => {
    if (!content || content.trim() === '') {
        return ''
    }
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
    return new Date(dateString).toLocaleTimeString('fr-FR', {
        hour: '2-digit',
        minute: '2-digit'
    })
}

// Watcher optimisé pour le scroll automatique
watch(() => displayMessages.value, (newMessages, oldMessages) => {
    console.log('📜 Messages changés, scroll vers le bas')
    scrollToBottom()
}, { deep: true, flush: 'post' })

// Watcher spécifique pour le streaming
watch(() => props.isStreaming, (newValue) => {
    console.log('🔄 État streaming changé:', newValue)
    if (newValue) {
        scrollToBottom()
    }
})

// Watcher pour le contenu du dernier message pendant le streaming
watch(() => lastAssistantMessage.value?.content, (newContent) => {
    if (props.isStreaming && newContent) {
        console.log('📝 Contenu du message assistant mis à jour:', newContent.substring(0, 50) + '...')
        scrollToBottom()
    }
}, { flush: 'post' })

onMounted(() => {
    console.log('🚀 MessageList monté avec', displayMessages.value.length, 'messages')
    scrollToBottom()
})
</script>

<template>
    <div
        ref="messagesContainer"
        class="h-full overflow-y-auto p-4 space-y-4"
    >
        <div v-if="displayMessages.length === 0" class="text-center text-gray-500 mt-8">
            No messages yet. Start the conversation!
        </div>

        <div
            v-for="message in displayMessages"
            :key="message.id || `temp-${message.role}-${message.created_at}`"
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
                <!-- Contenu du message -->
                <div
                    v-if="message.content && message.content.trim() !== ''"
                    class="prose prose-sm max-w-none prose-gray prose-pre:bg-gray-200 prose-pre:text-gray-800"
                    v-html="renderMarkdown(message.content)"
                ></div>

                <!-- Indicateur de streaming pour ce message spécifique -->
                <div
                    v-if="isStreamingLastMessage && message === lastAssistantMessage"
                    class="flex items-center space-x-2 mt-2"
                >
                    <div class="animate-pulse flex space-x-1">
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                    </div>
                    <span class="text-xs text-gray-500">AI is typing...</span>
                </div>

                <!-- Placeholder si le message est vide et pas en streaming -->
                <div
                    v-else-if="!message.content || message.content.trim() === ''"
                    class="text-gray-400 italic"
                >
                    <em>Generating response from AI...</em>
                </div>

                <div class="text-xs mt-1 text-left text-gray-500">
                    {{ formatTime(message.created_at) }}
                </div>
            </div>
        </div>

        <!-- Indicateur global de streaming si aucun message assistant n'est présent -->
        <div
            v-if="isStreaming && !lastAssistantMessage"
            class="flex justify-start"
        >
            <div class="max-w-3xl bg-gray-100 text-gray-900 rounded-lg px-4 py-2">
                <div class="flex items-center space-x-2">
                    <div class="animate-pulse flex space-x-1">
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                    </div>
                    <span class="text-xs text-gray-500">AI is preparing response...</span>
                </div>
            </div>
        </div>
    </div>
</template>
