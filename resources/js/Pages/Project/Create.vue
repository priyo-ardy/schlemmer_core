<script setup>
import "vue3-toastify/dist/index.css";
import { ref, watch, computed, nextTick, onMounted, onUnmounted } from "vue";
import { Head, router, Link, usePage, useForm } from "@inertiajs/vue3";
import { toast } from "vue3-toastify";
import axios from "axios";
import dayjs from "dayjs";
import "dayjs/locale/id";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

defineOptions({ layout: AuthenticatedLayout });

defineProps({});

// formating date
dayjs.locale("id");
const formatLogDate = (date) => {
    if (!date) return "-";
    return dayjs(date).format("DD MMM YYYY, HH:mm:ss [WIB]");
};

const formatTableDate = (date) => {
    if (!date) return "-";
    return dayjs(date).format("DD-MMM-YYYY HH:mm:ss");
};

const page = usePage();
const errors = computed(() => page.props.errors || {});

// Form Details
const createBlankItem = () => ({
    row_key: Math.random().toString(36).substring(2, 9),
    material_id: '',
    specification: '',
    customer_part_name: '',
    selected_material: null,
    errors: { material_id: null } // Tambahkan inisialisasi object error internal
});

// Main Form
const form = useForm({
    code: "",
    name: "",
    customer_id: "",
    vehicle_model: "",
    main_part_number: "",
    main_part_name: "",
    apqp_phase: "",
    status: "",
    kick_off_date: "",
    target_proto_date: "",
    target_ppap_date: "",
    target_sop_date: "",
    confidentiality_level: "",
    revision: "",
    is_active: true,
    remark: "",
    details:[createBlankItem()]
});

const isProcessing = computed(() => form.processing || false);

watch(
    errors,
    (newErrors) => {
        if(newErrors && newErrors.error){
            toast.error(newErrors.error);
        }
    },
    { deep: true }
);

// Dropdown customer
const isCustomerOpen = ref(false);
const customerSearch = ref('');
const highlightedCustomerIndex = ref(-1);
const customerDropDown = ref([]);
const customerPage = ref(1);
const isCustomerLoading = ref(false);
const searchCustomerInput = ref(null);
const optionsCustomerList = ref(null);
const activeSelectedCustomer = ref(null);
const hasMore = ref(true);

const selectedCustomerName = computed(() => {
    if(!form.customer_id) return "Select customer ...";
    return activeSelectedCustomer.value
        ? `[${activeSelectedCustomer.value.code}] - ${activeSelectedCustomer.value.name}`
        : "Select customer ...";
});

const fetchCustomers = async(isNewSearch = false) => {
    if(isCustomerLoading.value) return;

    if(isNewSearch){
        customerPage.value = 1;
        customerDropDown.value = [];
        hasMore.value = true;
    }

    if(!hasMore.value) return;

    isCustomerLoading.value = true;
    try{
        const response = await axios.get('/api/v1/customers', {
            params: {
                search: customerSearch.value,
                page: customerPage.value
            }
        });

        customerDropDown.value = [...customerDropDown.value, ...response.data.data];
        hasMore.value = response.data.has_more;

        if (hasMore.value) {
            customerPage.value++;
        }
    } catch(error){
        console.error("Failed to load customer data: ", error);
    } finally{
        isCustomerLoading.value = false;
    }
}

const handleCustomerScroll = (e) => {
    const { scrollTop, clientHeight, scrollHeight } = e.target;
    if (scrollHeight - scrollTop - clientHeight < 10) {
        fetchCustomers(false);
    }
}

let debounceTimeout = null;
const handlesearch = () => {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
        fetchCustomers(true);
    }, 400);
};

const toggleCustomerDropdown = async () => {
    isCustomerOpen.value = !isCustomerOpen.value;
    if (isCustomerOpen.value) {
        highlightedCustomerIndex.value = 0;
        if (customerDropDown.value.length === 0) {
            await fetchCustomers(true);
        }
        await nextTick();
        searchCustomerInput.value?.focus();
    }
};

const selectCustomer = (mat) => {
    form.customer_id = mat.id;
    activeSelectedCustomer.value = mat;
    isCustomerOpen.value = false;
    customerSearch.value = "";
    fetchCustomers(true);
};

const moveCustomerDown = () => {
    if (highlightedCustomerIndex.value < customerDropDown.value.length - 1) {
        highlightedCustomerIndex.value++;
        ensureCustomerVisible();
    }
};

const moveCustomerlUp = () => {
    if (highlightedCustomerIndex.value > 0) {
        highlightedCustomerIndex.value--;
        ensureCustomerVisible();
    }
};

const selectCustomerHighlighted = () => {
    if (highlightedCustomerIndex.value >= 0 && highlightedCustomerIndex.value < customerDropDown.value.length) {
        selectCustomer(customerDropDown.value[highlightedCustomerIndex.value]);
    }
};

