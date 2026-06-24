<script setup>
import "vue3-toastify/dist/index.css";
import { ref, computed, watch } from 'vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { toast } from 'vue3-toastify';
import dayjs from "dayjs";
import "dayjs/locale/id";
import { ceil } from "lodash";

defineOptions({layout: AuthenticatedLayout});

// Datetime format
dayjs.locale("id");
const formatLogDate = (date) => {
    if (!date) return "-"; // Guard clause jika data tanggal kosong/null
    return dayjs(date).format("DD MMM YYYY, HH:mm:ss [WIB]");
};

const formatTableDate = (date) => {
    if (!date) return "-";
    return dayjs(date).format("DD-MMM-YYYY HH:mm:ss");
};

// Initialize data form controller
const props = defineProps({
    units: Array
})

const page = usePage();
const errors = computed(() => page.props.errors ||{});
const deleteForm = useForm({
    ids: [],
    remark: ''
})

const isSlideOverOpen = ref(false);
const slideOverTitle = ref("New UoM");
const isEditMode = ref(false);
const searchQuery = ref("");
const selectedFilter = ref("all");
const selectedUnits = ref([]);
const isRefreshing = ref(false);
const isDeleting = ref(false);
const showConfirmModal = ref(false);
const deleteReason = ref("");
const itemsPerPage = ref(10);
const currentPage = ref(1);

// History
const isHistoryModalOpen = ref(false);
const historyLogs = ref([]);
const isLoadingHistory = ref(false);
const selectedUnitsName = ref("");

// Fungsi pencarian
const filteredUnits = computed(() => {
    return (props.units || []).filter((unit) => {
        const symbol = unit.symbol ? unit.symbol.toLowerCase() : '';
        const name = unit.name ? unit.name.toLowerCase() : '';
        const remark = unit.remark ? unit.remark.toLowerCase() : '';
        const search = searchQuery.value.toLowerCase();

        const matchesSearch = symbol.includes(search) || 
                              name.includes(search) || 
                              remark.includes(search);

        if (selectedFilter.value === 'enable') return matchesSearch && unit.is_active;
        if (selectedFilter.value === 'disable') return matchesSearch && !unit.is_active;

        return matchesSearch;
    })
});

// Refresh table
const refreshTable = () => {
    isRefreshing.value = true;
    router.reload({
        only: ['units'],
        onSuccess: () => {
            isRefreshing.value = false;
            toast.success('Refreshing success')
        },
        onError: () => {
            isRefreshing.value = false;
            toast.error('Failed to refresh data');
        }
    });
};

watch(
    errors, 
    (newErrors) => {
        if (newErrors && newErrors.error) {
            toast.error(newErrors.error);
        }
    }, 
    { deep: true }
);

// Pagination
watch([searchQuery, selectedFilter, itemsPerPage], () => {
    currentPage.value = 1;
})


