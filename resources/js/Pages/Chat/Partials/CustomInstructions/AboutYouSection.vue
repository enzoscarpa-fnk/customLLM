<script setup>
import { ref, watch, onMounted } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import IconAbout from "@/Components/icons/IconAbout.vue";

const props = defineProps({
    modelValue: String,
    existingData: Object
})

const emit = defineEmits(['update:modelValue', 'dataUpdated'])

const aboutYou = ref(props.modelValue || '')
const isEditing = ref(false)
const isLoading = ref(false)

const existingAboutYou = ref('')

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

const route = usePage().props.route;

// Initialize existing data
const initializeExistingData = () => {
    existingAboutYou.value = props.existingData?.about_you || ''
}

onMounted(() => {
    initializeExistingData()
})

watch(aboutYou, (newValue) => {
    emit('update:modelValue', newValue)
})

watch(() => props.modelValue, (newValue) => {
    aboutYou.value = newValue || ''
})

watch(() => props.existingData, (newValue) => {
    initializeExistingData()
}, { deep: true })

const insertExample = (example) => {
    if (aboutYou.value) {
        aboutYou.value += '\n\n' + example
    } else {
        aboutYou.value = example
    }
}

const editExisting = () => {
    aboutYou.value = existingAboutYou.value
    isEditing.value = true
    setTimeout(() => {
        document.getElementById('about-you')?.focus()
    }, 100)
}

const saveChanges = async () => {
    if (!aboutYou.value.trim()) {
        return
    }

    isLoading.value = true

    try {
        router.post(route('instructions.update'), {
            type: 'about_you',
            data: aboutYou.value
        }, {
            preserveState: true,
            onSuccess: (page) => {
                existingAboutYou.value = aboutYou.value
                isEditing.value = false
                emit('dataUpdated', page.props.userInstructions)
            },
            onError: (errors) => {
                console.error('Error saving changes:', errors)
            }
        })
    } catch (error) {
        console.error('Error saving changes:', error)
    } finally {
        isLoading.value = false
    }
}

const deleteExisting = async () => {
    if (!confirm('Are you sure you want to delete your "About You" information?')) {
        return
    }

    isLoading.value = true

    try {
        router.delete(route('instructions.delete'), {
            data: { type: 'about_you' },
            preserveState: true,
            onSuccess: (page) => {
                existingAboutYou.value = ''
                aboutYou.value = ''
                isEditing.value = false
                emit('dataUpdated', page.props.userInstructions)
            },
            onError: (errors) => {
                console.error('Error deleting:', errors)
            }
        })
    } catch (error) {
        console.error('Error deleting:', error)
    } finally {
        isLoading.value = false
    }
}

