<script setup>
const props = defineProps({
  config: Object
})
</script>

<template>
  <div class="space-y-4">
    <div class="space-y-2">
      <label class="text-sm font-medium">Caminho / Chave JSON</label>
      <input 
        v-model="config.json_key" 
        type="text" 
        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" 
        placeholder="ex: data.cep" 
      />
      <p class="text-[10px] text-muted-foreground">
        Use ponto para navegar no JSON do passo anterior. Ex: <code>data.results.0.id</code>
      </p>
    </div>
    
    <div class="space-y-2">
      <label class="text-sm font-medium">Nome da Variável</label>
      <input 
        v-model="config.variable_name" 
        type="text" 
        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" 
        placeholder="ex: cep_encontrado" 
      />
      <p class="text-[10px] text-muted-foreground">
        Nome para referência futura.
      </p>
    </div>
  </div>
</template>
