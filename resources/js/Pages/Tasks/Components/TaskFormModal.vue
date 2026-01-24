<template>
  <Dialog v-model:open="isOpen">
    <DialogContent class="sm:max-w-[500px]">
      <DialogHeader>
        <DialogTitle>
          {{ task ? 'Editar Tarefa' : 'Nova Tarefa' }}
        </DialogTitle>
      </DialogHeader>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <!-- Título -->
        <div>
          <Label for="title">Título *</Label>
          <Input
            id="title"
            v-model="form.title"
            placeholder="Digite o título da tarefa"
            required
            autofocus
          />
          <p v-if="form.errors.title" class="text-sm text-red-600 mt-1">
            {{ form.errors.title }}
          </p>
        </div>

        <!-- Descrição -->
        <div>
          <Label for="description">Descrição</Label>
          <Textarea
            id="description"
            v-model="form.description"
            placeholder="Adicione mais detalhes..."
            rows="3"
          />
        </div>

        <!-- Data de vencimento -->
        <div>
          <Label for="due_date">Data de vencimento</Label>
          <Input
            id="due_date"
            v-model="form.due_date"
            type="date"
          />
        </div>

        <!-- Responsável -->
        <div v-if="teamUsers.length > 0">
          <Label for="assigned_to">Responsável</Label>
          <select
            id="assigned_to"
            v-model="form.assigned_to"
            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
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

        <!-- Botões -->
        <DialogFooter>
          <Button
            type="button"
            variant="outline"
            @click="closeModal"
            :disabled="form.processing"
          >
            Cancelar
          </Button>
          <Button
            type="submit"
            :disabled="form.processing"
          >
            <Icon
              v-if="form.processing"
              icon="lucide:loader-2"
              class="mr-2 h-4 w-4 animate-spin"
            />
            {{ task ? 'Salvar' : 'Criar' }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>

<script setup>
import { watch, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/Components/ui/dialog'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Textarea } from '@/Components/ui/textarea'
import { Label } from '@/Components/ui/label'
import { Icon } from '@iconify/vue'

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
  teamUsers: {
    type: Array,
    default: () => [],
  },
})

const emit = defineEmits(['update:open', 'saved'])

const isOpen = computed({
  get: () => props.open,
  set: (value) => emit('update:open', value),
})

const form = useForm({
  title: '',
  description: '',
  due_date: '',
  assigned_to: null,
})

// Preenche form ao editar
watch(() => props.task, (task) => {
  if (task) {
    form.title = task.title || ''
    form.description = task.description || ''
    form.due_date = task.due_date || ''
    form.assigned_to = task.assigned_to || null
  } else {
    form.reset()
  }
}, { immediate: true })

function handleSubmit() {
  if (props.task) {
    // Editar tarefa existente
    form.put(route('tasks.update', { task: props.task.id }), {
      preserveScroll: true,
      onSuccess: () => {
        emit('saved')
        closeModal()
      },
    })
  } else if (props.boardList) {
    // Criar nova tarefa
    form.post(route('tasks.store', { boardList: props.boardList.id }), {
      preserveScroll: true,
      onSuccess: () => {
        emit('saved')
        closeModal()
      },
    })
  }
}

function closeModal() {
  isOpen.value = false
  form.reset()
  form.clearErrors()
}
</script>
