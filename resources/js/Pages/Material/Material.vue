<script setup>
import "vue3-toastify/dist/index.css";
import { ref, watch, computed, isRef } from "vue";
import { Head, router, Link, usePage, useForm } from "@inertiajs/vue3";
import { toast } from "vue3-toastify";
import { debounce } from "lodash";
import axios from "axios";
import dayjs from "dayjs";
import "dayjs/locale/id";
import AuthenticatedLayout from "../../Layouts/AuthenticatedLayout.vue";

defineOptions({ layout: AuthenticatedLayout });

// formating date
dayjs.locale("id");
const formatLogDate = (date) => {
    if(!date) return "-";
    return dayjs(date).format("DD MMM YYYY, HH:mm:ss [WIB]");
}

const formatTableDate = (date) => {
    if(!date) return "-";
    return dayjs(date).format("DD-MMM-YYYY HH:mm:ss");
}

const page = usePage();
const searchQuery = ref("");
const selectedIds = ref([]);
const isRefreshing = ref(false);
const showConfirmModal = ref(false);
const selectedFilter = ref("all");
const isSearching = ref(false);
const deleteReason = ref("");
const isHistoryModalOpen = ref(false);
const historyLogs = ref([]);
const isLoadingHistory = ref(false);
const selectedName = ref("");
const isDrawerOpen = ref(false);
const selectedMaterial = ref(null);
const isEditMode = computed(() => selectedMaterial.value !== null);

// Delete form dalam modal
const deleteForm =useForm({
    ids: [],
    remark: ""
});


// Data dari BE
const props = defineProps({
    materials:{
        type: Object,
        default: () => ({ data:[], links:[], per_page: 10}),
    },
    units: Array,
});

// Form
const form = useForm({
    category: "",
    code: "",
    name: "",
    specification: "",
    customer_part_name: "",
    unit_id: "",
    grade: "",
    density: 0,
    melt_flow_index: 0,
    color: "",
    drawing_change: "",
    shrinkage_rate: "",
    gross_weight: 0,
    net_weight: 0,
    sprue_weight: 0,
    has_rohs: false,
    imds_number: "",
    msds_doc_path: "",
    risk_profile: "low",
    is_active: true,
    remark: "",
    reason: ""
});

// Setup per page
const perPage = ref(props.materials?.per_page || 10);
// Setup form processing
const isProcessing = computed(() => form.processing || false);
// Setup error
const errors = computed(() => page.props.errors || {});

// Refreshing table
const refreshTable = () => {
    isRefreshing.value = true;
    router.reload({
        only:["materials"],
        onSuccess: () => {
            isRefreshing.value = false;
        },
        onError: () => {
            isRefreshing.value = false;
            toast.error("Failed to refresh data");
        }
    })
}

// Event untuk pengecekan error
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

// Event untuk Select All
const toggleSelectAll = (event) => {
    selectedIds.value = event.target.checked
        ? props.materials.data.map((c) => c.id)
        : [];
}

// Open Create Drawer
const openCreateDrawer = () => {
    selectedMaterial.value = null;
    form.reset();
    form.clearErrors();
    isDrawerOpen.value = true;
};

// Open edit drawer
const openEditDrawer = (material) => {
    selectedMaterial.value = material;
    form.clearErrors();

    form.id = material.id;
    form.category = material.category ?? "";
    form.code = material.code ?? "";
    form.name = material.name ?? "";
    form.specification = material.specification ?? "";
    form.customer_part_name = material.customer_part_name ?? "";
    form.unit_id = material.unit_id ?? "";
    form.grade = material.grade ?? "";
    form.density = material.density ?? "";
    form.melt_flow_index = material.melt_flow_index ?? "";
    form.color = material.color ?? "";
    form.drawing_change = material.drawing_change ?? "";
    form.shrinkage_rate = material.shrinkage_rate ?? "";
    form.gross_weight = material.gross_weight ?? "";
    form.net_weight = material.net_weight ?? "";
    form.sprue_weight = material.sprue_weight ?? "";
    form.has_rohs = material.has_rohs ?? false;
    form.imds_number = material.imds_number ?? "";
    form.msds_doc_path = material.msds_doc_path ?? "";
    form.risk_profile = material.risk_profile ?? "";
    form.is_active = material.is_active ?? true;
    form.remark = material.remark ?? "";

    isDrawerOpen.value = true;
}

// Proses simpan atau update
const submitForm = () => {
    form.clearErrors();
    let isValid = true;

    if(!form.code){
        form.setError('code', 'This field is required');
        isValid = false;
    }else if(form.code.length > 50){
        form.setError('code', 'Project code cannot exceed 50 character');
        isValid = false;
    }

    if(!form.name){
        form.setError('name', 'This field is required');
        isValid = false;
    }else if(form.name.length > 150){
        form.setError('name', 'Project name cannot exceed 150 character');
        isValid = false;
    }

    if(!form.specification){
        form.setError('specification', 'This field is required')
        isValid = false;
    }

    if(!form.unit_id){
        form.setError('unit_id', 'This field is required')
        isValid = false;
    }

    if(!form.category){
        form.setError('category', 'This field is required')
        isValid = false;
    }

    if(isEditMode.value){
        if(!form.reason){
            form.setError('reason', 'Please fill the changed reason');
            isValid = false;
        }
    }

    if(!isValid){
        return;
    }


    form.transform((data) => {
        const payload = { ...data };

        if(isEditMode.value){
            payload._method = "put";
        }else{
            delete payload._method;
        }

        return payload;
    }).post(isEditMode.value ? `/materials/${form.id}` : '/materials', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        }
    });
}

