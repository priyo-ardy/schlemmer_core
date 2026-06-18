<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { toast } from 'vue3-toastify';

defineOptions({layout:AuthenticatedLayout});
const props = defineProps({
    allIds: Array,
    users: Array,
    header: {
        type: Object,
        default: () => ({})
    }
});

const isEditing = ref(false);

watch(isEditing, (newValue) => {
    document.title = (newValue ? 'Edit' : 'View') + ' PMFEA Template Data';
});

const pageTitle = computed(() => (isEditing.value ? 'Edit' : 'View') + ' PMFEA Template Data | ' + props.header?.name);

const form = useForm({
    name: props.header?.name || '',
    revision: props.header?.revision || '0',
    remark: props.header?.remark || '',
    processItems: props.header?.details?.length > 0 
        ? props.header.details 
        : [{
            id: null,
            previous_problem: '',
            requirements: '',
            potential_failure_mode: '',
            potential_effect_of_failure: '',
            potential_cause_of_failure: '',
            controls_prevention: '',
            controls_detection: ''
        }]
});

const addRow = () => {
    form.processItems.push({
        id: null,
        previous_problem: '',
        requirements: '',
        potential_failure_mode: '',
        potential_effect_of_failure: '',
        potential_cause_of_failure: '',
        controls_prevention: '',
        controls_detection: ''
    });
};

const removerRow = (index) => {
    if (form.processItems.length > 1) {
        form.processItems.splice(index, 1);
    }
};

const cancelForm = () => {
    router.visit('/process');
};

const validateAndSave = () => {
    form.clearErrors();
    let isValid = true;
    const nameValue = form.name ? form.name.trim() : '';

    if (!nameValue) {
        form.setError('name', 'Function name is required');
        isValid = false;
    } else if (nameValue.length > 150) {
        form.setError('name', 'Function name cannot exceed 150 characters');
        isValid = false;
    }

    form.processItems.forEach((item, index) => {
        // Contoh pengecekan untuk setiap kolom yang wajib diisi
        if (!item.requirements || item.requirements.trim() === '') {
            form.setError(`processItems.${index}.requirements`, 'Required');
            isValid = false;
        }
        if (!item.potential_failure_mode || item.potential_failure_mode.trim() === '') {
            form.setError(`processItems.${index}.potential_failure_mode`, 'Required');
            isValid = false;
        }
        if (!item.potential_effect_of_failure || item.potential_effect_of_failure.trim() === '') {
            form.setError(`processItems.${index}.potential_effect_of_failure`, 'Required');
            isValid = false;
        }
        if (!item.potential_cause_of_failure || item.potential_cause_of_failure.trim() === '') {
            form.setError(`processItems.${index}.potential_cause_of_failure`, 'Required');
            isValid = false;
        }
        if (!item.controls_prevention || item.controls_prevention.trim() === '') {
            form.setError(`processItems.${index}.controls_prevention`, 'Required');
            isValid = false;
        }
        if (!item.controls_detection || item.controls_detection.trim() === '') {
            form.setError(`processItems.${index}.controls_detection`, 'Required');
            isValid = false;
        }
    });

    if(!isValid){
        return;
    }

    form.put(`/process/${props.header?.id}`, {
        onSuccess: () => {
            isEditing.value = false;
        },
         onError: (errors) => {
            const firstErrorMessage = Object.values(errors)[0];
            toast.error(firstErrorMessage);
        }
    });
};

const firstData = () => {
    const firstId = props.allIds[0];
    if(props.header?.id === firstId){
        toast.info('You are in the first data');
        return;
    }
    router.get(`/process/${firstId}`);
};

const lastData = () => {
    const lastId = props.allIds[props.allIds.length - 1];

    if(props.header?.id === lastId){
        toast.info('You are in the last data');
        return;
    }
    router.get(`/process/${lastId}`);
};

const prevData = () => {
    const currentIndex = props.allIds.indexOf(props.header.id);
    if (currentIndex > 0) {
        router.get(`/process/${props.allIds[currentIndex - 1]}`);
    }
    else{
        toast.info('You are in the first data');
        return;
    }
};

const nextData = () => {
    const currentIndex = props.allIds.indexOf(props.header.id);
    if (currentIndex < props.allIds.length - 1) {
        router.get(`/process/${props.allIds[currentIndex + 1]}`);
    }else{
        toast.info('You are in the last data');
        return;
    }
};

