<script setup>
import { ref, computed } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import { useSidebar } from "@/Composables/useSidebar";

const page = usePage();
const currentPath = computed(() => page.url.split("?")[0]);

// Mengambil state reaktif dari composable sidebar
const { isCollapsed } = useSidebar();

const isUserManagementOpen = ref(currentPath.value.startsWith("/users") || currentPath.value.startsWith("/roles"));
const isProcessOpen = ref(currentPath.value.startsWith("/process"));
const isMaterialOpen = ref(currentPath.value.startsWith("/materials"));
const isProjectOpen = ref(currentPath.value.startsWith("/projects"));
const isUnitOpen = ref(currentPath.value.startsWith("/units") || currentPath.value.startsWith("/unit_category"));
const isCustomerOpen = ref(currentPath.value.startsWith("/customer"));
const isPfmeaOpen = ref(currentPath.value.startsWith("/pfmea"));
const isApqpOpen = ref(currentPath.value.startsWith("/apqp"));

const toggleUserManagement = () => { if (!isCollapsed.value) isUserManagementOpen.value = !isUserManagementOpen.value; };
const togglePfmeaList = () => { if (!isCollapsed.value) isPfmeaOpen.value = !isPfmeaOpen.value; };
const toggleApqp = () => { if (!isCollapsed.value) isApqpOpen.value = !isApqpOpen.value; };
const toggleProcessList = () => { if (!isCollapsed.value) isProcessOpen.value = !isProcessOpen.value; };
const toggleProjects = () => { if (!isCollapsed.value) isProjectOpen.value = !isProjectOpen.value; };
const toggleUnits = () => { if (!isCollapsed.value) isUnitOpen.value = !isUnitOpen.value; };
const toggleMaterials = () => { if (!isCollapsed.value) isMaterialOpen.value = !isMaterialOpen.value; };
const toggleCustomer = () => { if (!isCollapsed.value) isCustomerOpen.value = !isCustomerOpen.value; };
</script>

