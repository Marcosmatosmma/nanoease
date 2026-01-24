<template>
  <AppLayout title="Tarefas">
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
          Tarefas
        </h2>
        <Button @click="showCreateTaskModal = true">
          <Icon icon="lucide:plus" class="mr-2 h-4 w-4" />
          Nova Tarefa
        </Button>
      </div>
    </template>

    <div class="py-12">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div v-if="boards.length > 0" class="space-y-6">
          <div v-for="board in boards" :key="board.id">
            <div class="overflow-hidden bg-white shadow-xl dark:bg-gray-800 sm:rounded-lg">
              <div class="p-6">
                <h3 class="mb-4 text-lg font-medium text-gray-900 dark:text-gray-100">
                  {{ board.name }}
                </h3>

                <div class="flex space-x-4 overflow-x-auto pb-4">
                  <div
                    v-for="list in board.lists"
                    :key="list.id"
                    class="flex-shrink-0 w-80"
                  >
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-900 p-4">
                      <div class="mb-4 flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                          <div
                            class="h-3 w-3 rounded-full"
                            :style="{ backgroundColor: list.color }"
                          />
                          <h4 class="font-medium text-gray-700 dark:text-gray-300">
                            {{ list.name }}
                          </h4>
                          <span class="text-sm text-gray-500">
                            ({{ list.tasks?.length || 0 }})
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

                      <div class="space-y-2 min-h-[200px]">
                        <div
                          v-for="task in list.tasks"
                          :key="task.id"
                          class="p-3 bg-white dark:bg-gray-800 rounded border hover:shadow cursor-pointer"
                          @click="openTaskDetails(task)"
                        >
                          <h5 class="font-medium text-sm">{{ task.title }}</h5>
                          <p v-if="task.description" class="text-xs text-gray-500 mt-1">
                            {{ task.description?.substring(0, 50) }}{{ task.description?.length > 50 ? '...' : '' }}
                          </p>
                        </div>

                        <div
                          v-if="!list.tasks || list.tasks.length === 0"
                          class="text-center py-8 text-sm text-gray-400"
                        >
                          Nenhuma tarefa
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-else class="text-center py-12">
          <p class="text-gray-500">Nenhum board encontrado</p>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Button } from '@/Components/ui/button'
import { Icon } from '@iconify/vue'

defineProps({
  boards: {
    type: Array,
    required: true,
  },
})

const showCreateTaskModal = ref(false)
const selectedTask = ref(null)
const selectedList = ref(null)

function openCreateTask(list) {
  selectedList.value = list
  selectedTask.value = null
  showCreateTaskModal.value = true
}

function openTaskDetails(task) {
  selectedTask.value = task
  showCreateTaskModal.value = true
}
</script>
