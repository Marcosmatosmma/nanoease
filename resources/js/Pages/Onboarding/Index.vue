<script setup>
import { useForm } from '@inertiajs/vue3'
import Button from '@/Components/ui/button/Button.vue'
import Input from '@/Components/ui/input/Input.vue'
import Label from '@/Components/ui/label/Label.vue'
import Select from '@/Components/ui/select/Select.vue'
import SelectContent from '@/Components/ui/select/SelectContent.vue'
import SelectItem from '@/Components/ui/select/SelectItem.vue'
import SelectTrigger from '@/Components/ui/select/SelectTrigger.vue'
import SelectValue from '@/Components/ui/select/SelectValue.vue'
import AuthenticationCardLogo from '@/Components/LogoRedirect.vue'
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/Components/ui/card'

const form = useForm({
  segment: '',
  phone: '',
  company_size: '',
})

const submit = () => {
  form.post(route('onboarding.store'))
}
</script>

<template>
  <div class="flex min-h-screen flex-col items-center justify-center bg-gray-100 dark:bg-gray-900">
    <Card class="w-full max-w-md">
      <CardHeader>
        <CardTitle class="flex justify-center">
          <AuthenticationCardLogo />
        </CardTitle>
        <CardDescription class="text-center">
          Por favor, complete seu perfil para continuar para o painel.
        </CardDescription>
      </CardHeader>

      <CardContent>
        <form @submit.prevent="submit" class="space-y-6">
          <div>
            <Label for="segment">Segmento</Label>
            <Select v-model="form.segment">
              <SelectTrigger>
                <SelectValue placeholder="Selecione seu segmento" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="saas">SaaS</SelectItem>
                <SelectItem value="ecommerce">E-commerce</SelectItem>
                <SelectItem value="agency">Agência</SelectItem>
                <SelectItem value="freelancer">Freelancer</SelectItem>
                <SelectItem value="other">Outro</SelectItem>
              </SelectContent>
            </Select>
            <div v-if="form.errors.segment" class="text-sm text-red-500 mt-1">
              {{ form.errors.segment }}
            </div>
          </div>

          <div>
            <Label for="phone">Telefone (WhatsApp)</Label>
            <Input
              id="phone"
              v-model="form.phone"
              type="tel"
              placeholder="(00) 00000-0000"
              required
            />
            <div v-if="form.errors.phone" class="text-sm text-red-500 mt-1">
              {{ form.errors.phone }}
            </div>
          </div>

          <div>
            <Label for="company_size">Número de Colaboradores</Label>
            <Select v-model="form.company_size">
              <SelectTrigger>
                <SelectValue placeholder="Selecione o tamanho da empresa" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="1-5">1-5</SelectItem>
                <SelectItem value="6-20">6-20</SelectItem>
                <SelectItem value="21-50">21-50</SelectItem>
                <SelectItem value="50+">50+</SelectItem>
              </SelectContent>
            </Select>
            <div v-if="form.errors.company_size" class="text-sm text-red-500 mt-1">
              {{ form.errors.company_size }}
            </div>
          </div>

          <div class="flex items-center justify-end mt-4">
            <Button :disabled="form.processing" class="w-full">
              Concluir Cadastro
            </Button>
          </div>
        </form>
      </CardContent>
    </Card>
  </div>
</template>
