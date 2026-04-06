<script setup lang="ts">
definePageMeta({ middleware: 'auth' })

const api = useApi()
const { logout, fetchUser } = useAuth()
const sidebarOpen = ref(false)
const notes = ref<any[]>([])
const loading = ref(true)
const search = ref('')
const selectedId = ref<string | null>(null)
const form = reactive({
  title: '',
  note: '<p></p>',
})
const saving = ref(false)
const creating = ref(false)
const deleting = ref(false)
const showDeleteModal = ref(false)
const saveMessage = ref('')
const saveError = ref('')
let saveMessageTimeout: ReturnType<typeof setTimeout> | null = null

const selectedNote = computed(() => notes.value.find((note) => note.id === selectedId.value) || null)
const filteredCount = computed(() => notes.value.length)

function normalizeRichText(value: string | null | undefined) {
  if (!value) {
    return '<p></p>'
  }

  const normalized = value
    .replace(/<p>\s*<\/p>/gi, '')
    .replace(/<p><br><\/p>/gi, '')
    .trim()

  return normalized || '<p></p>'
}

function plainTextPreview(value: string | null | undefined) {
  const text = (value || '')
    .replace(/<(br|\/p|\/div|\/li|\/h[1-6])[^>]*>/gi, '\n')
    .replace(/<[^>]+>/g, ' ')
    .replace(/&nbsp;/g, ' ')
    .replace(/\s+/g, ' ')
    .trim()

  return text || 'Empty note'
}

function syncForm(note: any | null) {
  form.title = note?.title || ''
  form.note = normalizeRichText(note?.note)
}

function showSavedMessage(message: string) {
  saveMessage.value = message

  if (saveMessageTimeout) {
    clearTimeout(saveMessageTimeout)
  }

  saveMessageTimeout = setTimeout(() => {
    saveMessage.value = ''
    saveMessageTimeout = null
  }, 3000)
}

async function loadNotes() {
  loading.value = true

  try {
    const data = await api<any[]>('/knowledgebase-notes', {
      query: {
        search: search.value || undefined,
      }
    })

    notes.value = data

    if (!data.length) {
      selectedId.value = null
      syncForm(null)
      return
    }

    if (!selectedId.value || !data.some((note) => note.id === selectedId.value)) {
      selectedId.value = data[0].id
    }

    syncForm(data.find((note) => note.id === selectedId.value) || data[0])
  } finally {
    loading.value = false
  }
}

async function clearSearch() {
  search.value = ''
  await loadNotes()
}

function selectNote(note: any) {
  selectedId.value = note.id
  saveError.value = ''
  syncForm(note)
}

async function createNote() {
  creating.value = true
  saveError.value = ''

  try {
    const created = await api<any>('/knowledgebase-notes', {
      method: 'POST',
      body: {
        title: `New Note ${notes.value.length + 1}`,
        note: '<p></p>',
      }
    })

    await loadNotes()
    selectNote(created)
    showSavedMessage('New note created.')
  } catch (error: any) {
    saveError.value = error?.data?.message || 'Could not create note.'
  } finally {
    creating.value = false
  }
}

async function saveNote() {
  if (!selectedId.value) {
    saveError.value = 'Select or create a note first.'
    return
  }

  saving.value = true
  saveError.value = ''

  try {
    const updated = await api<any>(`/knowledgebase-notes/${selectedId.value}`, {
      method: 'PUT',
      body: {
        title: form.title.trim(),
        note: normalizeRichText(form.note),
      }
    })

    const existingIndex = notes.value.findIndex((note) => note.id === updated.id)

    if (existingIndex >= 0) {
      notes.value.splice(existingIndex, 1, updated)
    } else {
      notes.value.unshift(updated)
    }

    notes.value = [...notes.value].sort((a, b) => new Date(b.updated_at).getTime() - new Date(a.updated_at).getTime())
    selectedId.value = updated.id
    syncForm(updated)
    showSavedMessage('Knowledgebase note saved.')
  } catch (error: any) {
    saveError.value = error?.data?.message || 'Could not save note.'
  } finally {
    saving.value = false
  }
}

function promptDeleteNote() {
  if (!selectedId.value) return
  showDeleteModal.value = true
}

async function confirmDeleteNote() {
  showDeleteModal.value = false
  deleting.value = true
  saveError.value = ''

  try {
    await api(`/knowledgebase-notes/${selectedId.value}`, { method: 'DELETE' })
    const idx = notes.value.findIndex(n => n.id === selectedId.value)
    if (idx >= 0) notes.value.splice(idx, 1)
    
    selectedId.value = notes.value.length ? notes.value[0].id : null
    if (selectedId.value) syncForm(notes.value[0])
    else syncForm(null)
    
    showSavedMessage('Note deleted.')
  } catch (error: any) {
    saveError.value = error?.data?.message || 'Could not delete note.'
  } finally {
    deleting.value = false
  }
}

