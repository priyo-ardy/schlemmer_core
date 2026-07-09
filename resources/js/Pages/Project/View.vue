<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm, usePage, router, Link } from '@inertiajs/vue3';
import { toast } from 'vue3-toastify';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import axios from 'axios';

defineOptions({layout:AuthenticatedLayout});
const props =  defineProps({
    allIds: Array,
    header: {
        type: Object,
        default: () => {}
    },
    details: Array

});
const page = usePage();
const errors = computed(() => page.props.errors || {});
const selectedRowIndex = ref(null);
const loadingLogs = ref(false)

watch(
    errors,
    (newErrors) => {
        if(newErrors && newErrors.error){
            toast.error(newErrors.error);
        }
    },
    {
        deep: true
    }
);

defineEmits(['close']);
const isEditing = ref (false);
const showModal = ref(false);


// Navigation
const firstData = () => {
    const firstId = props.allIds[0];
    if(props.header?.id === firstId){
        toast.info('You are in the first data');
        return;
    }
    router.get(`/projects/${firstId}`);
};

const lastData = () => {
    const lastId = props.allIds[props.allIds.length - 1];

    if(props.header?.id === lastId){
        toast.info('You are in the last data');
        return;
    }
    router.get(`/projects/${lastId}`);
};

const prevData = () => {
    const currentIndex = props.allIds.indexOf(props.header.id);
    if (currentIndex > 0) {
        router.get(`/projects/${props.allIds[currentIndex - 1]}`);
    }
    else{
        toast.info('You are in the first data');
        return;
    }
};

const nextData = () => {
    const currentIndex = props.allIds.indexOf(props.header.id);
    if (currentIndex < props.allIds.length - 1) {
        router.get(`/projects/${props.allIds[currentIndex + 1]}`);
    }else{
        toast.info('You are in the last data');
        return;
    }
};

const newForm = () => {
    router.get('/projects/create');
}
// End of navigation

</script>

