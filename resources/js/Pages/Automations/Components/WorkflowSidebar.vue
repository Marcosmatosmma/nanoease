<script setup>
import { Icon } from '@iconify/vue'
import Button from '@/Components/ui/button/Button.vue'
import HttpRequestConfig from './Nodes/HttpRequestConfig.vue'
import ExtractDataConfig from './Nodes/ExtractDataConfig.vue'

const props = defineProps({
  step: Object
})

const emit = defineEmits(['close', 'apply', 'clear-result'])

// Mapping config components to types
const configComponents = {
  'http_request': HttpRequestConfig,
  'extract_data': ExtractDataConfig,
  // Add others as they are migrated...
}
</script>

<template>
  <aside class="w-96 border-l bg-white flex flex-col shadow-xl z-20 h-full">
    <div v-if="step" class="flex flex-col h-full">
      <!-- Sidebar Header -->
      <div class="p-4 border-b flex items-center gap-2 bg-slate-50">
        <Icon :icon="step.icon" class="h-5 w-5 text-muted-foreground" />
        <h2 class="font-semibold">{{ step.title }}</h2>
        
        <!-- Status Badge -->
        <span v-if="step.testResult" 
          :class="[
            'ml-auto text-xs px-2 py-0.5 rounded-full border',
            step.testResult.status === 'success' ? 'bg-green-100 text-green-700 border-green-200' : 'bg-red-100 text-red-700 border-red-200'
          ]"
        >
          {{ step.testResult.status === 'success' ? 'Sucesso' : 'Erro' }}
        </span>
      </div>
      
      <!-- Config Content -->
      <div class="flex-1 overflow-y-auto p-6 space-y-6">
        
        <!-- Dynamic Config Component -->
        <component 
          v-if="configComponents[step.type]" 
          :is="configComponents[step.type]" 
          :config="step.config" 
        />
        
        <!-- Fallback for unmigrated types -->
        <!-- Link Contract Config (Inline for now until migrated) -->
        <div v-else-if="step.type === 'link_contract'" class="space-y-4">
           <div class="p-3 bg-blue-50 text-blue-700 rounded-md text-xs">
             Este passo irá buscar um contrato ativo e vincular os dados processados a ele.
           </div>
           <div class="space-y-2">
            <label class="text-sm font-medium">Buscar Contrato por</label>
            <select v-model="step.config.search_by" class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm">
              <option value="cpf_cnpj">CPF/CNPJ do Fornecedor</option>
              <option value="contract_id">ID do Contrato</option>
            </select>
          </div>
          <div class="space-y-2">
            <label class="text-sm font-medium">Variável com o Valor</label>
            <input v-model="step.config.value_variable" type="text" class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm" placeholder="ex: user_cpf" />
          </div>
        </div>

        <!-- Send Email Config (Inline for now) -->
        <div v-else-if="step.type === 'send_email'" class="space-y-4">
          <div class="space-y-2">
            <label class="text-sm font-medium">Para (Email)</label>
            <input v-model="step.config.to_email" type="email" class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm" placeholder="email@exemplo.com" />
          </div>
           <div class="space-y-2">
            <label class="text-sm font-medium">Assunto</label>
            <input v-model="step.config.subject" type="text" class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm" placeholder="Novo alerta" />
          </div>
           <div class="space-y-2">
            <label class="text-sm font-medium">Mensagem</label>
            <textarea v-model="step.config.message" rows="4" class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm" placeholder="Sua mensagem..."></textarea>
          </div>
        </div>

        <div v-else class="text-center py-10 text-muted-foreground">
          <Icon icon="lucide:settings" class="h-8 w-8 mx-auto mb-2 opacity-20" />
          <p>Configuração para {{ step.title }}</p>
          <p class="text-xs">Este tipo de nó ainda não tem formulário.</p>
        </div>


        <!-- Result Display (Bottom) -->
        <div v-if="step.testResult" class="mt-8 rounded-lg border bg-slate-50 p-3 text-sm">
          <div class="flex items-center justify-between mb-2">
            <span class="font-medium text-slate-700">Resultado da Execução:</span>
            <button @click="$emit('clear-result')" class="text-xs text-muted-foreground hover:text-slate-700">Limpar</button>
          </div>
          <pre class="whitespace-pre-wrap text-xs font-mono bg-white p-2 rounded border border-slate-200 overflow-x-auto max-h-60">{{ step.testResult.output }}</pre>
        </div>

      </div>

      <div class="p-4 border-t bg-slate-50">
        <Button class="w-full" @click="$emit('apply')">Aplicar Alterações</Button>
      </div>
    </div>
    
    <div v-else class="flex-1 flex flex-col items-center justify-center text-muted-foreground p-8 text-center">
      <Icon icon="lucide:mouse-pointer-click" class="h-10 w-10 mb-4 opacity-20" />
      <p>Selecione um passo para configurar</p>
    </div>
  </aside>
</template>
