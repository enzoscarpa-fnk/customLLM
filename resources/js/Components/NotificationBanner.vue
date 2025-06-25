<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
    show: Boolean,
    type: {
        type: String,
        default: 'success',
        validator: (value) => ['success', 'error', 'warning', 'info'].includes(value)
    },
    message: String,
    duration: {
        type: Number,
        default: 4000
    }
})

const emit = defineEmits(['close'])

const isVisible = ref(false)
let timeoutId = null

watch(() => props.show, (newShow) => {
    if (newShow) {
        isVisible.value = true
        if (timeoutId) {
            clearTimeout(timeoutId)
        }
        timeoutId = setTimeout(() => {
            isVisible.value = false
            setTimeout(() => emit('close'), 300) // Attendre la fin de l'animation
        }, props.duration)
    } else {
        isVisible.value = false
    }
})

const close = () => {
    isVisible.value = false
    if (timeoutId) {
        clearTimeout(timeoutId)
    }
    setTimeout(() => emit('close'), 300)
}

const getTypeClasses = () => {
    const baseClasses = 'px-6 py-4 text-sm font-medium'
    switch (props.type) {
        case 'success':
            return `${baseClasses} bg-yellow-400 text-neutral-900`
        case 'error':
            return `${baseClasses} bg-pink-600 text-neutral-300`
        case 'warning':
            return `${baseClasses} bg-yellow-600 text-neutral-900`
        case 'info':
            return `${baseClasses} bg-cyan-700 text-neutral-300`
        default:
            return `${baseClasses} bg-yellow-400 text-neutral-900`
    }
}

const getIcon = () => {
    switch (props.type) {
        case 'success':
            return 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'
        case 'error':
            return 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z'
        case 'warning':
            return 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z'
        default:
            return 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
    }
}
</script>

<template>
    <Transition
        enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="transform -translate-y-full opacity-0"
        enter-to-class="transform translate-y-0 opacity-100"
        leave-active-class="transition-all duration-300 ease-in"
        leave-from-class="transform translate-y-0 opacity-100"
        leave-to-class="transform -translate-y-full opacity-0"
    >
        <div
            v-if="props.show && isVisible"
            class="fixed top-0 left-0 right-0 z-[60] shadow-lg"
        >
            <div :class="getTypeClasses()">
                <div class="flex items-center justify-between max-w-7xl mx-auto">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getIcon()"></path>
                        </svg>
                        <span>{{ message }}</span>
                    </div>
                    <button
                        @click="close"
                        class="ml-4 text-neutral-800 hover:text-pink-600 transition-colors"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>
