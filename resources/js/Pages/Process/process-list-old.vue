<script setup>
import { ref, watch, computed } from 'vue';
import { Head, router, Link, usePage, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { toast } from 'vue3-toastify';

defineOptions({ layout: AuthenticatedLayout, inheritAttrs: false });

const page = usePage();
const searchQuery = ref('');
const selectedIds = ref([]);
const showConfirmModal = ref(false);
const isFilterModalOpen = ref(false);
const isRefreshing = ref(false);
const isSearching = ref(false);
const selectedFilter = ref("all");
const deleteReason = ref("");
const isHistoryModalOpen = ref(false);
const historyLogs = ref([]);
const isLoadingHistory = ref(false);
const selectedName = ref("");

// Delete form dalam modal
const deleteForm = useForm({
    ids: [],
    remark: ""
});

const toggleSelectAll = (event) => {
    if (event.target.checked) {
        selectedIds.value = props.headers.data.map(h => h.id);
    } else {
        selectedIds.value = [];
    }
};

const deleteSelected = () => {
    if (selectedIds.value.length === 0) return;
    showConfirmModal.value = true;
};

const errors = computed(() => page.props.errors || {});

const confirmAction = () => {
    deleteForm.clearErrors();

    if(!deleteForm.remark || deleteForm.remark.trim() === ''){
        deleteForm.setError('remark', 'This field is required');
        return;
    }

    deleteForm.ids = selectedIds.value;

    deleteForm.post("/process/mass-delete", {
        preserveScroll: true,
        onSuccess: () => {
            selectedIds.value = [];
            showConfirmModal.value = false;
            deleteForm.reset();
        }
    })
};

const cancelConfirmAction = () => {
    deleteForm.clearErrors();
    selectedIds.value = [];
    showConfirmModal.value = false;
    deleteForm.reset();
}

const props = defineProps({
    headers: {
        type: Object,
        default: () => ({ data: [], links: [], per_page: 10 }),
    },
});

const perPage = ref(props.headers?.per_page || 10);

// Refreshing table
const refreshTable = () => {
    isRefreshing.value = true;
    router.reload({
        only:["headers"],
        onSuccess: () => {
            isRefreshing.value = false;
        },
        onError: () => {
            isRefreshing.value = false;
            toast.error("Failed to refresh data");
        }
    })
}

// Menggunakan computed untuk memantau status loading agar lebih reaktif
const isProcessing = computed(() => page.props.processing || false);

const debounce = (fn, delay) => {
    let timeout;
    return (...args) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => fn(...args), delay);
    };
};

const handleSearch = debounce((value) => {
    router.get(
        '/process',
        {
            search: value,
            per_page: props.headers.perPage || 10
        },
        { preserveScroll: true, preserveState: true }
    );
}, 500);

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

watch(
    searchQuery,
    debounce((value) => {
        router.get(
            window.location.pathname,
            {
                search: value,
                per_page: perPage.value,
                filter: selectedFilter.value
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                onStart: () => (isSearching.value = true),
                onFinish: () => (isSearching.value = false)
            }
        )
    }, 300),
);

const formatDateTime = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false
    }).replace(',', '').replace(/\./g, ':');
};

const goToDetail = (id) => {
    router.get(`/process/${id}`);
};

const updatePerPage = (event) => {
    router.get(
        window.location.pathname,
        {
            per_page: perPage.value,
            search: searchQuery.value,
            filter: selectedFilter.value
        },
        {
            preserveState: true,
            onStart: () => (isSearching.value = true),
            onFinish: () => (isSearching.value = false),
        }
    );
};

// proses histori change logs revisi
const openHistoryModal = async(id, name) => {
    isHistoryModalOpen.value = true;
    isLoadingHistory.value = true;
    selectedName.value = name,
    historyLogs.value = [];

    try{
        const response = await axios.get(`/process/${id}/logs`);
        historyLogs.value = response.data;
    } catch(errors){
        const firstError = Object.values(err)[0];
        toast.error(firstError);
    } finally {
        isLoadingHistory.value = false;
    }
}

const closeHistoryModal = () => {
    isHistoryModalOpen.value = false;
    selectedName.value = "";
    historyLogs.value = [];
}

// Isi perubahan
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

// Modal filter
const openFilterModal = () => {
    isFilterModalOpen.value = true;
}

