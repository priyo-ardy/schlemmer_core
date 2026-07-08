<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm, router, Link, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { toast } from 'vue3-toastify';
import axios from 'axios';
import dayjs from 'dayjs';
import 'dayjs/locale/id';

defineOptions({ layout: AuthenticatedLayout });

// PROPS: 100% Mengikuti struktur parameter dari file referensi Anda
const props = defineProps({
    allIds: Array,
    users: Array,
    header: {
        type: Object,
        default: () => ({})
    },
    show: Boolean,
    logs: Array,
    details: {
        type: Array,
        default: () => []
    },
    page_title: {
        type: String,
        default: 'Master Data / Project Management / List of Project / View'
    }
});

const page = usePage();
const errors = computed(() => page.props.errors || {});
const selectedRowIndex = ref(null);

watch(
    errors,
    (newErrors) => {
        if(newErrors && newErrors.error){
            toast.error(newErrors.error);
        }
    },
    { deep: true }
);

defineEmits(['close']);

// STATES: Diambil langsung dari file referensi Anda
const isEditing = ref(false);
const showModal = ref(false);
const showDetailModal = ref(false);
const loadingLogs = ref(false);
const logs = ref([]);
const selectedLogDetail = ref([]);
const detailLogs = ref([]);
const isFetching = ref(false);
const selectedLogRemark = ref([]);
const showConfirmModal = ref(false);

// FORMAT DATE SYSTEM
dayjs.locale("id");
const formatLogDate = (date) => {
    if (!date) return "-";
    return dayjs(date).format("DD MMM YYYY, HH:mm:ss [WIB]");
};

const formatTableDate = (date) => {
    if (!date) return "-";
    return dayjs(date).format("DD-MMM-YYYY HH:mm:ss");
};

// LOGIKA NAVIGASI HALAMAN (NEXT / PREVIOUS) DARI FILE REFERENSI ANDA
const currentIndex = computed(() => {
    if (!props.allIds || !props.header?.id) return -1;
    return props.allIds.indexOf(props.header.id);
});

const hasPrevious = computed(() => currentIndex.value > 0);
const hasNext = computed(() => props.allIds && currentIndex.value < props.allIds.length - 1);

const navigateTo = (direction) => {
    let targetIndex = currentIndex.value;
    if (direction === 'prev' && hasPrevious.value) {
        targetIndex--;
    } else if (direction === 'next' && hasNext.value) {
        targetIndex++;
    }

    if (targetIndex !== currentIndex.value) {
        const targetId = props.allIds[targetIndex];
        // Redirect ke halaman project view sesuai ID target navigasi
        router.visit(`/projects/view/${targetId}`);
    }
};

// LOGIKA ACTION TOOLBAR DARI FILE REFERENSI ANDA
const openLogs = async (id) => {
    isFetching.value = true;
    try {
        const response = await axios.get(`/projects/logs/${id}`);
        logs.value = response.data;
        showModal.value = true;
    } catch(error) {
        toast.error("Failed to load log data");
    } finally {
        isFetching.value = false;
    }
};

const viewDetailLog = async (logId) => {
    loadingLogs.value = true;
    try {
        const response = await axios.get(`/projects/logs/detail/${logId}`);
        detailLogs.value = response.data.details || [];
        selectedLogDetail.value = response.data.log || {};
        selectedLogRemark.value = response.data.log?.remark || '-';
        showDetailModal.value = true;
    } catch(error) {
        toast.error("Failed to load detail log data");
    } finally {
        loadingLogs.value = false;
    }
};

const closeDetailModal = () => {
    showDetailModal.value = false;
    detailLogs.value = [];
    selectedLogDetail.value = [];
};

const onDeleteProject = () => {
    showConfirmModal.value = true;
};

const confirmDelete = () => {
    router.delete(`/projects/${props.header.id}`, {
        onSuccess: () => {
            toast.success("Project data deleted successfully");
            showConfirmModal.value = false;
        },
        onError: (err) => {
            toast.error(err.error || "Failed to delete project");
        }
    });
};

// Label Mappings kustom untuk Project
const confidentCategory = [
    { value: 'public', label: 'Public' },
    { value: 'internal', label: 'Internal Use' },
    { value: 'confidential', label: 'Confidential' },
    { value: 'strictly_confidential', label: 'Strictly Confidential' },
];

const confidentialityLabel = computed(() => {
    if (!props.header?.confidentiality_level) return "-";
    const match = confidentCategory.find(c => c.value === props.header.confidentiality_level.toLowerCase());
    return match ? match.label : props.header.confidentiality_level;
});

