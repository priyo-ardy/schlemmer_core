<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { toast } from 'vue3-toastify';
defineOptions({layout: AuthenticatedLayout});

const props = defineProps({
    units: Array
})

const isSlideOverOpen = ref(false);
const slideOverTitle = ref("New UoM");
const isEditMode = ref(false);
const searchQuery = ref("");
const selectedFilter = ref("all");
const selectedUnits = ref([]);
const showConfirmModal = ref(false);
const isRefreshing = ref(false);
const isDeleting = ref(false);

// Fungsi pencarian
const filteredUnits = computed(() => {
    return (props.units || []).filter((unit) => {
        const symbol = unit.symbol ? unit.symbol.toLowerCase() : '';
        const name = unit.name ? unit.name.toLowerCase() : '';
        const remark = unit.remark ? unit.remark.toLowerCase() : '';
        const search = searchQuery.value.toLowerCase();

        const matchesSearch = symbol.includes(search) || 
                              name.includes(search) || 
                              remark.includes(search);

        if (selectedFilter.value === 'enable') return matchesSearch && unit.is_active;
        if (selectedFilter.value === 'disable') return matchesSearch && !unit.is_active;

        return matchesSearch;
    })
});

const refreshTable = () => {
    isRefreshing.value = true;
    router.reload({
        only: ['units'],
        onSuccess: () => {
            isRefreshing.value = false;
            toast.success('Refreshing success')
        },
        onError: () => {
            isRefreshing.value = false;
            toast.error('Failed to refresh data');
        }
    });
};

// Checkbox
const isAllSelected = computed(() => filteredUnits.value.length > 0 && selectedUnits.value.length === filteredUnits.value.length);
const toggleSelectAll = () => { selectedUnits.value = isAllSelected.value ? [] : filteredUnits.value.map(unit => unit.id) };
const toggleSelectUnit = (id) => {
    const index = selectedUnits.value.indexOf(id);
    index > -1 ? selectedUnits.value.splice(index, 1) : selectedUnits.value.push(id);
}

// Form binding
const form = useForm({
    id: null,
    symbol: "",
    name: "",
    is_active: true,
    remark: ""
});

const openCreateDrawer = () => {
    isEditMode.value = false;
    slideOverTitle.value = "Register new UoM";
    form.reset();
    form.clearErrors();
    form.is_active = true;
    isSlideOverOpen.value = true;
}

const openEditDrawer = (unit) => {
    isEditMode.value = true;
    slideOverTitle.value = `Update UoM data: ${unit.symbol}`;
    form.clearErrors();

    form.id = unit.id;
    form.symbol = unit.symbol;
    form.name = unit.name;
    form.remark = unit.remark;
    form.is_active = !!unit.is_active;

    isSlideOverOpen.value = true;
}

// Submit
const handleSubmit = () => {
    form.clearErrors();
    let isValid = true;

    if(!form.symbol){
        form.setError('symbol', 'This field is required');
        isValid = false;
    }

    if(!form.name){
        form.setError('name', 'This field is required');
        isValid = false;
    }else if(form.name.length > 150){
        form.setError('name', 'UoM name cannot exceed 150 characters');
        isValid = false;
    }

    if(!isValid) return;

    if(isEditMode.value){
        form.transform((data) => ({ ...data, _method: 'put' }))
            .post(`/units/${form.id}`, { 
                onSuccess: () => { 
                    isSlideOverOpen.value = false; 
                    form.reset(); 
                    // toast.success('Data updated successfully');
                },
                onError:(errors) => {
                    const firstErrorMessage = Object.values(errors)[0];
                    toast.error(firstErrorMessage);
                }
            });
    }else{
        form.post('/units', {
            preserveScroll: true,
            onSuccess:()=> {
                isSlideOverOpen.value = false; 
                form.reset();
                // toast.success('Data saved successfully');
            },
            onError: (errors) => {
                const firstErrorMessage = Object.values(errors)[0];
                toast.error(firstErrorMessage);
            }
        });
    }
}

