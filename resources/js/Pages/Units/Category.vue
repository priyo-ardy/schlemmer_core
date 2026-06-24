<script setup>

import "vue3-toastify/dist/index.css";
import { ref, computed, watch, isRef } from "vue";
import { Head, useForm, router, usePage } from "@inertiajs/vue3";
import { toast } from "vue3-toastify";
import { ceil } from "lodash";

import dayjs from "dayjs";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import "dayjs/locale/id";

defineOptions({ layout: AuthenticatedLayout });

// Datetime format
dayjs.locale("id");
const formatLogDate = (date) => {
    if (!date) return "-"; // Guard clause jika data tanggal kosong/null
    return dayjs(date).format("DD MMM YYYY, HH:mm:ss [WIB]");
};

const formatTableDate = (date) => {
    if (!date) return "-";
    return dayjs(date).format("DD-MMM-YYYY HH:mm:ss");
};

// Initialize props
const props = defineProps({
    categories: Object,
});

// Initialize page
const page = usePage();

// Initialize global error
const errors = computed(() => page.props.value.errors || {});

// Initialize delete confirmation form
const deleteForm = useForm({
    ids: [],
    remark: ''
});

// Initialize action variable
const isSlideOverOpen = ref(false);
const slideOverTitle = ref("New UoM Categories");
const isEditMode = ref(false);
const searchQuery = ref("");
const selectedFilter = ref("all");
const selectedCategory = ref([]);
const isRefreshing = ref(false);
const isDeleting = ref(false);
const showConfirmModal = ref(false);
const deleteReason = ref("");
const itemsPerPage = ref(10);
const currentPage = ref(1);

// Variable for getting change logs history
const isHistoryModalOpen = ref(false);
const historyLogs = ref([]);
const isLoadingHistory = ref(false);
const selectedCategoryName = ref("");

// Searching feature
const filteredCategories = computed(() => {
    return (props.categories || []).filter((category) => {
        const code = category.code ? category.code.toLowerCase() : '';
        const name = category.name ? category.name.toLowerCase() : '';
        const description = category.description ? category.description.toLowerCase() : '';
        const search = searchQuery.value.toLowerCase();

        const matchesSearch = code.includes(search) ||
            name.includes(search) ||
            description.includes(search);

        if (selectedFilter.value === 'enable') return matchesSearch && category.is_active;
        if (selectedFilter.value === 'disable') return matchesSearch && !category.is_active;

        return matchesSearch;
    });
});

// Refresh table
const refreshTable = () => {
    isRefreshing.value = true;
    router.reload({
        only: ['categories'],
        onSuccess: () => {
            isRefreshing.value = false;
            toast.success('Refresh success');
        },
        onError: () => {
            isRefreshing.value = false;
            toast.error('Failed to refresh data');
        }
    });
}

watch(
    errors,
    (newErrors) => {
        if (newErrors && newErrors.error) {
            toast.error(newErrors.error);
        }
    },
    {
        deep: true
    }
);

// Pagination
watch([searchQuery, selectedFilter, itemsPerPage], () => {
    currentPage.value = 1;
});

const paginateCategories = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start - itemsPerPage.value;
    return filteredCategories.value.slice(start, end);
});

const totalFiltered = computed(() => filteredCategories.value.length);
const totalPages = computed(() => Math.ceil(totalFiltered.value / itemsPerPage.value));

const paginationStart = computed(() => {
    if (totalFiltered.value === 0) return 0;
    return(currentPage.value - 1) * itemsPerPage.value + 1;
});

const paginationEnd = computed(() => {
    return Math.min(currentPage.value * itemsPerPage.value, totalFiltered.value);
});

const goToPrev = () => { if(currentPage.value > 1) currentPage.value--; };
const goToNext = () => { if(currentPage.value < totalPages.value) currentPage.value++; };

// Action for checkbox
const isAllSelected = computed(
    () =>
    paginateCategories.value.length > 0 &&
    paginateCategories.value.every((u) => selectedCategory.value.includes(u.id))
);

const toggleSelectAll = () => {
    if(isAllSelected.value){
        selectedCategory.value = selectedCategory.value.filter(
            (id) => !paginateCategories.value.some((u) => u.id === id)
        );
    }else{
        paginateCategories.value.forEach((category) => {
            if(!selectedCategory.value.includes(category.id)){
                selectedCategory.value.push(category.id);
            }
        });
    }
}

