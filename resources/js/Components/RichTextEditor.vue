<!--
  Componente: RichTextEditor
  
  Editor de texto rico baseado em Tiptap para criação de respostas automáticas.
  
  Features:
  - Formatação de texto (bold, italic, etc)
  - Links clicáveis
  - Listas com bullets
  - Assistente de IA integrado
  - Inserção de variáveis dinâmicas
  
  Props:
  - modelValue (String): Conteúdo HTML do editor
  - placeholder (String): Texto de placeholder
  - disabled (Boolean): Desabilita edição
  
  Emits:
  - update:modelValue: Emitido quando conteúdo muda
-->

<script setup>
import { Icon } from '@iconify/vue'
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Link from '@tiptap/extension-link'
import Placeholder from '@tiptap/extension-placeholder'
import { watch, onBeforeUnmount } from 'vue'
import AIAssistantPopover from './AIAssistantPopover.vue'
import VariablesDropdown from './VariablesDropdown.vue'
import Button from './ui/button/Button.vue'

const props = defineProps({
  modelValue: {
    type: String,
    default: '',
  },
  placeholder: {
    type: String,
    default: 'Digite sua mensagem...',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue'])

// Inicializa editor Tiptap
const editor = useEditor({
  extensions: [
    StarterKit,
    Link.configure({
      openOnClick: false,
      HTMLAttributes: {
        class: 'text-primary underline',
      },
    }),
    Placeholder.configure({
      placeholder: props.placeholder,
    }),
  ],
  content: props.modelValue,
  editable: !props.disabled,
  onUpdate: ({ editor }) => {
    emit('update:modelValue', editor.getHTML())
  },
})

// Sincroniza props com editor
watch(() => props.modelValue, (value) => {
  if (editor.value && editor.value.getHTML() !== value) {
    editor.value.commands.setContent(value, false)
  }
})

watch(() => props.disabled, (disabled) => {
  if (editor.value) {
    editor.value.setEditable(!disabled)
  }
})

// Cleanup ao destruir componente
onBeforeUnmount(() => {
  if (editor.value) {
    editor.value.destroy()
  }
})

// Ações do toolbar
const toggleBold = () => editor.value.chain().focus().toggleBold().run()
const toggleItalic = () => editor.value.chain().focus().toggleItalic().run()
const toggleBulletList = () => editor.value.chain().focus().toggleBulletList().run()
const toggleOrderedList = () => editor.value.chain().focus().toggleOrderedList().run()

const setLink = () => {
  const url = window.prompt('URL:')
  if (url) {
    editor.value.chain().focus().setLink({ href: url }).run()
  }
}

const removeLink = () => {
  editor.value.chain().focus().unsetLink().run()
}
</script>

<template>
  <div class="rounded-lg border border-input bg-background">
    <!-- Toolbar -->
    <div class="flex flex-wrap items-center gap-1 border-b border-border p-2">
      <!-- Formatação básica -->
      <div class="flex items-center gap-0.5 border-r border-border pr-2">
        <Button
          variant="ghost"
          size="sm"
          type="button"
          :class="{ 'bg-muted': editor?.isActive('bold') }"
          :disabled="disabled"
          @click="toggleBold"
        >
          <Icon icon="lucide:bold" class="h-4 w-4" />
        </Button>
        
        <Button
          variant="ghost"
          size="sm"
          type="button"
          :class="{ 'bg-muted': editor?.isActive('italic') }"
          :disabled="disabled"
          @click="toggleItalic"
        >
          <Icon icon="lucide:italic" class="h-4 w-4" />
        </Button>
      </div>

      <!-- Listas -->
      <div class="flex items-center gap-0.5 border-r border-border pr-2">
        <Button
          variant="ghost"
          size="sm"
          type="button"
          :class="{ 'bg-muted': editor?.isActive('bulletList') }"
          :disabled="disabled"
          @click="toggleBulletList"
        >
          <Icon icon="lucide:list" class="h-4 w-4" />
        </Button>
        
        <Button
          variant="ghost"
          size="sm"
          type="button"
          :class="{ 'bg-muted': editor?.isActive('orderedList') }"
          :disabled="disabled"
          @click="toggleOrderedList"
        >
          <Icon icon="lucide:list-ordered" class="h-4 w-4" />
        </Button>
      </div>

      <!-- Links -->
      <div class="flex items-center gap-0.5 border-r border-border pr-2">
        <Button
          v-if="!editor?.isActive('link')"
          variant="ghost"
          size="sm"
          type="button"
          :disabled="disabled"
          @click="setLink"
        >
          <Icon icon="lucide:link" class="h-4 w-4" />
        </Button>
        
        <Button
          v-else
          variant="ghost"
          size="sm"
          type="button"
          :disabled="disabled"
          @click="removeLink"
        >
          <Icon icon="lucide:unlink" class="h-4 w-4" />
        </Button>
      </div>

      <!-- Assistente de IA -->
      <AIAssistantPopover v-if="editor" :editor="editor" :disabled="disabled" />

      <!-- Variáveis dinâmicas -->
      <VariablesDropdown v-if="editor" :editor="editor" :disabled="disabled" />
    </div>

    <!-- Editor de conteúdo -->
    <EditorContent
      :editor="editor"
      class="prose prose-sm max-w-none p-4 focus:outline-none"
      :class="{ 'opacity-50': disabled }"
    />
  </div>
</template>

<style>
/* Estilos do editor Tiptap */
.ProseMirror {
  min-height: 150px;
  outline: none;
}

.ProseMirror p.is-editor-empty:first-child::before {
  content: attr(data-placeholder);
  float: left;
  color: hsl(var(--muted-foreground));
  pointer-events: none;
  height: 0;
}

.ProseMirror ul,
.ProseMirror ol {
  padding: 0 1.5rem;
  margin: 0.5rem 0;
}

.ProseMirror ul li,
.ProseMirror ol li {
  margin: 0.25rem 0;
}

.ProseMirror a {
  color: hsl(var(--primary));
  text-decoration: underline;
  cursor: pointer;
}

.ProseMirror strong {
  font-weight: 600;
}

.ProseMirror em {
  font-style: italic;
}
</style>
