<script setup>
import "vue3-toastify/dist/index.css";
import { ref, watch, computed, onMounted } from "vue";
import { Head, router, Link, usePage, useForm } from '@inertiajs/vue3';
import { toast } from "vue3-toastify";
import axios from "axios";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

defineOptions({ layout: AuthenticatedLayout, inheritAttrs: false });

const props = defineProps({
    allIds: Array,
    header: {
        type: Object,
        default: () => ({})
    },
    users: Array,
});

defineEmits(['close']);
const page = usePage();
const errors = computed(() => page.props.errors || {});
const selectedRowIndex = ref(null);
const loadingLogs = ref(false);
const isEditing = ref(false);

watch(
    errors,
    (newErrors) => {
        if (newErrors && newErrors.error) {
            toast.error(newErrors.error);
        }
    },
    { deep: true }
);

const firstData = () => {
    const firstId = props.allIds[0];
    if (props.header?.id === firstId) {
        toast.info('You are in the first data');
        return;
    }
    router.get(`/approval-setup/${firstId}/view`);
};

const lastData = () => {
    const lastId = props.allIds[props.allIds.length - 1];
    if (props.header?.id === lastId) {
        toast.info('You are in the last data');
        return;
    }
    router.get(`/approval-setup/${lastId}/view`);
};

const prevData = () => {
    const currentIndex = props.allIds.indexOf(props.header.id);
    if (currentIndex > 0) {
        router.get(`/approval-setup/${props.allIds[currentIndex - 1]}/view`);
    } else {
        toast.info('You are in the first data');
    }
};

const nextData = () => {
    const currentIndex = props.allIds.indexOf(props.header.id);
    if (currentIndex < props.allIds.length - 1) {
        router.get(`/approval-setup/${props.allIds[currentIndex + 1]}/view`);
    } else {
        toast.info('You are in the last data');
    }
};

const newForm = () => {
    router.get('/approval-setup/create');
};

// --- State untuk Users & Dropdown Per-Row (Approver) ---
const users = ref([]);
const userSearch = ref("");
const activeDropdownRow = ref(null);
const dropdownStyle = ref({});

onMounted(async () => {
    try {
        const response = await axios.get('/api/v1/users');
        users.value = response.data.data || [];
    } catch (error) {
        toast.error("Failed to load users data");
    }
});

const filteredUsers = computed(() => {
    if (!userSearch.value) return users.value;
    const lowerSearch = userSearch.value.toLowerCase();
    return users.value.filter(u => u.name.toLowerCase().includes(lowerSearch));
});

const getSelectedUserName = (approverId) => {
    if (!approverId) return "Select Approver";
    const user = users.value.find(u => u.id === approverId);
    return user ? user.name : "Select Approver";
};

const selectUser = (index, userId) => {
    form.approver[index].approver_id = userId;
    activeDropdownRow.value = null;
    userSearch.value = "";
};

// --- State untuk Custom Select Module ---
const isModuleDropdownOpen = ref(false);
const moduleSearch = ref("");

const staticModules = [
    { id: 'pfmea', name: 'PFMEA' },
    { id: 'apqp', name: 'APQP' }
];

const filteredModules = computed(() => {
    if (!moduleSearch.value) return staticModules;
    const lowerSearch = moduleSearch.value.toLowerCase();
    return staticModules.filter(m => m.name.toLowerCase().includes(lowerSearch));
});

const selectedModuleName = computed(() => {
    if (!form.module) return "Select Module Type";
    const mod = staticModules.find(m => m.id === form.module);
    return mod ? mod.name : "Select Module Type";
});

const selectModule = (id) => {
    form.module = id;
    isModuleDropdownOpen.value = false;
    moduleSearch.value = "";
};

// --- Tombol Aksi Tabel ---
const createBlankItem = () => ({
    row_key: Math.random().toString(36).substring(2, 9),
    approver_id: ''
});

const onNew = () => {
    if (!isEditing.value) return;
    form.approver.push(createBlankItem());
    selectedRowIndex.value = form.approver.length - 1;
};

const onInsert = () => {
    if (!isEditing.value) return;
    const idx = selectedRowIndex.value !== null ? selectedRowIndex.value : form.approver.length - 1;
    form.approver.splice(idx + 1, 0, createBlankItem());
    selectedRowIndex.value = idx + 1;
};

