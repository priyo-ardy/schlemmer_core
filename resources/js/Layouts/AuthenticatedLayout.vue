<script setup>
import { ref } from "vue";
import Sidebar from "@/Components/Dashboard/Sidebar.vue";
import Header from "@/Components/Dashboard/Header.vue";
import Footer from "@/Components/Dashboard/Footer.vue";

const isSidebarCollapsed = ref(false);
</script>

<template>
    <div
        class="h-screen w-screen flex overflow-hidden bg-slate-50 font-sans antialiased text-slate-800"
    >
        <Sidebar :is-collapsed="isSidebarCollapsed" />

        <div class="flex-1 flex flex-col h-full overflow-hidden">
            <Header
                @toggle-sidebar="isSidebarCollapsed = !isSidebarCollapsed"
            />

            <main class="flex-1 overflow-y-auto p-6 sm:p-8 bg-slate-50/50">
                <slot />
            </main>

            <Footer />
        </div>
    </div>
</template>

<style scoped>
main::-webkit-scrollbar {
    width: 6px;
}
main::-webkit-scrollbar-track {
    background: transparent;
}
main::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 20px;
}
</style>