const paginateUnits = computed(() => {
    const start = (currentPage.value -1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return filteredUnits.value.slice(start, end);
});

const totalFiltered = computed(() => filteredUnits.value.length);
const totalPages = computed(() => Math,ceil(totalFiltered.value / itemsPerPage.value));

const paginationStart = computed(() => {
    if(totalFiltered.value === 0) return 0;
    return(currentPage.value -1) * itemsPerPage.value + 1;
});

const paginationEnd = computed(() => {
    return Math.min(currentPage.value * itemsPerPage.value, totalFiltered.value);
});

const goToPrev = () => { if(currentPage.value > 1) currentPage.value--; };
const goToNext = () => { if(currentPage.value < totalPages.value) currentPage.value++ }

// Checkbox
const isAllSelected = computed(
    () => 
    paginateUnits.value.length > 0 &&
    paginateUnits.value.every((u) => selectedUnits.value.includes(u.id))
);

const toggleSelectAll = () => { 
    if(isAllSelected.value){
        selectedUnits.value = selectedUnits.value.filter(
            (id) => !paginateUnits.value.some((u) => u.id === id)
        );
    }else{
        paginateUnits.value.forEach((unit) => {
            if(!selectedUnits.value.includes(unit.id)){
                selectedUnits.value.push(unit.id);
            }
        })
    }
};

const toggleSelectUnit = (id) => {
    const index = selectedUnits.value.indexOf(id);
    index > -1
        ? selectedUnits.value.splice(index, 1)
        : selectedUnits.value.push(id);
}

// Form binding
const form = useForm({
    id: null,
    symbol: "",
    name: "",
    is_active: true,
    remark: ""
});

const openCreateDrawer = () => {
    isEditMode.value = false;
    slideOverTitle.value = "Register new UoM";
    form.reset();
    form.clearErrors();
    form.is_active = true;
    isSlideOverOpen.value = true;
}

const openEditDrawer = (unit) => {
    isEditMode.value = true;
    slideOverTitle.value = `Update UoM data: ${unit.symbol}`;
    form.clearErrors();

    form.id = unit.id;
    form.symbol = unit.symbol;
    form.name = unit.name;
    form.remark = unit.remark;
    form.is_active = !!unit.is_active;

    isSlideOverOpen.value = true;
}

// Submit
const handleSubmit = () => {
    form.clearErrors();
    let isValid = true;

    if(!form.symbol){
        form.setError('symbol', 'This field is required');
        isValid = false;
    }

    if(!form.name){
        form.setError('name', 'This field is required');
        isValid = false;
    }else if(form.name.length > 150){
        form.setError('name', 'UoM name cannot exceed 150 characters');
        isValid = false;
    }

    if (isEditMode.value) {
        if (!form.remark || !form.remark.trim()) {
            form.setError('remark', 'Please fill the content change reason');
            isValid = false;
        }
    }

    if(!isValid) return;

    form.transform((data) => {
        const payload = { ...data };

        if(isEditMode.value){
            payload._method = "put";
        }else{
            delete payload._method;
        }

        return payload;
    }).post(isEditMode.value ? `/units/${form.id}` : '/units', {
        preserveErrors: true,
        onSuccess: () => {
            isSlideOverOpen.value = false;
            form.reset();
        },
        onError: (errrs) => {
            console.error(errors);
        }
    });
}

// Bulk delete
const deleteSelected = () => {
    if(selectedUnits.value.length > 0) showConfirmModal.value = true;
}

const confirmAction = () => {
    if(!deleteReason.value.trim()){
        deleteForm.setError('remark', 'Please fill the deletion reason');
        return;
    }

    deleteForm.ids = selectedUnits.value;
    deleteForm.remark = deleteReason.value;
    deleteForm.post("/units/mass-delete", {
        preserveScroll: true,
        onSuccess: () => {
            selectedUnits.value = [];
            showConfirmModal.value = false;
            deleteReason.value = ""; 
            deleteForm.clearErrors();
        }
    });
}

// History logs
const openHistoryModal = async(id, name) => {
    isHistoryModalOpen.value = true;
    isLoadingHistory.value = true;
    selectedUnitsName.value = name;
    historyLogs.value = [];

    try{
        const response = await axios.get(`/units/${id}/logs`);
        historyLogs.value = response.data;
    } catch(error){
        toast.error("Failed to load revision history data.");
    } finally{
        isLoadingHistory.value = false;
    }
}

const closeHistoryModal = () => {
    isHistoryModalOpen.value = false;
    isHistoryModalOpen.value = false;
    selectedUnitsName.value = "";
    historyLogs.value = [];
};

const getChangedFields = (log) => {
    const ignoredKeys = ['id', 'uuid', 'created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by', 'revision', 'deleted_by'];
    const changes = [];
    
    if (log.event_name === 'update' && log.before && log.after) {
        Object.keys(log.after).forEach(key => {
            if (!ignoredKeys.includes(key) && log.before[key] !== log.after[key]) {
                changes.push({
                    field: key,
                    before: log.before[key],
                    after: log.after[key]
                });
            }
        });
    } else if (log.event_name === 'delete' && log.before) {
        Object.keys(log.before).forEach(key => {
            if (!ignoredKeys.includes(key) && log.before[key] !== null) {
                changes.push({
                    field: key,
                    before: log.before[key],
                    after: null
                });
            }
        });
    } else if (log.event_name === 'create' && log.after) {
        Object.keys(log.after).forEach(key => {
            if (!ignoredKeys.includes(key) && log.after[key] !== null) {
                changes.push({
                    field: key,
                    before: null,
                    after: log.after[key]
                });
            }
        });
    }
    
    return changes;
};

const formatFieldName = (text) => {
    if (!text) return '';
    return text.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
};
</script>

<template>
    <Head title="List of UoM"/>
    <div class="flex min-h-screen bg-slate-50 font-sans antialiased text-slate-800">
        <!-- Content Wrapper -->
        <div class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                <!-- Menu title -->
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">Unit of Measure</h1>
                    <p class="text-xs text-slate-500 mt-1">Manage application unit of measurement.</p>
                </div>
                <!-- Toolbar -->
                <div class="sticky top-0 z-40 bg-white/95 backdrop-blur-sm border-b border-slate-200 px-3 py-2 shadow-sm ">
                    <div class="flex items-center justify-between w-full">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-1.5">
                                <button type="button" @click="openCreateDrawer" class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-bold text-blue-600 bg-blue-50 border border-blue-100 transition-all hover:bg-blue-100 active:scale-95 shadow-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    <span>New</span>
                                </button>
                                <div v-if="selectedUnits.length > 0" class="w-px h-4 bg-slate-200 mx-1"></div>
                                <button
                                    type="button"
                                    @click="refreshTable"
                                    :disabled="isRefreshing"
                                    class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-bold text-slate-600 bg-orange-50 border border-slate-200 transition-all  hover:bg-orange-50 active:scale-95 shadow-sm disabled:opacity-60"
                                >
                                    <svg 
                                        xmlns="http://www.w3.org/2000/svg" 
                                        :class="isRefreshing ? 'animate-spin text-blue-600' : 'text-slate-500'"
                                        class="h-3.5 w-3.5 transition-colors duration-150" 
                                        fill="none" 
                                        viewBox="0 0 24 24" 
                                        stroke-width="2" 
                                        stroke="currentColor"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                    </svg>
                                    
                                    <span>{{ isRefreshing ? 'Refreshing...' : 'Refresh' }}</span>
                                </button>
                                <div v-if="selectedUnits.length > 0" class="w-px h-4 bg-slate-200 mx-1"></div>
                                <button type="button" @click="deleteSelected" :disabled="selectedUnits.length === 0" class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-bold text-rose-600 bg-rose-50 border border-rose-100 transition-all hover:bg-rose-100 active:scale-95 shadow-sm disabled:opacity-40 disabled:active:scale-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    <span>Delete Selected ({{ selectedUnits.length }})</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Search Bar, Per Page, Status-->
            <div class="bg-white border border-slate-200/80 shadow-sm p-4 mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="relative flex-1 max-w-md">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </span>
                    <input
                        type="text"
                        v-model="searchQuery"
                        placeholder="Search dynamically..."
                        class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 focus:border-blue-500 text-xs font-semibold focus:outline-none transition"
                    />
                </div>
                <!-- Per page section-->
                <div class="flex items-center gap-6">
                    <div class="flex items-center gap-2">
                        <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Per Page:</label>
                        <select
                            v-model="itemsPerPage"
                            class="px-3 py-2 bg-white border border-slate-200 text-slate-700 text-xs font-semibold focus:outline-none cursor-pointer w-20"
                        >
                            <option :value="5">5</option>
                            <option :value="10">10</option>
                            <option :value="20">20</option>
                            <option :value="50">50</option>
                        </select>
                    </div>
                </div>

                <!-- Status control -->
                <div class="flex items-center gap-2">
                    <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Status:</label>
                    <select
                        v-model="selectedFilter"
                        class="px-3 py-2 bg-white border border-slate-200 text-slate-700 text-xs font-semibold focus:outline-none cursor-pointer min-w-[100px]"
                    >
                        <option value="all">All</option>
                        <option value="enable">Enabled</option>
                        <option value="disable">Disabled</option>
                    </select>
                </div>
            </div>
            
            <!-- Content table -->
            <div class="bg-white border border-slate-200/80 shadow-sm overflow-hidden mb-auto flex flex-col">
                <div class="overflow-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left">
                        <thead class="bg-blue-300 text-[11px] font-bold text-slate-700 uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-4 w-12 text-center">
                                    <input type="checkbox" :checked="isAllSelected" @change="toggleSelectAll" class="rounded border-slate-300 text-blue-600 h-4 w-4 transition cursor-pointer" />
                                </th>
                                <th class="px-6 py-4">Symbol</th>
                                <th class="px-6 py-4">Name</th>
                                <th class="px-6 py-4 text-center">Status</th>
                                <th class="px-6 py-4">Revision</th>
                                <th class="px-6 py-4">Remark</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs font-medium text-slate-700">
                            <tr 
                                v-for="unit in filteredUnits" 
                                :key="unit.id" 
                                @click="openEditDrawer(unit)"
                                class="hover:bg-blue-50/50 transition hover:cursor-pointer duration-150" 
                                :class="{'bg-blue-50/30 font-medium': selectedUnits.includes(unit.id)}"
                            >
                                <td class="px-6 py-4 text-center" @click.stop>
                                    <input type="checkbox" :value="unit.id" :checked="selectedUnits.includes(unit.id)" @change="toggleSelectUnit(unit.id)" class="rounded border-slate-300 text-blue-600 h-4 w-4 transition cursor-pointer" />
                                </td>
                                <td class="px-6 py-4 font-mono font-bold text-slate-900">
                                    {{ unit.symbol }}
                                </td>
                                <td class="px-6 py-4 font-semibold text-slate-700">
                                    {{ unit.name }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span 
                                        :class="unit.is_active 
                                            ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' 
                                            : 'bg-rose-50 text-rose-600 border border-rose-100'" 
                                        class="inline-flex items-center px-2.5 py-0.5 text-xs text-[10px] font-bold uppercase tracking-wide shadow-sm"
                                    >
                                        <span :class="unit.is_active ? 'bg-emerald-500' : 'bg-rose-500'" class="h-1.5 w-1.5 rounded-full mr-1.5"></span>
                                        {{ unit.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td 
                                    @click.stop="openHistoryModal(unit.id, unit.name)"
                                    class="px-4 py-3 cursor-pointer hover:bg-slate-50 transition-colors"
                                >
                                    <div class="flex justify-center">
                                        <span
                                            class="px-2.5 py-0.5 text-xs text-[10px] text-blue-800 bg-blue-100"
                                        >
                                            Rev. {{ unit.revision || 0 }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-slate-500">
                                    {{ unit.remark || '-' }}
                                </td>
                            </tr>
                            <tr v-if="filteredUnits.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-slate-400 font-medium">No Unit of Measure data found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="flex items-center justify-between px-6 py-4 bg-white border-t border-slate-200 mt-auto">
                    <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        Showing {{ totalFiltered === 0 ? 0 : paginationStart }} to {{ paginationEnd }} of {{ totalFiltered }}
                    </div>
                    
                    <div class="flex items-center gap-1 text-[11px] font-bold">
                        <button 
                            @click="goToPrev" 
                            :disabled="currentPage === 1" 
                            class="px-2 py-1.5 text-slate-400 hover:text-blue-600 disabled:opacity-50 disabled:hover:text-slate-400 transition"
                        >
                            &laquo; Previous
                        </button>
                        
                        <div class="bg-blue-600 text-white rounded-sm w-7 h-7 flex items-center justify-center shadow-sm">
                            {{ currentPage }}
                        </div>
                        
                        <button 
                            @click="goToNext" 
                            :disabled="currentPage === totalPages || totalFiltered === 0" 
                            class="px-2 py-1.5 text-slate-400 hover:text-blue-600 disabled:opacity-50 disabled:hover:text-slate-400 transition"
                        >
                            Next &raquo;
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form slide over -->
        <div v-show="isSlideOverOpen" class="fixed inset-0 z-40 overflow-hidden" role="dialog" aria-modal="true">
            <div class="absolute inset-0 overflow-hidden">
                <transition enter-active-class="ease-in-out duration-300" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="ease-in-out duration-300" leave-from-class="opacity-100" leave-to-class="opacity-0">
                    <div v-show="isSlideOverOpen" @click="isSlideOverOpen = false" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>
                </transition>

                <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                    <transition enter-active-class="transform transition ease-in-out duration-300" enter-from-class="translate-x-full" enter-to-class="translate-x-0" leave-active-class="transform transition ease-in-out duration-300" leave-from-class="translate-x-0" leave-to-class="translate-x-full">
                        <div v-show="isSlideOverOpen" class="pointer-events-auto w-screen max-w-2xl bg-white shadow-2xl flex flex-col h-full border-l border-slate-200">
                            <div class="bg-slate-900 px-6 py-5 flex items-center justify-between shrink-0">
                                <div>
                                    <h2 class="text-base font-black text-white tracking-tight">{{ slideOverTitle }}</h2>
                                    <p class="text-[10px] text-slate-400 mt-0.5">Override UoM data.</p>
                                </div>
                                <button type="button" @click="isSlideOverOpen = false" class="text-slate-400 hover:text-white hover:bg-slate-800 p-1.5 transition">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <form @submit.prevent="handleSubmit" class="flex-1 p-6 overflow-y-auto space-y-6 bg-slate-200/70">
                                <div class="flex-1 overflow-y-auto p-6 space-y-5 bg-slate-50/50">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-400 tracking-wider mb-2">UoM Symbol <span class="text-rose-500 text-bold">*</span></label>
                                        <input type="text" v-model="form.symbol" maxlength="10" placeholder="UoM Symbol" 
                                            :class="[
                                                'w-full pl-4 pr-4 py-2.5 bg-slate-50 border focus:bg-white focus:ring-2 text-xs font-medium transition-all outline-none ',
                                                form.errors.symbol 
                                                    ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-100 text-rose-600' 
                                                    : 'border-slate-200 focus:border-blue-500 focus:ring-blue-100 text-slate-700'
                                            ]"
                                        />
                                        <p v-if="form.errors.symbol" class="mt-1.5 text-[10px] font-bold text-rose-500">{{ form.errors.symbol }}</p>
                                        <p v-else class="mt-1.5 text-[10px] font-medium text-slate-400">Unique units symbol</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-400 tracking-wider mb-2">UoM Name <span class="text-rose-500 text-bold">*</span></label>
                                        <input type="text" v-model="form.name" maxlength="150" placeholder="UoM Name" 
                                            :class="[
                                                'w-full pl-4 pr-4 py-2.5 bg-slate-50 border focus:bg-white focus:ring-2 text-xs font-medium transition-all outline-none ',
                                                form.errors.name 
                                                    ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-100 text-rose-600' 
                                                    : 'border-slate-200 focus:border-blue-500 focus:ring-blue-100 text-slate-700'
                                            ]"
                                        />
                                        <p v-if="form.errors.name" class="mt-1.5 text-[10px] font-bold text-rose-500">{{ form.errors.name }}</p>
                                    </div>
                                    <div class="space-y-4 pt-2">
                                        <div class="flex items-center justify-between p-3.5 bg-white border border-slate-200/80  shadow-sm">
                                            <div class="flex flex-col min-w-0 pr-4">
                                                <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">Activation Status</span>
                                                <span class="text-[11px] font-medium text-slate-400 mt-0.5 truncate">{{ form.is_active ? "🟢 Active." : "🔴 Inactive." }}</span>
                                            </div>
                                            <button type="button" @click="form.is_active = !form.is_active" :class="form.is_active ? 'bg-emerald-500' : 'bg-slate-200'" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out active:scale-95"><span :class="form.is_active ? 'translate-x-5' : 'translate-x-0'" class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-md transition duration-200"></span></button>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-400 tracking-wider mb-2">{{ !isEditMode ? 'Remark (Optional)' :  'Change Reason' }} <span class="text-bold text-rose-500" v-if="isEditMode">*</span></label>
                                        <textarea 
                                            v-model="form.remark" 
                                            :placeholder="isEditMode ? 'Describe why you are altering this data (Required) ...' : 'Write additional information here (Optional) ...'"
                                            @input="form.clearErrors('remark')"
                                            rows="3"
                                            :class="[
                                                'w-full pl-4 pr-4 py-2.5 bg-slate-50 border focus:bg-white focus:ring-2 text-xs font-medium transition-all outline-none ',
                                                form.errors.remark 
                                                    ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-100 text-rose-600' 
                                                    : 'border-slate-200 focus:border-blue-500 focus:ring-blue-100 text-slate-700'
                                            ]"
                                        ></textarea>
                                        
                                        <Transition
                                            enter-active-class="transition duration-150 ease-out"
                                            enter-from-class="transform -translate-y-1 opacity-0"
                                            enter-to-class="transform translate-y-0 opacity-100"
                                        >
                                            <p v-if="form.errors.remark" class="text-xs text-rose-600 mt-1 font-bold flex items-center gap-1">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5 flex-shrink-0">
                                                    <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                                                </svg>
                                                {{ form.errors.remark }}
                                            </p>
                                        </Transition>
                                    </div>
                                </div>
                            </form>
                            <div class="border-t border-slate-200 px-6 py-4 bg-white flex items-center justify-end gap-3">
                                <button type="button" @click="isSlideOverOpen = false" class="px-4 py-2.5 bg-white border border-slate-200 text-xs font-bold text-slate-700  hover:bg-slate-50 transition-all duration-150 active:scale-95 shadow-sm">Cancel</button>
                                <button type="submit" @click="handleSubmit" :disabled="form.processing" class="px-5 py-2.5 bg-blue-600 text-white font-bold text-xs  shadow-md hover:bg-blue-700 transition-all duration-150 active:scale-95 disabled:opacity-50 flex items-center gap-2">
                                    <svg v-if="form.processing" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ form.processing ? "Saving..." : isEditMode ? "Update" : "Save" }}
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>
        </div>

        <!-- Modal konfirmasi hapus -->
        <transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="showConfirmModal"
                class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 backdrop-blur-sm p-4"
            >
                <div
                    class="bg-white w-full max-w-sm border border-slate-200 shadow-2xl p-6"
                >
                    <div class="flex flex-col text-left">
                        <h3 class="text-lg font-black text-slate-900 mb-1">
                            Confirm Deletion
                        </h3>
                        <p class="text-xs text-slate-500 mb-4">
                            Are you sure you want to delete
                            <span class="font-bold text-slate-900"
                                >{{ selectedUnits.length }} items</span
                            >? This action cannot be undone.
                        </p>

                        <div class="mb-6">
                            <label
                                class="block text-[10px] font-bold text-slate-500 uppercase mb-1"
                                >Reason for Deletion
                                <span class="text-rose-500">*</span></label
                            >
                            <textarea
                                v-model="deleteReason"
                                rows="2"
                                @input="deleteForm.clearErrors('remark')" 
                                :class="deleteForm.errors.remark ? 'border-rose-500' : 'border-slate-300'"
                                class="w-full p-2 border text-xs focus:outline-none focus:border-blue-500 transition-all"
                                placeholder="e.g. Data redundancy, wrong entry, etc."
                            ></textarea>
                            <p v-if="deleteForm.errors.remark" class="text-xs text-rose-600 mt-1 font-medium">{{ deleteForm.errors.remark }}</p>
                        </div>

                        <div class="flex gap-3 w-full">
                            <button
                                @click="showConfirmModal = false"
                                @input="deleteForm.clearErrors('remark')"
                                class="flex-1 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition"
                            >
                                Cancel
                            </button>
                            <button
                                @click="confirmAction"
                                :disabled="deleteForm.processing"
                                class="flex-1 px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs transition"
                            >
                                {{
                                    deleteForm.processing
                                        ? "Deleting..."
                                        : "Yes, Delete"
                                }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </transition>

        <!-- History logs modal -->
         <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-show="isHistoryModalOpen"
                @click.self="closeHistoryModal"
                class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 backdrop-blur-sm p-4"
            >
                <div
                    class="bg-white w-full max-w-3xl border border-slate-200 shadow-2xl flex flex-col max-h-[90vh] overflow-hidden"
                >
                    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 bg-white shrink-0">
                        <h3 class="text-sm font-black text-slate-900 uppercase tracking-wider flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5 text-blue-600">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            UoM Revision History
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
                                        {{ formatLogDate(log.created_at) }}
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
                                </div>

                            </div>
                        </div>

                    </div>

                    <div class="border-t border-slate-100 px-6 py-3.5 bg-white flex justify-end shrink-0">
                        <button
                            type="button"
                            @click="closeHistoryModal"
                            class="px-5 py-2 bg-slate-100 border border-slate-200 text-slate-700 font-bold text-xs hover:bg-slate-200 transition active:scale-95 shadow-sm -lg"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </div>

</template>