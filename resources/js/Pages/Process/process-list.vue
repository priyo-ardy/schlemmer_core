<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({layout:AuthenticatedLayout});

const searchQuery = ref('');

const props = defineProps({
    users: Array,
    headers: {
        type: Object,
        default: () => ({})
    }
});

const formatDateTime = (dateString) => {
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false // Menggunakan format 24 jam
    })
    .replace(',', '')
    .replace(/\./g, ':');; // Menghapus koma bawaan jika tidak diinginkan
};

const goToDetail = (id) => {
    router.get(`/process/${id}`);
};
</script>

<template>
    <Head title="PMFEA Process Function"/>

    <div class="flex min-h-screen bg-slate-50 font-sans antialiased text-slate-800">
        <div class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">PMFEA Process Function list</h1>
                    <p class="text-xs text-slate-500 mt-1">Manage PMFEA process function list.</p>
                </div>
                <div class="flex items-center gap-3">
                    <button @click="" class="inline-flex items-center gap-2 px-4 py-2.5 bg-rose-50 border border-rose-200 text-rose-700 font-bold text-xs rounded-xl shadow-sm transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        Delete Selected ()
                    </button>
                    <Link href="/process/create" class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-md transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                            New Process Function
                    </Link>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-4 mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="relative flex-1 max-w-md">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </span>
                    <input type="text" v-model="searchQuery" placeholder="Search dynamically by function name or function item ..." class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 focus:border-blue-500 rounded-xl text-xs font-semibold focus:outline-none transition" />
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden mb-auto">
                <div class="overflow-auto min-h-96 rounded-xl border border-slate-200">
                    <table class="w-full text-left border-collapse bg-white">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase tracking-wider text-[10px] font-bold">
                            <tr>
                                <th class="px-6 py-4 w-12 text-center">
                                    <input type="checkbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 h-4 w-4" />
                                </th>
                                <th class="px-6 py-4">Process Function Name</th>
                                <th class="px-6 py-4 text-center">Revision</th>
                                <th class="px-6 py-4">Remarks</th>
                                <th class="px-6 py-4 text-center">Children</th>
                                <th class="px-6 py-4 text-center">Updated At</th>
                                <th class="px-6 py-4 text-center">Updated By</th>
                            </tr>
                        </thead>
                        
                        <tbody class="divide-y divide-slate-100 text-xs text-slate-600">
                            <tr v-for="header in headers.data" :key="header.id" @click="goToDetail(header.id)" class="hover:bg-blue-50 transition-colors cursor-pointer">
                                <td class="px-6 py-4 text-center">
                                    <input type="checkbox" class="rounded border-slate-300 text-blue-600 h-4 w-4" />
                                </td>
                                <td class="px-6 py-4 font-semibold text-slate-900">{{ header.name }}</td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">
                                        Rev. {{ header.revision }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 truncate">{{ header.remark || '-' }}</td>
                                <td class="px-6 py-4 text-center">{{ header.details?.length || 0 }}</td>
                                <td class="px-6 py-4 text-center font-medium">{{ formatDateTime(header.updated_at) }}</td>
                                <td class="px-6 py-4 text-center font-medium">{{ header.updater?.name }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>