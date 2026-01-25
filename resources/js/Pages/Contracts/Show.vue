<template>
  <AppLayout :title="contract.name">
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
          {{ contract.name }}
        </h2>
        <div class="flex gap-2">
          <Button
            variant="outline"
            @click="$inertia.visit(route('contracts.edit', contract.id))"
          >
            <Icon icon="lucide:edit" class="h-4 w-4 mr-2" />
            Editar
          </Button>
          <Button
            variant="destructive"
            @click="confirmDelete"
          >
            <Icon icon="lucide:trash-2" class="h-4 w-4 mr-2" />
            Excluir
          </Button>
        </div>
      </div>
    </template>

    <div class="py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Resumo da IA (acima das tabs se existir) -->
        <div v-if="contract.ai_summary" class="bg-blue-50 dark:bg-blue-900/20 rounded-lg shadow p-6 mb-6">
          <div class="flex items-start gap-3">
            <Icon icon="lucide:sparkles" class="h-5 w-5 text-blue-600 dark:text-blue-400 mt-0.5" />
            <div class="flex-1">
              <h3 class="text-lg font-medium text-blue-900 dark:text-blue-100 mb-2">
                Resumo Executivo (IA)
              </h3>
              <p class="text-sm text-blue-800 dark:text-blue-200 whitespace-pre-wrap">
                {{ contract.ai_summary }}
              </p>
            </div>
          </div>
        </div>

        <!-- TABS -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
          <!-- Tab Headers -->
          <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="flex -mb-px overflow-x-auto">
              <!-- Tab: Informações -->
              <button
                @click="activeTab = 'info'"
                :class="[
                  'flex-1 py-4 px-6 text-center border-b-2 font-medium text-sm transition-colors whitespace-nowrap',
                  activeTab === 'info'
                    ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                ]"
              >
                <Icon icon="lucide:file-text" class="h-4 w-4 inline-block mr-2" />
                Informações
              </button>

              <!-- Tab: Nota Fiscal -->
              <button
                v-if="contract.my_role === 'contratado'"
                @click="activeTab = 'invoice'"
                :class="[
                  'flex-1 py-4 px-6 text-center border-b-2 font-medium text-sm transition-colors whitespace-nowrap',
                  activeTab === 'invoice'
                    ? 'border-green-500 text-green-600 dark:text-green-400'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                ]"
              >
                <Icon icon="lucide:receipt" class="h-4 w-4 inline-block mr-2" />
                Nota Fiscal
              </button>

              <!-- Tab: Documentos -->
              <button
                @click="activeTab = 'documents'"
                :class="[
                  'flex-1 py-4 px-6 text-center border-b-2 font-medium text-sm transition-colors whitespace-nowrap',
                  activeTab === 'documents'
                    ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                ]"
              >
                <Icon icon="lucide:file-text" class="h-4 w-4 inline-block mr-2" />
                Documentos
                <span v-if="contract.documents.length > 0" class="ml-1 text-xs bg-blue-100 dark:bg-blue-900 px-2 py-0.5 rounded-full">
                  {{ contract.documents.length }}
                </span>
              </button>

              <!-- Tab: Alertas -->
              <button
                @click="activeTab = 'alerts'"
                :class="[
                  'flex-1 py-4 px-6 text-center border-b-2 font-medium text-sm transition-colors whitespace-nowrap',
                  activeTab === 'alerts'
                    ? 'border-orange-500 text-orange-600 dark:text-orange-400'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                ]"
              >
                <Icon icon="lucide:bell" class="h-4 w-4 inline-block mr-2" />
                Alertas
                <span v-if="contract.alerts.length > 0" class="ml-1 text-xs bg-orange-100 dark:bg-orange-900 px-2 py-0.5 rounded-full">
                  {{ contract.alerts.length }}
                </span>
              </button>

              <!-- Tab: Comentários -->
              <button
                @click="activeTab = 'comments'"
                :class="[
                  'flex-1 py-4 px-6 text-center border-b-2 font-medium text-sm transition-colors whitespace-nowrap',
                  activeTab === 'comments'
                    ? 'border-purple-500 text-purple-600 dark:text-purple-400'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                ]"
              >
                <Icon icon="lucide:message-square" class="h-4 w-4 inline-block mr-2" />
                Comentários
              </button>

              <!-- Tab: Dados Contratante -->
              <button
                @click="activeTab = 'contractor-data'"
                :class="[
                  'flex-1 py-4 px-6 text-center border-b-2 font-medium text-sm transition-colors whitespace-nowrap',
                  activeTab === 'contractor-data'
                    ? 'border-amber-500 text-amber-600 dark:text-amber-400'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                ]"
              >
                <Icon icon="lucide:building-2" class="h-4 w-4 inline-block mr-2" />
                Dados API
              </button>

              <!-- Tab: Histórico -->
              <button
                @click="activeTab = 'history'"
                :class="[
                  'flex-1 py-4 px-6 text-center border-b-2 font-medium text-sm transition-colors whitespace-nowrap',
                  activeTab === 'history'
                    ? 'border-gray-500 text-gray-600 dark:text-gray-400'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                ]"
              >
                <Icon icon="lucide:history" class="h-4 w-4 inline-block mr-2" />
                Histórico
                <span v-if="contract.history.length > 0" class="ml-1 text-xs bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded-full">
                  {{ contract.history.length }}
                </span>
              </button>
            </nav>
          </div>

          <!-- Tab Content -->
          <div class="p-6">
            <!-- Tab: Informações do Contrato -->
            <div v-if="activeTab === 'info'">
              <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                  Detalhes do Contrato
                </h3>
                <span
                  class="px-3 py-1 text-sm font-medium rounded-full"
                  :class="getStatusClass(contract.status)"
                >
                  {{ getStatusLabel(contract.status) }}
                </span>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                <div v-if="contract.my_role" class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                  <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Meu Papel</span>
                  <p class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">
                    {{ contract.my_role === 'contratante' ? 'Contratante' : 'Contratado' }}
                  </p>
                </div>

                <div v-if="contract.contract_type" class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                  <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Tipo</span>
                  <p class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">{{ contract.contract_type }}</p>
                </div>

                <div v-if="contract.contract_number" class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                  <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Número</span>
                  <p class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">{{ contract.contract_number }}</p>
                </div>

                <div v-if="contract.start_date" class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                  <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Data de Início</span>
                  <p class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">{{ formatDate(contract.start_date) }}</p>
                </div>

                <div v-if="contract.end_date" class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                  <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Data de Término</span>
                  <p class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">{{ formatDate(contract.end_date) }}</p>
                </div>

                <div v-if="contract.auto_renewal !== null" class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                  <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Renovação</span>
                  <p class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">
                    {{ contract.auto_renewal ? 'Automática' : 'Manual' }}
                  </p>
                </div>

                <div v-if="contract.amount" class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                  <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Valor</span>
                  <p class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">
                    {{ formatCurrency(contract.amount, contract.currency) }}
                  </p>
                </div>
              </div>

              <!-- Objeto do Contrato -->
              <div v-if="contract.contract_object" class="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Objeto do Contrato</span>
                <p class="mt-2 text-sm text-gray-900 dark:text-gray-100">
                  {{ contract.contract_object }}
                </p>
              </div>

              <!-- Partes do Contrato -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <!-- Contratante -->
                <div v-if="contract.contractor" class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                  <div class="flex items-center gap-2 mb-2">
                    <Icon icon="lucide:briefcase" class="h-5 w-5 text-gray-500" />
                    <span class="font-medium text-gray-700 dark:text-gray-300">Contratante</span>
                  </div>
                  <p class="text-sm text-gray-900 dark:text-gray-100 mb-1">{{ contract.contractor }}</p>
                  <p v-if="contract.contractor_cpf_cnpj" class="text-xs text-gray-500 dark:text-gray-400">
                    {{ formatCpfCnpj(contract.contractor_cpf_cnpj) }}
                  </p>
                </div>

                <!-- Contratado -->
                <div v-if="contract.contracted" class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                  <div class="flex items-center gap-2 mb-2">
                    <Icon icon="lucide:user-check" class="h-5 w-5 text-gray-500" />
                    <span class="font-medium text-gray-700 dark:text-gray-300">Contratado</span>
                  </div>
                  <p class="text-sm text-gray-900 dark:text-gray-100 mb-1">{{ contract.contracted }}</p>
                  <p v-if="contract.contracted_cpf_cnpj" class="text-xs text-gray-500 dark:text-gray-400">
                    {{ formatCpfCnpj(contract.contracted_cpf_cnpj) }}
                  </p>
                </div>
              </div>

              <!-- Condições de Pagamento -->
              <div v-if="contract.payment_terms" class="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Condições de Pagamento</span>
                <p class="mt-2 text-sm text-gray-900 dark:text-gray-100 whitespace-pre-wrap">
                  {{ contract.payment_terms }}
                </p>
              </div>

              <!-- Observações -->
              <div v-if="contract.notes" class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Observações</span>
                <p class="mt-2 text-sm text-gray-900 dark:text-gray-100 whitespace-pre-wrap">
                  {{ contract.notes }}
                </p>
              </div>
            </div>

            <!-- Tab: Nota Fiscal -->
            <div v-if="activeTab === 'invoice' && contract.my_role === 'contratado'">
              <!-- Informações de Configuração da NF -->
              <div v-if="!hasInvoiceData" class="text-center py-8 bg-gray-50 dark:bg-gray-700/50 rounded-lg mb-6">
                <Icon icon="lucide:settings" class="h-10 w-10 text-gray-400 mx-auto mb-2" />
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">Nenhuma configuração de nota fiscal cadastrada</p>
                <Button
                  size="sm"
                  @click="$inertia.visit(route('contracts.edit', contract.id))"
                >
                  Configurar Informações
                </Button>
              </div>

              <div v-else class="mb-6">
                <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-4">
                  Configurações de Emissão
                </h4>
                <div class="space-y-4">
                <!-- Contatos para Envio -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div v-if="contract.invoice_contact_email" class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Email para NF</span>
                    <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                      <a :href="`mailto:${contract.invoice_contact_email}`" class="text-blue-600 hover:underline">
                        {{ contract.invoice_contact_email }}
                      </a>
                    </p>
                  </div>

                  <div v-if="contract.invoice_contact_link" class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Link/Portal</span>
                    <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                      <a :href="contract.invoice_contact_link" target="_blank" class="text-blue-600 hover:underline">
                        {{ contract.invoice_contact_link }}
                      </a>
                    </p>
                  </div>

                  <div v-if="contract.invoice_system" class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Sistema de Gestão</span>
                    <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ contract.invoice_system }}</p>
                  </div>

                  <div v-if="contract.invoice_service_code" class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Código do Serviço (ISS)</span>
                    <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ contract.invoice_service_code }}</p>
                  </div>

                  <div v-if="contract.invoice_due_day" class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Dia do Envio da NF</span>
                    <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">Dia {{ contract.invoice_due_day }} de cada mês</p>
                  </div>
                </div>

                <!-- Destinatário da NF -->
                <div v-if="contract.invoice_recipient_name" class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                  <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                    Destinatário da Nota Fiscal
                  </h4>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                      <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Nome/Razão Social</span>
                      <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ contract.invoice_recipient_name }}</p>
                    </div>
                    <div v-if="contract.invoice_recipient_cnpj">
                      <span class="text-xs font-medium text-gray-600 dark:text-gray-400">CNPJ</span>
                      <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ formatCpfCnpj(contract.invoice_recipient_cnpj) }}</p>
                    </div>
                    <div v-if="contract.invoice_state_registration">
                      <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Inscrição Estadual</span>
                      <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ contract.invoice_state_registration }}</p>
                    </div>
                    <div v-if="contract.invoice_recipient_address" class="md:col-span-2">
                      <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Endereço</span>
                      <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ contract.invoice_recipient_address }}</p>
                    </div>
                  </div>
                </div>

                <!-- Descrição para NF -->
                <div v-if="contract.invoice_description" class="p-4 bg-white dark:bg-gray-800/50 rounded-lg border border-gray-200 dark:border-gray-700">
                  <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Descrição para Nota Fiscal</span>
                  <p class="mt-2 text-sm text-gray-900 dark:text-gray-100 whitespace-pre-wrap">{{ contract.invoice_description }}</p>
                </div>

                <!-- Observações Internas sobre NF -->
                <div v-if="contract.invoice_internal_notes" class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-700">
                  <div class="flex items-start gap-2">
                    <Icon icon="lucide:sticky-note" class="h-4 w-4 text-amber-600 dark:text-amber-400 mt-0.5" />
                    <div class="flex-1">
                      <span class="text-xs font-medium text-amber-800 dark:text-amber-300">Observações Internas (NF)</span>
                      <p class="mt-1 text-sm text-amber-900 dark:text-amber-100 whitespace-pre-wrap">{{ contract.invoice_internal_notes }}</p>
                    </div>
                  </div>
                </div>
                </div>
              </div>

              <!-- Histórico de Notas Fiscais Emitidas -->
              <div>
                <div class="flex items-center justify-between mb-4">
                  <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                    Notas Fiscais Emitidas
                  </h4>
                  <Button size="sm" @click="showInvoiceUploadModal = true">
                    <Icon icon="lucide:upload" class="h-4 w-4 mr-2" />
                    Nova Nota Fiscal
                  </Button>
                </div>

                <div v-if="contract.invoices.length === 0" class="text-center py-8 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                  <Icon icon="lucide:file-text" class="h-10 w-10 text-gray-400 mx-auto mb-2" />
                  <p class="text-sm text-gray-500 dark:text-gray-400">Nenhuma nota fiscal enviada ainda</p>
                </div>

                <div v-else class="space-y-3">
                  <div
                    v-for="invoice in contract.invoices"
                    :key="invoice.id"
                    class="p-4 bg-white dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 hover:shadow-md transition-shadow"
                  >
                    <div class="flex items-start justify-between">
                      <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                          <h5 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                            NF {{ invoice.invoice_number || 'S/N' }}
                          </h5>
                          <span
                            v-if="invoice.is_overdue"
                            class="px-2 py-0.5 text-xs rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200"
                          >
                            Vencida
                          </span>
                          <span
                            v-else-if="invoice.is_due_today"
                            class="px-2 py-0.5 text-xs rounded-full bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200"
                          >
                            Vence Hoje
                          </span>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-2">
                          <div v-if="invoice.invoice_date">
                            <span class="text-xs text-gray-500 dark:text-gray-400">Data Emissão</span>
                            <p class="text-sm text-gray-900 dark:text-gray-100">{{ formatDate(invoice.invoice_date) }}</p>
                          </div>
                          <div v-if="invoice.due_date">
                            <span class="text-xs text-gray-500 dark:text-gray-400">Vencimento</span>
                            <p class="text-sm text-gray-900 dark:text-gray-100">{{ formatDate(invoice.due_date) }}</p>
                          </div>
                          <div v-if="invoice.amount">
                            <span class="text-xs text-gray-500 dark:text-gray-400">Valor</span>
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                              {{ formatCurrency(invoice.amount, contract.currency) }}
                            </p>
                          </div>
                          <div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">Enviado por</span>
                            <p class="text-sm text-gray-900 dark:text-gray-100">{{ invoice.uploaded_by }}</p>
                          </div>
                        </div>

                        <p v-if="invoice.description" class="text-xs text-gray-600 dark:text-gray-400 mt-2">
                          {{ invoice.description }}
                        </p>
                      </div>

                      <div class="flex flex-col gap-2 ml-4">
                        <a
                          v-if="invoice.has_pdf"
                          :href="route('invoices.download-pdf', invoice.id)"
                          class="flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-md transition-colors"
                        >
                          <Icon icon="lucide:file-text" class="h-3 w-3" />
                          PDF
                        </a>
                        <a
                          v-if="invoice.has_xml"
                          :href="route('invoices.download-xml', invoice.id)"
                          class="flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-md transition-colors"
                        >
                          <Icon icon="lucide:code" class="h-3 w-3" />
                          XML
                        </a>
                        <button
                          @click="confirmDeleteInvoice(invoice.id)"
                          class="flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-md transition-colors"
                        >
                          <Icon icon="lucide:trash-2" class="h-3 w-3" />
                          Excluir
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Tab: Documentos -->
            <div v-if="activeTab === 'documents'">
              <div class="flex items-center justify-between mb-4">
                <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                  Documentos Anexados
                </h4>
                <Button size="sm" @click="showUploadModal = true">
                  <Icon icon="lucide:upload" class="h-4 w-4 mr-2" />
                  Upload
                </Button>
              </div>

              <div v-if="contract.documents.length === 0" class="text-center py-12">
                <Icon icon="lucide:file-text" class="h-12 w-12 text-gray-400 mx-auto mb-3" />
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Nenhum documento enviado ainda</p>
                <Button size="sm" @click="showUploadModal = true">
                  <Icon icon="lucide:upload" class="h-4 w-4 mr-2" />
                  Enviar Primeiro Documento
                </Button>
              </div>

              <div v-else class="space-y-2">
                <div
                  v-for="doc in contract.documents"
                  :key="doc.id"
                  class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
                >
                  <div class="flex items-center gap-3">
                    <Icon
                      :icon="doc.file_type === 'pdf' ? 'lucide:file-text' : 'lucide:file'"
                      class="h-6 w-6 text-blue-600 dark:text-blue-400"
                    />
                    <div>
                      <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                        {{ doc.file_name }}
                      </p>
                      <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ doc.file_size }} • {{ doc.uploaded_at }}
                      </p>
                    </div>
                  </div>
                  <a
                    :href="route('contracts.documents.download', doc.id)"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-md transition-colors"
                  >
                    <Icon icon="lucide:download" class="h-4 w-4" />
                    Download
                  </a>
                </div>
              </div>
            </div>

            <!-- Tab: Alertas -->
            <div v-if="activeTab === 'alerts'">
              <div class="flex items-center justify-between mb-4">
                <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                  Alertas Configurados
                </h4>
                <Button size="sm" @click="showAlertModal = true">
                  <Icon icon="lucide:plus" class="h-4 w-4 mr-2" />
                  Novo Alerta
                </Button>
              </div>

              <div v-if="contract.alerts.length === 0" class="text-center py-12">
                <Icon icon="lucide:bell-off" class="h-12 w-12 text-gray-400 mx-auto mb-3" />
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Nenhum alerta configurado</p>
                <Button size="sm" @click="showAlertModal = true">
                  <Icon icon="lucide:plus" class="h-4 w-4 mr-2" />
                  Criar Primeiro Alerta
                </Button>
              </div>

              <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                  v-for="alert in contract.alerts"
                  :key="alert.id"
                  class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg"
                >
                  <div class="flex items-start justify-between mb-2">
                    <div class="flex items-center gap-2">
                      <Icon icon="lucide:bell" class="h-5 w-5 text-orange-600 dark:text-orange-400" />
                      <span class="font-medium text-gray-900 dark:text-gray-100 text-sm">
                        {{ getAlertTypeLabel(alert.alert_type) }}
                      </span>
                    </div>
                    <span
                      class="px-2 py-0.5 text-xs rounded-full"
                      :class="alert.triggered_at ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200'"
                    >
                      {{ alert.triggered_at ? 'Disparado' : 'Ativo' }}
                    </span>
                  </div>
                  <p v-if="alert.days_before" class="text-xs text-gray-600 dark:text-gray-400 mt-2">
                    <Icon icon="lucide:calendar" class="h-3 w-3 inline-block mr-1" />
                    {{ alert.days_before }} dias antes
                  </p>
                  <p v-if="alert.triggered_at" class="text-xs text-gray-600 dark:text-gray-400 mt-2">
                    <Icon icon="lucide:check-circle" class="h-3 w-3 inline-block mr-1" />
                    Disparado em {{ alert.triggered_at }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Tab: Comentários -->
            <div v-if="activeTab === 'comments'">
              <div class="text-center py-12">
                <Icon icon="lucide:message-square" class="h-12 w-12 text-gray-400 mx-auto mb-3" />
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  Funcionalidade de comentários será implementada em breve
                </p>
              </div>
            </div>

            <!-- Tab: Dados do Contratante -->
            <div v-if="activeTab === 'contractor-data'">
              <div v-if="!contract.invoice_cnpj_api_data" class="text-center py-12">
                <Icon icon="lucide:building-2" class="h-12 w-12 text-gray-400 mx-auto mb-3" />
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  Nenhum dado da API CNPJ disponível
                </p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">
                  Os dados são salvos automaticamente quando você busca o CNPJ ao criar/editar o contrato
                </p>
              </div>

              <div v-else class="space-y-4">
                <div class="flex items-center gap-2 mb-4">
                  <Icon icon="lucide:database" class="h-5 w-5 text-amber-600 dark:text-amber-400" />
                  <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                    Dados da Receita Federal (CNPJ.ws)
                  </h4>
                </div>

                <pre class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg text-xs text-gray-800 dark:text-gray-200 overflow-auto max-h-96">{{ formatJson(contract.invoice_cnpj_api_data) }}</pre>
              </div>
            </div>

            <!-- Tab: Histórico -->
            <div v-if="activeTab === 'history'">
              <div v-if="contract.history.length === 0" class="text-center py-12">
                <Icon icon="lucide:history" class="h-12 w-12 text-gray-400 mx-auto mb-3" />
                <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum evento no histórico</p>
              </div>

              <div v-else class="space-y-3">
                <div
                  v-for="event in contract.history"
                  :key="event.id"
                  class="border-l-2 border-blue-300 dark:border-blue-600 pl-4 py-2"
                >
                  <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                    {{ event.description }}
                  </p>
                  <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ event.user_name }} • {{ event.created_at }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de Upload -->
    <div
      v-if="showUploadModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click.self="showUploadModal = false"
    >
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 w-full max-w-md">
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
          Upload de Documento
        </h3>
        <form @submit.prevent="uploadDocument">
          <input
            ref="fileInput"
            type="file"
            accept=".pdf,.doc,.docx"
            @change="handleFileChange"
            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
          />
          <div class="flex justify-end gap-2 mt-4">
            <Button type="button" variant="outline" @click="showUploadModal = false">
              Cancelar
            </Button>
            <Button type="submit" :disabled="!selectedFile || uploadingFile">
              <Icon
                v-if="uploadingFile"
                icon="lucide:loader-2"
                class="h-4 w-4 mr-2 animate-spin"
              />
              Enviar
            </Button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal de Upload de Nota Fiscal -->
    <div
      v-if="showInvoiceUploadModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click.self="showInvoiceUploadModal = false"
    >
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
          Upload de Nota Fiscal
        </h3>
        <form @submit.prevent="uploadInvoice" class="space-y-4">
          <!-- Aviso -->
          <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-700">
            <p class="text-sm text-blue-800 dark:text-blue-200">
              <Icon icon="lucide:info" class="h-4 w-4 inline-block mr-1" />
              Envie pelo menos um arquivo (PDF ou XML). O XML permite extração automática dos dados.
            </p>
          </div>

          <!-- Arquivos -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                PDF da Nota Fiscal
              </label>
              <input
                ref="pdfFileInput"
                type="file"
                accept=".pdf"
                @change="handlePdfChange"
                :disabled="extractingPdfData"
                class="block w-full text-sm text-gray-500 file:mr-2 file:py-2 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 disabled:opacity-50"
              />
              <p v-if="extractingPdfData" class="mt-1 text-xs text-blue-600 dark:text-blue-400">
                <Icon icon="lucide:loader-2" class="h-3 w-3 inline-block mr-1 animate-spin" />
                Extraindo dados com IA...
              </p>
              <p v-else-if="selectedPdfFile" class="mt-1 text-xs text-green-600 dark:text-green-400">
                ✓ {{ selectedPdfFile.name }}
              </p>
              <p v-else class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Os dados serão extraídos automaticamente com IA
              </p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                XML da Nota Fiscal
              </label>
              <input
                ref="xmlFileInput"
                type="file"
                accept=".xml"
                @change="handleXmlChange"
                class="block w-full text-sm text-gray-500 file:mr-2 file:py-2 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100"
              />
              <p v-if="!selectedXmlFile" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Os dados serão extraídos automaticamente do XML
              </p>
              <p v-else class="mt-1 text-xs text-green-600 dark:text-green-400">
                ✓ {{ selectedXmlFile.name }} - Dados extraídos
              </p>
            </div>
          </div>

          <!-- Dados da NF -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Número da NF
              </label>
              <Input v-model="invoiceForm.invoice_number" placeholder="Ex: 123456" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Data de Emissão
              </label>
              <Input v-model="invoiceForm.invoice_date" type="date" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Data de Vencimento
              </label>
              <Input v-model="invoiceForm.due_date" type="date" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Valor
              </label>
              <Input v-model="invoiceForm.amount" type="number" step="0.01" placeholder="0.00" />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Descrição/Observação (opcional)
            </label>
            <textarea
              v-model="invoiceForm.description"
              rows="3"
              placeholder="Observações sobre esta nota fiscal..."
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            ></textarea>
          </div>

          <div class="flex justify-end gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
            <Button type="button" variant="outline" @click="cancelInvoiceUpload">
              Cancelar
            </Button>
            <Button type="submit" :disabled="uploadingInvoice || (!selectedPdfFile && !selectedXmlFile)">
              <Icon
                v-if="uploadingInvoice"
                icon="lucide:loader-2"
                class="h-4 w-4 mr-2 animate-spin"
              />
              Enviar Nota Fiscal
            </Button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/components/ui/button/Button.vue'
import Input from '@/components/ui/input/Input.vue'
import { Icon } from '@iconify/vue'

const props = defineProps({
  contract: Object,
})

const activeTab = ref('info')
const showUploadModal = ref(false)
const showAlertModal = ref(false)
const selectedFile = ref(null)
const uploadingFile = ref(false)

// Notas Fiscais
const showInvoiceUploadModal = ref(false)
const selectedPdfFile = ref(null)
const selectedXmlFile = ref(null)
const uploadingInvoice = ref(false)
const extractingPdfData = ref(false)
const invoiceForm = ref({
  invoice_number: '',
  invoice_date: '',
  due_date: '',
  amount: '',
  description: '',
})

// Verifica se há dados de NF preenchidos
const hasInvoiceData = computed(() => {
  return !!(
    props.contract.invoice_contact_email ||
    props.contract.invoice_contact_link ||
    props.contract.invoice_system ||
    props.contract.invoice_description ||
    props.contract.invoice_internal_notes ||
    props.contract.invoice_recipient_name ||
    props.contract.invoice_recipient_cnpj ||
    props.contract.invoice_state_registration ||
    props.contract.invoice_recipient_address ||
    props.contract.invoice_service_code ||
    props.contract.invoice_due_day
  )
})

const getStatusClass = (status) => {
  const classes = {
    'ativo': 'bg-green-100 text-green-800',
    'vencido': 'bg-red-100 text-red-800',
    'encerrado': 'bg-gray-100 text-gray-800',
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const getStatusLabel = (status) => {
  const labels = {
    'ativo': 'Ativo',
    'vencido': 'Vencido',
    'encerrado': 'Encerrado',
  }
  return labels[status] || status
}

const getAlertTypeLabel = (type) => {
  const labels = {
    'before_expiration': 'Antes do vencimento',
    'on_expiration': 'No dia do vencimento',
    'after_expiration': 'Após vencimento',
  }
  return labels[type] || type
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return date.toLocaleDateString('pt-BR')
}

const formatCurrency = (amount, currency = 'BRL') => {
  if (!amount) return '-'
  return new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: currency,
  }).format(amount)
}

const formatCpfCnpj = (value) => {
  if (!value) return ''
  
  // Remove tudo que não é dígito
  const numbers = value.replace(/\D/g, '')
  
  // CPF: 000.000.000-00
  if (numbers.length === 11) {
    return numbers.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4')
  }
  
  // CNPJ: 00.000.000/0000-00
  if (numbers.length === 14) {
    return numbers.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5')
  }
  
  return value
}

const formatJson = (data) => {
  if (!data) return ''
  try {
    const parsed = typeof data === 'string' ? JSON.parse(data) : data
    return JSON.stringify(parsed, null, 2)
  } catch (e) {
    return data
  }
}

const handleFileChange = (event) => {
  selectedFile.value = event.target.files[0]
}

const uploadDocument = () => {
  if (!selectedFile.value) return

  uploadingFile.value = true
  const formData = new FormData()
  formData.append('document', selectedFile.value)

  router.post(route('contracts.documents.upload', props.contract.id), formData, {
    onSuccess: () => {
      showUploadModal.value = false
      selectedFile.value = null
      uploadingFile.value = false
    },
    onError: () => {
      uploadingFile.value = false
    },
  })
}

const confirmDelete = () => {
  if (confirm(`Tem certeza que deseja excluir este contrato?\n\nEsta ação não pode ser desfeita.`)) {
    router.delete(route('contracts.destroy', props.contract.id))
  }
}

// Notas Fiscais
const handlePdfChange = async (event) => {
  const file = event.target.files[0]
  selectedPdfFile.value = file
  
  if (!file) return
  
  // Tentar extrair dados do PDF com IA
  extractingPdfData.value = true
  
  try {
    const formData = new FormData()
    formData.append('pdf_file', file)
    
    const response = await fetch(route('contracts.extract-invoice-pdf'), {
      method: 'POST',
      body: formData,
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json',
      },
    })
    
    if (!response.ok) {
      console.warn('Erro ao extrair dados do PDF')
      return
    }
    
    const result = await response.json()
    
    if (result.success && result.data) {
      const data = result.data
      
      // Preencher campos se encontrou dados
      if (data.numero) {
        invoiceForm.value.invoice_number = data.numero
      }
      
      if (data.data_emissao) {
        invoiceForm.value.invoice_date = data.data_emissao
      }
      
      if (data.valor_total) {
        invoiceForm.value.amount = data.valor_total
      }
      
      if (data.data_vencimento) {
        invoiceForm.value.due_date = data.data_vencimento
      }
      
      console.log('Dados extraídos do PDF com IA:', data)
    }
  } catch (error) {
    console.error('Erro ao extrair dados do PDF:', error)
  } finally {
    extractingPdfData.value = false
  }
}

