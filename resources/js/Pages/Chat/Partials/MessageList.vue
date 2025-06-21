<script setup>
import { ref, onMounted, nextTick, watch, computed } from 'vue'
import TypewriterText from "@/Components/TypewriterText.vue";
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

// État pour suivre si l'animation typewriter est en cours
const typingStates = ref(new Map())

// Utiliser computed pour s'assurer de la réactivité
const displayMessages = computed(() => {
    return props.messages || []
})

// Computed pour détecter le dernier message assistant
const lastAssistantMessage = computed(() => {
    const messages = displayMessages.value
    if (messages.length === 0) return null

    const lastMessage = messages[messages.length - 1]
    return lastMessage && lastMessage.role === 'assistant' ? lastMessage : null
})

// Computed pour vérifier si on est en train de streamer OU de taper SEULEMENT pour le dernier message
const isStreamingOrTyping = computed(() => {
    const lastMsg = lastAssistantMessage.value
    if (!lastMsg) return false

    const isTyping = typingStates.value.get(lastMsg.id) || false
    const isStreamingThisMessage = props.isStreaming && lastMsg === lastAssistantMessage.value

    return isStreamingThisMessage || isTyping
})

// Fonction pour vérifier si un message spécifique doit avoir l'animation typewriter
const shouldAnimateMessage = (message) => {
    // Seulement animer si c'est le dernier message assistant ET qu'il n'a pas encore été complètement affiché
    const isLast = message === lastAssistantMessage.value
    const isTyping = typingStates.value.get(message.id) || false
    const isStreamingThis = props.isStreaming && isLast

    return isLast && (isStreamingThis || isTyping)
}

const handleTypingComplete = (messageId) => {
    typingStates.value.set(messageId, false)
}

const handleTypingStart = (messageId) => {
    typingStates.value.set(messageId, true)
}

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
    scrollToBottom()
}, { deep: true, flush: 'post' })

// Watcher spécifique pour le streaming
watch(() => props.isStreaming, (newValue) => {
    if (newValue) {
        scrollToBottom()
    }
})

// Watcher pour le contenu du dernier message pendant le streaming
watch(() => lastAssistantMessage.value?.content, (newContent) => {
    if (props.isStreaming && newContent) {
        scrollToBottom()
    }
}, { flush: 'post' })

onMounted(() => {
    scrollToBottom()
})
</script>

<template>
    <div ref="messagesContainer" class="h-full overflow-y-auto p-4 space-y-4">
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
            <div v-if="message.role === 'user'" class="max-w-3xl bg-blue-600 text-white rounded-lg px-4 py-2">
                <div class="whitespace-pre-wrap text-white">
                    {{ message.content }}
                </div>
                <div class="text-xs mt-1 text-right text-blue-100">
                    {{ formatTime(message.created_at) }}
                </div>
            </div>

            <!-- Assistant Message -->
            <div v-else-if="message.role === 'assistant'" class="max-w-3xl bg-gray-100 text-gray-900 rounded-lg px-4 py-2">
                <div class="prose prose-sm max-w-none prose-gray prose-pre:bg-gray-200 prose-pre:text-gray-800">
                    <!-- Utiliser TypewriterText seulement pour le dernier message en cours -->
                    <TypewriterText
                        v-if="shouldAnimateMessage(message)"
                        :text="renderMarkdown(message.content)"
                        :speed="20"
                        :is-receiving="isStreaming && message === lastAssistantMessage"
                        @typing-complete="handleTypingComplete(message.id)"
                        @vue:mounted="handleTypingStart(message.id)"
                    />
                    <!-- Affichage normal pour les autres messages -->
                    <div v-else v-html="renderMarkdown(message.content)"></div>
                </div>

                <!-- Indicateur de streaming/typing seulement pour le dernier message -->
                <div
                    v-if="isStreamingOrTyping && message === lastAssistantMessage"
                    class="flex items-center space-x-2 mt-2"
                >
                    <div class="animate-pulse flex space-x-1">
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                    </div>
                    <span class="text-xs text-gray-500">
                        {{ isStreaming ? 'AI is responding...' : 'AI is typing...' }}
                    </span>
                </div>

                <div class="text-xs mt-1 text-left text-gray-500">
                    {{ formatTime(message.created_at) }}
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.message-enter-active {
    transition: all 0.3s ease-out;
}

.message-enter-from {
    opacity: 0;
    transform: translateY(10px);
}

.message-enter-to {
    opacity: 1;
    transform: translateY(0);
}

/* Animation pour le contenu qui grandit */
.content-grow {
    animation: contentGrow 0.2s ease-out;
}

@keyframes contentGrow {
    from {
        max-height: 0;
        opacity: 0;
    }
    to {
        max-height: 200px;
        opacity: 1;
    }
}
</style>