const closeFilterModal = () => {
    isFilterModalOpen.value = false;
}

const emit = defineEmits(['apply', 'cancel'])
// Row index pointer tracker
const selectedIndex = ref(0)

// Main Array State untuk filter table
const conditions = ref([
  { openParen: '', field: '', compare: '=', value: '', closeParen: '', logic: 'AND' }
])

const createEmptyRow = () => ({
  openParen: '',
  field: '',
  compare: '=',
  value: '',
  closeParen: '',
  logic: 'AND'
})

// Fungsi Eksekusi Utama (Emit Data)
const handleApply = () => {
  // Mengirim data array objek filter saat ini ke komponen luar
  emit('apply', conditions.value)
}

const handleCancel = () => {
    isFilterModalOpen.value = false;
    conditions.value = [createEmptyRow()];
    selectedIndex.value = 0;
    emit('cancel');
}

// 1. NEW
const addRow = () => {
  conditions.value.push(createEmptyRow())
  selectedIndex.value = conditions.value.length - 1
}

// 2. DELETE (Berdasarkan baris terpilih)
const deleteRow = () => {
  if (conditions.value.length === 0) return
  if (selectedIndex.value !== null && selectedIndex.value >= 0) {
    conditions.value.splice(selectedIndex.value, 1)
    selectedIndex.value = conditions.value.length > 0 ? 0 : null
  } else {
    conditions.value.pop()
  }
}

// 3. DELETE ALL
const deleteAllRows = () => {
  conditions.value = []
  selectedIndex.value = null
}

// 4. INSERT (Menyisipkan tepat di atas indeks terpilih)
const insertRow = () => {
  if (selectedIndex.value !== null && selectedIndex.value >= 0) {
    conditions.value.splice(selectedIndex.value, 0, createEmptyRow())
  } else {
    conditions.value.unshift(createEmptyRow())
    selectedIndex.value = 0
  }
}

// 5. COPY (Menduplikasi data baris aktif tepat di bawahnya)
const copyRow = () => {
  if (selectedIndex.value !== null && selectedIndex.value >= 0) {
    const clonedRow = { ...conditions.value[selectedIndex.value] }
    conditions.value.splice(selectedIndex.value + 1, 0, clonedRow)
    selectedIndex.value = selectedIndex.value + 1
  }
}


</script>