const ensureCustomerVisible = () => {
    nextTick(() => {
        const listEl = optionsCustomerList.value;
        if (!listEl) return;
        const activeEl = listEl.children[highlightedCustomerIndex.value];
        if (!activeEl) return;
        if (activeEl.offsetTop + activeEl.clientHeight > listEl.scrollTop + listEl.clientHeight) {
            listEl.scrollTop = activeEl.offsetTop + activeEl.clientHeight - listEl.clientHeight;
        } else if (activeEl.offsetTop < listEl.scrollTop) {
            listEl.scrollTop = activeEl.offsetTop;
        }
    });
};

const clearCustomer = () => {
    form.customer_id = null;
    activeSelectedCustomer.value = null;
    highlightedCustomerIndex.value = -1;
    customerSearch.value = "";
    fetchCustomers(true);
};


// Toolbar detail
const selectedRowIndex = ref(null);

const onNew = () => {
    form.details.push(createBlankItem());
    selectedRowIndex.value = form.details.length - 1;
}

const onInsert = () => {
    if(selectedRowIndex.value !== null && selectedRowIndex.value !== undefined){
        form.details.splice(selectedRowIndex.value, 0, createBlankItem());
    }else{
        onNew();
    }
}

const OnDelete = () => {
    if(selectedRowIndex.value !== null){
        if(form.details.length > 1) {
            form.details.splice(selectedRowIndex.value, 1);
            if(selectedRowIndex.value >= form.details.length){
                selectedRowIndex.value = form.details.length - 1;
            }
        } else {
            toast.warning("There must be at least one row in the table");
        }
    }else{
        toast.info("Select or click one of the table rows first to delete it.");
    }
}

const onDeleteAll = () => {
    form.details = [createBlankItem()];
    selectedRowIndex.value = 0;
}


// Dropdown material (Floating + Teleport)
const openedRowIndex = ref(null);
const materialSearch = ref('');
const highlightedMaterialIndex = ref(-1);
const materialDropDown = ref([]);
const materialPage = ref(1);
const isMaterialLoading = ref(false);
const searchMaterialInput = ref(null);
const optionsMaterialList = ref(null);
const hasMaterialMore = ref(true);

const dropdownStyle = ref({
    position: 'absolute',
    top: '0px',
    left: '0px',
    width: '0px'
});

const getSelectedMaterialName = (item) => {
    if(!item.material_id) return "Select material ...";
    return item.selected_material
        ? `[${item.selected_material.code}] - ${item.selected_material.name}`
        : "Select material ...";
};

const fetchMaterials = async(isNewSearch = false) => {
    if(isMaterialLoading.value) return;

    if(isNewSearch){
        materialPage.value = 1;
        materialDropDown.value = [];
        hasMaterialMore.value = true;
    }

    if(!hasMaterialMore.value) return;

    isMaterialLoading.value = true;
    try {
        const response = await axios.get('/api/v1/materials', {
            params: {
                search: materialSearch.value,
                page: materialPage.value
            }
        });

        materialDropDown.value = [...materialDropDown.value, ...response.data.data];
        hasMaterialMore.value = response.data.has_more;

        if (hasMaterialMore.value) {
            materialPage.value++;
        }
    } catch(error) {
        console.error("Failed to load material data: ", error);
    } finally {
        isMaterialLoading.value = false;
    }
}

const handleMaterialScroll = (e) => {
    const { scrollTop, clientHeight, scrollHeight } = e.target;
    if (scrollHeight - scrollTop - clientHeight < 10) {
        fetchMaterials(false);
    }
}

let materialDebounceTimeout = null;
const handleMaterialSearch = () => {
    clearTimeout(materialDebounceTimeout);
    materialDebounceTimeout = setTimeout(() => {
        fetchMaterials(true);
    }, 400);
};

const updateDropdownPosition = (index) => {
    const triggerEl = document.getElementById(`material-trigger-${index}`);
    if (triggerEl) {
        const rect = triggerEl.getBoundingClientRect();
        dropdownStyle.value = {
            position: 'fixed',
            top: `${rect.bottom + window.scrollY}px`,
            left: `${rect.left + window.scrollX}px`,
            width: `${rect.width}px`,
            zIndex: '9999'
        };
    }
};

const toggleMaterialDropdown = async (index) => {
    if (openedRowIndex.value === index) {
        openedRowIndex.value = null;
    } else {
        openedRowIndex.value = index;
        highlightedMaterialIndex.value = 0;
        materialSearch.value = "";
        
        await nextTick();
        updateDropdownPosition(index);

        if (materialDropDown.value.length === 0) {
            await fetchMaterials(true);
        }
        await nextTick();
        
        // FIX BUG: Karena di dalam v-for, ref berupa array. Kita ambil sesuai index baris yang dibuka.
        if (searchMaterialInput.value && searchMaterialInput.value[index]) {
            searchMaterialInput.value[index].focus();
        } else if (searchMaterialInput.value && typeof searchMaterialInput.value.focus === 'function') {
            // Fallback jika Vue membacanya sebagai single element pada kondisi tertentu
            searchMaterialInput.value.focus();
        }
    }
};

