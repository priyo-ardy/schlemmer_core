<script setup>
import "vue3-toastify/dist/index.css";
import { ref, watch, computed, isRef } from "vue";
import { Head, router, Link, usePage, useForm } from "@inertiajs/vue3";
import { toast } from "vue3-toastify";
import { debounce } from "lodash";
import axios from "axios";
import dayjs from "dayjs";
import "dayjs/locale/id";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

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
const selectedProject = ref(null);
const isEditMode = computed(() => selectedProject.value !== null);

// Delete form dalam modal
const deleteForm =useForm({
    ids: [],
    remark: ""
});

// Tangkap data dari BE
const props = defineProps({
    projects:{
        type: Object,
        default: () => ({ data:[], links:[], per_page: 10}),
    },
    customers: Array
});

// Definisikan form
const form = useForm({
    code: "",
    name: "",
    customer_id: "",
    vehicle_model: "",
    main_part_number: "",
    main_part_name: "",
    apqp_phase: "",
    status: "",
    kick_off_date: "",
    target_proto_date: "",
    target_ppap_date: "",
    target_sop_date: "",
    confidentiality_level: "",
    revision: "",
    is_active: true,
    remark: "",
    reason: "",
});

// Setup per page
const perPage = ref(props.projects?.per_page || 10);
// Setup form processing
const isProcessing = computed(() => form.processing || false);
// Setup error
const errors = computed(() => page.props.errors || {});

// Refreshing table
const refreshTable = () => {
    isRefreshing.value = true;
    router.reload({
        only:["projects"],
        onSuccess: () => {
            isRefreshing.value = false;
        },
        onError: () => {
            isRefreshing.value = false;
            toast.error("Failed to refresh data");
        }
    })
}

// Event untuk Select All
const toggleSelectAll = (event) => {
    selectedIds.value = event.target.checked
        ? props.projects.data.map((c) => c.id)
        : [];
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

const openCreateDrawer = () => {
    selectedProject.value = null;
    form.reset();
    form.clearErrors();
    isDrawerOpen.value = true;
}

const openNewPage = () => {
    router.visit('/projects/create');
}

const openEditDrawer = (project) => {
    selectedProject.value = project;
    form.clearErrors();

    form.id = project.id;
    form.code = project.code ?? "";
    form.name = project.name ?? "";
    form.customer_id = project.customer_id ?? "";
    form.vehicle_model = project.vehicle_model ?? "";
    form.main_part_number = project.main_part_number ?? "";
    form.main_part_name = project.main_part_name ?? "";
    form.apqp_phase = project.apqp_phase ?? "";
    form.status = project.status ?? "";
    form.kick_off_date = project.kick_off_date ?? "";
    form.target_proto_date = project.target_proto_date ?? "";
    form.target_ppap_date = project.target_ppap_date ?? "";
    form.target_sop_date = project.target_sop_date ?? "";
    form.confidentiality_level = project.confidentiality_level ?? "";
    form.revision = project.revision ?? "";
    form.is_active = project.is_active ?? true;
    form.remark = project.remark ?? "";

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
    }).post(isEditMode.value ? `/projects/${form.id}` : '/projects', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0];
            toast.error(firstError);
        }
    });
}

const deleteSelected = () => {
    if(selectedIds.value.length > 0) showConfirmModal.value = true;
}

const confirmAction = () => {
    deleteForm.clearErrors();

    // Langsung cek dari deleteForm.remark
    if (!deleteForm.remark || deleteForm.remark.trim() === "") {
        deleteForm.setError('remark', 'This field is required');
        return;
    }

    // IDS diambil dari selectedIds
    deleteForm.ids = selectedIds.value;

    deleteForm.post("/projects/mass-delete", {
        preserveScroll: true,
        onSuccess: () => {
            selectedIds.value = [];
            showConfirmModal.value = false;
            deleteForm.reset(); // Ini bakal nge-reset remark ke string kosong ""
            toast.success("Selected items deleted successfully");
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0];
            toast.error(firstError || "Failed to delete items");
        }
    });
};

