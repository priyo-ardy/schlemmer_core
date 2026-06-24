<script setup>
import { ref, computed, watch } from "vue";
import { Head, useForm, router } from "@inertiajs/vue3";
import AuthenticatedLayout from "../../Layouts/AuthenticatedLayout.vue";
import { toast } from "vue3-toastify";
import "vue3-toastify/dist/index.css";
import dayjs from "dayjs";
import "dayjs/locale/id";

dayjs.locale("id");

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
    trashItems: {
        type: Array,
        default: () => []
    }
});

// --- CONTROLS STATE ---
const searchQuery = ref("");
const selectedFilter = ref("all");
const perPage = ref(10); // State untuk batas baris data
const currentPage = ref(1); // State halaman aktif
const isRefreshing = ref(false);

// --- RESTORE MODAL STATE ---
const isRestoreModalOpen = ref(false);
const restoreReason = ref("");
const activeItem = ref(null);

const restoreForm = useForm({
    resource: "",
    id: null,
    remark: ""
});

const formatTableDate = (date) => {
    if (!date) return "-";
    return dayjs(date).format("DD-MMM-YYYY HH:mm:ss");
};

// --- DATA FILTERING LOGIC ---
const filteredTrashItems = computed(() => {
    return (props.trashItems || []).filter((item) => {
        const matchesSearch = item.identifier.toLowerCase().includes(searchQuery.value.toLowerCase());
        
        if (selectedFilter.value !== "all") {
            return matchesSearch && item.resource === selectedFilter.value;
        }
        return matchesSearch;
    });
});

// --- CLIENT-SIDE PAGINATION LOGIC ---
const totalEntries = computed(() => filteredTrashItems.value.length);
const totalPages = computed(() => Math.ceil(totalEntries.value / perPage.value) || 1);

const fromEntry = computed(() => {
    if (totalEntries.value === 0) return 0;
    return (currentPage.value - 1) * perPage.value + 1;
});

const toEntry = computed(() => {
    const currentMax = currentPage.value * perPage.value;
    return currentMax > totalEntries.value ? totalEntries.value : currentMax;
});

// Potong data untuk ditampilkan per halaman aktif
const paginatedTrashItems = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    const end = start + perPage.value;
    return filteredTrashItems.value.slice(start, end);
});

// Reset halaman ke 1 jika user melakukan pencarian atau ganti modul filter
watch([searchQuery, selectedFilter, perPage], () => {
    currentPage.value = 1;
});

// --- ACTION METHODS ---
const refreshTable = () => {
    isRefreshing.value = true;
    router.reload({
        only: ["trashItems"],
        onSuccess: () => {
            isRefreshing.value = false;
            toast.success("Refresh Success");
        },
        onError: () => {
            isRefreshing.value = false;
            toast.error("Failed to refresh data");
        }
    });
};

const openRestoreModal = (item) => {
    activeItem.value = item;
    restoreReason.value = "";
    restoreForm.clearErrors();
    isRestoreModalOpen.value = true;
};

const closeRestoreModal = () => {
    isRestoreModalOpen.value = false;
    activeItem.value = null;
    restoreReason.value = "";
};

const confirmRestore = () => {
    if (!activeItem.value) return;

    if (!restoreReason.value.trim()) {
        restoreForm.setError('remark', 'Please fill the restoration reason');
        return;
    }

    restoreForm.resource = activeItem.value.resource;
    restoreForm.id = activeItem.value.id;
    restoreForm.remark = restoreReason.value.trim();

    restoreForm.post("/recycle-bin/restore", {
        preserveScroll: true,
        onSuccess: () => {
            closeRestoreModal();
            toast.success("Data restored successfully");
        },
        onError: () => {
            toast.error("Failed to restore data");
        }
    });
};
</script>

