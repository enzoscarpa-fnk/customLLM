<script setup>
import { ref, computed, watch, onMounted, markRaw } from 'vue'
import { useForm, router, usePage } from '@inertiajs/vue3'
import AboutYouSection from './AboutYouSection.vue'
import BehaviorSection from './BehaviorSection.vue'
import CommandsSection from './CommandsSection.vue'
import IconAbout from '@/Components/icons/IconAbout.vue';
import IconBehavior from '@/Components/icons/IconBehavior.vue';
import IconCommands from '@/Components/icons/IconCommands.vue';
import { route } from 'ziggy-js';

const props = defineProps({
    show: Boolean,
    userInstructions: Object
})

const emit = defineEmits(['close', 'saved'])

const userInstructionsData = ref({})

const form = useForm({
    about_you: '',
    behavior: '',
    custom_commands: [],
    enabled: true
})

const activeTab = ref('about')

const tabs = [
    { id: 'about', label: 'About You', icon: markRaw(IconAbout) },
    { id: 'behavior', label: 'Behavior', icon: markRaw(IconBehavior) },
    { id: 'commands', label: 'Commands', icon: markRaw(IconCommands) }
];


// Initialize data from props
const initializeData = () => {
    const instructions = props.userInstructions || {}

    userInstructionsData.value = {
        about_you: instructions.about_you || '',
        behavior: instructions.behavior || '',
        custom_commands: instructions.custom_commands || [],
        enabled: instructions.enabled !== undefined ? instructions.enabled : true
    }

    // Update form with existing data
    form.about_you = userInstructionsData.value.about_you
    form.behavior = userInstructionsData.value.behavior
    form.custom_commands = userInstructionsData.value.custom_commands
    form.enabled = userInstructionsData.value.enabled
}

onMounted(() => {
    initializeData()
})

const validateCommands = () => {
    const commandNames = form.custom_commands.map(cmd => cmd.name)
    const uniqueNames = new Set(commandNames)
    return commandNames.length === uniqueNames.size
}

const save = () => {
    if (!validateCommands()) {
        alert('Duplicate command names are not allowed.')
        return
    }

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

const toggleEnabled = async (event) => {
    form.enabled = event.target.checked
    try {
        router.post(route('instructions.toggle'), {
            enabled: form.enabled
        }, {
            preserveState: true,
            onSuccess: (page) => {
                userInstructionsData.value = page.props.userInstructions || {}
                emit('saved')
            }
        })
    } catch (error) {
        console.error('Error toggling instructions:', error)
    }
}

const closeModal = () => {
    emit('close')
}

const handleDataUpdated = (newInstructions) => {
    userInstructionsData.value = newInstructions || {}

    // Update form data to reflect changes
    form.about_you = userInstructionsData.value.about_you || ''
    form.behavior = userInstructionsData.value.behavior || ''
    form.custom_commands = userInstructionsData.value.custom_commands || []
    form.enabled = userInstructionsData.value.enabled !== undefined ? userInstructionsData.value.enabled : true

    emit('saved')
}

// Watch for show prop changes to reset form
watch(() => props.show, (newShow) => {
    if (newShow) {
        initializeData()
    }
})

// Watch for userInstructions prop changes
watch(() => props.userInstructions, (newInstructions) => {
    if (newInstructions) {
        initializeData()
    }
}, { deep: true })
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
                class="inline-block w-full max-w-4xl p-6 my-4 overflow-hidden text-left align-middle transition-all transform text-neutral-200 bg-neutral-900 shadow-xl [clip-path:polygon(0_0,100%_0,100%_100%,15px_100%,0_calc(100%-14px))]"
                @click.stop
            >
                <!-- Header -->
                <div class="flex items-center justify-between pb-4 border-b-2 border-yellow-400">
                    <div>
                        <h3 class="text-lg font-medium text-neutral-200">
                            Custom Instructions
                        </h3>
                        <p class="text-sm text-neutral-400">
                            Personalize how the AI assistant interacts with you
                        </p>
                    </div>
                    <button
                        @click="closeModal"
                        class="text-neutral-300 hover:text-pink-600 transition-colors"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Enable/Disable Toggle -->
                <div class="flex items-center justify-between py-4 border-b-2 border-yellow-400">
                    <div>
                        <label class="text-sm font-medium text-neutral-200">
                            Enable Custom Instructions
                        </label>
                        <p class="text-xs text-neutral-400">
                            Apply these instructions to all new conversations
                        </p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input
                            :checked="form.enabled"
                            type="checkbox"
                            class="sr-only peer"
                            @change="toggleEnabled"
                        >
                        <div class="w-11 h-6 bg-neutral-700 peer-focus:outline-none peer peer-checked:after:translate-x-full peer-checked:after:border-neutral-800 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-neutral-800 after:border-yellow-400 after:border after:h-5 after:w-5 after:transition-all peer-checked:bg-yellow-400"></div>
                    </label>
                </div>

                <!-- Tabs -->
                <div class="flex space-x-1 bg-neutral-700 p-1 mt-4 [clip-path:polygon(0_0,100%_0,100%_100%,15px_100%,0_calc(100%-14px))]">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        @click="activeTab = tab.id"
                        class="flex-1 flex items-center justify-center px-3 py-2 text-sm font-medium transition-colors"
                        :class="{
                            'bg-yellow-400 text-neutral-900 shadow-sm [clip-path:polygon(0_0,100%_0,100%_100%,15px_100%,0_calc(100%-14px))]': activeTab === tab.id,
                            'text-neutral-200 hover:text-yellow-400': activeTab !== tab.id
                        }"
                    >
                        <component :is="tab.icon" class="mr-2 w-5 h-5" />
                        {{ tab.label }}
                    </button>
                </div>

                <!-- Tab Content -->
                <div class="mt-6 min-h-[400px]">
                    <AboutYouSection
                        v-if="activeTab === 'about'"
                        v-model="form.about_you"
                        :existing-data="userInstructionsData"
                        @data-updated="handleDataUpdated"
                    />
                    <BehaviorSection
                        v-if="activeTab === 'behavior'"
                        v-model="form.behavior"
                        :existing-data="userInstructionsData"
                        @data-updated="handleDataUpdated"
                    />
                    <CommandsSection
                        v-if="activeTab === 'commands'"
                        v-model="form.custom_commands"
                        :existing-data="userInstructionsData"
                        @data-updated="handleDataUpdated"
                    />
                </div>

                <!-- Footer -->
                <div class="flex items-center justify-end pt-6 mt-3 space-x-3 border-t-2 border-yellow-400">
                    <button
                        @click="closeModal"
                        class="px-4 py-2 text-sm font-medium text-neutral-800 bg-neutral-200 border border-neutral-300 hover:bg-pink-600 hover:border-pink-800 hover:text-neutral-200 focus:outline-none transition-all [clip-path:polygon(0_0,100%_0,100%_100%,15px_100%,0_24px)]"
                    >
                        Close
                    </button>
                    <button
                        @click="save"
                        :disabled="form.processing"
                        class="px-4 py-2 text-sm font-medium text-neutral-800 bg-yellow-400 border border-transparent hover:bg-amber-400 focus:outline-none disabled:opacity-50 [clip-path:polygon(0_0,100%_0,100%_100%,15px_100%,0_24px)]"
                    >
                        <span v-if="form.processing">Saving...</span>
                        <span v-else>Save All Instructions</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
