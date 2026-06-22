<script setup>
import { ref, watch, computed, onMounted, onUnmounted } from 'vue';
import { Head, router, Link, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { toast } from 'vue3-toastify';

defineOptions({layout: AuthenticatedLayout});

const page = usePage();

// 1. Karena ini satu file, kita pakai variabel lokal (ref) untuk nyimpen UoM, bukan props/emit
const selectedUom = ref(''); 

const isOpen = ref(false);
const searchQuery = ref('');
const options = ['Pcs', 'Set', 'Kg', 'Unit(s)', 'Meter', 'Roll', 'Box'];

const filteredOptions = computed(() => {
    return options.filter(opt => opt.toLowerCase().includes(searchQuery.value.toLowerCase()));
});

const closeDropdown = (e) => {
    if (!e.target.closest('.relative')) isOpen.value = false;
};

const selectOption = (opt) => {
    // 2. Langsung isi variabel lokalnya saat diklik
    selectedUom.value = opt;
    isOpen.value = false;
    searchQuery.value = '';
};

onMounted(() => document.addEventListener('click', closeDropdown));
onUnmounted(() => document.removeEventListener('click', closeDropdown));
</script>

<template>
    <Head title="Register New Material Data"/>
    <div class="flex min-h-screen bg-slate-50 font-sans antialiased text-slate-800">
        <div class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">New Material</h1>
                    <p class="text-xs text-slate-500 mt-1">Register new material/part number data.</p>
                </div>
                <div class="sticky top-0 z-40 bg-white/95 backdrop-blur-sm border-b border-slate-200 px-3 py-2 shadow-sm">
                    <div class="flex items-center justify-between w-full">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-1.5">
                                <Link href="/materials" class="group flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-bold text-slate-500 hover:text-blue-600 hover:bg-blue-50/50 rounded-lg transition-colors active:scale-95">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform group-hover:-translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                    </svg>
                                    <span class="hidden sm:inline">Back</span>
                                </Link>
                                
                                <div class="w-px h-6 bg-slate-300 mx-1"></div>

                                <button class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-lg shadow-sm transition active:scale-95 disabled:opacity-70">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                    Save
                                </button>

                                <button class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs rounded-lg transition-all active:scale-95 shadow-sm">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-slate-200/80 shadow-sm p-6 mb-6">
                <div class="grid grid-cols-1 lg:grid-cols-10 gap-6 items-start">
                    <div class="lg:col-span-2">
                        <div class="flex items-center gap-1.5 mb-2">
                            <label class="block text-[10px] font-extrabold text-slate-600 uppercase tracking-wider">
                                Part Number <span class="text-rose-500">*</span>
                            </label>
                        </div>
                        <input type="text" placeholder="Enter part number here ..." maxlength="150" autocomplete="off"
                            class="w-full pl-4 pr-4 py-2.5 bg-slate-50 border focus:bg-white focus:ring-2 text-xs font-medium transition-all outline-none border-slate-200 focus:border-blue-500 focus:ring-blue-100 text-slate-700"
                        >
                    </div>
                    <div class="lg:col-span-3">
                        <div class="flex items-center gap-1.5 mb-2">
                            <label class="block text-[10px] font-extrabold text-slate-600 uppercase tracking-wider">
                                Part Name <span class="text-rose-500">*</span>
                            </label>
                        </div>
                        <input type="text" placeholder="Enter part name here ..." maxlength="150" autocomplete="off"
                            class="w-full pl-4 pr-4 py-2.5 bg-slate-50 border focus:bg-white focus:ring-2 text-xs font-medium transition-all outline-none border-slate-200 focus:border-blue-500 focus:ring-blue-100 text-slate-700"
                        >
                    </div>
                    <div class="lg:col-span-5">
                        <div class="flex items-center gap-1.5 mb-2">
                            <label class="block text-[10px] font-extrabold text-slate-600 uppercase tracking-wider">
                                Specification <span class="text-rose-500">*</span>
                            </label>
                        </div>
                        <input type="text" placeholder="Enter specification here ..." maxlength="255" autocomplete="off"
                            class="w-full pl-4 pr-4 py-2.5 bg-slate-50 border focus:bg-white focus:ring-2 text-xs font-medium transition-all outline-none border-slate-200 focus:border-blue-500 focus:ring-blue-100 text-slate-700"
                        >
                    </div>
                    <div class="lg:col-span-2 relative">
                        <div class="flex items-center gap-1.5 mb-2">
                            <label class="block text-[10px] font-extrabold text-slate-600 uppercase tracking-wider">
                                UoM <span class="text-rose-500">*</span>
                            </label>
                        </div>

                        <div @click="isOpen = !isOpen"
                            class="w-full pl-4 pr-4 py-2.5 bg-slate-50 border border-slate-200 cursor-pointer flex justify-between items-center text-xs font-semibold hover:border-blue-500 transition shadow-sm text-slate-600">
                            <span :class="selectedUom ? 'text-slate-900' : 'text-slate-400'">
                                {{ selectedUom || 'Select UoM...' }}
                            </span>
                            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>

                        <div v-if="isOpen" 
                            class="absolute z-[9999] w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-2xl p-2 overflow-hidden animate-in fade-in zoom-in duration-200">
                            
                            <input 
                                v-model="searchQuery"
                                type="text" 
                                placeholder="Search UoM..." 
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-100 rounded-lg text-xs font-medium focus:outline-none focus:border-blue-400 mb-2 shadow-sm text-slate-600"
                                @click.stop
                            />
                            
                            <div class="max-h-40 overflow-y-auto">
                                <div v-for="opt in filteredOptions" :key="opt" 
                                    @click.stop="selectOption(opt)" 
                                    class="px-3 py-2 text-xs font-medium text-slate-600 hover:bg-blue-50 hover:text-blue-600 cursor-pointer rounded-lg transition">
                                    {{ opt }}
                                </div>
                                <div v-if="filteredOptions.length === 0" class="px-3 py-2 text-xs text-slate-400">
                                    No match found
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>