<script setup>
import { ref, watch, computed } from 'vue';
import { Head, router, Link, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { toast } from 'vue3-toastify';

defineOptions({ layout: AuthenticatedLayout });

const page = usePage();
const searchQuery = ref('');
const selectedIds = ref([]);
const showConfirmModal = ref(false);

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

const confirmAction = () => {
    router.post('/process/delete', { ids: selectedIds.value }, {
        onSuccess: () => {
            selectedIds.value = [];
            showConfirmModal.value = false;
            toast.success('Data deleted successfully'); // Pastikan toast.success ada isinya
        }
    });
};

const props = defineProps({
    headers: {
        type: Object,
        default: () => ({ data: [], links: [], per_page: 10 }),
    },
});

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
            per_page: props.headers.per_page || 10
        },
        { preserveScroll: true, preserveState: true }
    );
}, 500);

watch(searchQuery, (newValue) => {
    handleSearch(newValue);
});

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
        '/process',
        {
            per_page: event.target.value,
            search: searchQuery.value
        },
        { preserveScroll: true, preserveState: true }
    );
};
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
                <div class="flex items-center gap-3">
                    <button @click="deleteSelected" :disabled="selectedIds.length === 0" class="inline-flex items-center gap-2 px-4 py-2.5 bg-rose-50 border border-rose-200 text-rose-700 font-bold text-xs rounded-xl shadow-sm transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        Delete Selected ({{ selectedIds.length }})
                    </button>
                    <Link href="/process/create" class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-md transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                        New Process Function
                    </Link>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-4 mb-6">
                <div class="relative max-w-md">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </span>
                    <input type="text" v-model="searchQuery" placeholder="Search dynamically..." class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 focus:border-blue-500 rounded-xl text-xs font-semibold focus:outline-none transition" />
                    <span v-if="isProcessing" class="absolute right-3 top-3 text-[10px] text-blue-600 animate-pulse">Searching...</span>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden mb-auto">
                <div class="flex items-center gap-3 p-5">
                    <span class="text-xs font-medium text-slate-500">Rows per page:</span>
                    <select @change="updatePerPage" :value="headers.per_page" class="appearance-none pl-3 pr-8 py-1.5 bg-white border border-slate-200 text-slate-700 text-xs font-semibold rounded-lg shadow-sm cursor-pointer hover:bg-slate-50">
                        <option v-for="n in [5, 10, 20, 50, 100]" :key="n" :value="n">{{ n }}</option>
                    </select>
                </div>
                
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
                                <td class="px-6 py-4 text-center"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-200 text-slate-600">Rev. {{ header.revision }}</span></td>
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
                            <Link v-if="link.url" :href="link.url" v-html="link.label" class="px-3 py-1.5 text-xs font-bold rounded-lg transition-all" :class="link.active ? 'bg-blue-600 text-white shadow-md' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'" />
                            <span v-else v-html="link.label" class="px-3 py-1.5 text-xs text-slate-400"></span>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal konfirmasi -->
     <transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
        <div v-if="showConfirmModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
            <div class="bg-white rounded-2xl w-full max-w-sm shadow-2xl p-6 border border-slate-100">
                <div class="flex flex-col items-center text-center">
                    <div class="p-3 bg-rose-50 text-rose-500 rounded-full mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    </div>
                    <h3 class="text-lg font-black text-slate-900">Confirm Deletion</h3>
                    <p class="text-sm text-slate-500 mt-2 mb-6">Are you sure you want to delete these <span class="font-bold text-slate-900">{{ selectedIds.length }} items</span>? This action cannot be undone.</p>
                    <div class="flex gap-3 w-full">
                        <button @click="showConfirmModal = false" class="flex-1 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition">Cancel</button>
                        <button @click="confirmAction" class="flex-1 px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-xl shadow-lg transition">Yes, Delete</button>
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>