<script setup>
import { ref, computed } from 'vue'
import { Icon } from '@iconify/vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import { Link } from '@inertiajs/vue3'
import axios from 'axios'

// Components
import StepCard from './Components/StepCard.vue'
import WorkflowSidebar from './Components/WorkflowSidebar.vue'

// --- Mock Initial Data ---
const steps = ref([
  {
    id: 'step_1',
    type: 'http_request',
    title: 'Requisição HTTP',
    icon: 'lucide:globe',
    config: {
      url: 'https://viacep.com.br/ws/74955520/json/',
      method: 'GET',
      headers: [],
      body: ''
    }
  }
])

const availableNodes = [
  { type: 'http_request', title: 'Requisição HTTP', icon: 'lucide:globe', description: 'Chamada API externa' },
  { type: 'extract_data', title: 'Extrair Dados', icon: 'lucide:code', description: 'Capturar valor de JSON' },
  { type: 'save_json', title: 'Salvar JSON', icon: 'lucide:database', description: 'Salvar dados no banco' },
  { type: 'link_contract', title: 'Vincular Contrato', icon: 'lucide:file-text', description: 'Associar a um contrato' },
  { type: 'logic', title: 'Lógica', icon: 'lucide:git-branch', description: 'Condições If/Else' },
  { type: 'create_task', title: 'Criar Tarefa', icon: 'lucide:check-square', description: 'Nova tarefa no Kanban' },
  { type: 'send_email', title: 'Enviar Email', icon: 'lucide:mail', description: 'Disparar notificação' },
]

const selectedStepId = ref(null)
const isNodeSelectorOpen = ref(false)
const insertIndex = ref(null)

const selectStep = (step) => {
  selectedStepId.value = step.id
}

const openNodeSelector = (index) => {
  insertIndex.value = index
  isNodeSelectorOpen.value = true
}

const addNode = (nodeType) => {
  const newNode = {
    id: `step_${Date.now()}`,
    type: nodeType.type,
    title: nodeType.title,
    icon: nodeType.icon,
    config: {
        // Init default configs if needed
        headers: [] 
    }
  }

  // Insert at specific index or push
  if (insertIndex.value !== null) {
    steps.value.splice(insertIndex.value + 1, 0, newNode)
  } else {
    steps.value.push(newNode)
  }
  
  isNodeSelectorOpen.value = false
  insertIndex.value = null
  selectedStepId.value = newNode.id
}

const deleteStep = (index) => {
  steps.value.splice(index, 1)
  selectedStepId.value = null
}

// --- Configuration Logic ---
const currentStep = computed(() => steps.value.find(s => s.id === selectedStepId.value))

const isTesting = ref(false)

const testWorkflow = async () => {
  isTesting.value = true
  
  // Clear previous results
  steps.value.forEach(s => s.testResult = null)

  try {
    const { data } = await axios.post(route('api.automations.workflow.test'), {
      steps: steps.value
    })
    
    // Assign results to steps
    if (data.logs && Array.isArray(data.logs)) {
      data.logs.forEach(log => {
        const step = steps.value.find(s => s.id === log.step_id)
        if (step) {
          step.testResult = log
        }
      })
    }
    
    // If the currently selected step has a result, we might want to ensure it updates in usage.
    // Vue reactivity handles checking currentStep.testResult.

  } catch (error) {
    alert('Erro ao executar fluxo: ' + (error.response?.data?.error || error.message))
  } finally {
    isTesting.value = false
  }
}

const applyChanges = () => {
    // Just a placeholder, as v-model updates directly.
    // Can be used for "Save Draft" toaster.
    console.log("Applied", currentStep.value)
}

const clearResult = () => {
    if (currentStep.value) {
        currentStep.value.testResult = null
    }
}
</script>

