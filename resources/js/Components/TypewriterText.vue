<script setup>
import { ref, watch, onMounted } from 'vue'

const props = defineProps({
    text: String,
    speed: {
        type: Number,
        default: 50 // millisecondes par caractère
    },
    isReceiving: Boolean
})

const emit = defineEmits(['typing-complete'])

const displayedText = ref('')
const currentIndex = ref(0)
const animationId = ref(null)
const isTypingComplete = ref(false)

const typeWriter = () => {
    if (currentIndex.value < props.text.length) {
        displayedText.value = props.text.substring(0, currentIndex.value + 1)
        currentIndex.value++

        animationId.value = setTimeout(typeWriter, props.speed)
    } else {
        isTypingComplete.value = true
        emit('typing-complete')
    }
}

// Watcher pour détecter les nouveaux caractères
watch(() => props.text, (newText, oldText) => {
    // Reset si le texte change complètement
    if (newText.length < (oldText?.length || 0)) {
        currentIndex.value = 0
        displayedText.value = ''
        isTypingComplete.value = false
    }

    // Si le nouveau texte est plus long, continuer l'animation
    if (newText.length > currentIndex.value && !animationId.value) {
        typeWriter()
    }
}, { immediate: true })

// Reset quand on commence à recevoir de nouvelles données
watch(() => props.isReceiving, (receiving) => {
    if (receiving) {
        isTypingComplete.value = false
    }
})

onMounted(() => {
    if (props.text) {
        typeWriter()
    }
})
</script>

<template>
    <div class="typewriter-container">
        <span v-html="displayedText"></span>
    </div>
</template>

<style scoped>
.cursor {
    @apply text-gray-400 font-mono;
}
</style>
