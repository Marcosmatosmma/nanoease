<template>
  <AppLayout :title="`Editar: ${contract.name}`">
    <div class="space-y-6">
      <!-- Header com botões -->
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
            Editar Contrato
          </h2>
          <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ contract.name }}
          </p>
        </div>
        <div class="flex gap-2">
          <Button variant="outline" @click="$inertia.visit(route('contracts.show', contract.id))">
            <Icon icon="lucide:x" class="h-4 w-4 mr-2" />
            Cancelar
          </Button>
          <Button @click="submit" :disabled="form.processing">
            <Icon
              :icon="form.processing ? 'lucide:loader-2' : 'lucide:save'"
              :class="['h-4 w-4 mr-2', { 'animate-spin': form.processing }]"
            />
            Salvar Alterações
          </Button>
        </div>
      </div>

      <!-- Sistema de Abas -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <!-- Tabs Navigation -->
        <div class="border-b border-gray-200 dark:border-gray-700">
          <nav class="flex -mb-px overflow-x-auto">
            <!-- Tab: Informações -->
            <button
                @click="activeTab = 'info'"
                :class="[
                  'flex-1 py-4 px-6 text-center border-b-2 font-medium text-sm transition-colors whitespace-nowrap',
                  activeTab === 'info'
                    ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                ]"
              >
                <Icon icon="lucide:info" class="h-4 w-4 inline-block mr-2" />
                Informações
              </button>

              <!-- Tab: Nota Fiscal -->
              <button

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
                    ? 'border-purple-500 text-purple-600 dark:text-purple-400'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                ]"
              >
                <Icon icon="lucide:file-text" class="h-4 w-4 inline-block mr-2" />
                Documentos
                <span v-if="contract.documents && contract.documents.length > 0" class="ml-1 text-xs bg-purple-100 dark:bg-purple-900 px-2 py-0.5 rounded-full">
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
                <span v-if="contract.alerts && contract.alerts.length > 0" class="ml-1 text-xs bg-orange-100 dark:bg-orange-900 px-2 py-0.5 rounded-full">
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
                <span v-if="contract.comments && contract.comments.length > 0" class="ml-1 text-xs bg-purple-100 dark:bg-purple-900 px-2 py-0.5 rounded-full">
                  {{ contract.comments.length }}
                </span>
              </button>
            </nav>
          </div>

          <!-- Tab Content -->
          <div class="p-6">
            <!-- Tab: Informações -->
            <div v-if="activeTab === 'info'" class="space-y-6">
              <!-- Informações Básicas -->
              <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                  Informações Básicas
                </h3>
                <div class="space-y-4">
                  <!-- Nome do Contrato -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      Nome do Contrato *
                    </label>
                    <Input
                      v-model="form.name"
                      placeholder="Ex: Contrato de Prestação de Serviços - Empresa X"
                      :error="form.errors.name"
                      required
                    />
                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                      {{ form.errors.name }}
                    </p>
                  </div>

                  <!-- Meu Papel -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      Meu Papel neste Contrato
                    </label>
                    <select
                      v-model="form.my_role"
                      class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                      <option value="">Selecione...</option>
                      <option value="contratante">Sou o contratante (quem contratou o serviço)</option>
                      <option value="contratado">Sou o contratado (quem vai prestar o serviço)</option>
                    </select>
                  </div>

                  <!-- Tipo do Contrato -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      Tipo de Contrato
                    </label>
                    <select
                      v-model="form.contract_type"
                      class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                      <option value="">Selecione...</option>
                      <option value="Prestação de Serviços">Prestação de Serviços</option>
                      <option value="Aluguel">Aluguel</option>
                      <option value="SaaS">SaaS</option>
                      <option value="Fornecimento">Fornecimento</option>
                      <option value="Trabalho">Trabalho</option>
                      <option value="Outro">Outro</option>
                    </select>
                  </div>

                  <!-- Objeto do Contrato -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      Objeto do Contrato
                    </label>
                    <Input
                      v-model="form.contract_object"
                      placeholder="Ex: Desenvolvimento de sistema web"
                    />
                  </div>

                  <!-- Número do Contrato -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      Número do Contrato
                    </label>
                    <Input
                      v-model="form.contract_number"
                      placeholder="Ex: CT-2025-001"
                    />
                  </div>
                </div>
              </div>

              <!-- Partes Envolvidas -->
              <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                  Partes Envolvidas
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <!-- Contratante -->
                  <div class="space-y-4">
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                      <Icon icon="lucide:users" class="h-4 w-4 inline-block mr-1" />
                      Contratante
                    </h4>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Razão Social / Nome
                      </label>
                      <Input
                        v-model="form.contractor"
                        placeholder="Nome do contratante"
                      />
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        CPF/CNPJ
                      </label>
                      <Input
                        v-model="form.contractor_cpf_cnpj"
                        placeholder="000.000.000-00 ou 00.000.000/0000-00"
                      />
                    </div>
                  </div>

                  <!-- Contratado -->
                  <div class="space-y-4">
                    <div class="flex items-center justify-between">
                      <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        <Icon icon="lucide:briefcase" class="h-4 w-4 inline-block mr-1" />
                        Contratado <span v-if="form.my_role === 'contratante'">(Fornecedor)</span>
                      </h4>
                      <span v-if="form.my_role === 'contratante'" class="text-xs text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 px-2 py-1 rounded">
                        Chave de acesso ao portal
                      </span>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Razão Social / Nome *
                      </label>
                      <Input
                        v-model="form.contracted"
                        placeholder="Nome do contratado"
                        :required="form.my_role === 'contratante'"
                      />
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        CPF/CNPJ *
                      </label>
                      <Input
                        v-model="form.contracted_cpf_cnpj"
                        placeholder="000.000.000-00 ou 00.000.000/0000-00"
                        :required="form.my_role === 'contratante'"
                      />
                      <p v-if="form.my_role === 'contratante'" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Este CNPJ será usado pelo fornecedor para acessar o portal de envio de notas.
                      </p>
                    </div>

                    <!-- Dia do Envio da NF (Apenas para Contratante) -->
                    <div v-if="form.my_role === 'contratante'">
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Dia Limite para Envio da NF
                      </label>
                      <Input
                        v-model.number="form.invoice_due_day"
                        type="number"
                        min="1"
                        max="31"
                        placeholder="Ex: 20"
                      />
                      <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Dia limite para o fornecedor subir a nota fiscal.
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Datas e Vigência -->
              <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                  Datas e Vigência
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      Data de Início *
                    </label>
                    <Input
                      type="date"
                      v-model="form.start_date"
                      required
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      Data de Término
                    </label>
                    <Input
                      type="date"
                      v-model="form.end_date"
                    />
                  </div>
                  <div class="md:col-span-2">
                    <label class="flex items-center">
                      <input
                        type="checkbox"
                        v-model="form.auto_renewal"
                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                      />
                      <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                        Renovação Automática
                      </span>
                    </label>
                  </div>
                </div>
              </div>

              <!-- Valores e Condições Financeiras -->
              <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                  Valores e Condições Financeiras
                </h3>
                <div class="space-y-4">
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Valor do Contrato
                      </label>
                      <Input
                        type="number"
                        step="0.01"
                        v-model="form.amount"
                        placeholder="0.00"
                      />
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Moeda
                      </label>
                      <select
                        v-model="form.currency"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                      >
                        <option value="BRL">Real (BRL)</option>
                        <option value="USD">Dólar (USD)</option>
                        <option value="EUR">Euro (EUR)</option>
                      </select>
                    </div>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      Condições de Pagamento
                    </label>
                    <textarea
                      v-model="form.payment_terms"
                      rows="4"
                      class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                      placeholder="Descreva as condições de pagamento..."
                    />
                  </div>
                </div>
              </div>

              <!-- Status -->
              <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                  Status
                </h3>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Status do Contrato
                  </label>
                  <select
                    v-model="form.status"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                  >
                    <option value="ativo">Ativo</option>
                    <option value="vencido">Vencido</option>
                    <option value="encerrado">Encerrado</option>
                  </select>
                </div>
              </div>

              <!-- Observações -->
              <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                  Observações
                </h3>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Observações Gerais
                  </label>
                  <textarea
                    v-model="form.notes"
                    rows="4"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                    placeholder="Observações adicionais sobre o contrato..."
                  />
                </div>
              </div>
            </div>

            <!-- Tab: Nota Fiscal -->
            <div v-if="activeTab === 'invoice'" class="space-y-6">
              <div v-if="form.my_role === 'contratado'" class="space-y-6">
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-4">

                Configure as informações necessárias para emissão de Notas Fiscais
              </div>

              <!-- Contatos para Envio -->
              <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                  Contatos para Envio
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      Email para NF
                    </label>
                    <Input
                      type="email"
                      v-model="form.invoice_contact_email"
                      placeholder="fiscal@empresa.com.br"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      Link/Portal para Envio
                    </label>
                    <Input
                      v-model="form.invoice_contact_link"
                      placeholder="https://portal.empresa.com.br"
                    />
                  </div>
                </div>
              </div>

              <!-- Sistema e Código -->
              <div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      Sistema de Cadastro/Gestão
                    </label>
                    <Input
                      v-model="form.invoice_system"
                      placeholder="Ex: SAP, Totvs..."
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      Código do Serviço (ISS)
                    </label>
                    <Input
                      v-model="form.invoice_service_code"
                      placeholder="Ex: 01.07"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      Dia do Envio da NF
                    </label>
                    <Input
                      type="number"
                      min="1"
                      max="31"
                      v-model="form.invoice_due_day"
                      placeholder="Ex: 20"
                    />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                      Dia do mês para envio da NF
                    </p>
                  </div>
                </div>
              </div>

              <!-- Destinatário da NF -->
              <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                  Destinatário da Nota Fiscal
                </h3>
                <div class="space-y-4">
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Nome/Razão Social
                      </label>
                      <Input
                        v-model="form.invoice_recipient_name"
                        placeholder="Razão Social do destinatário"
                      />
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        CNPJ do Destinatário
                      </label>
                      <div class="flex gap-2">
                        <Input
                          v-model="form.invoice_recipient_cnpj"
                          placeholder="00.000.000/0000-00"
                          class="flex-1"
                        />
                        <Button
                          type="button"
                          variant="outline"
                          @click="fetchCnpjData"
                          :disabled="loadingCnpjData || !form.invoice_recipient_cnpj"
                        >
                          <Icon
                            :icon="loadingCnpjData ? 'lucide:loader-2' : 'lucide:search'"
                            :class="['h-4 w-4', { 'animate-spin': loadingCnpjData }]"
                          />
                        </Button>
                      </div>
                      <p v-if="cnpjApiMessage" class="mt-1 text-xs text-green-600">
                        {{ cnpjApiMessage }}
                      </p>
                    </div>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      Inscrição Estadual
                    </label>
                    <Input
                      v-model="form.invoice_state_registration"
                      placeholder="Inscrição Estadual"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      Endereço Completo
                    </label>
                    <textarea
                      v-model="form.invoice_recipient_address"
                      rows="2"
                      class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                      placeholder="Rua, número, bairro, cidade, estado, CEP"
                    />
                  </div>
                </div>
              </div>

              <!-- Descrição para NF -->
              <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                  Descrição para Nota Fiscal
                </h3>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Descrição/Discriminação do Serviço
                  </label>
                  <textarea
                    v-model="form.invoice_description"
                    rows="4"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                    placeholder="Descrição detalhada do serviço para a NF..."
                  />
                </div>
              </div>

              <!-- Observações Internas -->
              <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                  Observações Internas
                </h3>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Observações Internas sobre Emissão de NF
                  </label>
                  <textarea
                    v-model="form.invoice_internal_notes"
                    rows="3"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                    placeholder="Observações internas (não aparecem na NF)..."
                  />
                </div>
              </div>

              </div>

              <!-- Histórico de Notas Fiscais Emitidas -->
              <div class="border-t border-gray-200 dark:border-gray-700 pt-6">

                <div class="flex items-center justify-between mb-4">
                  <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                    Notas Fiscais Emitidas
                  </h4>
                  <Button size="sm" type="button" @click="showInvoiceUploadModal = true">
                    <Icon icon="lucide:upload" class="h-4 w-4 mr-2" />
                    Nova Nota Fiscal
                  </Button>
                </div>

                <div v-if="!contract.invoices || contract.invoices.length === 0" class="text-center py-8 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
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
                            <p class="text-sm text-gray-900 dark:text-gray-100">
                              {{ invoice.uploaded_by ? invoice.uploaded_by.name : '-' }}
                            </p>
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
                          @click="openEditInvoice(invoice)"
                          class="flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-md transition-colors"
                        >
                          <Icon icon="lucide:edit" class="h-3 w-3" />
                          Editar
                        </button>
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
                <div>
                  <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                    Documentos do Contrato
                  </h4>
                  <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Faça upload de documentos relacionados ao contrato (PDF, DOC, DOCX)
                  </p>
                </div>
                <Button size="sm" type="button" @click="showDocumentUploadModal = true">
                  <Icon icon="lucide:upload" class="h-4 w-4 mr-2" />
                  Novo Documento
                </Button>
              </div>

              <div v-if="!contract.documents || contract.documents.length === 0" class="text-center py-12 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <Icon icon="lucide:file-text" class="h-12 w-12 text-gray-400 mx-auto mb-3" />
                <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum documento enviado ainda</p>
              </div>

              <div v-else class="space-y-2">
                <div
                  v-for="document in contract.documents"
                  :key="document.id"
                  class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
                >
                  <div class="flex items-center gap-3 flex-1 min-w-0">
                    <Icon
                      icon="lucide:file-text"
                      class="h-6 w-6 text-blue-600 dark:text-blue-400 flex-shrink-0"
                    />
                    <div class="flex-1 min-w-0">
                      <p class="text-sm font-medium text-gray-900 dark:text-gray-100 break-words">
                        {{ document.file_name }}
                      </p>
                    </div>
                  </div>
                  <div class="flex items-center gap-2 flex-shrink-0 ml-4">
                    <a
                      :href="route('contracts.documents.download', document.id)"
                      class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-md transition-colors"
                    >
                      <Icon icon="lucide:download" class="h-4 w-4" />
                      Baixar
                    </a>
                    <button
                      @click="confirmDeleteDocument(document.id)"
                      class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-md transition-colors"
                    >
                      <Icon icon="lucide:trash-2" class="h-4 w-4" />
                      Excluir
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Tab: Alertas -->
            <div v-if="activeTab === 'alerts'">
              <div class="mb-6">
                <div class="flex items-center justify-between mb-4">
                  <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                      Alertas de Vencimento
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                      Configure alertas automáticos para não perder nenhum prazo importante
                    </p>
                  </div>
                  <Button @click="showAlertModal = true">
                    <Icon icon="lucide:bell-plus" class="h-4 w-4 mr-2" />
                    Novo Alerta
                  </Button>
                </div>

                <!-- Estado vazio -->
                <div v-if="contract.alerts.length === 0" class="text-center py-12 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                  <Icon icon="lucide:bell-off" class="h-12 w-12 text-gray-400 mx-auto mb-3" />
                  <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Nenhum alerta configurado</p>
                  <p class="text-xs text-gray-400 dark:text-gray-500 mb-4">
                    Crie alertas para ser notificado antes do vencimento
                  </p>
                  <Button @click="showAlertModal = true" variant="outline" size="sm">
                    <Icon icon="lucide:bell-plus" class="h-4 w-4 mr-2" />
                    Criar Primeiro Alerta
                  </Button>
                </div>

                <!-- Lista de Alertas -->
                <div v-else class="space-y-2">
                  <div
                    v-for="alert in contract.alerts"
                    :key="alert.id"
                    class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
                  >
                    <div class="flex items-center gap-3 flex-1 min-w-0">
                      <Icon
                        :icon="alert.is_active ? 'lucide:bell' : 'lucide:bell-off'"
                        :class="[
                          'h-6 w-6 flex-shrink-0',
                          alert.is_active 
                            ? 'text-blue-600 dark:text-blue-400' 
                            : 'text-gray-400 dark:text-gray-500'
                        ]"
                      />
                      <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                          {{ getAlertLabel(alert) }}
                        </p>
                        <div class="flex items-center gap-2 mt-1">
                          <span
                            :class="[
                              'text-xs px-2 py-0.5 rounded-full',
                              alert.is_active
                                ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                : 'bg-gray-100 text-gray-600 dark:bg-gray-600 dark:text-gray-300'
                            ]"
                          >
                            {{ alert.is_active ? 'Ativo' : 'Inativo' }}
                          </span>
                          <span v-if="alert.triggered_at" class="text-xs text-gray-500 dark:text-gray-400">
                            Disparado em {{ alert.triggered_at }}
                          </span>
                          <span v-if="alert.task_id" class="text-xs text-blue-600 dark:text-blue-400">
                            <Icon icon="lucide:check-circle" class="h-3 w-3 inline-block mr-1" />
                            Tarefa criada
                          </span>
                        </div>
                      </div>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0 ml-4">
                      <button
                        @click="toggleAlert(alert)"
                        :title="alert.is_active ? 'Desativar' : 'Ativar'"
                        class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-md transition-colors"
                      >
                        <Icon 
                          :icon="alert.is_active ? 'lucide:toggle-right' : 'lucide:toggle-left'" 
                          class="h-5 w-5" 
                        />
                      </button>
                      <button
                        v-if="!alert.task_id"
                        @click="createTaskFromAlert(alert)"
                        title="Criar Tarefa"
                        class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-md transition-colors"
                      >
                        <Icon icon="lucide:list-todo" class="h-4 w-4" />
                      </button>
                      <button
                        @click="confirmDeleteAlert(alert.id)"
                        title="Excluir"
                        class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-md transition-colors"
                      >
                        <Icon icon="lucide:trash-2" class="h-4 w-4" />
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Tab: Comentários -->
            <div v-if="activeTab === 'comments'">
              <div class="mb-6">
                <div class="mb-4">
                  <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    Comentários
                  </h3>
                  <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Adicione comentários para registrar informações importantes sobre este contrato
                  </p>
                </div>

                <!-- Formulário de Novo Comentário -->
                <form @submit.prevent="addComment" class="mb-6">
                  <div class="flex gap-3">
                    <textarea
                      v-model="newComment"
                      rows="3"
                      class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-gray-100 resize-none"
                      placeholder="Digite seu comentário..."
                      required
                    ></textarea>
                    <div class="flex flex-col gap-2">
                      <Button type="submit" :disabled="!newComment.trim() || addingComment">
                        <Icon
                          v-if="addingComment"
                          icon="lucide:loader-2"
                          class="h-4 w-4 animate-spin"
                        />
                        <Icon v-else icon="lucide:send" class="h-4 w-4" />
                      </Button>
                    </div>
                  </div>
                </form>

                <!-- Lista de Comentários -->
                <div v-if="contract.comments.length === 0" class="text-center py-12 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                  <Icon icon="lucide:message-square-off" class="h-12 w-12 text-gray-400 mx-auto mb-3" />
                  <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum comentário ainda</p>
                  <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                    Seja o primeiro a comentar
                  </p>
                </div>

                <div v-else class="space-y-4">
                  <div
                    v-for="comment in contract.comments"
                    :key="comment.id"
                    class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg"
                  >
                    <div class="flex items-start justify-between mb-2">
                      <div class="flex items-center gap-2">
                        <div class="h-8 w-8 rounded-full bg-purple-100 dark:bg-purple-900 flex items-center justify-center">
                          <Icon icon="lucide:user" class="h-4 w-4 text-purple-600 dark:text-purple-400" />
                        </div>
                        <div>
                          <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                            {{ comment.user.name }}
                          </p>
                          <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ comment.created_at }}
                          </p>
                        </div>
                      </div>
                      <button
                        v-if="comment.is_author"
                        @click="confirmDeleteComment(comment.id)"
                        class="text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 p-2 rounded-md transition-colors"
                        title="Excluir comentário"
                      >
                        <Icon icon="lucide:trash-2" class="h-4 w-4" />
                      </button>
                    </div>
                    <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ comment.comment }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
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

        <!-- Tabs: Upload Individual ou CSV -->
        <div class="flex border-b border-gray-200 dark:border-gray-700 mb-4">
          <button
            @click="invoiceUploadTab = 'individual'"
            :class="[
              'px-4 py-2 text-sm font-medium border-b-2 transition-colors',
              invoiceUploadTab === 'individual'
                ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'
            ]"
          >
            <Icon icon="lucide:file-text" class="h-4 w-4 inline-block mr-2" />
            Upload Individual
          </button>
          <button
            @click="invoiceUploadTab = 'csv'"
            :class="[
              'px-4 py-2 text-sm font-medium border-b-2 transition-colors',
              invoiceUploadTab === 'csv'
                ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'
            ]"
          >
            <Icon icon="lucide:table" class="h-4 w-4 inline-block mr-2" />
            Importar CSV
          </button>
        </div>

        <!-- Tab: Upload Individual -->
        <form v-if="invoiceUploadTab === 'individual'" @submit.prevent="uploadInvoice" class="space-y-4">
          <!-- Aviso -->
          <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-700">
            <p class="text-sm text-blue-800 dark:text-blue-200">
              <Icon icon="lucide:info" class="h-4 w-4 inline-block mr-1" />
              PDF e XML são opcionais. Preencha os dados manualmente ou envie arquivos para extração automática.
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

          <!-- Dados da NF (obrigatórios) -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Número da NF <span class="text-red-600">*</span>
              </label>
              <Input v-model="invoiceForm.invoice_number" placeholder="Ex: 123456" required />
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
                Valor <span class="text-red-600">*</span>
              </label>
              <Input v-model="invoiceForm.amount" type="number" step="0.01" placeholder="0.00" required />
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

          <!-- Status de Pagamento -->
          <div class="flex items-center gap-4 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
            <label class="flex items-center gap-2 cursor-pointer">
              <input
                v-model="invoiceForm.is_paid"
                type="checkbox"
                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
              />
              <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                Nota fiscal paga
              </span>
            </label>
            
            <div v-if="invoiceForm.is_paid" class="flex-1">
              <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                Data do pagamento
              </label>
              <input
                v-model="invoiceForm.paid_at"
                type="date"
                class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
              />
            </div>
          </div>

          <div class="flex justify-end gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
            <Button type="button" variant="outline" @click="cancelInvoiceUpload">
              Cancelar
            </Button>
            <Button type="submit" :disabled="uploadingInvoice">
              <Icon
                v-if="uploadingInvoice"
                icon="lucide:loader-2"
                class="h-4 w-4 mr-2 animate-spin"
              />
              Enviar Nota Fiscal
            </Button>
          </div>
        </form>

        <!-- Tab: Importar CSV -->
        <form v-else @submit.prevent="importInvoicesCsv" class="space-y-4">
          <!-- Instruções -->
          <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-700">
            <h4 class="text-sm font-semibold text-amber-900 dark:text-amber-200 mb-2">
              <Icon icon="lucide:info" class="h-4 w-4 inline-block mr-1" />
              Formato do CSV
            </h4>
            <p class="text-xs text-amber-800 dark:text-amber-300 mb-2">
              O arquivo CSV deve conter as seguintes colunas (cabeçalho obrigatório):
            </p>
            <div class="bg-white dark:bg-gray-800 p-2 rounded text-xs font-mono">
              numero_nf;valor;data_emissao;data_vencimento;descricao;pago;data_pagamento
            </div>
            <ul class="mt-2 text-xs text-amber-800 dark:text-amber-300 space-y-1">
              <li>• <strong>numero_nf</strong> e <strong>valor</strong> são obrigatórios</li>
              <li>• Datas no formato: DD/MM/AAAA ou AAAA-MM-DD</li>
              <li>• Valor com ponto ou vírgula como decimal (ex: 1000.50 ou 1000,50)</li>
              <li>• <strong>pago</strong>: sim/não, s/n, 1/0 (opcional)</li>
              <li>• <strong>data_pagamento</strong>: data do pagamento se pago=sim (opcional)</li>
            </ul>
          </div>

          <!-- Download template -->
          <div class="flex items-center gap-2">
            <Button type="button" variant="outline" size="sm" @click="downloadCsvTemplate">
              <Icon icon="lucide:download" class="h-4 w-4 mr-2" />
              Baixar modelo CSV
            </Button>
          </div>

          <!-- Upload CSV -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Arquivo CSV
            </label>
            <input
              ref="csvFileInput"
              type="file"
              accept=".csv,.txt"
              @change="handleCsvChange"
              required
              class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
            />
            <p v-if="selectedCsvFile" class="mt-2 text-xs text-green-600 dark:text-green-400">
              ✓ {{ selectedCsvFile.name }}
            </p>
            
            <!-- Exibir erro de importação -->
            <div v-if="csvImportError" class="mt-3 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg">
              <div class="flex items-start gap-2">
                <Icon icon="lucide:alert-circle" class="h-5 w-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" />
                <div class="flex-1">
                  <p class="text-sm font-semibold text-red-800 dark:text-red-200 mb-1">
                    Erro ao importar CSV
                  </p>
                  <p class="text-xs text-red-700 dark:text-red-300 whitespace-pre-line">
                    {{ csvImportError }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <div class="flex justify-end gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
            <Button type="button" variant="outline" @click="cancelInvoiceUpload">
              Cancelar
            </Button>
            <Button type="submit" :disabled="importingCsv || !selectedCsvFile">
              <Icon
                v-if="importingCsv"
                icon="lucide:loader-2"
                class="h-4 w-4 mr-2 animate-spin"
              />
              Importar Notas Fiscais
            </Button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal de Edição de Nota Fiscal -->
    <div
      v-if="showEditInvoiceModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click.self="cancelEditInvoice"
    >
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
          Editar Nota Fiscal
        </h3>

        <form @submit.prevent="updateInvoice" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Número da NF <span class="text-red-600">*</span>
              </label>
              <Input v-model="editInvoiceForm.invoice_number" placeholder="Ex: 123456" required />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Valor (R$) <span class="text-red-600">*</span>
              </label>
              <Input v-model="editInvoiceForm.amount" type="number" step="0.01" placeholder="0.00" required />
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Data de Emissão
              </label>
              <Input v-model="editInvoiceForm.invoice_date" type="date" />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Data de Vencimento
              </label>
              <Input v-model="editInvoiceForm.due_date" type="date" />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Descrição/Observação
            </label>
            <textarea
              v-model="editInvoiceForm.description"
              rows="3"
              placeholder="Observações sobre esta nota fiscal..."
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            ></textarea>
          </div>

          <!-- Status de Pagamento -->
          <div class="flex items-center gap-4 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
            <label class="flex items-center gap-2 cursor-pointer">
              <input
                v-model="editInvoiceForm.is_paid"
                type="checkbox"
                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
              />
              <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                Nota fiscal paga
              </span>
            </label>
            
            <div v-if="editInvoiceForm.is_paid" class="flex-1">
              <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                Data do pagamento
              </label>
              <input
                v-model="editInvoiceForm.paid_at"
                type="date"
                class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
              />
            </div>
          </div>

          <div class="flex justify-end gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
            <Button type="button" variant="outline" @click="cancelEditInvoice">
              Cancelar
            </Button>
            <Button type="submit" :disabled="updatingInvoice">
              <Icon
                v-if="updatingInvoice"
                icon="lucide:loader-2"
                class="h-4 w-4 mr-2 animate-spin"
              />
              Salvar Alterações
            </Button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal de Upload de Documento -->
    <div
      v-if="showDocumentUploadModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click.self="showDocumentUploadModal = false"
    >
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 w-full max-w-md">
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
          Upload de Documento
        </h3>
        <form @submit.prevent="uploadDocument" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Selecione o arquivo
            </label>
            <input
              ref="documentFileInput"
              type="file"
              accept=".pdf,.doc,.docx"
              @change="handleDocumentChange"
              class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
            />
            <p v-if="selectedDocumentFile" class="mt-2 text-xs text-green-600 dark:text-green-400">
              ✓ {{ selectedDocumentFile.name }}
            </p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
              Formatos aceitos: PDF, DOC, DOCX (máx. 20MB)
            </p>
          </div>
          
          <div class="flex justify-end gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
            <Button type="button" variant="outline" @click="cancelDocumentUpload">
              Cancelar
            </Button>
            <Button type="submit" :disabled="!selectedDocumentFile || uploadingDocument">
              <Icon
                v-if="uploadingDocument"
                icon="lucide:loader-2"
                class="h-4 w-4 mr-2 animate-spin"
              />
              Enviar
            </Button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal de Criação de Alerta -->
    <div
      v-if="showAlertModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click.self="showAlertModal = false"
    >
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 w-full max-w-md">
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
          Criar Alerta de Vencimento
        </h3>
        <form @submit.prevent="createAlert" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Tipo de Alerta
            </label>
            <select
              v-model="alertForm.alert_type"
              class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-gray-100"
            >
              <option value="before_expiration">Antes do Vencimento</option>
              <option value="on_expiration">No Dia do Vencimento</option>
              <option value="after_expiration">Após o Vencimento</option>
            </select>
          </div>

          <div v-if="alertForm.alert_type === 'before_expiration'">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Quantos dias antes?
            </label>
            <Input
              v-model.number="alertForm.days_before"
              type="number"
              min="1"
              max="365"
              placeholder="Ex: 30"
            />
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
              O alerta será disparado X dias antes do vencimento
            </p>
          </div>

          <!-- Notificação por E-mail -->
          <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
            <div class="flex items-center gap-2 mb-4">
              <input
                id="send_email_checkbox"
                v-model="alertForm.send_email"
                type="checkbox"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
              />
              <label for="send_email_checkbox" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                Enviar notificação por e-mail
              </label>
            </div>

            <div v-if="alertForm.send_email" class="space-y-3">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  E-mail Principal
                </label>
                <div class="flex gap-2">
                  <Input
                    v-model="alertForm.email_to"
                    type="email"
                    placeholder="email@exemplo.com"
                    class="flex-1"
                    required
                  />
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    @click="loadUserEmail"
                    :disabled="loadingEmail"
                  >
                    <Icon
                      :icon="loadingEmail ? 'lucide:loader-2' : 'lucide:user'"
                      :class="['h-4 w-4', { 'animate-spin': loadingEmail }]"
                    />
                  </Button>
                </div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                  {{ emailSource === 'gmail_integration' ? 'E-mail da integração Gmail' : 'Clique no ícone para carregar seu e-mail' }}
                </p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Cópias (CC) <span class="text-gray-400 font-normal">- Opcional</span>
                </label>
                <div class="space-y-2">
                  <div
                    v-for="(email, index) in alertForm.email_cc"
                    :key="index"
                    class="flex gap-2"
                  >
                    <Input
                      v-model="alertForm.email_cc[index]"
                      type="email"
                      placeholder="email@exemplo.com"
                      class="flex-1"
                    />
                    <Button
                      type="button"
                      variant="outline"
                      size="sm"
                      @click="removeEmailCc(index)"
                    >
                      <Icon icon="lucide:x" class="h-4 w-4" />
                    </Button>
                  </div>
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    @click="addEmailCc"
                  >
                    <Icon icon="lucide:plus" class="h-4 w-4 mr-2" />
                    Adicionar E-mail
                  </Button>
                </div>
              </div>
            </div>
          </div>

          <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-700">
            <p class="text-sm text-blue-800 dark:text-blue-200">
              <Icon icon="lucide:info" class="h-4 w-4 inline-block mr-1" />
              O sistema verificará automaticamente e criará uma tarefa quando o alerta for disparado
            </p>
          </div>
          
          <div class="flex justify-end gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
            <Button type="button" variant="outline" @click="cancelAlertCreation">
              Cancelar
            </Button>
            <Button type="submit">
              <Icon icon="lucide:bell-plus" class="h-4 w-4 mr-2" />
              Criar Alerta
            </Button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/components/ui/button/Button.vue'
import Input from '@/components/ui/input/Input.vue'
import { Icon } from '@iconify/vue'
import DoughnutChart from '@/components/charts/DoughnutChart.vue'
import BarChart from '@/components/charts/BarChart.vue'
import { computed } from 'vue'

const props = defineProps({
  contract: Object,
  report: Object,
})

const activeTab = ref('info')

// Documentos
const showDocumentUploadModal = ref(false)
const selectedDocumentFile = ref(null)
const uploadingDocument = ref(false)

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
  is_paid: false,
  paid_at: '',
})
const invoiceUploadTab = ref('individual')
const selectedCsvFile = ref(null)
const importingCsv = ref(false)
const csvFileInput = ref(null)
const csvImportError = ref('')