const toggleSelectCategory = (id) => {
    const index = selectedCategory.value.indexOf(id);
    index > -1
        ? selectedCategory.value.splice(index, 1)
        : selectedCategory.value.push(id);
}

// Initilize form
const form = useForm({
    id: null,
    code: "",
    name: "",
    description: "",
    sort_order:"",
    is_active: true
});

// Initialize new slideover
const openCreateDrawer = () => {
    isEditMode.value = false;
    slideOverTitle.value = "New Unit Categories";
    form.reset();
    form.clearErrors();
    form.is_active = true;
    isSlideOverOpen = true;
}

// Initialize edit slideover
const openEditDrawer = (category) => {
    isEditMode.value = true;
    slideOverTitle.value = `Update Unit Category Data: ${category.name}`;
    form.clearErrors;

    form.id = category.id;
    form.code = category.code;
    form.name = category.name;
    form.description = category.description;
    form.is_active = !!category.is_active;

    isSlideOverOpen.value = true;
};

const handleSubmit = () => {
    form.clearErrors();
    let isValid = true;

    if(!form.name){
        form.setError('name', 'This field is required');
        isValid = false;
    }else if(form.name.length > 150){
        form.setError('name', 'Unit category name cannot exceed 150 caracter');
        isValid = false
    }

    if(isEditMode.value){
        if(!form.description || !form.description.trim()){
            form.setError('description', 'Please fill the content change reason');
            isValid = false;
        }
    }

    if(!isValid) return;

    form.transform((data) => {
        const payload = { ...data };

        if(isEditMode.value){
            payload._method = "put";
        }else{
            delete payload._method;
        }

        return payload;
    }).post(isEditMode.value ? `/unit_category/${form.id}` : '/unit_category', {
        preserveScroll: true,
        onSuccess: () => {
            isSlideOverOpen.value = false;
            form.reset();
        },
        onError: (errors) => {
            console.error(errors);
        }
    });
}

// Initialize mass delete
const deleteSelected = () => {
    if(selectedCategory.value.length > 0) showConfirmModal.value = true;
}


const confirmAction = () => {
    if(!deleteReason.value.trim()){
        deleteForm.setError('remark', 'Please fill the deletion reason');
        return;
    }

    deleteForm.ids = selectedCategory.value;
    deleteForm.remark = deleteReason.value;
    deleteForm.post("/unit_category/mass-delete", {
        preserveScroll: true,
        onSuccess: () => {
            selectedCategory.value = [];
            showConfirmModal.value = false;
            deleteReason.value = "";
            deleteForm.clearErrors();
        }
    });
}

// History logs
const openHistoryModal = async(id, name) => {
    isHistoryModalOpen.value = true;
    isLoadingHistory.value = true;
    selectedUnitsName.value = name;
    historyLogs.value = [];

    try{
        const response = await axios.get(`/units/${id}/logs`);
        historyLogs.value = response.data;
    } catch(error){
        toast.error("Failed to load revision history data.");
    } finally{
        isLoadingHistory.value = false;
    }
}

const closeHistoryModal = () => {
    isHistoryModalOpen.value = false;
    isHistoryModalOpen.value = false;
    selectedUnitsName.value = "";
    historyLogs.value = [];
};

const getChangedFields = (log) => {
    const ignoredKeys = ['id', 'uuid', 'created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by', 'revision', 'deleted_by'];
    const changes = [];

    if (log.event_name === 'update' && log.before && log.after) {
        Object.keys(log.after).forEach(key => {
            if (!ignoredKeys.includes(key) && log.before[key] !== log.after[key]) {
                changes.push({
                    field: key,
                    before: log.before[key],
                    after: log.after[key]
                });
            }
        });
    } else if (log.event_name === 'delete' && log.before) {
        Object.keys(log.before).forEach(key => {
            if (!ignoredKeys.includes(key) && log.before[key] !== null) {
                changes.push({
                    field: key,
                    before: log.before[key],
                    after: null
                });
            }
        });
    } else if (log.event_name === 'create' && log.after) {
        Object.keys(log.after).forEach(key => {
            if (!ignoredKeys.includes(key) && log.after[key] !== null) {
                changes.push({
                    field: key,
                    before: null,
                    after: log.after[key]
                });
            }
        });
    }

    return changes;
};
</script>
