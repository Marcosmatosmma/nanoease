<template>
  <div class="min-h-screen flex flex-col items-center justify-center bg-gray-100 dark:bg-gray-900 px-4">
    <div class="w-full max-w-md bg-white dark:bg-gray-800 shadow-md rounded-lg p-8">
      <!-- Logo / Header -->
      <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
          Portal do Fornecedor
        </h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
          Envie suas notas fiscais de forma rápida e segura
        </p>
      </div>

      <!-- Form -->
      <form @submit.prevent="submit">
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Informe seu CNPJ para acessar
          </label>
          <Input
            v-model="form.cnpj"
            placeholder="00.000.000/0000-00"
            class="w-full text-center text-lg tracking-wider"
            required
            maxlength="18"
            @input="formatCNPJ"
          />
          <p v-if="form.errors.cnpj" class="mt-2 text-sm text-red-600">
            {{ form.errors.cnpj }}
          </p>
        </div>

        <Button
          type="submit"
          class="w-full justify-center"
          :disabled="form.processing"
        >
          <Icon 
            v-if="form.processing" 
            icon="lucide:loader-2" 
            class="animate-spin h-5 w-5 mr-2" 
          />
          Acessar Contratos
        </Button>
      </form>
    </div>

    <div class="mt-8 text-center text-xs text-gray-500 dark:text-gray-400">
      &copy; {{ new Date().getFullYear() }} Larasonic UI. Todos os direitos reservados.
    </div>
  </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import Input from '@/components/ui/input/Input.vue'
import Button from '@/components/ui/button/Button.vue'
import { Icon } from '@iconify/vue'

const form = useForm({
  cnpj: ''
})

const formatCNPJ = (e) => {
  let value = e.target.value.replace(/\D/g, '')
  if (value.length > 14) value = value.slice(0, 14)
  
  // Máscara simples
  if (value.length > 12) {
    value = value.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2}).*/, '$1.$2.$3/$4-$5')
  } else if (value.length > 8) {
    value = value.replace(/^(\d{2})(\d{3})(\d{3}).*/, '$1.$2.$3')
  } else if (value.length > 5) {
     value = value.replace(/^(\d{2})(\d{3}).*/, '$1.$2')
  } else if (value.length > 2) {
    value = value.replace(/^(\d{2}).*/, '$1.')
  }
  
  form.cnpj = value
}

const submit = () => {
  form.post(route('portal.supplier.search'))
}
</script>
