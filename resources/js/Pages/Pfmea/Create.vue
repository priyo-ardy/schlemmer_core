<script setup>
import "vue3-toastify/dist/index.css";
import { ref, watch, computed, isRef, onMounted, nextTick, onUnmounted } from "vue";
import { Head, router, Link, usePage, useForm } from "@inertiajs/vue3";
import { toast } from "vue3-toastify";
import { debounce } from "lodash";
import axios from "axios";
import dayjs from "dayjs";
import "dayjs/locale/id";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

defineOptions({ layout: AuthenticatedLayout , inheritAttrs: false});

const props = defineProps({
    departments: {
        type: Array,
        default: () => []
    },
    materials: {
        type: Array,
        default: () => {}
    }
})

const page = usePage();
const errors = computed(() => page.props.errors || {});
const isDeptDropDownOpen = ref (false);
const deptSearch = ref('');
const searchInputDept = ref(null);
const highlightedDeptIndex = ref(-1);

const form = useForm({
    code: '',
    date: '',
    department_id: '',
    version: '',
    scope: '',
    material_id: '',
    process_responsibility: '',
    prepared_by: '',
    reviewed_by: '',
    approved_by: '',
});

// Filter dept berdasarkan pencarian
const filteredDept = computed(() => {
    if (!deptSearch.value) return props.departments;
    const lowerSearch = deptSearch.value.toLowerCase();
    return props.departments.filter(c => c.name.toLowerCase().includes(lowerSearch));
});

watch(deptSearch, () => {
    highlightedIndex.value = filteredDept.value.length > 0 ? 0 : -1;
});

const toggleDeptDropdown = async () => {
    isDeptDropDownOpen.value = !isDeptDropDownOpen.value;
    
    if (isDeptDropDownOpen.value) {
        highlightedIndex.value = filteredDept.value.length > 0 ? 0 : -1;
        // Tunggu Vue selesai render input, baru set focus
        await nextTick();
        searchInput.value?.focus();
    }
};

const moveDown = () => {
    if (highlightedIndex.value < filteredDept.value.length - 1) {
        highlightedIndex.value++;
        ensureVisible();
    }
};

const moveUp = () => {
    if (highlightedIndex.value > 0) {
        highlightedIndex.value--;
        ensureVisible();
    }
};

const selectHighlighted = () => {
    // Jika ada item yang di-highlight, pilih item tersebut
    if (highlightedIndex.value >= 0 && highlightedIndex.value < filteredDept.value.length) {
        const targetDept = filteredDept.value[highlightedIndex.value];
        selectDept(targetDept.id);
    }
};

// Nampilin department yang dipilih;
const selectedDeptName = computed(() => {
    if(!form.department_id) return "Select department ...";

    const dept = props.departments.find(c => c.id === form.department_id);
    return dept ? `${dept.short_name} - ${dept.name}` : "Select department ...";
});

const selectDept = (id) => {
    form.department_id = id;
    isDeptDropDownOpen.value = false;
    deptSearch.value = "";
    highlightedIndex.value = -1;
}

const optionsList = ref(null);
const ensureVisible = () => {
    nextTick(() => {
        const listEl = optionsList.value;
        if (!listEl) return;
        
        const activeEl = listEl.children[highlightedIndex.value];
        if (!activeEl) return;

        const listScrollTop = listEl.scrollTop;
        const listHeight = listEl.clientHeight;
        const activeTop = activeEl.offsetTop;
        const activeHeight = activeEl.clientHeight;

        if (activeTop + activeHeight > listScrollTop + listHeight) {
            listEl.scrollTop = activeTop + activeHeight - listHeight;
        } else if (activeTop < listScrollTop) {
            listEl.scrollTop = activeTop;
        }
    });
};

// Buat document scope
const PRODUCTION_STAGES = [
    { id: 'prototype', name: 'Prototype', short_name: 'PROTO' },
    { id: 'pre_launch', name: 'Pre-Launch', short_name: 'PRE' },
    { id: 'containment_epc', name: 'Containment EPC', short_name: 'EPC' },
    { id: 'mass_production', name: 'Mass Production', short_name: 'MP' }
];