const selectMaterial = (mat, index) => {
    const item = form.details[index];
    if (item) {
        item.material_id = mat.id;
        item.selected_material = mat;
        
        item.specification = mat.specification || mat.spec || '';
        item.customer_part_name = mat.customer_part_name || '';
        if (item.errors) item.errors.material_id = null; // Reset error saat dipilih ulang
    }
    openedRowIndex.value = null;
    materialSearch.value = "";
    fetchMaterials(true);
};

const moveMaterialDown = () => {
    if (highlightedMaterialIndex.value < materialDropDown.value.length - 1) {
        highlightedMaterialIndex.value++;
        ensureMaterialVisible();
    }
};

const moveMaterialUp = () => {
    if (highlightedMaterialIndex.value > 0) {
        highlightedMaterialIndex.value--;
        ensureMaterialVisible();
    }
};

const selectMaterialHighlighted = (index) => {
    if (highlightedMaterialIndex.value >= 0 && highlightedMaterialIndex.value < materialDropDown.value.length) {
        selectMaterial(materialDropDown.value[highlightedMaterialIndex.value], index);
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

const clearMaterial = (index) => {
    const item = form.details[index];
    if (item) {
        item.material_id = null;
        item.selected_material = null;
        item.specification = '';
        item.customer_part_name = '';
        if (item.errors) item.errors.material_id = null;
    }
    highlightedMaterialIndex.value = -1;
    materialSearch.value = "";
    fetchMaterials(true);
};

const handleWindowResizeOrScroll = () => {
    if (openedRowIndex.value !== null) {
        updateDropdownPosition(openedRowIndex.value);
    }
};

onMounted(() => {
    window.addEventListener('resize', handleWindowResizeOrScroll);
    window.addEventListener('scroll', handleWindowResizeOrScroll, true);
});

onUnmounted(() => {
    window.removeEventListener('resize', handleWindowResizeOrScroll);
    window.removeEventListener('scroll', handleWindowResizeOrScroll, true);
});

const validateAndSave = () => {
    form.clearErrors();
    let isValid = true;

    if(!form.code){
        form.setError('code', 'This field is required');
        isValid = false;
    }else if(form.code.length > 50){
        form.setError('code', 'Project code cannot exceed 50 character');
        isValid = false;
    }

    if(!form.name){
        form.setError('name', 'This field is required');
        isValid = false;
    }else if(form.name.length > 150){
        form.setError('name', 'Project name cannot exceed 150 character');
        isValid = false;
    }

    if(!form.customer_id){
        form.setError('customer_id', 'Customer is required');
        isValid = false;
    }

    if(!form.confidentiality_level){
        form.setError('confidentiality_level', 'This field is required');
        isValid = false;
    }

    // Validasi details
    if (!form.details || form.details.length === 0) {
        toast.error("Detail data cannot be empty. Please add at least one material.");
        return;
    }

    const seenMaterialIds = new Set();
    let hasDuplicate = false;
    let hasEmptyMaterial = false;

    form.details.forEach((item, index) => {
        // Pastikan kontainer object errors internal siap digunakan
        if (!item.errors) item.errors = {};
        item.errors.material_id = null;

        if (!item.material_id) {
            item.errors.material_id = "Material is required";
            hasEmptyMaterial = true;
            isValid = false;
        } else {
            if (seenMaterialIds.has(item.material_id)) {
                // Sesuai Rekuest: Menampilkan teks keterangan spesifik di baris duplikat
                item.errors.material_id = "This part no. is duplicate";
                hasDuplicate = true;
                isValid = false;
            } else {
                seenMaterialIds.add(item.material_id);
            }
        }
    });

    if (hasEmptyMaterial) {
        toast.error("Please select a material for all rows.");
    }
    if (hasDuplicate) {
        toast.error("Duplicate materials found in the details table.");
    }

    if(!isValid) return;

    form.post("/projects", {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0];
            toast.error(firstError);
        },
    });
};

const cancelForm = () => {
    router.visit('/projects');
};

// Dropdown Confidentiality Level
const isConfidentialityLevelDropdownOpen = ref(false);
const ConfidentialityLevelSearch = ref("");
const searchConfidentialityInput = ref(null);

const confidentCategory = [
    { value: 'public', label: 'Public' },
    { value: 'internal', label: 'Internal' },
    { value: 'confidential', label: 'Confidential' },
    { value: 'strictly_confidential', label: 'Strictly Confidential' },
];

const selectedConfidentialityName = computed(() => {
    if(!form.confidentiality_level) return "Select Confidentiality Level ...";
    const match = confidentCategory.find(c => c.value === form.confidentiality_level);
    return match ? match.label : "Select Confidentiality Level ...";
});