// Delete selected
const deleteSelected = () => {
    if(selectedIds.value.length > 0) showConfirmModal.value = true;
}

// Konfirmasi hapus
const confirmAction = () => {
    deleteForm.clearErrors();

    if (!deleteForm.remark || deleteForm.remark.trim() === "") {
        deleteForm.setError('remark', 'This field is required');
        return;
    }

    deleteForm.ids = selectedIds.value;

    deleteForm.post("/materials/mass-delete", {
        preserveScroll: true,
        onSuccess: () => {
            selectedIds.value = [];
            showConfirmModal.value = false;
            deleteForm.reset();
            toast.success("Selected items deleted successfully");
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0];
            toast.error(firstError || "Failed to delete items");
        }
    });
};

// Batalin penghapusan
const cancelConfirmAction = () => {
    deleteForm.clearErrors();
    selectedIds.value = [];
    showConfirmModal.value = false;
    deleteForm.reset();
}

// Update jumlah data per halaman
const updatePerPage = () => {
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
    )
}

// Query pencarian
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
        );
    }, 300),
);

// proses histori change logs revisi
const openHistoryModal = async(id, name) => {
    isHistoryModalOpen.value = true;
    isLoadingHistory.value = true;
    selectedName.value = name;
    historyLogs.value = [];

    try{
        const response = await axios.get(`/materials/${id}/logs`);
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
    if (!text) return '-';
    return text.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
};

// Dropdown uom
const isUnitDropdownOpen = ref(false);
const unitSearch = ref("");

// Filter data uom berdasarkan inputan search
const filteredUnits = computed(() => {
    if(!unitSearch.value) return props.units;
    const lowerSearch = unitSearch.value.toLowerCase();
    return props.units.filter(c => c.name.toLowerCase().includes(lowerSearch));
});

// Nampilin UoM pada kotak utama
const selectedUnitName = computed(() => {
    if(!form.unit_id) return "Select UoM";

    const uom = props.units.find(c => c.id === form.unit_id);
    return uom ? `${uom.symbol} - ${uom.name}` : "Select UoM";
});

// Fungsi ketika data yang dibutuhkan di klik
const selectUnit = (id) => {
    form.unit_id = id;
    isUnitDropdownOpen.value = false;
    unitSearch.value = "";
}

// Benerin isi table kalo isidatanya kosong atau null
const formStatus = (status) => {
    if (!status) return "-";

    const statusMap = {
        'raw_material': 'Raw Material',
        'purchased_parts': 'Purchased Parts',
        'chemical_additive': 'Chemical Additive',
        'tooling_consumable': 'Tooling Consumable',
        'packaging': 'Packaging',
        'sfg': 'Semi Finished Goods',
        'fg': 'Finished Goods'
    };

    return statusMap[status] || status;
}


// Dropdown Category ala Select2
const isCategoryDropdownOpen = ref(false);
const categorySearch = ref("");

const materialCategories = [
    { value: 'raw_material', label: 'Raw Material' },
    { value: 'purchased_parts', label: 'Purchased Parts' },
    { value: 'chemical_additive', label: 'Chemical Additive' },
    { value: 'tooling_consumable', label: 'Tooling & Consumable' },
    { value: 'packaging', label: 'Packaging' },
    { value: 'sfg', label: 'Semi-Finished Goods (SFG)' },
    { value: 'fg', label: 'Finished Goods (FG)' },
];

// Menampilkan label terpilih di kotak utama
const selectedCategoryName = computed(() => {
    if(!form.category) return "Select Category";
    const match = materialCategories.find(c => c.value === form.category);
    return match ? match.label : "Select Category";
});

// Memfilter isi list berdasarkan inputan pencarian
const filteredCategories = computed(() => {
    if(!categorySearch.value) return materialCategories;
    const lowerSearch = categorySearch.value.toLowerCase();
    return materialCategories.filter(c => 
        c.label.toLowerCase().includes(lowerSearch) || 
        c.value.toLowerCase().includes(lowerSearch)
    );
});

// Aksi ketika item dipilih
const selectCategory = (value) => {
    form.category = value;
    isCategoryDropdownOpen.value = false;
    categorySearch.value = "";
}

// Hitung berat sprue
const sprueCalculate = () => {
    if (form.gross_weight && form.net_weight) {
        const gross = parseFloat(form.gross_weight);
        const net = parseFloat(form.net_weight);

        if (gross >= net) {
            form.sprue_weight = (gross - net).toFixed(4);
        } else {
            form.sprue_weight = (0).toFixed(4);
        }
    }
}

const ucwords = (str) => {
    if (!str) return '';
    return str.toLowerCase().replace(/\b\w/g, char => char.toUpperCase());
}
</script>

<template>
    <Head title="Material Management"/>
    <div class="flex min-h-screen bg-slate-50 font-sans antialiased text-slate-800">
        <div class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">
                        Material Management
                    </h1>
                    <p class="text-xs text-slate-500 mt-1">
                        Manage your material.
                    </p>
                </div>
                
                <div class="sticky top-0 z-30 bg-white/95 backdrop-blur-sm border border-slate-200 px-3 py-2 shadow-sm">
                    <div class="flex items-center justify-between w-full">
                        <div class="flex items-center gap-1.5">
                            <button
                                @click="openCreateDrawer"
                                type="button"
                                class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-bold text-blue-600 bg-blue-50 border border-blue-100 transition-all hover:bg-blue-100 active:scale-95 shadow-sm"
                            >
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
                            </button>

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

                            <div v-if="selectedIds.length > 0" class="w-px h-4 bg-slate-200 mx-1"></div>

                            <button
                                @click="deleteSelected"
                                :disabled="selectedIds.length === 0"
                                type="button"
                                class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-bold text-rose-600 bg-rose-50 border border-rose-100 transition-all hover:bg-rose-100 active:scale-95 shadow-sm disabled:opacity-40 disabled:active:scale-100"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-4 w-4"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                    />
                                </svg>
                                <span>Delete ({{ selectedIds.length }})</span>
                            </button>
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

                <div class="bg-white border border-slate-200/80 shadow-sm p-4 mb-6 flex flex-col gap-4">
                    <div class="overflow-auto max-h-[calc(100vh-320px)]">
                        <table class="w-full text-left border-collapse bg-white whitespace-nowrap">
                            <thead class="bg-blue-300 text-slate-700 uppercase tracking-wider text-[11px] font-bold border-b border-slate-200 sticky top-0 z-10">
                                <tr>
                                    <th class="px-4 py-3 w-12 text-center">
                                        <input
                                            type="checkbox"
                                            @change="toggleSelectAll"
                                            class="border-slate-300 text-blue-600 h-4 w-4 cursor-pointer"
                                        />
                                    </th>
                                    <th class="px-4 py-3">Revision</th>
                                    <th class="px-4 py-3">Category</th>
                                    <th class="px-4 py-3">Part Number</th>
                                    <th class="px-4 py-3">Part Name</th>
                                    <th class="px-4 py-3">Specification</th>
                                    <th class="px-4 py-3">Customer Part Name</th>
                                    <th class="px-4 py-3">Uom</th>
                                    <th class="px-4 py-3">Grade</th>
                                    <th class="px-4 py-3">Density</th>
                                    <th class="px-4 py-3">Melt Flow Index</th>
                                    <th class="px-4 py-3">Color</th>
                                    <th class="px-4 py-3">Drawing Change</th>
                                    <th class="px-4 py-3">Shrinkage Rate</th>
                                    <th class="px-4 py-3">Gross Weight</th>
                                    <th class="px-4 py-3">Net Weight</th>
                                    <th class="px-4 py-3">Sprue Weight</th>
                                    <th class="px-4 py-3">RoHs</th>
                                    <th class="px-4 py-3">IMDS Number</th>
                                    <th class="px-4 py-3">Risk Profile</th>
                                    <th class="px-4 py-3">Remark</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-xs">
                                <tr 
                                    v-for="(material, index) in props.materials.data" 
                                    :key="material.id"
                                    :class="[
                                        selectedIds.includes(material.id) ? 'bg-blue-50/60 font-semibold' : 'hover:bg-slate-50/80',
                                        'transition-colors duration-150'
                                    ]"
                                >
                                    <td class="px-4 py-3 text-center">
                                        <input
                                            type="checkbox"
                                            v-model="selectedIds"
                                            :value="material.id"
                                            class="border-slate-300 text-blue-600 h-4 w-4 cursor-pointer"
                                        />
                                    </td>
                                    <td class="px-4 py-3 font-semibold text-slate-500">Rev. {{ material.revision }}</td>
                                    <td class="px-4 py-3 font-bold text-slate-600">
                                        {{ formStatus(material.category) }}
                                    </td>
                                    <td class="px-4 py-3 font-black text-slate-900 tracking-tight">{{ material.code }}</td>
                                    <td class="px-4 py-3 font-medium text-slate-700 truncate max-w-xs" :title="material.name">{{ material.name }}</td>
                                    <td class="px-4 py-3 text-slate-600 truncate max-w-xs" :title="material.specification">{{ material.specification ?? '-' }}</td>
                                    <td class="px-4 py-3 text-slate-600 truncate max-w-xs" :title="material.customer_part_name">{{ material.customer_part_name ?? '-' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="font-bold bg-slate-100 text-slate-700 px-1.5 py-0.5 border border-slate-200">
                                            {{ material.unit_id ? material.units.symbol : '-' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">{{ material.grade ?? '-' }}</td>
                                    <td class="px-4 py-3 font-mono text-slate-600">{{ material.density ?? '-' }}</td>
                                    <td class="px-4 py-3 font-mono text-slate-600">{{ material.melt_flow_index ?? '-' }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ material.color ?? '-' }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ material.drawing_change ?? '-' }}</td>
                                    <td class="px-4 py-3 font-mono text-slate-600">{{ material.shrinkage_rate ?? '-' }}</td>
                                    <td class="px-4 py-3 font-mono font-bold text-slate-700">{{ material.gross_weight ?? '-' }}</td>
                                    <td class="px-4 py-3 font-mono font-bold text-slate-700">{{ material.net_weight ?? '-' }}</td>
                                    <td class="px-4 py-3 font-mono font-bold text-slate-700">{{ material.sprue_weight ?? '-' }}</td>
                                    <td class="px-4 py-3">
                                        <span :class="material.has_rohs ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'" class="px-2 py-0.5 font-bold border">
                                            {{ material.has_rohs ? 'RoHS Comp.' : 'Non-RoHS' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 font-mono text-slate-600">{{ material.imds_number ?? '-' }}</td>
                                    <td class="px-4 py-3">
                                        <span 
                                            :class="[
                                                material.risk_profile === 'high' ? 'bg-rose-100 text-rose-800' :
                                                material.risk_profile === 'medium' ? 'bg-amber-100 text-amber-800' :
                                                'bg-emerald-100 text-emerald-800'
                                            ]"
                                            class="px-2 py-0.5 font-black uppercase text-[10px]"
                                        >
                                            {{ material.risk_profile ?? 'low' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-400 italic truncate max-w-xs" :title="material.remark">{{ material.remark ?? '-' }}</td>
                                    <td class="px-4 py-3">
                                        <span :class="material.is_active ? 'text-emerald-600 bg-emerald-50' : 'text-slate-400 bg-slate-100'" class="px-2 py-1 font-bold text-[10px] tracking-wider uppercase">
                                            {{ material.is_active ? 'Enabled' : 'Disabled' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <button 
                                                @click="openEditDrawer(material)"
                                                type="button" 
                                                class="text-blue-600 hover:text-blue-800 font-bold hover:underline"
                                            >
                                                Edit
                                            </button>
                                            <span class="text-slate-300">|</span>
                                            <button 
                                                @click="openHistoryModal(material.id, material.name)"
                                                type="button" 
                                                class="text-slate-500 hover:text-slate-800 font-bold hover:underline"
                                            >
                                                Logs
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="props.materials.data.length === 0">
                                    <td colspan="23" class="px-4 py-12 text-center text-slate-400 italic">
                                        No materials data found matching your query...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div
                        v-if="materials.total > 0"
                        class="px-6 py-4 bg-white border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4"
                    >
                        <div
                            class="text-xs font-bold text-slate-500 uppercase tracking-wider"
                        >
                            Showing {{ materials.from ?? 0 }} to
                            {{ materials.to ?? 0 }} of
                            {{ materials.total ?? 0 }}
                        </div>

                        <div class="flex items-center gap-1">
                            <template
                                v-for="(link, index) in materials.links"
                                :key="index"
                            >
                                <Link
                                    v-if="index === 0 || index === materials.links.length - 1"
                                    :href="link.url ?? '#'"
                                    v-html="link.label"
                                    preserve-scroll
                                    :as="!link.url ? 'span' : 'a'"
                                    class="px-2 py-1 text-xs font-bold text-slate-500 transition"
                                    :class="[
                                        !link.url 
                                            ? 'opacity-30 cursor-not-allowed' 
                                            : 'hover:text-slate-900 cursor-pointer'
                                    ]"
                                />

                                <Link
                                    v-else
                                    :href="link.url ?? '#'"
                                    v-html="link.label"
                                    preserve-scroll
                                    :as="!link.url ? 'span' : 'a'"
                                    class="px-3 py-1.5 text-xs font-bold border transition-all"
                                    :class="
                                        link.active
                                            ? 'bg-blue-600 text-white border-blue-600'
                                            : !link.url
                                                ? 'bg-white text-slate-400 border-slate-200 cursor-not-allowed'
                                                : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-100 cursor-pointer'
                                    "
                                />
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div
        class="fixed inset-0 z-40 overflow-hidden transition-all duration-300"
        :class="isDrawerOpen ? 'visible opacity-100' : 'invisible opacity-0 delay-300'"
        role="dialog"
        aria-modal="true"
    >
        <div class="absolute inset-0 overflow-hidden">
            
            <Transition
                enter-active-class="transition-opacity ease-in-out duration-300"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave-active-class="transition-opacity ease-in-out duration-300"
                leave-from="opacity-100"
                leave-to="opacity-0"
            >
                <div v-if="isDrawerOpen" @click="isDrawerOpen = false" class="absolute inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity"></div>
            </Transition>

            <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                <Transition
                    enter-active-class="transform transition ease-in-out duration-300"
                    enter-from="translate-x-full"
                    enter-to="translate-x-0"
                    leave-active-class="transform transition ease-in-out duration-300"
                    leave-from="translate-x-0"
                    leave-to="translate-x-full"
                >
                    <div v-if="isDrawerOpen" class="pointer-events-auto w-screen max-w-2xl bg-white shadow-2xl flex flex-col h-full border-l border-slate-200">
                        <div class="px-6 py-5 bg-slate-50 border-b border-slate-200 flex items-center justify-between shrink-0">
                            <div>
                                <h2 class="text-base font-black text-slate-900 tracking-tight">
                                    {{ isEditMode ? 'Modify Material Data' : 'Add New Material Master' }}
                                </h2>
                                <p class="text-[11px] text-slate-500 mt-0.5">
                                    {{ isEditMode ? 'Update data fields below for existing records.' : 'Fill up information setup to generate new item.' }}
                                </p>
                            </div>
                            <button @click="isDrawerOpen = false" type="button" class="text-slate-400 hover:text-slate-600 transition p-1 bg-white border border-slate-200 rounded-sm active:scale-95 shadow-sm">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <form @submit.prevent="submitForm" class="flex-1 overflow-y-auto p-6 space-y-5 text-xs text-slate-800">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-500 mb-1">Part Number / Code <span class="text-rose-500">*</span></label>
                                    <input 
                                        type="text" 
                                        v-model="form.code" 
                                        maxlength="50" 
                                        placeholder="e.g. PART-XYZ-01" 
                                        :disabled="isEditMode"
                                        :class="[form.errors.code ? 'border-rose-500 focus:border-rose-500 bg-rose-50/20 text-rose-600' : 'border-slate-200 focus:border-blue-500 bg-slate-50/30 text-slate-800', isEditMode ? 'bg-slate-100 text-slate-400 cursor-not-allowed' : '']"
                                        class="w-full px-3 py-2 border text-xs focus:outline-none font-semibold transition"
                                    />
                                    <p v-if="form.errors.code" class="mt-1 text-[10px] font-bold text-rose-500">{{ form.errors.code }}</p>
                                </div>

                                <div>
                                    <label class="block text-[11px] font-bold text-slate-500 mb-1">Part Name / Item Name <span class="text-rose-500">*</span></label>
                                    <input 
                                        type="text" 
                                        v-model="form.name" 
                                        maxlength="150" 
                                        placeholder="Enter item identifier name..." 
                                        :class="[form.errors.name ? 'border-rose-500 focus:border-rose-500 text-rose-600' : 'border-slate-200 focus:border-blue-500 text-slate-800']"
                                        class="w-full px-3 py-2 border text-xs focus:outline-none font-semibold transition"
                                    />
                                    <p v-if="form.errors.name" class="mt-1 text-[10px] font-bold text-rose-500">{{ form.errors.name }}</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-500 mb-1">Material Category <span class="text-rose-500">*</span></label>
                                    <div class="relative">
                                        <div v-if="isCategoryDropdownOpen" @click="isCategoryDropdownOpen = false" class="fixed inset-0 z-0"></div>
                                        <div 
                                            @click="isCategoryDropdownOpen = !isCategoryDropdownOpen"
                                            :class="[form.errors.category ? 'border-rose-500 text-rose-600 bg-rose-50/20' : 'border-slate-200 text-slate-800']"
                                            class="relative z-10 w-full px-3 py-2.5 border text-xs bg-white cursor-pointer flex justify-between items-center font-semibold transition"
                                        >
                                            <span :class="form.category ? 'text-slate-800' : 'text-slate-400'">{{ selectedCategoryName }}</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400 transition-transform" :class="{'rotate-180 text-blue-500': isCategoryDropdownOpen}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </div>
                                        <p v-if="form.errors.category" class="mt-1 text-[10px] font-bold text-rose-500">{{ form.errors.category }}</p>

                                        <Transition enter-active-class="transition duration-100 ease-out" enter-from-class="transform scale-95 opacity-0" enter-to-class="transform scale-100 opacity-100" leave-active-class="transition duration-75 ease-out" leave-from-class="transform scale-100 opacity-100" leave-to-class="transform scale-95 opacity-0">
                                            <div v-if="isCategoryDropdownOpen" class="absoluteSub z-30 w-full mt-1 bg-white border border-slate-200 shadow-xl overflow-hidden rounded-sm">
                                                <div class="p-2 border-b border-slate-100 bg-slate-50 sticky top-0">
                                                    <input type="text" v-model="categorySearch" @click.stop class="w-full px-2 py-1.5 border border-slate-200 text-xs rounded-xs focus:outline-none focus:border-blue-500 bg-white" placeholder="Search category..."/>
                                                </div>
                                                <div class="max-h-40 overflow-y-auto">
                                                    <div v-for="cat in filteredCategories" :key="cat.value" @click="selectCategory(cat.value)" class="px-3 py-2 text-xs cursor-pointer border-b border-slate-50 text-slate-700 hover:bg-blue-50 hover:text-blue-700 font-semibold last:border-0" :class="[form.category === cat.value ? 'bg-blue-50/50 text-blue-600 font-bold border-l-2 border-blue-600' : '']">
                                                        {{ cat.label }}
                                                    </div>
                                                    <div v-if="filteredCategories.length === 0" class="px-3 py-4 text-xs text-center text-slate-400 italic bg-slate-50">No category matches...</div>
                                                </div>
                                            </div>
                                        </Transition>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[11px] font-bold text-slate-500 mb-1">Unit of Measurement (UoM) <span class="text-rose-500">*</span></label>
                                    <div class="relative">
                                        <div v-if="isUnitDropdownOpen" @click="isUnitDropdownOpen = false" class="fixed inset-0 z-0"></div>
                                        <div 
                                            @click="isUnitDropdownOpen = !isUnitDropdownOpen"
                                            :class="[form.errors.unit_id ? 'border-rose-500 text-rose-600 bg-rose-50/20' : 'border-slate-200 text-slate-800']"
                                            class="relative z-10 w-full px-3 py-2.5 border text-xs bg-white cursor-pointer flex justify-between items-center font-semibold transition"
                                        >
                                            <span :class="form.unit_id ? 'text-slate-800' : 'text-slate-400'">{{ selectedUnitName }}</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400 transition-transform" :class="{'rotate-180 text-blue-500': isUnitDropdownOpen}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </div>
                                        <p v-if="form.errors.unit_id" class="mt-1 text-[10px] font-bold text-rose-500">{{ form.errors.unit_id }}</p>

                                        <Transition enter-active-class="transition duration-100 ease-out" enter-from-class="transform scale-95 opacity-0" enter-to-class="transform scale-100 opacity-100" leave-active-class="transition duration-75 ease-out" leave-from-class="transform scale-100 opacity-100" leave-to-class="transform scale-95 opacity-0">
                                            <div v-if="isUnitDropdownOpen" class="absoluteSub z-30 w-full mt-1 bg-white border border-slate-200 shadow-xl overflow-hidden rounded-sm">
                                                <div class="p-2 border-b border-slate-100 bg-slate-50 sticky top-0">
                                                    <input type="text" v-model="unitSearch" @click.stop class="w-full px-2 py-1.5 border border-slate-200 text-xs rounded-xs focus:outline-none focus:border-blue-500 bg-white" placeholder="Search unit..."/>
                                                </div>
                                                <div class="max-h-40 overflow-y-auto">
                                                    <div v-for="unit in filteredUnits" :key="unit.id" @click="selectUnit(unit.id)" class="px-3 py-2 text-xs cursor-pointer border-b border-slate-50 text-slate-700 hover:bg-blue-50 hover:text-blue-700 font-semibold last:border-0" :class="[form.unit_id === unit.id ? 'bg-blue-50/50 text-blue-600 font-bold border-l-2 border-blue-600' : '']">
                                                        {{ unit.symbol }} - {{ unit.name }}
                                                    </div>
                                                    <div v-if="filteredUnits.length === 0" class="px-3 py-4 text-xs text-center text-slate-400 italic bg-slate-50">No unit symbols match...</div>
                                                </div>
                                            </div>
                                        </Transition>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-500 mb-1">Part Specification <span class="text-rose-500">*</span></label>
                                    <input type="text" v-model="form.specification" placeholder="e.g. Steel Plate 2.0mm" :class="[form.errors.specification ? 'border-rose-500 focus:border-rose-500 text-rose-600' : 'border-slate-200 focus:border-blue-500 text-slate-800']" class="w-full px-3 py-2 border text-xs focus:outline-none font-semibold transition" />
                                    <p v-if="form.errors.specification" class="mt-1 text-[10px] font-bold text-rose-500">{{ form.errors.specification }}</p>
                                </div>

                                <div>
                                    <label class="block text-[11px] font-bold text-slate-500 mb-1">Customer Part Name</label>
                                    <input type="text" v-model="form.customer_part_name" placeholder="Internal customer identity name" class="w-full px-3 py-2 border border-slate-200 text-xs focus:outline-none font-semibold focus:border-blue-500 text-slate-800 transition" />
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-500 mb-1">Material Grade</label>
                                    <input type="text" v-model="form.grade" placeholder="e.g. SUS304 / Grade A" class="w-full px-3 py-2 border border-slate-200 text-xs focus:outline-none font-semibold focus:border-blue-500 text-slate-800 transition" />
                                </div>

                                <div>
                                    <label class="block text-[11px] font-bold text-slate-500 mb-1">Density (g/cm³)</label>
                                    <input type="number" step="0.0001" v-model="form.density" placeholder="0.0000" class="w-full px-3 py-2 border border-slate-200 text-xs focus:outline-none font-mono font-semibold focus:border-blue-500 text-slate-800 transition" />
                                </div>

                                <div>
                                    <label class="block text-[11px] font-bold text-slate-500 mb-1">Melt Flow Index (g/10min)</label>
                                    <input type="number" step="0.01" v-model="form.melt_flow_index" placeholder="0.00" class="w-full px-3 py-2 border border-slate-200 text-xs focus:outline-none font-mono font-semibold focus:border-blue-500 text-slate-800 transition" />
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-500 mb-1">Color Spec</label>
                                    <input type="text" v-model="form.color" placeholder="e.g. Black Matte / Natural" class="w-full px-3 py-2 border border-slate-200 text-xs focus:outline-none font-semibold focus:border-blue-500 text-slate-800 transition" />
                                </div>

                                <div>
                                    <label class="block text-[11px] font-bold text-slate-500 mb-1">Drawing Change Level</label>
                                    <input type="text" v-model="form.drawing_change" placeholder="e.g. Rev. B-1" class="w-full px-3 py-2 border border-slate-200 text-xs focus:outline-none font-semibold focus:border-blue-500 text-slate-800 transition" />
                                </div>

                                <div>
                                    <label class="block text-[11px] font-bold text-slate-500 mb-1">Shrinkage Rate (%)</label>
                                    <input type="text" v-model="form.shrinkage_rate" placeholder="e.g. 1.5% - 2.0%" class="w-full px-3 py-2 border border-slate-200 text-xs focus:outline-none font-mono font-semibold focus:border-blue-500 text-slate-800 transition" />
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-4 bg-slate-50 p-3 border border-slate-200/60 rounded-xs">
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Gross Weight (kg)</label>
                                    <input type="number" step="0.0001" v-model="form.gross_weight" @input="sprueCalculate" placeholder="0.0000" class="w-full px-3 py-2 border border-slate-200 bg-white text-xs focus:outline-none font-mono font-bold focus:border-blue-500 text-slate-800 transition" />
                                </div>

                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Net Weight (kg)</label>
                                    <input type="number" step="0.0001" v-model="form.net_weight" @input="sprueCalculate" placeholder="0.0000" class="w-full px-3 py-2 border border-slate-200 bg-white text-xs focus:outline-none font-mono font-bold focus:border-blue-500 text-slate-800 transition" />
                                </div>

                                <div>
                                    <label class="block text-[11px] font-bold text-slate-400 mb-1">Sprue Weight (Auto-Calc)</label>
                                    <input type="number" step="0.0001" v-model="form.sprue_weight" placeholder="0.0000" readonly class="w-full px-3 py-2 border border-slate-200 bg-slate-100 font-mono font-bold text-slate-500 outline-none cursor-not-allowed text-xs" />
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-500 mb-1">IMDS System Number</label>
                                    <input type="text" v-model="form.imds_number" placeholder="Enter IMDS registration id..." class="w-full px-3 py-2 border border-slate-200 text-xs focus:outline-none font-mono font-semibold focus:border-blue-500 text-slate-800 transition" />
                                </div>

                                <div>
                                    <label class="block text-[11px] font-bold text-slate-500 mb-1">Risk Profile Category</label>
                                    <select v-model="form.risk_profile" class="w-full px-3 py-2 border border-slate-200 text-xs font-bold bg-white focus:outline-none focus:border-blue-500 text-slate-800 cursor-pointer transition">
                                        <option value="low">Low Risk Profile</option>
                                        <option value="medium">Medium Risk Profile</option>
                                        <option value="high">High Risk Profile</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4 items-center pt-2">
                                <div class="flex items-center justify-between p-2.5 bg-white border border-slate-200">
                                    <div class="flex flex-col min-w-0 pr-4">
                                        <span class="text-[10px] font-black text-slate-700 uppercase tracking-wider">RoHS Compliance</span>
                                        <span class="text-[9px] font-semibold text-slate-400 mt-0.5">{{ form.has_rohs ? "Material fulfills RoHS requirement directives." : "Does not align with RoHS standard compliance." }}</span>
                                    </div>
                                    <button type="button" @click="form.has_rohs = !form.has_rohs" :class="form.has_rohs ? 'bg-emerald-600' : 'bg-slate-300'" class="relative inline-flex h-5 w-10 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out active:scale-95"><span :class="form.has_rohs ? 'translate-x-5' : 'translate-x-0'" class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition duration-200"></span></button>
                                </div>

                                <div class="flex items-center justify-between p-2.5 bg-white border border-slate-200">
                                    <div class="flex flex-col min-w-0 pr-4">
                                        <span class="text-[10px] font-black text-slate-700 uppercase tracking-wider">Master Data State</span>
                                        <span class="text-[9px] font-semibold text-slate-400 mt-0.5">{{ form.is_active ? "Material visible globally across systems." : "Master entry locked and flagged inactive." }}</span>
                                    </div>
                                    <button type="button" @click="form.is_active = !form.is_active" :class="form.is_active ? 'bg-blue-600' : 'bg-slate-300'" class="relative inline-flex h-5 w-10 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out active:scale-95"><span :class="form.is_active ? 'translate-x-5' : 'translate-x-0'" class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition duration-200"></span></button>
                                </div>
                            </div>

                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 mb-1">Remark Notes (optional)</label>
                                <textarea v-model="form.remark" rows="3" placeholder="Write additional technical or reference material records here..." class="w-full px-3 py-2 border border-slate-200 text-xs focus:outline-none font-semibold focus:border-blue-500 text-slate-800 transition"></textarea>
                            </div>

                            <div v-if="isEditMode" class="p-3 bg-amber-50/60 border border-amber-200/70">
                                <label class="block text-[11px] font-bold text-amber-800 mb-1">Reason for Change Modification <span class="text-rose-500">*</span></label>
                                <textarea v-model="form.reason" rows="2" placeholder="Mandatory! Describe technical reasons or engineering change context details for adjusting this master entity..." :class="[form.errors.reason ? 'border-rose-400 focus:border-rose-500 text-rose-700 bg-white' : 'border-amber-200 focus:border-amber-500 text-slate-800 bg-white']" class="w-full px-3 py-1.5 border text-xs focus:outline-none font-semibold transition"></textarea>
                                <p v-if="form.errors.reason" class="mt-1 text-[10px] font-bold text-rose-500">{{ form.errors.reason }}</p>
                            </div>
                        </form>

                        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-2 shrink-0">
                            <button @click="isDrawerOpen = false" type="button" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 font-bold hover:bg-slate-50 transition active:scale-95 shadow-sm">Cancel</button>
                            <button @click="submitForm" type="button" :disabled="isProcessing" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold shadow-sm transition active:scale-95 disabled:opacity-60 inline-flex items-center gap-1.5">
                                <svg v-if="isProcessing" class="animate-spin h-3.5 w-3.5 text-white" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span>{{ isEditMode ? 'Update Master' : 'Save Entry' }}</span>
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </div>
    </div>

    <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="showConfirmModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
            <div @click.stop class="bg-white max-w-md w-full shadow-2xl border border-slate-100 flex flex-col rounded-sm overflow-hidden">
                <div class="p-5 flex items-start gap-4">
                    <div class="p-2 bg-rose-50 text-rose-600 border border-rose-100 shrink-0">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-sm font-black text-slate-900 tracking-tight uppercase">Confirm Material Destruction</h3>
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                            Are you absolutely sure to delete the <span class="font-bold text-rose-600 bg-rose-50 px-1">{{ selectedIds.length }} selected material</span> master items? This process cannot be reverted.
                        </p>
                        
                        <div class="mt-4">
                            <label class="block text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-1">Reason for Removal <span class="text-rose-500">*</span></label>
                            <textarea v-model="deleteForm.remark" rows="2" placeholder="Write engineering justification or reason for clearing out entries..." :class="[deleteForm.errors.remark ? 'border-rose-400 focus:border-rose-500 text-rose-700 bg-rose-50/10' : 'border-slate-200 focus:border-blue-500 text-slate-800 bg-white']" class="w-full px-2.5 py-1.5 border text-xs focus:outline-none font-semibold transition-all"></textarea>
                            <p v-if="deleteForm.errors.remark" class="mt-0.5 text-[9px] font-bold text-rose-500">{{ deleteForm.errors.remark }}</p>
                        </div>
                    </div>
                </div>
                <div class="px-5 py-3.5 bg-slate-50 border-t border-slate-100 flex justify-end gap-2">
                    <button type="button" @click="cancelConfirmAction" class="px-3.5 py-1.5 bg-white border border-slate-200 text-slate-600 font-bold text-xs hover:bg-slate-50 transition active:scale-95 shadow-sm">Abort</button>
                    <button type="button" @click="confirmAction" class="px-4 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-sm transition active:scale-95">Confirm Erase</button>
                </div>
            </div>
        </div>
    </Transition>

    <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="isHistoryModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div @click.stop class="bg-white max-w-4xl w-full h-[85vh] shadow-2xl border border-slate-200 flex flex-col rounded-sm overflow-hidden animate-scaleUp">
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between shrink-0">
                    <div>
                        <h3 class="text-sm font-black text-slate-900 uppercase tracking-tight">Revision Logs Timeline</h3>
                        <p class="text-[10px] font-medium text-blue-600 mt-0.5 truncate max-w-xl">Active Entity Track: <span class="font-black bg-blue-50 border border-blue-100 px-1 py-0.5 rounded-xs">"{{ selectedName }}"</span></p>
                    </div>
                    <button @click="closeHistoryModal" type="button" class="text-slate-400 hover:text-slate-600 transition p-1 bg-white border border-slate-200 rounded-sm active:scale-95 shadow-sm">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto p-6 bg-slate-50/50">
                    <div v-if="isLoadingHistory" class="flex flex-col items-center justify-center h-full py-12">
                        <svg class="animate-spin h-8 w-8 text-blue-600 mb-3" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-widest animate-pulse">Gathering history log tracks...</span>
                    </div>

                    <div v-else-if="historyLogs.length === 0" class="flex flex-col items-center justify-center h-full py-12 border-2 border-dashed border-slate-200 bg-white">
                        <span class="text-xs font-bold text-slate-400 italic">No historical changes or logs registered for this material entity.</span>
                    </div>

                    <div v-else class="space-y-6">
                        <div v-for="(log, idx) in historyLogs" :key="log.id" class="bg-white border border-slate-200 p-5 shadow-xs relative hover:border-slate-300 transition-colors duration-200">
                            <div class="flex flex-col md:flex-row md:items-center justify-between border-b border-slate-100 pb-3 mb-4 gap-2">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span class="font-mono text-[10px] bg-slate-100 border border-slate-200 text-slate-600 px-1.5 py-0.5 font-bold shrink-0">#{{ historyLogs.length - idx }}</span>
                                    <span :class="[log.event_name === 'create' ? 'bg-emerald-600 text-white' : log.event_name === 'update' ? 'bg-blue-600 text-white' : 'bg-rose-600 text-white']" class="px-2 py-0.5 text-[9px] font-black uppercase tracking-wider shrink-0">{{ log.event_name }}</span>
                                    <span class="text-xs font-black text-slate-800 truncate" :title="log.user_name ?? 'System Process'">By: {{ log.user_name ?? 'System Process' }}</span>
                                </div>
                                <div class="text-[10px] font-mono font-bold text-slate-400 whitespace-nowrap bg-slate-50 px-2 py-0.5 border border-slate-100 rounded-sm">{{ formatLogDate(log.created_at) }}</div>
                            </div>

                            <div class="mb-4 text-xs font-medium text-slate-700 bg-slate-50/70 border-l-2 border-slate-300 p-2.5 italic rounded-r-xs">
                                <span class="font-black text-slate-500 uppercase text-[9px] block tracking-wider not-italic mb-0.5">Reason / Description Note:</span>
                                "{{ log.reason_changed ?? 'No explicit change note recorded' }}"
                            </div>

                                    <div v-if="getChangedFields(log).length > 0" class="mt-3">
                                        <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider block mb-2">Field Modification Summary:</span>
                                        <div class="border border-slate-100 overflow-x-auto rounded-sm">
                                            <table class="w-full text-left text-xs border-collapse whitespace-nowrap">
                                                <thead class="bg-slate-50 text-[10px] font-black text-slate-400 uppercase tracking-wider border-b border-slate-100">
                                                    <tr>
                                                        <th class="px-3 py-2 w-1/4">Field Target</th>
                                                        <th class="px-3 py-2 w-3/8" v-if="log.event_name !== 'create'">State Before</th>
                                                        <th class="px-3 py-2 w-3/8" v-if="log.event_name !== 'delete'">State After</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-50 font-medium">
                                                    <tr v-for="item in getChangedFields(log)" :key="item.field" class="hover:bg-slate-50/50">
                                                        <td class="px-3 py-1.5 font-bold text-slate-700 text-[11px]">{{ formatFieldName(item.field) }}</td>
                                                        
                                                        <td class="py-1.5" v-if="log.event_name !== 'create'">
                                                            <span class="bg-slate-100 text-slate-600 px-1.5 py-0.5 -sm block w-fit max-w-xs truncate" :title="String(item.before)">
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
</template>

<style scoped>
/* Transisi pudar */
.fade-enter-active, .fade-leave-active {
    transition: opacity 0.2s ease;
}
.fade-enter-from, .fade-leave-to {
    opacity: 0;
}

/* Transisi geser panel drawer */
.slide-enter-active, .slide-leave-active {
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.slide-enter-from { transform: translateX(100%); }
.slide-leave-to { transform: translateX(100%); }

/* Animasi skala up modal */
@keyframes scaleUp {
    from { opacity: 0; transform: scale(0.97); }
    to { opacity: 1; transform: scale(1); }
}
.animate-scaleUp {
    animation: scaleUp 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

/* Fix dropdown overflow positioning select2 container */
.absoluteSub {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
}
</style>