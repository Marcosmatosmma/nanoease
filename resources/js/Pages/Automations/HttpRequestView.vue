<script setup>
import { ref } from 'vue'
import { Icon } from '@iconify/vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import { Link } from '@inertiajs/vue3'
import axios from 'axios'

const isLoading = ref(false)
const response = ref(null)
const error = ref(null)

const form = ref({
  url: '',
  method: 'GET',
  headers: [],
  body: ''
})

const methods = ['GET', 'POST', 'PUT', 'DELETE']

const addHeader = () => {
  form.value.headers.push({ key: '', value: '' })
}

const removeHeader = (index) => {
  form.value.headers.splice(index, 1)
}

const testRequest = async () => {
  isLoading.value = true
  response.value = null
  error.value = null

  try {
    // Converte array de headers para objeto
    const headersObj = form.value.headers.reduce((acc, curr) => {
      if (curr.key) acc[curr.key] = curr.value
      return acc
    }, {})

    const { data } = await axios.post(route('api.automations.http-request.test'), {
      url: form.value.url,
      method: form.value.method,
      headers: headersObj,
      body: form.value.body
    })

    response.value = data
  } catch (err) {
    error.value = err.response?.data?.error || err.message
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <AppLayout title="Requisição HTTP">
    <div class="space-y-6 max-w-4xl mx-auto">
      
      <!-- Header -->
      <header>
        <div class="flex items-center gap-3 mb-2">
          <Button :as="Link" :href="route('automations.new')" variant="outline" size="sm">
            <Icon icon="lucide:arrow-left" class="h-4 w-4" />
            Voltar
          </Button>
        </div>
        <h1 class="text-2xl font-bold">Requisição HTTP</h1>
        <p class="text-muted-foreground">Configure uma chamada externa para testar a comunicação.</p>
      </header>

      <div class="grid gap-6 md:grid-cols-2">
        
        <!-- Configuração -->
        <div class="space-y-4">
          <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-6 space-y-4">
            
            <!-- URL -->
            <div class="space-y-2">
              <label class="text-sm font-medium">URL</label>
              <input
                v-model="form.url"
                type="url"
                placeholder="https://api.exemplo.com/dados"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
              />
            </div>

            <!-- Method -->
            <div class="space-y-2">
              <label class="text-sm font-medium">Método</label>
              <select
                v-model="form.method"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
              >
                <option v-for="m in methods" :key="m" :value="m">{{ m }}</option>
              </select>
            </div>

            <!-- Headers -->
            <div class="space-y-2">
              <div class="flex items-center justify-between">
                <label class="text-sm font-medium">Headers</label>
                <button @click="addHeader" class="text-xs text-primary hover:underline">+ Adicionar</button>
              </div>
              <div v-for="(header, index) in form.headers" :key="index" class="flex gap-2">
                <input v-model="header.key" placeholder="Key" class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50" />
                <input v-model="header.value" placeholder="Value" class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50" />
                <button @click="removeHeader(index)" class="text-red-500 hover:text-red-700">
                  <Icon icon="lucide:trash-2" class="h-4 w-4" />
                </button>
              </div>
            </div>

            <!-- Body -->
            <div class="space-y-2">
              <label class="text-sm font-medium">Body (JSON)</label>
              <textarea
                v-model="form.body"
                rows="5"
                placeholder='{"key": "value"}'
                class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 font-mono"
              ></textarea>
            </div>

            <Button @click="testRequest" :disabled="isLoading" class="w-full">
              <Icon v-if="isLoading" icon="lucide:loader-2" class="mr-2 h-4 w-4 animate-spin" />
              {{ isLoading ? 'Testando...' : 'Testar Requisição' }}
            </Button>
          </div>
        </div>

        <!-- Resultado -->
        <div class="space-y-4">
          <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-6 h-full flex flex-col">
            <h3 class="font-semibold mb-4">Resultado</h3>
            
            <div v-if="response" class="space-y-4 flex-1 overflow-auto">
              <div class="flex items-center gap-2">
                <span class="text-sm font-medium">Status:</span>
                <span :class="{'text-green-600': response.status >= 200 && response.status < 300, 'text-red-600': response.status >= 400}">{{ response.status }}</span>
              </div>
              
              <div class="space-y-1">
                <span class="text-sm font-medium">Response Body:</span>
                <pre class="bg-muted p-4 rounded-md text-xs font-mono overflow-auto max-h-[400px]">{{ response.data }}</pre>
              </div>

               <div class="space-y-1">
                <span class="text-sm font-medium">Headers:</span>
                <pre class="bg-muted p-4 rounded-md text-xs font-mono overflow-auto max-h-[200px]">{{ response.headers }}</pre>
              </div>
            </div>

            <div v-else-if="error" class="text-red-500 p-4 border border-red-200 rounded-md bg-red-50">
              <p class="font-bold">Erro:</p>
              <p>{{ error }}</p>
            </div>

            <div v-else class="flex-1 flex items-center justify-center text-muted-foreground text-sm border border-dashed rounded-md">
              Os resultados aparecerão aqui.
            </div>
          </div>
        </div>

      </div>
    </div>
  </AppLayout>
</template>
