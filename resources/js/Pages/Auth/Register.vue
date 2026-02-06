<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { inject, computed } from 'vue'
import InputError from '@/Components/InputError.vue'
import AuthenticationCardLogo from '@/Components/LogoRedirect.vue'
import SocialLoginButton from '@/Components/SocialLoginButton.vue'

import Button from '@/Components/ui/button/Button.vue'
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/Components/ui/card'
import Checkbox from '@/Components/ui/checkbox/Checkbox.vue'
import Input from '@/Components/ui/input/Input.vue'
import Label from '@/Components/ui/label/Label.vue'
import { useSeoMetaTags } from '@/composables/useSeoMetaTags.js'

useSeoMetaTags({
  title: 'Cadastrar-se',
})

const props = defineProps({
  availableOauthProviders: Object,
})

const route = inject('route')
const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  terms: false,
})

const hasOauthProviders = computed(
  () => Object.keys(props.availableOauthProviders || {}).length > 0,
)

function submit() {
  form.post(route('register'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
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
          Crie sua conta
        </CardDescription>
      </CardHeader>

      <CardContent>
        <div v-if="hasOauthProviders" class="mb-6">
          <div class="grid gap-2">
            <SocialLoginButton
              v-for="provider in availableOauthProviders"
              :key="provider.slug"
              :provider="provider"
              :disabled="form.processing"
            />
          </div>
          <div class="relative mt-6">
            <div class="absolute inset-0 flex items-center">
              <span class="w-full border-t" />
            </div>
            <div class="relative flex justify-center text-xs uppercase">
              <span class="bg-background px-2 text-muted-foreground">
                Ou cadastre-se com email
              </span>
            </div>
          </div>
        </div>

        <form @submit.prevent="submit">
          <div class="grid gap-4">
            <!-- Name -->
            <div class="grid gap-2">
              <Label for="name">Nome</Label>
              <Input
                id="name"
                v-model="form.name"
                type="text"
                required
                autofocus
                autocomplete="name"
              />
              <InputError :message="form.errors.name" />
            </div>

            <!-- Email -->
            <div class="grid gap-2">
              <Label for="email">Email</Label>
              <Input
                id="email"
                v-model="form.email"
                type="email"
                required
                autocomplete="username"
              />
              <InputError :message="form.errors.email" />
            </div>

            <!-- Password -->
            <div class="grid gap-2">
              <Label for="password">Senha</Label>
              <Input
                id="password"
                v-model="form.password"
                type="password"
                required
                autocomplete="new-password"
              />
              <InputError :message="form.errors.password" />
            </div>

            <!-- Confirm Password -->
            <div class="grid gap-2">
              <Label for="password_confirmation">Confirmar Senha</Label>
              <Input
                id="password_confirmation"
                v-model="form.password_confirmation"
                type="password"
                required
                autocomplete="new-password"
              />
              <InputError
                :message="form.errors.password_confirmation"
              />
            </div>

            <!-- Terms -->
            <div
              v-if="
                $page.props.jetstream
                  .hasTermsAndPrivacyPolicyFeature
              "
            >
              <div class="flex items-center space-x-2">
                <Checkbox
                  id="terms"
                  v-model:checked="form.terms"
                  name="terms"
                  required
                />
                <label
                  for="terms"
                  class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                >
                  Eu concordo com os
                  <a
                    target="_blank"
                    :href="route('terms.show')"
                    class="rounded-md text-sm underline"
                  >Termos de Serviço</a>
                  e
                  <a
                    target="_blank"
                    :href="route('policy.show')"
                    class="rounded-md text-sm underline"
                  >Política de Privacidade</a>
                </label>
              </div>
              <InputError :message="form.errors.terms" />
            </div>

            <div class="flex items-center justify-end gap-4">
              <Link
                :href="route('login')"
                class="text-sm underline"
              >
                Já tem uma conta?
              </Link>

              <Button
                :class="{ 'opacity-25': form.processing }"
                :disabled="form.processing"
              >
                Cadastrar
              </Button>
            </div>
          </div>
        </form>
      </CardContent>
    </Card>
  </div>
</template>