// Bulk delete
const deleteSelected = () => {
    if(selectedUnits.value.length === 0) return;
    showConfirmModal.value = true;
}

const confirmAction = () => {
    isDeleting.value = true;
    router.post('/units/mass-delete', { ids: selectedUnits.value }, {
        onSuccess: () => {
            selectedUnits.value = []; 
            showConfirmModal.value = false;
            isDeleting.value = false;
            // toast.success('Data deleted successfully');
        },
        onError: (errors) => {
            const firstErrorMessage = Object.values(errors)[0];
            toast.error(firstErrorMessage);
        }
    });
}
</script>

<template>
    <Head title="List of UoM"/>
    <div class="flex min-h-screen bg-slate-50 font-sans antialiased text-slate-800">
        <div class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">Unit of Measure</h1>
                    <p class="text-xs text-slate-500 mt-1">Manage application unit of measurement.</p>
                </div>
                <div class="sticky top-0 z-40 bg-white/95 backdrop-blur-sm border-b border-slate-200 px-3 py-2 shadow-sm ">
                    <div class="flex items-center justify-between w-full">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-1.5">
                                <button type="button" @click="openCreateDrawer" class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-bold text-blue-600 bg-blue-50 border border-blue-100 transition-all hover:bg-blue-100 active:scale-95 shadow-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    <span>New</span>
                                </button>
                                <div v-if="selectedUnits.length > 0" class="w-px h-4 bg-slate-200 mx-1"></div>
                                <button
                                    type="button"
                                    @click="refreshTable"
                                    :disabled="isRefreshing"
                                    class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-bold text-slate-600 bg-orange-50 border border-slate-200 transition-all  hover:bg-orange-50 active:scale-95 shadow-sm disabled:opacity-60"
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
                                <div v-if="selectedUnits.length > 0" class="w-px h-4 bg-slate-200 mx-1"></div>
                                <button type="button" @click="deleteSelected" :disabled="selectedUnits.length === 0" class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-bold text-rose-600 bg-rose-50 border border-rose-100 transition-all hover:bg-rose-100 active:scale-95 shadow-sm disabled:opacity-40 disabled:active:scale-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    <span>Delete Selected ({{ selectedUnits.length }})</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="bg-white border border-slate-200/80 shadow-sm p-4 mb-6  flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="relative flex-1 max-w-md">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </span>
                    <input type="text" placeholder="Search dynamically..." v-model="searchQuery" class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200  focus:border-blue-500 text-xs font-semibold focus:outline-none transition" />
                </div>
                <div class="flex items-center gap-3">
                    <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Status:</label>
                    <select v-model="selectedFilter" class="px-4 py-2.5 bg-white border border-slate-200 text-slate-700 text-xs font-semibold  focus:outline-none cursor-pointer">
                        <option value="all">All</option>
                        <option value="enable">Enabled</option>
                        <option value="disable">Disabled</option>
                    </select>
                </div>
            </div>
            
            <div class="bg-white border border-slate-200/80 shadow-sm  overflow-hidden mb-auto">
                <div class="overflow-auto">
                    <table class="w-full text-left border-collapse bg-white">
                        <thead class="bg-blue-300 text-slate-700 uppercase tracking-wider text-[11px] font-bold border-b border-blue-200">
                            <tr>
                                <th class="px-6 py-4 w-12 text-center">
                                    <input type="checkbox" :checked="isAllSelected" @change="toggleSelectAll" class="rounded border-slate-300 text-blue-600 h-4 w-4 transition cursor-pointer" />
                                </th>
                                <th class="px-6 py-4">Symbol</th>
                                <th class="px-6 py-4">Name</th>
                                <th class="px-6 py-4 text-center">Status</th>
                                <th class="px-6 py-4">Revision</th>
                                <th class="px-6 py-4">Remark</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs text-slate-600">
                            <tr 
                                v-for="unit in filteredUnits" 
                                :key="unit.id" 
                                @click="openEditDrawer(unit)"
                                class="hover:bg-blue-50/50 transition hover:cursor-pointer duration-150" 
                                :class="{'bg-blue-50/30 font-medium': selectedUnits.includes(unit.id)}"
                            >
                                <td class="px-6 py-4 text-center" @click.stop>
                                    <input type="checkbox" :value="unit.id" :checked="selectedUnits.includes(unit.id)" @change="toggleSelectUnit(unit.id)" class="rounded border-slate-300 text-blue-600 h-4 w-4 transition cursor-pointer" />
                                </td>
                                <td class="px-6 py-4 font-mono font-bold text-slate-900">
                                    {{ unit.symbol }}
                                </td>
                                <td class="px-6 py-4 font-semibold text-slate-700">
                                    {{ unit.name }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span 
                                        :class="unit.is_active 
                                            ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' 
                                            : 'bg-rose-50 text-rose-600 border border-rose-100'" 
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide shadow-sm"
                                    >
                                        <span :class="unit.is_active ? 'bg-emerald-500' : 'bg-rose-500'" class="h-1.5 w-1.5 rounded-full mr-1.5"></span>
                                        {{ unit.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-bold text-slate-400">Rev. {{ unit.revision ?? 0 }}</td>
                                <td class="px-6 py-4 text-slate-500">
                                    {{ unit.remark || '-' }}
                                </td>
                            </tr>
                            <tr v-if="filteredUnits.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-slate-400 font-medium">No Unit of Measure data found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div v-show="isSlideOverOpen" class="fixed inset-0 z-40 overflow-hidden" role="dialog" aria-modal="true">
        <div class="absolute inset-0 overflow-hidden">
            <Transition enter-active-class="ease-in-out duration-300" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="ease-in-out duration-300" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-show="isSlideOverOpen" @click="isSlideOverOpen = false" class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity"></div>
            </Transition>

            <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                <Transition enter-active-class="transform transition ease-in-out duration-300" enter-from-class="translate-x-full" enter-to-class="translate-x-0" leave-active-class="transform transition ease-in-out duration-300" leave-from-class="translate-x-0" leave-to-class="translate-x-full">
                    <div v-show="isSlideOverOpen" class="pointer-events-auto w-screen max-w-md">
                        <form @submit.prevent="handleSubmit" class="flex h-full flex-col bg-white shadow-2xl border-l border-slate-200">
                            <div class="bg-slate-900 px-6 py-5 flex items-center justify-between">
                                <div>
                                    <h2 class="text-base font-black text-white tracking-tight">{{ slideOverTitle }}</h2>
                                    <p class="text-[10px] text-slate-400 mt-0.5">Override UoM data.</p>
                                </div>
                                <button type="button" @click="isSlideOverOpen = false" class=" text-slate-400 hover:text-white hover:bg-slate-800 p-1.5 transition">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </div>

                            <div class="flex-1 overflow-y-auto p-6 space-y-5 bg-slate-50/50">
                                <div>
                                    <label class="block text-xs font-bold text-slate-400 tracking-wider mb-2">UoM Symbol <span class="text-rose-500 text-bold">*</span></label>
                                    <input type="text" v-model="form.symbol" maxlength="10" placeholder="UoM Symbol" 
                                        :class="[
                                            'w-full pl-4 pr-4 py-2.5 bg-slate-50 border focus:bg-white focus:ring-2 text-xs font-medium transition-all outline-none ',
                                            form.errors.symbol 
                                                ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-100 text-rose-600' 
                                                : 'border-slate-200 focus:border-blue-500 focus:ring-blue-100 text-slate-700'
                                        ]"
                                    />
                                    <p v-if="form.errors.symbol" class="mt-1.5 text-[10px] font-bold text-rose-500">{{ form.errors.symbol }}</p>
                                    <p v-else class="mt-1.5 text-[10px] font-medium text-slate-400">Unique units symbol</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-400 tracking-wider mb-2">UoM Name <span class="text-rose-500 text-bold">*</span></label>
                                    <input type="text" v-model="form.name" maxlength="150" placeholder="UoM Name" 
                                        :class="[
                                            'w-full pl-4 pr-4 py-2.5 bg-slate-50 border focus:bg-white focus:ring-2 text-xs font-medium transition-all outline-none ',
                                            form.errors.name 
                                                ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-100 text-rose-600' 
                                                : 'border-slate-200 focus:border-blue-500 focus:ring-blue-100 text-slate-700'
                                        ]"
                                    />
                                    <p v-if="form.errors.name" class="mt-1.5 text-[10px] font-bold text-rose-500">{{ form.errors.name }}</p>
                                </div>
                                <div class="space-y-4 pt-2">
                                    <div class="flex items-center justify-between p-3.5 bg-white border border-slate-200/80  shadow-sm">
                                        <div class="flex flex-col min-w-0 pr-4">
                                            <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">Activation Status</span>
                                            <span class="text-[11px] font-medium text-slate-400 mt-0.5 truncate">{{ form.is_active ? "🟢 Active." : "🔴 Inactive." }}</span>
                                        </div>
                                        <button type="button" @click="form.is_active = !form.is_active" :class="form.is_active ? 'bg-emerald-500' : 'bg-slate-200'" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out active:scale-95"><span :class="form.is_active ? 'translate-x-5' : 'translate-x-0'" class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-md transition duration-200"></span></button>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-400 tracking-wider mb-2">Remark (Optional)</label>
                                    <textarea v-model="form.remark" placeholder="Write additional information here ..." class="w-full px-4 py-2.5 bg-white border border-slate-200 text-xs font-semibold  focus:outline-none focus:border-blue-500 transition"></textarea>
                                </div>
                            </div>
                            <div class="border-t border-slate-200 px-6 py-4 bg-white flex items-center justify-end gap-3">
                                <button type="button" @click="isSlideOverOpen = false" class="px-4 py-2.5 bg-white border border-slate-200 text-xs font-bold text-slate-700  hover:bg-slate-50 transition-all duration-150 active:scale-95 shadow-sm">Cancel</button>
                                <button type="submit" :disabled="form.processing" class="px-5 py-2.5 bg-blue-600 text-white font-bold text-xs  shadow-md hover:bg-blue-700 transition-all duration-150 active:scale-95 disabled:opacity-50 flex items-center gap-2">
                                    <svg v-if="form.processing" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ form.processing ? "Saving..." : isEditMode ? "Update" : "Save" }}
                                </button>
                            </div>
                        </form>
                    </div>
                </Transition>
            </div>
        </div>
    </div>

    <transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
        <div v-if="showConfirmModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
            <div class="bg-white  w-full max-w-sm shadow-2xl p-6 border border-slate-100">
                <div class="flex flex-col items-center text-center">
                    <div class="p-3 bg-rose-50 text-rose-500 rounded-full mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    </div>
                    <h3 class="text-lg font-black text-slate-900">Confirm Deletion</h3>
                    <p class="text-sm text-slate-500 mt-2 mb-6">Are you sure you want to delete these <span class="font-bold text-slate-900">{{ selectedUnits.length }} items</span>? This action cannot be undone.</p>
                    <div class="flex gap-3 w-full">
                        <button 
                            v-if="!isDeleting"
                            type="button"
                            @click="showConfirmModal = false" 
                            class="flex-1 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition duration-150 active:scale-95 shadow-sm"
                        >
                            Cancel
                        </button>
                        
                        <button 
                            type="button"
                            @click="confirmAction" 
                            :disabled="isDeleting"
                            class="flex-1 px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-lg transition duration-150 active:scale-95 disabled:opacity-50 disabled:active:scale-100 flex items-center justify-center gap-2"
                        >
                            <svg v-if="isDeleting" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>

                            {{ isDeleting ? 'Processing ...' : 'Yes, Delete' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>