<script setup>
import "vue3-toastify/dist/index.css";
import { ref, watch, computed } from "vue";
import { Head, router, Link, usePage, useForm } from "@inertiajs/vue3";
import { toast } from "vue3-toastify";
import { debounce } from "lodash";
import axios from "axios";
import dayjs from "dayjs";
import "dayjs/locale/id";
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

dayjs.locale("id");

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
    approvals:{
        type: Object,
        default: () => ({ data: [], links: [], per_page: 10, total: 0})
    }
});

const page = usePage();
const searchQuery = ref('');
const perPage = ref(props.approvals.per_page || 10);
const selectedFilter = ref("all");
const selectedIds = ref([]);
const isRefreshing = ref(false);
const isSearching = ref(false);
const showConfirmModal = ref(false);
const deleteReason = ref("");

const isHistoryModalOpen = ref(false);
const historyLogs = ref([]);
const isLoadingHistory = ref(false);
const selectedApprovalName = ref(false);

const showDetailsModal = ref(false);
const selectedDetailLogs = ref(null);

const errors = computed(() => page.props.errors || {});

watch(
    errors,
    (newErrors) => {
        if(newErrors && newErrors.error){
            toast.error(newErrors.error);
        }
    },{
        deep:true
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
        );
    }, 350)
);

const deleteForm = useForm({
    ids: [],
    reason: "",
});

const formatTableDate = (date) => {
    if (!date) return "-";
    return dayjs(date).format("DD-MMM-YYYY");
};

// Navigasi saat baris diklik
const goToView = (id) => {
    router.get(`/approval-setup/${id}/view`);
};

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
    );
};