const filteredConfidentialityLevel = computed(() => {
    if(!ConfidentialityLevelSearch.value) return confidentCategory;
    const lowerSearch = ConfidentialityLevelSearch.value.toLowerCase();
    return confidentCategory.filter(c => 
        c.label.toLowerCase().includes(lowerSearch) || 
        c.value.toLowerCase().includes(lowerSearch)
    );
});

const selectConfidentialityLevel = (value) => {
    form.confidentiality_level = value;
    isConfidentialityLevelDropdownOpen.value = false;
    ConfidentialityLevelSearch.value = "";
}

const toggleConfidentialityDropdown = async () => {
    isConfidentialityLevelDropdownOpen.value = !isConfidentialityLevelDropdownOpen.value;
    if(isConfidentialityLevelDropdownOpen.value) {
        isProjectStatusDropdownOpen.value = false;
        await nextTick();
        searchConfidentialityInput.value?.focus();
    }
}

const clearConfidentiality = () => {
    form.confidentiality_level = "";
    ConfidentialityLevelSearch.value = "";
};

// Dropdown Project Status
const isProjectStatusDropdownOpen = ref(false);
const projectStatusSearch = ref("");
const searchProjectStatusInput = ref(null);

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

const selectedProjectStatusName = computed(() => {
    if(!form.status) return "Select Project Status ...";
    const match = statusCategory.find(s => s.value === form.status);
    return match ? match.label : "Select Project Status ...";
});

const filteredProjectStatus = computed(() => {
    if(!projectStatusSearch.value) return statusCategory;
    const lowerSearch = projectStatusSearch.value.toLowerCase();
    return statusCategory.filter(s => 
        s.label.toLowerCase().includes(lowerSearch) || 
        s.value.toLowerCase().includes(lowerSearch)
    );
});

const selectProjectStatus = (value) => {
    form.status = value;
    isProjectStatusDropdownOpen.value = false;
    projectStatusSearch.value = "";
}

const toggleProjectStatusDropdown = async () => {
    isProjectStatusDropdownOpen.value = !isProjectStatusDropdownOpen.value;
    if(isProjectStatusDropdownOpen.value) {
        isConfidentialityLevelDropdownOpen.value = false;
        await nextTick();
        searchProjectStatusInput.value?.focus();
    }
}

const clearProjectStatus = () => {
    form.status = "";
    projectStatusSearch.value = "";
};
</script>

