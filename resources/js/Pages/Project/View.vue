<script setup>
import "vue3-toastify/dist/index.css";
import { ref, watch, computed, nextTick, onMounted, onUnmounted } from "vue";
import { Head, router, Link, usePage, useForm } from "@inertiajs/vue3";
import { toast } from "vue3-toastify";
import axios from "axios";
import dayjs from "dayjs";
import "dayjs/locale/id";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

defineOptions({layout:AuthenticatedLayout});
const props =  defineProps({
    allIds: Array,
    header: {
        type: Object,
        default: () => {}
    },

});
const page = usePage();
const errors = computed(() => page.props.errors || {});
const loadingLogs = ref(false)

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

defineEmits(['close']);
const isEditing = ref (false);
const showModal = ref(false);


// Navigation
const firstData = () => {
    const firstId = props.allIds[0];
    if(props.header?.id === firstId){
        toast.info('You are in the first data');
        return;
    }
    router.get(`/projects/view/${firstId}`);
};

const lastData = () => {
    const lastId = props.allIds[props.allIds.length - 1];

    if(props.header?.id === lastId){
        toast.info('You are in the last data');
        return;
    }
    router.get(`/projects/view/${lastId}`);
};

const prevData = () => {
    const currentIndex = props.allIds.indexOf(props.header.id);
    if (currentIndex > 0) {
        router.get(`/projects/view/${props.allIds[currentIndex - 1]}`);
    }
    else{
        toast.info('You are in the first data');
        return;
    }
};

const nextData = () => {
    const currentIndex = props.allIds.indexOf(props.header.id);
    if (currentIndex < props.allIds.length - 1) {
        router.get(`/projects/view/${props.allIds[currentIndex + 1]}`);
    }else{
        toast.info('You are in the last data');
        return;
    }
};

const newForm = () => {
    router.get('/projects/create');
}
// End of navigation

// Dropdown customer
const isCustomerOpen = ref(false);
const customerSearch = ref('');
const highlightedCustomerIndex = ref(-1);
const customerDropDown = ref([]);
const customerPage = ref(1);
const isCustomerLoading = ref(false);
const searchCustomerInput = ref(null);
const optionsCustomerList = ref(null);
// FIX: Mengambil objek data customer hasil eager load dari database backend
const activeSelectedCustomer = ref(props.header?.customer || null);
const hasMore = ref(true);

const isProcessing = computed(() => form.processing || false);


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
// End of dropdown customer

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
// End toolbar detail


