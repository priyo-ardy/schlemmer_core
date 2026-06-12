<template>
    <footer
        class="h-12 bg-white border-t border-slate-200/80 flex items-center justify-between px-6 flex-shrink-0 text-xs text-slate-400 font-medium z-10"
    >
        <p>
            &copy; {{ new Date().getFullYear() }} Schlemmer Indonesia Digital
            Platform. All rights reserved.
        </p>
        <div class="flex gap-4">
            <a href="#" class="hover:text-blue-600 transition">Privacy SOP</a>
            <a href="#" class="hover:text-blue-600 transition"
                >Security Core Audit</a
            >
        </div>
    </footer>
</template>
