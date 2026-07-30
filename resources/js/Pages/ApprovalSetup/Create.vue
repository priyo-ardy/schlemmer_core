<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Head, useForm, router, Link, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';
import { toast } from 'vue3-toastify';
import axios from 'axios';

defineOptions({layout:AuthenticatedLayout, inheritAttrs: false});

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
    {
        deep: true
    }
);

const cancelForm = () => {
    router.get('/approval-setup');
}

const createBlankItem = () => ({
    row_key: Math.random().toString(36).substring(2, 9),
    approver_id : ''
});

const form = useForm({
    module: '',
    name: '',
    revision: '',
    remark:'',
    approver: [createBlankItem()]
});

// --- State untuk Custom Select Module ---
const isModuleDropdownOpen = ref(false);
const moduleSearch = ref("");

const staticModules = [
    { id: 'pfmea', name: 'PFMEA' },
    { id: 'apqp', name: 'APQP' }
];

const filteredModules = computed(() => {
    if (!moduleSearch.value) return staticModules;
    const lowerSearch = moduleSearch.value.toLowerCase();
    return staticModules.filter(m =>
        m.name.toLowerCase().includes(lowerSearch)
    );
});

const selectedModuleName = computed(() => {
    if (!form.module) return "Select Module Type";
    const mod = staticModules.find(m => m.id === form.module);
    return mod ? mod.name : "Select Module Type";
});

const selectModule = (id) => {
    form.module = id;
    isModuleDropdownOpen.value = false;
    moduleSearch.value = "";
};

// --- State untuk Users & Dropdown Per-Row (Approver) ---
const users = ref([]);
const userSearch = ref("");
const activeDropdownRow = ref(null);
const dropdownStyle = ref({});

// Ambil data user dari endpoint /api/v1/users saat mounted
onMounted(async () => {
    try {
        const response = await axios.get('/api/v1/users');
        users.value = response.data.data || [];
    } catch (error) {
        toast.error("Failed to load users data");
    }
});

const filteredUsers = computed(() => {
    if (!userSearch.value) return users.value;
    const lowerSearch = userSearch.value.toLowerCase();
    return users.value.filter(u =>
        u.name.toLowerCase().includes(lowerSearch)
    );
});

// Helper untuk menampilkan nama approver yang terpilih pada baris tertentu
const getSelectedUserName = (approverId) => {
    if (!approverId) return "Select Approver";
    const user = users.value.find(u => u.id === approverId);
    return user ? user.name : "Select Approver";
};

const selectUser = (index, userId) => {
    form.approver[index].approver_id = userId;
    activeDropdownRow.value = null;
    userSearch.value = "";
};

// --- Tombol Aksi Tabel (New, Insert, Delete, Delete All) ---
const onNew = () => {
    form.approver.push(createBlankItem());
    selectedRowIndex.value = form.approver.length - 1;
};

const onInsert = () => {
    const idx = selectedRowIndex.value !== null ? selectedRowIndex.value : form.approver.length - 1;
    form.approver.splice(idx + 1, 0, createBlankItem());
    selectedRowIndex.value = idx + 1;
};

const onDelete = () => {
    if (form.approver.length > 1 && selectedRowIndex.value !== null) {
        form.approver.splice(selectedRowIndex.value, 1);
        selectedRowIndex.value = Math.max(0, selectedRowIndex.value - 1);
    } else {
        toast.warning("At least one approver is required.");
    }
};

const onDeleteAll = () => {
    form.approver = [createBlankItem()];
    selectedRowIndex.value = 0;
};

const validateAndSave = () => {
    form.clearErrors();
    let isValid = true;

    if (!form.module) {
        form.setError('module', 'Module name is required');
        isValid = false;
    }

    if (!form.approver || form.approver.length === 0) {
        toast.error('At least one approver is required.');
        return;
    }

    const idCounts = {};
    form.approver.forEach((item) => {
        if (item.approver_id) {
            idCounts[item.approver_id] = (idCounts[item.approver_id] || 0) + 1;
        }
    });

    let hasEmptyApprover = false;
    let hasDuplicate = false;

    form.approver.forEach((item, index) => {
        const fieldKey = `approver.${index}.approver_id`;

        if (!item.approver_id) {
            form.setError(fieldKey, 'Approver is required');
            hasEmptyApprover = true;
            isValid = false;
        } 
        else if (idCounts[item.approver_id] > 1) {
            form.setError(fieldKey, 'This approver is duplicated');
            hasDuplicate = true;
            isValid = false;
        }
    });

    if (hasEmptyApprover) {
        toast.error('Please select an approver for all rows.');
    }

    if (hasDuplicate) {
        toast.error('Duplicate approver found, please check it.');
    }

    if (!isValid) {
        return;
    }

    form.post('/approval-setup', {
        preserveScroll: true,
        onError: (errors) => {
            const firstError = Object.values(errors)[0];
            toast.error(firstError || 'Failed to save data.');
        }
    });
};

