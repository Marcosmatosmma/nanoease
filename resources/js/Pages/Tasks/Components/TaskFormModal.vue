<template>
  <Dialog v-model:open="isOpen">
    <DialogContent class="sm:max-w-[1200px] max-h-[95vh] overflow-y-auto">
      <!-- Modo Edição: Modal completo estilo Trello -->
      <div v-if="task" class="space-y-4">
        <!-- Header com título editável -->
        <div class="flex items-start gap-3">
          <Icon icon="lucide:square-check" class="h-6 w-6 text-gray-500 mt-1" />
          <div class="flex-1">
            <Input
              v-model="form.title"
              class="text-xl font-semibold border-0 px-0 focus:ring-0 focus:border-b-2"
              @blur="saveTitle"
            />
            <div class="flex items-center gap-2 mt-1 flex-wrap">
              <p class="text-sm text-gray-500">
                em <strong>{{ boardListName }}</strong>
              </p>
              <span
                v-if="currentTask.due_date"
                class="text-xs px-2 py-0.5 rounded font-medium"
                :class="getDueDateBadgeClass(currentTask.due_date, currentTask.is_completed)"
              >
                {{ getDueDateLabel(currentTask.due_date, currentTask.is_completed) }}
              </span>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-4 gap-6">
          <!-- Coluna Principal (3/4) -->
          <div class="col-span-3 space-y-4">
            <!-- Aba Home -->
            <div v-if="activeTab === 'home'">
              <!-- Origem da Atividade -->
              <div>
                <div class="flex items-center gap-2 mb-2">
                  <Icon icon="lucide:workflow" class="h-5 w-5 text-gray-600" />
                  <h3 class="font-semibold text-sm">Origem do Cartão</h3>
                </div>
                <div class="flex items-center gap-2 text-sm flex-wrap">
                  <span
                    class="px-2 py-1 rounded text-xs font-medium"
                    :class="getSourceBadgeClass(currentTask.source)"
                  >
                    {{ getSourceLabel(currentTask.source) }}
                  </span>
                  <span v-if="currentTask.source_id" class="text-gray-500">
                    #{{ currentTask.source_id }}
                  </span>
                  
                  <!-- Etiquetas -->
                  <template v-if="currentLabels.length">
                    <span class="text-gray-400 mx-1">•</span>
                    <span
                      v-for="label in currentLabels"
                      :key="label.id"
                      class="text-xs px-2 py-1 rounded text-white"
                      :style="{ backgroundColor: label.color }"
                    >
                      {{ label.name }}
                    </span>
                  </template>
                </div>
              </div>

              <!-- Descrição -->
              <div class="mt-6">
                <div class="flex items-center gap-2 mb-2">
                  <Icon icon="lucide:align-left" class="h-5 w-5 text-gray-600" />
                  <h3 class="font-semibold text-sm">Descrição</h3>
                </div>
                <TipTapEditor
                  v-model="form.description"
                  placeholder="Adicione uma descrição mais detalhada..."
                  @blur="saveDescription"
                />
              </div>
            </div>

            <!-- Aba Tags -->
            <div v-if="activeTab === 'tags'">
              <div class="flex items-center gap-2 mb-4">
                <Icon icon="lucide:tag" class="h-5 w-5 text-gray-600" />
                <h3 class="font-semibold text-sm">Gerenciar Etiquetas</h3>
              </div>

              <!-- Lista de etiquetas atuais -->
              <div v-if="currentLabels.length" class="mb-6">
                <h4 class="text-xs font-semibold text-gray-500 uppercase mb-3">
                  Etiquetas do Cartão
                </h4>
                <div class="flex flex-wrap gap-2">
                  <button
                    v-for="label in currentLabels"
                    :key="label.id"
                    type="button"
                    class="text-sm px-3 py-2 rounded text-white hover:opacity-80 flex items-center gap-2 transition-opacity"
                    :style="{ backgroundColor: label.color }"
                    @click="removeLabel(label.id)"
                  >
                    {{ label.name }}
                    <Icon icon="lucide:x" class="h-4 w-4" />
                  </button>
                </div>
              </div>

              <!-- Adicionar nova etiqueta -->
              <div>
                <h4 class="text-xs font-semibold text-gray-500 uppercase mb-3">
                  Adicionar Etiqueta
                </h4>
                
                <div class="grid grid-cols-2 gap-3 mb-4">
                  <button
                    v-for="color in labelColors"
                    :key="color.value"
                    type="button"
                    class="text-left px-4 py-3 rounded text-white text-sm font-medium hover:opacity-80 transition-opacity"
                    :class="{ 'ring-2 ring-offset-2 ring-gray-800': selectedLabelColor === color.value }"
                    :style="{ backgroundColor: color.value }"
                    @click="selectLabelColor(color.value)"
                  >
                    {{ color.name }}
                  </button>
                </div>

                <!-- Formulário de nova etiqueta -->
                <div v-if="selectedLabelColor" class="border-t pt-4 space-y-3">
                  <Input
                    v-model="newLabelName"
                    placeholder="Nome da etiqueta"
                    class="text-sm"
                    @keyup.enter="createLabel"
                  />
                  <div class="flex gap-2">
                    <Button
                      type="button"
                      size="sm"
                      class="flex-1"
                      @click="createLabel"
                    >
                      <Icon icon="lucide:plus" class="h-4 w-4 mr-2" />
                      Criar Etiqueta
                    </Button>
                    <Button
                      type="button"
                      variant="outline"
                      size="sm"
                      @click="cancelLabelCreation"
                    >
                      Cancelar
                    </Button>
                  </div>
                </div>
              </div>
            </div>

              <!-- Atividades -->
              <div v-if="activeTab === 'activity'">
                <div class="flex items-center gap-2 mb-4">
                  <Icon icon="lucide:activity" class="h-5 w-5 text-gray-600" />
                  <h3 class="font-semibold text-sm">Histórico de Atividades</h3>
                </div>
                
                <div class="space-y-4">
                  <!-- Criação -->
                  <div class="flex gap-3">
                    <div class="flex-shrink-0">
                      <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                        <Icon icon="lucide:user" class="h-4 w-4 text-blue-600 dark:text-blue-300" />
                      </div>
                    </div>
                    <div class="flex-1">
                      <p class="text-sm">
                        <span class="font-medium">{{ currentTask.user?.name }}</span>
                        <span class="text-gray-500"> criou esta tarefa</span>
                      </p>
                      <p class="text-xs text-gray-400 mt-1">
                        {{ formatDateTime(currentTask.created_at) }}
                      </p>
                    </div>
                  </div>

                  <!-- Conclusão -->
                  <div v-if="currentTask.completed_at" class="flex gap-3">
                    <div class="flex-shrink-0">
                      <div class="w-8 h-8 rounded-full bg-green-100 dark:bg-green-900 flex items-center justify-center">
                        <Icon icon="lucide:check-circle" class="h-4 w-4 text-green-600 dark:text-green-300" />
                      </div>
                    </div>
                    <div class="flex-1">
                      <p class="text-sm text-gray-500">Tarefa concluída</p>
                      <p class="text-xs text-gray-400 mt-1">
                        {{ formatDateTime(currentTask.completed_at) }}
                      </p>
                    </div>
                  </div>
                </div>
              </div>
          </div>

          <!-- Sidebar (1/4) -->
          <div class="space-y-4">
            <!-- Navegação de Abas -->
            <div>
              <h4 class="text-xs font-semibold text-gray-500 uppercase mb-2">
                Navegação
              </h4>
              <div class="space-y-1">
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  class="w-full justify-start text-xs"
                  :class="{ 'bg-gray-100 dark:bg-gray-800': activeTab === 'home' }"
                  @click="activeTab = 'home'"
                >
                  <Icon icon="lucide:home" class="h-3 w-3 mr-2" />
                  Home
                </Button>
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  class="w-full justify-start text-xs"
                  :class="{ 'bg-gray-100 dark:bg-gray-800': activeTab === 'tags' }"
                  @click="activeTab = 'tags'"
                >
                  <Icon icon="lucide:tag" class="h-3 w-3 mr-2" />
                  Etiquetas
                </Button>
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  class="w-full justify-start text-xs"
                  :class="{ 'bg-gray-100 dark:bg-gray-800': activeTab === 'activity' }"
                  @click="activeTab = 'activity'"
                >
                  <Icon icon="lucide:activity" class="h-3 w-3 mr-2" />
                  Atividades
                </Button>
              </div>
            </div>

            <div class="border-t pt-4">
              <h4 class="text-xs font-semibold text-gray-500 uppercase mb-2">
                Detalhes do Cartão
              </h4>

              <!-- Mover para lista -->
              <div class="mb-3">
                <Label class="text-xs mb-1">Lista</Label>
                <select
                  v-model="form.board_list_id"
                  class="w-full rounded-md border border-gray-300 dark:border-gray-600 px-2 py-1.5 text-sm bg-white dark:bg-gray-800"
                  @change="saveList"
                >
                  <option
                    v-for="list in boardLists"
                    :key="list.id"
                    :value="list.id"
                  >
                    {{ list.name }}
                  </option>
                </select>
              </div>

              <!-- Responsável -->
              <div class="mb-3">
                <Label class="text-xs mb-1">Responsável</Label>
                <select
                  v-model="form.assigned_to"
                  class="w-full rounded-md border border-gray-300 dark:border-gray-600 px-2 py-1.5 text-sm bg-white dark:bg-gray-800"
                  @change="saveAssigned"
                >
                  <option :value="null">Ninguém</option>
                  <option
                    v-for="user in teamUsers"
                    :key="user.id"
                    :value="user.id"
                  >
                    {{ user.name }}
                  </option>
                </select>
              </div>

              <!-- Data de vencimento -->
              <div class="mb-3">
                <Label class="text-xs mb-1">Vencimento</Label>
                <Input
                  v-model="form.due_date"
                  type="date"
                  class="text-sm"
                  @change="saveDueDate"
                />
              </div>
            </div>

            <!-- Ações -->
            <div class="pt-4 border-t">
              <h4 class="text-xs font-semibold text-gray-500 uppercase mb-2">
                Ações
              </h4>
              <Button
                v-if="!currentTask.is_completed"
                variant="outline"
                size="sm"
                class="w-full justify-start text-xs mb-2"
                @click="markAsCompleted"
              >
                <Icon icon="lucide:check" class="h-3 w-3 mr-2" />
                Marcar como concluída
              </Button>
              <Button
                v-else
                variant="outline"
                size="sm"
                class="w-full justify-start text-xs mb-2"
                @click="markAsIncomplete"
              >
                <Icon icon="lucide:x" class="h-3 w-3 mr-2" />
                Marcar como pendente
              </Button>
              <Button
                variant="outline"
                size="sm"
                class="w-full justify-start text-xs text-orange-600 hover:text-orange-700 mb-2"
                @click="archiveTask"
              >
                <Icon icon="lucide:archive" class="h-3 w-3 mr-2" />
                Arquivar tarefa
              </Button>
              <Button
                variant="outline"
                size="sm"
                class="w-full justify-start text-xs text-red-600 hover:text-red-700"
                @click="deleteTask"
              >
                <Icon icon="lucide:trash-2" class="h-3 w-3 mr-2" />
                Excluir tarefa
              </Button>
            </div>
          </div>
        </div>
      </div>

      <!-- Modo Criação: Modal simples -->
      <div v-else>
        <DialogHeader>
          <DialogTitle>Nova Tarefa</DialogTitle>
        </DialogHeader>

        <form @submit.prevent="handleCreate" class="space-y-4 mt-4">
          <div>
            <Label for="title">Título *</Label>
            <Input
              id="title"
              v-model="form.title"
              placeholder="Digite o título da tarefa"
              required
            />
          </div>

          <div>
            <Label for="description">Descrição</Label>
            <TipTapEditor
              id="description"
              v-model="form.description"
              placeholder="Adicione mais detalhes..."
            />
          </div>

          <div>
            <Label for="due_date">Data de vencimento</Label>
            <Input
              id="due_date"
              v-model="form.due_date"
              type="date"
            />
          </div>

          <div v-if="teamUsers.length > 0">
            <Label for="assigned_to">Responsável</Label>
            <select
              id="assigned_to"
              v-model="form.assigned_to"
              class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
            >
              <option :value="null">Ninguém</option>
              <option
                v-for="user in teamUsers"
                :key="user.id"
                :value="user.id"
              >
                {{ user.name }}
              </option>
            </select>
          </div>

          <DialogFooter>
            <Button type="button" variant="outline" @click="closeModal">
              Cancelar
            </Button>
            <Button type="submit" :disabled="form.processing">
              Criar
            </Button>
          </DialogFooter>
        </form>
      </div>
    </DialogContent>
  </Dialog>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { useForm, router, usePage } from '@inertiajs/vue3'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/Components/ui/dialog'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Label } from '@/Components/ui/label'