<template>
    <Head title="System Recycle Bin" />

    <div class="flex min-h-screen bg-slate-50 font-sans antialiased text-slate-800">
        <div class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">
            
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">
                        System Recycle Bin
                    </h1>
                    <p class="text-xs text-slate-500 mt-1">
                        Audit deleted credentials, trace data components, and restore master data blocks safely.
                    </p>
                </div>

                <div class="bg-white border border-slate-200 px-3 py-2 shadow-sm">
                    <div class="flex items-center justify-between w-full">
                        <div class="flex items-center gap-1.5">
                            <button
                                type="button"
                                @click="refreshTable"
                                :disabled="isRefreshing"
                                class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-bold text-slate-600 bg-orange-50 border border-slate-200 transition-all hover:bg-orange-100 active:scale-95 shadow-sm disabled:opacity-60"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" :class="isRefreshing ? 'animate-spin text-blue-600' : 'text-slate-500'" class="h-3.5 w-3.5 transition-colors duration-150" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.772 0l3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                </svg>
                                <span>{{ isRefreshing ? "Refreshing..." : "Refresh" }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-slate-200/80 shadow-sm p-4 mb-6 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="relative flex-1 max-w-md">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input
                        type="text"
                        v-model="searchQuery"
                        placeholder="Search dynamically..."
                        class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 focus:border-blue-500 text-xs font-semibold focus:outline-none transition"
                    />
                </div>
                
                <div class="flex flex-wrap items-center gap-4 sm:gap-6">
                    <div class="flex items-center gap-2">
                        <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Per Page:</label>
                        <select
                            v-model="perPage"
                            class="px-3 py-2 bg-white border border-slate-200 text-slate-700 text-xs font-semibold focus:outline-none cursor-pointer min-w-[70px]"
                        >
                            <option :value="10">10</option>
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                            <option :value="100">100</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2">
                        <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Module Group:</label>
                        <select
                            v-model="selectedFilter"
                            class="px-3 py-2 bg-white border border-slate-200 text-slate-700 text-xs font-semibold focus:outline-none cursor-pointer min-w-[120px]"
                        >
                            <option value="all">All</option>
                            <option value="Material">Material</option>
                            <option value="Customer">Customer</option>
                            <option value="User">User</option>
                            <option value="Unit">Unit</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-slate-200/80 shadow-sm overflow-hidden mb-6 flex flex-col">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left">
                        <thead class="bg-blue-300 text-[11px] font-bold text-slate-700 uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-4 w-50">Module Resource</th>
                                <th class="px-6 py-4">Data Identifier (Code / Name)</th>
                                <th class="px-6 py-4 w-100">Deleted At</th>
                                <th class="px-6 py-4 w-50 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs font-medium text-slate-700">
                            <tr v-if="filteredTrashItems.length === 0">
                                <td colspan="4" class="px-6 py-12 text-center text-slate-400 font-bold uppercase tracking-wider">
                                    No trashed records found in this configuration.
                                </td>
                            </tr>

                            <tr
                                v-else
                                v-for="item in paginatedTrashItems"
                                :key="item.uuid"
                                class="hover:bg-slate-50/80 transition"
                            >
                                <td class="px-6 py-4 font-mono font-bold text-[10px]">
                                    <span :class="{
                                        'bg-blue-50 text-blue-700 border-blue-200': item.resource === 'Material',
                                        'bg-purple-50 text-purple-700 border-purple-200': item.resource === 'Customer',
                                        'bg-amber-50 text-amber-700 border-amber-200': item.resource === 'User',
                                        'bg-indigo-50 text-indigo-700 border-indigo-200': item.resource === 'Unit',
                                    }" class="px-2 py-0.5 border uppercase tracking-wide">
                                        {{ item.resource }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm font-bold text-slate-900">
                                    {{ item.identifier }}
                                </td>
                                <td class="px-6 py-4 font-mono text-slate-500 text-xs">
                                    {{ formatTableDate(item.deleted_at) }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button
                                        type="button"
                                        @click="openRestoreModal(item)"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold text-emerald-600 bg-emerald-50 border border-emerald-100 transition-all hover:bg-emerald-100 active:scale-95 shadow-sm"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                        </svg>
                                        &nbsp;
                                        Restore
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="filteredTrashItems.length > 0" class="border-t border-slate-200/80 px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-slate-50/50">
                    <span class="text-xs text-slate-500 font-medium font-mono">
                        Showing {{ fromEntry }} to {{ toEntry }} of {{ totalEntries }} entries
                    </span>
                    
                    <div class="flex items-center gap-1.5 select-none">
                        <button
                            type="button"
                            @click="currentPage > 1 ? currentPage-- : null"
                            :disabled="currentPage === 1"
                            class="px-2.5 py-1.5 text-xs font-bold border border-slate-200 bg-white hover:bg-slate-50 disabled:opacity-40 transition-all text-slate-600 font-mono active:scale-95"
                        >
                            &larr; Prev
                        </button>

                        <button
                            v-for="pageIndex in totalPages"
                            :key="pageIndex"
                            type="button"
                            @click="currentPage = pageIndex"
                            :class="pageIndex === currentPage 
                                ? 'bg-slate-800 text-white border-slate-800 font-black' 
                                : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50 font-semibold'"
                            class="px-3 py-1.5 text-xs border font-mono transition-all active:scale-95 rounded-sm"
                        >
                            {{ pageIndex }}
                        </button>

                        <button
                            type="button"
                            @click="currentPage < totalPages ? currentPage++ : null"
                            :disabled="currentPage === totalPages"
                            class="px-2.5 py-1.5 text-xs font-bold border border-slate-200 bg-white hover:bg-slate-50 disabled:opacity-40 transition-all text-slate-600 font-mono active:scale-95"
                        >
                            Next &rarr;
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95"
    >
        <div
            v-show="isRestoreModalOpen"
            class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 backdrop-blur-sm p-4"
        >
            <div class="bg-white w-full max-w-sm border border-slate-200 shadow-2xl p-6">
                <div class="flex flex-col text-left">
                    <h3 class="text-lg font-black text-slate-900 mb-1">
                        Confirm Restoration
                    </h3>
                    <p class="text-xs text-slate-500 mb-4">
                        Are you sure you want to restore <span class="font-bold text-slate-900">{{ activeItem?.identifier }}</span>? This data will return to its master database module.
                    </p>

                    <div class="mb-6">
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">
                            Reason for Restoration <span class="text-rose-500">*</span>
                        </label>
                        <textarea
                            v-model="restoreReason"
                            rows="2"
                            @input="restoreForm.clearErrors('remark')"
                            :class="restoreForm.errors.remark ? 'border-rose-500' : 'border-slate-300'"
                            class="w-full p-2 border text-xs focus:outline-none focus:border-blue-500 transition-all"
                            placeholder="e.g. Data needed for PFMEA calculation, wrong input deletion..."
                        ></textarea>
                        <p v-if="restoreForm.errors.remark" class="text-[10px] text-rose-600 mt-1 font-bold">
                            {{ restoreForm.errors.remark }}
                        </p>
                    </div>

                    <div class="flex gap-3 w-full">
                        <button
                            type="button"
                            @click="closeRestoreModal"
                            class="flex-1 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            @click="confirmRestore"
                            :disabled="restoreForm.processing"
                            class="flex-1 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs transition"
                        >
                            {{ restoreForm.processing ? "Processing..." : "Yes, Restore" }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>