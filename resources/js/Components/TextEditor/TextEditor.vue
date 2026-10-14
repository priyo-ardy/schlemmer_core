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
})

const emit = defineEmits(['update:modelValue'])

const fileInput = ref(null)

// Custom Image Extension untuk mendukung Alignment & Width
const CustomImage = Image.extend({
  addAttributes() {
    return {
      ...this.parent?.(),
      alignment: {
        default: 'center',
        parseHTML: element => element.getAttribute('data-alignment') || 'center',
        renderHTML: attributes => {
          return {
            'data-alignment': attributes.alignment,
            class: `image-align-${attributes.alignment}`,
          }
        },
      },
      width: {
        default: '100%',
        parseHTML: element => element.style.width || element.getAttribute('width') || '100%',
        renderHTML: attributes => {
          return {
            style: `width: ${attributes.width}; height: auto; display: block;`,
          }
        },
      },
    }
  },
})

const editor = useEditor({
  content: props.modelValue,
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

// Sinkronisasi jika nilai prop berubah dari luar
watch(() => props.modelValue, (newValue) => {
  if (editor.value && newValue !== editor.value.getHTML()) {
    editor.value.commands.setContent(newValue, false)
  }
})

// Membuka dialog file komputer saat tombol Insert Image diklik
const triggerFileInput = () => {
  fileInput.value.click()
}

// Mengirim file gambar ke backend Laravel
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
      editor.value.chain().focus().setImage({ 
        src: imageUrl, 
        alignment: 'center', 
        width: '100%' 
      }).run()
    }
  } catch (error) {
    console.error('Gagal mengupload gambar:', error)
    alert('Gagal mengupload gambar ke server.')
  } finally {
    event.target.value = ''
  }
}

// Fungsi mengubah posisi gambar yang sedang dipilih
const setImageAlignment = (align) => {
  editor.value?.chain().focus().updateAttributes('image', { alignment: align }).run()
}

// Fungsi mengubah ukuran gambar yang sedang dipilih
const setImageWidth = (width) => {
  editor.value?.chain().focus().updateAttributes('image', { width: width }).run()
}
</script>

<template>
  <div class="border border-slate-300 w-full bg-white">
    <!-- TOOLBAR -->
    <div class="flex flex-wrap gap-1 p-1 bg-slate-100 border-b border-slate-300 text-xs items-center">
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
        @click="editor?.chain().focus().toggleHeading({ level: 1 }).run()"
        :class="{ 'bg-slate-800 text-white font-bold': editor?.isActive('heading', { level: 1 }) }"
        class="px-2 py-0.5 border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 transition"
      >
        H1
      </button>

      <button 
        type="button"
        @click="editor?.chain().focus().toggleHeading({ level: 2 }).run()"
        :class="{ 'bg-slate-800 text-white font-bold': editor?.isActive('heading', { level: 2 }) }"
        class="px-2 py-0.5 border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 transition"
      >
        H2
      </button>

      <button 
        type="button"
        @click="editor?.chain().focus().toggleBulletList().run()"
        :class="{ 'bg-slate-800 text-white font-bold': editor?.isActive('bulletList') }"
        class="px-2 py-0.5 border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 transition"
      >
        Bullet List
      </button>

      <!-- Tombol Upload dari Komputer -->
      <button 
        type="button"
        @click="triggerFileInput"
        class="px-2 py-0.5 border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 transition font-medium flex items-center gap-1"
      >
        Insert Image
      </button>

      <!-- KONTROL TAMBAHAN KHUSUS GAMBAR (Hanya aktif jika gambar diklik/dipilih) -->
      <template v-if="editor?.isActive('image')">
        <div class="w-px h-4 bg-slate-300 mx-1"></div>
        <span class="text-[10px] text-slate-400 font-semibold">Align:</span>
        <button type="button" @click="setImageAlignment('left')" class="px-1.5 py-0.5 border bg-white hover:bg-slate-50">Left</button>
        <button type="button" @click="setImageAlignment('center')" class="px-1.5 py-0.5 border bg-white hover:bg-slate-50">Center</button>
        <button type="button" @click="setImageAlignment('right')" class="px-1.5 py-0.5 border bg-white hover:bg-slate-50">Right</button>

        <div class="w-px h-4 bg-slate-300 mx-1"></div>
        <span class="text-[10px] text-slate-400 font-semibold">Size:</span>
        <button type="button" @click="setImageWidth('25%')" class="px-1.5 py-0.5 border bg-white hover:bg-slate-50">25%</button>
        <button type="button" @click="setImageWidth('50%')" class="px-1.5 py-0.5 border bg-white hover:bg-slate-50">50%</button>
        <button type="button" @click="setImageWidth('100%')" class="px-1.5 py-0.5 border bg-white hover:bg-slate-50">100%</button>
      </template>

      <!-- Input file tersembunyi -->
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
.ProseMirror ul {
  list-style-type: disc;
  padding-left: 1.5rem;
}
.ProseMirror ol {
  list-style-type: decimal;
  padding-left: 1.5rem;
}
.ProseMirror img {
  height: auto;
  margin-top: 0.5rem;
  margin-bottom: 0.5rem;
}
/* Styling posisi gambar berdasarkan atribut alignment */
.ProseMirror img.image-align-left {
  margin-right: auto;
  margin-left: 0;
}
.ProseMirror img.image-align-center {
  margin-right: auto;
  margin-left: auto;
}
.ProseMirror img.image-align-right {
  margin-right: 0;
  margin-left: auto;
}
</style>