const refreshTable = () => {
    isRefreshing.value = true;
    router.reload({
        only: ["approval"],
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
        ? props.approval.data.map(m => m.id)
        : [];
};

const deleteSelected = () => {
    if (selectedIds.value.length > 0) showConfirmModal.value = true;
};

const confirmAction = () => {
    deleteForm.clearErrors();

    if (!deleteReason.value || deleteReason.value.trim() === "") {
        deleteForm.setError('reason', 'This field is required');
        return;
    }

    deleteForm.ids = selectedIds.value;
    deleteForm.reason = deleteReason.value;

    deleteForm.post("/approval-setup/mass-delete", {
        preserveScroll: true,
        onSuccess: () => {
            selectedIds.value = [];
            showConfirmModal.value = false;
            deleteReason.value = "";
            toast.success("Successfully deleted selected item(s)");
        },
        onError: (errs) => {
            const firstError = Object.values(errs)[0];
            toast.error(firstError || "Failed to delete items");
        }
    });
};

const cancelConfirmAction = () => {
    deleteForm.clearErrors();
    showConfirmModal.value = false;
    deleteReason.value = "";
    deleteForm.reset();
};

const openHistoryModal = async (item) => {
    selectedApprovalName.value = item.code;
    isHistoryModalOpen.value = true;
    isLoadingHistory.value = true;
    historyLogs.value = [];

    try {
        const response = await axios.get(`/approval-setup/${item.id}/logs`);
        historyLogs.value = response.data;
    } catch (err) {
        toast.error(err.response?.data?.message || "Failed to fetch change logs");
    } finally {
        isLoadingHistory.value = false;
    }
};

const openDetailsModal = (log) => {
    selectedDetailLogs.value = log;
    showDetailsModal.value = true;
};

const getHeaderDiffs = (log) => {}

const getDetailItems = (log) => {}

const hasDetailChanges = (log) => {}
</script>

<template>
    <Head title="Approval Setup List"/>

    <div class="flex min-h-screen bg-slate-50 font-sans antialiased text-slate-800">
        <div class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">
            <!-- Heading -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">
                        Approval Setup List
                    </h1>
                    <p class="text-xs text-slate-500 mt-1">
                        Manage application approval process
                    </p>
                </div>

                <!-- Toolbar -->
                <div class="sticky top-0 z-30 bg-white/95 backdrop-blur-sm border border-slate-200 px-3 py-2 shadow-sm">
                    <div class="flex items-center gap-1.5">
                        <!-- Button New -->
                        <Link
                            v-if="$can('create approval-setup')"
                            href="/approval-setup/create"
                            class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-bold text-blue-600 bg-blue-50 border border-blue-100 transition-all hover:bg-blue-100 active:scale-95 shadow-sm"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            <span>New</span>
                        </Link>

                        <!-- Button Refresh -->
                        <button
                            @click="refreshTable"
                            :disabled="isRefreshing"
                            type="button"
                            class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-bold text-slate-600 bg-slate-50 border border-slate-200 transition-all hover:bg-slate-100 active:scale-95 shadow-sm disabled:opacity-60"
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

                        <div v-if="selectedIds.length > 0" class="w-px h-4 bg-slate-200 mx-1"></div>

                        <!-- Button Delete (Hanya aktif jika ada checkbox yang dipilih) -->
                        <button
                            v-if="$can('mass_delete approval-setup')"
                            @click="deleteSelected"
                            :disabled="selectedIds.length === 0"
                            type="button"
                            class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-bold text-rose-600 bg-rose-50 border border-rose-100 transition-all hover:bg-rose-100 active:scale-95 shadow-sm disabled:opacity-40 disabled:pointer-events-none"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            <span>Delete ({{ selectedIds.length }})</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-slate-200/80 shadow-sm p-4 mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
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
                <div class="flex items-center gap-4">
                    <!-- Per page -->
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Per Page:</label>
                        <select
                            v-model="perPage"
                            @change="updatePerPage"
                            class="px-3 py-2 bg-white border border-slate-200 text-slate-700 text-xs font-semibold focus:outline-none focus:border-blue-500 cursor-pointer transition"
                        >
                            <option :value="10">10</option>
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                            <option :value="100">100</option>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Status:</label>
                        <select
                            v-model="selectedFilter"
                            @change="updatePerPage"
                            class="px-3 py-2 bg-white border border-slate-200 text-slate-700 text-xs font-semibold focus:outline-none focus:border-blue-500 cursor-pointer transition"
                        >
                            <option value="all">All</option>
                            <option value="enable">Enabled</option>
                            <option value="disable">Disabled</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-slate-200/80 shadow-sm flex-1 flex flex-col justify-between gap-4">
                <div class="overflow-auto max-h-[calc(100vh-320px)]">
                    <table class="w-full text-left border-collapse bg-white whitespace-nowrap text-xs">
                        <thead class="bg-blue-300 text-slate-700 uppercase tracking-wider text-[11px] font-bold border-b border-slate-200 sticky top-0 z-10">
                            <tr>
                                <th class="px-3 py-3 w-10 text-center">
                                    <input
                                        type="checkbox"
                                        @change="toggleSelectAll"
                                        :checked="selectedIds.length === props.approvals.data.length && props.approvals.data.length > 0"
                                        class="border-slate-300 text-blue-600 h-4 w-4 cursor-pointer"
                                    />
                                </th>
                                <th class="px-4 py-3">Module Name</th>
                                <th class="px-4 py-3">Total Approver</th>
                                <th class="px-4 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs text-slate-600">
                            <tr
                                v-for="approval in approvals.data"
                                :key="approval.id"
                                @click="openEditDrawer(approval)"
                                class="hover:bg-blue-50 transition-colors cursor-pointer"
                            >
                                <td
                                    class="px-4 py-3 text-center"
                                    @click.stop
                                >
                                    <input
                                        type="checkbox"
                                        v-model="selectedIds"
                                        :value="approval.id"
                                        class="border-slate-300 text-blue-600 h-4 w-4 cursor-pointer"
                                    />
                                </td>
                                <td class="px-4 py-3">
                                    {{ approval.module }}
                                </td>
                                <td class="px-4 py-3">
                                    {{ approval.details?.length || 0 }}
                                </td>
                                <td class="px-4 py-3">
                                    {{ approval.is_active }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="text-xs text-slate-500">Showing {{ approvals.from }} to {{ approvals.to }} of {{ approvals.total }}</div>
                    <div class="flex items-center gap-1">
                        <template v-for="(link, index) in approvals.links" :key="index">
                            <Link v-if="link.url" :href="link.url" v-html="link.label" class="px-3 py-1.5 text-xs font-bold transition-all" :class="link.active ? 'bg-blue-600 text-white shadow-md' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'" />
                            <span v-else v-html="link.label" class="px-3 py-1.5 text-xs text-slate-400"></span>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>