async function signOut() {
  await logout()
  await navigateTo('/employer/login')
}

onMounted(async () => {
  await fetchUser()
  await loadNotes()
})
</script>

<template>
  <div class="page-shell employer-ui">
    <AdminSidebar :open="sidebarOpen" @close="sidebarOpen = false" @logout="signOut" />

    <main class="admin-main md:ml-72">
      <div class="admin-content space-y-6">
        <div class="flex items-center justify-between gap-4 flex-wrap bg-slate-50 p-3 rounded-md border border-slate-200 mb-5">
          <div class="text-base font-bold text-slate-700">
            Notes contain hiring person's individual views, this does not represent company's hiring practices or guidelines.
          </div>
          <div class="flex items-center gap-3 ml-auto">
            <button class="btn-admin-primary !mt-0" :disabled="creating" @click="createNote">
              {{ creating ? 'Creating...' : 'New Note' }}
            </button>
            <button class="btn-admin-outline md:!hidden !mt-0" @click="sidebarOpen = true">Menu</button>
          </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[360px_minmax(0,1fr)]">
          <section class="card space-y-4">
            <div class="admin-panel-header">
              <div>
                <h2 class="admin-panel-title">Notes</h2>
              </div>
            </div>

            <div class="flex gap-2">
              <input v-model="search" class="input w-full" placeholder="Search notes" @keyup.enter="loadNotes" />
              <button class="btn-admin-primary flex-shrink-0 !px-3" @click="loadNotes" aria-label="Search">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </button>
            </div>

            <div v-if="loading" class="text-sm text-slate-500">Loading notes...</div>
            <div v-else-if="!notes.length" class="rounded-md border border-dashed border-slate-300 px-4 py-6 text-sm text-slate-500">
              No notes found. Create one to start your knowledgebase.
            </div>
            <div v-else class="space-y-3">
              <button
                v-for="note in notes"
                :key="note.id"
                :class="[
                  'w-full rounded-md border px-4 py-4 text-left transition',
                  selectedId === note.id
                    ? 'border-blue-400 bg-blue-50 shadow-sm'
                    : 'border-slate-200 bg-white hover:border-blue-200 hover:bg-blue-50/40'
                ]"
                @click="selectNote(note)"
              >
                <div class="text-sm font-semibold text-slate-900">{{ note.title }}</div>
                <div class="mt-2 line-clamp-3 text-sm text-slate-600">{{ plainTextPreview(note.note) }}</div>
                <div class="mt-3 text-xs uppercase tracking-[0.18em] text-slate-400">
                  Updated {{ new Date(note.updated_at).toLocaleString() }}
                </div>
              </button>
            </div>
          </section>

          <section class="card space-y-4">


            <template v-if="selectedId">
              <div>
                <label class="label">Title</label>
                <input v-model="form.title" class="input" placeholder="Note title" />
              </div>

              <div>
                <label class="label">Note</label>
                <RichTextEditor
                  v-model="form.note"
                  :show-highlight="true"
                  :show-link="true"
                  placeholder="Write your knowledgebase note..."
                />
              </div>

              <div class="flex flex-wrap items-center justify-between gap-3">
                <button class="btn-admin-primary" :disabled="saving" @click="saveNote">
                  {{ saving ? 'Saving...' : 'Save Note' }}
                </button>
                <button class="btn-admin-outline !border-red-200 !text-red-600 hover:!bg-red-50" :disabled="deleting" @click="promptDeleteNote">
                  {{ deleting ? 'Deleting...' : 'Delete Note' }}
                </button>
              </div>

              <span v-if="saveMessage" class="text-sm font-medium text-emerald-700">{{ saveMessage }}</span>
              <span v-if="saveError" class="text-sm font-medium text-red-600">{{ saveError }}</span>
            </template>

            <div v-else class="rounded-md border border-dashed border-slate-300 px-4 py-10 text-sm text-slate-500">
              Choose a note from the list or create a new one to begin editing.
            </div>
          </section>
        </div>
      </div>
    </main>

    <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 backdrop-blur-sm p-4">
      <div class="bg-white rounded-xl shadow-xl max-w-sm w-full p-6 text-center">
        <h3 class="text-xl font-bold text-slate-900 mb-2">Delete Note</h3>
        <p class="text-sm text-slate-600 mb-6">Are you sure you want to delete this note? This cannot be undone.</p>
        <div class="flex gap-3 justify-center">
          <button class="btn-admin-outline flex-1" type="button" @click="showDeleteModal = false">Cancel</button>
          <button class="btn-admin-primary !bg-red-600 hover:!bg-red-700 !border-red-600 flex-1" type="button" @click="confirmDeleteNote">Yes, Delete</button>
        </div>
      </div>
    </div>
  </div>
</template>
