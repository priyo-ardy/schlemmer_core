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

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
    pfmea: {
        type: Object,
        default: () => ({ data: [], links: [], per_page: 10, total: 0 })
    }
});

const page = usePage();
const searchQuery = ref('');
const perPage = ref(props.pfmea.per_page || 10);
const selectedFilter = ref("all");
const selectedIds = ref([]);
const isRefreshing = ref(false);
const isSearching = ref(false);

// Modal Confirm Delete
const showConfirmModal = ref(false);
const deleteReason = ref("");

// Modal Logs History State
const isHistoryModalOpen = ref(false);
const historyLogs = ref([]);
const isLoadingHistory = ref(false);
const selectedPfmeaName = ref("");

// Modal Detail Breakdown Logs State
const showDetailModal = ref(false);
const selectedDetailLog = ref(null);

const errors = computed(() => page.props.errors || {});

watch(
    errors,
    (newErrors) => {
        if (newErrors && newErrors.error) {
            toast.error(newErrors.error);
        }
    },
    { deep: true }
);

const deleteForm = useForm({
    ids: [],
    remark: "",
});

const formatTableDate = (date) => {
    if (!date) return "-";
    return dayjs(date).format("DD-MMM-YYYY");
};

// Navigasi saat baris diklik
const goToView = (id) => {
    router.get(`/pfmea/${id}/view`);
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
        only: ["pfmea"],
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
};

const deleteSelected = () => {
    if (selectedIds.value.length > 0) showConfirmModal.value = true;
};

