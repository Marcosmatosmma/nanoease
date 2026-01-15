<script setup>
import { ref, computed, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import Select from '@/components/ui/select/Select.vue'
import SelectContent from '@/components/ui/select/SelectContent.vue'
import SelectItem from '@/components/ui/select/SelectItem.vue'
import SelectTrigger from '@/components/ui/select/SelectTrigger.vue'
import SelectValue from '@/components/ui/select/SelectValue.vue'
import Input from '@/components/ui/input/Input.vue'
import Button from '@/components/ui/button/Button.vue'

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
const showNewInput = ref(false)
const newLabelName = ref('')

const selectedLabel = computed({
  get: () => props.modelValue,
  set: (value) => {
    if (value === '__new__') {
      showNewInput.value = true
    } else {
      emit('update:modelValue', value)
    }
  },
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

const createNew = () => {
  if (newLabelName.value.trim()) {
    emit('update:modelValue', newLabelName.value.trim())
    showNewInput.value = false
    newLabelName.value = ''
  }
}

const cancelNew = () => {
  showNewInput.value = false
  newLabelName.value = ''
}

onMounted(() => {
  if (!props.disabled) {
    fetchLabels()
  }
})
</script>

<template>
  <div class="space-y-2">
    <div v-if="!showNewInput">
      <Select v-model="selectedLabel" :disabled="disabled || loading">
        <SelectTrigger>
          <SelectValue placeholder="Selecione ou crie uma label..." />
        </SelectTrigger>
        <SelectContent>
          <SelectItem 
            v-for="label in labels" 
            :key="label.id" 
            :value="label.name"
          >
            <div class="flex items-center gap-2">
              <Icon icon="lucide:tag" class="h-3 w-3" />
              {{ label.name }}
            </div>
          </SelectItem>
          <SelectItem value="__new__" class="border-t mt-2 pt-2">
            <div class="flex items-center gap-2 font-semibold text-primary">
              <Icon icon="lucide:plus" class="h-3 w-3" />
              Criar nova label...
            </div>
          </SelectItem>
        </SelectContent>
      </Select>
      <p v-if="loading" class="text-xs text-muted-foreground">
        Carregando labels do Gmail...
      </p>
    </div>

    <div v-else class="flex gap-2">
      <Input
        v-model="newLabelName"
        type="text"
        placeholder="ex.: Financeiro/Boletos"
        @keyup.enter="createNew"
        @keyup.esc="cancelNew"
        autofocus
      />
      <Button size="sm" variant="default" @click="createNew">
        <Icon icon="lucide:check" class="h-4 w-4" />
      </Button>
      <Button size="sm" variant="outline" @click="cancelNew">
        <Icon icon="lucide:x" class="h-4 w-4" />
      </Button>
    </div>

    <p class="text-xs text-muted-foreground">
      💡 Use "/" para hierarquia: "Financeiro/Boletos"
    </p>
  </div>
</template>
