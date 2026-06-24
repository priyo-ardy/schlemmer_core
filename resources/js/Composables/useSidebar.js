import { ref } from "vue";

// State global diletakkan di luar fungsi agar nilainya sama di seluruh komponen
const isCollapsed = ref(false);

export function useSidebar() {
    const toggleSidebar = () => {
        isCollapsed.value = !isCollapsed.value;
    };

    return {
        isCollapsed,
        toggleSidebar
    };
}