// Edição de Nota Fiscal
const showEditInvoiceModal = ref(false)
const editInvoiceForm = ref({
  id: null,
  invoice_number: '',
  invoice_date: '',
  due_date: '',
  amount: '',
  description: '',
  is_paid: false,
  paid_at: '',
})
const updatingInvoice = ref(false)

// Alertas
const showAlertModal = ref(false)
const alertForm = ref({
  alert_type: 'before_expiration',
  days_before: 30,
  send_email: false,
  email_to: '',
  email_cc: [],
})
const loadingEmail = ref(false)
const emailSource = ref('')

// Comentários
const newComment = ref('')
const addingComment = ref(false)

const form = useForm({
  name: props.contract.name,
  my_role: props.contract.my_role,
  contract_type: props.contract.contract_type,
  contract_object: props.contract.contract_object,
  contract_number: props.contract.contract_number,
  contractor: props.contract.contractor,
  contractor_cpf_cnpj: props.contract.contractor_cpf_cnpj,
  contracted: props.contract.contracted,
  contracted_cpf_cnpj: props.contract.contracted_cpf_cnpj,
  start_date: props.contract.start_date,
  end_date: props.contract.end_date,
  auto_renewal: props.contract.auto_renewal,
  amount: props.contract.amount,
  currency: props.contract.currency || 'BRL',
  payment_terms: props.contract.payment_terms,
  status: props.contract.status,
  notes: props.contract.notes,
  // Invoice fields
  invoice_contact_email: props.contract.invoice_contact_email,
  invoice_contact_link: props.contract.invoice_contact_link,
  invoice_system: props.contract.invoice_system,
  invoice_description: props.contract.invoice_description,
  invoice_internal_notes: props.contract.invoice_internal_notes,
  invoice_recipient_name: props.contract.invoice_recipient_name,
  invoice_recipient_cnpj: props.contract.invoice_recipient_cnpj,
  invoice_state_registration: props.contract.invoice_state_registration,
  invoice_recipient_address: props.contract.invoice_recipient_address,
  invoice_service_code: props.contract.invoice_service_code,
  invoice_due_day: props.contract.invoice_due_day,
})

