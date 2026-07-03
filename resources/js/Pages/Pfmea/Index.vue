<script setup>
import { ref, watch, computed } from 'vue';
import { Head, router, Link, usePage, useForm } from '@inertiajs/vue3';
import { toast } from 'vue3-toastify';
import { debounce } from 'lodash';
import axios from 'axios';
import dayjs from 'dayjs';
import "dayjs/locale/id";
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
dayjs.locale("id");

defineOptions({layout: AuthenticatedLayout});

const page = usePage();
const searchQuery = ref('');
const selectedIds = ref([]);
const isRefreshing = ref(false);
const selectedFilter = ref("all");
const showConfirmModal = ref(false);
const isSearching = ref(false);
const deleteReason = ref("");
const isHistoryModalOpen = ref(false);
const historyLogs = ref([]);
const isLoadingHistory = ref(false);
const selectedPfmeaName = ref("")
const isProcessing = computed(() => page.props.prcessing || false);
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

const deleteForm = useForm({
    ids: [],
    remark: "", // Alasan penghapusan
});

const formatLogDate = (date) => {
    if (!date) return "-"; // Guard clause jika data tanggal kosong/null
    return dayjs(date).format("DD MMM YYYY, HH:mm:ss [WIB]");
};

const formatTableDate = (date) => {
    if (!date) return "-";
    return dayjs(date).format("DD-MMM-YYYY HH:mm:ss");
};

const props = defineProps({
    'pfmea':{
        type: Object,
        default: () => ({ data: [], links: [], per_page: 10 })
    }
})

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

const refreshTable = () => {
    isRefreshing.value = true;
    router.reload({
        only: ["customers"],
        onSuccess: () => {
            isRefreshing.value = false;
        },
        onError: () => {
            isRefreshing.value = false;
            toast.error("Failed to refresh data");
        },
    });
};

const toggleSelectAll = (event) => {
    selectedIds.value = event.target.checked
        ? props.pfmea.data.map(m => m.id)
        : [];
}

const deleteSelected = () => {
    if(selectedIds.value.length > 1) showConfirmModal.value = true;
}

const confirmAction = () => {
    deleteForm.clearErrors();

    if(!deleteReason.value || deleteReason.value.trim() === ""){
        deleteForm.setError('remark', 'This field is required');
        return;
    }

    deleteForm.ids = selectedIds.value;
    deleteForm.remark = deleteReason.value;

    deleteForm.post("/pfmea/mass-delete", {
        preserveScroll: true,
        onSuccess: () => {
            selectedIds.value = [];
            showConfirmModal.value = false;
            deleteReason.value = "";
        },
        onError: (errors) =>{
            const firstError = Object.values(errors)[0];
            toast.error(firstError || "Failed to delete items");
        }
    })
}

const cancelConfirmAction = () => {
    deleteForm.clearErrors();
    selectedIds.value = [];
    showConfirmModal.value = false;
    deleteForm.reset();
}

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

const formatFieldName = (text) => {
    if (!text) return '';
    return text.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
};
</script>

<template>
    <Head title="PFMEA List"/>

    <!-- Page Wrapper -->
    <div class="flex min-h-screen bg-slate-50 font-sans antialiased text-slate-800">
        <!-- Content wrapper -->
        <div class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">
            <!-- Heading -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                <!-- Head Title -->
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">
                        PFMEA List
                    </h1>
                    <p class="text-xs text-slate-500 mt-1">
                        Manage all PFMEA document.
                    </p>
                </div>

                <!-- Toolbar -->
                <div class="sticky top-0 z-30 bg-white/95 backdrop-blur-sm border border-slate-200 px-3 py-2 shadow-sm">
                    <div class="flex items-center justify-between w-full">
                        <div class="flex items-center gap-1.5">
                            <!-- Button New -->
                            <Link
                                href="/pfmea/create"
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
                            </Link>

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

            <!-- Searcing, per page, data status -->
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
                                    <th class="px-4 py-3">
                                        PFMEA Document No.
                                    </th>
                                    <th class="px-4 py-3">
                                        Issue Date
                                    </th>
                                    <th class="px-4 py-3">
                                        Key Date
                                    </th>
                                    <th class="px-4 py-3">
                                        Revision
                                    </th>
                                    <th class="px-4 py-3">
                                        Issuing Dept.
                                    </th>
                                    <th class="px-4 py-3">
                                        Document Scope
                                    </th>
                                    <th class="px-4 py-3">
                                        Part No
                                    </th>
                                    <th class="px-4 py-3">
                                        Part Name
                                    </th>
                                    <th class="px-4 py-3">
                                        Project Name
                                    </th>
                                    <th class="px-4 py-3">
                                        Drawing Level
                                    </th>
                                    <th class="px-4 py-3">
                                        Process Responsibility
                                    </th>
                                    <th class="px-4 py-3">
                                        Core Team
                                    </th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>