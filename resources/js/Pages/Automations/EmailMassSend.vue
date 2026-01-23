<script setup>
import { Icon } from '@iconify/vue'
import AppLayout from '@/layouts/AppLayout.vue'
import Badge from '@/components/ui/badge/Badge.vue'
import Button from '@/components/ui/button/Button.vue'
import Card from '@/components/ui/card/Card.vue'
import CardContent from '@/components/ui/card/CardContent.vue'
import CardDescription from '@/components/ui/card/CardDescription.vue'
import CardHeader from '@/components/ui/card/CardHeader.vue'
import CardTitle from '@/components/ui/card/CardTitle.vue'
import Input from '@/components/ui/input/Input.vue'
import Textarea from '@/components/ui/textarea/Textarea.vue'
import RichTextEditor from '@/components/RichTextEditor.vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'

/**
 * Props do componente
 * @property {string} connectedEmail - Email conectado do Gmail
 * @property {boolean} hasGmail - Se tem integração com Gmail ativa
 * @property {object|null} automation - Dados da automação (quando editando)
 */
const props = defineProps({
  connectedEmail: {
    type: String,
    default: null,
  },
  hasGmail: {
    type: Boolean,
    default: false,
  },
  automation: {
    type: Object,
    default: null,
  },
})

/**
 * Formulário principal da automação
 * Gerencia todos os dados do envio em massa
 */
const form = useForm({
  name: '',
  send_type: 'now', // 'now' ou 'scheduled'
  scheduled_at: '',
  recipient_source: 'csv', // 'csv' ou 'sheets'
  recipients_data: null,
  subject: '',
  body: '',
  email_column: 'email',
  variable_mapping: {},
})

/**
 * Estado do wizard
 * Controla em qual etapa o usuário está
 */
const currentStep = ref(1)
const totalSteps = 5

/**
 * Estado da validação de destinatários
 * Armazena resumo dos emails validados
 */
const recipientsValidation = ref(null)
const validatingRecipients = ref(false)

/**
 * Estado da prévia do email
 * Mostra exemplo de como ficará o email personalizado
 */
const emailPreview = ref(null)

/**
 * Estado do arquivo CSV
 * Armazena o arquivo selecionado e dados parseados
 */
const csvFile = ref(null)
const csvData = ref(null)
const csvColumns = ref([])

/**
 * Verifica se está editando uma automação existente
 */
const isEditing = computed(() => !!props.automation?.id)

/**
 * Preenche o formulário se estiver editando
 */
if (props.automation) {
  form.name = props.automation.name || ''
  form.send_type = props.automation.send_type || 'now'
  form.scheduled_at = props.automation.scheduled_at || ''
  form.subject = props.automation.subject || ''
  form.body = props.automation.body || ''
}

/**
 * Navega para o próximo passo do wizard
 * Valida o passo atual antes de avançar
 */
function nextStep() {
  if (!validateCurrentStep()) return
  
  if (currentStep.value < totalSteps) {
    currentStep.value++
  }
}

/**
 * Volta para o passo anterior do wizard
 */
function prevStep() {
  if (currentStep.value > 1) {
    currentStep.value--
  }
}

/**
 * Valida o passo atual do wizard
 * @returns {boolean} true se válido
 */
function validateCurrentStep() {
  form.clearErrors()
  
  switch (currentStep.value) {
    case 1:
      if (!form.name.trim()) {
        form.setError('name', 'Digite um nome para identificar este envio')
        return false
      }
      if (form.send_type === 'scheduled' && !form.scheduled_at) {
        form.setError('scheduled_at', 'Selecione data e hora do envio')
        return false
      }
      break
      
    case 2:
      if (!csvData.value || csvData.value.length === 0) {
        form.setError('recipients_data', 'Selecione um arquivo CSV com destinatários')
        return false
      }
      if (!form.email_column) {
        form.setError('email_column', 'Selecione a coluna que contém os emails')
        return false
      }
      break
      
    case 3:
      if (!form.subject.trim()) {
        form.setError('subject', 'Digite o assunto do email')
        return false
      }
      if (!form.body.trim()) {
        form.setError('body', 'Digite o corpo do email')
        return false
      }
      break
  }
  
  return true
}

/**
 * Processa o arquivo CSV selecionado
 * Extrai colunas e dados para validação
 * @param {Event} event - Evento do input file
 */
