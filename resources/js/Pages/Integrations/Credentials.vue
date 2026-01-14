<script setup>
import { Icon } from '@iconify/vue'
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import Button from '@/components/ui/button/Button.vue'
import Card from '@/components/ui/card/Card.vue'
import CardContent from '@/components/ui/card/CardContent.vue'
import CardDescription from '@/components/ui/card/CardDescription.vue'
import CardHeader from '@/components/ui/card/CardHeader.vue'
import CardTitle from '@/components/ui/card/CardTitle.vue'
import Input from '@/components/ui/input/Input.vue'
import Label from '@/components/ui/label/Label.vue'
import InputError from '@/components/InputError.vue'

const props = defineProps({
  provider: {
    type: String,
    required: true,
  },
  redirectHint: {
    type: String,
    required: true,
  },
})

const form = useForm({
  client_id: '',
  client_secret: '',
  redirect_uri: props.redirectHint,
})

function submit() {
  form.post(route('integrations.gmail.credentials.store'))
}
</script>

<template>
  <AppLayout title="Credenciais do Gmail">
    <div class="space-y-6 max-w-3xl">
      <header class="space-y-2">
        <p class="text-sm text-muted-foreground">Integrações</p>
        <h1 class="text-3xl font-semibold">Conectar Gmail</h1>
        <p class="text-muted-foreground">
          Salve o Client ID e Client Secret do seu projeto Google. Use a mesma redirect URI configurada no console.
        </p>
      </header>

      <Card>
        <CardHeader>
          <CardTitle>Credenciais do {{ provider === 'gmail' ? 'Gmail' : provider }}</CardTitle>
          <CardDescription>
            Redirect URI sugerida: <span class="font-mono">{{ redirectHint }}</span>
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="space-y-2">
            <Label for="client_id">Client ID</Label>
            <Input
              id="client_id"
              v-model="form.client_id"
              type="text"
              autocomplete="off"
              placeholder="xxxxxxxx.apps.googleusercontent.com"
            />
            <InputError :message="form.errors.client_id" />
          </div>

          <div class="space-y-2">
            <Label for="client_secret">Client Secret</Label>
            <Input
              id="client_secret"
              v-model="form.client_secret"
              type="password"
              autocomplete="off"
              placeholder="••••••••••"
            />
            <InputError :message="form.errors.client_secret" />
          </div>

          <div class="space-y-2">
            <Label for="redirect_uri">Redirect URI</Label>
            <Input
              id="redirect_uri"
              v-model="form.redirect_uri"
              type="text"
              autocomplete="off"
            />
            <InputError :message="form.errors.redirect_uri" />
            <p class="text-xs text-muted-foreground flex items-center gap-1">
              <Icon icon="lucide:info" class="h-3 w-3" />
              Cadastre exatamente essa URI no console do Google.
            </p>
          </div>

          <div class="flex gap-2">
            <Button :as="Link" :href="route('integrations.index')" variant="ghost">
              Cancelar
            </Button>
            <Button :disabled="form.processing" @click.prevent="submit">
              Salvar credenciais
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
