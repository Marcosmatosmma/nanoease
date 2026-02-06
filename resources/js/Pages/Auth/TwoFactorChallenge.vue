<script setup>
import { useForm } from '@inertiajs/vue3'
import { inject, nextTick, ref } from 'vue'
import InputError from '@/components/InputError.vue'
import AuthenticationCardLogo from '@/components/LogoRedirect.vue'
import Button from '@/components/ui/button/Button.vue'
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import Input from '@/components/ui/input/Input.vue'

import Label from '@/components/ui/label/Label.vue'
import { useSeoMetaTags } from '@/composables/useSeoMetaTags.js'

useSeoMetaTags({
  title: 'Confirmação de Dois Fatores',
})

const route = inject('route')
const recovery = ref(false)

const form = useForm({
  code: '',
  recovery_code: '',
})

const recoveryCodeInput = ref(null)
const codeInput = ref(null)

async function toggleRecovery() {
  recovery.value ^= true

  await nextTick()

  if (recovery.value) {
    recoveryCodeInput.value.focus()
    form.code = ''
  }
  else {
    codeInput.value.focus()
    form.recovery_code = ''
  }
}

function submit() {
  form.post(route('two-factor.login'))
}
</script>

<template>
  <div class="flex min-h-screen flex-col items-center justify-center">
    <Card class="mx-auto max-w-lg">
      <CardHeader>
        <CardTitle class="flex justify-center">
          <AuthenticationCardLogo />
        </CardTitle>
        <CardDescription class="text-center text-2xl">
          Autenticação de dois fatores
        </CardDescription>
      </CardHeader>

      <CardContent>
        <div class="mb-4 text-sm">
          <template v-if="!recovery">
            Por favor, confirme o acesso à sua conta digitando o
            código de autenticação fornecido pelo seu aplicativo
            autenticador.
          </template>

          <template v-else>
            Por favor, confirme o acesso à sua conta digitando um dos
            seus códigos de recuperação de emergência.
          </template>
        </div>

        <form @submit.prevent="submit">
          <div v-if="!recovery">
            <Label for="code">Código</Label>
            <Input
              id="code"
              ref="codeInput"
              v-model="form.code"
              type="text"
              inputmode="numeric"
              class="mt-1 block w-full"
              autofocus
              autocomplete="one-time-code"
            />
            <InputError class="mt-2" :message="form.errors.code" />
          </div>

          <div v-else>
            <Label for="recovery_code">Código de Recuperação</Label>
            <Input
              id="recovery_code"
              ref="recoveryCodeInput"
              v-model="form.recovery_code"
              type="text"
              class="mt-1 block w-full"
              autocomplete="one-time-code"
            />
            <InputError
              class="mt-2"
              :message="form.errors.recovery_code"
            />
          </div>

          <div class="mt-4 flex items-center justify-end">
            <button
              type="button"
              class="cursor-pointer text-sm"
              @click.prevent="toggleRecovery"
            >
              <template v-if="!recovery">
                Usar um código de recuperação
              </template>

              <template v-else>
                Usar um código de autenticação
              </template>
            </button>

            <Button
              class="ms-4"
              :class="{ 'opacity-25': form.processing }"
              :disabled="form.processing"
            >
              Entrar
            </Button>
          </div>
        </form>
      </CardContent>
    </Card>
  </div>
</template>