const cancelEdit = () => {
    aboutYou.value = existingAboutYou.value
    isEditing.value = false
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <h3 class="text-lg font-medium text-yellow-400 flex items-center">
                <IconAbout class="mr-2 w-5 h-5" />
                About You
            </h3>
            <p class="mt-1 text-sm text-neutral-400">
                Tell the assistant about yourself, your interests, and your expertise. This helps the AI provide more relevant and personalized responses.
            </p>
        </div>

        <!-- Current Description Section -->
        <div v-if="existingAboutYou && existingAboutYou.trim() && !isEditing" class="bg-yellow-400 p-4 [clip-path:polygon(0_0,100%_0,100%_100%,15px_100%,0_calc(100%-14px))]">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <h4 class="text-sm font-medium text-neutral-900 mb-2 flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Current Description
                    </h4>
                    <div class="text-sm text-neutral-200 bg-neutral-800 p-3 [clip-path:polygon(0_0,100%_0,100%_100%,15px_100%,0_calc(100%-14px))]">
                        <p class="whitespace-pre-wrap">{{ existingAboutYou }}</p>
                    </div>
                </div>
                <div class="ml-4 flex space-x-2">
                    <button
                        @click="editExisting"
                        :disabled="isLoading"
                        class="p-1 text-neutral-800 hover:text-yellow-400 hover:bg-neutral-800 transition-colors disabled:opacity-50 [clip-path:polygon(0_0,100%_0,100%_100%,8px_100%,0_16px)]"
                        title="Edit this description"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </button>
                    <button
                        @click="deleteExisting"
                        :disabled="isLoading"
                        class="p-1 text-rose-600 hover:text-rose-600 hover:bg-neutral-800 transition-colors disabled:opacity-50 [clip-path:polygon(0_0,100%_0,100%_100%,8px_100%,0_16px)]"
                        title="Delete description"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Empty State for Current Description -->
        <div v-else-if="!existingAboutYou || !existingAboutYou.trim()" class="bg-gray-50 border border-gray-200 p-4 text-center">
            <div class="text-gray-400 mb-2">
                <svg class="mx-auto h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
            </div>
            <p class="text-sm text-gray-500">No description configured yet</p>
            <p class="text-xs text-gray-400 mt-1">Add information about yourself below</p>
        </div>

        <!-- Edit/Add Section -->
        <div v-if="isEditing || !existingAboutYou || !existingAboutYou.trim()">
            <label for="about-you" class="block text-sm font-medium text-neutral-400 mb-2">
                {{ existingAboutYou && existingAboutYou.trim() ? 'Edit Your Information' : 'Add Your Information' }}
            </label>
            <textarea
                id="about-you"
                v-model="aboutYou"
                rows="8"
                class="w-full border-yellow-400 shadow-sm text-sm text-neutral-800 [clip-path:polygon(0_0,100%_0,100%_100%,15px_100%,0_calc(100%-14px))]"
                placeholder="Describe yourself, your profession, interests, goals, and expertise level..."
                :disabled="isLoading"
            ></textarea>
            <p class="mt-2 text-xs text-neutral-400">
                {{ aboutYou.length }}/2000 characters
            </p>

            <!-- Action Buttons for Editing -->
            <div v-if="isEditing" class="flex space-x-3 mt-4">
                <button
                    @click="saveChanges"
                    :disabled="isLoading || !aboutYou.trim()"
                    class="px-4 py-2 bg-cyan-700 text-sm text-white hover:bg-cyan-800 focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed [clip-path:polygon(0_0,100%_0,100%_100%,15px_100%,0_26px)]"
                >
                    <span v-if="isLoading">Saving...</span>
                    <span v-else>Save Changes</span>
                </button>
                <button
                    @click="cancelEdit"
                    :disabled="isLoading"
                    class="px-4 py-2 bg-gray-300 text-sm text-gray-700 hover:bg-gray-400 focus:outline-none disabled:opacity-50 [clip-path:polygon(0_0,100%_0,100%_100%,15px_100%,0_26px)]"
                >
                    Cancel
                </button>
            </div>
        </div>

        <!-- Tips & Suggestions -->
        <div class="bg-neutral-800 p-4 [clip-path:polygon(0_0,100%_0,100%_100%,15px_100%,0_calc(100%-14px))]">
            <div class="text-md font-semibold text-yellow-400 mb-2 flex items-center">
                <svg class="w-6 h-6 mr-2 mb-1 text-yellow-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                    <path d="M11 3a1 1 0 10-2 0v1a1 1 0 102 0V3zM15.657 5.757a1 1 0 00-1.414-1.414l-.707.707a1 1 0 001.414 1.414l.707-.707zM18 10a1 1 0 01-1 1h-1a1 1 0 110-2h1a1 1 0 011 1zM5.05 6.464A1 1 0 106.464 5.05l-.707-.707a1 1 0 00-1.414 1.414l.707.707zM5 10a1 1 0 01-1 1H3a1 1 0 110-2h1a1 1 0 011 1zM8 16v-1h4v1a2 2 0 11-4 0zM12 14c.015-.34.208-.646.477-.859a4 4 0 10-4.954 0c.27.213.462.519.477.859h4z"/>
                </svg> Tips for Better Results
            </div>
            <ul class="text-sm text-yellow-400 space-y-1">
                <li v-for="suggestion in suggestions" :key="suggestion" class="flex items-start">
                    <span class="mr-2 mt-0.5">•</span>
                    <span>{{ suggestion }}</span>
                </li>
            </ul>
        </div>

        <!-- Examples -->
        <div v-if="!isEditing">
            <div class="text-md font-semibold text-yellow-400 mb-2 flex items-center">
                <svg class="w-5 h-5 mr-2 text-yellow-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z"/>
                </svg>
                Example Descriptions
            </div>
            <div class="space-y-2">
                <div
                    v-for="(example, index) in examples"
                    :key="index"
                    class="p-3 bg-neutral-800 text-neutral-200 hover:text-neutral-900 hover:bg-yellow-400 transition-colors cursor-pointer [clip-path:polygon(0_0,100%_0,100%_100%,15px_100%,0_calc(100%-14px))]"
                    @click="insertExample(example)"
                >
                    <p class="text-sm">{{ example }}</p>
                    <p class="text-xs text-neutral-500 mt-1">Click to add to your description</p>
                </div>
            </div>
        </div>
    </div>
</template>
