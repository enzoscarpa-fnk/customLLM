<script setup>
import { ref, watch, onMounted } from 'vue'

const props = defineProps({
    text: String,
    speed: {
        type: Number,
        default: 50 // millisecondes par caractère
    },
    isStreaming: Boolean
})

const displayedText = ref('')
const currentIndex = ref(0)
const animationId = ref(null)

const typeWriter = () => {
    if (currentIndex.value < props.text.length) {
        displayedText.value = props.text.substring(0, currentIndex.value + 1)
        currentIndex.value++

        animationId.value = setTimeout(typeWriter, props.speed)
    }
}

// Watcher pour détecter les nouveaux caractères
watch(() => props.text, (newText, oldText) => {
    // Si le nouveau texte est plus long, continuer l'animation
    if (newText.length > (oldText?.length || 0)) {
        if (!animationId.value && currentIndex.value < newText.length) {
            typeWriter()
        }
    }
}, { immediate: true })

// Curseur clignotant pendant le streaming
const showCursor = ref(true)
let cursorInterval = null

watch(() => props.isStreaming, (streaming) => {
    if (streaming) {
        cursorInterval = setInterval(() => {
            showCursor.value = !showCursor.value
        }, 500)
    } else {
        if (cursorInterval) {
            clearInterval(cursorInterval)
            cursorInterval = null
        }
        showCursor.value = false
    }
})

onMounted(() => {
    if (props.text) {
        typeWriter()
    }
})
</script>

<template>
    <span class="typewriter-container">
        <span v-html="displayedText"></span>
        <span
            v-if="isStreaming && showCursor"
            class="cursor animate-pulse"
        >|</span>
    </span>
</template>

<style scoped>
.cursor {
    @apply text-gray-400 font-mono;
}
</style>
