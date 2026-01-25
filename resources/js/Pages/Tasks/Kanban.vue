<template>
  <AppLayout title="Tarefas">
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
          Tarefas
        </h2>
      </div>
    </template>

    <div class="h-[calc(100vh-120px)] overflow-x-auto overflow-y-hidden p-6">
      <div v-if="boards.length > 0" class="inline-flex gap-4 h-full">
        <!-- Kanban Board -->
        <Draggable
          v-model="boardLists"
          item-key="id"
          class="flex gap-4"
          :animation="200"
          handle=".list-handle"
          @end="onListsReordered"
        >
          <template #item="{ element: list, index: listIndex }">
            <div class="flex-shrink-0 w-80 flex flex-col">
              <!-- Header da Lista -->
              <div class="flex items-center justify-between mb-3 px-2">
                <div class="flex items-center gap-2 flex-1">
                  <!-- Handle para arrastar lista -->
                  <Icon
                    icon="lucide:grip-vertical"
                    class="h-4 w-4 text-gray-400 cursor-move list-handle"
                  />
                  <!-- Modo de edição -->
                  <Input
                    v-if="editingListId === list.id"
                    v-model="editingListName"
                    class="text-sm font-semibold h-7"
                    @keyup.enter="saveListName(list)"
                    @keyup.esc="cancelEditingList"
                    @blur="saveListName(list)"
                    autofocus
                  />
                  <!-- Modo de visualização -->
                  <span
                    v-else
                    class="font-semibold text-sm text-gray-700 dark:text-gray-300 cursor-pointer hover:text-gray-900 dark:hover:text-gray-100"
                    @click="startEditingList(list)"
                    :title="'Clique para editar'"
                  >
                    {{ list.name }}
                  </span>
                  <span class="text-xs text-gray-500 bg-gray-200 dark:bg-gray-700 px-2 py-0.5 rounded-full">
                    {{ list.tasks?.length || 0 }}
                  </span>
                </div>
                <Button
                  variant="ghost"
                  size="sm"
                  @click="openCreateTask(list)"
                >
                  <Icon icon="lucide:plus" class="h-4 w-4" />
                </Button>
              </div>

              <!-- Cards Container -->
              <div class="flex-1 bg-gray-100 dark:bg-gray-900 rounded-lg p-3 overflow-y-auto">
                <div class="space-y-2 min-h-[100px]">
                  <Draggable
                    :list="list.tasks"
                    item-key="id"
                    :group="{ name: 'tasks' }"
                    class="space-y-2 min-h-[50px]"
                    :animation="200"
                    ghost-class="opacity-50"
                    drag-class="cursor-grabbing"
                    @start="onDragStart"
                    @end="onDragEnd"
                    @change="(event) => onTaskChange(event, list.id)"
                  >
                    <template #item="{ element: task }">
                      <div
                        class="bg-white dark:bg-gray-800 rounded-lg p-3 shadow-sm hover:shadow-md transition-shadow cursor-grab active:cursor-grabbing border border-gray-200 dark:border-gray-700"
                        @click.prevent="!isDragging && openTaskDetails(task)"
                      >
                        <!-- Título -->
                        <h5 class="font-medium text-sm text-gray-900 dark:text-gray-100 mb-2">
                          {{ task.title }}
                        </h5>

                        <!-- Labels -->
                        <div v-if="task.labels?.length" class="flex flex-wrap gap-1 mb-2">
                          <span
                            v-for="label in task.labels"
                            :key="label.id"
                            class="text-xs px-2 py-0.5 rounded text-white"
                            :style="{ backgroundColor: label.color }"
                          >
                            {{ label.name }}
                          </span>
                        </div>

                        <!-- Footer -->
                        <div class="flex items-center justify-between text-xs text-gray-500">
                          <!-- Data de vencimento -->
                          <div v-if="task.due_date" class="flex items-center gap-1">
                            <Icon icon="lucide:calendar" class="h-3 w-3" />
                            <span>{{ formatDate(task.due_date) }}</span>
                          </div>

                          <!-- Responsável -->
                          <div v-if="task.assigned_user" class="flex items-center gap-1">
                            <Icon icon="lucide:user" class="h-3 w-3" />
                            <span class="text-xs">{{ task.assigned_user.name }}</span>
                          </div>
                        </div>
                      </div>
                    </template>
                  </Draggable>

                  <!-- Empty State -->
                  <div
                    v-if="!list.tasks || list.tasks.length === 0"
                    class="text-center py-8 text-sm text-gray-400"
                  >
                    <Icon icon="lucide:inbox" class="h-8 w-8 mx-auto mb-2 opacity-50" />
                    <p>Nenhuma tarefa</p>
                  </div>
                </div>

                <!-- Botão Adicionar -->
                <Button
                  variant="ghost"
                  size="sm"
                  class="w-full justify-start text-gray-600 hover:bg-gray-200 dark:hover:bg-gray-800 mt-2"
                  @click="openCreateTask(list)"
                >
                  <Icon icon="lucide:plus" class="h-4 w-4 mr-2" />
                  Adicionar tarefa
                </Button>
              </div>
            </div>
          </template>
        </Draggable>

        <!-- Adicionar Nova Lista -->
        <div class="flex-shrink-0 w-80 flex flex-col mr-24">
          <!-- Modo de criação -->
          <div v-if="creatingList" class="bg-gray-100 dark:bg-gray-900 rounded-lg p-3">
            <Input
              v-model="newListName"
              placeholder="Nome da lista..."
              class="text-sm mb-2"
              @keyup.enter="saveNewList"
              @keyup.esc="cancelCreatingList"
              autofocus
            />
            <div class="flex gap-2">
              <Button
                size="sm"
                @click="saveNewList"
              >
                Adicionar
              </Button>
              <Button
                variant="ghost"
                size="sm"
                @click="cancelCreatingList"
              >
                <Icon icon="lucide:x" class="h-4 w-4" />
              </Button>
            </div>
          </div>
          
          <!-- Botão para iniciar criação -->
          <Button
            v-else
            variant="ghost"
            class="w-full justify-start text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg h-auto py-2"
            @click="startCreatingList"
          >
            <Icon icon="lucide:plus" class="h-4 w-4 mr-2" />
            Adicionar lista
          </Button>
        </div>
      </div>

      <!-- Empty State - Nenhum Board -->
      <div v-else class="flex items-center justify-center h-full">
        <div class="text-center">
          <Icon icon="lucide:kanban-square" class="h-16 w-16 mx-auto text-gray-400 mb-4" />
          <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
            Nenhum board encontrado
          </h3>
          <p class="text-sm text-gray-500">
            Execute o seeder para criar o board padrão
          </p>
        </div>
      </div>
    </div>

    <!-- Modal de Criar/Editar Tarefa -->
    <TaskFormModal
      v-model:open="showTaskModal"
      :task="selectedTask"
      :board-list="selectedList"
      :board-lists="boardLists"
      :team-users="teamUsers"
      @saved="handleTaskSaved"
    />
  </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Icon } from '@iconify/vue'
