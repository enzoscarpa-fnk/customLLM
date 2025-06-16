<script setup>
import { ref, computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AboutYouSection from './AboutYouSection.vue'
import BehaviorSection from './BehaviorSection.vue'
import CommandsSection from './CommandsSection.vue'

const props = defineProps({
    show: Boolean,
    userInstructions: Object
})

const emit = defineEmits(['close', 'saved'])

const initializeForm = () => {
    return {
        about_you: props.userInstructions?.about_you || '',
        behavior: props.userInstructions?.behavior || '',
        custom_commands: props.userInstructions?.custom_commands || [],
        enabled: props.userInstructions?.enabled ?? true
    }
}

const form = useForm(initializeForm())

const activeTab = ref('about')

const tabs = [
    { id: 'about', label: 'About You', icon: '👤' },
    { id: 'behavior', label: 'Behavior', icon: '🎯' },
    { id: 'commands', label: 'Commands', icon: '⚡' }
]

const save = () => {
    form.post(route('instructions.store'), {
        preserveState: true,
        onSuccess: () => {
            emit('saved')
            emit('close')
        },
        onError: (errors) => {
            console.error('Save errors:', errors)
        }
    })
}

const closeModal = () => {
    emit('close')
}

// Watch for show prop changes to reset form
watch(() => props.show, (newShow) => {
    if (newShow) {
        const newData = initializeForm()

        form.about_you = newData.about_you
        form.behavior = newData.behavior
        form.custom_commands = newData.custom_commands
        form.enabled = newData.enabled

        console.log('Modal opened with data:', newData)
    }
})
</script>

<template>
    <!-- Modal Backdrop -->
    <div
        v-if="show"
        class="fixed inset-0 z-50 overflow-y-auto"
        @click="closeModal"
    >
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"></div>

            <!-- Modal panel -->
            <div
                class="inline-block w-full max-w-4xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-lg"
                @click.stop
            >
                <!-- Header -->
                <div class="flex items-center justify-between pb-4 border-b border-gray-200">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">
                            Custom Instructions
                        </h3>
                        <p class="text-sm text-gray-500">
                            Personalize how the AI assistant interacts with you
                        </p>
                    </div>
                    <button
                        @click="closeModal"
                        class="text-gray-400 hover:text-gray-600 transition-colors"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Enable/Disable Toggle -->
                <div class="flex items-center justify-between py-4 border-b border-gray-200">
                    <div>
                        <label class="text-sm font-medium text-gray-700">
                            Enable Custom Instructions
                        </label>
                        <p class="text-xs text-gray-500">
                            Apply these instructions to all new conversations
                        </p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input
                            v-model="form.enabled"
                            type="checkbox"
                            class="sr-only peer"
                        >
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>

                <!-- Tabs -->
                <div class="flex space-x-1 bg-gray-100 p-1 rounded-lg mt-4">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        @click="activeTab = tab.id"
                        class="flex-1 flex items-center justify-center px-3 py-2 text-sm font-medium rounded-md transition-colors"
                        :class="{
                            'bg-white text-blue-600 shadow-sm': activeTab === tab.id,
                            'text-gray-500 hover:text-gray-700': activeTab !== tab.id
                        }"
                    >
                        <span class="mr-2">{{ tab.icon }}</span>
                        {{ tab.label }}
                    </button>
                </div>

                <!-- Tab Content -->
                <div class="mt-6 min-h-[400px]">
                    <AboutYouSection
                        v-if="activeTab === 'about'"
                        v-model="form.about_you"
                    />
                    <BehaviorSection
                        v-if="activeTab === 'behavior'"
                        v-model="form.behavior"
                    />
                    <CommandsSection
                        v-if="activeTab === 'commands'"
                        v-model="form.custom_commands"
                    />
                </div>

                <!-- Footer -->
                <div class="flex items-center justify-end pt-6 space-x-3 border-t border-gray-200">
                    <button
                        @click="closeModal"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        Cancel
                    </button>
                    <button
                        @click="save"
                        :disabled="form.processing"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                    >
                        <span v-if="form.processing">Saving...</span>
                        <span v-else>Save Instructions</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