import { Icon } from '@iconify/vue'
import TipTapEditor from '@/Components/TipTapEditor.vue'

const props = defineProps({
  open: Boolean,
  task: {
    type: Object,
    default: null,
  },
  boardList: {
    type: Object,
    default: null,
  },
  boardLists: {
    type: Array,
    default: () => [],
  },
  teamUsers: {
    type: Array,
    default: () => [],
  },
})

const emit = defineEmits(['update:open', 'saved'])

const page = usePage()

const currentTask = computed(() => {
  if (!props.task?.id) return props.task
  
  const boards = page.props.boards || []
  for (const board of boards) {
    for (const list of board.lists || []) {
      const task = (list.tasks || []).find(t => t.id === props.task.id)
      if (task) return task
    }
  }
  return props.task
})

const currentLabels = computed(() => currentTask.value?.labels || [])

const isOpen = computed({
  get: () => props.open,
  set: (value) => emit('update:open', value),
})

const boardListName = computed(() => {
  // Tenta pegar da task atual primeiro
  if (currentTask.value?.board_list) {
    return currentTask.value.board_list.name
  }
  
  // Se não tiver, busca nas listas usando board_list_id
  if (currentTask.value?.board_list_id && props.boardLists) {
    const list = props.boardLists.find(l => l.id === currentTask.value.board_list_id)
    if (list) return list.name
  }
  
  // Fallback para boardList passado como prop
  return props.boardList?.name || ''
})

