<script setup>
import 'vue3-toastify/dist/index.css';
import { ref, watch, computed } from 'vue';
import { Head, router, Link, usePage, useForm } from '@inertiajs/vue3';
import { toast } from 'vue3-toastify';
import { debounce } from 'lodash';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({layout: AuthenticatedLayout});

const page = usePage();
const searchQuery = ref('');
const selectedIds = ref([]);
const isRefreshing = ref(false);
const showConfirmModal = ref(false);
const selectedFilter = ref("all");
const isSearching = ref(false);
const deleteReason = ref('');

const deleteForm = useForm({
    ids: [],
    remark: '' // Alasan penghapusan
});

const props = defineProps({
    customers: { type: Object, default: () => ({ data: [], links: [], per_page: 10 }) }
});

// Setup untuk "Per Page"
const perPage = ref(props.customers?.per_page || 10);

// State untuk Drawer
const isDrawerOpen = ref(false);
const selectedCustomer = ref(null);
const isEditMode = computed(() => selectedCustomer.value !== null);

const form = useForm({
    code: '',
    name: '',
    alias: '',
    tax_number: '',
    tier_level: 'tier-1',
    csr_reference_doc: '',
    risk_profile: 'medium',
    email: '',
    phone: '',
    billing_address: '',
    shipping_address: '',
    is_active: true,
    remark: '',
});

const refreshTable = () => {
    isRefreshing.value = true;
    router.reload({
        only: ['customers'],
        onSuccess: () => {
            isRefreshing.value = false;
        },
        onError: () => {
            isRefreshing.value = false;
            toast.error('Failed to refresh data');
        }
    });
}

const isProcessing = computed(() => form.processing || false);

const toggleSelectAll = (event) => {
    selectedIds.value = event.target.checked
    ? props.customers.data.map(c => c.id)
    : [];
};

const openCreateDrawer = () => {
    selectedCustomer.value = null;
    form.reset();
    form.clearErrors();
    isDrawerOpen.value = true; 
}

const openEditDrawer = (customer) => {
    selectedCustomer.value = customer;
    form.clearErrors();
    
    form.name = customer.name ?? '';
    form.alias = customer.alias ?? '';
    form.tax_number = customer.tax_number ?? '';
    form.tier_level = customer.tier_level ?? 'tier-1';
    form.csr_reference_doc = customer.csr_reference_doc ?? '';
    form.risk_profile = customer.risk_profile ?? 'medium';
    form.email = customer.email ?? '';
    form.phone = customer.phone ?? '';
    form.billing_address = customer.billing_address ?? '';
    form.shipping_address = customer.shipping_address ?? '';
    form.is_active = customer.is_active ?? true;
    form.remark = ''; 
    
    isDrawerOpen.value = true;
};

const submitForm = () => {
    if (isEditMode.value) {
        if(!form.remark){
            form.setError('remark', 'Please fill the update change reason');
            return;
        }
        form.put(`/customer/${selectedCustomer.value.id}`, {
            onSuccess: () => {
                isDrawerOpen.value = false;
                // toast.success('Customer updated successfully');
                form.reset();
            },
            onError: () => toast.error('Please check the form errors')
        });
    } else {
        form.post('/customer', {
            onSuccess: () => {
                isDrawerOpen.value = false;
                // toast.success('Customer registered successfully');
                form.reset();
            },
            onError: () => toast.error('Please check the form errors')
        });
    }
};

const deleteSelected = () => {
    if(selectedIds.value.length > 0) showConfirmModal.value = true;
};

const confirmAction = () => {
    if (!deleteReason.value) {
        toast.error('Reason for deletion is required!');
        return;
    }

    deleteForm.ids = selectedIds.value;
    deleteForm.remark = deleteReason.value;

    deleteForm.post('/customer/mass-delete', {
        onSuccess: () => {
            selectedIds.value = [];
            showConfirmModal.value = false;
            deleteReason.value = ''; // Reset
        },
        onError: (errors) => {
            toast.error('Failed to delete data');
        }
    });
};

// Fungsi update jumlah data per halaman
const updatePerPage = () => {
    router.get(window.location.pathname, { 
        per_page: perPage.value, 
        search: searchQuery.value,
        filter: selectedFilter.value 
    }, { 
        preserveState: true,
        onStart: () => isSearching.value = true,
        onFinish: () => isSearching.value = false,
    });
}

