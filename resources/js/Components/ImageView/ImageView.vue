<script setup>
import { NodeViewWrapper, nodeViewProps } from '@tiptap/vue-3'
import { ref, computed } from 'vue'

const props = defineProps(nodeViewProps)

const width = computed({
  get: () => props.node.attrs.width || '200px',
  set: (val) => props.updateAttributes({ width: val })
})

const alignment = computed({
  get: () => props.node.attrs.alignment || 'inline',
  set: (val) => props.updateAttributes({ alignment: val })
})

const showResizeMenu = ref(false)

const setCustomWidth = (w) => {
  width.value = w
  showResizeMenu.value = false
}

const setAlignment = (alignValue) => {
  alignment.value = alignValue
  showResizeMenu.value = false
}
</script>

<template>
  <NodeViewWrapper 
    as="span"
    class="image-node-wrapper relative inline-block align-middle mx-1"
    :class="{
      'float-left mr-3 mb-1': node.attrs.alignment === 'left',
      'float-right ml-3 mb-1': node.attrs.alignment === 'right',
      'inline-block align-middle': node.attrs.alignment === 'inline'
    }"
  >
    <span class="relative group inline-block align-middle">
      <img 
        :src="node.attrs.src" 
        :style="{ width: node.attrs.width, height: 'auto' }"
        class="border border-slate-300 rounded cursor-pointer transition-all hover:ring-2 hover:ring-blue-400 inline-block align-middle"
        @click="showResizeMenu = !showResizeMenu"
      />

      <!-- Popover Kontrol Ukuran & Posisi -->
      <div v-if="showResizeMenu" class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 bg-slate-900 text-white text-[10px] p-2 rounded shadow-xl z-50 flex flex-col gap-1.5 whitespace-nowrap">
        <div class="flex items-center gap-1">
          <span class="text-slate-400">Size:</span>
          <button type="button" @click="setCustomWidth('50px')" class="px-1.5 py-0.5 bg-slate-700 hover:bg-blue-600 rounded">XS (50px)</button>
          <button type="button" @click="setCustomWidth('100px')" class="px-1.5 py-0.5 bg-slate-700 hover:bg-blue-600 rounded">S (100px)</button>
          <button type="button" @click="setCustomWidth('200px')" class="px-1.5 py-0.5 bg-slate-700 hover:bg-blue-600 rounded">M (200px)</button>
          <button type="button" @click="setCustomWidth('400px')" class="px-1.5 py-0.5 bg-slate-700 hover:bg-blue-600 rounded">L (400px)</button>
          <button type="button" @click="setCustomWidth('100%')" class="px-1.5 py-0.5 bg-slate-700 hover:bg-blue-600 rounded">Full</button>
        </div>
        <div class="flex items-center gap-1 border-t border-slate-700 pt-1">
          <span class="text-slate-400">Layout:</span>
          <button type="button" @click="setAlignment('inline')" class="px-1.5 py-0.5 bg-slate-700 hover:bg-blue-600 rounded">Sebaris (Inline)</button>
          <button type="button" @click="setAlignment('left')" class="px-1.5 py-0.5 bg-slate-700 hover:bg-blue-600 rounded">Float Left</button>
          <button type="button" @click="setAlignment('right')" class="px-1.5 py-0.5 bg-slate-700 hover:bg-blue-600 rounded">Float Right</button>
        </div>
      </div>
    </span>
  </NodeViewWrapper>
</template>