const loadingCnpjData = ref(false)
const cnpjApiMessage = ref('')

const fetchCnpjData = () => {
  const cnpj = form.invoice_recipient_cnpj?.replace(/\D/g, '')
  
  if (!cnpj || cnpj.length !== 14) {
    alert('Digite um CNPJ válido com 14 dígitos')
    return
  }

  fetchCnpjDataSilent()
}

const fetchCnpjDataSilent = async () => {
  loadingCnpjData.value = true
  cnpjApiMessage.value = ''

  try {
    const response = await fetch(route('contracts.fetch-cnpj'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      },
      body: JSON.stringify({
        cnpj: form.invoice_recipient_cnpj?.replace(/\D/g, ''),
      }),
    })

    if (!response.ok) {
      throw new Error('Erro ao buscar dados do CNPJ')
    }

    const data = await response.json()

    if (data.success && data.data) {
      // Preencher campos
      if (data.data.inscricao_estadual) {
        form.invoice_state_registration = data.data.inscricao_estadual
      }
      if (data.data.endereco_completo) {
        form.invoice_recipient_address = data.data.endereco_completo
      }
      if (data.data.razao_social) {
        form.invoice_recipient_name = data.data.razao_social
      }

      cnpjApiMessage.value = 'Dados carregados com sucesso!'
      setTimeout(() => {
        cnpjApiMessage.value = ''
      }, 3000)
    }
  } catch (error) {
    console.error('Erro ao buscar CNPJ:', error)
  } finally {
    loadingCnpjData.value = false
  }
}