const statusCategory = [
    { value: 'planning', label: 'Planning' },
    { value: 'design_dev', label: 'Design Development' },
    { value: 'process_dev', label: 'Process Development' },
    { value: 'validation', label: 'Validation' },
    { value: 'ppap_submitted', label: 'PPAP Submitted' },
    { value: 'ppap_approved', label: 'PPAP Approved' },
    { value: 'mass_production', label: 'Mass Production' },
    { value: 'change_request', label: 'Change Request' },
    { value: 'discontinued', label: 'Discontinued' },
];

const statusLabel = computed(() => {
    if (!props.header?.status) return "-";
    const match = statusCategory.find(s => s.value === props.header.status.toLowerCase());
    return match ? match.label : props.header.status;
});
</script>

<template>
    <Head :title="`View Project - ${header?.code || ''}`"/>
    <div class="flex min-h-screen bg-slate-50 font-sans antialiased text-slate-800">
        <div class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto p-6">
            
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8 border-b border-slate-200 pb-4">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">
                        View Project Details
                    </h1>
                    <p class="text-xs text-slate-500 mt-1">{{ page_title }}</p>
                </div>

                <div class="sticky top-0 z-50 bg-white border border-slate-200 p-1.5 shadow-xs rounded-sm flex items-center justify-between gap-6">
                    <div class="flex items-center gap-1">
                        <Link href="/projects" class="group flex items-center gap-1.5 px-3 py-2 text-xs font-bold text-slate-500 hover:text-blue-600 hover:bg-slate-50 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            <span class="hidden sm:inline">Back</span>
                        </Link>
                        
                        <div class="w-px h-6 bg-slate-200 mx-1"></div>
                        
                        <button 
                            @click="navigateTo('prev')" 
                            :disabled="!hasPrevious" 
                            class="p-2 border border-slate-200 hover:bg-slate-50 disabled:opacity-30 disabled:hover:bg-white text-slate-600 transition-all"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                        </button>
                        
                        <div class="text-xs font-mono font-bold px-3 text-slate-500 select-none">
                            {{ currentIndex + 1 }} / {{ allIds ? allIds.length : 0 }}
                        </div>

                        <button 
                            @click="navigateTo('next')" 
                            :disabled="!hasNext" 
                            class="p-2 border border-slate-200 hover:bg-slate-50 disabled:opacity-30 disabled:hover:bg-white text-slate-600 transition-all"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                        </button>

                        <div class="w-px h-6 bg-slate-200 mx-1"></div>

                        <button @click="openLogs(header?.id)" :disabled="isFetching" class="inline-flex items-center gap-1.5 px-3 py-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs transition-all disabled:opacity-50">
                            <svg v-if="isFetching" class="animate-spin h-3.5 w-3.5 text-slate-500" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle></svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            Log
                        </button>

                        <Link :href="`/projects/edit/${header?.id}`" class="inline-flex items-center gap-1.5 px-3 py-2 bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                            Edit
                        </Link>

                        <button @click="onDeleteProject" class="inline-flex items-center gap-1.5 px-3 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            Delete
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="bg-white border border-slate-200/80 shadow-xs p-4 mb-6">
                <div class="grid grid-cols-4 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Project Code</label>
                        <input type="text" :value="header?.code" readonly class="w-full pl-3 pr-3 py-2 bg-slate-50 border border-slate-200 text-xs font-semibold text-slate-600 outline-none" />
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Project Name</label>
                        <input type="text" :value="header?.name" readonly class="w-full pl-3 pr-3 py-2 bg-slate-50 border border-slate-200 text-xs font-semibold text-slate-600 outline-none" />
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Customer</label>
                        <div class="w-full pl-3 pr-3 py-2.5 bg-slate-50 border border-slate-200 text-xs font-semibold text-slate-600 min-h-[38px]">
                            {{ header?.customer ? `[${header.customer.code}] - ${header.customer.name}` : header?.customer_id || '-' }}
                        </div>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Vehicle Model</label>
                        <input type="text" :value="header?.vehicle_model || '-'" readonly class="w-full pl-3 pr-3 py-2 bg-slate-50 border border-slate-200 text-xs font-semibold text-slate-600 outline-none" />
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Main Part Number</label>
                        <input type="text" :value="header?.main_part_number || '-'" readonly class="w-full pl-3 pr-3 py-2 bg-slate-50 border border-slate-200 text-xs font-semibold text-slate-600 outline-none" />
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Main Part Name</label>
                        <input type="text" :value="header?.main_part_name || '-'" readonly class="w-full pl-3 pr-3 py-2 bg-slate-50 border border-slate-200 text-xs font-semibold text-slate-600 outline-none" />
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">APQP Phase</label>
                        <input type="text" :value="header?.apqp_phase || '-'" readonly class="w-full pl-3 pr-3 py-2 bg-slate-50 border border-slate-200 text-xs font-semibold text-slate-600 outline-none" />
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Project Status</label>
                        <div class="w-full pl-3 pr-3 py-2.5 bg-slate-50 border border-slate-200 text-xs font-semibold text-slate-600 min-h-[38px]">
                            {{ statusLabel }}
                        </div>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Kick Off Date</label>
                        <div class="w-full pl-3 pr-3 py-2 bg-slate-50 border border-slate-200 text-xs font-semibold text-slate-600 min-h-[34px]">
                            {{ formatTableDate(header?.kick_off_date) }}
                        </div>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Target Proto Date</label>
                        <div class="w-full pl-3 pr-3 py-2 bg-slate-50 border border-slate-200 text-xs font-semibold text-slate-600 min-h-[34px]">
                            {{ formatTableDate(header?.target_proto_date) }}
                        </div>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Target APQP Date</label>
                        <div class="w-full pl-3 pr-3 py-2 bg-slate-50 border border-slate-200 text-xs font-semibold text-slate-600 min-h-[34px]">
                            {{ formatTableDate(header?.target_ppap_date) }}
                        </div>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Target SOP Date</label>
                        <div class="w-full pl-3 pr-3 py-2 bg-slate-50 border border-slate-200 text-xs font-semibold text-slate-600 min-h-[34px]">
                            {{ formatTableDate(header?.target_sop_date) }}
                        </div>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Confidentiality Level</label>
                        <div class="w-full pl-3 pr-3 py-2.5 bg-slate-50 border border-slate-200 text-xs font-semibold text-slate-600 min-h-[38px]">
                            {{ confidentialityLabel }}
                        </div>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Data Status</label>
                        <div class="flex items-center justify-between p-2 bg-slate-50 border border-slate-200 min-h-[38px]">
                            <div class="flex flex-col min-w-0 pr-4">
                                <span class="text-[10px] font-black text-slate-700 uppercase tracking-wider">Status</span>
                                <span class="text-[9px] font-semibold text-slate-500 mt-0.5 truncate">{{ header?.is_active ? "Active" : "Disabled" }}</span>
                            </div>
                            <div :class="header?.is_active ? 'bg-emerald-600' : 'bg-slate-300'" class="relative inline-flex h-5 w-10 flex-shrink-0 rounded-full border-2 border-transparent transition-colors">
                                <span :class="header?.is_active ? 'translate-x-5' : 'translate-x-0'" class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition duration-200"></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Remark</label>
                        <textarea :value="header?.remark || '-'" readonly rows="3" class="w-full pl-3 pr-3 py-2 bg-slate-50 border border-slate-200 text-xs font-semibold text-slate-600 outline-none resize-none"></textarea>
                    </div>
                </div>
            </div>

            <div class="w-full bg-white border border-slate-200/80 shadow-xs overflow-hidden mb-6 flex flex-col">
                <div class="p-3 border-b border-slate-100 bg-slate-50/80">
                    <span class="text-xs font-black text-slate-700 uppercase tracking-wider">Project Material Details</span>
                </div>

                <div class="overflow-auto max-h-[50vh]">
                    <table class="w-full min-w-max divide-y divide-slate-200 text-left whitespace-nowrap">
                        <thead class="bg-blue-100 text-[10px] font-extrabold text-slate-500 uppercase tracking-wider sticky top-0 z-20 shadow-sm">
                            <tr>
                                <th class="px-4 py-3 text-center w-16">No.</th>
                                <th class="px-4 py-3 min-w-[350px]">Material</th>
                                <th class="px-4 py-3 min-w-[350px]">Specification</th>
                                <th class="px-4 py-3 min-w-[350px]">Customer Part Name</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="(item, index) in details"
                                :key="item.id || index"
                                @click="selectedRowIndex = index"
                                :class="['transition-colors duration-150 cursor-pointer', selectedRowIndex === index ? 'bg-blue-50/80 border-l-4 border-l-blue-500' : 'hover:bg-slate-50/50']"
                            >
                                <td class="px-4 py-3 text-center text-xs font-semibold text-slate-600">
                                    <div class="py-2">{{ index + 1 }}.</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="w-full pl-3 pr-3 py-2.5 bg-slate-50 border border-slate-200 text-xs font-semibold text-slate-700 rounded-sm">
                                        {{ item.material ? `[${item.material.code}] - ${item.material.name}` : `ID: ${item.material_id}` }}
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="text" :value="item.material?.specification || item.specification || '-'" class="w-full px-3 py-2 bg-slate-50 border border-slate-100 text-xs font-semibold text-slate-600 outline-none" readonly>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="text" :value="item.material?.customer_part_name || item.customer_part_name || '-'" class="w-full px-3 py-2 bg-slate-50 border border-slate-100 text-xs font-semibold text-slate-600 outline-none" readonly>
                                </td>
                            </tr>
                            <tr v-if="details.length === 0">
                                <td colspan="4" class="px-4 py-8 text-xs text-center text-slate-400 italic bg-slate-50">
                                    No material detail items found in this project.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div v-if="showModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 backdrop-blur-xs p-4">
                <div class="bg-white w-full max-w-4xl shadow-2xl h-[80vh] flex flex-col border border-slate-100">
                    <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                        <div>
                            <h2 class="text-sm font-black text-slate-900 uppercase tracking-tight">System Log History</h2>
                            <p class="text-[10px] text-slate-400 font-semibold mt-0.5">Audit trail records for this record</p>
                        </div>
                        <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                    <div class="flex-1 overflow-auto p-4">
                        <table class="w-full divide-y divide-slate-200 text-left whitespace-nowrap">
                            <thead class="bg-slate-100 text-[10px] font-black text-slate-500 uppercase tracking-wider sticky top-0">
                                <tr>
                                    <th class="px-4 py-2 text-center w-12">No.</th>
                                    <th class="px-4 py-2">Date & Time</th>
                                    <th class="px-4 py-2">User</th>
                                    <th class="px-4 py-2">Action</th>
                                    <th class="px-4 py-2">Description</th>
                                    <th class="px-4 py-2 text-center w-20">Details</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-xs">
                                <tr v-for="(log, idx) in logs" :key="log.id" class="hover:bg-slate-50/80">
                                    <td class="px-4 py-2.5 text-center font-medium text-slate-400">{{ idx + 1 }}</td>
                                    <td class="px-4 py-2.5 font-semibold text-slate-600">{{ formatLogDate(log.created_at) }}</td>
                                    <td class="px-4 py-2.5 font-bold text-slate-700">{{ log.user?.name || 'System' }}</td>
                                    <td class="px-4 py-2.5">
                                        <span :class="[
                                            log.action === 'create' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 
                                            log.action === 'update' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-rose-50 text-rose-700 border-rose-200',
                                            'px-2 py-0.5 text-[10px] font-black uppercase tracking-wider border rounded-sm'
                                        ]">{{ log.action }}</span>
                                    </td>
                                    <td class="px-4 py-2.5 text-slate-500 font-medium truncate max-w-xs">{{ log.description }}</td>
                                    <td class="px-4 py-2.5 text-center">
                                        <button @click="viewDetailLog(log.id)" class="text-blue-600 hover:text-blue-800 font-bold hover:underline">View</button>
                                    </td>
                                </tr>
                                <tr v-if="logs.length === 0">
                                    <td colspan="6" class="px-4 py-8 text-center text-slate-400 italic">No log history records found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div v-if="showDetailModal" class="fixed inset-0 z-[110] flex items-center justify-center bg-black/50 backdrop-blur-xs p-4">
                <div class="bg-white w-full max-w-2xl shadow-2xl h-[65vh] flex flex-col border border-slate-100">
                    <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                        <h3 class="text-xs font-black text-slate-900 uppercase tracking-tight">Log Properties Changes</h3>
                        <button @click="closeDetailModal" class="text-slate-400 hover:text-slate-600 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                    <div class="flex-1 overflow-auto p-4 text-xs font-mono bg-slate-900 text-slate-200">
                        <div class="mb-4 text-slate-400 border-b border-slate-800 pb-2">
                            // Action: <span class="text-amber-400">{{ selectedLogDetail.action }}</span><br/>
                            // Remark: <span class="text-slate-300 font-sans">{{ selectedLogRemark }}</span>
                        </div>
                        <pre class="whitespace-pre-wrap text-[11px]">{{ JSON.stringify(detailLogs, null, 2) }}</pre>
                    </div>
                </div>
            </div>

            <div v-if="showConfirmModal" class="fixed inset-0 z-[120] flex items-center justify-center bg-black/40 backdrop-blur-xs p-4">
                <div class="bg-white w-full max-w-sm shadow-2xl p-6 border border-slate-100">
                    <div class="flex flex-col items-center text-center">
                        <div class="p-3 bg-rose-50 text-rose-500 mb-4 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        </div>
                        <h3 class="text-lg font-black text-slate-900">Confirm Deletion</h3>
                        <p class="text-sm text-slate-500 mt-2 mb-6">Are you sure you want to delete this project data (<span class="font-bold text-slate-900">{{ header?.name }}</span>)? This action cannot be undone.</p>
                        <div class="flex items-center gap-3 w-full">
                            <button @click="confirmDelete" class="flex-1 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs transition-colors">Yes, Delete</button>
                            <button @click="showConfirmModal = false" class="flex-1 py-2 bg-white border border-slate-200 text-slate-600 font-bold text-xs hover:bg-slate-50 transition-colors">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>