const confirmAction = () => {
    deleteForm.clearErrors();

    if (!deleteReason.value || deleteReason.value.trim() === "") {
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

// --- LOGIC PARSING CHANGE LOGS (PERSIS SEPERTI VIEW.VUE) ---
const openHistoryModal = async (item) => {
    selectedPfmeaName.value = item.code;
    isHistoryModalOpen.value = true;
    isLoadingHistory.value = true;
    historyLogs.value = [];

    try {
        const response = await axios.get(`/pfmea/${item.id}/logs`);
        historyLogs.value = response.data;
    } catch (err) {
        toast.error(err.response?.data?.message || "Failed to fetch change logs");
    } finally {
        isLoadingHistory.value = false;
    }
};

const openDetailsModal = (log) => {
    selectedDetailLog.value = log;
    showDetailModal.value = true;
};

const formatTeams = (teams) => {
    if (!teams || !Array.isArray(teams) || teams.length === 0) return '-';
    return teams
        .map(t => t.team?.name || t.name || t.user?.name || (t.user_id ? `User #${t.user_id}` : `User #${t.id}`))
        .filter(Boolean)
        .join(', ');
};

const getHeaderDiffs = (log) => {
    const beforeH = log.before?.header || {};
    const afterH = log.after?.header || {};

    const beforeTeams = formatTeams(log.before?.core_teams);
    const afterTeams = formatTeams(log.after?.core_teams);

    const comparisons = [
        { label: 'Code', before: beforeH.code, after: afterH.code },
        { label: 'Scope', before: beforeH.scope, after: afterH.scope },
        { label: 'Department', before: beforeH.department?.name || beforeH.department?.short_name, after: afterH.department?.name || afterH.department?.short_name },
        { label: 'Project', before: beforeH.project?.name || beforeH.project?.code, after: afterH.project?.name || afterH.project?.code },
        { label: 'Material', before: beforeH.material?.name || beforeH.material?.code, after: afterH.material?.name || afterH.material?.code },
        { label: 'Process Responsibility', before: beforeH.process_responsibility, after: afterH.process_responsibility },
        { label: 'Core Teams', before: beforeTeams, after: afterTeams }
    ];

    if (log.event_name === 'create') {
        return comparisons.filter(c => c.after && c.after !== '-').map(c => ({ label: c.label, before: '-', after: c.after }));
    }

    const changed = comparisons.filter(c => (c.before || '') !== (c.after || ''));
    return changed.length > 0 ? changed : [{ label: 'Remark', before: '-', after: '-' }];
};

const getDetailItems = (log) => {
    if (!log) return [];
    const beforeDetails = log.before?.details || [];
    const afterDetails = log.after?.details || [];

    const maxLen = Math.max(beforeDetails.length, afterDetails.length);
    const result = [];

    for (let i = 0; i < maxLen; i++) {
        const b = beforeDetails[i] || {};
        const a = afterDetails[i] || {};
        const pfB = b.process_function || b.process || {};
        const pfA = a.process_function || a.process || {};

        const rawFields = [
            { label: 'Order', before: b.order, after: a.order },
            { label: 'Process Function Name', before: pfB.name, after: pfA.name },
            { label: 'Sequence', before: pfB.sequence, after: pfA.sequence },
            { label: 'Process Parent', before: pfB.process_parent, after: pfA.process_parent },
            { label: 'Process Child', before: pfB.process_child, after: pfA.process_child },
            { 
                label: 'Revision', 
                before: pfB.revision !== undefined && pfB.revision !== null ? `Rev. ${pfB.revision}` : undefined, 
                after: pfA.revision !== undefined && pfA.revision !== null ? `Rev. ${pfA.revision}` : undefined 
            }
        ];

        const changedFields = rawFields.filter(f => {
            const valBefore = (f.before === null || f.before === undefined || f.before === '') ? '-' : String(f.before);
            const valAfter = (f.after === null || f.after === undefined || f.after === '') ? '-' : String(f.after);

            if (log.event_name === 'create') {
                return valAfter !== '-';
            }

            return valBefore !== valAfter;
        }).map(f => ({
            label: f.label,
            before: (f.before === null || f.before === undefined || f.before === '') ? '-' : String(f.before),
            after: (f.after === null || f.after === undefined || f.after === '') ? '-' : String(f.after)
        }));

        if (changedFields.length > 0) {
            result.push({
                order: a.order || b.order || (i + 1),
                updater: a.updater?.name || log.after?.header?.updater?.name || 'System User',
                date: dayjs(a.created_at || log.created_at).format('DD/MM/YYYY, HH.mm.ss'),
                event_name: log.event_name,
                fields: changedFields
            });
        }
    }

    return result;
};

const hasDetailChanges = (log) => {
    if (!log) return false;
    
    const beforeDetails = log.before?.details || [];
    const afterDetails = log.after?.details || [];

    if (log.event_name === 'create' && afterDetails.length > 0) return true;
    if (beforeDetails.length !== afterDetails.length) return true;

    for (let i = 0; i < beforeDetails.length; i++) {
        const b = beforeDetails[i] || {};
        const a = afterDetails[i] || {};
        const pfB = b.process_function || b.process || {};
        const pfA = a.process_function || a.process || {};

        if (
            b.order !== a.order ||
            b.process_id !== a.process_id ||
            pfB.name !== pfA.name ||
            pfB.sequence !== pfA.sequence ||
            pfB.process_parent !== pfA.process_parent ||
            pfB.process_child !== pfA.process_child ||
            pfB.revision !== pfA.revision
        ) {
            return true;
        }
    }
    
    return false;
};

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

const getCoreTeamNames = (coreTeams) => {
    if (!coreTeams || !coreTeams.length) return '-';
    return coreTeams.map(ct => ct.team?.name || 'N/A').join(', ');
};
</script>

<template>
    <Head title="PFMEA List" />

    <!-- Page Wrapper -->
    <div class="flex min-h-screen bg-slate-50 font-sans antialiased text-slate-800">
        <div class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">
            <!-- Heading -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">
                        PFMEA List
                    </h1>
                    <p class="text-xs text-slate-500 mt-1">
                        Manage all Process Failure Mode and Effects Analysis documents.
                    </p>
                </div>

                <!-- Toolbar -->
                <div class="bg-white/95 backdrop-blur-sm border border-slate-200 px-3 py-2 shadow-sm">
                    <div class="flex items-center gap-1.5">
                        <!-- Button New -->
                        <Link
                            href="/pfmea/create"
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

            <!-- Filter Controls -->
            <div class="bg-white border border-slate-200/80 shadow-sm p-4 mb-4 flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
                <!-- Search bar -->
                <div class="relative flex-1 max-w-md">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                        <svg v-if="isSearching" class="animate-spin h-4 w-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input
                        type="text"
                        placeholder="Search document no, material, dept, project..."
                        v-model="searchQuery"
                        class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 focus:border-blue-500 text-xs font-semibold focus:outline-none transition"
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

            <!-- Table Section -->
            <div class="bg-white border border-slate-200/80 shadow-sm flex-1 flex flex-col justify-between gap-4">
                <div class="overflow-auto max-h-[calc(100vh-320px)]">
                    <table class="w-full text-left border-collapse bg-white whitespace-nowrap text-xs">
                        <thead class="bg-blue-300 text-slate-700 uppercase tracking-wider text-[11px] font-bold border-b border-slate-200 sticky top-0 z-10">
                            <tr>
                                <th class="px-3 py-3 w-10 text-center">
                                    <input
                                        type="checkbox"
                                        @change="toggleSelectAll"
                                        :checked="selectedIds.length === props.pfmea.data.length && props.pfmea.data.length > 0"
                                        class="border-slate-300 text-blue-600 h-4 w-4 cursor-pointer"
                                    />
                                </th>
                                <th class="px-4 py-3">PFMEA Document No.</th>
                                <th class="px-4 py-3">Issue Date</th>
                                <th class="px-4 py-3 text-center">Rev</th>
                                <th class="px-4 py-3">Issuing Dept.</th>
                                <th class="px-4 py-3">Document Scope</th>
                                <th class="px-4 py-3">Part No</th>
                                <th class="px-4 py-3">Part Name</th>
                                <th class="px-4 py-3">Project</th>
                                <th class="px-4 py-3">Drawing Level</th>
                                <th class="px-4 py-3">Process Responsibility</th>
                                <th class="px-4 py-3">Core Team</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <tr v-if="props.pfmea.data.length === 0">
                                <td colspan="12" class="px-4 py-8 text-center text-slate-400 italic">
                                    No PFMEA records found.
                                </td>
                            </tr>
                            <tr
                                v-for="item in props.pfmea.data"
                                :key="item.id"
                                @click="goToView(item.id)"
                                class="hover:bg-blue-50/40 transition-colors cursor-pointer group"
                            >
                                <!-- Checkbox Column (@click.stop mencegah pemicu goToView) -->
                                <td class="px-3 py-2 text-center" @click.stop>
                                    <input
                                        type="checkbox"
                                        :value="item.id"
                                        v-model="selectedIds"
                                        class="border-slate-300 text-blue-600 h-4 w-4 cursor-pointer"
                                    />
                                </td>

                                <!-- Document No -->
                                <td class="px-4 py-3 font-black text-slate-900 tracking-tight">
                                    {{ item.code }}
                                </td>

                                <!-- Issue Date -->
                                <td class="px-4 py-2 text-slate-600">
                                    {{ formatTableDate(item.date) }}
                                </td>

                                <!-- Revision Badge Clickable (@click.stop membuka modal logs) -->
                                <td 
                                    class="px-4 py-3 cursor-pointer hover:bg-slate-50 transition-colors"
                                    @click.stop="openHistoryModal(item)">
                                    <div class="flex justify-center">
                                        <span
                                            class="px-2.5 py-0.5 text-[10px] font-bold text-blue-900 bg-blue-100 tracking-tight"
                                        >
                                            Rev. {{ item.version ?? 0 }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Issuing Dept -->
                                <td class="px-4 py-2 text-slate-700">
                                    {{ item.department?.short_name || item.department?.name || '-' }}
                                </td>

                                <!-- Document Scope -->
                                <td class="px-4 py-2 capitalize text-slate-700">
                                    {{ item.scope || '-' }}
                                </td>

                                <!-- Part No -->
                                <td class="px-4 py-2 font-semibold text-slate-800">
                                    {{ item.material?.code || '-' }}
                                </td>

                                <!-- Part Name -->
                                <td class="px-4 py-2 text-slate-700 max-w-xs truncate" :title="item.material?.name">
                                    {{ item.material?.name || '-' }}
                                </td>

                                <!-- Project Name -->
                                <td class="px-4 py-2 text-slate-700">
                                    {{ item.project?.name || item.project?.code || '-' }}
                                </td>

                                <!-- Drawing Level -->
                                <td class="px-4 py-2 text-slate-600">
                                    {{ item.material?.drawing_change || '-' }}
                                </td>

                                <!-- Process Responsibility -->
                                <td class="px-4 py-2 text-slate-600 max-w-xs truncate" :title="item.process_responsibility">
                                    {{ item.process_responsibility || '-' }}
                                </td>

                                <!-- Core Team -->
                                <td class="px-4 py-2 text-slate-600 max-w-xs truncate" :title="getCoreTeamNames(item.core_team)">
                                    {{ getCoreTeamNames(item.core_team) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Links -->
                <div v-if="props.pfmea.links && props.pfmea.links.length > 3" class="flex items-center justify-between pt-4 border-t border-slate-200 text-xs">
                    <div class="text-slate-500 font-medium">
                        Showing {{ props.pfmea.from || 0 }} to {{ props.pfmea.to || 0 }} of {{ props.pfmea.total || 0 }} entries
                    </div>
                    <div class="flex items-center gap-1">
                        <template v-for="(link, key) in props.pfmea.links" :key="key">
                            <div
                                v-if="link.url === null"
                                class="px-3 py-1.5 text-slate-400 bg-slate-50 border border-slate-200 cursor-not-allowed"
                                v-html="link.label"
                            />
                            <Link
                                v-else
                                :href="link.url"
                                class="px-3 py-1.5 border transition"
                                :class="link.active ? 'bg-blue-600 text-white border-blue-600 font-bold' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Delete Confirmation -->
    <div v-if="showConfirmModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4">
        <div class="bg-white border border-slate-200 shadow-xl max-w-md w-full p-6">
            <h3 class="text-lg font-bold text-slate-900 mb-2">Delete Confirmation</h3>
            <p class="text-xs text-slate-600 mb-4">
                Are you sure you want to delete <strong>{{ selectedIds.length }}</strong> selected PFMEA item(s)?
            </p>

            <div class="mb-4">
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Delete Reason <span class="text-rose-500">*</span></label>
                <textarea
                    v-model="deleteReason"
                    rows="3"
                    placeholder="Enter reason for deletion..."
                    class="w-full p-2.5 bg-slate-50 border border-slate-200 text-xs focus:outline-none focus:border-blue-500"
                ></textarea>
                <p v-if="deleteForm.errors.remark" class="text-xs text-rose-500 mt-1">{{ deleteForm.errors.remark }}</p>
            </div>

            <div class="flex items-center justify-end gap-2">
                <button
                    @click="cancelConfirmAction"
                    type="button"
                    class="px-4 py-2 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition"
                >
                    Cancel
                </button>
                <button
                    @click="confirmAction"
                    :disabled="deleteForm.processing"
                    type="button"
                    class="px-4 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 transition disabled:opacity-50"
                >
                    {{ deleteForm.processing ? 'Deleting...' : 'Confirm Delete' }}
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL 1: PROCESS FUNCTION REVISION HISTORY TIMELINE -->
    <Teleport to="body">
        <div v-if="isHistoryModalOpen" class="fixed inset-0 z-[9990] bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white border border-slate-200 shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col font-sans">
                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 bg-white sticky top-0 z-10">
                    <div class="flex items-center gap-3">
                        <div class="p-1.5 bg-blue-50 text-blue-600 border border-blue-100 rounded-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-sm font-black text-slate-900 tracking-wider uppercase">
                            PROCESS FUNCTION REVISION HISTORY : {{ selectedPfmeaName || 'DOCUMENT' }}
                        </h3>
                    </div>
                    <button type="button" @click="isHistoryModalOpen = false" class="text-slate-400 hover:text-slate-700 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Timeline Content Body -->
                <div class="p-6 overflow-y-auto space-y-6 bg-slate-50/50">
                    <div v-if="isLoadingHistory" class="py-12 text-center text-xs text-slate-500 font-medium">
                        Loading logs history...
                    </div>
                    <div v-else-if="historyLogs.length === 0" class="text-center py-12 text-slate-400 font-medium text-xs">
                        No revision logs found for this document.
                    </div>
                    <div v-else class="relative border-l-2 border-slate-200 ml-3 pl-6 space-y-6">
                        <div v-for="log in historyLogs" :key="log.id" class="relative group">
                            <!-- Timeline Marker Dot -->
                            <div class="absolute -left-[31px] top-3.5 h-3 w-3 bg-blue-600 border-2 border-white shadow-sm"></div>

                            <!-- Card Item -->
                            <div class="bg-white border border-slate-200 shadow-xs p-5 space-y-4">
                                <!-- Meta Header -->
                                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 text-[10px] font-mono font-bold text-slate-600 border border-slate-300 uppercase">
                                            REV. {{ log.after?.header?.version ?? log.revision ?? 0 }}
                                        </span>
                                        <span class="px-2 py-0.5 text-[10px] font-bold uppercase text-blue-700 bg-blue-100">
                                            {{ log.event_name }}
                                        </span>
                                        <span class="text-xs font-black text-slate-800 ml-1">
                                            {{ log.after?.header?.updater?.name || log.before?.header?.updater?.name || 'System User' }}
                                        </span>
                                    </div>
                                    <span class="text-xs font-semibold text-slate-400">
                                        {{ dayjs(log.created_at).format('DD/MM/YYYY, HH.mm.ss') }}
                                    </span>
                                </div>

                                <!-- Change Reason -->
                                <div>
                                    <span class="block text-[10px] font-extrabold uppercase text-slate-400 tracking-wider">
                                        CHANGE REASON
                                    </span>
                                    <p class="text-xs font-bold text-slate-800 mt-1">
                                        {{ log.change_reason || 'No description provided.' }}
                                    </p>
                                </div>

                                <!-- Header Diff Table -->
                                <div class="border border-slate-100 bg-white">
                                    <table class="w-full text-left border-collapse">
                                        <thead>
                                            <tr class="border-b border-slate-100 bg-slate-50 text-[10px] font-black uppercase tracking-wider">
                                                <th class="py-2 px-3 text-slate-400 w-1/3">FIELD DATA</th>
                                                <th class="py-2 px-3 text-rose-500 w-1/3">DATA BEFORE</th>
                                                <th class="py-2 px-3 text-emerald-600 w-1/3">DATA AFTER</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 text-xs">
                                            <tr v-for="diff in getHeaderDiffs(log)" :key="diff.label">
                                                <td class="py-2 px-3 font-semibold text-slate-600">{{ diff.label }}</td>
                                                <td class="py-2 px-3">
                                                    <span v-if="diff.before !== '-'" class="bg-rose-100/80 text-rose-700 line-through px-1.5 py-0.5 font-mono text-[11px]">
                                                        {{ diff.before }}
                                                    </span>
                                                    <span v-else class="text-slate-400 font-mono">-</span>
                                                </td>
                                                <td class="py-2 px-3">
                                                    <span v-if="diff.after !== '-'" class="bg-emerald-100/80 text-emerald-800 font-bold px-1.5 py-0.5 font-mono text-[11px]">
                                                        {{ diff.after }}
                                                    </span>
                                                    <span v-else class="text-slate-400 font-mono">-</span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Trigger Show Details Modal (Hanya muncul jika ada perubahan pada details) -->
                                <div v-if="hasDetailChanges(log)" class="pt-2 border-t border-slate-100/60 mt-4">
                                    <button type="button" @click="openDetailsModal(log)" class="text-xs font-bold text-blue-600 hover:text-blue-800 hover:underline transition-all">
                                        Show Details ...
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- MODAL 2: HISTORY DETAILS (DETAILS BREAKDOWN) -->
    <Teleport to="body">
        <div v-if="showDetailModal" class="fixed inset-0 z-[9995] bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white border border-slate-200 shadow-2xl w-full max-w-5xl max-h-[90vh] flex flex-col font-sans">
                <!-- Modal Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 bg-white sticky top-0 z-10">
                    <div class="flex items-center gap-3">
                        <div class="p-1.5 bg-blue-50 text-blue-600 border border-blue-100 rounded-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-sm font-black text-slate-900 tracking-wider uppercase flex items-center gap-2">
                            HISTORY DETAILS 
                            <span class="bg-blue-100 text-blue-700 text-[10px] font-mono px-2 py-0.5 rounded-xs uppercase">
                                REV. {{ selectedDetailLog?.after?.header?.version ?? selectedDetailLog?.revision ?? 1 }}
                            </span>
                        </h3>
                    </div>
                    <button type="button" @click="showDetailModal = false" class="text-slate-400 hover:text-slate-700 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 overflow-y-auto space-y-6 bg-slate-50/50">
                    <!-- STATE BILA TIDAK ADA FIELD DETAILS YANG BERUBAH -->
                    <div v-if="getDetailItems(selectedDetailLog).length === 0" class="text-center py-12 text-slate-400 font-medium text-xs bg-white border border-slate-200">
                        No process function details were changed in this revision.
                    </div>

                    <!-- HANYA MENAMPILKAN CARD DETAIL YANG PUNYA PERUBAHAN DATA -->
                    <div v-else v-for="item in getDetailItems(selectedDetailLog)" :key="item.order" class="bg-white border border-slate-200 shadow-xs">
                        <div class="flex items-center justify-between bg-slate-100/70 px-4 py-3 border-b border-slate-200">
                            <div>
                                <h4 class="text-xs font-black text-slate-800">Detail Order {{ item.order }}</h4>
                                <p class="text-[10px] text-slate-500 font-medium mt-0.5">
                                    Revision {{ selectedDetailLog?.after?.header?.version ?? selectedDetailLog?.revision ?? 1 }} • {{ item.updater }} • {{ item.date }}
                                </p>
                            </div>
                            <span class="px-2 py-0.5 text-[10px] font-bold uppercase text-blue-700 bg-blue-100">
                                {{ item.event_name }}
                            </span>
                        </div>

                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-slate-100 bg-slate-50 text-[10px] font-black uppercase tracking-wider">
                                    <th class="py-2.5 px-4 text-slate-400 w-1/3">FIELD DATA</th>
                                    <th class="py-2.5 px-4 text-rose-500 w-1/3">DATA BEFORE</th>
                                    <th class="py-2.5 px-4 text-emerald-600 w-1/3">DATA AFTER</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-xs font-mono">
                                <tr v-for="field in item.fields" :key="field.label">
                                    <td class="py-2 px-4 font-sans font-bold text-slate-600">{{ field.label }}</td>
                                    <td class="py-2 px-4">
                                        <span v-if="field.before !== '-'" class="bg-rose-100/80 text-rose-700 line-through px-1.5 py-0.5">
                                            {{ field.before }}
                                        </span>
                                        <span v-else class="text-slate-400">-</span>
                                    </td>
                                    <td class="py-2 px-4">
                                        <span v-if="field.after !== '-'" class="bg-emerald-100/80 text-emerald-800 font-bold px-1.5 py-0.5">
                                            {{ field.after }}
                                        </span>
                                        <span v-else class="text-slate-400">-</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>