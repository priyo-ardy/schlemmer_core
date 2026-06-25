<script setup>
import "vue3-toastify/dist/index.css";
import { ref, watch, computed } from "vue";
import { Head, router, Link, usePage, useForm } from "@inertiajs/vue3";
import { toast } from "vue3-toastify";
import { debounce } from "lodash";
import axios from "axios";
import dayjs from "dayjs";
import "dayjs/locale/id";

dayjs.locale("id");
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

defineOptions({ layout: AuthenticatedLayout });
</script>

<template>
    <Head title="List of Project" />
    <!-- Page Wrapper -->
    <div class="flex min-h-screen bg-slate-50 font-sans antialiased text-slate-800">
        <!-- Content Wrapper -->
         <div class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">
            <!-- Heading -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                <!-- Head Title -->
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">
                        Projects Management
                    </h1>
                    <p class="text-xs text-slate-500 mt-1">
                        Manage project.
                    </p>
                </div>

                <!-- Toolbar -->
                <div class="sticky top-0 z-30 bg-white/95 backdrop-blur-sm border border-slate-200 px-3 py-2 shadow-sm">
                    <div class="flex items-center justify-between w-full">
                        <div class="flex items-center gap-1.5">
                            <!-- Button New -->
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-bold text-blue-600 bg-blue-50 border border-blue-100 transition-all hover:bg-blue-100 active:scale-95 shadow-sm"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-3.5 w-3.5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="2"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                                    />
                                </svg>
                                <span>New</span>
                            </button>

                            <!-- Button Refresh -->
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-bold text-slate-600 bg-orange-50 border border-slate-200 transition-all hover:bg-orange-100 active:scale-95 shadow-sm disabled:opacity-60"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-3.5 w-3.5 transition-colors duration-150"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="2"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"
                                    />
                                </svg>
                                <span>Refresh</span>
                            </button>

                            <!-- Button Delete -->
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-bold text-rose-600 bg-rose-50 border border-rose-100 transition-all hover:bg-rose-100 active:scale-95 shadow-sm disabled:opacity-40 disabled:active:scale-100"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-4 w-4"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                    />
                                </svg>
                                <span>Delete (0)</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Search, per page, status -->
            <div class="flex-1 flex flex-col">
                <div class="bg-white border border-slate-200/80 shadow-sm p-4 mb-6 flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
                    <!-- Search bar -->
                    <div class="relative flex-1 max-w-md">
                        <input
                            type="text"
                            placeholder="Search dynamically..."
                            class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 focus:border-blue-500 text-xs font-semibold focus:outline-none transition"
                        />
                    </div>

                    <div class="flex items-center gap-4">
                        <!-- Per page -->
                        <div class="flex items-center gap-2">
                            <label
                                class="text-xs font-bold text-slate-400 uppercase tracking-wider"
                                >Per Page:</label
                            >
                            <select class="px-3 py-2.5 bg-white border border-slate-200 text-slate-700 text-xs font-semibold focus:outline-none focus:border-blue-500 cursor-pointer transition">
                                <option :value="10">10</option>
                                <option :value="25">25</option>
                                <option :value="50">50</option>
                                <option :value="100">100</option>
                            </select>
                        </div>

                        <!-- Status Filter -->
                        <div class="flex items-center gap-2">
                            <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">
                                Status:
                            </label>
                            <select class="px-3 py-2.5 bg-white border border-slate-200 text-slate-700 text-xs font-semibold focus:outline-none focus:border-blue-500 cursor-pointer transition">
                                <option value="all">All</option>
                                <option value="enable">Enabled</option>
                                <option value="disable">Disabled</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table Section -->
             <div class="bg-white border border-slate-200/80 shadow-sm overflow-hidden mb-8">
                <div class="overflow-auto max-h-[calc(100vh-320px)]">
                    <table class="w-full text-left border-collapse bg-white whitespace-nowrap">
                        <thead class="bg-blue-300 text-slate-700 uppercase tracking-wider text-[11px] font-bold border-b border-slate-200 sticky top-0 z-10">
                            <tr>
                                <th class="px-4 py-3 w-12 text-center">
                                    <input type="checkbox" class="border-slate-300 text-blue-600 h-4 w-4 cursor-pointer"/>
                                </th>
                                <th class=" px-4 py-3">Project Code</th>
                                <th class=" px-4 py-3">Project Name</th>
                                <th class=" px-4 py-3">Customer</th>
                                <th class=" px-4 py-3">Revision</th>
                                <th class=" px-4 py-3">Vehicle Model</th>
                                <th class=" px-4 py-3">Main Part Number</th>
                                <th class=" px-4 py-3">Main Part Name</th>
                                <th class=" px-4 py-3">Main Part Name</th>
                                <th class=" px-4 py-3">APQP Phase</th>
                                <th class=" px-4 py-3">Project Status</th>
                                <th class=" px-4 py-3">Kick Off Date</th>
                                <th class=" px-4 py-3">Target Proto Date</th>
                                <th class=" px-4 py-3">Target PPAP Date</th>
                                <th class=" px-4 py-3">Targe SOP Date</th>
                                <th class=" px-4 py-3">Confidentiality Level</th>
                                <th class=" px-4 py-3">Data Status</th>
                                <th class=" px-4 py-3">Remark</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs text-slate-600">
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
             </div>
        </div>
    </div>

    <!-- Slideover -->

    <!-- Konfirmasi hapus -->

    <!-- Hostory -->
</template>