const form = useForm({
  title: '',
  description: '',
  due_date: '',
  assigned_to: null,
  is_completed: false,
  board_list_id: null,
})

const activeTab = ref('home')
const selectedLabelColor = ref(null)
const newLabelName = ref('')

const labelColors = [
  { name: 'Verde', value: '#22c55e' },
  { name: 'Amarelo', value: '#eab308' },
  { name: 'Laranja', value: '#f97316' },
  { name: 'Vermelho', value: '#ef4444' },
  { name: 'Roxo', value: '#a855f7' },
  { name: 'Azul', value: '#3b82f6' },
  { name: 'Rosa', value: '#ec4899' },
  { name: 'Cinza', value: '#6b7280' },
]

watch(() => props.task, (task) => {
  if (task) {
    form.title = task.title || ''
    form.description = task.description || ''
    form.due_date = formatDateForInput(task.due_date)
    form.assigned_to = task.assigned_to || null
    form.is_completed = task.is_completed || false
    form.board_list_id = task.board_list_id || null
    activeTab.value = 'home'
  } else {
    form.reset()
  }
}, { immediate: true })

// Watch para atualizar form quando currentTask mudar (após reload)
watch(() => currentTask.value, (task) => {
  if (task && props.task) {
    form.due_date = formatDateForInput(task.due_date)
    form.assigned_to = task.assigned_to || null
    form.is_completed = task.is_completed || false
    form.board_list_id = task.board_list_id || null
  }
}, { deep: true })

