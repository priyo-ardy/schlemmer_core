<script setup>
import { ref } from "vue";
import { Head } from "@inertiajs/vue3";
import Sidebar from "@/Components/Dashboard/Sidebar.vue"; // <-- JANGAN LUPA IMPORT SIDEBAR KEMARIN (Sesuaikan path-nya)

// State untuk komponen interaktif
const searchQuery = ref("");
const selectedFilter = ref("all");

// Dummy Data untuk karyawan
const users = ref([
    {
        id: 1,
        emp_id: "SLM-001",
        name: "Ardy Priyo S",
        email: "priyo.ardy@schlemmer.co.id",
        dept: "IT & ERP",
        role: "Administrator",
        status: "Active",
    },
    {
        id: 2,
        emp_id: "SLM-042",
        name: "Budi Santoso",
        email: "budi.s@schlemmer.co.id",
        dept: "Logistics",
        role: "Staff",
        status: "Active",
    },
    {
        id: 3,
        emp_id: "SLM-105",
        name: "Siti Rahma",
        email: "siti.r@schlemmer.co.id",
        dept: "Production",
        role: "Supervisor",
        status: "Active",
    },
    {
        id: 4,
        emp_id: "SLM-089",
        name: "Eko Wijaya",
        email: "eko.w@schlemmer.co.id",
        dept: "Quality Control",
        role: "Staff",
        status: "Inactive",
    },
    {
        id: 5,
        emp_id: "SLM-112",
        name: "Rian Hidayat",
        email: "rian.h@schlemmer.co.id",
        dept: "Logistics",
        role: "Staff",
        status: "Pending",
    },
]);

const handleSearch = () => console.log("Searching...", searchQuery.value);
const handleExport = () => alert("Exporting data...");
const handleNewUser = () => alert("Opening New User Form...");
</script>

<template>
    <Head title="User Management" />

    <div
        class="flex min-h-screen bg-slate-50 font-sans antialiased text-slate-800"
    >
        <Sidebar class="flex-shrink-0" />

        <div
            class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto p-6 md:p-8"
        >
            <div
                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8"
            >
                <div>
                    <h1
                        class="text-2xl font-black text-slate-900 tracking-tight"
                    >
                        User Management
                    </h1>
                    <p class="text-xs text-slate-500 mt-1">
                        Manage platform access, roles, and employee directory
                        accounts.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        @click="handleExport"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-slate-200 text-slate-700 hover:text-slate-900 font-bold text-xs rounded-xl shadow-sm hover:bg-slate-100 transition duration-150"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4 text-slate-400"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                            />
                        </svg>
                        Export Data
                    </button>
                    <button
                        @click="handleNewUser"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-md shadow-blue-600/10 transition duration-150"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/xl"
                            class="h-4 w-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2.5"
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                        New User
                    </button>
                </div>
            </div>

            <div
                class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-4 mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4"
            >
                <div class="relative flex-1 max-w-md">
                    <span
                        class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                            />
                        </svg>
                    </span>
                    <input
                        type="text"
                        v-model="searchQuery"
                        @input="handleSearch"
                        placeholder="Search by name, email, or employee ID..."
                        class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 focus:border-blue-500 rounded-xl text-xs font-medium focus:outline-none focus:ring-2 focus:ring-blue-600/10 transition"
                    />
                </div>

                <div class="flex items-center gap-3">
                    <label
                        class="text-xs font-bold text-slate-400 uppercase tracking-wider"
                        >Status:</label
                    >
                    <select
                        v-model="selectedFilter"
                        class="px-4 py-2.5 bg-white border border-slate-200 text-slate-700 rounded-xl text-xs font-medium focus:outline-none focus:ring-2 focus:ring-blue-600/10 focus:border-blue-500 transition"
                    >
                        <option value="all">All Employee Status</option>
                        <option value="active">Active Only</option>
                        <option value="inactive">Inactive Only</option>
                        <option value="pending">Pending Invitation</option>
                    </select>
                </div>
            </div>

            <div
                class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden mb-auto"
            >
                <div class="overflow-x-auto">
                    <table
                        class="min-w-full divide-y divide-slate-200 text-left"
                    >
                        <thead
                            class="bg-slate-50 text-[11px] font-bold text-slate-400 uppercase tracking-wider"
                        >
                            <tr>
                                <th class="px-6 py-4">Emp ID</th>
                                <th class="px-6 py-4">User Info</th>
                                <th class="px-6 py-4">Department</th>
                                <th class="px-6 py-4">System Role</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-slate-100 text-xs font-medium text-slate-700"
                        >
                            <tr
                                v-for="user in users"
                                :key="user.id"
                                class="hover:bg-slate-50/80 transition"
                            >
                                <td
                                    class="px-6 py-4 font-mono text-slate-500 font-semibold"
                                >
                                    {{ user.emp_id }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <span
                                            class="text-sm font-bold text-slate-900"
                                            >{{ user.name }}</span
                                        >
                                        <span
                                            class="text-xs text-slate-400 font-normal mt-0.5"
                                            >{{ user.email }}</span
                                        >
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="px-2.5 py-1 bg-slate-100 text-slate-600 rounded-md text-[11px] font-semibold"
                                        >{{ user.dept }}</span
                                    >
                                </td>
                                <td class="px-6 py-4 text-slate-600">
                                    {{ user.role }}
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide"
                                        :class="{
                                            'bg-emerald-50 text-emerald-700 border border-emerald-200':
                                                user.status === 'Active',
                                            'bg-rose-50 text-rose-700 border border-rose-200':
                                                user.status === 'Inactive',
                                            'bg-amber-50 text-amber-700 border border-amber-200':
                                                user.status === 'Pending',
                                        }"
                                    >
                                        <span
                                            class="h-1.5 w-1.5 rounded-full"
                                            :class="{
                                                'bg-emerald-500':
                                                    user.status === 'Active',
                                                'bg-rose-500':
                                                    user.status === 'Inactive',
                                                'bg-amber-500':
                                                    user.status === 'Pending',
                                            }"
                                        ></span>
                                        {{ user.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div
                                        class="flex items-center justify-end gap-2"
                                    >
                                        <button
                                            class="px-2.5 py-1.5 text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            class="px-2.5 py-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition"
                                        >
                                            Suspend
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    class="bg-slate-50/50 px-6 py-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 text-xs"
                >
                    <div class="text-slate-400 font-medium">
                        Showing
                        <span class="text-slate-800 font-bold">1</span> to
                        <span class="text-slate-800 font-bold">5</span> of
                        <span class="text-slate-800 font-bold">48</span> entries
                    </div>
                    <div class="flex items-center gap-1.5">
                        <button
                            class="px-3 py-1.5 bg-white border border-slate-200 text-slate-400 rounded-lg font-bold"
                            disabled
                        >
                            Previous
                        </button>
                        <button
                            class="px-3 py-1.5 bg-blue-600 text-white rounded-lg font-bold"
                        >
                            1
                        </button>
                        <button
                            class="px-3 py-1.5 bg-white border border-slate-200 text-slate-700 rounded-lg font-bold"
                        >
                            2
                        </button>
                        <button
                            class="px-3 py-1.5 bg-white border border-slate-200 text-slate-700 rounded-lg font-bold"
                        >
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
