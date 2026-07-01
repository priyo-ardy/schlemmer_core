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
    selectedName.value = name,
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
    <!-- Page wrapper -->
    <div class="flex min-h-screen bg-slate-50 font-sans antialiased text-slate-800">
        <!-- Content Wrapper -->
        <div class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">
            <!-- Heading Section -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                <!-- Heading title -->
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">
                        Material Management
                    </h1>
                    <p class="text-xs text-slate-500 mt-1">
                        Manage your material.
                    </p>
                </div>
                
                <!-- toolbar -->
                <div class="sticky top-0 z-30 bg-white/95 backdrop-blur-sm border border-slate-200 px-3 py-2 shadow-sm">
                    <div class="flex items-center justify-between w-full">
                        <div class="flex items-center gap-1.5">
                            <!-- Button New -->
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

                            <!-- Button Refresh -->
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

                            <!-- Button Delete -->
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

            <!-- Search, per page, status -->
            <div class="flex-1 flex flex-col">
                <div class="bg-white border border-slate-200/80 shadow-sm p-4 mb-6 flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
                    <!-- Search input -->
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

                    <!-- Perpage and action status -->
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

                <!-- Data table section -->
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
                                    <th class="px-4 py-3">Code</th>
                                    <th class="px-4 py-3">Name</th>
                                    <th class="px-4 py-3">Specification</th>
                                    <th class="px-4 py-3">UoM</th>
                                    <th class="px-4 py-3">Grade</th>
                                    <th class="px-4 py-3">Density</th>
                                    <th class="px-4 py-3">Melt Flow Index</th>
                                    <th class="px-4 py-3">Color</th>
                                    <th class="px-4 py-3">Shrinkage Rate</th>
                                    <th class="px-4 py-3">Gross Weight</th>
                                    <th class="px-4 py-3">Net Weight</th>
                                    <th class="px-4 py-3">Sprue Weight</th>
                                    <th class="px-4 py-3">Has ROHS</th>
                                    <th class="px-4 py-3">IMDS Number</th>
                                    <th class="px-4 py-3">MSDS Doc. Path</th>
                                    <th class="px-4 py-3">Risk Profile</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Remark</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-xs text-slate-600">
                                <tr
                                    v-for="material in materials.data"
                                    :key="material.id"
                                    @click="openEditDrawer(material)"
                                    class="hover:bg-blue-50 transition-colors cursor-pointer"
                                >
                                    <td class="px-4 py-3 text-center" @click.stop>
                                        <input
                                            type="checkbox"
                                            :value="material.id"
                                            v-model="selectedIds"
                                            class="border-slate-300 text-blue-600 h-4 w-4 cursor-pointer"
                                        >
                                    </td>
                                    <td class="px-4 py-3"
                                            @click.stop="openHistoryModal(material.id, material.code)"
                                        >
                                        <div class="flex justify-center">
                                            <span class="px-2.5 py-0.5 text-xs text-[10px] text-blue-800 bg-blue-100">
                                                Rev. {{ material.revision }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">{{ formStatus(material.category) }}</td>
                                    <td class="px-4 py-3">{{ material.code }}</td>
                                    <td class="px-4 py-3">{{ material.name }}</td>
                                    <td class="px-4 py-3">{{ material.specification }}</td>
                                    <td class="px-4 py-3">{{ material.units?.symbol }}</td>
                                    <td class="px-4 py-3">{{ formatFieldName(material.grade) }}</td>
                                    <td class="px-4 py-3">{{ material.density }}</td>
                                    <td class="px-4 py-3">{{ material.melt_flow_index }}</td>
                                    <td class="px-4 py-3">{{ material.color }}</td>
                                    <td class="px-4 py-3">{{ formatFieldName(material.shrinkage_rate) }}</td>
                                    <td class="px-4 py-3">{{ material.gross_weight }}</td>
                                    <td class="px-4 py-3">{{ material.net_weight }}</td>
                                    <td class="px-4 py-3">{{ material.sprue_weight }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-center">
                                            <span
                                                :class="
                                                    material.has_rohs
                                                        ? 'bg-green-100 text-green-700'
                                                        : 'bg-rose-100 text-rose-600'
                                                "
                                                class="px-2 py-1 font-bold text-[10px]"
                                            >
                                                {{
                                                    material.has_rohs
                                                        ? "Yes"
                                                        : "No"
                                                }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">{{ formatFieldName(material.imds_number) }}</td>
                                    <td class="px-4 py-3">{{ formatFieldName(material.msds_doc_path) }}</td>
                                    <td class="px-4 py-3">
                                            <div class="flex justify-center">
                                                <span
                                                    class="inline-flex items-center px-2.5 py-1 text-xs text-[10px] border tracking-wider"
                                                    :class="{
                                                        'bg-emerald-50 text-emerald-700 border-emerald-200': material.risk_profile === 'low',
                                                        'bg-amber-50 text-amber-700 border-amber-200': material.risk_profile === 'medium',
                                                        'bg-rose-50 text-rose-700 border-rose-200': material.risk_profile === 'high'
                                                    }"
                                                >
                                                    <span 
                                                        class="w-1.5 h-1.5 rounded-full mr-1.5"
                                                        :class="{
                                                            'bg-emerald-500': material.risk_profile === 'low',
                                                            'bg-amber-500': material.risk_profile === 'medium',
                                                            'bg-rose-500': material.risk_profile === 'high'
                                                        }"
                                                    ></span>
                                                    
                                                    {{ ucwords(material.risk_profile) }}
                                                </span>
                                            </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-center">
                                                <span
                                                    :class="
                                                        material.is_active
                                                            ? 'bg-emerald-100 text-emerald-700'
                                                            : 'bg-slate-100 text-slate-600'
                                                    "
                                                    class="px-2 py-1 font-bold text-[10px]"
                                                >
                                                    {{
                                                        material.is_active
                                                            ? "Active"
                                                            : "Inactive"
                                                    }}
                                                </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">{{ material.remark }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
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
                                    v-if="
                                        link.label.includes('Previous') ||
                                        link.label.includes('Next')
                                    "
                                    :href="link.url ?? '#'"
                                    v-html="link.label"
                                    preserve-scroll
                                    class="px-2 py-1 text-xs font-bold text-slate-500 hover:text-slate-900 transition"
                                    :class="{
                                        'opacity-30 cursor-not-allowed':
                                            !link.url,
                                    }"
                                />

                                <Link
                                    v-else
                                    :href="link.url ?? '#'"
                                    v-html="link.label"
                                    preserve-scroll
                                    class="px-3 py-1.5 text-xs font-bold border transition-all"
                                    :class="
                                        link.active
                                            ? 'bg-blue-600 text-white border-blue-600'
                                            : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-100'
                                    "
                                />
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Slideover / Form Drawer-->
    <div
        class="fixed inset-0 z-40 overflow-hidden transition-all duration-300"
        :class="isDrawerOpen ? 'visible opacity-100' : 'invisible opacity-0 delay-300'"
        role="dialog"
        aria-modal="true"
    >
        <div class="absolute inset-0 overflow-hidden">
            
            <Transition
                enter-active-class="transition-opacity ease-in-out duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity ease-in-out duration-300"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-show="isDrawerOpen"
                    @click="isDrawerOpen = false"
                    class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                ></div>
            </Transition>
            
            <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                <Transition
                    enter-active-class="transform transition ease-in-out duration-300"
                    enter-from-class="translate-x-full"
                    enter-to-class="translate-x-0"
                    leave-active-class="transform transition ease-in-out duration-300"
                    leave-from-class="translate-x-0"
                    leave-to-class="translate-x-full"
                >
                    <div v-show="isDrawerOpen" class="pointer-events-auto w-screen max-w-2xl bg-white shadow-2xl flex flex-col h-full border-l border-slate-200">
                        
                        <div class="bg-slate-900 px-6 py-5 flex items-center justify-between shrink-0">
                            <div>
                                <h2 class="text-base font-black text-white tracking-tight">
                                    {{ isEditMode ? 'Edit Material : ' + selectedMaterial?.name : 'Register New Material' }}
                                </h2>
                                <p class="text-[10px] text-slate-400 mt-0.5">
                                    Material management form.
                                </p>
                            </div>
                            <button
                                type="button"
                                @click="isDrawerOpen = false"
                                class="text-slate-400 hover:text-white hover:bg-slate-800 p-1.5 transition"
                            >
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <form class="flex-1 overflow-y-auto space-y-6 bg-slate-50">
                            <div class="flex-1 p-6 space-y-5 bg-slate-50/50">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">
                                            Material Code 
                                            <span class="text-bold text-rose-500">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            v-model="form.code"
                                            maxlength="50"
                                            required
                                            placeholder="e.g. 7171-1234-50"
                                            :class="[
                                                'w-full pl-3 pr-3 py-2 bg-white border text-xs font-medium focus:outline-none transition-all',
                                                form.errors.code
                                                    ? 'border-rose-500 focus:border-rose-500 focus:ring-1 focus:ring-rose-500 text-rose-600'
                                                    : 'border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800',
                                            ]"
                                        />
                                        <p
                                            v-if="form.errors.code"
                                            class="mt-1 text-[10px] font-bold text-rose-500"
                                        >
                                            {{ form.errors.code }}
                                        </p>
                                    </div>

                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">
                                            Material Name
                                            <span class="text-rose-500">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            v-model="form.name"
                                            maxlength="150"
                                            required
                                            placeholder="Enter material name ..."
                                            :class="[
                                                'w-full pl-3 pr-3 py-2 bg-white border text-xs font-medium focus:outline-none transition-all',
                                                form.errors.name
                                                    ? 'border-rose-500 focus:border-rose-500 focus:ring-1 focus:ring-rose-500 text-rose-600'
                                                    : 'border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800',
                                            ]"
                                        />
                                        <p
                                            v-if="form.errors.name"
                                            class="mt-1 text-[10px] font-bold text-rose-500"
                                        >
                                            {{ form.errors.name }}
                                        </p>
                                    </div>

                                    <div class="col-span-2">
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">
                                            Specification
                                            <span class="text-bold text-rose-500">*</span>
                                        </label>
                                        <textarea 
                                            required
                                            v-model="form.specification"
                                            placeholder="Write material specification here ..."
                                            rows="3"
                                            :class="[
                                                'w-full pl-3 pr-3 py-2 bg-white border text-xs font-medium focus:outline-none transition-all',
                                                form.errors.specification
                                                    ? 'border-rose-500 focus:border-rose-500 focus:ring-1 focus:ring-rose-500 text-rose-600'
                                                    : 'border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800',
                                            ]"
                                        ></textarea>
                                        <p
                                            v-if="form.errors.specification"
                                            class="mt-1 text-[10px] font-bold text-rose-500"
                                        >
                                            {{ form.errors.specification }}
                                        </p>
                                    </div>
                                </div>
                                <div class="grid grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">
                                            Material Category
                                            <span class="text-bold text-rose-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <div
                                                v-if="isCategoryDropdownOpen"
                                                @click="isCategoryDropdownOpen = false"
                                                class="fixed inset-0 z-0"
                                            ></div>

                                            <div
                                                @click="isCategoryDropdownOpen = !isCategoryDropdownOpen"
                                                class="relative z-20 w-full pl-3 pr-3 py-2 border text-xs focus:outline-none focus:border-blue-500 bg-white cursor-pointer flex justify-between items-center transition-all"
                                                :class="[
                                                    form.errors.category
                                                        ? 'border-rose-500 focus:border-rose-500 focus:ring-1 focus:ring-rose-500 text-rose-600'
                                                        : 'border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800'
                                                ]"
                                            >
                                                <span :class="form.category ? 'text-slate-800 font-semibold' : 'text-slate-400'">
                                                    {{ selectedCategoryName }}
                                                </span>
                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    class="h-4 w-4 text-slate-400 transition-transform duration-200"
                                                    :class="{'rotate-180 text-blue-500': isCategoryDropdownOpen}"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                >
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </div>

                                            <p
                                                v-if="form.errors.category"
                                                class="mt-1 text-[10px] font-bold text-rose-500"
                                            >
                                                {{ form.errors.category }}
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
                                                    v-if="isCategoryDropdownOpen"
                                                    class="absolute z-30 w-full mt-1 bg-white border border-slate-200 shadow-xl overflow-hidden"
                                                >
                                                    <div class="p-2 border-b border-slate-100 bg-slate-50 sticky top-0">
                                                        <div class="relative">
                                                            <svg class="absolute left-2 top-2 h-3.5 w-3.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                            </svg>
                                                            <input
                                                                type="text"
                                                                v-model="categorySearch"
                                                                @click.stop
                                                                class="w-full pl-7 pr-2 py-1.5 border border-slate-200 text-xs rounded-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 bg-white"
                                                                placeholder="Type to search category..."
                                                                autofocus
                                                            />
                                                        </div>
                                                    </div>

                                                    <div class="max-h-48 overflow-y-auto">
                                                        <div
                                                            v-for="item in filteredCategories"
                                                            :key="item.value"
                                                            @click="selectCategory(item.value)"
                                                            class="px-3 py-2.5 text-xs cursor-pointer hover:bg-blue-50 hover:text-blue-700 transition-colors border-b border-slate-50 last:border-0"
                                                            :class="{'bg-blue-50 text-blue-700 font-bold border-l-2 border-l-blue-600': form.category === item.value}"
                                                        >
                                                            {{ item.label }}
                                                        </div>

                                                        <div v-if="filteredCategories.length === 0" class="px-3 py-6 text-xs text-center text-slate-400 italic bg-slate-50">
                                                            No category found matching "{{ categorySearch }}"
                                                        </div>
                                                    </div>
                                                </div>
                                            </Transition>
                                        </div>        
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">
                                            UoM <span class="text-bold text-rose-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <div
                                                v-if="isUnitDropdownOpen"
                                                @click="isUnitDropdownOpen = false"
                                                class="fixed inset-0 z-0"
                                            ></div>
                                            
                                            <div
                                                @click="isUnitDropdownOpen = !isUnitDropdownOpen"
                                                class="relative z-20 w-full pl-3 pr-3 py-2 border text-xs focus:outline-none focus:border-blue-500 bg-white cursor-pointer flex justify-between items-center transition-all"
                                                :class="[
                                                    // GANTI dari form.unit_id menjadi form.errors.unit_id
                                                    form.errors.unit_id
                                                        ? 'border-rose-500 focus:border-rose-500 focus:ring-1 focus:ring-rose-500 text-rose-600'
                                                        : 'border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800'
                                                ]"
                                            >
                                                <span :class="form.unit_id ? 'text-slate-800 font-semibold' : 'text-slate-400'">
                                                    {{ selectedUnitName }}
                                                </span>
                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    class="h-4 w-4 text-slate-400 transition-transform duration-200"
                                                    :class="{'rotate-180 text-blue-500': isUnitDropdownOpen}"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                >
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </div>
                                            <p
                                                v-if="form.errors.unit_id"
                                                class="mt-1 text-[10px] font-bold text-rose-500"
                                            >
                                                {{ form.errors.unit_id }}
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
                                                    v-if="isUnitDropdownOpen"
                                                    class="absolute z-30 w-full mt-1 bg-white border border-slate-200 shadow-xl overflow-hidden"
                                                >
                                                    <div class="p-2 border-b border-slate-100 bg-slate-50 sticky top-0">
                                                        <div class="relative">
                                                            <svg class="absolute left-2 top-2 h-3.5 w-3.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                            </svg>
                                                            <input
                                                                type="text"
                                                                v-model="unitSearch"
                                                                @click.stop
                                                                class="w-full pl-7 pr-2 py-1.5 border border-slate-200 text-xs rounded-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 bg-white"
                                                                placeholder="Type to search..."
                                                                autofocus
                                                            />
                                                        </div>
                                                    </div>
                                                    <div class="max-h-48 overflow-y-auto">
                                                        <div
                                                            v-for="uom in filteredUnits"
                                                            :key="uom.id"
                                                            @click="selectUnit(uom.id)"
                                                            class="px-3 py-2.5 text-xs cursor-pointer hover:bg-blue-50 hover:text-blue-700 transition-colors border-b border-slate-50 last:border-0"
                                                            :class="{'bg-blue-50 text-blue-700 font-bold border-l-2 border-l-blue-600': form.unit_id === uom.id}"
                                                        >
                                                            {{ uom.symbol }} - {{ uom.name  }}
                                                        </div>
                                                        
                                                        <div v-if="filteredUnits.length === 0" class="px-3 py-6 text-xs text-center text-slate-400 italic bg-slate-50">
                                                            No customer found matching "{{ unitSearch }}"
                                                        </div>
                                                    </div>
                                                </div>
                                            </Transition>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">
                                            Customer Part Name
                                        </label>
                                        <input
                                            type="text"
                                            v-model="form.customer_part_name"
                                            maxlength="255"
                                            required
                                            placeholder="Customer part name ..."
                                            class="w-full pl-3 pr-3 py-2 bg-white border text-xs font-medium focus:outline-none transition-all border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800"
                                        />
                                    </div>
                                </div>
                                <div class="grid grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">
                                            Drawing Change No.
                                        </label>
                                        <input
                                            type="text"
                                            v-model="form.drawing_change"
                                            maxlength="255"
                                            required
                                            placeholder="Drawing change no ..."
                                            class="w-full pl-3 pr-3 py-2 bg-white border text-xs font-medium focus:outline-none transition-all border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">
                                            Grade
                                        </label>
                                        <input
                                            type="text"
                                            v-model="form.grade"
                                            maxlength="150"
                                            placeholder="Material grade ..."
                                            class="w-full pl-3 pr-3 py-2 bg-white border text-xs font-medium focus:outline-none transition-all border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">
                                            Density
                                        </label>
                                        <input
                                            type="number"
                                            v-model="form.density"
                                            maxlength="8"
                                            step="0.0000"
                                            placeholder="Material density ..."
                                            class="w-full pl-3 pr-3 py-2 bg-white border text-xs font-medium focus:outline-none transition-all border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800"
                                        />
                                    </div>
                                </div>
                                <div class="grid grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">
                                            Melt Flow Index
                                        </label>
                                        <input
                                            type="number"
                                            v-model="form.melt_flow_index"
                                            maxlength="8"
                                            step="0.0000"
                                            placeholder="MFI (g/10 min) - Determining the flow viscosity of resin in a molding machine"
                                            class="w-full pl-3 pr-3 py-2 bg-white border text-xs font-medium focus:outline-none transition-all border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">
                                            Color
                                        </label>
                                        <input
                                            type="text"
                                            v-model="form.color"
                                            maxlength="50"
                                            placeholder="Define material color ..."
                                            class="w-full pl-3 pr-3 py-2 bg-white border text-xs font-medium focus:outline-none transition-all border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">
                                            Shrinkage Rate
                                        </label>
                                        <input
                                            type="text"
                                            v-model="form.shrinkage_rate"
                                            maxlength="50"
                                            placeholder="Material shrinkage rate (%) for dimensional accuracy in molding ..."
                                            class="w-full pl-3 pr-3 py-2 bg-white border text-xs font-medium focus:outline-none transition-all border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800"
                                        />
                                    </div>
                                </div>
                                <div class="grid grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">
                                            Gross Weight
                                        </label>
                                        <input
                                            type="number"
                                            v-model="form.gross_weight"
                                            maxlength="8"
                                            step="0.0000"
                                            @input="sprueCalculate"
                                            placeholder="Material gross weigt ..."
                                            class="w-full pl-3 pr-3 py-2 bg-white border text-xs font-medium focus:outline-none transition-all border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">
                                            Net Wight
                                        </label>
                                        <input
                                            type="number"
                                            v-model="form.net_weight"
                                            maxlength="8"
                                            step="0.0000"
                                            @input="sprueCalculate"
                                            placeholder="Material net weight ..."
                                            class="w-full pl-3 pr-3 py-2 bg-white border text-xs font-medium focus:outline-none transition-all border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">
                                            Sprue Weight
                                        </label>
                                        <input
                                            type="number"
                                            v-model="form.sprue_weight"
                                            maxlength="8"
                                            step="0.0000"
                                            readonly
                                            placeholder="Material sprue weight ..."
                                            class="w-full pl-3 pr-3 py-2 bg-white border text-xs font-medium focus:outline-none transition-all border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800"
                                        />
                                    </div>
                                </div>
                                <div class="grid grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">
                                            ROHS Status
                                        </label>
                                        <div
                                            class="flex items-center justify-between p-3 bg-white border border-slate-300"
                                        >
                                            <div class="flex flex-col min-w-0 pr-4">
                                                <span
                                                    class="text-xs font-bold text-slate-800 uppercase tracking-wider"
                                                    >ROHS Status</span
                                                >
                                                <span
                                                    class="text-[10px] font-medium text-slate-500 mt-0.5 truncate"
                                                >
                                                    {{
                                                        form.has_rohs
                                                            ? "ROHS Available."
                                                            : "ROHS Not Available."
                                                    }}
                                                </span>
                                            </div>
                                            <button
                                                type="button"
                                                @click="
                                                    form.has_rohs = !form.has_rohs
                                                "
                                                :class="
                                                    form.has_rohs
                                                        ? 'bg-emerald-600'
                                                        : 'bg-slate-400'
                                                "
                                                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer border-2 border-transparent transition-colors duration-200 ease-in-out active:scale-95"
                                            >
                                                <span
                                                    :class="
                                                        form.has_rohs
                                                            ? 'translate-x-5'
                                                            : 'translate-x-0'
                                                    "
                                                    class="pointer-events-none inline-block h-5 w-5 transform bg-white shadow-sm transition duration-200"
                                                >
                                                </span>
                                            </button>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">
                                            IMDS Number
                                        </label>
                                        <input
                                            type="text"
                                            v-model="form.imds_number"
                                            maxlength="50"
                                            placeholder="International Material Data System registration number ..."
                                            class="w-full pl-3 pr-3 py-2 bg-white border text-xs font-medium focus:outline-none transition-all border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">
                                            MSDS Doc Path
                                        </label>
                                        <input
                                            type="text"
                                            v-model="form.imds_number"
                                            maxlength="150"
                                            placeholder="Pathway file PDF Material Safety Data Sheet ..."
                                            class="w-full pl-3 pr-3 py-2 bg-white border text-xs font-medium focus:outline-none transition-all border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800"
                                        />
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">
                                            Risk Profile
                                        </label>
                                        <div class="grid grid-cols-3 gap-3">
                                            <label 
                                                class="flex flex-col items-center justify-center p-3 border cursor-pointer transition-all select-none text-center"
                                                :class="form.risk_profile === 'low' 
                                                    ? 'border-emerald-500 bg-emerald-50/50 text-emerald-700 ring-2 ring-emerald-500/20 font-bold' 
                                                    : 'border-slate-200 bg-white hover:bg-slate-50 text-slate-600'"
                                            >
                                                <input type="radio" v-model="form.risk_profile" value="low" class="sr-only" />
                                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 mb-1"></span>
                                                <span class="text-xs uppercase tracking-wider">Low</span>
                                            </label>

                                            <label 
                                                class="flex flex-col items-center justify-center p-3 border cursor-pointer transition-all select-none text-center"
                                                :class="form.risk_profile === 'medium' 
                                                    ? 'border-amber-500 bg-amber-50/50 text-amber-700 ring-2 ring-amber-500/20 font-bold' 
                                                    : 'border-slate-200 bg-white hover:bg-slate-50 text-slate-600'"
                                            >
                                                <input type="radio" v-model="form.risk_profile" value="medium" class="sr-only" />
                                                <span class="w-2.5 h-2.5 rounded-full bg-amber-500 mb-1"></span>
                                                <span class="text-xs uppercase tracking-wider">Medium</span>
                                            </label>

                                            <label 
                                                class="flex flex-col items-center justify-center p-3 border cursor-pointer transition-all select-none text-center"
                                                :class="form.risk_profile === 'high' 
                                                    ? 'border-rose-500 bg-rose-50/50 text-rose-700 ring-2 ring-rose-500/20 font-bold' 
                                                    : 'border-slate-200 bg-white hover:bg-slate-50 text-slate-600'"
                                            >
                                                <input type="radio" v-model="form.risk_profile" value="high" class="sr-only" />
                                                <span class="w-2.5 h-2.5 rounded-full bg-rose-500 mb-1"></span>
                                                <span class="text-xs uppercase tracking-wider">High</span>
                                            </label>
                                        </div>
                                        
                                        <p v-if="form.errors.risk_profile" class="mt-1 text-[10px] font-bold text-rose-500">
                                            {{ form.errors.risk_profile }}
                                        </p>
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">
                                            Status
                                        </label>
                                        <div
                                                class="flex items-center justify-between p-3 bg-white border border-slate-300"
                                            >
                                                <div class="flex flex-col min-w-0 pr-4">
                                                    <span
                                                        class="text-xs font-bold text-slate-800 uppercase tracking-wider"
                                                        >Data Status</span
                                                    >
                                                    <span
                                                        class="text-[10px] font-medium text-slate-500 mt-0.5 truncate"
                                                    >
                                                        {{
                                                            form.is_active
                                                                ? "Project status is currently Active."
                                                                : "Project status is Disabled."
                                                        }}
                                                    </span>
                                                </div>
                                                <button
                                                    type="button"
                                                    @click="
                                                        form.is_active = !form.is_active
                                                    "
                                                    :class="
                                                        form.is_active
                                                            ? 'bg-emerald-600'
                                                            : 'bg-slate-400'
                                                    "
                                                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer border-2 border-transparent transition-colors duration-200 ease-in-out active:scale-95"
                                                >
                                                    <span
                                                        :class="
                                                            form.is_active
                                                                ? 'translate-x-5'
                                                                : 'translate-x-0'
                                                        "
                                                        class="pointer-events-none inline-block h-5 w-5 transform bg-white shadow-sm transition duration-200"
                                                    >
                                                    </span>
                                                </button>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-500 mb-1">
                                        Remark (optional)
                                    </label>
                                    <textarea
                                        v-model="form.remark"
                                        placeholder="Write additional information here ..."
                                        rows="3"
                                        class="w-full pl-3 pr-3 py-2 bg-white border text-xs font-medium focus:outline-none transition-all border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800"
                                    ></textarea>
                                </div>
                                <div
                                    v-show="isEditMode"
                                >
                                    <label class="block text-[11px] font-bold text-slate-500 mb-1">
                                        Change Reason <span class="text-bold text-rose-500">*</span>
                                    </label>
                                    <textarea
                                        v-model="form.reason"
                                        :required="isEditMode"
                                        placeholder="Write additional information here ..."
                                        rows="3"
                                        :class="[
                                                'w-full pl-3 pr-3 py-2 bg-white border text-xs font-medium focus:outline-none transition-all',
                                                form.errors.reason
                                                    ? 'border-rose-500 focus:border-rose-500 focus:ring-1 focus:ring-rose-500 text-rose-600'
                                                    : 'border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800',
                                            ]"
                                    ></textarea>
                                    <p
                                        v-if="form.errors.reason"
                                        class="mt-1 text-[10px] font-bold text-rose-500"
                                    >
                                        {{ form.errors.reason }}
                                    </p>
                                </div>
                            </div>
                        </form>

                        <div class="px-6 py-4 bg-white border-t border-slate-200 flex justify-end gap-3 shrink-0">
                            <button
                                @click="isDrawerOpen = false"
                                type="button"
                                class="px-5 py-2.5 text-xs font-bold text-slate-600 bg-white border border-slate-300 hover:bg-slate-50 active:scale-95 transition">
                                Cancel
                            </button>

                            <button
                                @click="submitForm"
                                :disabled="form.processing"
                                class="px-6 py-2.5 bg-blue-600 text-white font-bold text-xs shadow-md hover:bg-blue-700 transition-all active:scale-95 disabled:opacity-50 flex items-center gap-2"
                            >
                                <svg
                                    v-if="form.processing"
                                    class="animate-spin h-3.5 w-3.5 text-white"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>

                                {{ form.processing ? "Saving..." : isEditMode ? "Apply Changes" : "Save Material" }}
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </div>
    </div>

    <!-- konfirmasi hapus -->
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
                class="bg-white w-full max-w-3xl border border-slate-200 shadow-2xl flex flex-col max-h-[90vh] overflow-hidden"
            >
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 bg-white shrink-0">
                    <h3 class="text-sm font-black text-slate-900 uppercase tracking-wider flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5 text-blue-600">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        Material Revision History : {{ selectedName }}
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
</template>