const isStageDropDownOpen = ref(false);
const stageSearch = ref('');
const highlightedStageIndex = ref(-1);

const searchStageInput = ref(null);
const optionsStageList = ref(null);

const ensureStageVisible = () => {
    nextTick(() => {
        const listEl = optionsStageList.value;
        if (!listEl) return;
        
        const activeEl = listEl.children[highlightedStageIndex.value];
        if (!activeEl) return;

        const listScrollTop = listEl.scrollTop;
        const listHeight = listEl.clientHeight;
        const activeTop = activeEl.offsetTop;
        const activeHeight = activeEl.clientHeight;

        if (activeTop + activeHeight > listScrollTop + listHeight) {
            listEl.scrollTop = activeTop + activeHeight - listHeight;
        } else if (activeTop < listScrollTop) {
            listEl.scrollTop = activeTop;
        }
    });
};

const filteredStages = computed(() => {
    if (!stageSearch.value) return PRODUCTION_STAGES;
    const lowerSearch = stageSearch.value.toLowerCase();
    return PRODUCTION_STAGES.filter(s => 
        s.name.toLowerCase().includes(lowerSearch) || 
        s.short_name.toLowerCase().includes(lowerSearch)
    );
});

const selectedStageName = computed(() => {
    if (!form.scope) return "Document scope  ...";
    const stage = PRODUCTION_STAGES.find(s => s.id === form.scope);
    return stage ? `${stage.name}` : "Select document scope ...";
});

watch(stageSearch, () => {
    highlightedStageIndex.value = filteredStages.value.length > 0 ? 0 : -1;
});

const toggleStagesDropdown = async () => {
    isStageDropDownOpen.value = !isStageDropDownOpen.value;
    if (isStageDropDownOpen.value) {
        highlightedStageIndex.value = filteredStages.value.length > 0 ? 0 : -1;
        await nextTick();
        searchStageInput.value?.focus();
    }
};

const moveStageDown = () => {
    if (highlightedStageIndex.value < filteredStages.value.length - 1) {
        highlightedStageIndex.value++;
        ensureStageVisible();
    }
};

const moveStageUp = () => {
    if (highlightedStageIndex.value > 0) {
        highlightedStageIndex.value--;
        ensureStageVisible();
    }
};

const selectStageHighlighted = () => {
    if (highlightedStageIndex.value >= 0 && highlightedStageIndex.value < filteredStages.value.length) {
        const target = filteredStages.value[highlightedStageIndex.value];
        selectStage(target.id);
    }
};

const selectStage = (id) => {
    form.scope = id;
    isStageDropDownOpen.value = false;
    stageSearch.value = "";
    highlightedStageIndex.value = -1;
};
// end document scope

// Dropdown material
const isMaterialOpen = ref(false);
const materialSearch = ref('');
const highlightedMaterialIndex = ref(-1);

const dropdownMaterials = ref([]);
const materialPage = ref(1);
const isMaterialLoading = ref(false);

const searchMaterialInput = ref(null);
const optionsMaterialList = ref(null);
const activeSelectedMaterial = ref(null);

const hasMore = ref(true);

const selectedMaterialName = computed(() => {
    if(!form.material_id) return "Select material...";
    return activeSelectedMaterial.value 
        ? `[${activeSelectedMaterial.value.code}] - ${activeSelectedMaterial.value.name}`
        : "Select material ...";
});

const fetchMaterials = async (isNewSearch = false) => {
    if (isMaterialLoading.value) return;

    if (isNewSearch) {
        materialPage.value = 1;
        dropdownMaterials.value = [];
        hasMore.value = true;
    }

    if (!hasMore.value) return;

    isMaterialLoading.value = true;
    try {
        const response = await axios.get('/api/materials', {
            params: {
                search: materialSearch.value,
                page: materialPage.value
            }
        });

        dropdownMaterials.value = [...dropdownMaterials.value, ...response.data.data];
        hasMore.value = response.data.has_more;

        if (hasMore.value) {
            materialPage.value++;
        }
    } catch (error) {
        console.error("Gagal load material:", error);
    } finally {
        isMaterialLoading.value = false;
    }
};

