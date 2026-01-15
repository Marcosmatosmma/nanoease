<script setup>
import { ref, computed, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import Input from '@/components/ui/input/Input.vue'
import Button from '@/components/ui/button/Button.vue'
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover'

const props = defineProps({
  modelValue: {
    type: String,
    default: '',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue'])

const labels = ref([])
const loading = ref(false)
const open = ref(false)
const searchQuery = ref('')

const filteredLabels = computed(() => {
  if (!searchQuery.value) return labels.value
  
  const query = searchQuery.value.toLowerCase()
  return labels.value.filter(label => 
    label.name.toLowerCase().includes(query)
  )
})

const filteredBySource = computed(() => {
  const groups = {
    database: [],
    suggested: [],
    gmail: [],
  }
  
  filteredLabels.value.forEach(label => {
    const source = label.source || 'gmail'
    if (groups[source]) {
      groups[source].push(label)
    }
  })
  
  return groups
})

const hasResults = computed(() => {
  return filteredBySource.value.database.length > 0 ||
         filteredBySource.value.suggested.length > 0 ||
         filteredBySource.value.gmail.length > 0
})

const fetchLabels = async () => {
  loading.value = true
  try {
    const response = await fetch(route('gmail.labels'))
    const data = await response.json()
    
    if (data.success) {
      labels.value = data.labels
    }
  } catch (error) {
    console.error('Erro ao buscar labels:', error)
  } finally {
    loading.value = false
  }
}

const selectLabel = (labelName) => {
  emit('update:modelValue', labelName)
  open.value = false
  searchQuery.value = ''
}

const createNew = () => {
  if (searchQuery.value.trim()) {
    const newLabelName = searchQuery.value.trim()
    
    // Adicionar à lista local se não existir
    const exists = labels.value.find(l => l.name === newLabelName)
    if (!exists) {
      labels.value.push({
        id: `local-${Date.now()}`,
        name: newLabelName,
        color: null,
      })
    }
    
    emit('update:modelValue', newLabelName)
    open.value = false
    searchQuery.value = ''
  }
}

const filteredLabels = computed(() => {
  if (!searchQuery.value) return labels.value
  
  return labels.value.filter(label => 
    label.name.toLowerCase().includes(searchQuery.value.toLowerCase())
  )
})

onMounted(() => {
  if (!props.disabled) {
    fetchLabels()
  }
})
</script>

<template>
  <div class="space-y-2">
    <Popover v-model:open="open">
      <PopoverTrigger as-child>
        <Button
          variant="outline"
          role="combobox"
          :aria-expanded="open"
          class="w-full justify-between"
          :disabled="disabled || loading"
        >
          <span v-if="modelValue" class="flex items-center gap-2">
            <Icon icon="lucide:tag" class="h-3 w-3" />
            {{ modelValue }}
          </span>
          <span v-else class="text-muted-foreground">
            {{ loading ? 'Carregando labels...' : 'Selecione ou crie uma label...' }}
          </span>
          <Icon icon="lucide:chevron-down" class="ml-2 h-4 w-4 shrink-0 opacity-50" />
        </Button>
      </PopoverTrigger>
      <PopoverContent class="w-full p-0" align="start">
        <div class="p-2">
          <Input
            v-model="searchQuery"
            placeholder="Buscar ou criar nova..."
            class="mb-2"
            @keyup.enter="createNew"
          />
        </div>
        
        <div class="max-h-80 overflow-y-auto">
          <!-- Labels já usadas (do banco) -->
          <div v-if="filteredBySource.database.length > 0">
            <div class="px-3 py-1.5 text-xs font-semibold text-muted-foreground bg-muted/50">
              Suas labels
            </div>
            <button
              v-for="label in filteredBySource.database"
              :key="label.id"
              class="w-full px-4 py-2.5 text-left text-sm hover:bg-accent transition-colors flex items-center gap-2"
              @click="selectLabel(label.name)"
            >
              <Icon icon="lucide:tag" class="h-3 w-3 text-blue-600" />
              <span class="flex-1">{{ label.name }}</span>
              <Icon icon="lucide:check" class="h-3 w-3 text-blue-600" v-if="modelValue === label.name" />
            </button>
          </div>

          <!-- Labels sugeridas -->
          <div v-if="filteredBySource.suggested.length > 0">
            <div class="px-3 py-1.5 text-xs font-semibold text-muted-foreground bg-muted/50 border-t">
              Sugestões
            </div>
            <button
              v-for="label in filteredBySource.suggested"
              :key="label.id"
              class="w-full px-4 py-2.5 text-left text-sm hover:bg-accent transition-colors flex items-center gap-2"
              @click="selectLabel(label.name)"
            >
              <Icon icon="lucide:lightbulb" class="h-3 w-3 text-amber-600" />
              <span>{{ label.name }}</span>
            </button>
          </div>

          <!-- Labels do Gmail -->
          <div v-if="filteredBySource.gmail.length > 0">
            <div class="px-3 py-1.5 text-xs font-semibold text-muted-foreground bg-muted/50 border-t">
              Do Gmail
            </div>
            <button
              v-for="label in filteredBySource.gmail"
              :key="label.id"
              class="w-full px-4 py-2.5 text-left text-sm hover:bg-accent transition-colors flex items-center gap-2"
              @click="selectLabel(label.name)"
            >
              <Icon icon="lucide:mail" class="h-3 w-3 text-green-600" />
              <span>{{ label.name }}</span>
            </button>
          </div>
          
          <!-- Nenhuma label encontrada -->
          <div v-if="!hasResults && !searchQuery" class="p-4 text-center text-sm text-muted-foreground">
            Nenhuma label disponível
          </div>
          
          <!-- Criar nova -->
          <div v-if="searchQuery && !hasResults" class="border-t">
            <button
              class="w-full px-4 py-3 text-left text-sm hover:bg-accent transition-colors flex items-center gap-2 font-semibold text-primary"
              @click="createNew"
            >
              <Icon icon="lucide:plus" class="h-4 w-4" />
              Criar "{{ searchQuery }}"
            </button>
          </div>
        </div>
      </PopoverContent>
    </Popover>

    <p class="text-xs text-muted-foreground">
      💡 Use "/" para hierarquia: "Financeiro/Boletos"
    </p>
  </div>
</template>