const cancelConfirmAction = () => {
    deleteForm.clearErrors();
    selectedIds.value = [];
    showConfirmModal.value = false;
    deleteForm.reset();
}

// fungsi buat update jumlah perhalaman
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

// Search Query
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
        const response = await axios.get(`/projects/${id}/logs`);
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

// --- State untuk Custom Select2 ---
const isCustomerDropdownOpen = ref(false);
const customerSearch = ref("");

// Filter data customer berdasarkan inputan search
const filteredCustomers = computed(() => {
    if (!customerSearch.value) return props.customers;
    const lowerSearch = customerSearch.value.toLowerCase();
    return props.customers.filter(c =>
        c.name.toLowerCase().includes(lowerSearch)
    );
});

// Nampilin nama customer yang dipilih di kotak utama
const selectedCustomerName = computed(() => {
    if (!form.customer_id) return "Select Core Customer";
    // Cari nama customer dari props.customers berdasarkan form.customer_id
    const cust = props.customers.find(c => c.id === form.customer_id);
    return cust ? cust.name : "Select Core Customer";
});

// Fungsi eksekusi pas data diklik
const selectCustomer = (id) => {
    form.customer_id = id; // Nah, ini yang bikin datanya kepilih beneran!
    isCustomerDropdownOpen.value = false;
    customerSearch.value = ""; // Reset search tiap habis milih
};

const formatStatus = (status) => {
    // Kalau kosong/null/string kosong, langsung balikin "-"
    if (!status) return "-";

    // 1. Cek kalau 4 huruf terakhirnya adalah "_dev", ubah jadi "_development"
    let formatted = status.replace(/_dev$/, "_development");

    // 2. Replace semua underscore "_" jadi spasi " "
    formatted = formatted.replace(/_/g, " ");

    // 3. Bikin ucwords (Huruf depan tiap kata jadi kapital)
    formatted = formatted.replace(/\b\w/g, (char) => char.toUpperCase());

    return formatted;
};
</script>