// Dropdown material
const openedRowIndex = ref(null);
const materialSearch = ref('');
const highlightedMaterialIndex = ref(-1);
const materialDropDown = ref([]);
const materialPage = ref(1);
const isMaterialLoading = ref(false);
const searchMaterialInput = ref({});
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
        
        if (searchMaterialInput.value && searchMaterialInput.value[index]) {
            searchMaterialInput.value[index].focus();
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
        if (item.errors) item.errors.material_id = null;
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

const createBlankItem = () => ({
    row_key: Math.random().toString(36).substring(2, 9),
    material_id: '',
    specification: '',
    customer_part_name: '',
    selected_material: null,
    errors: { material_id: null }
});

// Form logic stubs for layout consistency
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

    if(!form.reason){
        form.setError('reason', 'Please fill the change reason')
        isValid = false;
    }

    if (!form.details || form.details.length === 0) {
        toast.error("Detail data cannot be empty. Please add at least one material.");
        return;
    }

    const seenMaterialIds = new Set();
    let hasDuplicate = false;
    let hasEmptyMaterial = false;

    form.details.forEach((item, index) => {
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

    form.put(`/projects/${form.id}`, {
        onSuccess: () => { isEditing.value = false; toast.success("Saved successfully"); }
    }); 
};

// Action delete
const deleteForm =useForm({
    id: null,
    reason: ""
});
const showConfirmModal = ref(false);

const deleteSelected = (id) => { 
    deleteForm.id = id;
    deleteForm.reason = ""; 
    deleteForm.clearErrors();
    showConfirmModal.value = true;    
};

const confirmAction = (id) => {
    deleteForm.clearErrors();
    let isValid = true;

    if(!deleteForm.reason){
        deleteForm.setError('reason', 'Please fill the deletion reason');
        isValid = false;
    }

    if(!isValid){
        return;
    }

    deleteForm.post('/projects/delete', {
        preserveScroll: true,
        onSuccess: () => {
            showConfirmModal.value = false;
            deleteForm.reset();
        }
    });
}
// End of action delete



// Main Form
const form = useForm({
    id: props.header?.id,
    code: props.header?.code,
    name: props.header?.name || "",
    customer_id: props.header?.customer_id,
    vehicle_model: props.header?.vehicle_model || '',
    main_part_number: props.header?.main_part_number || '',
    main_part_name: props.header?.main_part_name || '',
    apqp_phase: props.header?.apqp_phase || '',
    status: props.header?.status || '',
    
    // FIX: Parsing ISO string timestamp database ke YYYY-MM-DD menggunakan dayjs agar muncul di input HTML
    kick_off_date: props.header?.kick_off_date ? dayjs(props.header.kick_off_date).format('YYYY-MM-DD') : '',
    target_proto_date: props.header?.target_proto_date ? dayjs(props.header.target_proto_date).format('YYYY-MM-DD') : '',
    target_ppap_date: props.header?.target_ppap_date ? dayjs(props.header.target_ppap_date).format('YYYY-MM-DD') : '',
    target_sop_date: props.header?.target_sop_date ? dayjs(props.header.target_sop_date).format('YYYY-MM-DD') : '',
    
    confidentiality_level: props.header?.confidentiality_level || '',
    revision: props.header?.revision || '',
    is_active: props.header?.is_active !== undefined ? props.header.is_active : true ,
    remark: props.header?.remark || '',
    reason: '',
    // FIX: Mengganti key array ke 'details' agar sinkron dengan baris v-for table & fungsi toolbar bawaan lu
    details: props.header?.details?.length > 0
        ? props.header.details.map(item => ({
            row_key: Math.random().toString(36).substring(2, 9),
            id: item.id,
            project_id: item.project_id,
            material_id: item.material_id,
            specification: item.specification || (item.material?.specification || item.material?.spec || ''),
            customer_part_name: item.customer_part_name || (item.material?.customer_part_name || ''),
            selected_material: item.material || null,
            errors: { material_id: null }
        }))
        : [createBlankItem()]
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
    <Head :title="`Project Details | ${header.code}`" />
    <div class="flex min-h-screen bg-slate-50 font-sans antialiased text-slate-800">
        <div class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                <!-- page heading title -->
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">{{ isEditing ? 'Edit' : 'View' }} Project Details | {{ props.header?.code }} (Rev. {{ props.header?.revision }})</h1>
                    <p class="text-xs text-slate-500 mt-1">{{ isEditing ? 'Edit' : 'View' }} project details {{ props.header?.code }}.</p>
                </div>

                <div class="sticky top-0 z-40 bg-white/95 backdrop-blur-sm border-b border-slate-200 px-3 py-2 shadow-sm">
                    <div class="flex items-center justify-between w-full">
                        <!-- Toolbar -->
                        <div class="flex items-center gap-3"> 
                            <div class="flex items-center gap-1.5">
                                <Link href="/projects" class="group flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-bold text-slate-500 hover:text-blue-600 hover:bg-blue-50/50  transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform group-hover:-translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                    </svg>
                                    <span class="hidden sm:inline">Back</span>
                                </Link>

                                <div class="w-px h-6 bg-slate-300 mx-1" v-if="!isEditing"></div>

                                <div class="flex items-center bg-slate-100/80 p-0.5  border border-slate-200/50 shadow-inner" v-if="!isEditing">
                                    <button @click="firstData" :disabled="allIds.indexOf(header.id) === 0" type="button" class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-white hover:shadow-sm  transition-all" title="First">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                                        </svg>
                                    </button>
                                    <button type="button" @click="prevData" :disabled="allIds.indexOf(header.id) === 0" class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-white hover:shadow-sm  transition-all" title="Previous">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                        </svg>
                                    </button>
                                    <button type="button" @click="nextData" :disabled="allIds.indexOf(header.id) === allIds.length - 1" class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-white hover:shadow-sm  transition-all" title="Next">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>
                                    <button type="button" @click="lastData" :disabled="allIds.indexOf(header.id) === allIds.length - 1" class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-white hover:shadow-sm  transition-all" title="Last">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="w-px h-6 bg-slate-300 mx-1"></div>

                            <div class="flex items-center gap-1.5">
                                
                                <button v-if="isEditing" type="button" @click="validateAndSave" :disabled="form.processing"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs  shadow-sm transition active:scale-95 disabled:opacity-70 disabled:cursor-not-allowed">
                                    <svg v-if="form.processing" class="animate-spin h-3.5 w-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                                    </svg>
                                    <span>{{ form.processing ? 'Saving...' : 'Save' }}</span>
                                </button>
                                <button 
                                    v-if="!isEditing"
                                    type="button" 
                                    @click="newForm"
                                    class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-bold text-green-600 bg-blue-50 border border-blue-100  transition-all hover:bg-blue-100 active:scale-95 shadow-sm"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    
                                    <span>New</span>
                                </button>
                                <button type="button" 
                                    @click="isEditing ? (isEditing = false, form.reset()) : isEditing = true" 
                                    :class="[
                                        'flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold  transition-all shadow-sm border',
                                        isEditing 
                                            ? 'bg-slate-100 text-slate-600 border-slate-200 hover:bg-slate-200' 
                                            : 'bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white border-blue-100 hover:border-blue-600'
                                    ]">
                                    <svg v-if="!isEditing" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    <span>{{ isEditing ? 'Cancel' : 'Edit' }}</span>
                                </button>
                                
                                <div class="w-px h-6 bg-slate-300 mx-1"></div>

                                <div class="relative group">
                                    <button type="button" class="flex items-center gap-1.5 px-3 py-1.5 bg-white text-slate-600 hover:text-blue-600 border border-slate-200 hover:border-blue-300 hover:bg-blue-50/50 text-xs font-bold  transition-all shadow-sm">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                                        </svg>
                                        Tools
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400 group-hover:text-blue-600 group-hover:rotate-180 transition-all duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                    <div class="absolute right-0 top-full mt-1.5 w-40 bg-white border border-slate-100 shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50 overflow-hidden origin-top-right transform scale-95 group-hover:scale-100">
                                        <div class="py-1 flex flex-col">
                                            <button type="button" @click="openLogs(props.header.id)" :disabled="loadingLogs" class="w-full text-left px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-blue-50 hover:text-blue-600 flex items-center gap-2 transition-colors">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                                                    <path d="M21 6.375c0 2.692-4.03 4.875-9 4.875S3 9.067 3 6.375 7.03 1.5 12 1.5s9 2.183 9 4.875Z" />
                                                    <path d="M12 12.75c2.685 0 5.19-.586 7.078-1.609a8.283 8.283 0 0 0 1.897-1.384c.016.121.025.244.025.368C21 12.817 16.97 15 12 15s-9-2.183-9-4.875c0-.124.009-.247.025-.368a8.285 8.285 0 0 0 1.897 1.384C6.809 12.164 9.315 12.75 12 12.75Z" />
                                                    <path d="M12 16.5c2.685 0 5.19-.586 7.078-1.609a8.282 8.282 0 0 0 1.897-1.384c.016.121.025.244.025.368 0 2.692-4.03 4.875-9 4.875s-9-2.183-9-4.875c0-.124.009-.247.025-.368a8.284 8.284 0 0 0 1.897 1.384C6.809 15.914 9.315 16.5 12 16.5Z" />
                                                    <path d="M12 20.25c2.685 0 5.19-.586 7.078-1.609a8.282 8.282 0 0 0 1.897-1.384c.016.121.025.244.025.368 0 2.692-4.03 4.875-9 4.875s-9-2.183-9-4.875c0-.124.009-.247.025-.368a8.284 8.284 0 0 0 1.897 1.384C6.809 19.664 9.315 20.25 12 20.25Z" />
                                                </svg>
                                                <svg v-if="loadingLogs" class="animate-spin h-3 w-3 text-white" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                
                                                {{ loadingLogs ? 'Loading...' : 'View Change Logs' }}
                                            </button>
                                            <button type="button" class="w-full text-left px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-blue-50 hover:text-blue-600 flex items-center gap-2 transition-colors">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                                </svg>
                                                Print Data
                                            </button>
                                            </div>
                                    </div>
                                </div>

                                <div class="w-px h-4 bg-slate-200 mx-1"></div>

                                <button type="button" @click="deleteSelected(props.header?.id)" class="p-1.5 text-slate-400 hover:text-white hover:bg-rose-500  transition-colors" title="Delete Record">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form header -->
            <div class="bg-white border border-slate-200/80 shadow-sm p-4 mb-6 flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
                <div class="flex-1 space-y-6">
                    <div class="grid grid-cols-4 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Project Code <span class="text-bold text-rose-500">*</span></label>
                            <input
                                type="text"
                                v-model="form.code"
                                maxlength="50"
                                :readonly="!isEditing"
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
                                :readonly="!isEditing"
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
                                    @click="isEditing && toggleCustomerDropdown()"
                                    class="relative z-20 w-full pl-3 pr-3 py-2.5 border text-xs focus:outline-none focus:border-blue-500 bg-white cursor-pointer flex justify-between items-center transition-all"
                                    :class="[form.errors.customer_id ? 'border-rose-500 text-rose-600' : 'border-slate-300 text-slate-800']"
                                >
                                    <span :class="form.customer_id ? 'text-slate-800 font-semibold' : 'text-slate-400'">{{ selectedCustomerName }}</span>
                                    <div class="flex items-center space-x-1.5 relative z-30" v-if="isEditing">
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
                            <input type="text" v-model="form.vehicle_model" :readonly="!isEditing" maxlength="100" placeholder="e.g. SUV Type-X" class="w-full pl-3 pr-3 py-2 border text-xs focus:outline-none border-slate-300 focus:border-blue-500 text-slate-800" />
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Main Part Number</label>
                            <input type="text" v-model="form.main_part_number" :readonly="!isEditing" maxlength="100" placeholder="PN-88291-XX" class="w-full pl-3 pr-3 py-2 border text-xs focus:outline-none border-slate-300 focus:border-blue-500 text-slate-800" />
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Main Part Name</label>
                            <input type="text" v-model="form.main_part_name" :readonly="!isEditing" maxlength="100" placeholder="Component base identifier" class="w-full pl-3 pr-3 py-2 border text-xs focus:outline-none border-slate-300 focus:border-blue-500 text-slate-800" />
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">APQP Phase</label>
                            <input type="text" v-model="form.apqp_phase" :readonly="!isEditing" maxlength="50" placeholder="e.g. Phase 1: Planning" class="w-full pl-3 pr-3 py-2 border text-xs focus:outline-none border-slate-300 focus:border-blue-500 text-slate-800" />
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
                                    @click="isEditing && toggleProjectStatusDropdown()"
                                    class="relative z-20 w-full pl-3 pr-3 py-2.5 border text-xs focus:outline-none focus:border-blue-500 bg-white cursor-pointer flex justify-between items-center transition-all border-slate-300 text-slate-800"
                                >
                                    <span :class="form.status ? 'text-slate-800 font-semibold' : 'text-slate-400'">
                                        {{ selectedProjectStatusName }}
                                    </span>
                                    <div class="flex items-center space-x-1.5 relative z-30" v-if="isEditing">
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
                            <input type="date" v-model="form.kick_off_date" :readonly="!isEditing" class="w-full pl-3 pr-3 py-2 border text-xs focus:outline-none border-slate-300 focus:border-blue-500 text-slate-800" />
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Target Proto Date</label>
                            <input type="date" v-model="form.target_proto_date" :readonly="!isEditing" class="w-full pl-3 pr-3 py-2 border text-xs focus:outline-none border-slate-300 focus:border-blue-500 text-slate-800" />
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Target APQP Date</label>
                            <input type="date" v-model="form.target_ppap_date" :readonly="!isEditing" class="w-full pl-3 pr-3 py-2 border text-xs focus:outline-none border-slate-300 focus:border-blue-500 text-slate-800" />
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Target SOP Date</label>
                            <input type="date" v-model="form.target_sop_date" :readonly="!isEditing" class="w-full pl-3 pr-3 py-2 border text-xs focus:outline-none border-slate-300 focus:border-blue-500 text-slate-800" />
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
                                    @click="isEditing && toggleConfidentialityDropdown()"
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
                                    <div class="flex items-center space-x-1.5 relative z-30" v-if="isEditing">
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
                                <button type="button" :disabled="!isEditing" @click="form.is_active = !form.is_active" :class="form.is_active ? 'bg-emerald-600' : 'bg-slate-300'" class="relative inline-flex h-5 w-10 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out active:scale-95 disabled:opacity-60"><span :class="form.is_active ? 'translate-x-5' : 'translate-x-0'" class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition duration-200"></span></button>
                            </div>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Remark (optional)</label>
                            <textarea 
                                v-model="form.remark" 
                                :readonly="!isEditing" 
                                placeholder="Write additional information here ..." 
                                rows="3" 
                                class="w-full pl-3 pr-3 py-2 border text-xs focus:outline-none border-slate-300 focus:border-blue-500 text-slate-800"></textarea>
                        </div>
                        <div class="col-span-4"">
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Change Reason <span class="text-bold text-rose-500">*</span></label>
                            <textarea 
                                v-model="form.reason"
                                :readonly="!isEditing" 
                                placeholder="Write additional information here ..."
                                rows="3" 
                                class="relative z-20 w-full pl-3 pr-3 py-2.5 border text-xs focus:outline-none focus:border-blue-500 bg-white cursor-pointer flex justify-between items-center transition-all"
                                        :class="[form.errors.reason ? 'border-rose-500 text-rose-600' : 'border-slate-300 text-slate-800']"
                            ></textarea>
                            <p v-if="form.errors.reason" class="mt-1 text-[10px] font-bold text-rose-500">{{ form.errors.reason }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form detail -->
            <div class="w-full bg-white border border-slate-200/80 shadow-sm overflow-hidden mb-6 flex flex-col">
                <div class="overflow-auto border-t border-slate-100 p-4 flex items-center gap-5" v-if="isEditing">
                    <button @click="onNew" class="text-sm text-[12px] text-slate-600 hover:text-blue-600 hover:underline hover:cursor-pointer underline-offset-4 transition-all duration-200">New</button>
                    <button @click="onInsert" class="text-sm text-[12px] text-slate-600 hover:text-blue-600 hover:underline hover:cursor-pointer underline-offset-4 transition-all duration-200">Insert</button>
                    <button @click="OnDelete" class="text-sm text-[12px] text-slate-600 hover:text-red-600 hover:underline hover:cursor-pointer underline-offset-4 transition-all duration-200">Delete</button>
                    <button @click="onDeleteAll" class="text-sm text-[12px] text-slate-600 hover:text-red-700 hover:underline hover:cursor-pointer underline-offset-4 transition-all duration-200">Delete All</button>
                </div>

                <div class="overflow-auto max-h-[80vh]">
                    <table class="w-full min-w-max divide-y divide-slate-200 text-left whitespace-nowrap">
                        <thead class="bg-blue-100 text-[10px] font-extrabold text-slate-500 uppercase tracking-wider sticky top-0 z-30 shadow-sm">
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
                                        <div
                                            :id="`material-trigger-${index}`"
                                            @click="isEditing && toggleMaterialDropdown(index)"
                                            class="relative z-10 w-full pl-3 pr-3 py-2.5 border text-xs focus:outline-none focus:border-blue-500 bg-white cursor-pointer flex justify-between items-center transition-all"
                                            :class="[
                                                item.errors?.material_id 
                                                    ? 'border-rose-500 focus:border-rose-500 bg-rose-50 text-rose-600' 
                                                    : 'border-slate-300 focus:border-blue-500 text-slate-800'
                                            ]"
                                        >
                                            <span :class="item.material_id ? 'text-slate-800 font-semibold' : 'text-slate-400'">
                                                {{ getSelectedMaterialName(item) }}
                                            </span>

                                            <div class="flex items-center space-x-1.5 relative z-30" v-if="isEditing">
                                                <svg v-if="item.material_id" @click.stop="clearMaterial(index)" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400 hover:text-rose-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400 transition-transform" :class="{'rotate-180 text-blue-500': openedRowIndex === index}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </div>
                                        </div>

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
                                                                :ref="el => { if(el) searchMaterialInput[index] = el }"
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

    <!-- konfirmasi hapus -->
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95"
    >
        <div
            v-if="showConfirmModal"
            class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 backdrop-blur-sm p-4"
        >
            <div class="bg-white w-full max-w-lg border border-slate-200 shadow-2xl p-6">
                <div class="flex flex-col text-left">
                    <h3 class="text-lg font-black text-slate-900 mb-1">
                        Confirm Deletion
                    </h3>
                    <p class="text-xs text-slate-500 mb-4">
                        Are you sure you want to delete this project 
                        <span class="font-bold text-slate-900">
                            {{ props.header?.code }}
                        </span>? <br>This action cannot be undone.
                    </p>

                    <div class="mb-6">
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">
                            Reason for deletion <span class="text-bold text-rose-500">*</span>
                        </label>
                       <textarea
                            v-model="deleteForm.reason" 
                            rows="3"
                            class="w-full p-3 text-xs bg-slate-50 border focus:outline-none focus:ring-2 transition-all resize-none"
                            :class="deleteForm.errors.reason 
                                ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-500/20 text-rose-900' 
                                : 'border-slate-200 focus:border-blue-500 focus:ring-blue-500/20 text-slate-700'"
                            placeholder="Describe why this data is being deleted..."
                            @input="deleteForm.clearErrors('reason')"
                        ></textarea>

                        <p v-if="deleteForm.errors.reason" class="mt-1 text-[10px] font-bold text-rose-500">
                            {{ deleteForm.errors.reason }}
                        </p>
                    </div>

                    <div class="flex gap-3 w-full">
                        <button
                            @click="cancelConfirmAction"
                            class="flex-1 px-4 py-2 bg-slate-300 hover:bg-slate-400 text-slate-700 font-bold text-xs transition-all"
                        >
                            Cancel
                        </button>
                        <button
                            @click="confirmAction"
                            class="flex-1 px-4 py-2 bg-rose-600 hover:bg-rose-800 text-white font-bold text-xs transition-all"
                        >
                            Yes, Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>