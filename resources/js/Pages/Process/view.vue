<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm, router, Link, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { toast } from 'vue3-toastify';
import axios from 'axios';

defineOptions({layout:AuthenticatedLayout});
const props = defineProps({
    allIds: Array,
    users: Array,
    header: {
        type: Object,
        default: () => ({})
    },
    show: Boolean,
    logs: Array,
    responsibility: Array
});

const page = usePage();
const errors = computed(() => page.props.errors || {});
const isLoadingHistory = ref(false);
const historyLogs = ref([]);
const selectedName = ref("");

const selectedRowIndex = ref(null);

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

const isEditing = ref(false);
const showModal = ref(false);
const showDetailModal = ref(false);
const loadingLogs = ref(false);
const logs = ref([]);
const selectedLogDetail = ref([]);
const detailLogs = ref([]);
const isFetching = ref(false);
const selectedLogRemark = ref([]);
const showConfirmModal = ref(false);

const openLogs = async (id) => {
    isFetching.value = true;
    isLoadingHistory.value = true;
    selectedName.value = '';
    historyLogs.value = [];

    try{
        const response = await axios.get(`/process/logs/${id}`);
        historyLogs.value = response.data;
        selectedName.value = response.data.header?.name;
    } catch(e){
        const firstError = Object.values(err)[0];
        toast.error(firstError);
    }
    finally{
        isFetching.value = false;
        showModal.value = true;
        isLoadingHistory.value = false;
    }
};

const closeHistoryModal = () => {
    showModal.value = false;
    selectedName.value = "";
    historyLogs.value = [];
}

const deleteSelected = (headerId) => {
    showConfirmModal.value = true;
}

const confirmAction = (headerId) => {
    router.post('/process/remove', {id: headerId},{
        onSuccess: () => {
            showConfirmModal.value = false;
            toast.success('Process function data deleted successfuully')
        }
    });
}

const openDetail = async(log) => {
    isFetching.value = true;
    try{
        const response = await axios.get(`/process/logs/detail/${log.header_id}`);
        detailLogs.value = response.data.details;
        selectedLogDetail.value = response.data.name + " (Rev. " + response.data.revision + ")";
        selectedLogRemark.value = response.data.remark;
        showDetailModal.value = true;
    } catch(e){
        toast.error('Failed to getting logs detail data: ', e);
    } finally{
        isFetching.value = false;
    }
}

watch(isEditing, (newValue) => {
    document.title = (newValue ? 'Edit' : 'View') + ' PMFEA Template Data';
});

const pageTitle = computed(() => (isEditing.value ? 'Edit' : 'View') + ' PMFEA Template Data | ' + props.header?.name);

const processId = computed(() => {
    const processParent = props.header?.process_parent;
    const processChild = props.header?.process_child;

    return processChild == null
        ? processParent
        : `${processParent}.${processChild}`;
});

const form = useForm({
    name: props.header?.name || '',
    process_id: processId.value,
    revision: props.header?.revision || '0',
    remark: props.header?.remark || '',
    reason: '',
    processItems: props.header?.details?.length > 0 
        ? props.header.details 
        : [{
            id: null,
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
            controls_detection: '',
            responsibility: '', 
            target_completion_date: '', 
            action_taken_completion_date: '', 
            result_severity: 0, 
            result_occurrence: 0, 
            result_detection: 0, 
            result_rpn: 0
        }]
});

