<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { toast } from 'vue3-toastify';

defineOptions({layout:AuthenticatedLayout});
const props = defineProps({
    users: Array,
    header: {
        type: Object,
        default: () => ({})
    }
});

const form = useForm({
    name: props.header?.name || '',
    revision: props.header?.revision || '0',
    remark: props.header?.remark || '',
    // Jika tidak ada data dari backend, set default 1 baris kosong
    processItems: props.header?.details?.length > 0 
        ? props.header.details 
        : [{
            previous_problem: '',
            requirements: '',
            potential_failure_mode: '',
            potential_effect_of_failure: '',
            potential_cause_of_failure: '',
            controls_prevention: '',
            controls_detection: ''
        }]
});

const addRow = () => {
    form.processItems.push({
        previous_problem: '',
        requirements: '',
        potential_failure_mode: '',
        potential_effect_of_failure: '',
        potential_cause_of_failure: '',
        controls_prevention: '',
        controls_detection: ''
    });
};

const removerRow = (index) => {
    if (form.processItems.length > 1) {
        form.processItems.splice(index, 1);
    }
};

const cancelForm = () => {
    router.visit('/process');
};

const validateAndSave = () => {
    form.post('/process', { // Sesuaikan route-nya jika pakai nama route: route('process.store')
        onSuccess: () => {
            showToast('success', 'Data PMFEA berhasil disimpan!');
        },
        onError: () => {
            showToast('error', 'Cek kembali form anda, ada yang belum valid!');
        }
    });
};
</script>