import TaskFormModal from './Components/TaskFormModal.vue'
import VueDraggable from 'vuedraggable'

console.log('Draggable component:', VueDraggable)

// Alias para usar no template
const Draggable = VueDraggable

const props = defineProps({
  boards: {
    type: Array,
    required: true,
  },
  teamUsers: {
    type: Array,
    default: () => [],
  },
})

const showTaskModal = ref(false)
const selectedTask = ref(null)
const selectedList = ref(null)
const editingListId = ref(null)
const editingListName = ref('')
const creatingList = ref(false)
const newListName = ref('')
const localLists = ref([])
const isDragging = ref(false)

const boardLists = computed({
  get: () => {
    if (props.boards.length > 0) {
      if (localLists.value.length === 0) {
        localLists.value = props.boards[0].lists.map(list => ({
          ...list,
          tasks: [...(list.tasks || [])]
        }))
      }
      return localLists.value
    }
    return []
  },
  set: (value) => {
    localLists.value = value
  }
})

watch(() => props.boards, () => {
  localLists.value = []
}, { deep: true })

watch(localLists, (newVal) => {
  console.log('localLists updated:', newVal.length, 'lists')
  newVal.forEach((list, index) => {
    console.log(`List ${index} (${list.name}): ${list.tasks?.length || 0} tasks`)
  })
}, { deep: true })

function openCreateTask(list) {
  selectedList.value = list
  selectedTask.value = null
  showTaskModal.value = true
}

function openTaskDetails(task) {
  if (isDragging.value) return
  
  selectedTask.value = task
  selectedList.value = null
  showTaskModal.value = true
}

function handleTaskSaved() {
  router.reload({ only: ['boards'], preserveScroll: true })
}

function startEditingList(list) {
  editingListId.value = list.id
  editingListName.value = list.name
}

function saveListName(list) {
  if (!editingListName.value.trim()) {
    cancelEditingList()
    return
  }

  router.put(route('tasks.lists.update', { boardList: list.id }), {
    name: editingListName.value,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      editingListId.value = null
      editingListName.value = ''
    },
  })
}

function cancelEditingList() {
  editingListId.value = null
  editingListName.value = ''
}

function startCreatingList() {
  creatingList.value = true
  newListName.value = ''
}

function saveNewList() {
  if (!newListName.value.trim() || props.boards.length === 0) {
    cancelCreatingList()
    return
  }

  router.post(route('tasks.lists.store', { board: props.boards[0].id }), {
    name: newListName.value,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      cancelCreatingList()
    },
  })
}

function cancelCreatingList() {
  creatingList.value = false
  newListName.value = ''
}

function onDragStart() {
  console.log('Drag started')
  isDragging.value = true
}

function onDragEnd() {
  console.log('Drag ended')
  setTimeout(() => {
    isDragging.value = false
  }, 100)
}

function onTaskChange(event, listId) {
  console.log('Task change event:', event, 'listId:', listId)
  console.log('Event keys:', Object.keys(event))
  
  // Verifica se foi adicionado à lista (moved from another list ou reordered)
  if (event.added) {
    const task = event.added.element
    const newIndex = event.added.newIndex
    
    console.log('Task added:', task.id, 'to list:', listId, 'position:', newIndex)
    
    router.post(route('tasks.move', { task: task.id }), {
      board_list_id: listId,
      position: newIndex,
    }, {
      preserveScroll: true,
      preserveState: true,
    })
  }
  
  // Se foi movido dentro da mesma lista
  if (event.moved) {
    const task = event.moved.element
    const newIndex = event.moved.newIndex
    
    console.log('Task moved:', task.id, 'in list:', listId, 'position:', newIndex)
    
    router.post(route('tasks.move', { task: task.id }), {
      board_list_id: listId,
      position: newIndex,
    }, {
      preserveScroll: true,
      preserveState: true,
    })
  }
}

function onListsReordered() {
  if (props.boards.length === 0) return

  const listIds = boardLists.value.map(list => list.id)
  
  router.post(route('tasks.lists.reorder', { board: props.boards[0].id }), {
    list_ids: listIds,
  }, {
    preserveScroll: true,
    preserveState: true,
  })
}

function formatDate(date) {
  return new Date(date).toLocaleDateString('pt-BR', {
    day: '2-digit',
    month: 'short',
  })
}
</script>