// Formata data para o input type="date" (YYYY-MM-DD)
function formatDateForInput(date) {
  if (!date) return ''
  
  // Se já estiver no formato correto, retorna
  if (typeof date === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(date)) {
    return date
  }
  
  // Converte para Date e formata
  const d = new Date(date)
  if (isNaN(d.getTime())) return ''
  
  const year = d.getFullYear()
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  
  return `${year}-${month}-${day}`
}

function handleCreate() {
  form.post(route('tasks.store', { boardList: props.boardList.id }), {
    preserveScroll: true,
    onSuccess: () => {
      emit('saved')
      closeModal()
    },
  })
}

function saveTitle() {
  updateTask({ title: form.title })
}

function saveDescription() {
  updateTask({ description: form.description })
}

function saveDueDate() {
  updateTask({ due_date: form.due_date })
}

function saveAssigned() {
  updateTask({ assigned_to: form.assigned_to })
}

function saveList() {
  updateTask({ board_list_id: form.board_list_id })
}

function selectLabelColor(color) {
  selectedLabelColor.value = color
}

function cancelLabelCreation() {
  selectedLabelColor.value = null
  newLabelName.value = ''
}

function createLabel() {
  if (!newLabelName.value || !selectedLabelColor.value) return

  router.post(route('tasks.labels.store', { task: props.task.id }), {
    name: newLabelName.value,
    color: selectedLabelColor.value,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      router.reload({ only: ['boards'] })
      cancelLabelCreation()
    },
  })
}

