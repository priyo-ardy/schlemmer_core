<script setup>
import { ref, watch, onMounted } from "vue";
import { toast } from "vue3-toastify";
import { usePage } from "@inertiajs/vue3";
import Sidebar from "@/Components/Dashboard/Sidebar.vue";
import Header from "@/Components/Dashboard/Header.vue";
import Footer from "@/Components/Dashboard/Footer.vue";

const isSidebarCollapsed = ref(true);

const page = usePage();

const isMobile = ref(false);

onMounted(() => {
    const checkMobile = () => {
        isMobile.value = window.innerWidth < 768;
    };

    checkMobile();
    window.addEventListener('resize', checkMobile);
});

watch(
    () => page.props.flash,
    (flash) => {
        if (!flash) return;

        if (flash.success) {
            toast.success(flash.success);
        }

        if (flash.error) {
            toast.error(flash.error);
        }

        if (flash.warning) {
            toast.warning(flash.warning);
        }

        if (flash.info) {
            toast.info(flash.info);
        }
    },
    { immediate: true, deep: true }
);
</script>

<template>
    <div v-if="isMobile" class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900 text-white p-6 text-center">
        <div>
            <h2 class="text-xl font-bold mb-2">Perangkat Tidak Didukung</h2>
            <p class="text-sm text-slate-400">Aplikasi ini hanya tersedia untuk tampilan desktop.</p>
        </div>
    </div>

    <div
        class="h-screen w-screen flex overflow-hidden bg-slate-50 font-sans antialiased text-slate-800"
        :class="{'opacity-20 pointer-events-none': isMobile}"
    >
        <Sidebar :is-collapsed="isSidebarCollapsed" />

        <div class="flex-1 flex flex-col h-full overflow-hidden">
            <Header @toggle-sidebar="isSidebarCollapsed = !isSidebarCollapsed" />

            <main class="flex-1 overflow-y-auto p-2 sm:p-4 bg-slate-50/50">
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
