<script setup lang="ts">
import Link from '@tiptap/extension-link'
import Underline from '@tiptap/extension-underline'
import StarterKit from '@tiptap/starter-kit'
import { EditorContent, useEditor } from '@tiptap/vue-3'

const props = defineProps<{
  modelValue: string
  placeholder?: string
}>()

const emit = defineEmits<{
  'update:modelValue': [string]
}>()

const editor = useEditor({
  extensions: [
    StarterKit,
    Underline,
    Link.configure({
      openOnClick: false,
      autolink: true
    })
  ],
  content: props.modelValue,
  editorProps: {
    attributes: {
      class: 'tiptap prose-content',
      'data-placeholder': props.placeholder || ''
    }
  },
  onUpdate({ editor }) {
    emit('update:modelValue', editor.getHTML())
  }
})

watch(() => props.modelValue, (value) => {
  if (editor.value && value !== editor.value.getHTML()) {
    editor.value.commands.setContent(value || '<p></p>', false)
  }
})

onBeforeUnmount(() => {
  editor.value?.destroy()
})

function runCommand(command: () => void) {
  if (!editor.value) {
    return
  }

  command()
}

function setLink() {
  if (!editor.value) {
    return
  }

  const selection = {
    from: editor.value.state.selection.from,
    to: editor.value.state.selection.to
  }
  const previousUrl = editor.value.getAttributes('link').href || ''
  const url = window.prompt('Enter URL', previousUrl)

  if (url === null) {
    return
  }

  if (url === '') {
    editor.value.chain().focus().setTextSelection(selection).unsetLink().run()
    return
  }

  editor.value.chain().focus().setTextSelection(selection).extendMarkRange('link').setLink({ href: url }).run()
}
</script>

<template>
  <div class="overflow-hidden rounded-md border border-slate-200 bg-white">
    <div class="tiptap-toolbar flex flex-wrap gap-2 border-b border-slate-200 p-3">
      <button class="btn-secondary !min-h-9 !px-3 !py-2" :class="{ '!border-blue-500 !bg-blue-50 !text-blue-700': editor?.isActive('bold') }" title="Bold" aria-label="Bold" type="button" @mousedown.prevent @click="runCommand(() => editor?.chain().focus().toggleBold().run())">
        <svg aria-hidden="true" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M7 5h6a4 4 0 0 1 0 8H7z" />
          <path d="M7 13h7a4 4 0 0 1 0 8H7z" />
        </svg>
      </button>
      <button class="btn-secondary !min-h-9 !px-3 !py-2" :class="{ '!border-blue-500 !bg-blue-50 !text-blue-700': editor?.isActive('italic') }" title="Italic" aria-label="Italic" type="button" @mousedown.prevent @click="runCommand(() => editor?.chain().focus().toggleItalic().run())">
        <svg aria-hidden="true" class="h-4 w-4" fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="2" viewBox="0 0 24 24">
          <path d="M14 4h4" />
          <path d="M6 20h4" />
          <path d="M14 4 10 20" />
        </svg>
      </button>
      <button class="btn-secondary !min-h-9 !px-3 !py-2" :class="{ '!border-blue-500 !bg-blue-50 !text-blue-700': editor?.isActive('underline') }" title="Underline" aria-label="Underline" type="button" @mousedown.prevent @click="runCommand(() => editor?.chain().focus().toggleUnderline().run())">
        <svg aria-hidden="true" class="h-4 w-4" fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="2" viewBox="0 0 24 24">
          <path d="M7 4v6a5 5 0 0 0 10 0V4" />
          <path d="M5 20h14" />
        </svg>
      </button>
      <button class="btn-secondary !min-h-9 !px-3 !py-2" :class="{ '!border-blue-500 !bg-blue-50 !text-blue-700': editor?.isActive('bulletList') }" title="Bullet List" aria-label="Bullet List" type="button" @mousedown.prevent @click="runCommand(() => editor?.chain().focus().toggleBulletList().run())">
        <svg aria-hidden="true" class="h-4 w-4" fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="2" viewBox="0 0 24 24">
          <circle cx="5" cy="7" r="1.5" fill="currentColor" stroke="none" />
          <circle cx="5" cy="12" r="1.5" fill="currentColor" stroke="none" />
          <circle cx="5" cy="17" r="1.5" fill="currentColor" stroke="none" />
          <path d="M9 7h10" />
          <path d="M9 12h10" />
          <path d="M9 17h10" />
        </svg>
      </button>
      <button class="btn-secondary !min-h-9 !px-3 !py-2" :class="{ '!border-blue-500 !bg-blue-50 !text-blue-700': editor?.isActive('orderedList') }" title="Numbered List" aria-label="Numbered List" type="button" @mousedown.prevent @click="runCommand(() => editor?.chain().focus().toggleOrderedList().run())">
        <svg aria-hidden="true" class="h-4 w-4" fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="2" viewBox="0 0 24 24">
          <path d="M10 7h9" />
          <path d="M10 12h9" />
          <path d="M10 17h9" />
          <path d="M4 6h2v3" />
          <path d="M4 12c0-1.1.9-2 2-2s2 .9 2 2c0 2-4 1-4 4h4" />
          <path d="M4 16.5c.5-.3 1.1-.5 1.8-.5 1.2 0 2.2.7 2.2 1.8S7 20 5.8 20c-.7 0-1.3-.2-1.8-.5" />
        </svg>
      </button>
      <button class="btn-secondary !min-h-9 !px-3 !py-2" :class="{ '!border-blue-500 !bg-blue-50 !text-blue-700': editor?.isActive('link') }" title="Link" aria-label="Link" type="button" @mousedown.prevent @click="setLink">
        <svg aria-hidden="true" class="h-4 w-4" fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="2" viewBox="0 0 24 24">
          <path d="M10 13a5 5 0 0 1 0-7l1.5-1.5a5 5 0 1 1 7 7L17 13" />
          <path d="M14 11a5 5 0 0 1 0 7L12.5 19.5a5 5 0 1 1-7-7L7 11" />
        </svg>
      </button>
    </div>
    <EditorContent :editor="editor" />
  </div>
</template>