const addRow = () => {
    form.processItems.push({
        row_key: Math.random().toString(36).substring(2, 9),
        id: null,
        previous_problem: '',
        requirements: '',
        potential_failure_mode: '',
        potential_effect_of_failure: '',
        severity: 0,
        classification: '',
        potential_cause_of_failure: '',
        occurrence: 0,
        controls_prevention: '',
        detection: 0,
        rpn: 0,
        recommended_action: 'None',
        controls_detection: '',
        responsibility: '', 
        target_completion_date: '', 
        action_taken_completion_date: '', 
        result_severity: 0, 
        result_occurrence: 0, 
        result_detection: 0, 
        result_rpn: 0
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
    const reasonValue = form.reason ? form.reason.trim() : '';

    if (!nameValue) {
        form.setError('name', 'Function name is required');
        isValid = false;
    } else if (nameValue.length > 150) {
        form.setError('name', 'Function name cannot exceed 150 characters');
        isValid = false;
    }

    if(!reasonValue){
        form.setError('reason', 'Please fill the change reason before save the data')
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

const calculateRpn = (item) => {
    const s = Number(item.severity);
    const o = Number(item.occurrence);
    const d = Number(item.detection);

    item.rpn = (s && o && d) ? s * o * d : 0;
}

const calculateResultRpn = (item) => {
    const s = Number(item.result_severity);
    const o = Number(item.result_occurrence);
    const d = Number(item.result_detection);

    item.result_rpn = (s && o && d) ? s * o * d : 0;
}

// table action
const createBlankItem = () => ({
    row_key: Math.random().toString(36).substring(2, 9),
    previous_problem: '',
        requirements: '',
        potential_failure_mode: '',
        potential_effect_of_failure: '',
        severity: 0,
        classification: '',
        potential_cause_of_failure: '',
        occurrence: 0,
        controls_prevention: '',
        detection: 0,
        rpn: 0,
        recommended_action: 'None',
        controls_detection: '',
        responsibility: '', 
        target_completion_date: '', 
        action_taken_completion_date: '', 
        result_severity: 0, 
        result_occurrence: 0, 
        result_detection: 0, 
        result_rpn: 0
});

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

// Select2 responsibility
// Dropdown responsibility
const openDropdownIndex = ref(null);
const dropdownStyle = ref({});
const userSearch = ref("");

const filteredUsers = computed(() => {
    if(!userSearch.value) return props.responsibility;
    const lowerSearch = userSearch.value.toLowerCase();
    return props.responsibility.filter(c => 
        c.name.toLowerCase().includes(lowerSearch)
    );
});

const getSelectedUserName = (responsibilityId) => {
    if(!responsibilityId) return "Select Responsibility";
    const user = props.responsibility.find(c => c.id === responsibilityId);
    return user ? user.name : "Select Responsibility";
};

const selectUser = (id, item) => {
    item.responsibility = id; 
    openDropdownIndex.value = null;
    userSearch.value = "";
}

const getChangedFields = (log) => {
    // Kolom-kolom teknis database yang tidak perlu ditampilkan ke user
    const ignoredKeys = ['id', 'uuid', 'created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by', 'revision', 'deleted_by'];
    const changes = [];
    
    if (log.event_name === 'update' && log.before.header && log.after.header) {
        // Cari perbedaan antara data sebelum dan sesudah
        Object.keys(log.after.header).forEach(key => {
            if (!ignoredKeys.includes(key) && log.before.header[key] !== log.after.header[key]) {
                changes.push({
                    field: key,
                    before: log.before.header[key],
                    after: log.after.header[key]
                });
            }
        });
    } else if (log.event_name === 'delete' && log.before.header) {
        // Tampilkan semua data yang dihapus
        Object.keys(log.before.header).forEach(key => {
            if (!ignoredKeys.includes(key) && log.before.header[key] !== null) {
                changes.push({
                    field: key,
                    before: log.before.header[key],
                    after: null
                });
            }
        });
    } else if (log.event_name === 'create' && log.after.header) {
        // Tampilkan semua data yang baru dibuat
        Object.keys(log.after.header).forEach(key => {
            if (!ignoredKeys.includes(key) && log.after.header[key] !== null) {
                changes.push({
                    field: key,
                    before: null,
                    after: log.after.header[key]
                });
            }
        });
    }
    
    return changes;
};

// Show logs details
const historyLogsDetails = ref([]);
const showModalDetail = ref(false);
const showLogsDetails = async (id) => {
    isFetching.value = true;
    isLoadingHistory.value = true;

    try{
        const response = await axios(`/process/logs/detail/${id}`);
        historyLogsDetails.value = response.data;

        console.log(response.data);
    } catch(err){
        const firstError = Object.values(err)[0];
        toast.error(firstError);
    }finally{
        isFetching.value = false;
        isLoadingHistory.value = false;
    }
};

// const getChangedDetailsFields = (log) => {
//     const ignoredKeys = ['id', 'uuid', 'created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by', 'revision', 'deleted_by'];
//     const changes = [];
    
//     if (log.event_name === 'update' && log.before.detail && log.after.detail) {
//         // Cari perbedaan antara data sebelum dan sesudah
//         Object.keys(log.after.detail).forEach(key => {
//             if (!ignoredKeys.includes(key) && log.before.detail[key] !== log.after.detail[key]) {
//                 changes.push({
//                     field: key,
//                     before: log.before.detail[key],
//                     after: log.after.detail[key]
//                 });
//             }
//         });
//     } else if (log.event_name === 'delete' && log.before.detail) {
//         // Tampilkan semua data yang dihapus
//         Object.keys(log.before.detail).forEach(key => {
//             if (!ignoredKeys.includes(key) && log.before.detail[key] !== null) {
//                 changes.push({
//                     field: key,
//                     before: log.before.detail[key],
//                     after: null
//                 });
//             }
//         });
//     } else if (log.event_name === 'create' && log.after.header) {
//         // Tampilkan semua data yang baru dibuat
//         Object.keys(log.after.header).forEach(key => {
//             if (!ignoredKeys.includes(key) && log.after.header[key] !== null) {
//                 changes.push({
//                     field: key,
//                     before: null,
//                     after: log.after.header[key]
//                 });
//             }
//         });
//     }
    
//     return changes;
// }

// Helper untuk mempercantik nama kolom (contoh: billing_address -> Billing Address)
const formatFieldName = (text) => {
    if (!text) return '';
    return text.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
};
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
                <!-- <div class="flex items-center justify-between bg-white p-3 shadow-sm"> -->
                    <div class="flex items-center justify-between w-full">
                        <!-- Toolbar -->
                        <div class="flex items-center gap-3"> 
                            <div class="flex items-center gap-1.5">
                                <Link href="/process" class="group flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-bold text-slate-500 hover:text-blue-600 hover:bg-blue-50/50  transition-colors">
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
                        <!-- End of toolbar -->
                    </div>
                </div>
            </div>

            <!-- Buat header -->
            <div class="bg-white  border border-slate-200/80 shadow-sm p-6 mb-6">
                <div class="grid grid-cols-1 lg:grid-cols-10 gap-6 items-start">
                    <div class="lg:col-span-2">
                        <div class="flex gap-1.5 mb-2">
                            <label class="block text-[10px] font-extrabold text-slate-600 uppercase tracking-wider">
                                Process ID <span class="text-rose-500">*</span>
                            </label>
                        </div>
                        <div class="relative">
                            <input type="number" v-model="form.process_id" placeholder="Process ID"
                                :class="[
                                    'w-full pl-4 pr-4 py-2.5 bg-slate-50 border focus:bg-white focus:ring-2  text-xs font-medium transition-all outline-none',
                                    form.errors.process_id 
                                        ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-100 text-rose-600' 
                                        : 'border-slate-200 focus:border-blue-500 focus:ring-blue-100 text-slate-700'
                                ]"
                            />
                            <p v-if="form.errors.process_id" class="mt-1.5 text-[10px] font-bold text-rose-500">{{ form.errors.process_id }}</p>
                            <p v-else class="mt-1.5 text-[10px] font-medium text-slate-400">Unique process ID</p>
                        </div>
                    </div>
                    <div class="lg:col-span-5">
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
                                'w-full pl-10 pr-4 py-2.5 bg-slate-50 border focus:bg-white focus:ring-2 text-xs font-medium transition-all outline-none',
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
                                <span class="text-xs font-black tracking-widest">Rev.<span class="text-sm">{{ props.header?.revision }}</span></span>
                                <input type="hidden" name="revision" value="0">
                            </div>
                        </div>
                        <p class="mt-1.5 text-[10px] font-medium text-slate-400 text-center">Initial</p>
                    </div>

                    <div class="lg:col-span-5">
                        <div class="flex items-center gap-1.5 mb-2">
                            <label class="block text-[10px] font-extrabold text-slate-600 uppercase tracking-wider">
                                Remarks (Change reason)
                            </label>
                        </div>
                        <textarea v-model="form.remark" rows="2" placeholder="Provide detailed context, e.g., on equipment conditions, key dependencies, environmental factors..." 
                            :disabled="!isEditing"
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 text-xs font-medium text-slate-700 transition-all outline-none resize-none leading-relaxed"></textarea>
                    </div>
                    <div class="lg:col-span-5">
                        <div class="flex items-center gap-1.5 mb-2">
                            <label class="block text-[10px] font-extrabold text-slate-600 uppercase tracking-wider">
                                Change Reason <span class="text-rose-500">*</span>
                            </label>
                        </div>
                        <textarea
                            v-model="form.reason"
                            rows="2"
                            placeholder="Describe the change reason here ..."
                            :disabled="!isEditing"
                            :class="[
                                'w-full px-4 py-2.5 bg-slate-50 border focus:bg-white focus:ring-2 text-xs font-medium transition-all outline-none resize-none',
                                form.errors.reason
                                    ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-100 text-rose-600'
                                    : 'border-slate-200 focus:border-blue-500 focus:ring-blue-100 text-slate-700'
                            ]"
                        />
                        <p
                            v-if="form.errors.reason"
                            class="mt-1.5 text-[10px] font-bold text-rose-500"
                        >
                            {{ form.errors.reason }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Buat details --> 
            <div class="w-full bg-white  border border-slate-200/80 shadow-sm overflow-hidden mb-6 flex flex-col">
                <!-- table action bar -->
                <div class="overflow-auto border-t border-slate-100 p-4 flex items-center gap-5">
                    <button 
                        @click="onNew"
                        :disabled="!isEditing"
                        class="text-sm text-[12px] text-slate-600 hover:text-blue-600 hover:underline hover:cursor-pointer underline-offset-4 transition-all duration-200 disabled:opacity-40 disabled:pointer-events-none"
                    >
                        New
                    </button>

                    <button 
                        @click="onInsert" 
                        :disabled="!isEditing"
                        class="text-sm text-[12px] text-slate-600 hover:text-blue-600 hover:underline hover:cursor-pointer underline-offset-4 transition-all duration-200 disabled:opacity-40 disabled:pointer-events-none"
                    >
                    Insert
                    </button>

                    <button 
                        @click="onDelete" 
                        :disabled="!isEditing"
                        class="text-sm text-[12px] text-slate-600 hover:text-red-600 hover:underline hover:cursor-pointer underline-offset-4 transition-all duration-200 disabled:opacity-40 disabled:pointer-events-none"
                    >
                    Delete
                    </button>

                    <button 
                        :disabled="!isEditing"
                        @click="onDeleteAll" 
                        class="text-sm text-[12px] text-slate-600 hover:text-red-700 hover:underline hover:cursor-pointer underline-offset-4 transition-all duration-200 disabled:opacity-40 disabled:pointer-events-none"
                    >
                    Delete All
                    </button>
                </div>
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
                                <th class="p-4 y-3 min-w-[350px]">Responsibility</th>
                                <th class="p-4 y-3 min-w-[200px]">Target Completion Date</th>
                                <th class="p-4 y-3 min-w-[200px]">Action Taken Completion Date</th>
                                <th class="p-4 y-3 min-w-[100px]">Result Severity</th>
                                <th class="p-4 y-3 min-w-[100px]">Result Occurrence</th>
                                <th class="p-4 y-3 min-w-[100px]">Result Detection</th>
                                <th class="p-4 y-3 min-w-[100px]">Result RPN</th>
                            </tr>
                        </thead>
                        
                        <tbody class="divide-y divide-slate-100">
                            <tr 
                                v-for="(item, index) in form.processItems" 
                                :key="item.row_key" @click="selectedRowIndex = index"
                                @focusin="selectedRowIndex = index"
                                :disabled="!isEditing"
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
                                    <input :disabled="!isEditing" type="text" v-model="item.previous_problem" placeholder="Enter previous ..." class="w-full px-3 py-2 bg-slate-50 border border-slate-200 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 text-xs transition-all outline-none">
                                </td>
                                <td class="px-4 py-3">
                                    <input :disabled="!isEditing" type="text" v-model="item.requirements" placeholder="Enter requirements..." required 
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
                                    <input :disabled="!isEditing" type="text" v-model="item.potential_failure_mode" placeholder="Failure mode..." required
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
                                    <input :disabled="!isEditing" type="text" v-model="item.potential_effect_of_failure" placeholder="Effects..." required
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
                                    <input :disabled="!isEditing" type="number" v-model="item.severity" placeholder="Severity..." required
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
                                    <input :disabled="!isEditing" type="text" v-model="item.classification" placeholder="Classsification..." required
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
                                    <input :disabled="!isEditing" type="text" v-model="item.potential_cause_of_failure" placeholder="Causes..." required
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
                                    <input :disabled="!isEditing" type="number" v-model="item.occurrence" placeholder="Occurance..." required
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
                                    <input :disabled="!isEditing" type="text" v-model="item.controls_prevention" placeholder="Prevention..." required
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
                                    <input :disabled="!isEditing" type="text" v-model="item.controls_detection" placeholder="Control Detection..." required
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
                                    <input :disabled="!isEditing" type="number" v-model="item.detection" placeholder="Detection..." required
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
                                    <input :disabled="!isEditing" type="number" v-model="item.rpn" placeholder="RPN..." required readonly
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
                                    <input :disabled="!isEditing" type="text" v-model="item.recommended_action" placeholder="Recommended Action..." required
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
                                <td class="px-4 py-3">
                                    <div class="relative w-full">
                                        <!-- Tombol Trigger Dropdown -->
                                        <div
                                            @click="(e) => {
                                                if(!isEditing) return;
                                                
                                                if (openDropdownIndex === index) {
                                                    openDropdownIndex = null;
                                                } else {
                                                    openDropdownIndex = index;
                                                    const rect = e.currentTarget.getBoundingClientRect();
                                                    dropdownStyle = {
                                                        top: rect.bottom + 'px',
                                                        left: rect.left + 'px',
                                                        width: rect.width + 'px'
                                                    };
                                                }
                                            }"
                                            class="relative z-20 w-full pl-3 pr-3 py-2 border text-xs focus:outline-none focus:border-blue-500 bg-white flex justify-between items-center transition-all"
                                            :class="[
                                                form.errors[`processItems.${index}.responsibility`]
                                                    ? 'border-rose-500 focus:border-rose-500 focus:ring-1 focus:ring-rose-500 text-rose-600'
                                                    : 'border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800',
                                                !isEditing ? 'pointer-events-none bg-slate-50 cursor-not-allowed text-slate-400 border-slate-200' : 'cursor-pointer'
                                            ]"
                                        >
                                            <span :class="item.responsibility ? 'text-slate-800 font-semibold' : 'text-slate-400'">
                                                {{ getSelectedUserName(item.responsibility) }}
                                            </span>
                                            
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                class="h-4 w-4 text-slate-400 transition-transform duration-200"
                                                :class="{'rotate-180 text-blue-500': openDropdownIndex === index}"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                            >
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </div>

                                        <!-- Pesan Error Per Baris -->
                                        <p
                                            v-if="form.errors[`processItems.${index}.responsibility`]"
                                            class="mt-1 text-[10px] font-bold text-rose-500"
                                        >
                                            {{ form.errors[`processItems.${index}.responsibility`] }}
                                        </p>

                                        <!-- Teleportasi Panel Dropdown ke elemen Body luar -->
                                        <Teleport to="body">
                                            <!-- Backdrop/Overlay untuk menutup dropdown saat klik luar -->
                                            <div
                                                v-if="openDropdownIndex === index"
                                                @click="openDropdownIndex = null"
                                                class="fixed inset-0 z-40 bg-transparent"
                                            ></div>

                                            <Transition
                                                enter-active-class="transition duration-100 ease-out"
                                                enter-from-class="transform scale-95 opacity-0"
                                                enter-to-class="transform scale-100 opacity-100"
                                                leave-active-class="transition duration-75 ease-out"
                                                leave-from-class="transform scale-100 opacity-100"
                                                leave-to-class="transform scale-95 opacity-0"
                                            >
                                                <!-- Dropdown Panel -->
                                                <div
                                                    v-if="openDropdownIndex === index"
                                                    :style="dropdownStyle"
                                                    class="fixed z-50 bg-white border border-slate-200 shadow-xl overflow-hidden mt-1 rounded-sm"
                                                >
                                                    <!-- Form Search -->
                                                    <div class="p-2 border-b border-slate-100 bg-slate-50 sticky top-0">
                                                        <div class="relative">
                                                            <svg class="absolute left-2 top-2 h-3.5 w-3.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                            </svg>
                                                            <input
                                                                type="text"
                                                                v-model="userSearch"
                                                                @click.stop
                                                                class="w-full pl-7 pr-2 py-1.5 border border-slate-200 text-xs rounded-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 bg-white"
                                                                placeholder="Type to search..."
                                                                autofocus
                                                            />
                                                        </div>
                                                    </div>

                                                    <!-- List Data User -->
                                                    <div class="max-h-48 overflow-y-auto">
                                                        <div
                                                            v-for="user in filteredUsers"
                                                            :key="user.id"
                                                            @click="selectUser(user.id, item)"
                                                            class="px-3 py-2.5 text-xs cursor-pointer hover:bg-blue-50 hover:text-blue-700 transition-colors border-b border-slate-50 last:border-0"
                                                            :class="{'bg-blue-50 text-blue-700 font-bold border-l-2 border-l-blue-600': item.responsibility === user.id}"
                                                        >
                                                            {{ user.name }}
                                                        </div>
                                                        
                                                        <div v-if="filteredUsers.length === 0" class="px-3 py-6 text-xs text-center text-slate-400 italic bg-slate-50">
                                                            No customer found matching "{{ userSearch }}"
                                                        </div>
                                                    </div>
                                                </div>
                                            </Transition>
                                        </Teleport>
                                    </div>
                                </td>
    
                                <td class="px-4 py-3">
                                    <input
                                        type="date"
                                        :disabled="!isEditing"
                                        v-model="form.target_completion_date"
                                        class="w-full pl-3 pr-3 py-2 bg-white border text-xs font-medium focus:outline-none transition-all border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800"
                                    >
                                </td>
                                <td class="px-4 py-3">
                                    <input
                                        type="date"
                                        :disabled="!isEditing"
                                        v-model="form.action_taken_completion_date"
                                        class="w-full pl-3 pr-3 py-2 bg-white border text-xs font-medium focus:outline-none transition-all border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800"
                                    >
                                </td>
                                <td class="px-4 py-3">
                                    <input type="number" v-model="item.result_severity" placeholder="Severity..."
                                        @input="calculateResultRpn(item)"
                                        :disabled="!isEditing"
                                        class="w-full px-3 py-2 bg-slate-50 border focus:bg-white focus:ring-2 text-xs transition-all outline-none border-slate-200 focus:border-blue-500 focus:ring-blue-100">
                                </td>
                                <td class="px-4 py-3">
                                    <input type="number" v-model="item.result_occurrence" placeholder="Severity..."
                                        @input="calculateResultRpn(item)"
                                        :disabled="!isEditing"
                                        class="w-full px-3 py-2 bg-slate-50 border focus:bg-white focus:ring-2 text-xs transition-all outline-none border-slate-200 focus:border-blue-500 focus:ring-blue-100">
                                </td>
                                <td class="px-4 py-3">
                                    <input type="number" v-model="item.result_detection" placeholder="Severity..."
                                        @input="calculateResultRpn(item)"
                                        :disabled="!isEditing"
                                        class="w-full px-3 py-2 bg-slate-50 border focus:bg-white focus:ring-2 text-xs transition-all outline-none border-slate-200 focus:border-blue-500 focus:ring-blue-100">
                                </td>
                                <td class="px-4 py-3">
                                    <input type="number" v-model="item.result_rpn" placeholder="Severity..." readonly
                                        class="w-full px-3 py-2 bg-slate-50 border focus:bg-white focus:ring-2 text-xs transition-all outline-none border-slate-200 focus:border-blue-500 focus:ring-blue-100">
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal buat nampilin change logs -->
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95"
    >
        <div
            v-show="showModal"
            @click.self="closeHistoryModal"
            class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 backdrop-blur-sm p-4"
        >
            <div
                class="bg-white w-full max-w-5xl border border-slate-200 shadow-2xl flex flex-col max-h-[90vh] overflow-hidden"
            >
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 bg-white shrink-0">
                    <h3 class="text-sm font-black text-slate-900 uppercase tracking-wider flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5 text-blue-600">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        Process Function Revision History
                    </h3>
                    <button @click="closeHistoryModal" class="text-slate-400 hover:text-rose-600 p-1 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="max-h-[65vh] overflow-y-auto flex-1 px-6 py-6 bg-slate-50/60 divide-y divide-slate-200/60">
                    <div v-if="isLoadingHistory" class="flex flex-col items-center justify-center py-12 gap-3">
                        <div class="animate-spin h-7 w-7 border-b-2 border-blue-600"></div>
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Loading system logs...</span>
                    </div>

                    <div v-else-if="historyLogs.length === 0" class="text-center py-12 border border-dashed border-slate-200 bg-white p-8 ">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">No History Records</span>
                        <p class="text-[11px] text-slate-400 mt-0.5">This customer profile has no recorded changes.</p>
                    </div>

                    <div v-else class="relative border-l-2 border-slate-200 ml-3 space-y-8 pb-4">
                        <div v-for="(log, index) in historyLogs" :key="log.id" class="relative pl-6 animate-fade-in">
                            <div
                                :class="{
                                    'bg-emerald-500 border-emerald-100 ring-4 ring-emerald-50': log.event_name === 'create',
                                    'bg-blue-600 border-blue-100 ring-4 ring-blue-50': log.event_name === 'update' && index === 0,
                                    'bg-slate-400 border-white': log.event_name === 'update' && index !== 0,
                                    'bg-rose-500 border-rose-100 ring-4 ring-rose-50': log.event_name === 'delete'
                                }"
                                class="absolute w-3.5 h-3.5 -left-[8px] top-1 border-2 shadow-sm transition-all"
                            ></div>

                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-2 gap-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] font-mono font-bold uppercase px-2 py-0.5  border bg-white shadow-sm text-slate-700">
                                        Rev. {{ log.revision }}
                                    </span>
                                    <span 
                                        :class="{
                                            'bg-emerald-50 text-emerald-700 border-emerald-200': log.event_name === 'create',
                                            'bg-blue-50 text-blue-700 border-blue-200': log.event_name === 'update',
                                            'bg-rose-50 text-rose-700 border-rose-200': log.event_name === 'delete'
                                        }"
                                        class="text-[9px] font-bold uppercase px-1.5 py-0.5 border -sm tracking-wide"
                                    >
                                        {{ log.event_name }}
                                    </span>
                                    <span class="text-xs font-bold text-slate-900">
                                        {{ log.creator?.name || 'System Auto' }}
                                    </span>
                                </div>
                                <span class="text-[10px] font-mono font-bold text-slate-400 uppercase tracking-wider">
                                    {{ new Date(log.created_at).toLocaleString('id-ID') }}
                                </span>
                            </div>

                            <div class="bg-white p-4 border border-slate-200 shadow-sm space-y-3">
                                <div>
                                    <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block mb-0.5">Change Reason</span>
                                    <p class="text-xs font-bold text-slate-800 leading-relaxed whitespace-pre-line">
                                        {{ log.change_reason || 'No description provided.' }}
                                    </p>
                                </div>

                                <div v-if="getChangedFields(log).length > 0" class="pt-2 border-t border-slate-100 overflow-x-auto">
                                    <table class="min-w-full text-[11px] font-mono">
                                        <thead>
                                            <tr class="text-slate-400 border-b border-slate-100 text-left font-bold uppercase tracking-wider text-[10px]">
                                                <th class="pb-1.5 w-1/4">Field Data</th>
                                                <th class="pb-1.5 w-3/8 text-rose-600" v-if="log.event_name !== 'create'">Data Before</th>
                                                <th class="pb-1.5 w-3/8 text-emerald-600" v-if="log.event_name !== 'delete'">Data After</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-50 text-slate-600 font-medium">
                                            <tr v-for="item in getChangedFields(log)" :key="item.field" class="hover:bg-slate-50/50">
                                                <td class="py-1.5 font-bold text-slate-500">{{ formatFieldName(item.field) }}</td>
                                                
                                                <td class="py-1.5 pr-2" v-if="log.event_name !== 'create'">
                                                    <span class="bg-rose-50 text-rose-700 px-1.5 py-0.5 -sm line-through block w-fit max-w-xs truncate" :title="String(item.before)">
                                                        {{ item.before === null || item.before === '' ? '-' : item.before }}
                                                    </span>
                                                </td>
                                                
                                                <td class="py-1.5" v-if="log.event_name !== 'delete'">
                                                    <span class="bg-emerald-50 text-emerald-700 px-1.5 py-0.5 -sm font-bold block w-fit max-w-xs truncate" :title="String(item.after)">
                                                        {{ item.after === null || item.after === '' ? '-' : item.after }}
                                                    </span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div>
                                    <span 
                                        @click="showLogsDetails(log.id)"
                                        class="text-[10px] font-mono font-bold text-blue-400 hover:text-blue-800 uppercase tracking-wider cursor-pointer"
                                    >
                                        Show Details ...
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Transition>

    <!-- Modal Log Details -->
    <transition
        enter-active-class="transition duration-500 ease-out"
        enter-from-class="opacity-0 translate-y-4"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 translate-y-4"
    >
        <div v-if="showDetailModal" class="fixed inset-0 z-[70] flex items-center justify-center bg-black/50 p-4">
            <div class="bg-white  w-full shadow-xl p-6 max-h-[80vh] overflow-y-auto">
                <div class="flex justify-between items-center mb-4">
                    <div>
                        <h2 class="text-lg font-black">Detail Log: {{ selectedLogDetail }}</h2>
                        <p class="text-sm font-slate-500 mt-1">Remark: {{ selectedLogRemark }}</p>
                    </div>
                    <button @click="showDetailModal = false" class="text-slate-400 hover:text-black">✕</button>
                </div>

                <!-- Tabel Detail -->
                <table class="w-full text-[11px] border-collapse bg-white">
                    <thead class="bg-blue-300 text-slate-500 uppercase tracking-wider text-[12px] font-bold">
                        <tr>
                            <th class="px-6 py-4 text-center">Step</th>
                            <th class="px-6 py-4 text-center">Previous Problem</th>
                            <th class="px-6 py-4 text-center">Requirement</th>
                            <th class="px-6 py-4 text-center">Potential Failure Mode</th>
                            <th class="px-6 py-4 text-center">Potential Effect of Failure</th>
                            <th class="px-6 py-4 text-center">Potential Cause of Failure</th>
                            <th class="px-6 py-4 text-center">Controls Prevention</th>
                            <th class="px-6 py-4 text-center">Controls Detection</th>
                            <th class="px-6 py-4 text-center">Created At</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs text-slate-600">
                        <tr v-for="item in detailLogs" :key="item.id" class=" hover:bg-slate-50">
                            <td class="px-6 py-4 font-medium text-center font-mono">{{ item.order }}</td>
                            <td class="px-6 py-4 font-medium">{{ item.previous_problem }}</td>
                            <td class="px-6 py-4 font-medium">{{ item.requirements }}</td>
                            <td class="px-6 py-4 font-medium">{{ item.potential_failure_mode }}</td>
                            <td class="px-6 py-4 font-medium">{{ item.potential_effect_of_failure }}</td>
                            <td class="px-6 py-4 font-medium">{{ item.potential_cause_of_failure }}</td>
                            <td class="px-6 py-4 font-medium">{{ item.controls_prevention }}</td>
                            <td class="px-6 py-4 font-medium">{{ item.controls_detection }}</td>
                            <td class="px-6 py-4 font-medium">
                                {{ new Date(item.created_at).toLocaleString('id-ID') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        </transition>

   <!-- Loading Overlay -->
    <div v-if="isFetching" class="fixed inset-0 z-[100] flex items-center justify-center bg-white/80 backdrop-blur-sm">
        <div class="flex flex-col items-center">
            <svg class="animate-spin h-12 w-12 text-blue-600 mb-4" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="text-sm font-black text-blue-900 animate-pulse tracking-widest">
                Retrieving data, please wait...
            </p>
        </div>
    </div>

    <!-- Modal konfirmasi -->
    <transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
        <div v-if="showConfirmModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
            <div class="bg-white  w-full max-w-sm shadow-2xl p-6 border border-slate-100">
                <div class="flex flex-col items-center text-center">
                    <div class="p-3 bg-rose-50 text-rose-500  mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    </div>
                    <h3 class="text-lg font-black text-slate-900">Confirm Deletion</h3>
                    <p class="text-sm text-slate-500 mt-2 mb-6">Are you sure you want to delete these process function data (<span class="font-bold text-slate-900">{{ header?.name }}</span>)? This action cannot be undone.</p>
                    <div class="flex gap-3 w-full">
                        <button @click="showConfirmModal = false" class="flex-1 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition">Cancel</button>
                        <button @click="confirmAction(header?.id)" class="flex-1 px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-lg transition">Yes, Delete</button>
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>