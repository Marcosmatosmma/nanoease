<template>
  <AppLayout title="Tarefas">
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
          Tarefas
        </h2>
      </div>
    </template>

    <div class="h-[calc(100vh-120px)] overflow-x-auto p-6">
      <div v-if="boards.length > 0">
        <!-- Kanban Board -->
        <div class="flex gap-4 h-full">
          <div
            v-for="list in boards[0].lists"
            :key="list.id"
            class="flex-shrink-0 w-80 flex flex-col"
          >
            <!-- Header da Lista -->
            <div class="flex items-center justify-between mb-3 px-2">
              <div class="flex items-center gap-2">
                <span class="font-semibold text-sm text-gray-700 dark:text-gray-300">
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
              <div class="space-y-2">
                <!-- Task Cards -->
                <div
                  v-for="task in list.tasks"
                  :key="task.id"
                  class="bg-white dark:bg-gray-800 rounded-lg p-3 shadow-sm hover:shadow-md transition-shadow cursor-pointer border border-gray-200 dark:border-gray-700"
                  @click="openTaskDetails(task)"
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

                <!-- Empty State -->
                <div
                  v-if="!list.tasks || list.tasks.length === 0"
                  class="text-center py-8 text-sm text-gray-400"
                >
                  <Icon icon="lucide:inbox" class="h-8 w-8 mx-auto mb-2 opacity-50" />
                  <p>Nenhuma tarefa</p>
                </div>

                <!-- Botão Adicionar -->
                <Button
                  variant="ghost"
                  size="sm"
                  class="w-full justify-start text-gray-600 hover:bg-gray-200 dark:hover:bg-gray-800"
                  @click="openCreateTask(list)"
                >
                  <Icon icon="lucide:plus" class="h-4 w-4 mr-2" />
                  Adicionar tarefa
                </Button>
              </div>
            </div>
          </div>
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
      :team-users="teamUsers"
      @saved="handleTaskSaved"
    />
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Button } from '@/Components/ui/button'
import { Icon } from '@iconify/vue'
import TaskFormModal from './Components/TaskFormModal.vue'

defineProps({
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

function openCreateTask(list) {
  selectedList.value = list
  selectedTask.value = null
  showTaskModal.value = true
}

function openTaskDetails(task) {
  selectedTask.value = task
  selectedList.value = null
  showTaskModal.value = true
}

function handleTaskSaved() {
  router.reload({ only: ['boards'] })
}

function formatDate(date) {
  return new Date(date).toLocaleDateString('pt-BR', {
    day: '2-digit',
    month: 'short',
  })
}
</script>