const newForm = () => {
    router.get('/process/create');
}
</script>


<template>
    <Head :title="pageTitle" />

    <div class="flex min-h-screen bg-slate-50 font-sans antialiased text-slate-800">
        <div class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">{{ isEditing ? 'Edit' : 'View' }} PMFEA Process Function | {{ props.header?.name }}</h1>
                    <p class="text-xs text-slate-500 mt-1">{{ isEditing ? 'Edit' : 'View' }} PMFEA template process function {{ props.header?.name }}.</p>
                </div>
                <div class="sticky top-0 z-40 bg-white/95 backdrop-blur-sm border-b border-slate-200 px-3 py-2 shadow-sm">
                <!-- <div class="flex items-center justify-between rounded-xl bg-white p-3 shadow-sm"> -->
                    <div class="flex items-center justify-between w-full">
                        <!-- Toolbar -->
                        <div class="flex items-center gap-3"> 
                            <div class="flex items-center gap-1.5">
                                <Link href="/process" class="group flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-bold text-slate-500 hover:text-blue-600 hover:bg-blue-50/50 rounded-lg transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform group-hover:-translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                    </svg>
                                    <span class="hidden sm:inline">Back</span>
                                </Link>

                                <div class="w-px h-6 bg-slate-300 mx-1" v-if="!isEditing"></div>

                                <div class="flex items-center bg-slate-100/80 p-0.5 rounded-lg border border-slate-200/50 shadow-inner" v-if="!isEditing">
                                    <button @click="firstData" :disabled="allIds.indexOf(header.id) === 0" type="button" class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-white hover:shadow-sm rounded-md transition-all" title="First">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                                        </svg>
                                    </button>
                                    <button type="button" @click="prevData" :disabled="allIds.indexOf(header.id) === 0" class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-white hover:shadow-sm rounded-md transition-all" title="Previous">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                        </svg>
                                    </button>
                                    <button type="button" @click="nextData" :disabled="allIds.indexOf(header.id) === 0" class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-white hover:shadow-sm rounded-md transition-all" title="Next">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>
                                    <button type="button" @click="lastData" :disabled="allIds.indexOf(header.id) === 0" class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-white hover:shadow-sm rounded-md transition-all" title="Last">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="w-px h-6 bg-slate-300 mx-1"></div>

                            <div class="flex items-center gap-1.5">
                                
                                <button v-if="isEditing" type="button" @click="validateAndSave" :disabled="form.processing"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-lg shadow-sm transition active:scale-95 disabled:opacity-70 disabled:cursor-not-allowed">
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
                                    type="button" 
                                    @click="newForm"
                                    class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-bold text-blue-600 bg-blue-50 border border-blue-100 rounded-lg transition-all hover:bg-blue-100 active:scale-95 shadow-sm"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    
                                    <span>New</span>
                                </button>
                                <button type="button" 
                                    @click="isEditing ? (isEditing = false, form.reset()) : isEditing = true" 
                                    :class="[
                                        'flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-lg transition-all shadow-sm border',
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
                                    <button type="button" class="flex items-center gap-1.5 px-3 py-1.5 bg-white text-slate-600 hover:text-blue-600 border border-slate-200 hover:border-blue-300 hover:bg-blue-50/50 text-xs font-bold rounded-lg transition-all shadow-sm">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                                        </svg>
                                        Tools
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400 group-hover:text-blue-600 group-hover:rotate-180 transition-all duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                    <div class="absolute right-0 top-full mt-1.5 w-40 bg-white border border-slate-100 rounded-xl shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50 overflow-hidden origin-top-right transform scale-95 group-hover:scale-100">
                                        <div class="py-1 flex flex-col">
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

                                <button type="button" class="p-1.5 text-slate-400 hover:text-white hover:bg-rose-500 rounded-lg transition-colors" title="Delete Record">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>

                            </div>
                        </div>
                        <!-- End of toolbar -->
                    </div>
                </div>
            </div>

            <!-- Buat header -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 mb-6">
                <div class="grid grid-cols-1 lg:grid-cols-10 gap-6 items-start">
                    <div class="lg:col-span-3">
                        <div class="flex items-center gap-1.5 mb-2">
                            <label class="block text-[10px] font-extrabold text-slate-600 uppercase tracking-wider">
                                Function Name <span class="text-rose-500">*</span>
                            </label>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400 cursor-help" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7" />
                                </svg>
                            </div>
                            <input type="text" v-model="form.name" placeholder="Enter process function name..." maxlength="255" autocomplete="off"
                            :disabled="!isEditing"
                            :class="[
                                'w-full pl-10 pr-4 py-2.5 bg-slate-50 border focus:bg-white focus:ring-2 rounded-xl text-xs font-medium transition-all outline-none',
                                form.errors.name 
                                    ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-100 text-rose-600' 
                                    : 'border-slate-200 focus:border-blue-500 focus:ring-blue-100 text-slate-700'
                            ]">
                        </div>
                        <p v-if="form.errors.name" class="mt-1.5 text-[10px] font-bold text-rose-500">{{ form.errors.name }}</p>
                        <p v-else class="mt-1.5 text-[10px] font-medium text-slate-400">Unique process function name</p>
                    </div>

                    <div class="lg:col-span-1 flex flex-col justify-start">
                        <div class="flex items-center gap-1.5 mb-2">
                            <label class="block text-[10px] font-extrabold text-slate-600 uppercase tracking-wider">
                                Revision
                            </label>
                        </div>
                        <div class="w-full flex">
                            <div class="inline-flex items-center justify-center gap-1.5 px-2 py-2 bg-sky-50 border border-sky-200 text-sky-700 rounded-full w-full shadow-sm select-none cursor-not-allowed whitespace-nowrap">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                                </svg>
                                <span class="text-xs font-black tracking-widest">v.<span class="text-sm">{{ props.header?.revision }}</span></span>
                                <input type="hidden" name="revision" value="0">
                            </div>
                        </div>
                        <p class="mt-1.5 text-[10px] font-medium text-slate-400 text-center">Initial</p>
                    </div>

                    <div class="lg:col-span-6">
                        <div class="flex items-center gap-1.5 mb-2">
                            <label class="block text-[10px] font-extrabold text-slate-600 uppercase tracking-wider">
                                Remarks (Optional)
                            </label>
                        </div>
                        <textarea v-model="form.remark" rows="2" placeholder="Provide detailed context, e.g., on equipment conditions, key dependencies, environmental factors..." 
                            :disabled="!isEditing"
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 rounded-xl text-xs font-medium text-slate-700 transition-all outline-none resize-none leading-relaxed"></textarea>
                    </div>
                    
                </div>
            </div>

            <!-- Buat details --> 
            <div class="w-full bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden mb-6 flex flex-col">
                
                <div class="overflow-auto min-h-96">
                    <table class="w-full min-w-max divide-y divide-slate-200 text-left whitespace-nowrap">
                        
                        <thead class="bg-slate-50 text-[10px] font-extrabold text-slate-500 uppercase tracking-wider sticky top-0 z-20 shadow-sm">
                            <tr>
                                <th class="px-4 py-3 text-center w-16">No.</th>
                                <th class="px-4 py-3 min-w-[150px]">Previous</th>
                                <th class="px-4 py-3 min-w-[350px]">Requirements</th>
                                <th class="px-4 py-3 min-w-[350px]">Potential Failure Mode</th>
                                <th class="px-4 py-3 min-w-[350px]">Potential Effect(s)</th>
                                <th class="px-4 py-3 min-w-[350px]">Potential Cause(s)</th>
                                <th class="px-4 py-3 min-w-[350px]">Controls Prevention</th>
                                <th class="px-4 py-3 min-w-[350px]">Control Detection</th>
                                <th class="px-4 py-3 text-center w-24" v-show="isEditing">Action</th>
                            </tr>
                        </thead>
                        
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="(item, index) in form.processItems" :key="index" class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-4 py-3 text-center text-xs font-semibold text-slate-600">
                                    <div class="py-2">{{ index + 1 }}.</div>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="text" :disabled="!isEditing" v-model="item.previous_problem" placeholder="Enter previous ..." class="w-full px-3 py-2 bg-slate-50 border border-slate-200 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 rounded-lg text-xs transition-all outline-none">
                                </td>
                                <td class="px-4 py-3">
                                    <input type="text" v-model="item.requirements" placeholder="Enter requirements..." required 
                                        :disabled="!isEditing"
                                        :class="[
                                            'w-full px-3 py-2 bg-slate-50 border focus:bg-white focus:ring-2 rounded-lg text-xs transition-all outline-none',
                                            form.errors[`processItems.${index}.requirements`] 
                                                ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-100 placeholder-rose-300' 
                                                : 'border-slate-200 focus:border-blue-500 focus:ring-blue-100'
                                        ]">
                                    <span v-if="form.errors[`processItems.${index}.requirements`]" class="block mt-1 text-[9px] font-bold text-rose-500">
                                        {{ form.errors[`processItems.${index}.requirements`] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="text" v-model="item.potential_failure_mode" placeholder="Failure mode..." required
                                        :disabled="!isEditing"
                                        :class="[
                                            'w-full px-3 py-2 bg-slate-50 border focus:bg-white focus:ring-2 rounded-lg text-xs transition-all outline-none',
                                            form.errors[`processItems.${index}.potential_failure_mode`] 
                                                ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-100 placeholder-rose-300' 
                                                : 'border-slate-200 focus:border-blue-500 focus:ring-blue-100'
                                        ]">
                                    <span v-if="form.errors[`processItems.${index}.potential_failure_mode`]" class="block mt-1 text-[9px] font-bold text-rose-500">
                                        {{ form.errors[`processItems.${index}.potential_failure_mode`] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="text" v-model="item.potential_effect_of_failure" placeholder="Effects..." required
                                        :disabled="!isEditing"
                                        :class="[
                                            'w-full px-3 py-2 bg-slate-50 border focus:bg-white focus:ring-2 rounded-lg text-xs transition-all outline-none',
                                            form.errors[`processItems.${index}.potential_effect_of_failure`] 
                                                ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-100 placeholder-rose-300' 
                                                : 'border-slate-200 focus:border-blue-500 focus:ring-blue-100'
                                        ]">
                                    <span v-if="form.errors[`processItems.${index}.potential_effect_of_failure`]" class="block mt-1 text-[9px] font-bold text-rose-500">
                                        {{ form.errors[`processItems.${index}.potential_effect_of_failure`] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="text" v-model="item.potential_cause_of_failure" placeholder="Causes..." required
                                        :disabled="!isEditing"
                                        :class="[
                                            'w-full px-3 py-2 bg-slate-50 border focus:bg-white focus:ring-2 rounded-lg text-xs transition-all outline-none',
                                            form.errors[`processItems.${index}.potential_cause_of_failure`] 
                                                ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-100 placeholder-rose-300' 
                                                : 'border-slate-200 focus:border-blue-500 focus:ring-blue-100'
                                        ]">
                                    <span v-if="form.errors[`processItems.${index}.potential_cause_of_failure`]" class="block mt-1 text-[9px] font-bold text-rose-500">
                                        {{ form.errors[`processItems.${index}.potential_cause_of_failure`] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="text" v-model="item.controls_prevention" placeholder="Prevention..." required
                                        :disabled="!isEditing"
                                        :class="[
                                            'w-full px-3 py-2 bg-slate-50 border focus:bg-white focus:ring-2 rounded-lg text-xs transition-all outline-none',
                                            form.errors[`processItems.${index}.controls_prevention`] 
                                                ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-100 placeholder-rose-300' 
                                                : 'border-slate-200 focus:border-blue-500 focus:ring-blue-100'
                                        ]">
                                    <span v-if="form.errors[`processItems.${index}.controls_prevention`]" class="block mt-1 text-[9px] font-bold text-rose-500">
                                        {{ form.errors[`processItems.${index}.controls_prevention`] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="text" v-model="item.controls_detection" placeholder="Detection..." required
                                        :disabled="!isEditing"
                                        :class="[
                                            'w-full px-3 py-2 bg-slate-50 border focus:bg-white focus:ring-2 rounded-lg text-xs transition-all outline-none',
                                            form.errors[`processItems.${index}.controls_detection`] 
                                                ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-100 placeholder-rose-300' 
                                                : 'border-slate-200 focus:border-blue-500 focus:ring-blue-100'
                                        ]">
                                    <span v-if="form.errors[`processItems.${index}.controls_detection`]" class="block mt-1 text-[9px] font-bold text-rose-500">
                                        {{ form.errors[`processItems.${index}.controls_detection`] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button type="button" @click="addRow" class="p-1.5 bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white rounded-lg transition-colors shadow-sm" v-show="isEditing">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                            </svg>
                                        </button>
                                        <button type="button" @click="removerRow(index)" :disabled="form.processItems.length === 1" 
                                            class="p-1.5 bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white rounded-lg transition-colors shadow-sm disabled:opacity-50 disabled:cursor-not-allowed" v-show="isEditing">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>