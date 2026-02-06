<script setup>
import { useForm } from '@inertiajs/vue3'
import { inject, ref } from 'vue'
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

const route = inject('route')
const form = useForm({
  password: '',
})

useSeoMetaTags({
  title: 'Confirmar Senha',
})

const passwordInput = ref(null)

function submit() {
  form.post(route('password.confirm'), {
    onFinish: () => {
      form.reset()
      passwordInput.value.focus()
    },
  })
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
          Confirme sua senha
        </CardDescription>
      </CardHeader>

      <CardContent>
        <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
          Esta é uma área segura da aplicação. Por favor, confirme
          sua senha antes de continuar.
        </div>

        <form @submit.prevent="submit">
          <div class="grid gap-4">
            <div class="grid gap-2">
              <Label for="password">Senha</Label>
              <Input
                id="password"
                ref="passwordInput"
                v-model="form.password"
                type="password"
                required
                autocomplete="current-password"
                autofocus
              />
              <InputError :message="form.errors.password" />
            </div>

            <Button
              type="submit"
              :class="{ 'opacity-25': form.processing }"
              :disabled="form.processing"
            >
              Confirmar
            </Button>
          </div>
        </form>
      </CardContent>
    </Card>
  </div>
</template>