<template>
    <aside
        :class="isCollapsed ? 'w-16' : 'w-64'"
        class="min-h-screen bg-slate-900 text-slate-300 flex flex-col border-r border-slate-800 font-sans antialiased transition-all duration-300 ease-in-out overflow-hidden shrink-0"
    >
        <div class="h-16 flex items-center px-5 border-b border-slate-800 whitespace-nowrap overflow-hidden">
            <span v-show="!isCollapsed" class="text-sm font-black tracking-widest text-white uppercase">
                PMFEA <span class="text-blue-500 font-normal">CORE</span>
            </span>
            <span v-show="isCollapsed" class="text-sm font-black tracking-widest text-blue-500 uppercase mx-auto">
                PM
            </span>
        </div>

        <nav class="flex-1 px-3 py-6 space-y-7 overflow-y-auto overflow-x-hidden select-none">

            <!-- DASHBOARD -->
            <div class="space-y-1">
                <Link
                    href="/dashboard"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition duration-150 group whitespace-nowrap"
                    :class="currentPath === '/dashboard' ? 'bg-slate-800 text-white font-semibold shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white'"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 transition" :class="currentPath === '/dashboard' ? 'text-blue-500' : 'text-slate-400 group-hover:text-blue-500'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2v-4zM14 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2v-4z" />
                    </svg>
                    <span v-show="!isCollapsed">Dashboard</span>
                </Link>
            </div>

            <div class="space-y-1">
                <span v-show="!isCollapsed" class="block px-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2 whitespace-nowrap">
                    Transaction
                </span>

                <!-- MODUL PFMEA -->
                <div v-if="$can('view pfmea')">
                    <button @click="togglePfmeaList" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white hover:bg-slate-800/20 transition duration-150 whitespace-nowrap">
                        <div class="flex items-center gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                            </svg>
                            <span v-show="!isCollapsed">PFMEA</span>
                        </div>
                        <svg v-show="!isCollapsed" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 transition-transform duration-200" :class="isPfmeaOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div v-show="isPfmeaOpen && !isCollapsed" class="mt-1 ml-4 pl-4 border-l border-slate-800 space-y-1">
                        <Link href="/pfmea" class="flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-medium transition duration-150" :class="currentPath.startsWith('/pfmea') ? 'text-white font-bold bg-slate-800/60' : 'text-slate-400 hover:text-white hover:bg-slate-800/20'">
                            <span>PFMEA List</span>
                        </Link>
                    </div>
                </div>

                <!-- MODUL APQP -->
                <div v-if="$can('view apqp')">
                    <button @click="toggleApqp" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white hover:bg-slate-800/20 transition duration-150 whitespace-nowrap">
                        <div class="flex items-center gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                            </svg>
                            <span v-show="!isCollapsed">APQP</span>
                        </div>
                        <svg v-show="!isCollapsed" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 transition-transform duration-200" :class="isApqpOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                </div>

                <span v-show="!isCollapsed" class="block px-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2 whitespace-nowrap">
                    Master Data
                </span>

                <!-- MODUL PROCESS -->
                <div v-if="$can('view process')">
                    <button @click="toggleProcessList" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white hover:bg-slate-800/20 transition duration-150 whitespace-nowrap">
                        <div class="flex items-center gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                            </svg>
                            <span v-show="!isCollapsed">Process Templates</span>
                        </div>
                        <svg v-show="!isCollapsed" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 transition-transform duration-200" :class="isProcessOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div v-show="isProcessOpen && !isCollapsed" class="mt-1 ml-4 pl-4 border-l border-slate-800 space-y-1">
                        <Link href="/process" class="flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-medium transition duration-150" :class="currentPath.startsWith('/process') ? 'text-white font-bold bg-slate-800/60' : 'text-slate-400 hover:text-white hover:bg-slate-800/20'">
                            <span>Process Template</span>
                        </Link>
                    </div>
                </div>

                <!-- MODUL CUSTOMER -->
                <div v-if="$can('view customer')">
                    <button @click="toggleCustomer" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white hover:bg-slate-800/20 transition duration-150 whitespace-nowrap">
                        <div class="flex items-center gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <span v-show="!isCollapsed">Customer Management</span>
                        </div>
                        <svg v-show="!isCollapsed" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 transition-transform duration-200" :class="isCustomerOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div v-show="isCustomerOpen && !isCollapsed" class="mt-1 ml-4 pl-4 border-l border-slate-800 space-y-1">
                        <Link href="/customer" class="flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-medium transition duration-150" :class="currentPath.startsWith('/customer') ? 'text-white font-bold bg-slate-800/60' : 'text-slate-400 hover:text-white hover:bg-slate-800/20'">
                            <span>List of Customer</span>
                        </Link>
                    </div>
                </div>

                <!-- MODUL PROJECTS -->
                <div v-if="$can('view projects')">
                    <button @click="toggleProjects" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white hover:bg-slate-800/20 transition duration-150 whitespace-nowrap">
                        <div class="flex items-center gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span v-show="!isCollapsed">Projects Management</span>
                        </div>
                        <svg v-show="!isCollapsed" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 transition-transform duration-200" :class="isProjectOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div v-show="isProjectOpen && !isCollapsed" class="mt-1 ml-4 pl-4 border-l border-slate-800 space-y-1">
                        <Link href="/projects" class="flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-medium transition duration-150" :class="currentPath.startsWith('/projects') ? 'text-white font-bold bg-slate-800/60' : 'text-slate-400 hover:text-white hover:bg-slate-800/20'">
                            <span>List of Project</span>
                        </Link>
                    </div>
                </div>

                <!-- MODUL UNITS -->
                <div v-if="$can('view units')">
                    <button @click="toggleUnits" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white hover:bg-slate-800/20 transition duration-150 whitespace-nowrap">
                        <div class="flex items-center gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1.5m0 15V21m-9-9h1.5m15 0H21m-3.343-5.657l-1.06 1.06m-9.193 9.193l-1.06 1.06m11.314 0l-1.06-1.06m-9.193-9.193l-1.06-1.06M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <span v-show="!isCollapsed">Unit of Measure</span>
                        </div>
                        <svg v-show="!isCollapsed" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 transition-transform duration-200" :class="isUnitOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div v-show="isUnitOpen && !isCollapsed" class="mt-1 ml-4 pl-4 border-l border-slate-800 space-y-1">
                        <Link href="/unit_category" class="flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-medium transition duration-150" :class="currentPath.startsWith('/unit_category') ? 'text-white font-bold bg-slate-800/60' : 'text-slate-400 hover:text-white hover:bg-slate-800/20'">
                            <span>UoM Categories</span>
                        </Link>
                        <Link href="/units" class="flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-medium transition duration-150" :class="currentPath.startsWith('/units') ? 'text-white font-bold bg-slate-800/60' : 'text-slate-400 hover:text-white hover:bg-slate-800/20'">
                            <span>List of UoM</span>
                        </Link>
                    </div>
                </div>

                <!-- MODUL MATERIALS -->
                <div v-if="$can('view materials')">
                    <button @click="toggleMaterials" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white hover:bg-slate-800/20 transition duration-150 whitespace-nowrap">
                        <div class="flex items-center gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            <span v-show="!isCollapsed">Materials</span>
                        </div>
                        <svg v-show="!isCollapsed" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 transition-transform duration-200" :class="isMaterialOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div v-show="isMaterialOpen && !isCollapsed" class="mt-1 ml-4 pl-4 border-l border-slate-800 space-y-1">
                        <Link href="/materials" class="flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-medium transition duration-150" :class="currentPath.startsWith('/materials') ? 'text-white font-bold bg-slate-800/60' : 'text-slate-400 hover:text-white hover:bg-slate-800/20'">
                            <span>List of Materials</span>
                        </Link>
                    </div>
                </div>
            </div>

            <div class="space-y-1">
                <span v-show="!isCollapsed" class="block px-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2 whitespace-nowrap">
                    Application Setting
                </span>

                <!-- USER MANAGEMENT SETTING -->
                <div v-if="$can('view users') || $can('view roles')">
                    <button
                        @click="toggleUserManagement"
                        class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-medium transition duration-150 group whitespace-nowrap"
                        :class="currentPath.startsWith('/users') || currentPath.startsWith('/roles') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white'"
                    >
                        <div class="flex items-center gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 transition" :class="currentPath.startsWith('/users') || currentPath.startsWith('/roles') ? 'text-blue-500' : 'text-slate-400 group-hover:text-blue-500'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2" />
                                <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2" />
                            </svg>
                            <span v-show="!isCollapsed">User Management</span>
                        </div>
                        <svg v-show="!isCollapsed" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-500 transition-transform duration-200" :class="{ 'rotate-180 text-blue-500': isUserManagementOpen }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div v-show="isUserManagementOpen && !isCollapsed" class="mt-1 ml-4 pl-4 border-l border-slate-800 space-y-1">
                        <Link v-if="$can('view users')" href="/users" class="flex items-center px-3 py-2 rounded-lg text-xs font-medium transition duration-150" :class="currentPath === '/users' ? 'text-white font-bold bg-slate-800/60' : 'text-slate-400 hover:text-white hover:bg-slate-800/20'">
                            Users List
                        </Link>
                        <Link v-if="$can('view roles')" href="/roles" class="flex items-center px-3 py-2 rounded-lg text-xs font-medium transition duration-150" :class="currentPath === '/roles' ? 'text-white font-bold bg-slate-800/60' : 'text-slate-400 hover:text-white hover:bg-slate-800/20'">
                            User Role
                        </Link>
                    </div>
                </div>

                <Link
                    href="/recycle-bin"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition duration-150 group whitespace-nowrap"
                    :class="currentPath.startsWith('/recycle-bin') ? 'bg-slate-800 text-white font-semibold shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white'"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 transition" :class="currentPath.startsWith('/recycle-bin') ? 'text-blue-500' : 'text-slate-400 group-hover:text-blue-500'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    <span v-show="!isCollapsed">Recycle Bin</span>
                </Link>

                <Link
                    href="/activity-logs"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition duration-150 group whitespace-nowrap"
                    :class="currentPath.startsWith('/activity-logs') ? 'bg-slate-800 text-white font-semibold shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white'"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 transition" :class="currentPath.startsWith('/activity-logs') ? 'text-blue-500' : 'text-slate-400 group-hover:text-blue-500'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2a4 4 0 014-4h3m0 0l3-3m-3 3l-3 3m-7 4h2a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <span v-show="!isCollapsed">Activity Logs</span>
                </Link>

            </div>
        </nav>

        <div class="p-4 border-t border-slate-800 bg-slate-950/40 text-center whitespace-nowrap overflow-hidden">
            <span class="text-[10px] font-mono text-slate-600 tracking-wider">
                {{ isCollapsed ? 'V1' : 'v' + ($page.props.app_version ?? '1.0.0') }}
            </span>
        </div>
    </aside>
</template>