const submit = () => {
  form.put(route('contracts.update', props.contract.id))
}

// Funções auxiliares
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

// Funções de Notas Fiscais
const handlePdfChange = async (event) => {
  const file = event.target.files[0]
  selectedPdfFile.value = file
  
  if (!file) return
  
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
  
  try {
    const text = await file.text()
    const parser = new DOMParser()
    const xmlDoc = parser.parseFromString(text, 'text/xml')
    
    const parserError = xmlDoc.querySelector('parsererror')
    if (parserError) {
      console.warn('Erro ao fazer parse do XML')
      return
    }
    
    console.log('XML parsed successfully')
    
    let numero = xmlDoc.querySelector('nNF')?.textContent
    let dataEmissao = xmlDoc.querySelector('dhEmi, dEmi')?.textContent
    let valor = xmlDoc.querySelector('vNF')?.textContent
    let dataVencimento = null
    
    console.log('Tentativa 1 (NF-e):', { numero, dataEmissao, valor })
    
    if (!numero) {
      numero = xmlDoc.querySelector('Numero, NumeroNfse')?.textContent
      console.log('Tentativa 2 (NFS-e) - Numero:', numero)
    }
    if (!dataEmissao) {
      dataEmissao = xmlDoc.querySelector('DataEmissao, DtEmi')?.textContent
      console.log('Tentativa 2 (NFS-e) - DataEmissao:', dataEmissao)
    }
    if (!valor) {
      valor = xmlDoc.querySelector('ValorServicos, ValorTotal, Valor, ValorLiquidoNfse')?.textContent
      console.log('Tentativa 2 (NFS-e) - Valor:', valor)
    }
    
    const infosComplementares = xmlDoc.querySelector('InformacoesComplementares')?.textContent
    console.log('InformacoesComplementares:', infosComplementares)
    
    if (infosComplementares) {
      const vencimentoMatch = infosComplementares.match(/VENCIMENTO:\s*(\d{2})\/(\d{2})\/(\d{4})/i)
      if (vencimentoMatch) {
        const [, dia, mes, ano] = vencimentoMatch
        dataVencimento = `${ano}-${mes}-${dia}`
        console.log('Vencimento extraído:', dataVencimento)
      }
    }
    
    if (numero) {
      invoiceForm.value.invoice_number = numero
    }
    
    if (dataEmissao) {
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
    console.log('invoiceForm atualizado:', invoiceForm.value)
  } catch (error) {
    console.error('Erro ao extrair dados do XML:', error)
  }
}

const cancelInvoiceUpload = () => {
  showInvoiceUploadModal.value = false
  selectedPdfFile.value = null
  selectedXmlFile.value = null
  selectedCsvFile.value = null
  csvImportError.value = ''
  invoiceForm.value = {
    invoice_number: '',
    invoice_date: '',
    due_date: '',
    amount: '',
    description: '',
    is_paid: false,
    paid_at: '',
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
  
  console.log('Dados do invoiceForm antes do envio:', invoiceForm.value)
  
  if (invoiceForm.value.invoice_number) formData.append('invoice_number', invoiceForm.value.invoice_number)
  if (invoiceForm.value.invoice_date) formData.append('invoice_date', invoiceForm.value.invoice_date)
  if (invoiceForm.value.due_date) formData.append('due_date', invoiceForm.value.due_date)
  if (invoiceForm.value.amount) formData.append('amount', invoiceForm.value.amount)
  if (invoiceForm.value.description) formData.append('description', invoiceForm.value.description)
  formData.append('is_paid', invoiceForm.value.is_paid ? '1' : '0')
  if (invoiceForm.value.paid_at) formData.append('paid_at', invoiceForm.value.paid_at)

  console.log('FormData entries:')
  for (let pair of formData.entries()) {
    console.log(pair[0] + ': ' + pair[1])
  }

  router.post(route('contracts.invoices.upload', props.contract.id), formData, {
    onSuccess: () => {
      cancelInvoiceUpload()
      uploadingInvoice.value = false
    },
    onError: (errors) => {
      console.error('Erro ao enviar invoice:', errors)
      uploadingInvoice.value = false
    },
  })
}

const confirmDeleteInvoice = (invoiceId) => {
  if (confirm('Tem certeza que deseja excluir esta nota fiscal?\n\nEsta ação não pode ser desfeita.')) {
    router.delete(route('invoices.destroy', invoiceId))
  }
}

const openEditInvoice = (invoice) => {
  editInvoiceForm.value = {
    id: invoice.id,
    invoice_number: invoice.invoice_number,
    invoice_date: invoice.invoice_date,
    due_date: invoice.due_date,
    amount: invoice.amount,
    description: invoice.description || '',
    is_paid: invoice.is_paid,
    paid_at: invoice.paid_at ? invoice.paid_at.split('/').reverse().join('-') : '', // Converter DD/MM/YYYY para YYYY-MM-DD
  }
  showEditInvoiceModal.value = true
}

const cancelEditInvoice = () => {
  showEditInvoiceModal.value = false
  editInvoiceForm.value = {
    id: null,
    invoice_number: '',
    invoice_date: '',
    due_date: '',
    amount: '',
    description: '',
    is_paid: false,
    paid_at: '',
  }
}

const updateInvoice = () => {
  updatingInvoice.value = true
  
  router.put(route('invoices.update', editInvoiceForm.value.id), {
    invoice_number: editInvoiceForm.value.invoice_number,
    invoice_date: editInvoiceForm.value.invoice_date,
    due_date: editInvoiceForm.value.due_date,
    amount: editInvoiceForm.value.amount,
    description: editInvoiceForm.value.description,
    is_paid: editInvoiceForm.value.is_paid ? 1 : 0,
    paid_at: editInvoiceForm.value.paid_at,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      cancelEditInvoice()
      updatingInvoice.value = false
    },
    onError: (errors) => {
      console.error('Erro ao atualizar nota fiscal:', errors)
      updatingInvoice.value = false
    }
  })
}

// Watch para preencher data de pagamento automaticamente quando marcar como pago
watch(() => editInvoiceForm.value.is_paid, (isPaid) => {
  if (isPaid && !editInvoiceForm.value.paid_at && editInvoiceForm.value.due_date) {
    editInvoiceForm.value.paid_at = editInvoiceForm.value.due_date
  }
})

// Funções de Documentos
const handleDocumentChange = (event) => {
  selectedDocumentFile.value = event.target.files[0]
}

const cancelDocumentUpload = () => {
  showDocumentUploadModal.value = false
  selectedDocumentFile.value = null
}

const uploadDocument = () => {
  if (!selectedDocumentFile.value) return

  uploadingDocument.value = true
  const formData = new FormData()
  formData.append('document', selectedDocumentFile.value)

  router.post(route('contracts.documents.upload', props.contract.id), formData, {
    onSuccess: () => {
      cancelDocumentUpload()
      uploadingDocument.value = false
    },
    onError: (errors) => {
      console.error('Erro ao enviar documento:', errors)
      uploadingDocument.value = false
    },
  })
}

const confirmDeleteDocument = (documentId) => {
  if (confirm('Tem certeza que deseja excluir este documento?\n\nEsta ação não pode ser desfeita.')) {
    router.delete(route('contracts.documents.destroy', documentId))
  }
}

// Funções de Alertas
const getAlertLabel = (alert) => {
  switch (alert.alert_type) {
    case 'before_expiration':
      return `${alert.days_before} dias antes do vencimento`
    case 'on_expiration':
      return 'No dia do vencimento'
    case 'after_expiration':
      return 'Após o vencimento'
    default:
      return alert.alert_type
  }
}

// Carregar e-mail do usuário
const loadUserEmail = async () => {
  loadingEmail.value = true
  try {
    const response = await fetch(route('contracts.get-user-email'))
    const data = await response.json()
    alertForm.value.email_to = data.email
    emailSource.value = data.source
  } catch (error) {
    console.error('Erro ao carregar e-mail:', error)
  } finally {
    loadingEmail.value = false
  }
}

// Gerenciar cópias de e-mail
const addEmailCc = () => {
  alertForm.value.email_cc.push('')
}

const removeEmailCc = (index) => {
  alertForm.value.email_cc.splice(index, 1)
}

const createAlert = () => {
  const data = {
    alert_type: alertForm.value.alert_type,
    send_email: alertForm.value.send_email,
  }
  
  if (alertForm.value.alert_type === 'before_expiration') {
    data.days_before = alertForm.value.days_before
  }

  if (alertForm.value.send_email) {
    data.email_to = alertForm.value.email_to
    // Filtrar e-mails vazios
    data.email_cc = alertForm.value.email_cc.filter(email => email.trim() !== '')
  }

  router.post(route('contracts.alerts.store', props.contract.id), data, {
    onSuccess: () => {
      cancelAlertCreation()
    },
    onError: (errors) => {
      console.error('Erro ao criar alerta:', errors)
    },
  })
}

const cancelAlertCreation = () => {
  showAlertModal.value = false
  alertForm.value = {
    alert_type: 'before_expiration',
    days_before: 30,
    send_email: false,
    email_to: '',
    email_cc: [],
  }
  emailSource.value = ''
}

const toggleAlert = (alert) => {
  router.post(route('contracts.alerts.toggle', alert.id), {}, {
    preserveScroll: true,
  })
}

const createTaskFromAlert = (alert) => {
  if (confirm('Criar tarefa no Kanban para este alerta?')) {
    router.post(route('contract-alerts.create-task', alert.id), {}, {
      preserveScroll: true,
    })
  }
}

const confirmDeleteAlert = (alertId) => {
  if (confirm('Tem certeza que deseja excluir este alerta?\n\nEsta ação não pode ser desfeita.')) {
    router.delete(route('contracts.alerts.destroy', alertId))
  }
}

// Funções de Comentários
const addComment = () => {
  if (!newComment.value.trim()) return

  addingComment.value = true
  
  router.post(route('contracts.comments.store', props.contract.id), {
    comment: newComment.value,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      newComment.value = ''
      addingComment.value = false
    },
    onError: (errors) => {
      console.error('Erro ao adicionar comentário:', errors)
      addingComment.value = false
    },
  })
}