function removeLabel(labelId) {
  router.delete(route('tasks.labels.destroy', { task: props.task.id, label: labelId }), {
    preserveScroll: true,
    onSuccess: () => {
      router.reload({ only: ['boards'] })
    },
  })
}

function markAsCompleted() {
  updateTask({ is_completed: true })
}

function markAsIncomplete() {
  updateTask({ is_completed: false })
}

function updateTask(data) {
  router.put(route('tasks.update', { task: props.task.id }), data, {
    preserveScroll: true,
    onSuccess: () => emit('saved'),
  })
}

function deleteTask() {
  if (!confirm('Deseja realmente deletar esta tarefa?')) return

  router.delete(route('tasks.destroy', { task: props.task.id }), {
    preserveScroll: true,
    onSuccess: () => closeModal(),
  })
}

function archiveTask() {
  if (!confirm('Deseja arquivar esta tarefa?')) return

  router.post(route('tasks.archive', { task: props.task.id }), {}, {
    preserveScroll: true,
    onSuccess: () => {
      closeModal()
      router.reload({ only: ['boards'] })
    },
  })
}

function closeModal() {
  isOpen.value = false
  form.reset()
}

function getSourceLabel(source) {
  const labels = {
    manual: 'Criação Manual',
    automation: 'Automação',
    form: 'Formulário Externo',
    system: 'Sistema',
  }
  return labels[source] || source
}

function getSourceBadgeClass(source) {
  const classes = {
    manual: 'bg-gray-100 text-gray-700',
    automation: 'bg-blue-100 text-blue-700',
    form: 'bg-purple-100 text-purple-700',
    system: 'bg-green-100 text-green-700',
  }
  return classes[source] || 'bg-gray-100 text-gray-700'
}

function getDueDateLabel(dueDate, isCompleted) {
  if (isCompleted) return 'Concluída'
  
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  const due = new Date(dueDate)
  due.setHours(0, 0, 0, 0)
  
  if (due < today) return 'Atrasada'
  if (due.getTime() === today.getTime()) return 'Vence hoje'
  return 'No prazo'
}

function getDueDateBadgeClass(dueDate, isCompleted) {
  if (isCompleted) return 'bg-green-100 text-green-700'
  
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  const due = new Date(dueDate)
  due.setHours(0, 0, 0, 0)
  
  if (due < today) return 'bg-red-100 text-red-700'
  if (due.getTime() === today.getTime()) return 'bg-yellow-100 text-yellow-700'
  return 'bg-blue-100 text-blue-700'
}

function formatDateTime(dateTime) {
  return new Date(dateTime).toLocaleString('pt-BR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}
</script>
