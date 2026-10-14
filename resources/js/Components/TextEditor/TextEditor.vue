<script setup>
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Image from '@tiptap/extension-image'
import { ref, watch } from 'vue'
import axios from 'axios'

const props = defineProps({
  modelValue: {
    type: String,
    default: '',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue'])
const fileInput = ref(null)

// Kustomisasi ekstensi Image agar mendukung style & alignment langsung di HTML
const CustomImage = Image.extend({
  inline() {
    return true
  },
  group() {
    return 'inline'
  },
  addAttributes() {
    return {
      ...this.parent?.(),
      width: {
        default: '200px',
        parseHTML: element => element.style.width || element.getAttribute('width') || '200px',
        renderHTML: attributes => ({
          style: `width: ${attributes.width}; height: auto; display: inline-block; vertical-align: middle; margin: 0 4px;`,
        }),
      },
      style: {
        default: 'display: inline-block; vertical-align: middle;',
        parseHTML: element => element.getAttribute('style'),
        renderHTML: attributes => ({
          style: attributes.style,
        })
      }
    }
  },
})

const editor = useEditor({
  content: props.modelValue,
  editable: !props.disabled,
  extensions: [
    StarterKit,
    CustomImage,
  ],
  editorProps: {
    attributes: {
      class: 'focus:outline-none min-h-[100px] p-2.5 text-xs bg-white text-slate-800 leading-relaxed',
    },
  },
  onUpdate: ({ editor }) => {
    emit('update:modelValue', editor.getHTML())
  },
})

watch(() => props.modelValue, (newValue) => {
  if (editor.value && newValue !== editor.value.getHTML()) {
    editor.value.commands.setContent(newValue, false)
  }
})

watch(() => props.disabled, (newVal) => {
  editor.value?.setEditable(!newVal)
})

const triggerFileInput = () => {
  if (props.disabled) return
  fileInput.value.click()
}

const uploadImageFile = async (event) => {
  const file = event.target.files[0]
  if (!file) return

  const formData = new FormData()
  formData.append('image', file)

  try {
    const response = await axios.post('/api/v1/upload-image', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })

    const imageUrl = response.data.url

    if (imageUrl && editor.value) {
      // Masukkan gambar dengan ukuran default 200px agar langsung sebaris
      editor.value.chain().focus().setImage({ 
        src: imageUrl, 
        width: '200px',
        style: 'display: inline-block; vertical-align: middle; margin: 0 4px;'
      }).run()
    }
  } catch (error) {
    console.error('Gagal mengupload gambar:', error)
    alert('Gagal mengupload gambar ke server.')
  } finally {
    event.target.value = ''
  }
}

// Fungsi ubah ukuran gambar aktif
const setImageWidth = (w) => {
  editor.value?.chain().focus().updateAttributes('image', { width: w }).run()
}

// Fungsi ubah posisi (Sebaris / Sebelah Kiri / Sebelah Kanan)
const setImageStyle = (customStyle) => {
  editor.value?.chain().focus().updateAttributes('image', { style: customStyle }).run()
}
</script>

<template>
  <div class="border border-slate-300 w-full bg-white">
    <!-- TOOLBAR -->
    <div v-if="!disabled" class="flex flex-wrap gap-1 p-1 bg-slate-100 border-b border-slate-300 text-xs items-center">
      <button 
        type="button"
        @click="editor?.chain().focus().toggleBold().run()"
        :class="{ 'bg-slate-800 text-white font-bold': editor?.isActive('bold') }"
        class="px-2 py-0.5 border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 transition"
      >
        Bold
      </button>

      <button 
        type="button"
        @click="editor?.chain().focus().toggleItalic().run()"
        :class="{ 'bg-slate-800 text-white font-bold': editor?.isActive('italic') }"
        class="px-2 py-0.5 border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 transition"
      >
        Italic
      </button>

      <button 
        type="button"
        @click="editor?.chain().focus().toggleBulletList().run()"
        :class="{ 'bg-slate-800 text-white font-bold': editor?.isActive('bulletList') }"
        class="px-2 py-0.5 border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 transition"
      >
        Bullet List
      </button>

      <button 
        type="button"
        @click="triggerFileInput"
        class="px-2 py-0.5 border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 transition font-medium flex items-center gap-1"
      >
        Insert Image
      </button>

      <!-- KONTROL KHUSUS GAMBAR (Hanya muncul saat gambar diklik di editor) -->
      <template v-if="editor?.isActive('image')">
        <div class="w-px h-4 bg-slate-300 mx-1"></div>
        <span class="text-[10px] text-slate-400 font-semibold">Size:</span>
        <button type="button" @click="setImageWidth('25px')" class="px-1.5 py-0.5 border bg-white hover:bg-slate-50">25px</button>
        <button type="button" @click="setImageWidth('50px')" class="px-1.5 py-0.5 border bg-white hover:bg-slate-50">50px</button>
        <button type="button" @click="setImageWidth('100px')" class="px-1.5 py-0.5 border bg-white hover:bg-slate-50">100px</button>
        <button type="button" @click="setImageWidth('200px')" class="px-1.5 py-0.5 border bg-white hover:bg-slate-50">200px</button>
        <button type="button" @click="setImageWidth('400px')" class="px-1.5 py-0.5 border bg-white hover:bg-slate-50">400px</button>

        <div class="w-px h-4 bg-slate-300 mx-1"></div>
        <span class="text-[10px] text-slate-400 font-semibold">Layout:</span>
        <button type="button" @click="setImageStyle('display: inline-block; vertical-align: middle; margin: 0 4px;')" class="px-1.5 py-0.5 border bg-white hover:bg-slate-50" title="Sejajar sebaris dengan teks">Inline</button>
        <button type="button" @click="setImageStyle('float: left; margin: 4px 8px 4px 0;')" class="px-1.5 py-0.5 border bg-white hover:bg-slate-50" title="Teks mengalir di sebelah kanan gambar">Float Left</button>
        <button type="button" @click="setImageStyle('float: right; margin: 4px 0 4px 8px;')" class="px-1.5 py-0.5 border bg-white hover:bg-slate-50" title="Teks mengalir di sebelah kiri gambar">Float Right</button>
      </template>

      <input 
        ref="fileInput" 
        type="file" 
        accept="image/*" 
        class="hidden" 
        @change="uploadImageFile" 
      />
    </div>

    <!-- AREA KONTEN EDITOR -->
    <editor-content :editor="editor" />
  </div>
</template>

<style>
.ProseMirror p {
  margin-bottom: 0.5em;
  line-height: 1.5;
}
.ProseMirror img {
  border-radius: 4px;
  border: 1px solid #cbd5e1;
}
</style>