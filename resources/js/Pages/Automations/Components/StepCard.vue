<script setup>
import { Icon } from '@iconify/vue'

const props = defineProps({
  step: Object,
  isSelected: Boolean,
  index: Number
})

const emit = defineEmits(['select', 'delete', 'test'])
</script>

<template>
  <div 
    @click="$emit('select', step)"
    :class="[
      'w-full bg-white rounded-lg border shadow-sm p-4 cursor-pointer transition-all hover:border-primary/50 hover:shadow-md flex items-center gap-3 relative',
      isSelected ? 'border-primary ring-1 ring-primary' : 'border-border',
      step.testResult?.status === 'success' ? '!border-green-400 !bg-green-50/20' : '',
      step.testResult?.status === 'error' ? '!border-red-400 !bg-red-50/20' : '',
    ]"
  >
    <div class="p-2 rounded-md bg-slate-100 text-slate-600">
      <Icon :icon="step.icon" class="h-5 w-5" />
    </div>
    
    <div class="flex-1 min-w-0">
      <h3 class="font-medium text-sm truncate">{{ step.title }}</h3>
      <p class="text-xs text-muted-foreground truncate">{{ step.type }}</p>
    </div>
    
    <!-- Test Button -->
    <button 
      @click.stop="$emit('test', step)" 
      class="p-1.5 mr-1 rounded-full text-slate-400 hover:text-primary hover:bg-slate-50 transition-colors"
      title="Testar até este passo"
    >
      <Icon icon="lucide:play" class="h-4 w-4" />
    </button>

    <!-- Delete Action -->
    <button 
      @click.stop="$emit('delete', index)" 
      class="opacity-0 group-hover:opacity-100 p-1 text-red-500 hover:bg-red-50 rounded transition-opacity"
      title="Remover passo"
    >
      <Icon icon="lucide:trash-2" class="h-4 w-4" />
    </button>
  </div>
</template>
