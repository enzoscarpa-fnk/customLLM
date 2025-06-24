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

const typingStates = ref(new Map())

const displayMessages = computed(() => {
    return props.messages || []
})

const lastAssistantMessage = computed(() => {
    const messages = displayMessages.value
    if (messages.length === 0) return null

    const lastMessage = messages[messages.length - 1]
    return lastMessage && lastMessage.role === 'assistant' ? lastMessage : null
})

const isStreamingOrTyping = computed(() => {
    const lastMsg = lastAssistantMessage.value
    if (!lastMsg) return false

    const isTyping = typingStates.value.get(lastMsg.id) || false
    const isStreamingThisMessage = props.isStreaming && lastMsg === lastAssistantMessage.value

    return isStreamingThisMessage || isTyping
})

// Typewriter animation check
const shouldAnimateMessage = (message) => {
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

// Watcher for automatic scroll
watch(() => displayMessages.value, (newMessages, oldMessages) => {
    scrollToBottom()
}, { deep: true, flush: 'post' })

// Watcher for streaming
watch(() => props.isStreaming, (newValue) => {
    if (newValue) {
        scrollToBottom()
    }
})

// Watcher for last message while streaming
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
        <div v-if="displayMessages.length === 0" class="text-center text-neutral-200 mt-8">
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
            <div v-if="message.role === 'user'" class="max-w-3xl bg-yellow-400 text-stone-900 text-sm px-4 py-2 [clip-path:polygon(0_0,100%_0,100%_calc(100%-16px),calc(100%-18px)_100%,0_100%)] shadow-[inset_0_0_0_1px_#facc15]">
                <div class="whitespace-pre-wrap text-stone-900">
                    {{ message.content }}
                </div>
                <div class="text-xs mt-1 text-right text-neutral-600">
                    {{ formatTime(message.created_at) }}
                </div>
            </div>

            <!-- Assistant Message -->
            <div v-else-if="message.role === 'assistant'" class="max-w-3xl bg-neutral-200 text-neutral-900 px-4 py-2 [clip-path:polygon(0_0,100%_0,100%_100%,18px_100%,0_calc(100%-16px))]">
                <div class="prose prose-sm max-w-none prose-gray prose-pre:bg-neutral-800 prose-pre:text-neutral-200">
                    <TypewriterText
                        v-if="shouldAnimateMessage(message)"
                        :text="renderMarkdown(message.content)"
                        :speed="10"
                        :is-receiving="isStreaming && message === lastAssistantMessage"
                        @typing-complete="handleTypingComplete(message.id)"
                        @vue:mounted="handleTypingStart(message.id)"
                    />
                    <div v-else v-html="renderMarkdown(message.content)"></div>
                </div>

                <!-- Streaming/typing indicator -->
                <div
                    v-if="isStreamingOrTyping && message === lastAssistantMessage"
                    class="flex items-center space-x-2 mt-2"
                >
                    <div class="animate-pulse flex space-x-1">
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                    </div>
                    <span class="text-xs text-neutral-600">
                        {{ isStreaming ? 'AI is responding...' : 'AI is typing...' }}
                    </span>
                </div>

                <div class="text-xs mt-1 text-left text-neutral-600">
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