const confirmDeleteComment = (commentId) => {
  if (confirm('Tem certeza que deseja excluir este comentário?\n\nEsta ação não pode ser desfeita.')) {
    router.delete(route('contracts.comments.destroy', commentId), {
      preserveScroll: true,
    })
  }
}

// Funções de importação CSV
const handleCsvChange = (event) => {
  selectedCsvFile.value = event.target.files[0]
  csvImportError.value = '' // Limpar erros anteriores
}

const importInvoicesCsv = () => {
  console.log('importInvoicesCsv chamada', {
    selectedCsvFile: selectedCsvFile.value,
    contractId: props.contract.id
  })
  
  if (!selectedCsvFile.value) {
    console.error('Nenhum arquivo CSV selecionado')
    return
  }
  
  csvImportError.value = '' // Limpar erros anteriores
  importingCsv.value = true
  const formData = new FormData()
  formData.append('csv_file', selectedCsvFile.value)
  
  console.log('Enviando CSV para backend...')
  
  router.post(route('contracts.invoices.import-csv', props.contract.id), formData, {
    preserveScroll: true,
    onSuccess: () => {
      console.log('CSV importado com sucesso!')
      selectedCsvFile.value = null
      importingCsv.value = false
      csvImportError.value = ''
      showInvoiceUploadModal.value = false
      if (csvFileInput.value) {
        csvFileInput.value.value = ''
      }
    },
    onError: (errors) => {
      console.error('Erro ao importar CSV:', errors)
      importingCsv.value = false
      // Capturar mensagem de erro
      if (errors.csv) {
        csvImportError.value = errors.csv
      } else {
        csvImportError.value = 'Erro ao importar CSV. Verifique o formato do arquivo.'
      }
    }
  })
}

const downloadCsvTemplate = () => {
  const csv = 'numero_nf;valor;data_emissao;data_vencimento;descricao;pago;data_pagamento\n' +
              '123456;1500.50;01/01/2024;15/01/2024;Serviços de consultoria;sim;10/01/2024\n' +
              '123457;2750.00;05/01/2024;20/01/2024;Manutenção mensal;não;'
  
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
  const link = document.createElement('a')
  link.href = URL.createObjectURL(blob)
  link.download = 'modelo_notas_fiscais.csv'
  link.click()
}
</script>
