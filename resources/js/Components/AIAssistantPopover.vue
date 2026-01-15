<!--
  Componente: AIAssistantPopover
  
  Menu popover com ações de IA para melhorar texto do editor.
  
  Features:
  - Melhorar texto (tom profissional)
  - Corrigir gramática e ortografia
  - Expandir conteúdo
  - Resumir texto
  - Feedback visual durante processamento
  
  Props:
  - editor (Object): Instância do editor Tiptap
  - disabled (Boolean): Desabilita botão
-->

<script setup>
import { Icon } from '@iconify/vue'
import { ref } from 'vue'
import Button from './ui/button/Button.vue'
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover'

const props = defineProps({
  editor: {
    type: Object,
    required: true,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
})

const isProcessing = ref(false)
const isOpen = ref(false)

// Token CSRF
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')

/**
 * Processa texto com IA
 * @param {string} action - Ação a executar (improve, correct, expand, summarize)
 */
const processWithAI = async (action) => {
  if (isProcessing.value || props.disabled) return

  const text = props.editor.getText()
  
  if (!text || text.trim().length === 0) {
    alert('Digite algum texto antes de usar a IA.')
    return
  }

  isProcessing.value = true
  isOpen.value = false

  try {
    const response = await fetch(route('api.ai.improve-text'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
      body: JSON.stringify({
        text,
        action,
      }),
    })

    if (!response.ok) {
      const error = await response.json()
      throw new Error(error.message || 'Erro ao processar texto')
    }

    const result = await response.json()
    
    // Substitui conteúdo do editor
    props.editor.commands.setContent(result.text)
  } catch (error) {
    console.error('Erro ao processar com IA:', error)
    alert(error.message || 'Não foi possível processar o texto. Tente novamente.')
  } finally {
    isProcessing.value = false
  }
}

// Ações disponíveis
const actions = [
  {
    key: 'improve',
    label: 'Melhorar texto',
    icon: 'lucide:wand-2',
    description: 'Torna o texto mais profissional',
  },
  {
    key: 'correct',
    label: 'Corrigir',
    icon: 'lucide:check-circle',
    description: 'Corrige gramática e ortografia',
  },
  {
    key: 'expand',
    label: 'Expandir',
    icon: 'lucide:maximize-2',
    description: 'Adiciona mais detalhes',
  },
  {
    key: 'summarize',
    label: 'Resumir',
    icon: 'lucide:minimize-2',
    description: 'Torna mais conciso',
  },
]
</script>

<template>
  <Popover v-model:open="isOpen">
    <PopoverTrigger as-child>
      <Button
        variant="ghost"
        size="sm"
        type="button"
        :disabled="disabled || isProcessing"
        class="gap-1.5"
      >
        <Icon
          :icon="isProcessing ? 'lucide:loader-2' : 'lucide:sparkles'"
          class="h-4 w-4"
          :class="{ 'animate-spin': isProcessing }"
        />
        <span class="text-xs">{{ isProcessing ? 'Processando...' : 'IA' }}</span>
      </Button>
    </PopoverTrigger>

    <PopoverContent class="w-64 p-2">
      <div class="space-y-1">
        <div class="px-2 py-1.5">
          <p class="text-sm font-semibold">Assistente de IA</p>
          <p class="text-xs text-muted-foreground">Melhore seu texto automaticamente</p>
        </div>

        <div class="space-y-0.5">
          <button
            v-for="action in actions"
            :key="action.key"
            type="button"
            class="flex w-full items-start gap-2 rounded-md px-2 py-2 text-sm transition hover:bg-muted"
            :disabled="isProcessing"
            @click="processWithAI(action.key)"
          >
            <Icon :icon="action.icon" class="mt-0.5 h-4 w-4 shrink-0 text-primary" />
            <div class="flex-1 text-left">
              <p class="font-medium">{{ action.label }}</p>
              <p class="text-xs text-muted-foreground">{{ action.description }}</p>
            </div>
          </button>
        </div>
      </div>
    </PopoverContent>
  </Popover>
</template>