<template>
    <Head title="List of Project" />
    <!-- Page Wrapper -->
    <div class="flex min-h-screen bg-slate-50 font-sans antialiased text-slate-800">
        <!-- Content Wrapper -->
         <div class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">
            <!-- Heading -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                <!-- Head Title -->
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">
                        Projects Management
                    </h1>
                    <p class="text-xs text-slate-500 mt-1">
                        Manage project.
                    </p>
                </div>

                <!-- Toolbar -->
                <div class="sticky top-0 z-30 bg-white/95 backdrop-blur-sm border border-slate-200 px-3 py-2 shadow-sm">
                    <div class="flex items-center justify-between w-full">
                        <div class="flex items-center gap-1.5">
                            <!-- Button New -->
                            <button
                                @click="openNewPage"
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
                    <!-- Search bar -->
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

                <!-- Table section -->
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
                                    <th class=" px-4 py-3">Project Code</th>
                                    <th class=" px-4 py-3">Project Name</th>
                                    <th class=" px-4 py-3">Customer</th>
                                    <th class=" px-4 py-3">Revision</th>
                                    <th class=" px-4 py-3">Vehicle Model</th>
                                    <th class=" px-4 py-3">Main Part Number</th>
                                    <th class=" px-4 py-3">Main Part Name</th>
                                    <th class=" px-4 py-3">APQP Phase</th>
                                    <th class=" px-4 py-3">Project Status</th>
                                    <th class=" px-4 py-3">Kick Off Date</th>
                                    <th class=" px-4 py-3">Target Proto Date</th>
                                    <th class=" px-4 py-3">Target PPAP Date</th>
                                    <th class=" px-4 py-3">Targe SOP Date</th>
                                    <th class=" px-4 py-3">Confidentiality Level</th>
                                    <th class=" px-4 py-3">Data Status</th>
                                    <th class=" px-4 py-3">Remark</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-xs text-slate-600">
                                <tr
                                    v-for="project in projects.data"
                                    :key="project.id"
                                    @click="openEditDrawer(project)"
                                    class="hover:bg-blue-50 transition-colors cursor-pointer"
                                >
                                    <td class="px-4 py-3 text-center" @click.stop>
                                        <input
                                            type="checkbox"
                                            :value="project.id"
                                            v-model="selectedIds"
                                            class="border-slate-300 text-blue-600 h-4 w-4 cursor-pointer"
                                        >
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ project.code }}
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ project.name }}
                                    </td>
                                    <td class=" px-4 py-3">{{ project.customer?.name }}</td>
                                    <td
                                        class="px-4 py-3 cursor-pointer hover:bg-slate-50 transition-colors"
                                        @click.stop="
                                            openHistoryModal(project.id, project.name)
                                        "
                                    >
                                        <div class="flex justify-center">
                                            <span
                                                class="px-2.5 py-0.5 text-xs text-[10px] text-blue-800 bg-blue-100"
                                            >
                                                Rev. {{ project.revision }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class=" px-4 py-3">
                                        {{ project.vehicle_model || "-" }}
                                    </td>
                                    <td class=" px-4 py-3">
                                        {{ project.main_part_number || "-" }}
                                    </td>
                                    <td class=" px-4 py-3">
                                        {{ project.main_part_name || "-" }}
                                    </td>
                                    <td class=" px-4 py-3">
                                        {{ project.apqp_phase || "-" }}
                                    </td>
                                    <td class=" px-4 py-3">
                                        {{ project.status ? formatStatus(project.status) : "-" }}
                                    </td>
                                    <td class=" px-4 py-3">
                                        {{ project.kick_off_date ? formatTableDate(project.kick_off_date) : "-" }}
                                    </td>
                                    <td class=" px-4 py-3">
                                        {{ project.target_proto_date ? formatTableDate(project.target_proto_date) : "-" }}
                                    </td>
                                    <td class=" px-4 py-3">
                                        {{ project.target_ppap_date ? formatTableDate(project.target_ppap_date) : "-" }}
                                    </td>
                                    <td class=" px-4 py-3">
                                        {{ project.targe_sop_date ? formatTableDate(project.targe_sop_date) : "-" }}
                                    </td>
                                    <td class=" px-4 py-3">
                                        {{ project.confidentiality_level }}
                                    </td>
                                    <td class=" px-4 py-3 text-center">
                                        <div class="flex justify-center">
                                            <span
                                                :class="
                                                    project.is_active
                                                        ? 'bg-emerald-100 text-emerald-700'
                                                        : 'bg-slate-100 text-slate-600'
                                                "
                                                class="px-2 py-1 font-bold text-[10px]"
                                            >
                                                {{
                                                    project.is_active
                                                        ? "Active"
                                                        : "Inactive"
                                                }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class=" px-4 py-3">{{ project.remark }}</td>
                                </tr>
                                <tr v-if="projects.data.length === 0">
                                    <td
                                        colspan="18"
                                        class="px-4 py-8 text-center text-slate-400 font-medium"
                                    >
                                        No projects data found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div
                        v-if="projects.total > 0"
                        class="px-6 py-4 bg-white border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4"
                    >
                        <div
                            class="text-xs font-bold text-slate-500 uppercase tracking-wider"
                        >
                            Showing {{ projects.from ?? 0 }} to
                            {{ projects.to ?? 0 }} of
                            {{ projects.total ?? 0 }}
                        </div>

                        <div class="flex items-center gap-1">
                            <template
                                v-for="(link, index) in projects.links"
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

    <!-- Slideover -->
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
                                    {{ isEditMode ? 'Edit Project : ' + selectedProject?.name : 'Register New Project' }}
                                </h2>
                                <p class="text-[10px] text-slate-400 mt-0.5">
                                    Auditable project form
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
                                            Project Code 
                                            <span class="text-bold text-rose-500">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            v-model="form.code"
                                            maxlength="50"
                                            required
                                            placeholder="e.g. PRJ-2024-001"
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
                                            Project Name
                                            <span class="text-rose-500">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            v-model="form.name"
                                            maxlength="150"
                                            required
                                            placeholder="Enter project name ..."
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
                                            Customer 
                                            <span class="text-bold text-rose-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <div
                                                v-if="isCustomerDropdownOpen"
                                                @click="isCustomerDropdownOpen = false"
                                                class="fixed inset-0 z-0"
                                            ></div>

                                            <div
                                                @click="isCustomerDropdownOpen = !isCustomerDropdownOpen"
                                                class="relative z-20 w-full pl-3 pr-3 py-2 border border-slate-200 text-xs focus:outline-none focus:border-blue-500 bg-white cursor-pointer flex justify-between items-center transition-all"
                                                :class="[
                                                    form.errors.customer_id
                                                        ? 'border-rose-500 focus:border-rose-500 focus:ring-1 focus:ring-rose-500 text-rose-600'
                                                        : 'border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800',
                                                ]"
                                            >
                                                <span :class="form.customer_id ? 'text-slate-800 font-semibold' : 'text-slate-400'">
                                                    {{ selectedCustomerName }}
                                                </span>
                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    class="h-4 w-4 text-slate-400 transition-transform duration-200"
                                                    :class="{'rotate-180 text-blue-500': isCustomerDropdownOpen}"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                >
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </div>
                                            <p
                                                v-if="form.errors.customer_id"
                                                class="mt-1 text-[10px] font-bold text-rose-500"
                                            >
                                                {{ form.errors.customer_id }}
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
                                                    v-if="isCustomerDropdownOpen"
                                                    class="absolute z-30 w-full mt-1 bg-white border border-slate-200 shadow-xl overflow-hidden"
                                                >
                                                    <div class="p-2 border-b border-slate-100 bg-slate-50 sticky top-0">
                                                        <div class="relative">
                                                            <svg class="absolute left-2 top-2 h-3.5 w-3.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                            </svg>
                                                            <input
                                                                type="text"
                                                                v-model="customerSearch"
                                                                @click.stop
                                                                class="w-full pl-7 pr-2 py-1.5 border border-slate-200 text-xs rounded-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 bg-white"
                                                                placeholder="Type to search..."
                                                                autofocus
                                                            />
                                                        </div>
                                                    </div>

                                                    <div class="max-h-48 overflow-y-auto">
                                                        <div
                                                            v-for="cust in filteredCustomers"
                                                            :key="cust.id"
                                                            @click="selectCustomer(cust.id)"
                                                            class="px-3 py-2.5 text-xs cursor-pointer hover:bg-blue-50 hover:text-blue-700 transition-colors border-b border-slate-50 last:border-0"
                                                            :class="{'bg-blue-50 text-blue-700 font-bold border-l-2 border-l-blue-600': form.customer_id === cust.id}"
                                                        >
                                                            {{ cust.name }}
                                                        </div>
                                                        
                                                        <div v-if="filteredCustomers.length === 0" class="px-3 py-6 text-xs text-center text-slate-400 italic bg-slate-50">
                                                            No customer found matching "{{ customerSearch }}"
                                                        </div>
                                                    </div>
                                                </div>
                                            </Transition>
                                        </div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">
                                            Vehicle Model
                                        </label>
                                        <input
                                            type="text"
                                            v-model="form.vehicle_model"
                                            maxlength="100"
                                            placeholder="e.g. SUV Type-X"
                                            class="w-full pl-3 pr-3 py-2 bg-white border text-xs font-medium focus:outline-none transition-all border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">
                                            Main Part Number
                                        </label>
                                        <input
                                            type="text"
                                            v-model="form.main_part_number"
                                            maxlength="100"
                                            placeholder="PN-88291-XX"
                                            class="w-full pl-3 pr-3 py-2 bg-white border text-xs font-medium focus:outline-none transition-all border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">
                                            Main Part Name
                                        </label>
                                        <input
                                            type="text"
                                            v-model="form.main_part_name"
                                            maxlength="100"
                                            placeholder="Component base identifier"
                                            class="w-full pl-3 pr-3 py-2 bg-white border text-xs font-medium focus:outline-none transition-all border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800"
                                        />
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">
                                            APQP Phase
                                        </label>
                                        <input
                                            type="text"
                                            v-model="form.apqp_phase"
                                            maxlength="50"
                                            placeholder="e.g. Phase 1: Planning"
                                            class="w-full pl-3 pr-3 py-2 bg-white border text-xs font-medium focus:outline-none transition-all border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">
                                            Project Status
                                        </label>
                                        <select
                                            v-model="form.status"
                                            class="w-full pl-3 pr-3 py-2 bg-white border text-xs font-medium focus:outline-none transition-all border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800"
                                        >
                                            <option value="">Select Project Status</option>
                                            <option value="planning">Planning</option>
                                            <option value="design_dev">Design Development</option>
                                            <option value="process_dev">Process Development</option>
                                            <option value="validation">Validation</option>
                                            <option value="ppap_submitted">PPAP Submitted</option>
                                            <option value="ppap_approved">PPAP Approved</option>
                                            <option value="mass_production">Mass Production</option>
                                            <option value="change_request">Change Request</option>
                                            <option value="change_request">Discontinued</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="grid grid-cols-4 gap-4">
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">
                                            Kick Off Date
                                        </label>
                                        <input
                                            type="date"
                                            v-model="form.kick_off_date"
                                            class="w-full pl-3 pr-3 py-2 bg-white border text-xs font-medium focus:outline-none transition-all border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">
                                            Target Proto Date
                                        </label>
                                        <input
                                            type="date"
                                            v-model="form.target_proto_date"
                                            class="w-full pl-3 pr-3 py-2 bg-white border text-xs font-medium focus:outline-none transition-all border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">
                                            Target APQP Date
                                        </label>
                                        <input
                                            type="date"
                                            v-model="form.target_ppap_date"
                                            class="w-full pl-3 pr-3 py-2 bg-white border text-xs font-medium focus:outline-none transition-all border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">
                                            Target SOP Date
                                        </label>
                                        <input
                                            type="date"
                                            v-model="form.target_sop_date"
                                            class="w-full pl-3 pr-3 py-2 bg-white border text-xs font-medium focus:outline-none transition-all border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800"
                                        />
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-500 mb-1">
                                        Confidentiality Level
                                    </label>
                                    <select v-model="form.confidentiality_level" class="w-full p-2 border border-slate-200 text-xs focus:outline-none focus:border-blue-500 bg-white cursor-pointer">
                                        <option value="">Select Level</option>
                                        <option value="Public">Public</option>
                                        <option value="Internal">Internal Use</option>
                                        <option value="Confidential">Confidential</option>
                                        <option value="Strictly Confidential">Strictly Confidential</option>
                                    </select>
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
                                <div>
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

                                {{ form.processing ? "Saving..." : isEditMode ? "Apply Changes" : "Save Project" }}
                            </button>
                        </div>
                    </div>
                </Transition>
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
                class="bg-white w-full max-w-3xl border border-slate-200 shadow-2xl flex flex-col max-h-[90vh] overflow-hidden"
            >
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 bg-white shrink-0">
                    <h3 class="text-sm font-black text-slate-900 uppercase tracking-wider flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5 text-blue-600">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        Project Revision History : {{ selectedName }}
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