<template>
    <Head title="PMFEA Process Function" />

    <div class="flex min-h-screen bg-slate-50 font-sans antialiased text-slate-800">
        <div class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">PMFEA Process Function list</h1>
                    <p class="text-xs text-slate-500 mt-1">Manage PMFEA process function list.</p>
                </div>
                <div class="sticky top-0 z-30 bg-white/95 backdrop-blur-sm border border-slate-200 px-3 py-2 shadow-sm">
                    <div class="flex items-center justify-between w-full">
                        <div class="flex items-center gap-1.5">
                            <!-- Tombol Filter -->
                            <button
                                type="button"
                                @click="openFilterModal"
                                class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-bold text-slate-600 bg-green-50 border border-slate-200 transition-all hover:bg-green-100 active:scale-95 shadow-sm disabled:opacity-60"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-3.5 w-3.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
                                </svg>

                                <span>Filter</span>
                            </button>
                            <!-- Tombol New -->
                            <Link 
                                href="/process/create" 
                                class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-bold text-blue-600 bg-blue-50 border border-blue-100 transition-all hover:bg-blue-100 active:scale-95 shadow-sm">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-3.5 w-3.5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="2"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                                    />
                                </svg>
                                <span>New</span>
                            </Link>
                            <!-- Tombol refresh -->
                            <button
                                @click="refreshTable"
                                :disabled="isRefreshing"
                                type="button"
                                class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-bold text-slate-600 bg-orange-50 border border-slate-200 transition-all hover:bg-orange-100 active:scale-95 shadow-sm disabled:opacity-60"
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
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"
                                    />
                                </svg>
                                <span>
                                    <span>{{ isRefreshing ? 'Refreshing...' : 'Refresh' }}</span>
                                </span>
                            </button>
                            
                            <!-- Tombol delete -->
                            <button 
                                @click="deleteSelected" 
                                :disabled="selectedIds.length === 0" 
                                class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-bold text-rose-600 bg-rose-50 border border-rose-100 transition-all hover:bg-rose-100 active:scale-95 shadow-sm disabled:opacity-40 disabled:active:scale-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    Delete Selected ({{ selectedIds.length }})
                            </button>

                            <!-- Tombol Tools -->
                            <div class="relative group">
                                <button
                                    type="button"
                                    class="flex items-center gap-1.5 px-3 py-1.5 bg-white text-slate-600 hover:text-blue-600 border border-slate-200 hover:border-blue-300 hover:bg-blue-50/50 text-xs font-bold transition-all shadow-sm"
                                >
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
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m9 13.5 3 3m0 0 3-3m-3 3v-6m1.06-4.19-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
                                            </svg>
                                            <span>Download Template</span>
                                        </button>
                                        <button type="button" class="w-full text-left px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-blue-50 hover:text-blue-600 flex items-center gap-2 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 8.25H7.5a2.25 2.25 0 0 0-2.25 2.25v9a2.25 2.25 0 0 0 2.25 2.25h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25H15m0-3-3-3m0 0-3 3m3-3V15" />
                                            </svg>

                                            Import Data
                                        </button>

                                        <button type="button" class="w-full text-left px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-blue-50 hover:text-blue-600 flex items-center gap-2 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 8.25H7.5a2.25 2.25 0 0 0-2.25 2.25v9a2.25 2.25 0 0 0 2.25 2.25h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25H15M9 12l3 3m0 0 3-3m-3 3V2.25" />
                                            </svg>

                                            Export Data
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex-1 flex flex-col">
                <div class="bg-white border border-slate-200/80 shadow-sm p-4 mb-6 flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
                    <div class="relative flex-1 max-w-md">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                            <svg
                                v-if="isSearching"
                                class="animate-spin h-4 w-4 text-blue-600"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                ></circle>
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                ></path>
                            </svg>
                            <svg
                                v-else
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-4 w-4 transition-all"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                />
                            </svg>
                        </span>
                        <input
                            type="text"
                            placeholder="Search dynamically..."
                            v-model="searchQuery"
                            class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 focus:border-blue-500 text-xs font-semibold focus:outline-none transition"
                        />
                    </div>

                    <div class="flex items-center gap-4">
                        <!-- Per page -->
                        <div class="flex items-center gap-2">
                            <label
                                class="text-xs font-bold text-slate-400 uppercase tracking-wider"
                                >Per Page:</label
                            >
                            <select 
                                v-model="perPage"
                                @change="updatePerPage"
                                class="px-3 py-2.5 bg-white border border-slate-200 text-slate-700 text-xs font-semibold focus:outline-none focus:border-blue-500 cursor-pointer transition"
                            >
                                <option :value="10">10</option>
                                <option :value="25">25</option>
                                <option :value="50">50</option>
                                <option :value="100">100</option>
                            </select>
                        </div>

                        <!-- Status Filter -->
                        <div class="flex items-center gap-2">
                            <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">
                                Status:
                            </label>
                            <select 
                                v-model="selectedFilter"
                                @change="updatePerPage"
                                class="px-3 py-2.5 bg-white border border-slate-200 text-slate-700 text-xs font-semibold focus:outline-none focus:border-blue-500 cursor-pointer transition"
                            >
                                <option value="all">All</option>
                                <option value="enable">Enabled</option>
                                <option value="disable">Disabled</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="bg-white border border-slate-200/80 shadow-sm overflow-hidden mb-auto">
                    <div class="overflow-auto border-t border-slate-100">
                        <table class="w-full text-left border-collapse bg-white">
                            <thead class="bg-blue-300 text-slate-500 uppercase tracking-wider text-[12px] font-bold">
                                <tr>
                                    <th class="px-6 py-4 w-12 text-center">
                                        <input type="checkbox" @change="toggleSelectAll" :checked="selectedIds.length === headers.data.length && headers.data.length > 0" class="rounded border-slate-300 text-blue-600 h-4 w-4" />
                                    </th>
                                    <th class="px-6 py-4">Process Function Name</th>
                                    <th class="px-6 py-4 text-center">Revision</th>
                                    <th class="px-6 py-4">Remarks</th>
                                    <th class="px-6 py-4 text-center">Children</th>
                                    <th class="px-6 py-4 text-center">Created At At</th>
                                    <th class="px-6 py-4 text-center">Updated At</th>
                                    <th class="px-6 py-4 text-center">Last Updated By</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-xs text-slate-600">
                                <tr v-for="header in headers.data" :key="header.id" @click="goToDetail(header.id)" class="hover:bg-blue-50 transition-colors cursor-pointer">
                                    <td class="px-6 py-4 text-center" @click.stop><input type="checkbox" v-model="selectedIds" :value="header.id" class="rounded border-slate-300 text-blue-600 h-4 w-4" /></td>
                                    <td class="px-6 py-4 font-semibold text-slate-900">{{ header.name }}</td>
                                    <td 
                                        @click.stop="
                                            openHistoryModal(header.id, header.name)
                                        "
                                        class="px-4 py-3 cursor-pointer hover:bg-slate-50 transition-colors"
                                    >
                                        <div class="flex justify-center">
                                            <span
                                                class="px-2.5 py-0.5 text-xs text-[10px] text-blue-800 bg-blue-100"
                                            >
                                                Rev. {{ header.revision }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 truncate max-w-[200px]">{{ header.remark || '-' }}</td>
                                    <td class="px-6 py-4 text-center">{{ header.details?.length || 0 }}</td>
                                    <td class="px-6 py-4 text-center font-medium">{{ formatDateTime(header.created_at) }}</td>
                                    <td class="px-6 py-4 text-center font-medium">{{ formatDateTime(header.updated_at) }}</td>
                                    <td class="px-6 py-4 text-center font-medium">{{ header.updater?.name }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <div class="text-xs text-slate-500">Showing {{ headers.from }} to {{ headers.to }} of {{ headers.total }}</div>
                        <div class="flex items-center gap-1">
                            <template v-for="(link, index) in headers.links" :key="index">
                                <Link v-if="link.url" :href="link.url" v-html="link.label" class="px-3 py-1.5 text-xs font-bold transition-all" :class="link.active ? 'bg-blue-600 text-white shadow-md' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'" />
                                <span v-else v-html="link.label" class="px-3 py-1.5 text-xs text-slate-400"></span>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Konfirmasi hapus -->
    <Transition
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
            <div class="bg-white w-full max-w-lg border border-slate-200 shadow-2xl p-6">
                <div class="flex flex-col text-left">
                    <h3 class="text-lg font-black text-slate-900 mb-1">
                        Confirm Deletion
                    </h3>
                    <p class="text-xs text-slate-500 mb-4">
                        Are you sure you want to delete
                        <span class="font-bold text-slate-900">
                            Items
                        </span>? This action cannot be undone.
                    </p>

                    <div class="mb-6">
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">
                            Reason for deletion <span class="text-bold text-rose-500">*</span>
                        </label>
                       <textarea
                            v-model="deleteForm.remark" 
                            rows="3"
                            class="w-full p-3 text-xs bg-slate-50 border focus:outline-none focus:ring-2 transition-all rounded-sm resize-none"
                            :class="deleteForm.errors.remark 
                                ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-500/20 text-rose-900' 
                                : 'border-slate-200 focus:border-blue-500 focus:ring-blue-500/20 text-slate-700'"
                            placeholder="Describe why this data is being deleted..."
                            @input="deleteForm.clearErrors('remark')"
                        ></textarea>

                        <p v-if="deleteForm.errors.remark" class="mt-1 text-[10px] font-bold text-rose-500">
                            {{ deleteForm.errors.remark }}
                        </p>
                    </div>

                    <div class="flex gap-3 w-full">
                        <button
                            @click="cancelConfirmAction"
                            class="flex-1 px-4 py-2 bg-slate-300 hover:bg-slate-400 text-slate-700 font-bold text-xs transition-all"
                        >
                            Cancel
                        </button>
                        <button
                            @click="confirmAction"
                            class="flex-1 px-4 py-2 bg-rose-600 hover:bg-rose-800 text-white font-bold text-xs transition-all"
                        >
                            Yes, Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Transition>

    <!-- History modal -->
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
                class="bg-white w-full max-w-9xl border border-slate-200 shadow-2xl flex flex-col max-h-[90vh] overflow-hidden"
            >
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 bg-white shrink-0">
                    <h3 class="text-sm font-black text-slate-900 uppercase tracking-wider flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5 text-blue-600">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        Process Function Revision History : {{ selectedName }}
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
                        <p class="text-[11px] text-slate-400 mt-0.5">This UoM category has no recorded changes.</p>
                    </div>

                    <div v-else class="relative border-l-2 border-slate-200 ml-3 space-y-8 pb-4">
                                <div v-for="(log, index) in historyLogs" :key="log.id" class="relative pl-6 animate-fade-in">
                                    <div
                                        :class="{
                                            'bg-emerald-500 border-emerald-100 ring-4 ring-emerald-50': log.event_name === 'create',
                                            'bg-blue-600 border-blue-100 ring-4 ring-blue-50': (log.event_name === 'update' || log.event_name === 'restore') && index === 0,
                                            'bg-purple-500 border-purple-100 ring-4 ring-purple-50': log.event_name === 'update' && index !== 0,
                                            'bg-rose-500 border-rose-100 ring-4 ring-rose-50': log.event_name === 'delete',
                                            'bg-orange-500 border-orange-100 ring-4 ring-orange-50': log.event_name === 'restore'
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
                                                    'bg-purple-50 text-purple-700 border-purple-200': log.event_name === 'update',
                                                    'bg-rose-50 text-rose-700 border-rose-200': log.event_name === 'delete',
                                                    'bg-orange-50 text-orange-700 border-orange-200': log.event_name === 'restore',
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

    <!-- modal filter -->
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95"
    >
        <div
            v-show="isFilterModalOpen"
            @click.self="closeFilterModal"
            class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 backdrop-blur-sm p-4"
        >
            <div class="bg-white w-full max-w-5xl border border-slate-200 shadow-2xl flex flex-col max-h-[90vh] overflow-hidden">
                <!-- Header modal -->
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 bg-white shrink-0">
                    <h3 class="text-sm font-black text-slate-900 uppercase tracking-wider flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5 text-blue-600">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        Create Custom Filter
                    </h3>
                </div>

                <!-- Body modal -->
                <div class="max-h-[65vh] overflow-y-auto flex-1 px-6 py-6 bg-slate-50/60 divide-y divide-slate-200/60">
                    <div class="w-full bg-white border border-gray-300 p-2 text-xs text-gray-800 font-sans shadow-sm">
                        <!-- Toolbar modal-->
                        <div class="flex gap-1.5 mb-2">
                            <button @click="addRow" class="px-3 py-1 bg-white border border-gray-300 hover:bg-gray-50 active:bg-gray-100 text-gray-700 font-medium rounded shadow-xs transition-colors">New</button>
                            <button @click="deleteRow" class="px-3 py-1 bg-white border border-gray-300 hover:bg-gray-50 active:bg-gray-100 text-gray-700 font-medium rounded shadow-xs transition-colors">Delete</button>
                            <button @click="deleteAllRows" class="px-3 py-1 bg-white border border-gray-300 hover:bg-gray-50 active:bg-gray-100 text-gray-700 font-medium rounded shadow-xs transition-colors">Delete All</button>
                            <button @click="insertRow" class="px-3 py-1 bg-white border border-gray-300 hover:bg-gray-50 active:bg-gray-100 text-gray-700 font-medium rounded shadow-xs transition-colors">Insert</button>
                            <button @click="copyRow" class="px-3 py-1 bg-white border border-gray-300 hover:bg-gray-50 active:bg-gray-100 text-gray-700 font-medium rounded shadow-xs transition-colors">Copy</button>
                        </div>

                        <!-- Table filter -->
                        <div class="border border-gray-300 bg-white overflow-x-auto rounded-xs">
                            <table class="w-full border-collapse text-left table-fixed">
                                <thead>
                                    <tr class="bg-gray-100 text-gray-600 select-none">
                                        <th class="w-12 border-r border-b border-gray-300 p-1.5 font-normal text-center">(</th>
                                        <th class="w-1/4 border-r border-b border-gray-300 p-1.5 font-normal">Field</th>
                                        <th class="w-24 border-r border-b border-gray-300 p-1.5 font-normal">Compare</th>
                                        <th class="w-1/3 border-r border-b border-gray-300 p-1.5 font-normal">Value</th>
                                        <th class="w-12 border-r border-b border-gray-300 p-1.5 font-normal text-center">)</th>
                                        <th class="w-28 border-b border-gray-300 p-1.5 font-normal">Logic</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr 
                                        v-for="(row, index) in conditions" 
                                        :key="index"
                                        :class="[
                                        selectedIndex === index ? 'bg-blue-50/70 border-b-blue-200' : 'hover:bg-gray-50/50'
                                        ]"
                                        @click="selectedIndex = index"
                                        class="transition-colors border-b border-gray-200"
                                    >
                                        <td class="p-0.5 border-r border-gray-200 relative flex items-center justify-center h-8">
                                        <span v-if="selectedIndex === index" class="absolute left-1 text-[8px] text-blue-600 select-none">▶</span>
                                        <input type="text" v-model="row.openParen" class="w-full h-full text-center bg-transparent border border-transparent focus:border-blue-500 focus:bg-white focus:outline-none rounded-xs p-1" />
                                        </td>
                                        
                                        <td class="p-0.5 border-r border-gray-200 h-8">
                                        <select v-model="row.field" class="w-full h-full bg-transparent border border-transparent focus:border-blue-500 focus:bg-white focus:outline-none rounded-xs p-0.5">
                                            <option value="">-- Select Field --</option>
                                            <option value="name">Process Function Name</option>
                                            <option value="revision">Revision</option>
                                            <option value="remark">Remark</option>
                                            <option value="created_by">Created By</option>
                                            <option value="updated_by">Updated By</option>
                                            <option value="created_at">Created On</option>
                                            <option value="updated_at">Updated On</option>
                                        </select>
                                        </td>
                                        
                                        <td class="p-0.5 border-r border-gray-200 h-8">
                                            <select v-model="row.compare" class="w-full h-full bg-transparent border border-transparent focus:border-blue-500 focus:bg-white focus:outline-none rounded-xs p-0.5">
                                                <option value="Equal to">Equal to</option>
                                                <option value="Not equal to">Not equal to</option>
                                                <option value="Greater than">Greater than</option>
                                                <option value="Greater than or equal to">Greater than or equal to</option>
                                                <option value="Less than">Less than</option>
                                                <option value="Less than or equal to">Less than or equal to</option>
                                                <option value="Null">Null</option>
                                                <option value="Not null">Not null</option>
                                                <option value="Like">Like</option>
                                                <option value="Include">Include</option>
                                                <option value="Exclude">Exclude</option>
                                                <option value="Left include">Left include</option>
                                                <option value="Right Include">Right Include</option>
                                                <option value="IN">IN</option>
                                            </select>
                                        </td>
                                        
                                        <td class="p-0.5 border-r border-gray-200 h-8">
                                        <input type="text" v-model="row.value" class="w-full h-full bg-transparent border border-transparent focus:border-blue-500 focus:bg-white focus:outline-none rounded-xs p-1" placeholder="Enter value..." />
                                        </td>
                                        
                                        <td class="p-0.5 border-r border-gray-200 h-8">
                                        <input type="text" v-model="row.closeParen" class="w-full h-full text-center bg-transparent border border-transparent focus:border-blue-500 focus:bg-white focus:outline-none rounded-xs p-1" />
                                        </td>
                                        
                                        <td class="p-0.5 h-8">
                                        <select v-model="row.logic" class="w-full h-full bg-transparent border border-transparent focus:border-blue-500 focus:bg-white focus:outline-none rounded-xs p-0.5">
                                            <option value="AND">AND</option>
                                            <option value="OR">OR</option>
                                        </select>
                                        </td>
                                    </tr>
                                    
                                    <tr v-if="conditions.length === 0">
                                        <td colspan="6" class="text-center p-6 text-gray-400 bg-gray-50/30 italic">
                                        Grid kosong. Klik <span class="font-semibold text-gray-600 not-italic">New</span> untuk menambahkan baris parameter filter PFMEA.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            
                        </div>
                    </div>
                    <!-- footer modal -->
                    <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                        <button 
                            @click="handleCancel" 
                            class="px-4 py-1.5 bg-white border border-gray-300 hover:bg-gray-100 text-gray-700 font-medium shadow-xs transition-colors"
                        >
                            Cancel
                        </button>
                        <button 
                            @click="handleApply" 
                            class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-medium shadow-xs transition-colors"
                        >
                            Apply Filter
                        </button>
                    </div>
                </div>

                
            </div>
        </div>
    </Transition>
</template>