watch(searchQuery, debounce((value) => {
    router.get(window.location.pathname, { 
        search: value, 
        per_page: perPage.value,
        filter: selectedFilter.value
    }, { 
        preserveState: true, 
        preserveScroll: true, 
        replace: true,
        onStart: () => isSearching.value = true,
        onFinish: () => isSearching.value = false,
    });
}, 300));

</script>

<template>
    <Head title="List of Customer"/>
    <div class="flex min-h-screen bg-slate-50 font-sans antialiased text-slate-800">
        <div class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">
            
            <!-- HEADER SECTION -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8 px-6 pt-6">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">Customer Management</h1>
                    <p class="text-xs text-slate-500 mt-1">Manage corporate clients and IATF requirements.</p>
                </div>
                <div class="sticky top-0 z-30 bg-white/95 backdrop-blur-sm border border-slate-200 px-3 py-2 shadow-sm">
                    <div class="flex items-center justify-between w-full">
                        <div class="flex items-center gap-1.5">
                            
                            <!-- Button New -->
                            <button @click="openCreateDrawer" type="button" class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-bold text-blue-600 bg-blue-50 border border-blue-100 transition-all hover:bg-blue-100 active:scale-95 shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                                <span>New</span>
                            </button>

                            <!-- Button Refresh -->
                            <button type="button" @click="refreshTable" :disabled="isRefreshing" class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-bold text-slate-600 bg-orange-50 border border-slate-200 transition-all hover:bg-orange-100 active:scale-95 shadow-sm disabled:opacity-60">
                                <svg xmlns="http://www.w3.org/2000/svg" :class="isRefreshing ? 'animate-spin text-blue-600' : 'text-slate-500'" class="h-3.5 w-3.5 transition-colors duration-150" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                </svg>
                                <span>{{ isRefreshing ? 'Refreshing...' : 'Refresh' }}</span>
                            </button>

                            <!-- Button Delete -->
                            <button type="button" @click="deleteSelected" :disabled="selectedIds.length === 0" class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-bold text-rose-600 bg-rose-50 border border-rose-100 transition-all hover:bg-rose-100 active:scale-95 shadow-sm disabled:opacity-40 disabled:active:scale-100">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                <span>Delete ({{ selectedIds.length }})</span>
                            </button>

                        </div>
                    </div>
                </div>
            </div>

            <div class="px-6 flex-1 flex flex-col">
                <!-- SEARCH & FILTER SECTION -->
                <div class="bg-white border border-slate-200/80 shadow-sm p-4 mb-6 flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
                    <div class="relative flex-1 max-w-md">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                            
                            <svg v-if="isSearching" class="animate-spin h-4 w-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>

                            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-all" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>

                        </span>
                        <input type="text" placeholder="Search dynamically..." v-model="searchQuery" class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 focus:border-blue-500 text-xs font-semibold focus:outline-none transition" />
                    </div>
                    
                    <div class="flex items-center gap-4">
                        <!-- Per Page Filter -->
                        <div class="flex items-center gap-2">
                            <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Per Page:</label>
                            <select v-model="perPage" @change="updatePerPage" class="px-3 py-2.5 bg-white border border-slate-200 text-slate-700 text-xs font-semibold focus:outline-none focus:border-blue-500 cursor-pointer transition">
                                <option :value="10">10</option>
                                <option :value="25">25</option>
                                <option :value="50">50</option>
                                <option :value="100">100</option>
                            </select>
                        </div>
                        
                        <!-- Status Filter -->
                        <div class="flex items-center gap-2">
                            <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Status:</label>
                            <select v-model="selectedFilter" class="px-3 py-2.5 bg-white border border-slate-200 text-slate-700 text-xs font-semibold focus:outline-none focus:border-blue-500 cursor-pointer transition">
                                <option value="all">All</option>
                                <option value="enable">Enabled</option>
                                <option value="disable">Disabled</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- TABLE SECTION -->
                <div class="bg-white border border-slate-200/80 shadow-sm overflow-hidden mb-8">
                    <div class="overflow-auto max-h-[calc(100vh-320px)]">
                        <table class="w-full text-left border-collapse bg-white whitespace-nowrap">
                            <thead class="bg-slate-100 text-slate-700 uppercase tracking-wider text-[11px] font-bold border-b border-slate-200 sticky top-0 z-10">
                                <tr>
                                    <th class="px-4 py-3 w-12 text-center">
                                        <input type="checkbox" @change="toggleSelectAll" class="border-slate-300 text-blue-600 h-4 w-4 cursor-pointer" />
                                    </th>
                                    <th class="px-4 py-3">Rev</th>
                                    <th class="px-4 py-3">Code</th>
                                    <th class="px-4 py-3">Name</th>
                                    <th class="px-4 py-3">Alias</th>
                                    <th class="px-4 py-3">Tier Level</th>
                                    <th class="px-4 py-3">Risk Profile</th>
                                    <th class="px-4 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-xs text-slate-600">
                                <tr v-for="customer in customers.data" :key="customer.id" 
                                    @click="openEditDrawer(customer)" 
                                    class="hover:bg-blue-50 transition-colors cursor-pointer">
                                    
                                    <td class="px-4 py-3 text-center" @click.stop>
                                        <input type="checkbox" v-model="selectedIds" :value="customer.id" class="border-slate-300 text-blue-600 h-4 w-4 cursor-pointer" />
                                    </td>
                                    
                                    <td class="px-4 py-3">{{ customer.revision }}</td>
                                    <td class="px-4 py-3 font-semibold text-slate-800">{{ customer.code }}</td>
                                    <td class="px-4 py-3 font-semibold">{{ customer.name }}</td>
                                    <td class="px-4 py-3">{{ customer.alias }}</td>
                                    <td class="px-4 py-3 uppercase text-[10px]">{{ customer.tier_level }}</td>
                                    <td class="px-4 py-3 uppercase text-[10px]">{{ customer.risk_profile }}</td>
                                    <td class="px-4 py-3">
                                        <span :class="customer.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'" class="px-2 py-1 font-bold text-[10px]">
                                            {{ customer.is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="customers.data.length === 0">
                                    <td colspan="8" class="px-4 py-8 text-center text-slate-400 font-medium">No customer data found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-if="customers.total > 0" class="px-6 py-4 bg-white border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">
                            Showing {{ customers.from ?? 0 }} to {{ customers.to ?? 0 }} of {{ customers.total ?? 0 }}
                        </div>
                        
                        <div class="flex items-center gap-1">
                            <template v-for="(link, index) in customers.links" :key="index">
                                <Link 
                                    v-if="link.label.includes('Previous') || link.label.includes('Next')"
                                    :href="link.url ?? '#'"
                                    v-html="link.label"
                                    preserve-scroll
                                    class="px-2 py-1 text-xs font-bold text-slate-500 hover:text-slate-900 transition"
                                    :class="{'opacity-30 cursor-not-allowed': !link.url}"
                                />
                                
                                <Link 
                                    v-else
                                    :href="link.url ?? '#'"
                                    v-html="link.label"
                                    preserve-scroll
                                    class="px-3 py-1.5 text-xs font-bold border transition-all"
                                    :class="link.active 
                                        ? 'bg-blue-600 text-white border-blue-600' 
                                        : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-100'"
                                />
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- SLIDEOVER / DRAWER FORM                    -->
    <!-- ========================================== -->
    <div v-show="isDrawerOpen" class="fixed inset-0 z-50 overflow-hidden" role="dialog" aria-modal="true">
        <div class="absolute inset-0 overflow-hidden">
            <!-- BACKDROP -->
            <Transition enter-active-class="ease-in-out duration-300" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="ease-in-out duration-300" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-show="isDrawerOpen" @click="isDrawerOpen = false" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>
            </Transition>

            <!-- DRAWER PANEL -->
            <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                <Transition enter-active-class="transform transition ease-in-out duration-300" enter-from-class="translate-x-full" enter-to-class="translate-x-0" leave-active-class="transform transition ease-in-out duration-300" leave-from-class="translate-x-0" leave-to-class="translate-x-full">
                    
                    <div v-show="isDrawerOpen" class="pointer-events-auto w-screen max-w-2xl bg-white shadow-2xl flex flex-col h-full border-l border-slate-200">
                        
                        <div class="bg-slate-900 px-6 py-5 flex items-center justify-between shrink-0">
                            <div>
                                <h2 class="text-base font-black text-white tracking-tight">
                                    {{ isEditMode ? 'Edit Customer Registration' : 'Register New Customer' }}
                                </h2>
                                <p class="text-[10px] text-slate-400 mt-0.5">IATF 16949 Auditable Data Form</p>
                            </div>
                            <button type="button" @click="isDrawerOpen = false" class="text-slate-400 hover:text-white hover:bg-slate-800 p-1.5 transition">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <form @submit.prevent="submitForm" class="flex-1 p-6 overflow-y-auto space-y-6 bg-slate-200/70">
                            
                            <div class="space-y-3">
                                <h4 class="text-[10px] font-bold text-blue-600 uppercase tracking-wide border-b border-slate-300 pb-1">1. General Information</h4>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Customer Name <span class="text-rose-500">*</span></label>
                                        <input type="text" v-model="form.name" maxlength="150" required placeholder="PT Customer Name .."
                                            :class="[
                                                'w-full pl-3 pr-3 py-2 bg-white border text-xs font-medium focus:outline-none transition-all',
                                                form.errors.name ? 'border-rose-500 focus:border-rose-500 focus:ring-1 focus:ring-rose-500 text-rose-600' : 'border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800'
                                            ]"
                                        >
                                        <p v-if="form.errors.name" class="mt-1 text-[10px] font-bold text-rose-500">{{ form.errors.name }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Alias / Short Name</label>
                                        <input type="text" v-model="form.alias" class="w-full pl-3 pr-3 py-2 bg-white border border-slate-300 text-xs font-medium focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800 transition-all" placeholder="Alias..">
                                    </div>
                                    <div class="col-span-2">
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Tax Registration Number (NPWP)</label>
                                        <input type="text" v-model="form.tax_number" class="w-full pl-3 pr-3 py-2 bg-white border border-slate-300 text-xs font-medium focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800 transition-all" placeholder="00.000.000.0-000.000">
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <h4 class="text-[10px] font-bold text-amber-600 uppercase tracking-wide border-b border-slate-300 pb-1">2. Quality Control & Risk Profile</h4>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Tier Level <span class="text-rose-500">*</span></label>
                                        <select v-model="form.tier_level" class="w-full pl-3 pr-3 py-2 bg-white border border-slate-300 text-xs font-medium focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800 transition-all">
                                            <option value="oem">OEM (Car Manufacturer)</option>
                                            <option value="tier-1">Tier-1 (Direct Supplier)</option>
                                            <option value="tier-2">Tier-2</option>
                                            <option value="aftermarket">Aftermarket</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Risk Profile (PFMEA Match) <span class="text-rose-500">*</span></label>
                                        <select v-model="form.risk_profile" class="w-full pl-3 pr-3 py-2 bg-white border border-slate-300 text-xs font-medium focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800 transition-all">
                                            <option value="low">Low Risk</option>
                                            <option value="medium">Medium Risk</option>
                                            <option value="high">High Risk</option>
                                        </select>
                                    </div>
                                    <div class="col-span-2">
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Customer Specific Requirements (CSR) Doc Ref</label>
                                        <input type="text" v-model="form.csr_reference_doc" class="w-full pl-3 pr-3 py-2 bg-white border border-slate-300 text-xs font-medium focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800 transition-all" placeholder="e.g. SQAM-TOY-2025-Rev3">
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <h4 class="text-[10px] font-bold text-slate-600 uppercase tracking-wide border-b border-slate-300 pb-1">3. Communication & Logistics</h4>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Official Email</label>
                                        <input type="email" v-model="form.email" class="w-full pl-3 pr-3 py-2 bg-white border border-slate-300 text-xs font-medium focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800 transition-all" placeholder="user@customer.com">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Phone / Extension</label>
                                        <input type="text" v-model="form.phone" class="w-full pl-3 pr-3 py-2 bg-white border border-slate-300 text-xs font-medium focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800 transition-all" placeholder="+62 21-XXXX-XXXX">
                                    </div>
                                    <div class="col-span-2">
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Billing Address</label>
                                        <textarea v-model="form.billing_address" rows="2" class="w-full pl-3 pr-3 py-2 bg-white border border-slate-300 text-xs font-medium focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800 transition-all" placeholder="Invoice address..."></textarea>
                                    </div>
                                    <div class="col-span-2">
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Shipping Address (Delivery Plant) <span class="text-rose-500">*</span></label>
                                        <textarea v-model="form.shipping_address" required rows="2" class="w-full pl-3 pr-3 py-2 bg-white border border-slate-300 text-xs font-medium focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800 transition-all" placeholder="Plant delivery address..."></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-3 pb-4">
                                <h4 class="text-[10px] font-bold text-emerald-600 uppercase tracking-wide border-b border-slate-300 pb-1">
                                    4. Audit Trail & Status
                                </h4>
                                
                                <div class="grid grid-cols-1 gap-4">
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">
                                            Change Reason / Remark 
                                            <span v-if="isEditMode" class="text-rose-500 font-black">* (Required for Audit Trail)</span>
                                            <span v-else class="text-slate-400 font-medium italic">(Optional)</span>
                                        </label>
                                        
                                        <textarea 
                                            v-model="form.remark" 
                                            rows="2" 
                                            :required="isEditMode"
                                            :placeholder="isEditMode ? 'Wajib isi alasan perubahan data untuk keperluan audit...' : 'Masukkan catatan tambahan (opsional)...'"
                                            :class="[
                                                'w-full pl-3 pr-3 py-2 bg-white border text-xs font-medium focus:outline-none transition-all',
                                                form.errors.remark 
                                                    ? 'border-rose-500 focus:ring-1 focus:ring-rose-500 text-rose-600' 
                                                    : 'border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800'
                                            ]"
                                        ></textarea>
                                        
                                        <p v-if="form.errors.remark" class="mt-1 text-[10px] font-bold text-rose-500">
                                            {{ form.errors.remark }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-3 pb-6">
                                <div class="flex items-center justify-between p-3 bg-white border border-slate-300">
                                    <div class="flex flex-col min-w-0 pr-4">
                                        <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">Customer Status</span>
                                        <span class="text-[10px] font-medium text-slate-500 mt-0.5 truncate">
                                            {{ form.is_active ? "Customer status is currently Active." : "Customer status is Disabled." }}
                                        </span>
                                    </div>
                                    <button type="button" @click="form.is_active = !form.is_active" 
                                        :class="form.is_active ? 'bg-emerald-600' : 'bg-slate-400'" 
                                        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer border-2 border-transparent transition-colors duration-200 ease-in-out active:scale-95">
                                        <span :class="form.is_active ? 'translate-x-5' : 'translate-x-0'" 
                                            class="pointer-events-none inline-block h-5 w-5 transform bg-white shadow-sm transition duration-200">
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </form>

                        <div class="px-6 py-4 bg-white border-t border-slate-200 flex justify-end gap-3 shrink-0">
                            <button type="button" @click="isDrawerOpen = false" class="px-5 py-2.5 text-xs font-bold text-slate-600 bg-white border border-slate-300 hover:bg-slate-50 active:scale-95 transition">
                                Cancel
                            </button>
                            <button @click="submitForm" :disabled="form.processing" 
                                class="px-6 py-2.5 bg-blue-600 text-white font-bold text-xs shadow-md hover:bg-blue-700 transition-all active:scale-95 disabled:opacity-50 flex items-center gap-2">
                                <svg v-if="form.processing" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>

                                {{ form.processing ? "Saving..." : (isEditMode ? "Apply Changes" : "Save Customer") }}
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </div>
    </div>

    <!-- MODAL KONFIRMASI DELETE -->
    <transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
        <div v-if="showConfirmModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
            <div class="bg-white w-full max-w-sm border border-slate-200 shadow-2xl p-6">
                <div class="flex flex-col text-left">
                    <h3 class="text-lg font-black text-slate-900 mb-1">Confirm Deletion</h3>
                    <p class="text-xs text-slate-500 mb-4">Are you sure you want to delete <span class="font-bold text-slate-900">{{ selectedIds.length }} items</span>? This action cannot be undone.</p>
                    
                    <div class="mb-6">
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Reason for Deletion <span class="text-rose-500">*</span></label>
                        <textarea 
                            v-model="deleteReason" 
                            rows="2" 
                            class="w-full p-2 border border-slate-300 text-xs focus:outline-none focus:border-blue-500"
                            placeholder="e.g. Data redundancy, wrong entry, etc."
                        ></textarea>
                    </div>

                    <div class="flex gap-3 w-full">
                        <button @click="showConfirmModal = false" class="flex-1 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition">Cancel</button>
                        <button 
                            @click="confirmAction" 
                            :disabled="deleteForm.processing"
                            class="flex-1 px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs transition"
                        >
                            {{ deleteForm.processing ? 'Deleting...' : 'Yes, Delete' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>