const handleXmlChange = async (event) => {
  const file = event.target.files[0]
  selectedXmlFile.value = file
  
  if (!file) return
  
  // Tentar extrair dados do XML automaticamente
  try {
    const text = await file.text()
    const parser = new DOMParser()
    const xmlDoc = parser.parseFromString(text, 'text/xml')
    
    // Verificar se há erro de parsing
    const parserError = xmlDoc.querySelector('parsererror')
    if (parserError) {
      console.warn('Erro ao fazer parse do XML')
      return
    }
    
    // Tentar extrair dados de NF-e (produto)
    let numero = xmlDoc.querySelector('nNF')?.textContent
    let dataEmissao = xmlDoc.querySelector('dhEmi, dEmi')?.textContent
    let valor = xmlDoc.querySelector('vNF')?.textContent
    let dataVencimento = null
    
    // Se não encontrou, tentar NFS-e (serviço)
    if (!numero) {
      numero = xmlDoc.querySelector('Numero, NumeroNfse')?.textContent
    }
    if (!dataEmissao) {
      dataEmissao = xmlDoc.querySelector('DataEmissao, DtEmi')?.textContent
    }
    if (!valor) {
      valor = xmlDoc.querySelector('ValorServicos, ValorTotal, Valor, ValorLiquidoNfse')?.textContent
    }
    
    // Tentar extrair data de vencimento de InformacoesComplementares (campo texto livre)
    const infosComplementares = xmlDoc.querySelector('InformacoesComplementares')?.textContent
    if (infosComplementares) {
      // Procurar por padrões de data: VENCIMENTO: DD/MM/YYYY
      const vencimentoMatch = infosComplementares.match(/VENCIMENTO:\s*(\d{2})\/(\d{2})\/(\d{4})/i)
      if (vencimentoMatch) {
        const [, dia, mes, ano] = vencimentoMatch
        dataVencimento = `${ano}-${mes}-${dia}`
      }
    }
    
    // Preencher campos se encontrou dados
    if (numero) {
      invoiceForm.value.invoice_number = numero
    }
    
    if (dataEmissao) {
      // Formatar data para YYYY-MM-DD
      let formattedDate = dataEmissao.substring(0, 10)
      invoiceForm.value.invoice_date = formattedDate
    }
    
    if (valor) {
      invoiceForm.value.amount = parseFloat(valor).toFixed(2)
    }
    
    if (dataVencimento) {
      invoiceForm.value.due_date = dataVencimento
    }
    
    console.log('Dados extraídos do XML:', { numero, dataEmissao, valor, dataVencimento })
  } catch (error) {
    console.error('Erro ao extrair dados do XML:', error)
  }
}