<template>
  <AppLayout title="Editor de Workflow">
    <div class="h-[calc(100vh-64px)] flex flex-col">
      
      <!-- Header -->
      <header class="border-b bg-background px-6 py-3 flex items-center justify-between">
        <div class="flex items-center gap-4">
          <Button :as="Link" :href="route('automations.index')" variant="ghost" size="icon">
            <Icon icon="lucide:arrow-left" class="h-5 w-5" />
          </Button>
          <div class="flex flex-col">
            <h1 class="font-semibold text-lg leading-tight">Nova Automação</h1>
            <span class="text-xs text-muted-foreground">Workflow Builder</span>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <Button variant="outline" @click="testWorkflow" :disabled="isTesting">
             <Icon v-if="isTesting" icon="lucide:loader-2" class="mr-2 h-4 w-4 animate-spin" />
             <Icon v-else icon="lucide:play" class="mr-2 h-4 w-4" />
             Testar Fluxo
          </Button>
          <Button variant="outline">Salvar Rascunho</Button>
          <Button>Ativar Automação</Button>
        </div>
      </header>

      <div class="flex-1 flex overflow-hidden">
        
        <!-- Canvas / Flow Area -->
        <main class="flex-1 bg-slate-50 relative overflow-y-auto p-8 flex flex-col items-center">
          
          <div class="w-full max-w-md flex flex-col items-center gap-4 z-10">
            
            <!-- Start Point -->
            <div class="flex flex-col items-center">
              <div class="w-12 h-12 rounded-full bg-green-100 text-green-600 flex items-center justify-center border-2 border-green-200 shadow-sm mb-2">
                <Icon icon="lucide:play" class="h-5 w-5 fill-current" />
              </div>
              <span class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Início</span>
            </div>

            <!-- Steps Loop -->
            <div v-for="(step, index) in steps" :key="step.id" class="flex flex-col items-center w-full group relative">
              
              <!-- Connection Line Upper -->
              <div class="h-6 w-0.5 bg-border"></div>

              <!-- Step Card Component -->
              <StepCard 
                :step="step" 
                :index="index"
                :is-selected="selectedStepId === step.id"
                @select="selectStep"
                @delete="deleteStep"
                @test="testWorkflow"
              />

              <!-- Add Button (After each step) -->
              <div class="flex flex-col items-center mt-2 group/add relative">
                 <div class="h-4 w-0.5 bg-border group-hover/add:bg-primary/50 transition-colors"></div>
                 <button 
                  @click="openNodeSelector(index)"
                  class="w-6 h-6 rounded-full bg-white border border-dashed border-slate-300 flex items-center justify-center text-slate-400 hover:border-primary hover:text-primary hover:bg-primary/5 transition-all shadow-sm z-20"
                >
                  <Icon icon="lucide:plus" class="h-3 w-3" />
                </button>
              </div>

            </div>

             <!-- End Point -->
            <div class="flex flex-col items-center mt-4 opacity-50">
               <span class="text-xs text-muted-foreground">Fim do fluxo</span>
            </div>

          </div>

          <!-- Node Selector Modal/Popover -->
          <div v-if="isNodeSelectorOpen" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" @click.self="isNodeSelectorOpen = false">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-lg overflow-hidden">
               <div class="p-4 border-b flex items-center justify-between bg-slate-50">
                 <h2 class="font-semibold">Adicionar Passo</h2>
                 <button @click="isNodeSelectorOpen = false" class="text-muted-foreground hover:text-foreground">
                   <Icon icon="lucide:x" class="h-5 w-5" />
                 </button>
               </div>
               <div class="p-4 grid grid-cols-2 gap-3 max-h-[60vh] overflow-y-auto">
                 <button 
                    v-for="node in availableNodes" 
                    :key="node.type"
                    @click="addNode(node)"
                    class="flex flex-col items-start p-3 rounded-lg border hover:border-primary hover:bg-primary/5 transition-all text-left"
                  >
                    <div class="flex items-center gap-2 mb-1">
                      <Icon :icon="node.icon" class="h-4 w-4 text-primary" />
                      <span class="font-medium text-sm">{{ node.title }}</span>
                    </div>
                    <span class="text-xs text-muted-foreground">{{ node.description }}</span>
                 </button>
               </div>
            </div>
          </div>

        </main>

        <!-- Configuration Sidebar Component -->
        <WorkflowSidebar 
            :step="currentStep" 
            @apply="applyChanges" 
            @clear-result="clearResult" 
        />

      </div>
    </div>
  </AppLayout>
</template>
