<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { computed, inject } from 'vue'
import AuthenticationCardLogo from '@/components/LogoRedirect.vue'
import Button from '@/components/ui/button/Button.vue'
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import { useSeoMetaTags } from '@/composables/useSeoMetaTags.js'

const props = defineProps({
  status: String,
})

useSeoMetaTags({
  title: 'Verificação de Email',
})

const route = inject('route')

const form = useForm({})

function submit() {
  form.post(route('verification.send'))
}

const verificationLinkSent = computed(
  () => props.status === 'verification-link-sent',
)
</script>

<template>
  <div class="flex min-h-screen flex-col items-center justify-center">
    <Card class="mx-auto max-w-lg">
      <CardHeader>
        <CardTitle class="flex justify-center">
          <AuthenticationCardLogo />
        </CardTitle>
        <CardDescription class="text-center text-2xl">
          Verifique seu email
        </CardDescription>
      </CardHeader>

      <CardContent>
        <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
          Antes de continuar, você poderia verificar seu endereço de email
          clicando no link que acabamos de enviar para você? Se você não
          recebeu o email, teremos prazer em enviar outro.
        </div>

        <div
          v-if="verificationLinkSent"
          class="mb-4 text-sm font-medium text-green-600 dark:text-green-400"
        >
          Um novo link de verificação foi enviado para o endereço de email
          que você forneceu nas configurações do seu perfil.
        </div>

        <form @submit.prevent="submit">
          <div class="mt-4 flex items-center justify-between">
            <Button
              :class="{ 'opacity-25': form.processing }"
              :disabled="form.processing"
            >
              Reenviar Email de Verificação
            </Button>

            <div>
              <Link
                :href="route('profile.show')"
                class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:text-gray-400 dark:hover:text-gray-100 dark:focus:ring-offset-gray-800"
              >
                Editar Perfil
              </Link>

              <Link
                :href="route('logout')"
                method="post"
                as="button"
                class="ms-2 rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:text-gray-400 dark:hover:text-gray-100 dark:focus:ring-offset-gray-800"
              >
                Sair
              </Link>
            </div>
          </div>
        </form>
      </CardContent>
    </Card>
  </div>
</template>
