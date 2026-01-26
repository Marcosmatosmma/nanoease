<template>
  <AppLayout :title="contract.name">
    <div class="space-y-6">
      <!-- Header com botões -->
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
          {{ contract.name }}
        </h2>
        <Button
          variant="outline"
          @click="$inertia.visit(route('contracts.edit', contract.id))"
        >
          <Icon icon="lucide:edit" class="h-4 w-4 mr-2" />
          Editar
        </Button>
      </div>

      <!-- Resumo da IA (acima das tabs se existir) -->
      <div v-if="contract.ai_summary" class="bg-blue-50 dark:bg-blue-900/20 rounded-lg shadow p-6">
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

              <!-- Tab: Relatório -->
              <button
                @click="activeTab = 'report'"
                :class="[
                  'flex-1 py-4 px-6 text-center border-b-2 font-medium text-sm transition-colors whitespace-nowrap',
                  activeTab === 'report'
                    ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                ]"
              >
                <Icon icon="lucide:bar-chart-3" class="h-4 w-4 inline-block mr-2" />
                Relatório
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
                <div class="mb-4">
                  <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                    Notas Fiscais Emitidas
                  </h4>
                  <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Para adicionar novas notas fiscais, utilize o editor do contrato
                  </p>
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
                            v-if="invoice.is_paid"
                            class="px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200"
                          >
                            Pago
                          </span>
                          <span
                            v-else
                            class="px-2 py-0.5 text-xs rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200"
                          >
                            A Pagar
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
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Tab: Documentos -->
            <div v-if="activeTab === 'documents'">
              <div class="mb-4">
                <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                  Documentos Anexados
                </h4>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                  Para adicionar novos documentos, utilize o editor do contrato
                </p>
              </div>

              <div v-if="contract.documents.length === 0" class="text-center py-12 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <Icon icon="lucide:file-text" class="h-12 w-12 text-gray-400 mx-auto mb-3" />
                <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum documento enviado ainda</p>
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
              <div class="mb-4">
                <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                  Alertas Configurados
                </h4>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                  Para configurar novos alertas, utilize o editor do contrato
                </p>
              </div>

              <div v-if="contract.alerts.length === 0" class="text-center py-12 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <Icon icon="lucide:bell-off" class="h-12 w-12 text-gray-400 mx-auto mb-3" />
                <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum alerta configurado</p>
              </div>

              <div v-else class="space-y-2">
                <div
                  v-for="alert in contract.alerts"
                  :key="alert.id"
                  class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg"
                >
                  <div class="flex items-center gap-3 flex-1 min-w-0">
                    <Icon
                      :icon="alert.is_active ? 'lucide:bell' : 'lucide:bell-off'"
                      :class="[
                        'h-6 w-6 flex-shrink-0',
                        alert.is_active 
                          ? 'text-orange-600 dark:text-orange-400' 
                          : 'text-gray-400 dark:text-gray-500'
                      ]"
                    />
                    <div class="flex-1 min-w-0">
                      <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                        {{ getAlertTypeLabel(alert.alert_type, alert.days_before) }}
                      </p>
                      <div class="flex items-center gap-2 mt-1 flex-wrap">
                        <span
                          :class="[
                            'text-xs px-2 py-0.5 rounded-full',
                            alert.triggered_at
                              ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                              : alert.is_active
                                ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400'
                                : 'bg-gray-100 text-gray-600 dark:bg-gray-600 dark:text-gray-300'
                          ]"
                        >
                          {{ alert.triggered_at ? 'Disparado' : alert.is_active ? 'Ativo' : 'Inativo' }}
                        </span>
                        <span v-if="alert.triggered_at" class="text-xs text-gray-500 dark:text-gray-400">
                          em {{ alert.triggered_at }}
                        </span>
                        <span v-if="alert.task_id" class="text-xs text-blue-600 dark:text-blue-400">
                          <Icon icon="lucide:check-circle" class="h-3 w-3 inline-block mr-1" />
                          Tarefa criada
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Tab: Comentários -->
            <div v-if="activeTab === 'comments'">
              <div class="mb-4">
                <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                  Comentários
                </h4>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                  Para adicionar novos comentários, utilize o editor do contrato
                </p>
              </div>

              <div v-if="contract.comments.length === 0" class="text-center py-12 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <Icon icon="lucide:message-square-off" class="h-12 w-12 text-gray-400 mx-auto mb-3" />
                <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum comentário</p>
              </div>

              <div v-else class="space-y-4">
                <div
                  v-for="comment in contract.comments"
                  :key="comment.id"
                  class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg"
                >
                  <div class="flex items-start gap-3 mb-2">
                    <div class="h-8 w-8 rounded-full bg-purple-100 dark:bg-purple-900 flex items-center justify-center flex-shrink-0">
                      <Icon icon="lucide:user" class="h-4 w-4 text-purple-600 dark:text-purple-400" />
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="flex items-center justify-between mb-1">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                          {{ comment.user.name }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                          {{ comment.created_at }}
                        </p>
                      </div>
                      <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ comment.comment }}</p>
                    </div>
                  </div>
                </div>
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

              <div v-else class="space-y-6">
                <div class="flex items-center gap-2 mb-4">
                  <Icon icon="lucide:database" class="h-5 w-5 text-amber-600 dark:text-amber-400" />
                  <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                    Dados da Receita Federal (CNPJ.ws)
                  </h4>
                </div>

                <!-- Dados formatados -->
                <div class="space-y-6">
                  <!-- Seção: Dados Principais -->
                  <div>
                    <h5 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3 flex items-center gap-2">
                      <Icon icon="lucide:building-2" class="h-4 w-4 text-amber-600 dark:text-amber-400" />
                      Dados Principais
                    </h5>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <!-- CNPJ -->
                      <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                          CNPJ
                        </label>
                        <input
                          type="text"
                          :value="formatCnpjFromApi(contract.invoice_cnpj_api_data)"
                          readonly
                          class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md text-sm text-gray-900 dark:text-gray-100"
                        />
                      </div>

                      <!-- Razão Social -->
                      <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                          Razão Social
                        </label>
                        <input
                          type="text"
                          :value="getApiField(contract.invoice_cnpj_api_data, 'razao_social')"
                          readonly
                          class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md text-sm text-gray-900 dark:text-gray-100"
                        />
                      </div>

                      <!-- Capital Social -->
                      <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                          Capital Social
                        </label>
                        <input
                          type="text"
                          :value="formatCurrency(getApiField(contract.invoice_cnpj_api_data, 'capital_social'), 'BRL')"
                          readonly
                          class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md text-sm text-gray-900 dark:text-gray-100"
                        />
                      </div>

                      <!-- Responsável Federativo -->
                      <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                          Responsável Federativo
                        </label>
                        <input
                          type="text"
                          :value="getApiField(contract.invoice_cnpj_api_data, 'responsavel_federativo')"
                          readonly
                          class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md text-sm text-gray-900 dark:text-gray-100"
                        />
                      </div>

                      <!-- Atualizado em -->
                      <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                          Última Atualização
                        </label>
                        <input
                          type="text"
                          :value="formatApiDate(getApiField(contract.invoice_cnpj_api_data, 'atualizado_em'))"
                          readonly
                          class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md text-sm text-gray-900 dark:text-gray-100"
                        />
                      </div>

                      <!-- Porte -->
                      <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                          Porte da Empresa
                        </label>
                        <input
                          type="text"
                          :value="getApiField(contract.invoice_cnpj_api_data, 'porte.descricao')"
                          readonly
                          class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md text-sm text-gray-900 dark:text-gray-100"
                        />
                      </div>

                      <!-- Natureza Jurídica -->
                      <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                          Natureza Jurídica
                        </label>
                        <input
                          type="text"
                          :value="getApiField(contract.invoice_cnpj_api_data, 'natureza_juridica.descricao')"
                          readonly
                          class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md text-sm text-gray-900 dark:text-gray-100"
                        />
                      </div>

                      <!-- Qualificação do Responsável -->
                      <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                          Qualificação do Responsável
                        </label>
                        <input
                          type="text"
                          :value="getApiField(contract.invoice_cnpj_api_data, 'qualificacao_do_responsavel.descricao')"
                          readonly
                          class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md text-sm text-gray-900 dark:text-gray-100"
                        />
                      </div>
                    </div>
                  </div>

                  <!-- Seção: Atividades Econômicas -->
                  <div v-if="getApiAtividades(contract.invoice_cnpj_api_data).principal || getApiAtividades(contract.invoice_cnpj_api_data).secundarias.length > 0">
                    <h5 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3 flex items-center gap-2">
                      <Icon icon="lucide:briefcase" class="h-4 w-4 text-amber-600 dark:text-amber-400" />
                      Atividades Econômicas
                    </h5>
                    <div class="space-y-3">
                      <!-- Atividade Principal -->
                      <div v-if="getApiAtividades(contract.invoice_cnpj_api_data).principal">
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                          Atividade Principal
                        </label>
                        <div class="p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-md">
                          <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                            {{ getApiAtividades(contract.invoice_cnpj_api_data).principal.subclasse }} - {{ getApiAtividades(contract.invoice_cnpj_api_data).principal.descricao }}
                          </p>
                        </div>
                      </div>

                      <!-- Atividades Secundárias -->
                      <div v-if="getApiAtividades(contract.invoice_cnpj_api_data).secundarias.length > 0">
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                          Atividades Secundárias
                        </label>
                        <div class="space-y-2">
                          <div
                            v-for="(atividade, index) in getApiAtividades(contract.invoice_cnpj_api_data).secundarias"
                            :key="index"
                            class="p-3 bg-gray-50 dark:bg-gray-700 rounded-md"
                          >
                            <p class="text-sm text-gray-900 dark:text-gray-100">
                              {{ atividade.subclasse }} - {{ atividade.descricao }}
                            </p>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Seção: Endereço -->
                  <div v-if="getApiField(contract.invoice_cnpj_api_data, 'estabelecimento') !== '-'">
                    <h5 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3 flex items-center gap-2">
                      <Icon icon="lucide:map-pin" class="h-4 w-4 text-amber-600 dark:text-amber-400" />
                      Endereço
                    </h5>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <!-- Tipo e Logradouro -->
                      <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                          Logradouro
                        </label>
                        <input
                          type="text"
                          :value="`${getApiField(contract.invoice_cnpj_api_data, 'estabelecimento.tipo_logradouro')} ${getApiField(contract.invoice_cnpj_api_data, 'estabelecimento.logradouro')}, ${getApiField(contract.invoice_cnpj_api_data, 'estabelecimento.numero')}`"
                          readonly
                          class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md text-sm text-gray-900 dark:text-gray-100"
                        />
                      </div>

                      <!-- Complemento -->
                      <div v-if="getApiField(contract.invoice_cnpj_api_data, 'estabelecimento.complemento') !== '-'">
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                          Complemento
                        </label>
                        <input
                          type="text"
                          :value="getApiField(contract.invoice_cnpj_api_data, 'estabelecimento.complemento')"
                          readonly
                          class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md text-sm text-gray-900 dark:text-gray-100"
                        />
                      </div>

                      <!-- Bairro -->
                      <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                          Bairro
                        </label>
                        <input
                          type="text"
                          :value="getApiField(contract.invoice_cnpj_api_data, 'estabelecimento.bairro')"
                          readonly
                          class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md text-sm text-gray-900 dark:text-gray-100"
                        />
                      </div>

                      <!-- CEP -->
                      <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                          CEP
                        </label>
                        <input
                          type="text"
                          :value="formatCep(getApiField(contract.invoice_cnpj_api_data, 'estabelecimento.cep'))"
                          readonly
                          class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md text-sm text-gray-900 dark:text-gray-100"
                        />
                      </div>

                      <!-- Cidade/UF -->
                      <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                          Cidade / UF
                        </label>
                        <input
                          type="text"
                          :value="`${getApiField(contract.invoice_cnpj_api_data, 'estabelecimento.cidade.nome')} - ${getApiField(contract.invoice_cnpj_api_data, 'estabelecimento.estado.sigla')}`"
                          readonly
                          class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md text-sm text-gray-900 dark:text-gray-100"
                        />
                      </div>
                    </div>
                  </div>

                  <!-- Seção: Contatos -->
                  <div v-if="getApiContatos(contract.invoice_cnpj_api_data).length > 0">
                    <h5 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3 flex items-center gap-2">
                      <Icon icon="lucide:phone" class="h-4 w-4 text-amber-600 dark:text-amber-400" />
                      Contatos
                    </h5>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                      <div
                        v-for="(contato, index) in getApiContatos(contract.invoice_cnpj_api_data)"
                        :key="index"
                        class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-md"
                      >
                        <Icon :icon="contato.icon" class="h-4 w-4 text-gray-400 flex-shrink-0" />
                        <div>
                          <p class="text-xs text-gray-500 dark:text-gray-400">{{ contato.tipo }}</p>
                          <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ contato.valor }}</p>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Seção: Inscrições -->
                  <div v-if="getApiInscricoes(contract.invoice_cnpj_api_data).length > 0">
                    <h5 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3 flex items-center gap-2">
                      <Icon icon="lucide:file-text" class="h-4 w-4 text-amber-600 dark:text-amber-400" />
                      Inscrições
                    </h5>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div
                        v-for="(inscricao, index) in getApiInscricoes(contract.invoice_cnpj_api_data)"
                        :key="index"
                      >
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                          {{ inscricao.tipo }}
                        </label>
                        <input
                          type="text"
                          :value="inscricao.numero"
                          readonly
                          class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md text-sm text-gray-900 dark:text-gray-100"
                        />
                      </div>
                    </div>
                  </div>

                  <!-- Seção: Sócios -->
                  <div v-if="getApiSocios(contract.invoice_cnpj_api_data).length > 0">
                    <h5 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3 flex items-center gap-2">
                      <Icon icon="lucide:users" class="h-4 w-4 text-amber-600 dark:text-amber-400" />
                      Sócios e Administradores
                    </h5>
                    <div class="space-y-2">
                      <div
                        v-for="(socio, index) in getApiSocios(contract.invoice_cnpj_api_data)"
                        :key="index"
                        class="flex items-start gap-3 p-4 bg-gray-50 dark:bg-gray-700 rounded-md border border-gray-200 dark:border-gray-600"
                      >
                        <Icon icon="lucide:user" class="h-5 w-5 text-gray-400 flex-shrink-0 mt-0.5" />
                        <div class="flex-1 min-w-0">
                          <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                            {{ socio.nome }}
                          </p>
                          <div class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                            <span>{{ socio.tipo }}</span>
                            <span v-if="socio.cpf_cnpj_socio">CPF/CNPJ: {{ formatCpfCnpj(socio.cpf_cnpj_socio) }}</span>
                            <span v-if="socio.qualificacao">{{ socio.qualificacao }}</span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- JSON Completo (Collapsible) -->
                  <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                    <details class="group">
                      <summary class="cursor-pointer flex items-center gap-2 text-xs font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">
                        <Icon icon="lucide:chevron-right" class="h-4 w-4 transition-transform group-open:rotate-90" />
                        Ver JSON completo da API
                      </summary>
                      <pre class="mt-3 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg text-xs text-gray-800 dark:text-gray-200 overflow-auto max-h-96 border border-gray-200 dark:border-gray-700">{{ formatJson(contract.invoice_cnpj_api_data) }}</pre>
                    </details>
                  </div>
                </div>
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

            <!-- Tab: Relatório -->
            <div v-if="activeTab === 'report'">
              <!-- Indicadores Resumidos -->
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <!-- Card: Valor do Contrato -->
                <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                  <span class="text-xs font-medium text-gray-600 dark:text-gray-400">
                    Valor do Contrato
                  </span>
                  <p class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">
                    {{ formatCurrency(report.financial.contract_amount) }}
                  </p>
                </div>

                <!-- Card: Saldo Liquidado -->
                <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                  <span class="text-xs font-medium text-green-600 dark:text-green-400">
                    Saldo Liquidado
                  </span>
                  <p class="mt-1 text-sm font-medium text-green-900 dark:text-green-100">
                    {{ formatCurrency(report.financial.total_paid) }}
                  </p>
                  <p class="text-xs text-green-600 dark:text-green-400 mt-1">
                    {{ report.financial.liquidated_percentage.toFixed(1) }}%
                  </p>
                </div>

                <!-- Card: Saldo a Liquidar -->
                <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
                  <span class="text-xs font-medium text-amber-600 dark:text-amber-400">
                    Saldo a Liquidar
                  </span>
                  <p class="mt-1 text-sm font-medium text-amber-900 dark:text-amber-100">
                    {{ formatCurrency(report.financial.remaining_amount) }}
                  </p>
                  <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">
                    {{ (100 - report.financial.liquidated_percentage).toFixed(1) }}%
                  </p>
                </div>

                <!-- Card: Tempo Restante -->
                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                  <span class="text-xs font-medium text-blue-600 dark:text-blue-400">
                    {{ report.time.is_expired ? 'Contrato Expirado' : 'Tempo Restante' }}
                  </span>
                  <p class="mt-1 text-sm font-medium text-blue-900 dark:text-blue-100">
                    {{ Math.round(report.time.remaining_days) }} dias
                  </p>
                  <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                    {{ (100 - report.time.time_elapsed_percentage).toFixed(1) }}%
                  </p>
                </div>
              </div>

              <!-- Gráficos -->
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Gráfico: Progresso de Tempo -->
                <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                  <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">
                    Progresso de Tempo
                  </h4>
                  <div class="h-40 flex items-center justify-center">
                    <DoughnutChart :data="timeChartData" />
                  </div>
                  <p class="mt-3 text-center text-xs text-gray-600 dark:text-gray-400">
                    {{ Math.round(report.time.elapsed_days) }} de {{ Math.round(report.time.total_days) }} dias
                  </p>
                </div>

                <!-- Gráfico: Notas Fiscais -->
                <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                  <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">
                    Notas Fiscais
                  </h4>
                  <div class="h-40 flex items-center justify-center">
                    <DoughnutChart :data="invoicesChartData" />
                  </div>
                  <p class="mt-3 text-center text-xs text-gray-600 dark:text-gray-400">
                    {{ report.invoices.paid }} de {{ report.invoices.total }} pagas
                  </p>
                </div>

                <!-- Gráfico: Execução Financeira (Barra Vertical) -->
                <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                  <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">
                    Execução Financeira
                  </h4>
                  <div class="h-40">
                    <BarChart :data="financialChartData" />
                  </div>
                  <div class="mt-3 space-y-1 text-xs">
                    <div class="flex items-center justify-between">
                      <div class="flex items-center">
                        <div class="w-2 h-2 bg-green-500 rounded-full mr-1"></div>
                        <span class="text-gray-600 dark:text-gray-400">Liquidado</span>
                      </div>
                      <span class="text-gray-900 dark:text-gray-100 font-medium">{{ formatCurrency(report.financial.total_paid) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                      <div class="flex items-center">
                        <div class="w-2 h-2 bg-amber-500 rounded-full mr-1"></div>
                        <span class="text-gray-600 dark:text-gray-400">Pendente</span>
                      </div>
                      <span class="text-gray-900 dark:text-gray-100 font-medium">{{ formatCurrency(report.financial.pending_payment) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                      <div class="flex items-center">
                        <div class="w-2 h-2 bg-gray-300 rounded-full mr-1"></div>
                        <span class="text-gray-600 dark:text-gray-400">Disponível</span>
                      </div>
                      <span class="text-gray-900 dark:text-gray-100 font-medium">{{ formatCurrency(Math.max(0, report.financial.remaining_amount - report.financial.pending_payment)) }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
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
import DoughnutChart from '@/components/charts/DoughnutChart.vue'
import BarChart from '@/components/charts/BarChart.vue'

const props = defineProps({
  contract: Object,
  report: Object,
})

const activeTab = ref('info')
const showAlertModal = ref(false)

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

const getAlertTypeLabel = (type, daysBefore = null) => {
  if (type === 'before_expiration' && daysBefore) {
    return `${daysBefore} dias antes do vencimento`
  }
  
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

const confirmDelete = () => {
  if (confirm(`Tem certeza que deseja excluir este contrato?\n\nEsta ação não pode ser desfeita.`)) {
    router.delete(route('contracts.destroy', props.contract.id))
  }
}

const formatJson = (data) => {
  if (!data) return 'Nenhum dado disponível'
  
  try {
    // Se já for string, tenta fazer parse
    const obj = typeof data === 'string' ? JSON.parse(data) : data
    return JSON.stringify(obj, null, 2)
  } catch (e) {
    return typeof data === 'string' ? data : JSON.stringify(data, null, 2)
  }
}

// Funções para extrair dados da API CNPJ
const getApiField = (data, path) => {
  if (!data) return '-'
  
  try {
    const obj = typeof data === 'string' ? JSON.parse(data) : data
    const keys = path.split('.')
    let value = obj
    
    for (const key of keys) {
      value = value?.[key]
      if (value === undefined || value === null) return '-'
    }
    
    return value || '-'
  } catch (e) {
    return '-'
  }
}

const formatCnpjFromApi = (data) => {
  const cnpj = getApiField(data, 'cnpj_raiz')
  if (cnpj === '-') return '-'
  return formatCpfCnpj(cnpj)
}

const formatApiDate = (dateString) => {
  if (!dateString || dateString === '-') return '-'
  
  try {
    const date = new Date(dateString)
    return date.toLocaleString('pt-BR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  } catch (e) {
    return dateString
  }
}

const getApiSocios = (data) => {
  if (!data) return []
  
  try {
    const obj = typeof data === 'string' ? JSON.parse(data) : data
    const socios = obj?.socios || []
    
    return socios.map(socio => ({
      nome: socio.nome || 'Não informado',
      tipo: socio.tipo || 'Pessoa Jurídica',
      cpf_cnpj_socio: socio.cpf_cnpj_socio || '',
      qualificacao: socio.qualificacao_socio?.descricao || ''
    }))
  } catch (e) {
    return []
  }
}

const getApiAtividades = (data) => {
  if (!data) return { principal: null, secundarias: [] }
  
  try {
    const obj = typeof data === 'string' ? JSON.parse(data) : data
    const estabelecimento = obj?.estabelecimento || {}
    
    return {
      principal: estabelecimento.atividade_principal ? {
        subclasse: estabelecimento.atividade_principal.subclasse || '',
        descricao: estabelecimento.atividade_principal.descricao || ''
      } : null,
      secundarias: (estabelecimento.atividades_secundarias || []).map(ativ => ({
        subclasse: ativ.subclasse || '',
        descricao: ativ.descricao || ''
      }))
    }
  } catch (e) {
    return { principal: null, secundarias: [] }
  }
}

const getApiContatos = (data) => {
  if (!data) return []
  
  try {
    const obj = typeof data === 'string' ? JSON.parse(data) : data
    const estabelecimento = obj?.estabelecimento || {}
    const contatos = []
    
    // Telefone 1
    if (estabelecimento.ddd1 && estabelecimento.telefone1) {
      contatos.push({
        tipo: 'Telefone',
        valor: `(${estabelecimento.ddd1}) ${estabelecimento.telefone1}`,
        icon: 'lucide:phone'
      })
    }
    
    // Telefone 2
    if (estabelecimento.ddd2 && estabelecimento.telefone2) {
      contatos.push({
        tipo: 'Telefone 2',
        valor: `(${estabelecimento.ddd2}) ${estabelecimento.telefone2}`,
        icon: 'lucide:phone'
      })
    }
    
    // Fax
    if (estabelecimento.ddd_fax && estabelecimento.fax) {
      contatos.push({
        tipo: 'Fax',
        valor: `(${estabelecimento.ddd_fax}) ${estabelecimento.fax}`,
        icon: 'lucide:printer'
      })
    }
    
    // Email
    if (estabelecimento.email) {
      contatos.push({
        tipo: 'E-mail',
        valor: estabelecimento.email,
        icon: 'lucide:mail'
      })
    }
    
    return contatos
  } catch (e) {
    return []
  }
}

const getApiInscricoes = (data) => {
  if (!data) return []
  
  try {
    const obj = typeof data === 'string' ? JSON.parse(data) : data
    const estabelecimento = obj?.estabelecimento || {}
    const inscricoes = []
    
    // Inscrição Estadual
    if (estabelecimento.inscricoes_estaduais && estabelecimento.inscricoes_estaduais.length > 0) {
      estabelecimento.inscricoes_estaduais.forEach(insc => {
        if (insc.inscricao_estadual) {
          inscricoes.push({
            tipo: `Inscrição Estadual ${insc.estado?.sigla || ''}`.trim(),
            numero: insc.inscricao_estadual
          })
        }
      })
    }
    
    return inscricoes
  } catch (e) {
    return []
  }
}

const formatCep = (cep) => {
  if (!cep || cep === '-') return '-'
  
  // Remove tudo que não é dígito
  const numbers = cep.replace(/\D/g, '')
  
  // Formata: 00000-000
  if (numbers.length === 8) {
    return numbers.replace(/(\d{5})(\d{3})/, '$1-$2')
  }
  
  return cep
}

// Computed properties para gráficos do relatório
const timeChartData = computed(() => ({
  labels: ['Tempo Decorrido', 'Tempo Restante'],
  datasets: [{
    data: [
      props.report.time.elapsed_days,
      props.report.time.remaining_days,
    ],
    backgroundColor: [
      'rgb(34, 197, 94)', // green-500
      'rgb(229, 231, 235)', // gray-200
    ],
    borderWidth: 0,
  }],
}))

const invoicesChartData = computed(() => ({
  labels: ['Notas Pagas', 'Notas a Pagar'],
  datasets: [{
    data: [
      props.report.invoices.paid,
      props.report.invoices.unpaid,
    ],
    backgroundColor: [
      'rgb(34, 197, 94)', // green-500
      'rgb(251, 191, 36)', // amber-400
    ],
    borderWidth: 0,
  }],
}))

const financialChartData = computed(() => {
  const available = Math.max(0, props.report.financial.remaining_amount - props.report.financial.pending_payment)
  
  return {
    labels: ['Saldo do Contrato'],
    datasets: [
      {
        label: 'Liquidado',
        data: [props.report.financial.total_paid],
        backgroundColor: 'rgb(34, 197, 94)', // green-500
      },
      {
        label: 'Pendente de Pagamento',
        data: [props.report.financial.pending_payment],
        backgroundColor: 'rgb(251, 191, 36)', // amber-400
      },
      {
        label: 'Disponível',
        data: [available],
        backgroundColor: 'rgb(229, 231, 235)', // gray-200
      },
    ],
  }
})

</script>