<template>
    <Head :title="`Project Details | ${header.code}`" />
    <div class="flex min-h-screen bg-slate-50 font-sans antialiased text-slate-800">
        <div class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                <!-- page heading title -->
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">{{ isEditing ? 'Edit' : 'View' }} Project Details | {{ props.header?.code }}</h1>
                    <p class="text-xs text-slate-500 mt-1">{{ isEditing ? 'Edit' : 'View' }} project details {{ props.header?.code }}.</p>
                </div>

                <div class="sticky top-0 z-40 bg-white/95 backdrop-blur-sm border-b border-slate-200 px-3 py-2 shadow-sm">
                    <div class="flex items-center justify-between w-full">
                        <!-- Toolbar -->
                        <div class="flex items-center gap-3"> 
                            <div class="flex items-center gap-1.5">
                                <Link href="/projects" class="group flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-bold text-slate-500 hover:text-blue-600 hover:bg-blue-50/50  transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform group-hover:-translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                    </svg>
                                    <span class="hidden sm:inline">Back</span>
                                </Link>

                                <div class="w-px h-6 bg-slate-300 mx-1" v-if="!isEditing"></div>

                                <div class="flex items-center bg-slate-100/80 p-0.5  border border-slate-200/50 shadow-inner" v-if="!isEditing">
                                    <button @click="firstData" :disabled="allIds.indexOf(header.id) === 0" type="button" class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-white hover:shadow-sm  transition-all" title="First">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                                        </svg>
                                    </button>
                                    <button type="button" @click="prevData" :disabled="allIds.indexOf(header.id) === 0" class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-white hover:shadow-sm  transition-all" title="Previous">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                        </svg>
                                    </button>
                                    <button type="button" @click="nextData" :disabled="allIds.indexOf(header.id) === 0" class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-white hover:shadow-sm  transition-all" title="Next">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>
                                    <button type="button" @click="lastData" :disabled="allIds.indexOf(header.id) === 0" class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-white hover:shadow-sm  transition-all" title="Last">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="w-px h-6 bg-slate-300 mx-1"></div>

                            <div class="flex items-center gap-1.5">
                                
                                <button v-if="isEditing" type="button" @click="validateAndSave" :disabled="form.processing"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs  shadow-sm transition active:scale-95 disabled:opacity-70 disabled:cursor-not-allowed">
                                    <svg v-if="form.processing" class="animate-spin h-3.5 w-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                                    </svg>
                                    <span>{{ form.processing ? 'Saving...' : 'Save' }}</span>
                                </button>
                                <button 
                                    v-if="!isEditing"
                                    type="button" 
                                    @click="newForm"
                                    class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-bold text-green-600 bg-blue-50 border border-blue-100  transition-all hover:bg-blue-100 active:scale-95 shadow-sm"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    
                                    <span>New</span>
                                </button>
                                <button type="button" 
                                    @click="isEditing ? (isEditing = false, form.reset()) : isEditing = true" 
                                    :class="[
                                        'flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold  transition-all shadow-sm border',
                                        isEditing 
                                            ? 'bg-slate-100 text-slate-600 border-slate-200 hover:bg-slate-200' 
                                            : 'bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white border-blue-100 hover:border-blue-600'
                                    ]">
                                    <svg v-if="!isEditing" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    <span>{{ isEditing ? 'Cancel' : 'Edit' }}</span>
                                </button>
                                
                                <div class="w-px h-6 bg-slate-300 mx-1"></div>

                                <div class="relative group">
                                    <button type="button" class="flex items-center gap-1.5 px-3 py-1.5 bg-white text-slate-600 hover:text-blue-600 border border-slate-200 hover:border-blue-300 hover:bg-blue-50/50 text-xs font-bold  transition-all shadow-sm">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                                        </svg>
                                        Tools
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400 group-hover:text-blue-600 group-hover:rotate-180 transition-all duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                    <div class="absolute right-0 top-full mt-1.5 w-40 bg-white border border-slate-100 shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50 overflow-hidden origin-top-right transform scale-95 group-hover:scale-100">
                                        <div class="py-1 flex flex-col">
                                            <button type="button" @click="openLogs(props.header.id)" :disabled="loadingLogs" class="w-full text-left px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-blue-50 hover:text-blue-600 flex items-center gap-2 transition-colors">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                                                    <path d="M21 6.375c0 2.692-4.03 4.875-9 4.875S3 9.067 3 6.375 7.03 1.5 12 1.5s9 2.183 9 4.875Z" />
                                                    <path d="M12 12.75c2.685 0 5.19-.586 7.078-1.609a8.283 8.283 0 0 0 1.897-1.384c.016.121.025.244.025.368C21 12.817 16.97 15 12 15s-9-2.183-9-4.875c0-.124.009-.247.025-.368a8.285 8.285 0 0 0 1.897 1.384C6.809 12.164 9.315 12.75 12 12.75Z" />
                                                    <path d="M12 16.5c2.685 0 5.19-.586 7.078-1.609a8.282 8.282 0 0 0 1.897-1.384c.016.121.025.244.025.368 0 2.692-4.03 4.875-9 4.875s-9-2.183-9-4.875c0-.124.009-.247.025-.368a8.284 8.284 0 0 0 1.897 1.384C6.809 15.914 9.315 16.5 12 16.5Z" />
                                                    <path d="M12 20.25c2.685 0 5.19-.586 7.078-1.609a8.282 8.282 0 0 0 1.897-1.384c.016.121.025.244.025.368 0 2.692-4.03 4.875-9 4.875s-9-2.183-9-4.875c0-.124.009-.247.025-.368a8.284 8.284 0 0 0 1.897 1.384C6.809 19.664 9.315 20.25 12 20.25Z" />
                                                </svg>
                                                <svg v-if="loadingLogs" class="animate-spin h-3 w-3 text-white" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                
                                                {{ loadingLogs ? 'Loading...' : 'View Change Logs' }}
                                            </button>
                                            <button type="button" class="w-full text-left px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-blue-50 hover:text-blue-600 flex items-center gap-2 transition-colors">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                                </svg>
                                                Print Data
                                            </button>
                                            </div>
                                    </div>
                                </div>

                                <div class="w-px h-4 bg-slate-200 mx-1"></div>

                                <button type="button" @click="deleteSelected(header?.id)" class="p-1.5 text-slate-400 hover:text-white hover:bg-rose-500  transition-colors" title="Delete Record">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>

                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form header -->
            <div class="sticky top-0 z-40 bg-white/95 backdrop-blur-sm border-b border-slate-200 px-3 py-2 shadow-sm">
            </div>

            <!-- Form details -->
            <div class="sticky top-0 z-40 bg-white/95 backdrop-blur-sm border-b border-slate-200 px-3 py-2 shadow-sm">
            </div>
        </div>
    </div>
</template>