<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm, router, Link, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';
import { toast } from 'vue3-toastify';

defineOptions({layout:AuthenticatedLayout, inheritAttrs: false});
const props = defineProps({
    users: Array,
});

const cancelForm = () => {
    router.get('/process');
}

const page = usePage();

const errors = computed(() => page.props.errors || {});

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

const selectedRowIndex = ref(null);

// Tambah baris pada table detail
const processItems = ref([
    {
        previous_problem: '',
        requirements: '',
        potential_failure_mode: '',
        potential_effect_of_failure: '',
        severity: '',
        classification: '',
        potential_cause_of_failure: '',
        occurrence: '',
        controls_prevention: '',
        detection: '',
        rpn: '',
        recommended_action: '',
        controls_detection: ''
    }
]);

const createBlankItem = () => ({
    row_key: Math.random().toString(36).substring(2, 9),
    previous_problem: '',
    requirements: '',
    potential_failure_mode: '',
    potential_effect_of_failure: '',
    severity: '',
    classification: '',
    potential_cause_of_failure: '',
    occurrence: '',
    controls_prevention: '',
    controls_detection: '',
    detection: '',
    rpn: 0,
    recommended_action: 'None'
});

const form = useForm({
    name: '',
    revision: '',
    remark:'',
    processItems: [createBlankItem()]
});

const addRow = () => {
    form.processItems.push(createBlankItem());
}

const calculateRpn = (item) => {
    const s = Number(item.severity);
    const o = Number(item.occurrence);
    const d = Number(item.detection);

    item.rpn = (s && o && d) ? s * o * d : 0;
}

const removerRow = (index) => {
    if(form.processItems.length > 1){
        form.processItems.splice(index, 1);
        if(selectedRowIndex.value === index){
            selectedRowIndex.value = null;
        }else if(selectedRowIndex.value > index){
            selectedRowIndex.value--;
        }
    }
}

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

        if(!item.severity || item.severity === ''){
            form.setError(`processItems.${index}.severity`, 'Required');
            isValid = false;
        }

        if(!item.occurrence || item.occurrence === ''){
            form.setError(`processItems.${index}.occurrence`, 'Required');
            isValid = false;
        }

        if(!item.detection || item.detection === ''){
            form.setError(`processItems.${index}.detection`, 'Required');
            isValid = false;
        }

        if(!item.rpn || item.rpn === ''){
            form.setError(`processItems.${index}.rpn`, 'Required');
            isValid = false;
        }
    });

    if(!isValid){
        return;
    }

    form.post('/process/store', {
        preserveScroll: true,
        onError: (errors) => {
            const firstErrorMessage = Object.values(errors)[0];
            toast.error(firstErrorMessage);
        }
    });
}

// table action
const onNew = () => {
  form.processItems.push(createBlankItem());
  selectedRowIndex.value = form.processItems.length -1;
}

const onInsert = () => {
    if (selectedRowIndex.value !== null && selectedRowIndex.value !== undefined) {
        form.processItems.splice(selectedRowIndex.value, 0, createBlankItem());
    } else {
        onNew();
    }
}

const onDelete = () => {
    if (selectedRowIndex.value !== null) {
        if (form.processItems.length > 1) {
            form.processItems.splice(selectedRowIndex.value, 1);
            // Sesuaikan index focus setelah penghapusan
            if (selectedRowIndex.value >= form.processItems.length) {
                selectedRowIndex.value = form.processItems.length - 1;
            }
        } else {
            toast.warning("There must be at least one row in the table.");
        }
    } else {
        toast.info("Select or click one of the table rows first to delete it.");
    }
}

const onDeleteAll = () => {
    form.processItems = [createBlankItem()];
    selectedRowIndex.value = 0;
}

</script>

