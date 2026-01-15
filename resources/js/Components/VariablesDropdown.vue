<!--
  Componente: VariablesDropdown
  
  Dropdown com variáveis dinâmicas que podem ser inseridas no texto.
  As variáveis são substituídas na hora da execução da automação.
  
  Variáveis disponíveis:
  - {from_name}: Nome do remetente
  - {from_email}: E-mail do remetente
  - {subject}: Assunto original
  - {date}: Data/hora atual
  - {body_preview}: Prévia do corpo (100 chars)
  
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

const isOpen = ref(false)

/**
 * Insere variável no cursor do editor
 * @param {string} variable - Variável a inserir (ex: '{from_name}')
 */
const insertVariable = (variable) => {
  if (props.disabled) return
  
  props.editor.chain().focus().insertContent(variable + ' ').run()
  isOpen.value = false
}

// Variáveis disponíveis
const variables = [
  {
    key: '{from_name}',
    label: 'Nome do remetente',
    icon: 'lucide:user',
    description: 'Ex: João Silva',
  },
  {
    key: '{from_email}',
    label: 'E-mail do remetente',
    icon: 'lucide:at-sign',
    description: 'Ex: joao@empresa.com',
  },
  {
    key: '{subject}',
    label: 'Assunto original',
    icon: 'lucide:mail',
    description: 'Assunto do e-mail recebido',
  },
  {
    key: '{date}',
    label: 'Data/hora',
    icon: 'lucide:calendar',
    description: 'Data e hora atual',
  },
  {
    key: '{body_preview}',
    label: 'Prévia do corpo',
    icon: 'lucide:file-text',
    description: 'Primeiras 100 caracteres',
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
        :disabled="disabled"
        class="gap-1.5"
      >
        <Icon icon="lucide:braces" class="h-4 w-4" />
        <span class="text-xs">Variáveis</span>
      </Button>
    </PopoverTrigger>

    <PopoverContent class="w-72 p-2">
      <div class="space-y-1">
        <div class="px-2 py-1.5">
          <p class="text-sm font-semibold">Variáveis dinâmicas</p>
          <p class="text-xs text-muted-foreground">
            Inserir dados do e-mail automaticamente
          </p>
        </div>

        <div class="space-y-0.5">
          <button
            v-for="variable in variables"
            :key="variable.key"
            type="button"
            class="flex w-full items-start gap-2 rounded-md px-2 py-2 text-sm transition hover:bg-muted"
            :disabled="disabled"
            @click="insertVariable(variable.key)"
          >
            <Icon :icon="variable.icon" class="mt-0.5 h-4 w-4 shrink-0 text-primary" />
            <div class="flex-1 text-left">
              <div class="flex items-center justify-between gap-2">
                <p class="font-medium">{{ variable.label }}</p>
                <code class="rounded bg-muted px-1 text-xs">{{ variable.key }}</code>
              </div>
              <p class="text-xs text-muted-foreground">{{ variable.description }}</p>
            </div>
          </button>
        </div>

        <div class="mt-2 rounded-md bg-muted/50 px-2 py-1.5">
          <p class="text-xs text-muted-foreground">
            💡 As variáveis serão substituídas pelos dados reais quando a automação executar.
          </p>
        </div>
      </div>
    </PopoverContent>
  </Popover>
</template>