<template>
    <Head title="Create New PMFEA Template Data"/>

    <div class="flex min-h-screen bg-slate-50 font-sans antialiased text-slate-800">
        <div class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">Create PMFEA Process Function</h1>
                    <p class="text-xs text-slate-500 mt-1">Create new PMFEA template process function.</p>
                </div>
                <div class="flex items-center gap-3">
                    <Link href="/process" class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-md transition active:scale-95 active:bg-slate-200">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 15.75 3 12m0 0 3.75-3.75M3 12h18" /></svg>

                        Back to List
                    </Link>
                </div>
            </div>

            <!-- Buat header -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 mb-6">
                <div class="grid grid-cols-1 lg:grid-cols-10 gap-6 items-start">
                    <div class="lg:col-span-3">
                        <div class="flex items-center gap-1.5 mb-2">
                            <label class="block text-[10px] font-extrabold text-slate-600 uppercase tracking-wider">
                                Function Name <span class="text-rose-500">*</span>
                            </label>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400 cursor-help" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7" />
                                </svg>
                            </div>
                            <input type="text" v-model="form.name" placeholder="Enter process function name..." maxlength="255" autocomplete="off"
                            :class="[
                                'w-full pl-10 pr-4 py-2.5 bg-slate-50 border focus:bg-white focus:ring-2 rounded-xl text-xs font-medium transition-all outline-none',
                                form.errors.name 
                                    ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-100 text-rose-600' 
                                    : 'border-slate-200 focus:border-blue-500 focus:ring-blue-100 text-slate-700'
                            ]">
                        </div>
                        <p v-if="form.errors.name" class="mt-1.5 text-[10px] font-bold text-rose-500">{{ form.errors.name }}</p>
                        <p v-else class="mt-1.5 text-[10px] font-medium text-slate-400">Unique process function name</p>
                    </div>

                    <div class="lg:col-span-1 flex flex-col justify-start">
                        <div class="flex items-center gap-1.5 mb-2">
                            <label class="block text-[10px] font-extrabold text-slate-600 uppercase tracking-wider">
                                Revision
                            </label>
                        </div>
                        <div class="w-full flex">
                            <div class="inline-flex items-center justify-center gap-1.5 px-2 py-2 bg-sky-50 border border-sky-200 text-sky-700 rounded-full w-full shadow-sm select-none cursor-not-allowed whitespace-nowrap">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                                </svg>
                                <span class="text-xs font-black tracking-widest">v.<span class="text-sm">0</span></span>
                                <input type="hidden" name="revision" value="0">
                            </div>
                        </div>
                        <p class="mt-1.5 text-[10px] font-medium text-slate-400 text-center">Initial</p>
                    </div>

                    <div class="lg:col-span-6">
                        <div class="flex items-center gap-1.5 mb-2">
                            <label class="block text-[10px] font-extrabold text-slate-600 uppercase tracking-wider">
                                Remarks (Optional)
                            </label>
                        </div>
                        <textarea name="remark" rows="2" placeholder="Provide detailed context, e.g., on equipment conditions, key dependencies, environmental factors..." 
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 rounded-xl text-xs font-medium text-slate-700 transition-all outline-none resize-none leading-relaxed"></textarea>
                    </div>
                    
                </div>
            </div>

            <!-- Buat details --> 
            <div class="w-full bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden mb-6 flex flex-col">
                
                <div class="overflow-auto max-h-[80vh]">
                    <table class="w-full min-w-max divide-y divide-slate-200 text-left whitespace-nowrap">
                        
                        <thead class="bg-slate-50 text-[10px] font-extrabold text-slate-500 uppercase tracking-wider sticky top-0 z-20 shadow-sm">
                            <tr>
                                <th class="px-4 py-3 text-center w-16">No.</th>
                                <th class="px-4 py-3 min-w-[150px]">Previous</th>
                                <th class="px-4 py-3 min-w-[350px]">Requirements</th>
                                <th class="px-4 py-3 min-w-[350px]">Potential Failure Mode</th>
                                <th class="px-4 py-3 min-w-[350px]">Potential Effect(s)</th>
                                <th class="px-4 py-3 min-w-[350px]">Potential Cause(s)</th>
                                <th class="px-4 py-3 min-w-[350px]">Controls Prevention</th>
                                <th class="px-4 py-3 min-w-[350px]">Control Detection</th>
                                <th class="px-4 py-3 text-center w-24">Action</th>
                            </tr>
                        </thead>
                        
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="(item, index) in form.processItems" :key="index" class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-4 py-3 text-center text-xs font-semibold text-slate-600">
                                    <div class="py-2">{{ index + 1 }}.</div>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="text" v-model="item.previous_problem" placeholder="Enter previous ..." class="w-full px-3 py-2 bg-slate-50 border border-slate-200 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 rounded-lg text-xs transition-all outline-none">
                                </td>
                                <td class="px-4 py-3">
                                    <input type="text" v-model="item.requirements" placeholder="Enter requirements..." required 
                                        :class="[
                                            'w-full px-3 py-2 bg-slate-50 border focus:bg-white focus:ring-2 rounded-lg text-xs transition-all outline-none',
                                            form.errors[`processItems.${index}.requirements`] 
                                                ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-100 placeholder-rose-300' 
                                                : 'border-slate-200 focus:border-blue-500 focus:ring-blue-100'
                                        ]">
                                    <span v-if="form.errors[`processItems.${index}.requirements`]" class="block mt-1 text-[9px] font-bold text-rose-500">
                                        {{ form.errors[`processItems.${index}.requirements`] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="text" v-model="item.potential_failure_mode" placeholder="Failure mode..." required
                                        :class="[
                                            'w-full px-3 py-2 bg-slate-50 border focus:bg-white focus:ring-2 rounded-lg text-xs transition-all outline-none',
                                            form.errors[`processItems.${index}.potential_failure_mode`] 
                                                ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-100 placeholder-rose-300' 
                                                : 'border-slate-200 focus:border-blue-500 focus:ring-blue-100'
                                        ]">
                                    <span v-if="form.errors[`processItems.${index}.potential_failure_mode`]" class="block mt-1 text-[9px] font-bold text-rose-500">
                                        {{ form.errors[`processItems.${index}.potential_failure_mode`] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="text" v-model="item.potential_effect_of_failure" placeholder="Effects..." required
                                        :class="[
                                            'w-full px-3 py-2 bg-slate-50 border focus:bg-white focus:ring-2 rounded-lg text-xs transition-all outline-none',
                                            form.errors[`processItems.${index}.potential_effect_of_failure`] 
                                                ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-100 placeholder-rose-300' 
                                                : 'border-slate-200 focus:border-blue-500 focus:ring-blue-100'
                                        ]">
                                    <span v-if="form.errors[`processItems.${index}.potential_effect_of_failure`]" class="block mt-1 text-[9px] font-bold text-rose-500">
                                        {{ form.errors[`processItems.${index}.potential_effect_of_failure`] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="text" v-model="item.potential_cause_of_failure" placeholder="Causes..." required
                                        :class="[
                                            'w-full px-3 py-2 bg-slate-50 border focus:bg-white focus:ring-2 rounded-lg text-xs transition-all outline-none',
                                            form.errors[`processItems.${index}.potential_cause_of_failure`] 
                                                ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-100 placeholder-rose-300' 
                                                : 'border-slate-200 focus:border-blue-500 focus:ring-blue-100'
                                        ]">
                                    <span v-if="form.errors[`processItems.${index}.potential_cause_of_failure`]" class="block mt-1 text-[9px] font-bold text-rose-500">
                                        {{ form.errors[`processItems.${index}.potential_cause_of_failure`] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="text" v-model="item.controls_prevention" placeholder="Prevention..." required
                                        :class="[
                                            'w-full px-3 py-2 bg-slate-50 border focus:bg-white focus:ring-2 rounded-lg text-xs transition-all outline-none',
                                            form.errors[`processItems.${index}.controls_prevention`] 
                                                ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-100 placeholder-rose-300' 
                                                : 'border-slate-200 focus:border-blue-500 focus:ring-blue-100'
                                        ]">
                                    <span v-if="form.errors[`processItems.${index}.controls_prevention`]" class="block mt-1 text-[9px] font-bold text-rose-500">
                                        {{ form.errors[`processItems.${index}.controls_prevention`] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="text" v-model="item.controls_detection" placeholder="Detection..." required
                                        :class="[
                                            'w-full px-3 py-2 bg-slate-50 border focus:bg-white focus:ring-2 rounded-lg text-xs transition-all outline-none',
                                            form.errors[`processItems.${index}.controls_detection`] 
                                                ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-100 placeholder-rose-300' 
                                                : 'border-slate-200 focus:border-blue-500 focus:ring-blue-100'
                                        ]">
                                    <span v-if="form.errors[`processItems.${index}.controls_detection`]" class="block mt-1 text-[9px] font-bold text-rose-500">
                                        {{ form.errors[`processItems.${index}.controls_detection`] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button type="button" @click="addRow" class="p-1.5 bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white rounded-lg transition-colors shadow-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                            </svg>
                                        </button>
                                        <button type="button" @click="removerRow(index)" :disabled="form.processItems.length === 1" 
                                            class="p-1.5 bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white rounded-lg transition-colors shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-4 shrink-0 mt-auto flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <button type="button" @click="cancelForm" class="active:scale-95 active:bg-slate-200 inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold text-xs rounded-xl shadow-sm transition-all duration-150">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Cancel
                    </button>
                
                    <button type="button" @click="validateAndSave" :disabled="form.processing" 
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-md transition active:scale-95 active:bg-slate-200 disabled:opacity-70 disabled:cursor-not-allowed">
                        
                        <svg v-if="form.processing" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        
                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        
                        {{ form.processing ? 'Saving...' : 'Save' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <Transition
        enter-active-class="transform ease-out duration-300 transition"
        enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-4"
        enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="toast.show" class="fixed top-6 right-6 z-50 flex w-full max-w-sm flex-col gap-2 pointer-events-none">
            
            <div :class="[
                'pointer-events-auto w-full max-w-sm overflow-hidden rounded-xl bg-white shadow-xl ring-1 ring-black/5 flex items-start p-4',
                toast.type === 'error' ? 'border-l-4 border-rose-500' : 'border-l-4 border-emerald-500'
            ]">
                
                <div class="flex-shrink-0">
                    <svg v-if="toast.type === 'success'" class="h-5 w-5 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <svg v-else class="h-5 w-5 text-rose-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>

                <div class="ml-3 w-0 flex-1 pt-0.5">
                    <p class="text-xs font-bold text-slate-800">
                        {{ toast.type === 'success' ? 'Success' : 'Action Required' }}
                    </p>
                    <p class="mt-1 text-[11px] font-medium text-slate-500 leading-relaxed">
                        {{ toast.message }}
                    </p>
                </div>

                <div class="ml-4 flex flex-shrink-0">
                    <button @click="toast.show = false" type="button" class="inline-flex rounded-md bg-white text-slate-400 hover:text-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                        <span class="sr-only">Close</span>
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
            </div>
        </div>
    </Transition>
</template>