const cancelInvoiceUpload = () => {
  showInvoiceUploadModal.value = false
  selectedPdfFile.value = null
  selectedXmlFile.value = null
  invoiceForm.value = {
    invoice_number: '',
    invoice_date: '',
    due_date: '',
    amount: '',
    description: '',
  }
}

const uploadInvoice = () => {
  if (!selectedPdfFile.value && !selectedXmlFile.value) return

  uploadingInvoice.value = true
  const formData = new FormData()
  
  if (selectedPdfFile.value) {
    formData.append('pdf_file', selectedPdfFile.value)
  }
  if (selectedXmlFile.value) {
    formData.append('xml_file', selectedXmlFile.value)
  }
  
  // Dados manuais
  if (invoiceForm.value.invoice_number) formData.append('invoice_number', invoiceForm.value.invoice_number)
  if (invoiceForm.value.invoice_date) formData.append('invoice_date', invoiceForm.value.invoice_date)
  if (invoiceForm.value.due_date) formData.append('due_date', invoiceForm.value.due_date)
  if (invoiceForm.value.amount) formData.append('amount', invoiceForm.value.amount)
  if (invoiceForm.value.description) formData.append('description', invoiceForm.value.description)

  router.post(route('contracts.invoices.upload', props.contract.id), formData, {
    onSuccess: () => {
      cancelInvoiceUpload()
      uploadingInvoice.value = false
    },
    onError: () => {
      uploadingInvoice.value = false
    },
  })
}

const confirmDeleteInvoice = (invoiceId) => {
  if (confirm('Tem certeza que deseja excluir esta nota fiscal?\n\nEsta ação não pode ser desfeita.')) {
    router.delete(route('invoices.destroy', invoiceId))
  }
}

</script>