function handleCsvUpload(event) {
  const file = event.target.files[0]
  if (!file) return
  
  csvFile.value = file
  
  const reader = new FileReader()
  reader.onload = (e) => {
    const text = e.target.result
    const lines = text.split('\n').filter(line => line.trim())
    
    if (lines.length === 0) {
      form.setError('recipients_data', 'Arquivo CSV vazio')
      return
    }
    
    // Detecta delimitador (ponto-e-vírgula ou vírgula)
    const delimiter = lines[0].includes(';') ? ';' : ','
    
    // Primeira linha = cabeçalhos
    const headers = lines[0].split(delimiter).map(h => h.trim())
    csvColumns.value = headers
    
    // Detecta automaticamente coluna de email
    const emailCol = headers.find(h => 
      h.toLowerCase() === 'email' || 
      h.toLowerCase() === 'e-mail' ||
      h.toLowerCase().includes('mail')
    )
    if (emailCol) {
      form.email_column = emailCol
    }
    
    // Parseia dados
    const data = []
    for (let i = 1; i < lines.length; i++) {
      const values = lines[i].split(delimiter).map(v => v.trim())
      const row = {}
      headers.forEach((header, index) => {
        row[header] = values[index] || ''
      })
      data.push(row)
    }
    
    csvData.value = data
    
    // Auto-valida após carregar
    validateRecipients()
  }
  
  reader.readAsText(file)
}

/**
 * Valida os destinatários do CSV
 * Verifica emails válidos, duplicados, etc
 */
function validateRecipients() {
  if (!csvData.value || !form.email_column) return
  
  validatingRecipients.value = true
  
  const emails = csvData.value.map(row => row[form.email_column])
  const validEmails = []
  const invalidEmails = []
  const duplicates = new Set()
  const seen = new Set()
  
  // Regex simples de email
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  
  emails.forEach((email, index) => {
    const emailTrimmed = (email || '').trim()
    
    if (!emailTrimmed) {
      invalidEmails.push({ line: index + 2, email: emailTrimmed, reason: 'Email vazio' })
      return
    }
    
    if (!emailRegex.test(emailTrimmed)) {
      invalidEmails.push({ line: index + 2, email: emailTrimmed, reason: 'Formato inválido' })
      return
    }
    
    if (seen.has(emailTrimmed)) {
      duplicates.add(emailTrimmed)
      invalidEmails.push({ line: index + 2, email: emailTrimmed, reason: 'Duplicado' })
      return
    }
    
    seen.add(emailTrimmed)
    validEmails.push(emailTrimmed)
  })
  
  recipientsValidation.value = {
    total: emails.length,
    valid: validEmails.length,
    invalid: invalidEmails.length,
    duplicates: duplicates.size,
    invalidList: invalidEmails.slice(0, 10), // Mostra apenas os 10 primeiros erros
  }
  
  validatingRecipients.value = false
}

/**
 * Gera prévia do email com dados reais
 * Substitui variáveis {{nome}}, {{empresa}} etc
 */
function generatePreview() {
  if (!csvData.value || csvData.value.length === 0) return
  
  // Pega o primeiro destinatário como exemplo
  const exampleRecipient = csvData.value[0]
  
  let previewSubject = form.subject
  let previewBody = form.body
  
  // Substitui variáveis
  csvColumns.value.forEach(col => {
    const value = exampleRecipient[col] || ''
    const regex = new RegExp(`{{${col}}}`, 'g')
    previewSubject = previewSubject.replace(regex, value)
    previewBody = previewBody.replace(regex, value)
  })
  
  emailPreview.value = {
    to: exampleRecipient[form.email_column],
    subject: previewSubject,
    body: previewBody,
  }
}

/**
 * Observa mudanças no assunto e corpo para atualizar prévia
 */
watch([() => form.subject, () => form.body], () => {
  generatePreview()
})

/**
 * Salva a automação (rascunho ou ativa)
 */
function submit() {
  if (!validateCurrentStep()) return
  
  // Prepara dados dos destinatários
  form.recipients_data = {
    file_name: csvFile.value?.name,
    columns: csvColumns.value,
    data: csvData.value,
    validation: recipientsValidation.value,
  }
  
  const endpoint = isEditing.value
    ? route('automations.email-mass-send.store', { automation: props.automation.id })
    : route('automations.email-mass-send.store')
  
  form.post(endpoint, {
    preserveScroll: true,
    onSuccess: () => {
      console.log('Sucesso!')
    },
    onError: (errors) => {
      console.error('Erros de validação:', errors)
      
      // Volta para o passo que tem erro
      if (errors.name || errors.send_type || errors.scheduled_at) {
        currentStep.value = 1
      } else if (errors.recipients_data || errors.email_column) {
        currentStep.value = 2
      } else if (errors.subject || errors.body) {
        currentStep.value = 3
      }
    },
  })
}

