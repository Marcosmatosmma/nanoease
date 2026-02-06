<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3'
import { computed, inject, onMounted } from 'vue'
import { toast } from 'vue-sonner'
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
import { Toaster } from '@/Components/ui/sonner'
import { useSeoMetaTags } from '@/composables/useSeoMetaTags.js'
import 'vue-sonner/style.css'

const props = defineProps({
  canResetPassword: Boolean,
  status: String,
  availableOauthProviders: Object,
})

const page = usePage()
const route = inject('route')

// Form state
const form = useForm({
  email: '',
  password: '',
  remember: false,
})

// Computed
const hasOauthProviders = computed(
  () => Object.keys(props.availableOauthProviders || {}).length > 0,
)

// Methods
function submit() {
  form
    .transform(data => ({
      ...data,
      remember: data.remember ? 'on' : '',
    }))
    .post(route('login'), {
      onFinish: () => form.reset('password'),
    })
}

// Lifecycle
onMounted(() => {
  if (page.props.flash.error) {
    toast.error(page.props.flash.error)
  }

  if (page.props.flash.success) {
    toast.success(page.props.flash.success)
  }
})

// SEO
useSeoMetaTags({
  title: 'Entrar',
})
</script>

<template>
  <Toaster position="top-center" />

  <div
    class="flex min-h-screen flex-col items-center justify-center bg-linear-to-b from-background/50 to-background"
  >
    <Card
      class="mx-auto w-[420px] shadow-lg transition-all duration-300 hover:shadow-xl"
    >
      <!-- Header -->
      <CardHeader>
        <CardTitle class="flex justify-center">
          <AuthenticationCardLogo />
        </CardTitle>
        <CardDescription class="text-center text-2xl font-light">
          Bem-vindo de volta
        </CardDescription>
      </CardHeader>

      <CardContent>
        <!-- Status Message -->
        <div
          v-if="status"
          class="mb-4 text-sm font-medium text-green-600"
        >
          {{ status }}
        </div>

        <form @submit.prevent="submit">
          <div class="grid gap-4">
            <!-- Email -->
            <div class="grid gap-2">
              <Label for="email">Email</Label>
              <Input
                id="email"
                v-model="form.email"
                type="email"
                placeholder="nome@exemplo.com"
                required
                autofocus
                autocomplete="username"
              />
              <InputError
                :message="form.errors.email"
              />
            </div>

            <!-- Password -->
            <div class="grid gap-2">
              <div
                class="flex items-center justify-between"
              >
                <Label for="password">Senha</Label>
                <Link
                  v-if="canResetPassword"
                  :href="
                    route('password.request')
                  "
                  class="text-sm text-muted-foreground hover:text-primary hover:underline underline-offset-4"
                >
                  Esqueceu a senha?
                </Link>
              </div>
              <Input
                id="password"
                v-model="form.password"
                type="password"
                required
                autocomplete="current-password"
              />
              <InputError
                :message="
                  form.errors.password
                "
              />
            </div>

            <!-- Remember Me -->
            <div class="flex items-center space-x-2">
              <Checkbox
                id="remember"
                v-model:checked="
                  form.remember
                "
                name="remember"
              />
              <label
                for="remember"
                class="text-sm text-muted-foreground"
              >
                Lembrar de mim
              </label>
            </div>

            <Button
              type="submit"
              class="w-full"
              :class="{
                'opacity-75':
                  form.processing,
              }"
              :disabled="form.processing"
            >
              {{
                form.processing
                  ? "Entrando..."
                  : "Entrar"
              }}
            </Button>
          </div>
        </form>

        <!-- OAuth Section -->
        <div v-if="hasOauthProviders" class="mt-6">
          <div class="relative">
            <div class="absolute inset-0 flex items-center">
              <span class="w-full border-t" />
            </div>
            <div
              class="relative flex justify-center text-xs uppercase"
            >
              <span
                class="bg-background px-2 text-muted-foreground"
              >
                Ou continue com
              </span>
            </div>
          </div>

          <div class="mt-6 grid gap-2">
            <SocialLoginButton
              v-for="provider in availableOauthProviders"
              :key="provider.slug"
              :provider="provider"
              :disabled="form.processing"
            />
          </div>
        </div>

        <!-- Sign Up Link -->
        <div class="mt-6 text-center text-sm text-muted-foreground">
          Não tem uma conta?
          <Link
            :href="route('register')"
            class="font-medium text-primary hover:underline underline-offset-4"
          >
            Cadastrar-se
          </Link>
        </div>
      </CardContent>
    </Card>
  </div>
</template>