<template>
    <Head title="Create New PMFEA Template Data"/>

    <div class="flex min-h-screen bg-slate-50 font-sans antialiased text-slate-800">
        <div class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">Create PMFEA Process Function</h1>
                    <p class="text-xs text-slate-500 mt-1">Create new PMFEA template process function.</p>
                </div>
                <div class="sticky top-0 z-40 bg-white/95 backdrop-blur-sm border-b border-slate-200 px-3 py-2 shadow-sm">
                    <div class="flex items-center justify-between w-full">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-1.5">
                                <Link href="/process" class="group flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-bold text-slate-500 hover:text-blue-600 hover:bg-blue-50/50 transition-colors active:scale-95">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform group-hover:-translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                    </svg>
                                    <span class="hidden sm:inline">Back</span>

                                </Link>
                                
                                <div class="w-px h-6 bg-slate-300 mx-1"></div>

                                <button @click="validateAndSave" :disabled="form.processing"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-sm transition active:scale-95 disabled:opacity-70">
                                    <svg v-if="form.processing" class="animate-spin h-3.5 w-3.5 text-white" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle></svg>
                                    <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                    Save
                                </button>

                                <button @click="cancelForm"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs transition-all active:scale-95 shadow-sm">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>     
            </div>

            <!-- Buat header -->
            <div class="bg-white border border-slate-200/80 shadow-sm p-6 mb-6">
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
                            :class="[
                                'w-full pl-10 pr-4 py-2.5 bg-slate-50 border focus:bg-white focus:ring-2  text-xs font-medium transition-all outline-none',
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
                            <div class="inline-flex items-center justify-center gap-1.5 px-2 py-2 bg-sky-50 border border-sky-200 text-sky-700  w-full shadow-sm select-none cursor-not-allowed whitespace-nowrap">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                                </svg>
                                <span class="text-xs font-black tracking-widest">v.<span class="text-sm">0</span></span>
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
                        <textarea name="remark" rows="2" placeholder="Provide detailed context, e.g., on equipment conditions, key dependencies, environmental factors..." 
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100  text-xs font-medium text-slate-700 transition-all outline-none resize-none leading-relaxed"></textarea>
                    </div>
                    
                </div>
            </div>

            <!-- Buat details --> 
            <div class="w-full bg-white border border-slate-200/80 shadow-sm overflow-hidden mb-6 flex flex-col">
                <!-- table action bar -->
                <div class="overflow-auto border-t border-slate-100 p-4 flex items-center gap-5">
                    <button 
                        @click="onNew" 
                        class="text-sm text-[12px] text-slate-600 hover:text-blue-600 hover:underline hover:cursor-pointer underline-offset-4 transition-all duration-200"
                    >
                        New
                    </button>

                    <button 
                    @click="onInsert" 
                    class="text-sm text-[12px] text-slate-600 hover:text-blue-600 hover:underline hover:cursor-pointer underline-offset-4 transition-all duration-200"
                    >
                    Insert
                    </button>

                    <button 
                    @click="onDelete" 
                    class="text-sm text-[12px] text-slate-600 hover:text-red-600 hover:underline hover:cursor-pointer underline-offset-4 transition-all duration-200"
                    >
                    Delete
                    </button>

                    <button 
                    @click="onDeleteAll" 
                    class="text-sm text-[12px] text-slate-600 hover:text-red-700 hover:underline hover:cursor-pointer underline-offset-4 transition-all duration-200"
                    >
                    Delete All
                    </button>
                </div>

                <!-- datatable -->
                <div class="overflow-auto max-h-[80vh]">
                    <table class="w-full min-w-max divide-y divide-slate-200 text-left whitespace-nowrap">
                        
                        <thead class="bg-blue-100 text-[10px] font-extrabold text-slate-500 uppercase tracking-wider sticky top-0 z-20 shadow-sm">
                            <tr>
                                <th class="px-4 py-3 text-center w-16">No.</th>
                                <th class="px-4 py-3 min-w-[150px]">Previous</th>
                                <th class="px-4 py-3 min-w-[350px]">Requirements</th>
                                <th class="px-4 py-3 min-w-[350px]">Potential Failure Mode</th>
                                <th class="px-4 py-3 min-w-[350px]">Potential Effect(s)</th>
                                <th class="px-4 py-3 min-w-[100px]">Severity</th>
                                <th class="px-4 py-3 min-w-[100px]">Classification</th>
                                <th class="px-4 py-3 min-w-[350px]">Potential Cause(s)</th>
                                <th class="px-4 py-3 min-w-[100px]">Occurrence</th>
                                <th class="px-4 py-3 min-w-[350px]">Controls Prevention</th>
                                <th class="px-4 py-3 min-w-[350px]">Control Detection</th>
                                <th class="p-4 y-3 min-w-[100px]">Detection</th>
                                <th class="p-4 y-3 min-w-[100px]">RPN</th>
                                <th class="p-4 y-3 min-w-[350px]">Recommended Action(s)</th>
                            </tr>
                        </thead>
                        
                        <tbody class="divide-y divide-slate-100">
                            <tr 
                                v-for="(item, index) in form.processItems" 
                                :key="item.row_key" @click="selectedRowIndex = index"
                                @focusin="selectedRowIndex = index"
                                :class="[
                                    'transition-colors duration-150 cursor-pointer',
                                    selectedRowIndex === index 
                                        ? 'bg-blue-50/80 hover:bg-blue-50 border-l-4 border-l-blue-500' 
                                        : 'hover:bg-slate-50/50'
                                ]"
                            >
                                <td class="px-4 py-3 text-center text-xs font-semibold text-slate-600">
                                    <div class="py-2">{{ index + 1 }}.</div>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="text" v-model="item.previous_problem" placeholder="Enter previous ..." class="w-full px-3 py-2 bg-slate-50 border border-slate-200 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 text-xs transition-all outline-none">
                                </td>
                                <td class="px-4 py-3">
                                    <input type="text" v-model="item.requirements" placeholder="Enter requirements..." required 
                                        :class="[
                                            'w-full px-3 py-2 bg-slate-50 border focus:bg-white focus:ring-2 text-xs transition-all outline-none',
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
                                        :class="[
                                            'w-full px-3 py-2 bg-slate-50 border focus:bg-white focus:ring-2 text-xs transition-all outline-none',
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
                                        :class="[
                                            'w-full px-3 py-2 bg-slate-50 border focus:bg-white focus:ring-2 text-xs transition-all outline-none',
                                            form.errors[`processItems.${index}.potential_effect_of_failure`] 
                                                ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-100 placeholder-rose-300' 
                                                : 'border-slate-200 focus:border-blue-500 focus:ring-blue-100'
                                        ]">
                                    <span v-if="form.errors[`processItems.${index}.potential_effect_of_failure`]" class="block mt-1 text-[9px] font-bold text-rose-500">
                                        {{ form.errors[`processItems.${index}.potential_effect_of_failure`] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="number" v-model="item.severity" placeholder="Severity..." required
                                        @input="calculateRpn(item)"
                                        :class="[
                                            'w-full px-3 py-2 bg-slate-50 border focus:bg-white focus:ring-2 text-xs transition-all outline-none',
                                            form.errors[`processItems.${index}.severity`] 
                                                ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-100 placeholder-rose-300' 
                                                : 'border-slate-200 focus:border-blue-500 focus:ring-blue-100'
                                        ]">
                                    <span v-if="form.errors[`processItems.${index}.severity`]" class="block mt-1 text-[9px] font-bold text-rose-500">
                                        {{ form.errors[`processItems.${index}.severity`] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="text" v-model="item.classification" placeholder="Classsification..." required
                                        :class="[
                                            'w-full px-3 py-2 bg-slate-50 border focus:bg-white focus:ring-2 text-xs transition-all outline-none',
                                            form.errors[`processItems.${index}.classification`] 
                                                ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-100 placeholder-rose-300' 
                                                : 'border-slate-200 focus:border-blue-500 focus:ring-blue-100'
                                        ]">
                                    <span v-if="form.errors[`processItems.${index}.classification`]" class="block mt-1 text-[9px] font-bold text-rose-500">
                                        {{ form.errors[`processItems.${index}.classification`] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="text" v-model="item.potential_cause_of_failure" placeholder="Causes..." required
                                        :class="[
                                            'w-full px-3 py-2 bg-slate-50 border focus:bg-white focus:ring-2 text-xs transition-all outline-none',
                                            form.errors[`processItems.${index}.potential_cause_of_failure`] 
                                                ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-100 placeholder-rose-300' 
                                                : 'border-slate-200 focus:border-blue-500 focus:ring-blue-100'
                                        ]">
                                    <span v-if="form.errors[`processItems.${index}.potential_cause_of_failure`]" class="block mt-1 text-[9px] font-bold text-rose-500">
                                        {{ form.errors[`processItems.${index}.potential_cause_of_failure`] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="number" v-model="item.occurrence" placeholder="Occurance..." required
                                        @input="calculateRpn(item)"
                                        :class="[
                                            'w-full px-3 py-2 bg-slate-50 border focus:bg-white focus:ring-2 text-xs transition-all outline-none',
                                            form.errors[`processItems.${index}.occurrence`] 
                                                ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-100 placeholder-rose-300' 
                                                : 'border-slate-200 focus:border-blue-500 focus:ring-blue-100'
                                        ]">
                                    <span v-if="form.errors[`processItems.${index}.occurrence`]" class="block mt-1 text-[9px] font-bold text-rose-500">
                                        {{ form.errors[`processItems.${index}.occurrence`] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="text" v-model="item.controls_prevention" placeholder="Prevention..." required
                                        :class="[
                                            'w-full px-3 py-2 bg-slate-50 border focus:bg-white focus:ring-2 text-xs transition-all outline-none',
                                            form.errors[`processItems.${index}.controls_prevention`] 
                                                ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-100 placeholder-rose-300' 
                                                : 'border-slate-200 focus:border-blue-500 focus:ring-blue-100'
                                        ]">
                                    <span v-if="form.errors[`processItems.${index}.controls_prevention`]" class="block mt-1 text-[9px] font-bold text-rose-500">
                                        {{ form.errors[`processItems.${index}.controls_prevention`] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="text" v-model="item.controls_detection" placeholder="Control Detection..." required
                                        :class="[
                                            'w-full px-3 py-2 bg-slate-50 border focus:bg-white focus:ring-2 text-xs transition-all outline-none',
                                            form.errors[`processItems.${index}.controls_detection`] 
                                                ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-100 placeholder-rose-300' 
                                                : 'border-slate-200 focus:border-blue-500 focus:ring-blue-100'
                                        ]">
                                    <span v-if="form.errors[`processItems.${index}.controls_detection`]" class="block mt-1 text-[9px] font-bold text-rose-500">
                                        {{ form.errors[`processItems.${index}.controls_detection`] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="number" v-model="item.detection" placeholder="Detection..." required
                                        @input="calculateRpn(item)"
                                        :class="[
                                            'w-full px-3 py-2 bg-slate-50 border focus:bg-white focus:ring-2 text-xs transition-all outline-none',
                                            form.errors[`processItems.${index}.detection`] 
                                                ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-100 placeholder-rose-300' 
                                                : 'border-slate-200 focus:border-blue-500 focus:ring-blue-100'
                                        ]">
                                    <span v-if="form.errors[`processItems.${index}.detection`]" class="block mt-1 text-[9px] font-bold text-rose-500">
                                        {{ form.errors[`processItems.${index}.detection`] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="number" v-model="item.rpn" placeholder="RPN..." required readonly
                                        :class="[
                                            'w-full px-3 py-2 bg-slate-50 border focus:bg-white focus:ring-2 text-xs transition-all outline-none',
                                            form.errors[`processItems.${index}.rpn`] 
                                                ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-100 placeholder-rose-300' 
                                                : 'border-slate-200 focus:border-blue-500 focus:ring-blue-100'
                                        ]">
                                    <span v-if="form.errors[`processItems.${index}.rpn`]" class="block mt-1 text-[9px] font-bold text-rose-500">
                                        {{ form.errors[`processItems.${index}.rpn`] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="text" v-model="item.recommended_action" placeholder="Recommended Action..." required
                                        :class="[
                                            'w-full px-3 py-2 bg-slate-50 border focus:bg-white focus:ring-2 text-xs transition-all outline-none',
                                            form.errors[`processItems.${index}.recommended_action`] 
                                                ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-100 placeholder-rose-300' 
                                                : 'border-slate-200 focus:border-blue-500 focus:ring-blue-100'
                                        ]">
                                    <span v-if="form.errors[`processItems.${index}.recommended_action`]" class="block mt-1 text-[9px] font-bold text-rose-500">
                                        {{ form.errors[`processItems.${index}.recommended_action`] }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>