</script>

<template>
    <Head title="Create New Approval Setup"/>

    <div class="flex min-h-screen bg-slate-50 font-sans antialiased text-slate-800">
        <div class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8 pb-0">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">Create Approval Setup</h1>
                    <p class="text-xs text-slate-500 mt-1">Create new process approval flow configuration.</p>
                </div>
                <div class="sticky top-0 z-40 bg-white/95 backdrop-blur-sm border-b border-slate-200 px-3 py-2 shadow-sm">
                    <div class="flex items-center justify-between w-full">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-1.5">
                                <Link href="/approval-setup" class="group flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-bold text-slate-500 hover:text-blue-600 hover:bg-blue-50/55 transition-colors active:scale-95">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform group-hover:-translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                    </svg>
                                    <span class="hidden sm:inline">Back</span>
                                </Link>
                                
                                <div class="w-px h-6 bg-slate-300 mx-1"></div>

                                <button @click="validateAndSave" :disabled="form.processing"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-sm transition active:scale-95 disabled:opacity-70">
                                    <svg v-if="form.processing" class="animate-spin h-3.5 w-3.5 text-white" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle></svg>
                                    <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                    Save
                                </button>

                                <button @click="cancelForm"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs transition-all active:scale-95 shadow-sm">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <div class="bg-white border border-slate-200/80 shadow-sm p-6 mb-6">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                        <!-- Module Selection -->
                        <div class="lg:col-span-3">
                            <div class="flex gap-1.5 mb-2">
                                <label class="block text-[10px] font-extrabold text-slate-600 uppercase tracking-wider">
                                    Module <span class="text-rose-500">*</span>
                                </label>
                            </div>
                            <div class="relative">
                                <div
                                    v-if="isModuleDropdownOpen"
                                    @click="isModuleDropdownOpen = false"
                                    class="fixed inset-0 z-0"
                                ></div>

                                <div
                                    @click="isModuleDropdownOpen = !isModuleDropdownOpen"
                                    class="relative z-20 w-full pl-3 pr-3 py-2 border text-xs focus:outline-none bg-white cursor-pointer flex justify-between items-center transition-all"
                                    :class="[
                                        form.errors.module
                                            ? 'border-rose-500 focus:ring-1 focus:ring-rose-500 text-rose-600'
                                            : 'border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800',
                                    ]"
                                >
                                    <span :class="form.module ? 'text-slate-800 font-semibold uppercase' : 'text-slate-400'">
                                        {{ selectedModuleName }}
                                    </span>
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-4 w-4 text-slate-400 transition-transform duration-200"
                                        :class="{'rotate-180 text-blue-500': isModuleDropdownOpen}"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>

                                <p
                                    v-if="form.errors.module"
                                    class="mt-1 text-[10px] font-bold text-rose-500"
                                >
                                    {{ form.errors.module }}
                                </p>

                                <Transition
                                    enter-active-class="transition duration-100 ease-out"
                                    enter-from-class="transform scale-95 opacity-0"
                                    enter-to-class="transform scale-100 opacity-100"
                                    leave-active-class="transition duration-75 ease-out"
                                    leave-from-class="transform scale-100 opacity-100"
                                    leave-to-class="transform scale-95 opacity-0"
                                >
                                    <div
                                        v-if="isModuleDropdownOpen"
                                        class="absolute z-30 w-full mt-1 bg-white border border-slate-200 shadow-xl overflow-hidden"
                                    >
                                        <div class="p-2 border-b border-slate-100 bg-slate-50 sticky top-0">
                                            <div class="relative">
                                                <svg class="absolute left-2 top-2 h-3.5 w-3.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                </svg>
                                                <input
                                                    type="text"
                                                    v-model="moduleSearch"
                                                    @click.stop
                                                    class="w-full pl-7 pr-2 py-1.5 border border-slate-200 text-xs rounded-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 bg-white"
                                                    placeholder="Search module..."
                                                    autofocus
                                                />
                                            </div>
                                        </div>

                                        <div class="max-h-48 overflow-y-auto">
                                            <div
                                                v-for="mod in filteredModules"
                                                :key="mod.id"
                                                @click="selectModule(mod.id)"
                                                class="px-3 py-2.5 text-xs cursor-pointer hover:bg-blue-50 hover:text-blue-700 transition-colors border-b border-slate-50 last:border-0 uppercase font-medium"
                                                :class="{'bg-blue-50 text-blue-700 font-bold border-l-2 border-l-blue-600': form.module === mod.id}"
                                            >
                                                {{ mod.name }}
                                            </div>
                                            
                                            <div v-if="filteredModules.length === 0" class="px-3 py-6 text-xs text-center text-slate-400 italic bg-slate-50">
                                                No module found matching "{{ moduleSearch }}"
                                            </div>
                                        </div>
                                    </div>
                                </Transition>
                            </div>
                        </div>

                        <!-- Revision -->
                        <div class="lg:col-span-2 flex flex-col justify-start">
                            <div class="flex items-center gap-1.5 mb-2">
                                <label class="block text-[10px] font-extrabold text-slate-600 uppercase tracking-wider">
                                    Revision
                                </label>
                            </div>
                            <div class="w-full flex">
                                <div class="inline-flex items-center justify-center gap-1.5 px-2 py-2 bg-sky-50 border border-sky-200 text-sky-700 w-full shadow-sm select-none cursor-not-allowed whitespace-nowrap">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                                    </svg>
                                    <span class="text-xs font-black tracking-widest">v.<span class="text-sm">0</span></span>
                                    <input type="hidden" name="revision" value="0">
                                </div>
                            </div>
                            <p class="mt-1.5 text-[10px] font-medium text-slate-400 text-center">Initial</p>
                        </div>

                        <!-- Remark -->
                        <div class="lg:col-span-7">
                            <div class="flex items-center gap-1.5 mb-2">
                                <label class="block text-[10px] font-extrabold text-slate-600 uppercase tracking-wider">
                                    Remark
                                </label>
                            </div>
                            <textarea v-model="form.remark" rows="2" placeholder="Write additional information here..." class="w-full px-4 py-2 bg-slate-50 border border-slate-200 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 text-xs font-medium text-slate-700 transition-all outline-none resize-none leading-relaxed"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Table Section -->
                <div class="w-full bg-white border border-slate-200/80 shadow-sm overflow-hidden mb-6 flex flex-col">
                    <div class="overflow-auto border-t border-slate-100 p-4 flex items-center gap-5">
                        <button 
                            @click="onNew" 
                            type="button"
                            class="text-sm text-[12px] text-slate-600 hover:text-blue-600 hover:underline hover:cursor-pointer underline-offset-4 transition-all duration-200"
                        >
                            New
                        </button>

                        <button 
                            @click="onInsert" 
                            type="button"
                            class="text-sm text-[12px] text-slate-600 hover:text-blue-600 hover:underline hover:cursor-pointer underline-offset-4 transition-all duration-200"
                        >
                            Insert
                        </button>

                        <button 
                            @click="onDelete" 
                            type="button"
                            class="text-sm text-[12px] text-slate-600 hover:text-red-600 hover:underline hover:cursor-pointer underline-offset-4 transition-all duration-200"
                        >
                            Delete
                        </button>

                        <button 
                            @click="onDeleteAll" 
                            type="button"
                            class="text-sm text-[12px] text-slate-600 hover:text-red-700 hover:underline hover:cursor-pointer underline-offset-4 transition-all duration-200"
                        >
                            Delete All
                        </button>
                    </div>

                    <div class="overflow-auto max-h-[80vh]">
                        <table class="w-full min-w-max divide-y divide-slate-200 text-left whitespace-nowrap">
                            <thead class="bg-blue-100 text-[10px] font-extrabold text-slate-500 uppercase tracking-wider sticky top-0 z-20 shadow-sm">
                                <tr>
                                    <th class="px-4 py-3 text-center w-16">No.</th>
                                    <th class="px-4 py-3 min-w-[250px]">Approver Name</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr
                                    v-for="(item, index) in form.approver"
                                    :key="item.row_key" 
                                    @click="selectedRowIndex = index"
                                    @focusin="selectedRowIndex = index"
                                    :class="[
                                        'transition-colors duration-150 cursor-pointer',
                                        selectedRowIndex === index 
                                            ? 'bg-blue-50/80 hover:bg-blue-50 border-l-4 border-l-blue-500' 
                                            : 'hover:bg-slate-50/50'
                                    ]"
                                >
                                    <td class="px-4 py-3 text-center text-xs font-semibold text-slate-600">
                                        <div class="py-2">{{ index + 1 }}.</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <!-- Custom Select Dropdown per Row -->
                                        <div
                                            @click.stop="(e) => {
                                                activeDropdownRow = activeDropdownRow === index ? null : index;
                                                if (activeDropdownRow === index) {
                                                    const rect = e.currentTarget.getBoundingClientRect();
                                                    dropdownStyle = {
                                                        top: (rect.bottom + 4) + 'px',
                                                        left: rect.left + 'px',
                                                        width: rect.width + 'px'
                                                    };
                                                }
                                            }"
                                            class="relative z-20 w-full pl-3 pr-3 py-2 border text-xs focus:outline-none cursor-pointer flex justify-between items-center transition-all shadow-sm"
                                            :class="[
                                                form.errors[`approver.${index}.approver_id`]
                                                    ? 'border-rose-500 bg-rose-50/50 text-rose-900 focus:border-rose-500 focus:ring-1 focus:ring-rose-500'
                                                    : 'border-slate-300 bg-white text-slate-800 hover:border-slate-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500'
                                            ]"
                                        >
                                            <span 
                                                :class="{
                                                    'text-slate-800 font-semibold': item.approver_id && !form.errors[`approver.${index}.approver_id`],
                                                    'text-rose-900 font-semibold': item.approver_id && form.errors[`approver.${index}.approver_id`],
                                                    'text-slate-400': !item.approver_id && !form.errors[`approver.${index}.approver_id`],
                                                    'text-rose-500 font-medium': !item.approver_id && form.errors[`approver.${index}.approver_id`]
                                                }"
                                            >
                                                {{ getSelectedUserName(item.approver_id) }}
                                            </span>

                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                class="h-4 w-4 transition-transform duration-200"
                                                :class="[
                                                    activeDropdownRow === index ? 'rotate-180 text-blue-500' : '',
                                                    form.errors[`approver.${index}.approver_id`] && activeDropdownRow !== index ? 'text-rose-500' : 'text-slate-400'
                                                ]"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                            >
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </div>

                                        <!-- Message Error di Bawah Box -->
                                        <p
                                            v-if="form.errors[`approver.${index}.approver_id`]"
                                            class="mt-1 text-[10px] font-bold text-rose-500 flex items-center gap-1"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                            <span>{{ form.errors[`approver.${index}.approver_id`] }}</span>
                                        </p>

                                        <!-- Teleport untuk Panel Dropdown User -->
                                        <Teleport to="body">
                                            <div
                                                v-if="activeDropdownRow === index"
                                                @click="activeDropdownRow = null"
                                                class="fixed inset-0 z-40"
                                            ></div>
                                            <Transition
                                                enter-active-class="transition duration-100 ease-out"
                                                enter-from-class="transform scale-95 opacity-0"
                                                enter-to-class="transform scale-100 opacity-100"
                                                leave-active-class="transition duration-75 ease-out"
                                                leave-from-class="transform scale-100 opacity-100"
                                                leave-to-class="transform scale-95 opacity-0"
                                            >
                                                <div
                                                    v-if="activeDropdownRow === index"
                                                    :style="dropdownStyle"
                                                    class="fixed z-50 bg-white border border-slate-200 shadow-xl overflow-hidden"
                                                >
                                                    <div class="p-2 border-b border-slate-100 bg-slate-50 sticky top-0">
                                                        <div class="relative">
                                                            <svg class="absolute left-2 top-2 h-3.5 w-3.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                            </svg>
                                                            <input
                                                                type="text"
                                                                v-model="userSearch"
                                                                @click.stop
                                                                class="w-full pl-7 pr-2 py-1.5 border border-slate-200 text-xs rounded-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 bg-white"
                                                                placeholder="Type to search user..."
                                                                autofocus
                                                            />
                                                        </div>
                                                    </div>
                                                    <div class="max-h-48 overflow-y-auto">
                                                        <div
                                                            v-for="user in filteredUsers"
                                                            :key="user.id"
                                                            @click="selectUser(index, user.id)"
                                                            class="px-3 py-2.5 text-xs cursor-pointer hover:bg-blue-50 hover:text-blue-700 transition-colors border-b border-slate-50 last:border-0"
                                                            :class="{'bg-blue-50 text-blue-700 font-bold border-l-2 border-l-blue-600': item.approver_id === user.id}"
                                                        >
                                                            {{ user.name }}
                                                        </div>
                                                        
                                                        <div v-if="filteredUsers.length === 0" class="px-3 py-6 text-xs text-center text-slate-400 italic bg-slate-50">
                                                            No user found matching "{{ userSearch }}"
                                                        </div>
                                                    </div>
                                                </div>
                                            </Transition>
                                        </Teleport>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>