/**
 * Títulos dos passos do wizard
 */
const stepTitles = {
  1: 'Informações básicas',
  2: 'Destinatários',
  3: 'Mensagem',
  4: 'Revisão',
  5: 'Envio',
}
</script>

<template>
  <AppLayout title="Envio de E-mail em Massa">
    <div class="space-y-6">
      <!-- Header com breadcrumb -->
      <header class="space-y-2">
        <div class="flex items-center gap-3">
          <Button :as="Link" :href="route('automations.index')" variant="outline" size="sm">
            <Icon icon="lucide:arrow-left" class="h-4 w-4" />
            Voltar
          </Button>
        </div>
        <div>
          <h1 class="text-2xl font-bold">{{ isEditing ? 'Editar' : 'Criar' }} Envio em Massa</h1>
          <p class="text-muted-foreground">
            Envie e-mails operacionais para múltiplos destinatários de forma automatizada
          </p>
        </div>
      </header>

      <!-- Indicador de progresso do wizard -->
      <Card>
        <CardContent class="pt-6">
          <div class="flex items-center justify-between">
            <div 
              v-for="step in totalSteps" 
              :key="step"
              class="flex items-center"
              :class="{ 'flex-1': step < totalSteps }"
            >
              <div class="flex flex-col items-center gap-2">
                <div 
                  class="flex h-10 w-10 items-center justify-center rounded-full border-2 transition"
                  :class="{
                    'border-primary bg-primary text-primary-foreground': currentStep >= step,
                    'border-gray-300 bg-white text-gray-400': currentStep < step,
                  }"
                >
                  <Icon 
                    v-if="currentStep > step"
                    icon="lucide:check"
                    class="h-5 w-5"
                  />
                  <span v-else class="text-sm font-semibold">{{ step }}</span>
                </div>
                <span 
                  class="text-xs font-medium"
                  :class="{
                    'text-primary': currentStep >= step,
                    'text-gray-400': currentStep < step,
                  }"
                >
                  {{ stepTitles[step] }}
                </span>
              </div>
              <div 
                v-if="step < totalSteps"
                class="mx-2 h-0.5 flex-1 transition"
                :class="{
                  'bg-primary': currentStep > step,
                  'bg-gray-300': currentStep <= step,
                }"
              />
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Passo 1: Informações básicas -->
      <Card v-show="currentStep === 1">
        <CardHeader>
          <CardTitle>Informações básicas</CardTitle>
          <CardDescription>
            Defina nome e quando o envio deve acontecer
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="space-y-2">
            <label class="text-sm font-medium">Nome do envio</label>
            <Input
              v-model="form.name"
              placeholder="Ex: Convite reunião anual 2025"
              :class="{ 'border-red-500': form.errors.name }"
            />
            <p v-if="form.errors.name" class="text-sm text-red-600">{{ form.errors.name }}</p>
          </div>

          <div class="space-y-2">
            <label class="text-sm font-medium">Quando enviar?</label>
            <div class="space-y-2">
              <label class="flex items-center gap-2 cursor-pointer">
                <input 
                  type="radio" 
                  v-model="form.send_type" 
                  value="now"
                  class="h-4 w-4"
                />
                <div>
                  <div class="font-medium">Enviar agora</div>
                  <div class="text-sm text-muted-foreground">
                    Após salvar, o envio iniciará imediatamente
                  </div>
                </div>
              </label>

              <label class="flex items-center gap-2 cursor-pointer">
                <input 
                  type="radio" 
                  v-model="form.send_type" 
                  value="scheduled"
                  class="h-4 w-4"
                />
                <div class="flex-1">
                  <div class="font-medium">Agendar envio</div>
                  <div class="text-sm text-muted-foreground">
                    Escolha data e hora para enviar
                  </div>
                  <Input
                    v-if="form.send_type === 'scheduled'"
                    type="datetime-local"
                    v-model="form.scheduled_at"
                    class="mt-2"
                    :class="{ 'border-red-500': form.errors.scheduled_at }"
                  />
                </div>
              </label>
            </div>
            <p v-if="form.errors.scheduled_at" class="text-sm text-red-600">{{ form.errors.scheduled_at }}</p>
          </div>
        </CardContent>
      </Card>

      <!-- Passo 2: Destinatários -->
      <Card v-show="currentStep === 2">
        <CardHeader>
          <CardTitle>Destinatários</CardTitle>
          <CardDescription>
            Faça upload do arquivo CSV com os destinatários
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="rounded-lg border-2 border-dashed border-gray-300 p-6 text-center">
            <Icon icon="lucide:upload" class="mx-auto h-12 w-12 text-gray-400 mb-4" />
            <label class="cursor-pointer">
              <span class="text-sm font-medium text-primary hover:underline">
                Clique para selecionar arquivo CSV
              </span>
              <input 
                type="file" 
                accept=".csv"
                class="hidden"
                @change="handleCsvUpload"
              />
            </label>
            <p class="mt-2 text-xs text-muted-foreground">
              O arquivo deve conter uma coluna de email obrigatória
            </p>
          </div>

          <!-- Informações do arquivo -->
          <div v-if="csvFile" class="rounded-lg bg-blue-50 border border-blue-200 p-4">
            <div class="flex items-center gap-2 text-blue-900">
              <Icon icon="lucide:file-text" class="h-5 w-5" />
              <span class="font-medium">{{ csvFile.name }}</span>
            </div>
          </div>

          <!-- Seletor de coluna de email -->
          <div v-if="csvColumns.length > 0" class="space-y-2">
            <label class="text-sm font-medium">Qual coluna contém os emails?</label>
            <select 
              v-model="form.email_column"
              class="w-full rounded-md border border-gray-300 px-3 py-2"
              @change="validateRecipients"
            >
              <option v-for="col in csvColumns" :key="col" :value="col">
                {{ col }}
              </option>
            </select>
          </div>

          <!-- Resumo da validação -->
          <div v-if="recipientsValidation" class="space-y-3">
            <div class="grid grid-cols-3 gap-4">
              <div class="rounded-lg border p-3">
                <div class="text-2xl font-bold">{{ recipientsValidation.total }}</div>
                <div class="text-xs text-muted-foreground">Total de linhas</div>
              </div>
              <div class="rounded-lg border border-green-200 bg-green-50 p-3">
                <div class="text-2xl font-bold text-green-700">{{ recipientsValidation.valid }}</div>
                <div class="text-xs text-green-600">E-mails válidos</div>
              </div>
              <div class="rounded-lg border border-red-200 bg-red-50 p-3">
                <div class="text-2xl font-bold text-red-700">{{ recipientsValidation.invalid }}</div>
                <div class="text-xs text-red-600">Ignorados</div>
              </div>
            </div>

            <!-- Lista de erros -->
            <div v-if="recipientsValidation.invalidList.length > 0" class="rounded-lg border border-amber-200 bg-amber-50 p-4">
              <div class="flex items-center gap-2 text-amber-900 mb-2">
                <Icon icon="lucide:alert-triangle" class="h-5 w-5" />
                <span class="font-semibold">E-mails com problema</span>
              </div>
              <div class="space-y-1 text-sm">
                <div 
                  v-for="(item, idx) in recipientsValidation.invalidList" 
                  :key="idx"
                  class="flex items-center gap-2"
                >
                  <Badge variant="outline" class="shrink-0">Linha {{ item.line }}</Badge>
                  <span class="text-muted-foreground">{{ item.email || '(vazio)' }}</span>
                  <span class="text-xs text-amber-700">· {{ item.reason }}</span>
                </div>
                <p v-if="recipientsValidation.invalidList.length < recipientsValidation.invalid" class="text-xs text-amber-600 mt-2">
                  + {{ recipientsValidation.invalid - recipientsValidation.invalidList.length }} outros erros...
                </p>
              </div>
            </div>
          </div>

          <p v-if="form.errors.recipients_data" class="text-sm text-red-600">{{ form.errors.recipients_data }}</p>
          <p v-if="form.errors.email_column" class="text-sm text-red-600">{{ form.errors.email_column }}</p>
        </CardContent>
      </Card>

      <!-- Passo 3: Mensagem -->
      <Card v-show="currentStep === 3">
        <CardHeader>
          <CardTitle>Mensagem</CardTitle>
          <CardDescription>
            Escreva o assunto e corpo do e-mail. Use variáveis como {{nome}}, {{empresa}}
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="space-y-2">
            <label class="text-sm font-medium">Assunto</label>
            <Input
              v-model="form.subject"
              placeholder="Ex: Convite para reunião - {{empresa}}"
              :class="{ 'border-red-500': form.errors.subject }"
            />
            <p v-if="csvColumns.length > 0" class="text-xs text-muted-foreground">
              Variáveis disponíveis: 
              <template v-for="(col, idx) in csvColumns" :key="col">
                <span v-if="idx > 0">, </span>
                <code class="font-mono text-primary">&#123;&#123;{{ col }}&#125;&#125;</code>
              </template>
            </p>
            <p v-if="form.errors.subject" class="text-sm text-red-600">{{ form.errors.subject }}</p>
          </div>

          <div class="space-y-2">
            <label class="text-sm font-medium">Corpo do e-mail</label>
            <Textarea
              v-model="form.body"
              rows="8"
              placeholder="Olá {{nome}},&#10;&#10;Estamos enviando este convite..."
              :class="{ 'border-red-500': form.errors.body }"
            />
            <p v-if="form.errors.body" class="text-sm text-red-600">{{ form.errors.body }}</p>
          </div>
        </CardContent>
      </Card>

      <!-- Passo 4: Revisão -->
      <Card v-show="currentStep === 4">
        <CardHeader>
          <CardTitle>Revisão</CardTitle>
          <CardDescription>
            Confira os dados antes de salvar
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="space-y-3">
            <div class="rounded-lg border p-4">
              <div class="text-sm font-medium text-muted-foreground mb-1">Nome do envio</div>
              <div class="font-medium">{{ form.name }}</div>
            </div>

            <div class="rounded-lg border p-4">
              <div class="text-sm font-medium text-muted-foreground mb-1">Quando enviar</div>
              <div class="font-medium">
                {{ form.send_type === 'now' ? 'Imediatamente após salvar' : `Agendado para ${form.scheduled_at}` }}
              </div>
            </div>

            <div class="rounded-lg border p-4">
              <div class="text-sm font-medium text-muted-foreground mb-1">Destinatários</div>
              <div class="font-medium">
                {{ recipientsValidation?.valid || 0 }} e-mails válidos
                <span v-if="recipientsValidation?.invalid > 0" class="text-amber-600">
                  ({{ recipientsValidation.invalid }} ignorados)
                </span>
              </div>
            </div>

            <!-- Prévia do email -->
            <div v-if="emailPreview" class="rounded-lg border border-blue-200 bg-blue-50 p-4">
              <div class="flex items-center gap-2 text-blue-900 mb-3">
                <Icon icon="lucide:eye" class="h-5 w-5" />
                <span class="font-semibold">Prévia com primeiro destinatário</span>
              </div>
              <div class="space-y-2 rounded-lg bg-white p-4">
                <div class="text-sm">
                  <span class="font-medium">Para:</span> {{ emailPreview.to }}
                </div>
                <div class="text-sm">
                  <span class="font-medium">Assunto:</span> {{ emailPreview.subject }}
                </div>
                <div class="border-t pt-2 text-sm whitespace-pre-wrap">
                  {{ emailPreview.body }}
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Botões de navegação -->
      <Card>
        <CardContent class="pt-6">
          <div class="flex items-center justify-between">
            <Button
              v-if="currentStep > 1"
              variant="outline"
              @click="prevStep"
            >
              <Icon icon="lucide:arrow-left" class="mr-2 h-4 w-4" />
              Voltar
            </Button>
            <div v-else />

            <div class="flex items-center gap-2">
              <Button
                v-if="currentStep < 4"
                @click="nextStep"
              >
                Próximo
                <Icon icon="lucide:arrow-right" class="ml-2 h-4 w-4" />
              </Button>

              <Button
                v-if="currentStep === 4"
                :disabled="!hasGmail || form.processing || (recipientsValidation?.valid || 0) === 0"
                @click="submit"
              >
                <Icon v-if="form.processing" icon="lucide:loader-2" class="mr-2 h-4 w-4 animate-spin" />
                {{ form.processing ? 'Salvando...' : (form.send_type === 'now' ? 'Salvar e Enviar' : 'Agendar Envio') }}
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