<template>
    <Head title="Create New Project"/>
    <div class="flex min-h-screen bg-slate-50 font-sans antialiased text-slate-800">
        <div class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">
                        Create New Project
                    </h1>
                    <p class="text-xs text-slate-500 mt-1">Register new project.</p>
                </div>

                <div class="sticky top-0 z-50 bg-white/95 backdrop-blur-sm border border-slate-200 px-3 py-2 shadow-sm">
                    <div class="flex items-center justify-between w-full">
                        <div class="flex items-center gap-1.5">
                            <Link href="/projects" class="group flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-bold text-slate-500 hover:text-blue-600 hover:bg-blue-50/50 transition-colors active:scale-95">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform group-hover:-translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                </svg>
                                <span class="hidden sm:inline">Back</span>
                            </Link>
                            
                            <div class="w-px h-6 bg-slate-300 mx-1"></div>
                            <button 
                                @click="validateAndSave" 
                                :disabled="isProcessing"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-sm transition active:scale-95 disabled:opacity-70"
                            >
                                <svg v-if="isProcessing" class="animate-spin h-3.5 w-3.5 text-white" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle></svg>
                                <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                Save
                            </button>
                            <button 
                                @click="cancelForm"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs transition-all active:scale-95 shadow-sm">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="bg-white border border-slate-200/80 shadow-sm p-4 mb-6 flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
                <div class="flex-1 space-y-6">
                    <div class="grid grid-cols-4 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Project Code <span class="text-bold text-rose-500">*</span></label>
                            <input
                                type="text"
                                v-model="form.code"
                                maxlength="50"
                                required
                                placeholder="e.g. PRJ-2024-001"
                                :class="[
                                    'w-full pl-3 pr-3 py-2 bg-white border text-xs font-medium focus:outline-none transition-all',
                                    form.errors.code ? 'border-rose-500 focus:border-rose-500 text-rose-600' : 'border-slate-300 focus:border-blue-500 text-slate-800',
                                ]"
                            />
                            <p v-if="form.errors.code" class="mt-1 text-[10px] font-bold text-rose-500">{{ form.errors.code }}</p>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Project Name <span class="text-rose-500">*</span></label>
                            <input
                                type="text"
                                v-model="form.name"
                                maxlength="150"
                                required
                                placeholder="Enter project name ..."
                                :class="[
                                    'w-full pl-3 pr-3 py-2 bg-white border text-xs font-medium focus:outline-none transition-all',
                                    form.errors.name ? 'border-rose-500 focus:border-rose-500 text-rose-600' : 'border-slate-300 focus:border-blue-500 text-slate-800',
                                ]"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-[10px] font-bold text-rose-500">{{ form.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Customer <span class="text-bold text-rose-500">*</span></label>
                            <div class="relative">
                                <div v-if="isCustomerOpen" @click="isCustomerOpen = false" class="fixed inset-0 z-0"></div>
                                <div
                                    @click="toggleCustomerDropdown"
                                    class="relative z-20 w-full pl-3 pr-3 py-2.5 border text-xs focus:outline-none focus:border-blue-500 bg-white cursor-pointer flex justify-between items-center transition-all"
                                    :class="[form.errors.customer_id ? 'border-rose-500 text-rose-600' : 'border-slate-300 text-slate-800']"
                                >
                                    <span :class="form.customer_id ? 'text-slate-800 font-semibold' : 'text-slate-400'">{{ selectedCustomerName }}</span>
                                    <div class="flex items-center space-x-1.5 relative z-30">
                                        <svg v-if="form.customer_id" @click.stop="clearCustomer" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400 hover:text-rose-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400 transition-transform" :class="{'rotate-180 text-blue-500': isCustomerOpen}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </div>
                                <p v-if="form.errors.customer_id" class="mt-1 text-[10px] font-bold text-rose-500">{{ form.errors.customer_id }}</p>

                                <Transition enter-active-class="transition duration-100 ease-out" enter-from-class="transform scale-95 opacity-0" enter-to-class="transform scale-100 opacity-100" leave-active-class="transition duration-75 ease-out" leave-from-class="transform scale-100 opacity-100" leave-to-class="transform scale-95 opacity-0">
                                    <div v-if="isCustomerOpen" class="absolute z-30 w-full mt-1 bg-white border border-slate-200 shadow-xl overflow-hidden">
                                        <div class="p-2 border-b border-slate-100 bg-slate-50 sticky top-0">
                                            <div class="relative">
                                                <svg class="absolute left-2 top-2 h-3.5 w-3.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                                <input ref="searchCustomerInput" type="text" v-model="customerSearch" @click.stop @input="handlesearch" @keydown.down.prevent="moveCustomerDown" @keydown.up.prevent="moveCustomerlUp" @keydown.enter.prevent="selectCustomerHighlighted" @keydown.esc="isCustomerOpen = false" class="w-full pl-7 pr-2 py-1.5 border border-slate-200 text-xs rounded-sm focus:outline-none focus:border-blue-500 bg-white" placeholder="Type to search..."/>
                                            </div>
                                        </div>
                                        <div ref="optionsCustomerList" @scroll="handleCustomerScroll" class="max-h-48 overflow-y-auto">
                                            <div v-for="(customer, index) in customerDropDown" :key="customer.id" @click="selectCustomer(customer)" @mouseenter="highlightedCustomerIndex = index" class="px-3 py-2.5 text-xs cursor-pointer border-b border-slate-50 last:border-0" :class="[form.customer_id === customer.id ? 'border-l-2 border-l-blue-600 font-bold bg-blue-50/30' : '', index === highlightedCustomerIndex ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-700']">
                                                [{{ customer.code }}] - {{ customer.name }}
                                            </div>
                                            <div v-if="isCustomerLoading" class="px-3 py-2.5 text-center text-[10px] text-blue-600 font-bold bg-slate-50 animate-pulse">Loading items...</div>
                                            <div v-if="customerDropDown.length === 0 && !isCustomerLoading" class="px-3 py-6 text-xs text-center text-slate-400 italic bg-slate-50">Customer not found ... "{{ customerSearch }}"</div>
                                        </div>
                                    </div>
                                </Transition>
                            </div>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Vehicle Model</label>
                            <input type="text" v-model="form.vehicle_model" maxlength="100" placeholder="e.g. SUV Type-X" class="w-full pl-3 pr-3 py-2 border text-xs focus:outline-none border-slate-300 focus:border-blue-500 text-slate-800" />
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Main Part Number</label>
                            <input type="text" v-model="form.main_part_number" maxlength="100" placeholder="PN-88291-XX" class="w-full pl-3 pr-3 py-2 border text-xs focus:outline-none border-slate-300 focus:border-blue-500 text-slate-800" />
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Main Part Name</label>
                            <input type="text" v-model="form.main_part_name" maxlength="100" placeholder="Component base identifier" class="w-full pl-3 pr-3 py-2 border text-xs focus:outline-none border-slate-300 focus:border-blue-500 text-slate-800" />
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">APQP Phase</label>
                            <input type="text" v-model="form.apqp_phase" maxlength="50" placeholder="e.g. Phase 1: Planning" class="w-full pl-3 pr-3 py-2 border text-xs focus:outline-none border-slate-300 focus:border-blue-500 text-slate-800" />
                        </div>
                        
                        <!-- Dropdown Project Status -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Project Status</label>
                            <div class="relative">
                                <div
                                    v-if="isProjectStatusDropdownOpen"
                                    @click="isProjectStatusDropdownOpen = false"
                                    class="fixed inset-0 z-0"
                                ></div>

                                <div
                                    @click="toggleProjectStatusDropdown"
                                    class="relative z-20 w-full pl-3 pr-3 py-2.5 border text-xs focus:outline-none focus:border-blue-500 bg-white cursor-pointer flex justify-between items-center transition-all border-slate-300 text-slate-800"
                                >
                                    <span :class="form.status ? 'text-slate-800 font-semibold' : 'text-slate-400'">
                                        {{ selectedProjectStatusName }}
                                    </span>
                                    <div class="flex items-center space-x-1.5 relative z-30">
                                        <svg v-if="form.status" @click.stop="clearProjectStatus" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400 hover:text-rose-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-4 w-4 text-slate-400 transition-transform duration-200"
                                            :class="{'rotate-180 text-blue-500': isProjectStatusDropdownOpen}"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </div>

                                <Transition
                                    enter-active-class="transition duration-100 ease-out"
                                    enter-from-class="transform scale-95 opacity-0"
                                    enter-to-class="transform scale-100 opacity-100"
                                    leave-active-class="transition duration-75 ease-out"
                                    leave-from-class="transform scale-100 opacity-100"
                                    leave-to-class="transform scale-95 opacity-0"
                                >
                                    <div
                                        v-if="isProjectStatusDropdownOpen"
                                        class="absolute z-30 w-full mt-1 bg-white border border-slate-200 shadow-xl overflow-hidden"
                                    >
                                        <div class="p-2 border-b border-slate-100 bg-slate-50 sticky top-0">
                                            <div class="relative">
                                                <svg class="absolute left-2 top-2 h-3.5 w-3.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                                <input
                                                    ref="searchProjectStatusInput"
                                                    type="text"
                                                    v-model="projectStatusSearch"
                                                    @click.stop
                                                    class="w-full pl-7 pr-2 py-1.5 border border-slate-200 text-xs rounded-sm focus:outline-none focus:border-blue-500 bg-white"
                                                    placeholder="Type to search status..."
                                                />
                                            </div>
                                        </div>

                                        <div class="max-h-48 overflow-y-auto">
                                            <div
                                                v-for="item in filteredProjectStatus"
                                                :key="item.value"
                                                @click="selectProjectStatus(item.value)"
                                                class="px-3 py-2.5 text-xs cursor-pointer border-b border-slate-50 last:border-0"
                                                :class="[
                                                    form.status === item.value ? 'border-l-2 border-l-blue-600 font-bold bg-blue-50/30 text-blue-700' : 'text-slate-700 hover:bg-slate-50'
                                                ]"
                                            >
                                                {{ item.label }}
                                            </div>

                                            <div v-if="filteredProjectStatus.length === 0" class="px-3 py-6 text-xs text-center text-slate-400 italic bg-slate-50">
                                                No status found matching "{{ projectStatusSearch }}"
                                            </div>
                                        </div>
                                    </div>
                                </Transition>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Kick Off Date</label>
                            <input type="date" v-model="form.kick_off_date" class="w-full pl-3 pr-3 py-2 border text-xs focus:outline-none border-slate-300 focus:border-blue-500 text-slate-800" />
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Target Proto Date</label>
                            <input type="date" v-model="form.target_proto_date" class="w-full pl-3 pr-3 py-2 border text-xs focus:outline-none border-slate-300 focus:border-blue-500 text-slate-800" />
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Target APQP Date</label>
                            <input type="date" v-model="form.target_ppap_date" class="w-full pl-3 pr-3 py-2 border text-xs focus:outline-none border-slate-300 focus:border-blue-500 text-slate-800" />
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Target SOP Date</label>
                            <input type="date" v-model="form.target_sop_date" class="w-full pl-3 pr-3 py-2 border text-xs focus:outline-none border-slate-300 focus:border-blue-500 text-slate-800" />
                        </div>
                        
                        <!-- Dropdown Confidentiality Level -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">
                                Confidentiality Level <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <div
                                    v-if="isConfidentialityLevelDropdownOpen"
                                    @click="isConfidentialityLevelDropdownOpen = false"
                                    class="fixed inset-0 z-0"
                                ></div>

                                <div
                                    @click="toggleConfidentialityDropdown"
                                    class="relative z-20 w-full pl-3 pr-3 py-2.5 border text-xs focus:outline-none focus:border-blue-500 bg-white cursor-pointer flex justify-between items-center transition-all"
                                    :class="[
                                        form.errors.confidentiality_level
                                            ? 'border-rose-500 text-rose-600'
                                            : 'border-slate-300 text-slate-800'
                                    ]"
                                >
                                    <span :class="form.confidentiality_level ? 'text-slate-800 font-semibold' : 'text-slate-400'">
                                        {{ selectedConfidentialityName }}
                                    </span>
                                    <div class="flex items-center space-x-1.5 relative z-30">
                                        <svg v-if="form.confidentiality_level" @click.stop="clearConfidentiality" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400 hover:text-rose-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-4 w-4 text-slate-400 transition-transform duration-200"
                                            :class="{'rotate-180 text-blue-500': isConfidentialityLevelDropdownOpen}"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </div>

                                <p v-if="form.errors.confidentiality_level" class="mt-1 text-[10px] font-bold text-rose-500">
                                    {{ form.errors.confidentiality_level }}
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
                                        v-if="isConfidentialityLevelDropdownOpen"
                                        class="absolute z-30 w-full mt-1 bg-white border border-slate-200 shadow-xl overflow-hidden"
                                    >
                                        <div class="p-2 border-b border-slate-100 bg-slate-50 sticky top-0">
                                            <div class="relative">
                                                <svg class="absolute left-2 top-2 h-3.5 w-3.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                                <input
                                                    ref="searchConfidentialityInput"
                                                    type="text"
                                                    v-model="ConfidentialityLevelSearch"
                                                    @click.stop
                                                    class="w-full pl-7 pr-2 py-1.5 border border-slate-200 text-xs rounded-sm focus:outline-none focus:border-blue-500 bg-white"
                                                    placeholder="Type to search confidentiality level..."
                                                />
                                            </div>
                                        </div>

                                        <div class="max-h-48 overflow-y-auto">
                                            <div
                                                v-for="item in filteredConfidentialityLevel"
                                                :key="item.value"
                                                @click="selectConfidentialityLevel(item.value)"
                                                class="px-3 py-2.5 text-xs cursor-pointer border-b border-slate-50 last:border-0"
                                                :class="[
                                                    form.confidentiality_level === item.value ? 'border-l-2 border-l-blue-600 font-bold bg-blue-50/30 text-blue-700' : 'text-slate-700 hover:bg-slate-50'
                                                ]"
                                            >
                                                {{ item.label }}
                                            </div>

                                            <div v-if="filteredConfidentialityLevel.length === 0" class="px-3 py-6 text-xs text-center text-slate-400 italic bg-slate-50">
                                                No category found matching "{{ ConfidentialityLevelSearch }}"
                                            </div>
                                        </div>
                                    </div>
                                </Transition>
                            </div>   
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Data Status</label>
                            <div class="flex items-center justify-between p-2 bg-white border border-slate-300 min-h-[38px] transition-all">
                                <div class="flex flex-col min-w-0 pr-4">
                                    <span class="text-[10px] font-black text-slate-700 uppercase tracking-wider">Status</span>
                                    <span class="text-[9px] font-semibold text-slate-400 mt-0.5 truncate">{{ form.is_active ? "Active" : "Disabled" }}</span>
                                </div>
                                <button type="button" @click="form.is_active = !form.is_active" :class="form.is_active ? 'bg-emerald-600' : 'bg-slate-300'" class="relative inline-flex h-5 w-10 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out active:scale-95"><span :class="form.is_active ? 'translate-x-5' : 'translate-x-0'" class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition duration-200"></span></button>
                            </div>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Remark (optional)</label>
                            <textarea v-model="form.remark" placeholder="Write additional information here ..." rows="3" class="w-full pl-3 pr-3 py-2 border text-xs focus:outline-none border-slate-300 focus:border-blue-500 text-slate-800"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="w-full bg-white border border-slate-200/80 shadow-sm overflow-hidden mb-6 flex flex-col">
                <div class="overflow-auto border-t border-slate-100 p-4 flex items-center gap-5">
                    <button @click="onNew" class="text-sm text-[12px] text-slate-600 hover:text-blue-600 hover:underline hover:cursor-pointer underline-offset-4 transition-all duration-200">New</button>
                    <button @click="onInsert" class="text-sm text-[12px] text-slate-600 hover:text-blue-600 hover:underline hover:cursor-pointer underline-offset-4 transition-all duration-200">Insert</button>
                    <button @click="OnDelete" class="text-sm text-[12px] text-slate-600 hover:text-red-600 hover:underline hover:cursor-pointer underline-offset-4 transition-all duration-200">Delete</button>
                    <button @click="onDeleteAll" class="text-sm text-[12px] text-slate-600 hover:text-red-700 hover:underline hover:cursor-pointer underline-offset-4 transition-all duration-200">Delete All</button>
                </div>

                <div class="overflow-auto max-h-[80vh]">
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
                                v-for="(item, index) in form.details"
                                :key="item.row_key"
                                @click="selectedRowIndex = index"
                                @focusin="selectedRowIndex = index"
                                :class="['transition-colors duration-150 cursor-pointer', selectedRowIndex === index ? 'bg-blue-50/80 hover:bg-blue-50 border-l-4 border-l-blue-500' : 'hover:bg-slate-50/50']"
                            >
                                <td class="px-4 py-3 text-center text-xs font-semibold text-slate-600">
                                    <div class="py-2">{{ index + 1 }}.</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="relative">
                                        <!-- Penambahan Style Kondisional Dinamis: Jika baris dideteksi duplikat atau error, border/bg langsung disulap merah -->
                                        <div
                                            :id="`material-trigger-${index}`"
                                            @click="toggleMaterialDropdown(index)"
                                            class="relative z-20 w-full pl-3 pr-3 py-2.5 border text-xs focus:outline-none focus:border-blue-500 bg-white cursor-pointer flex justify-between items-center transition-all"
                                            :class="[
                                                item.errors?.material_id 
                                                    ? 'border-rose-500 focus:border-rose-500 bg-rose-50 text-rose-600' 
                                                    : 'border-slate-300 focus:border-blue-500 text-slate-800'
                                            ]"
                                        >
                                            <span :class="item.material_id ? 'text-slate-800 font-semibold' : 'text-slate-400'">
                                                {{ getSelectedMaterialName(item) }}
                                            </span>

                                            <div class="flex items-center space-x-1.5 relative z-30">
                                                <svg v-if="item.material_id" @click.stop="clearMaterial(index)" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400 hover:text-rose-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400 transition-transform" :class="{'rotate-180 text-blue-500': openedRowIndex === index}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </div>
                                        </div>

                                        <!-- Menampilkan teks keterangan spesifik error "This part no. is duplicate" di baris bersangkutan -->
                                        <p v-if="item.errors?.material_id" class="mt-1 text-[10px] font-bold text-rose-500">{{ item.errors.material_id }}</p>

                                        <Teleport to="body">
                                            <div v-if="openedRowIndex === index" class="fixed inset-0 z-[9998]" @click="openedRowIndex = null"></div>
                                            
                                            <Transition
                                                enter-active-class="transition duration-100 ease-out"
                                                enter-from-class="transform scale-95 opacity-0"
                                                enter-to-class="transform scale-100 opacity-100"
                                                leave-active-class="transition duration-75 ease-out"
                                                leave-from-class="transform scale-100 opacity-100"
                                                leave-to-class="transform scale-95 opacity-0"
                                            >
                                                <div
                                                    v-if="openedRowIndex === index"
                                                    :style="dropdownStyle"
                                                    class="bg-white border border-slate-200 shadow-2xl overflow-hidden rounded-sm"
                                                    @click.stop
                                                >
                                                    <div class="p-2 border-b border-slate-100 bg-slate-50 sticky top-0">
                                                        <div class="relative">
                                                            <svg class="absolute left-2 top-2 h-3.5 w-3.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                                            <input
                                                                ref="searchMaterialInput"
                                                                type="text"
                                                                v-model="materialSearch"
                                                                @input="handleMaterialSearch"
                                                                @keydown.down.prevent="moveMaterialDown"
                                                                @keydown.up.prevent="moveMaterialUp"
                                                                @keydown.enter.prevent="selectMaterialHighlighted(index)"
                                                                @keydown.esc="openedRowIndex = null"
                                                                class="w-full pl-7 pr-2 py-1.5 border border-slate-200 text-xs rounded-sm focus:outline-none focus:border-blue-500 bg-white"
                                                                placeholder="Type to search material..."
                                                            />
                                                        </div>
                                                    </div>

                                                    <div ref="optionsMaterialList" @scroll="handleMaterialScroll" class="max-h-48 overflow-y-auto">
                                                        <div
                                                            v-for="(mat, idx) in materialDropDown"
                                                            :key="mat.id"
                                                            @click="selectMaterial(mat, index)"
                                                            @mouseenter="highlightedMaterialIndex = idx"
                                                            class="px-3 py-2.5 text-xs cursor-pointer transition-colors border-b border-slate-50 last:border-0"
                                                            :class="[
                                                                item.material_id === mat.id ? 'border-l-2 border-l-blue-600 font-bold bg-blue-50/30' : '',
                                                                idx === highlightedMaterialIndex ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-700'
                                                            ]"
                                                        >
                                                            [{{ mat.code }}] - {{ mat.name }}
                                                        </div>
                                                        <div v-if="isMaterialLoading" class="px-3 py-2.5 text-center text-[10px] text-blue-600 font-bold bg-slate-50 animate-pulse border-t border-slate-100">Loading items...</div>
                                                        <div v-if="materialDropDown.length === 0 && !isMaterialLoading" class="px-3 py-6 text-xs text-center text-slate-400 italic bg-slate-50">Material not found ... "{{ materialSearch }}"</div>
                                                    </div>
                                                </div>
                                            </Transition>
                                        </Teleport>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="text" v-model="item.specification" placeholder="Part Specification" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 focus:bg-white focus:border-blue-500 text-xs transition-all outline-none" readonly>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="text" v-model="item.customer_part_name" placeholder="Customer Part Name" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 focus:bg-white focus:border-blue-500 text-xs transition-all outline-none" readonly>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>