const handleMaterialScroll = (e) => {
    const { scrollTop, clientHeight, scrollHeight } = e.target;
    if (scrollHeight - scrollTop - clientHeight < 10) {
        fetchMaterials(false);
    }
};

let debounceTimeout = null;
const handleSearch = () => {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
        fetchMaterials(true);
    }, 400);
};

// Toggle Dropdown Function
const toggleMaterialDropdown = async () => {
    isMaterialOpen.value = !isMaterialOpen.value;
    if (isMaterialOpen.value) {
        highlightedMaterialIndex.value = 0;
        // Ambil data pertama kali jika array lokal masih kosong
        if (dropdownMaterials.value.length === 0) {
            await fetchMaterials(true);
        }
        await nextTick();
        searchMaterialInput.value?.focus();
    }
};

const selectMaterial = (mat) => {
    form.material_id = mat.id;
    activeSelectedMaterial.value = mat;
    isMaterialOpen.value = false;
    materialSearch.value = "";
    fetchMaterials(true);
};

const moveMaterialDown = () => {
    if (highlightedMaterialIndex.value < dropdownMaterials.value.length - 1) {
        highlightedMaterialIndex.value++;
        ensureMaterialVisible();
    }
};

const moveMaterualUp = () => {
    if (highlightedMaterialIndex.value > 0) {
        highlightedMaterialIndex.value--;
        ensureMaterialVisible();
    }
};

const selectMaterialHighlighted = () => {
    if (highlightedMaterialIndex.value >= 0 && highlightedMaterialIndex.value < dropdownMaterials.value.length) {
        selectMaterial(dropdownMaterials.value[highlightedMaterialIndex.value]);
    }
};

const ensureMaterialVisible = () => {
    nextTick(() => {
        const listEl = optionsMaterialList.value;
        if (!listEl) return;
        const activeEl = listEl.children[highlightedMaterialIndex.value];
        if (!activeEl) return;
        if (activeEl.offsetTop + activeEl.clientHeight > listEl.scrollTop + listEl.clientHeight) {
            listEl.scrollTop = activeEl.offsetTop + activeEl.clientHeight - listEl.clientHeight;
        } else if (activeEl.offsetTop < listEl.scrollTop) {
            listEl.scrollTop = activeEl.offsetTop;
        }
    });
};

const clearMaterial = () => {
    form.material_id = null;
    activeSelectedMaterial.value = null;
    highlightedMaterialIndex.value = -1;
    materialSearch.value = "";
    fetchMaterials(true); // Reset list dropdown ke kondisi awal/halaman 1
};
// End Dropdown material


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

// Buat save
const validateAndSave = () => {
    toast.info("Button save di clik");
}

// buat cancel form
const cancelForm = () => {
    form.clearErrors();
    router.get('/pfmea');
}
</script>

