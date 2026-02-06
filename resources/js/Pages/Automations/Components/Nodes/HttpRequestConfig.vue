<script setup>
import { Icon } from '@iconify/vue'
import { ref } from 'vue'

const props = defineProps({
  config: Object
})

const methods = ['GET', 'POST', 'PUT', 'DELETE']

const addHeader = () => {
  if (!props.config.headers) props.config.headers = []
  props.config.headers.push({ key: '', value: '' })
}

const removeHeader = (index) => {
  props.config.headers.splice(index, 1)
}
</script>

<template>
  <div class="space-y-4">
    <div class="space-y-2">
      <label class="text-sm font-medium">URL</label>
      <input 
        v-model="config.url" 
        type="url" 
        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" 
        placeholder="https://api..." 
      />
    </div>
    
    <div class="space-y-2">
      <label class="text-sm font-medium">Método</label>
      <select v-model="config.method" class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm">
        <option v-for="m in methods" :key="m" :value="m">{{ m }}</option>
      </select>
    </div>

    <!-- Headers -->
    <div class="space-y-2">
      <div class="flex items-center justify-between">
        <label class="text-sm font-medium">Headers</label>
        <button @click="addHeader" class="text-xs text-primary hover:underline">+ Add</button>
      </div>
      <div v-for="(header, idx) in config.headers" :key="idx" class="flex gap-2">
        <input v-model="header.key" placeholder="Key" class="flex h-8 w-full rounded-md border border-input bg-transparent px-2 text-xs" />
        <input v-model="header.value" placeholder="Value" class="flex h-8 w-full rounded-md border border-input bg-transparent px-2 text-xs" />
        <button @click="removeHeader(idx)" class="text-red-500">
          <Icon icon="lucide:trash-2" class="h-3 w-3" />
        </button>
      </div>
    </div>

    <!-- Body -->
    <div class="space-y-2">
      <label class="text-sm font-medium">Body (JSON)</label>
      <textarea 
        v-model="config.body" 
        rows="6" 
        class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm font-mono" 
        placeholder="{}"
      ></textarea>
    </div>
  </div>
</template>