const onDelete = () => {
    if (!isEditing.value) return;
    if (form.approver.length > 1 && selectedRowIndex.value !== null) {
        form.approver.splice(selectedRowIndex.value, 1);
        selectedRowIndex.value = Math.max(0, selectedRowIndex.value - 1);
    } else {
        toast.warning("At least one approver is required.");
    }
};

const onDeleteAll = () => {
    if (!isEditing.value) return;
    form.approver = [createBlankItem()];
    selectedRowIndex.value = 0;
};

// --- Form Setup Utama ---
const form = useForm({
    id: props.header?.id,
    module: props.header?.module,
    revision: props.header?.revision,
    remark: props.header?.remark || '',
    reason: '',
    approver: props.header?.details?.length > 0
        ? props.header?.details.map(item => ({
            row_key: Math.random().toString(36).substring(2, 9),
            id: item.id,
            approver_id: item.approver_id
        }))
        : [createBlankItem()]
});

const validateAndSave = () => {
    form.clearErrors();
    let isValid = true;

    if (!form.module) {
        form.setError('module', 'Module name is required');
        isValid = false;
    }

    if (!form.reason || !form.reason.trim()) {
        form.setError('reason', 'Change reason is required');
        isValid = false;
    } else if (form.reason.trim().length < 5) {
        form.setError('reason', 'Reason must be at least 5 characters');
        isValid = false;
    }

    if (!form.approver || form.approver.length === 0) {
        toast.error('At least one approver is required.');
        return;
    }

    const idCounts = {};
    form.approver.forEach((item) => {
        if (item.approver_id) {
            idCounts[item.approver_id] = (idCounts[item.approver_id] || 0) + 1;
        }
    });

    let hasEmptyApprover = false;
    let hasDuplicate = false;

    form.approver.forEach((item, index) => {
        const fieldKey = `approver.${index}.approver_id`;

        if (!item.approver_id) {
            form.setError(fieldKey, 'Approver is required');
            hasEmptyApprover = true;
            isValid = false;
        } else if (idCounts[item.approver_id] > 1) {
            form.setError(fieldKey, 'This approver is duplicated');
            hasDuplicate = true;
            isValid = false;
        }
    });

    if (hasEmptyApprover) toast.error('Please select an approver for all rows.');
    if (hasDuplicate) toast.error('Duplicate approver found, please check it.');

    if (!isValid) return;

    form.put(`/approval-setup/${form.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            // toast.success('Approval setup updated successfully.');
            isEditing.value = false;
            form.reason = '';
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0];
            toast.error(firstError || 'Failed to save data.');
        }
    });
};

// --- Action Delete Modal ---
const deleteForm = useForm({
    id: null,
    reason: ""
});
const showConfirmModal = ref(false);

const deleteSelected = (id) => { 
    deleteForm.id = id;
    deleteForm.reason = ""; 
    deleteForm.clearErrors();
    showConfirmModal.value = true;    
};

const confirmAction = () => {
    deleteForm.clearErrors();
    let isValid = true;

    if (!deleteForm.reason || !deleteForm.reason.trim()) {
        deleteForm.setError('reason', 'Please fill the deletion reason');
        isValid = false;
    }

    if (!isValid) return;

    deleteForm.post('/approval-setup/delete', {
        preserveScroll: true,
        onSuccess: () => {
            showConfirmModal.value = false;
            deleteForm.reset();
        }
    });
};
</script>

<template>
    <Head title="View Approval Setup"/>

    <div class="flex min-h-screen bg-slate-50 font-sans antialiased text-slate-800">
        <div class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8 p-6 pb-0">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">
                        {{ isEditing ? 'Edit' : 'View' }} Approval Setup | (Rev. {{ props.header?.revision }})
                    </h1>
                    <p class="text-xs text-slate-500 mt-1">
                        {{ isEditing ? 'Edit' : 'View' }} approval setup {{ props.header?.code }}.
                    </p>
                </div>

                <div class="sticky top-0 z-40 bg-white/95 backdrop-blur-sm border-b border-slate-200 px-3 py-2 shadow-sm">
                    <div class="flex items-center justify-between w-full">
                        <!-- Toolbar Navigasi & Aksi Utama -->
                        <div class="flex items-center gap-3"> 
                            <div class="flex items-center gap-1.5">
                                <Link href="/approval-setup" class="group flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-bold text-slate-500 hover:text-blue-600 hover:bg-blue-50/50 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform group-hover:-translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                    </svg>
                                    <span class="hidden sm:inline">Back</span>
                                </Link>

                                <div class="w-px h-6 bg-slate-300 mx-1" v-if="!isEditing"></div>

                                <div class="flex items-center bg-slate-100/80 p-0.5 border border-slate-200/50 shadow-inner" v-if="!isEditing">
                                    <button @click="firstData" :disabled="allIds.indexOf(header.id) === 0" type="button" class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-white hover:shadow-sm transition-all" title="First">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                                        </svg>
                                    </button>
                                    <button type="button" @click="prevData" :disabled="allIds.indexOf(header.id) === 0" class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-white hover:shadow-sm transition-all" title="Previous">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                        </svg>
                                    </button>
                                    <button type="button" @click="nextData" :disabled="allIds.indexOf(header.id) === allIds.length - 1" class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-white hover:shadow-sm transition-all" title="Next">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>
                                    <button type="button" @click="lastData" :disabled="allIds.indexOf(header.id) === allIds.length - 1" class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-white hover:shadow-sm transition-all" title="Last">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="w-px h-6 bg-slate-300 mx-1"></div>

                            <div class="flex items-center gap-1.5">
                                <button v-if="isEditing" type="button" @click="validateAndSave" :disabled="form.processing"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-sm transition active:scale-95 disabled:opacity-70 disabled:cursor-not-allowed">
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
                                    v-if="$can('create approval-setup') && !isEditing"
                                    type="button" 
                                    @click="newForm"
                                    class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-bold text-green-600 bg-blue-50 border border-blue-100 transition-all hover:bg-blue-100 active:scale-95 shadow-sm"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    <span>New</span>
                                </button>

                                <button type="button" 
                                    v-if="$can('edit approval-setup')"
                                    @click="isEditing ? (isEditing = false, form.reset()) : isEditing = true" 
                                    :class="[
                                        'flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold transition-all shadow-sm border',
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

                                <button 
                                    v-if="$can('delete approval-setup')"
                                    type="button" @click="deleteSelected(props.header?.id)" class="p-1.5 text-slate-400 hover:text-white hover:bg-rose-500 transition-colors" title="Delete Record">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-6">
                <!-- Header Form Card -->
                <div class="bg-white border border-slate-200/80 shadow-sm p-6 mb-6">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                        <!-- Module Selection (Readonly jika !isEditing) -->
                        <div class="lg:col-span-3">
                            <div class="flex gap-1.5 mb-2">
                                <label class="block text-[10px] font-extrabold text-slate-600 uppercase tracking-wider">
                                    Module <span class="text-rose-500">*</span>
                                </label>
                            </div>
                            <div class="relative">
                                <div
                                    v-if="isModuleDropdownOpen && isEditing"
                                    @click="isModuleDropdownOpen = false"
                                    class="fixed inset-0 z-0"
                                ></div>

                                <div
                                    @click="isEditing && (isModuleDropdownOpen = !isModuleDropdownOpen)"
                                    class="relative z-20 w-full pl-3 pr-3 py-2 border text-xs focus:outline-none flex justify-between items-center transition-all select-none"
                                    :class="[
                                        !isEditing 
                                            ? 'bg-slate-100 border-slate-200 text-slate-500 cursor-not-allowed' 
                                            : form.errors.module
                                                ? 'bg-rose-50/20 border-rose-500 text-rose-600 cursor-pointer focus:border-rose-500 focus:ring-1 focus:ring-rose-500'
                                                : 'bg-white border-slate-300 text-slate-800 cursor-pointer hover:border-slate-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500'
                                    ]"
                                >
                                    <span :class="[
                                        form.module ? 'font-semibold uppercase' : 'text-slate-400',
                                        !isEditing ? 'text-slate-500' : ''
                                    ]">
                                        {{ selectedModuleName }}
                                    </span>
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-4 w-4 transition-transform duration-200 shrink-0"
                                        :class="[
                                            !isEditing ? 'text-slate-400 opacity-60' : 'text-slate-400',
                                            isModuleDropdownOpen && isEditing ? 'rotate-180 text-blue-500' : ''
                                        ]"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>

                                <p v-if="form.errors.module" class="mt-1 text-[10px] font-bold text-rose-500 flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                    <span>{{ form.errors.module }}</span>
                                </p>

                                <Transition
                                    enter-active-class="transition duration-100 ease-out"
                                    enter-from-class="transform scale-95 opacity-0"
                                    enter-to-class="transform scale-100 opacity-100"
                                    leave-active-class="transition duration-75 ease-out"
                                    leave-from-class="transform scale-100 opacity-100"
                                    leave-to-class="transform scale-95 opacity-0"
                                >
                                    <div
                                        v-if="isModuleDropdownOpen && isEditing"
                                        class="absolute z-30 w-full mt-1 bg-white border border-slate-200 shadow-xl overflow-hidden"
                                    >
                                        <div class="p-2 border-b border-slate-100 bg-slate-50 sticky top-0">
                                            <div class="relative">
                                                <input
                                                    type="text"
                                                    v-model="moduleSearch"
                                                    @click.stop
                                                    class="w-full pl-3 pr-2 py-1.5 border border-slate-200 text-xs rounded-sm focus:outline-none focus:border-blue-500 bg-white"
                                                    placeholder="Search module..."
                                                    autofocus
                                                />
                                            </div>
                                        </div>

                                        <div class="max-h-48 overflow-y-auto">
                                            <div
                                                v-for="mod in filteredModules"
                                                :key="mod.id"
                                                @click="selectModule(mod.id)"
                                                class="px-3 py-2.5 text-xs cursor-pointer hover:bg-blue-50 hover:text-blue-700 transition-colors border-b border-slate-50 last:border-0 uppercase font-medium"
                                                :class="{'bg-blue-50 text-blue-700 font-bold border-l-2 border-l-blue-600': form.module === mod.id}"
                                            >
                                                {{ mod.name }}
                                            </div>
                                        </div>
                                    </div>
                                </Transition>
                            </div>
                        </div>

                        <!-- Revision Badge -->
                        <div class="lg:col-span-2 flex flex-col justify-start">
                            <div class="flex items-center gap-1.5 mb-2">
                                <label class="block text-[10px] font-extrabold text-slate-600 uppercase tracking-wider">
                                    Revision
                                </label>
                            </div>
                            <div class="w-full flex">
                                <div class="inline-flex items-center justify-center gap-1.5 px-2 py-2 bg-sky-50 border border-sky-200 text-sky-700 w-full shadow-sm select-none cursor-not-allowed whitespace-nowrap">
                                    <span class="text-xs font-black tracking-widest">v.<span class="text-sm">{{ form.revision ?? 0 }}</span></span>
                                </div>
                            </div>
                        </div>

                        <!-- Remark Textarea (Disabled jika !isEditing) -->
                        <div class="lg:col-span-7">
                            <div class="flex items-center gap-1.5 mb-2">
                                <label class="block text-[10px] font-extrabold text-slate-600 uppercase tracking-wider">
                                    Remark
                                </label>
                            </div>
                            <textarea 
                                v-model="form.remark" 
                                :disabled="!isEditing"
                                rows="2" 
                                placeholder="Write additional information here..." 
                                class="w-full px-4 py-2 border text-xs font-medium transition-all outline-none resize-none leading-relaxed"
                                :class="[
                                    !isEditing 
                                        ? 'bg-slate-100 border-slate-200 text-slate-500 cursor-not-allowed' 
                                        : 'bg-slate-50 border-slate-300 text-slate-800 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100'
                                ]"
                            ></textarea>
                        </div>
                    </div>

                    <!-- Reason Textarea (Hanya Tampil Pas Edit) -->
                    <div v-if="isEditing" class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start mt-4 pt-4 border-t border-slate-100">
                        <div class="lg:col-span-1">
                            <div class="flex items-center gap-1.5 mb-2">
                                <label class="block text-[10px] font-extrabold text-slate-600 uppercase tracking-wider">
                                    Reason of Change <span class="text-rose-500 font-bold">*</span>
                                </label>
                            </div>
                            <textarea 
                                v-model="form.reason" 
                                rows="2" 
                                placeholder="Describe why you are updating this setup..." 
                                @input="form.clearErrors('reason')"
                                class="w-full px-4 py-2 border text-xs font-medium transition-all outline-none resize-none leading-relaxed"
                                :class="[
                                    form.errors.reason
                                        ? 'border-rose-500 bg-rose-50/20 text-rose-900 focus:border-rose-500 focus:ring-2 focus:ring-rose-100'
                                        : 'border-slate-300 bg-slate-50 text-slate-800 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100'
                                ]"
                            ></textarea>
                            <p v-if="form.errors.reason" class="mt-1 text-[10px] font-bold text-rose-500 flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                <span>{{ form.errors.reason }}</span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Table Section (Approvers) -->
                <div class="w-full bg-white border border-slate-200/80 shadow-sm overflow-hidden mb-6 flex flex-col">
                    <!-- Action Bar (HANYA TAMPIL SAAT IS_EDITING) -->
                    <div v-if="isEditing" class="overflow-auto border-t border-slate-100 p-4 flex items-center gap-5 bg-slate-50/50">
                        <button @click="onNew" type="button" class="text-xs font-bold text-slate-600 hover:text-blue-600 hover:underline cursor-pointer">New</button>
                        <button @click="onInsert" type="button" class="text-xs font-bold text-slate-600 hover:text-blue-600 hover:underline cursor-pointer">Insert</button>
                        <button @click="onDelete" type="button" class="text-xs font-bold text-slate-600 hover:text-red-600 hover:underline cursor-pointer">Delete</button>
                        <button @click="onDeleteAll" type="button" class="text-xs font-bold text-slate-600 hover:text-red-700 hover:underline cursor-pointer">Delete All</button>
                    </div>

                    <div class="overflow-auto max-h-[80vh]">
                        <table class="w-full min-w-max divide-y divide-slate-200 text-left whitespace-nowrap">
                            <thead class="bg-blue-100 text-[10px] font-extrabold text-slate-500 uppercase tracking-wider sticky top-0 z-20 shadow-sm">
                                <tr>
                                    <th class="px-4 py-3 text-center w-16">No.</th>
                                    <th class="px-4 py-3 min-w-[250px]">Approver Name</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr
                                    v-for="(item, index) in form.approver"
                                    :key="item.row_key" 
                                    @click="isEditing && (selectedRowIndex = index)"
                                    :class="[
                                        'transition-colors duration-150',
                                        selectedRowIndex === index && isEditing 
                                            ? 'bg-blue-50/80 border-l-4 border-l-blue-500' 
                                            : 'hover:bg-slate-50/50'
                                    ]"
                                >
                                    <td class="px-4 py-3 text-center text-xs font-semibold text-slate-600">
                                        <div class="py-2">{{ index + 1 }}.</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <!-- Custom Select Approver (Readonly jika !isEditing) -->
                                        <div
                                            @click.stop="(e) => {
                                                if (!isEditing) return;
                                                activeDropdownRow = activeDropdownRow === index ? null : index;
                                                if (activeDropdownRow === index) {
                                                    const rect = e.currentTarget.getBoundingClientRect();
                                                    dropdownStyle = {
                                                        top: (rect.bottom + 4) + 'px',
                                                        left: rect.left + 'px',
                                                        width: rect.width + 'px'
                                                    };
                                                }
                                            }"
                                            class="relative z-20 w-full pl-3 pr-3 py-2 border text-xs flex justify-between items-center transition-all shadow-sm select-none"
                                            :class="[
                                                !isEditing 
                                                    ? 'bg-slate-100 border-slate-200 text-slate-500 cursor-not-allowed' 
                                                    : form.errors[`approver.${index}.approver_id`]
                                                        ? 'bg-rose-50/50 border-rose-500 text-rose-900 cursor-pointer focus:border-rose-500 focus:ring-1 focus:ring-rose-500'
                                                        : 'bg-white border-slate-300 text-slate-800 cursor-pointer hover:border-slate-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500'
                                            ]"
                                        >
                                            <span :class="[
                                                item.approver_id ? 'font-semibold text-slate-800' : 'text-slate-400',
                                                !isEditing ? 'text-slate-500' : ''
                                            ]">
                                                {{ getSelectedUserName(item.approver_id) }}
                                            </span>
                                            
                                            <svg v-if="isEditing" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </div>

                                        <p v-if="form.errors[`approver.${index}.approver_id`]" class="mt-1 text-[10px] font-bold text-rose-500 flex items-center gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                            <span>{{ form.errors[`approver.${index}.approver_id`] }}</span>
                                        </p>

                                        <!-- Teleport User Dropdown (Hanya Aktif Pas Edit) -->
                                        <Teleport to="body">
                                            <div v-if="activeDropdownRow === index && isEditing" @click="activeDropdownRow = null" class="fixed inset-0 z-40"></div>
                                            <Transition
                                                enter-active-class="transition duration-100 ease-out"
                                                enter-from-class="transform scale-95 opacity-0"
                                                enter-to-class="transform scale-100 opacity-100"
                                                leave-active-class="transition duration-75 ease-out"
                                                leave-from-class="transform scale-100 opacity-100"
                                                leave-to-class="transform scale-95 opacity-0"
                                            >
                                                <div
                                                    v-if="activeDropdownRow === index && isEditing"
                                                    :style="dropdownStyle"
                                                    class="fixed z-50 bg-white border border-slate-200 shadow-xl overflow-hidden"
                                                >
                                                    <div class="p-2 border-b border-slate-100 bg-slate-50 sticky top-0">
                                                        <div class="relative">
                                                            <input
                                                                type="text"
                                                                v-model="userSearch"
                                                                @click.stop
                                                                class="w-full pl-3 pr-2 py-1.5 border border-slate-200 text-xs rounded-sm focus:outline-none focus:border-blue-500 bg-white"
                                                                placeholder="Type to search user..."
                                                                autofocus
                                                            />
                                                        </div>
                                                    </div>
                                                    <div class="max-h-48 overflow-y-auto">
                                                        <div
                                                            v-for="user in filteredUsers"
                                                            :key="user.id"
                                                            @click="selectUser(index, user.id)"
                                                            class="px-3 py-2.5 text-xs cursor-pointer hover:bg-blue-50 hover:text-blue-700 transition-colors border-b border-slate-50 last:border-0"
                                                            :class="{'bg-blue-50 text-blue-700 font-bold border-l-2 border-l-blue-600': item.approver_id === user.id}"
                                                        >
                                                            {{ user.name }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </Transition>
                                        </Teleport>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Delete -->
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95"
    >
        <div v-if="showConfirmModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
            <div class="bg-white w-full max-w-lg border border-slate-200 shadow-2xl p-6">
                <div class="flex flex-col text-left">
                    <h3 class="text-lg font-black text-slate-900 mb-1">Confirm Deletion</h3>
                    <p class="text-xs text-slate-500 mb-4">
                        Are you sure you want to delete this approval setup <span class="font-bold text-slate-900">{{ props.header?.code }}</span>?
                    </p>

                    <div class="mb-6">
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">
                            Reason for deletion <span class="text-rose-500">*</span>
                        </label>
                        <textarea
                            v-model="deleteForm.reason" 
                            rows="3"
                            class="w-full p-3 text-xs bg-slate-50 border focus:outline-none transition-all resize-none"
                            :class="deleteForm.errors.reason ? 'border-rose-500 text-rose-900' : 'border-slate-200 text-slate-700'"
                            placeholder="Describe why this data is being deleted..."
                            @input="deleteForm.clearErrors('reason')"
                        ></textarea>
                        <p v-if="deleteForm.errors.reason" class="mt-1 text-[10px] font-bold text-rose-500">
                            {{ deleteForm.errors.reason }}
                        </p>
                    </div>

                    <div class="flex gap-3 w-full">
                        <button @click="showConfirmModal = false" class="flex-1 px-4 py-2 bg-slate-300 hover:bg-slate-400 text-slate-700 font-bold text-xs">Cancel</button>
                        <button @click="confirmAction" class="flex-1 px-4 py-2 bg-rose-600 hover:bg-rose-800 text-white font-bold text-xs">Yes, Delete</button>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>