<template>
    <Head title="Create New PFMEA Document."/>
    <!-- Page wrapper -->
    <div class="flex min-h-screen bg-slate-50 font-sans antialiased text-slate-800">
        <!-- Content Wrapper -->
        <div class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">
            <!-- Heading -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                <!-- Page Head -->
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">Create PMFEA Process Function</h1>
                    <p class="text-xs text-slate-500 mt-1">Create new PMFEA template process function.</p>
                </div>
                <div class="sticky top-0 z-40 bg-white/95 backdrop-blur-sm border-b border-slate-200 px-3 py-2 shadow-sm">
                    <div class="flex items-center justify-between w-full">
                        <div class="flex items-center gap-3">
                            <!-- Toolbar -->
                            <div class="flex items-center gap-1.5">
                                <Link href="/pfmea" class="group flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-bold text-slate-500 hover:text-blue-600 hover:bg-blue-50/50 transition-colors active:scale-95">
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

            <!-- Form header -->
            <div class="bg-white border border-slate-200/80 shadow-sm p-6 mb-6">
                <div class="grid grid-cols-1 lg:grid-cols-10 gap-6 items-start">
                    <div class="lg:col-span-3">
                        <div class="flex items-center gap-1.5 mb-2">
                            <label class="block text-[10px] font-extrabold text-slate-600 uppercase tracking-wider">
                                Document No. <span class="text-rose-500">*</span>
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
                            <input type="text" v-model="form.code" placeholder="ISLMGxxxxFMEA01(00)" maxlength="255" autocomplete="off" 
                            :class="[
                                'w-full pl-10 pr-4 py-2.5 bg-slate-50 border focus:bg-white focus:ring-2  text-xs font-medium transition-all outline-none',
                                form.errors.code 
                                    ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-100 text-rose-600' 
                                    : 'border-slate-200 focus:border-blue-500 focus:ring-blue-100 text-slate-700'
                            ]">
                        </div>
                        <p v-if="form.errors.code" class="mt-1.5 text-[10px] font-bold text-rose-500">{{ form.errors.code }}</p>
                        <p v-else class="mt-1.5 text-[10px] font-medium text-slate-400">Unique PFMEA Document No.</p>
                    </div>
                    <div class="lg:col-span-1 flex flex-col justify-start">
                        <div class="flex items-center gap-1.5 mb-2">
                            <label class="block text-[10px] font-extrabold text-slate-600 uppercase tracking-wider">
                                Revision
                            </label>
                        </div>
                        <div class="w-full flex">
                            <div class="inline-flex items-center justify-center gap-1.5 px-2 py-2 bg-sky-50 border border-sky-200 text-sky-700  w-full shadow-sm select-none cursor-not-allowed whitespace-nowrap">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                                </svg>
                                <span class="text-xs font-black tracking-widest">v.<span class="text-sm">0</span></span>
                                <input type="hidden" name="revision" value="0">
                            </div>
                        </div>
                        <p class="mt-1.5 text-[10px] font-medium text-slate-400 text-center">Initial</p>
                    </div>
                    <div class="lg:col-span-2">
                        <label class="block text-[10px] font-extrabold text-slate-600 uppercase tracking-wider">
                            Issue Date <span class="text-rose-500">*</span>
                        </label>
                        <div>
                            <input type="date" v-model="form.issue_date" 
                            :class="[
                                'w-full pl-2 pr-4 py-2.5 bg-slate-50 border focus:bg-white focus:ring-2  text-xs font-medium transition-all outline-none mt-2',
                                form.errors.issue_date 
                                    ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-100 text-rose-600' 
                                    : 'border-slate-200 focus:border-blue-500 focus:ring-blue-100 text-slate-700'
                            ]">
                        </div>
                    </div>
                    <div class="lg:col-span-2">
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">
                            Issued Department <span class="text-bold text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <div
                                v-if="isDeptDropDownOpen"
                                @click="isDeptDropDownOpen = false"
                                class="fixed inset-0 z-0"
                            ></div>

                            <div
                                @click="toggleDeptDropdown"
                                class="relative z-20 w-full pl-3 pr-3 py-2.5 border text-xs focus:outline-none focus:border-blue-500 bg-white cursor-pointer flex justify-between items-center transition-all"
                                :class="[
                                    form.errors.department_id
                                    ? 'border-rose-500 focus:border-rose-500 focus:ring-1 focus:ring-rose-500 text-rose-600'
                                    : 'border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800'
                                ]"
                            >
                                <span :class="form.department_id ? 'text-slate-800 font-semibold' : 'text-slate-400'">
                                    {{ selectedDeptName }}
                                </span>
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-4 w-4 text-slate-400 transition-transform duration-200"
                                    :class="{'rotate-180 text-blue-500': isDeptDropDownOpen}"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>

                            <p
                                v-if="form.errors.department_id"
                                class="mt-1 text-[10px] font-bold text-rose-500"
                            >
                                {{ form.errors.department_id }}
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
                                    v-if="isDeptDropDownOpen"
                                    class="absolute z-30 w-full mt-1 bg-white border border-slate-200 shadow-xl overflow-hidden"
                                >
                                    <div class="p-2 border-b border-slate-100 bg-slate-50 sticky top-0">
                                        <div class="relative">
                                            <svg class="absolute left-2 top-2 h-3.5 w-3.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                            </svg>
                                            <input
                                                ref="searchInput"
                                                type="text"
                                                v-model="deptSearch"
                                                @click.stop
                                                @keydown.down.prevent="moveDown"
                                                @keydown.up.prevent="moveUp"
                                                @keydown.enter.prevent="selectHighlighted"
                                                @keydown.esc="isDeptDropDownOpen = false"
                                                class="w-full pl-7 pr-2 py-1.5 border border-slate-200 text-xs rounded-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 bg-white"
                                                placeholder="Type to search..."
                                            />
                                        </div>
                                    </div>

                                    <div ref="optionsList" class="max-h-48 overflow-y-auto">
                                        <div
                                            v-for="(dept, index) in filteredDept"
                                            :key="dept.id"
                                            @click="selectDept(dept.id)"
                                            @mouseenter="highlightedDeptIndex = index"
                                            class="px-3 py-2.5 text-xs cursor-pointer transition-colors border-b border-slate-50 last:border-0"
                                            :class="[
                                            form.department_id === dept.id ? 'border-l-2 border-l-blue-600 font-bold' : '',
                                            index === highlightedDeptIndex ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-700'
                                            ]"
                                        >
                                            {{ dept.short_name }} - {{ dept.name }}
                                        </div>

                                    <div v-if="filteredDept.length === 0" class="px-3 py-6 text-xs text-center text-slate-400 italic bg-slate-50">
                                        Department not found ... "{{ deptSearch }}"
                                    </div>
                                    </div>
                                </div>
                            </Transition>
                        </div>
                    </div>
                    <div class="lg:col-span-2">
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">
                            Document Scope <span class="text-bold text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <div
                                v-if="isStageDropDownOpen"
                                @click="isStageDropDownOpen = false"
                                class="fixed inset-0 z-0"
                            ></div>

                            <div
                                @click="toggleStagesDropdown"
                                class="relative z-20 w-full pl-3 pr-3 py-2.5 border text-xs focus:outline-none focus:border-blue-500 bg-white cursor-pointer flex justify-between items-center transition-all"
                                :class="[
                                    form.errors.scope
                                    ? 'border-rose-500 focus:border-rose-500 focus:ring-1 focus:ring-rose-500 text-rose-600'
                                    : 'border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800'
                                ]"
                            >
                                <span :class="form.scope ? 'text-slate-800 font-semibold' : 'text-slate-400'">
                                    {{ selectedStageName }}
                                </span>
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-4 w-4 text-slate-400 transition-transform duration-200"
                                    :class="{'rotate-180 text-blue-500': isStageDropDownOpen}"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>

                            <p
                                v-if="form.errors.scope"
                                class="mt-1 text-[10px] font-bold text-rose-500"
                            >
                                {{ form.errors.scope }}
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
                                    v-if="isStageDropDownOpen"
                                    class="absolute z-30 w-full mt-1 bg-white border border-slate-200 shadow-xl overflow-hidden"
                                >
                                    <div class="p-2 border-b border-slate-100 bg-slate-50 sticky top-0">
                                        <div class="relative">
                                            <svg class="absolute left-2 top-2 h-3.5 w-3.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                            </svg>
                                            <input
                                                ref="searchStageInput"
                                                type="text"
                                                v-model="stageSearch"
                                                @click.stop
                                                @keydown.down.prevent="moveStageDown"
                                                @keydown.up.prevent="moveStageUp"
                                                @keydown.enter.prevent="selectStageHighlighted"
                                                @keydown.esc="isStageDropDownOpen = false"
                                                class="w-full pl-7 pr-2 py-1.5 border border-slate-200 text-xs rounded-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 bg-white"
                                                placeholder="Type to search scope..."
                                            />
                                        </div>
                                    </div>

                                    <div ref="optionsStageList" class="max-h-48 overflow-y-auto">
                                        <div
                                            v-for="(stage, index) in filteredStages"
                                            :key="stage.id"
                                            @click="selectStage(stage.id)"
                                            @mouseenter="highlightedStageIndex = index"
                                            class="px-3 py-2.5 text-xs cursor-pointer transition-colors border-b border-slate-50 last:border-0"
                                            :class="[
                                                form.scope === stage.id ? 'border-l-2 border-l-blue-600 font-bold' : '',
                                                index === highlightedStageIndex ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-700'
                                            ]"
                                        >
                                            {{ stage.name }}
                                        </div>

                                        <div v-if="filteredStages.length === 0" class="px-3 py-6 text-xs text-center text-slate-400 italic bg-slate-50">
                                            Document scope not found ... "{{ stageSearch }}"
                                        </div>
                                    </div>
                                </div>
                            </Transition>
                        </div>
                    </div>
                    <div class="lg:col-span-3">
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">
                            Part No. <span class="text-bold text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <div
                                v-if="isMaterialOpen"
                                @click="isMaterialOpen = false"
                                class="fixed inset-0 z-0"
                            ></div>

                            <div
                                @click="toggleMaterialDropdown"
                                class="relative z-20 w-full pl-3 pr-3 py-2.5 border text-xs focus:outline-none focus:border-blue-500 bg-white cursor-pointer flex justify-between items-center transition-all"
                                :class="[
                                    form.errors.material_id
                                    ? 'border-rose-500 focus:border-rose-500 focus:ring-1 focus:ring-rose-500 text-rose-600'
                                    : 'border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800'
                                ]"
                            >
                                <span :class="form.material_id ? 'text-slate-800 font-semibold' : 'text-slate-400'">
                                    {{ selectedMaterialName }}
                                </span>

                                <div class="flex items-center space-x-1.5 relative z-30">
                                    <svg
                                        v-if="form.material_id"
                                        @click.stop="clearMaterial"
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-3.5 w-3.5 text-slate-400 hover:text-rose-500 transition-colors duration-150"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                    </svg>

                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-4 w-4 text-slate-400 transition-transform duration-200"
                                        :class="{'rotate-180 text-blue-500': isMaterialOpen}"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </div>

                            <p
                                v-if="form.errors.material_id"
                                class="mt-1 text-[10px] font-bold text-rose-500"
                            >
                                {{ form.errors.material_id }}
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
                                    v-if="isMaterialOpen"
                                    class="absolute z-30 w-full mt-1 bg-white border border-slate-200 shadow-xl overflow-hidden"
                                >
                                    <div class="p-2 border-b border-slate-100 bg-slate-50 sticky top-0">
                                        <div class="relative">
                                            <svg class="absolute left-2 top-2 h-3.5 w-3.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                            </svg>
                                            <input
                                                ref="searchMaterialInput"
                                                type="text"
                                                v-model="materialSearch"
                                                @click.stop
                                                @input="handleSearch"
                                                @keydown.down.prevent="moveMaterialDown"
                                                @keydown.up.prevent="moveMaterualUp"
                                                @keydown.enter.prevent="selectMaterialHighlighted"
                                                @keydown.esc="isMaterialOpen = false"
                                                class="w-full pl-7 pr-2 py-1.5 border border-slate-200 text-xs rounded-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 bg-white"
                                                placeholder="Type to search material..."
                                            />
                                        </div>
                                    </div>

                                    <div 
                                        ref="optionsMaterialList" 
                                        @scroll="handleMaterialScroll"
                                        class="max-h-48 overflow-y-auto"
                                    >
                                        <div
                                            v-for="(mat, index) in dropdownMaterials"
                                            :key="mat.id"
                                            @click="selectMaterial(mat)"
                                            @mouseenter="highlightedMaterialIndex = index"
                                            class="px-3 py-2.5 text-xs cursor-pointer transition-colors border-b border-slate-50 last:border-0"
                                            :class="[
                                            form.material_id === mat.id ? 'border-l-2 border-l-blue-600 font-bold bg-blue-50/30' : '',
                                            index === highlightedMaterialIndex ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-700'
                                            ]"
                                        >
                                            [{{ mat.code }}] - {{ mat.name }}
                                        </div>

                                        <div 
                                            v-if="isMaterialLoading" 
                                            class="px-3 py-2.5 text-center text-[10px] text-blue-600 font-bold bg-slate-50 animate-pulse border-t border-slate-100"
                                        >
                                            Loading items...
                                        </div>

                                        <div 
                                            v-if="dropdownMaterials.length === 0 && !isMaterialLoading" 
                                            class="px-3 py-6 text-xs text-center text-slate-400 italic bg-slate-50"
                                        >
                                            Material not found ... "{{ materialSearch }}"
                                        </div>
                                    </div>
                                </div>
                            </Transition>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>